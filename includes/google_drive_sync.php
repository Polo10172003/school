<?php

declare(strict_types=1);

require_once __DIR__ . '/registrar_guides.php';

/**
 * Attempt to map a Drive file name to a known grade level.
 */
function registrar_guess_grade_from_filename(string $filename, array $gradeLevels): ?string
{
    $normalized = strtolower($filename);

    foreach ($gradeLevels as $grade) {
        if (strpos($normalized, strtolower($grade)) !== false) {
            return $grade;
        }
    }

    return null;
}

/**
 * Synchronize Excel files from Google Drive into the registrar_guides table.
 *
 * @return array<string, mixed> Summary data containing counts and errors.
 */
function registrar_sync_drive_guides(
    mysqli $conn,
    Google_Service_Drive $drive,
    string $folderId,
    array $gradeLevels
): array {
    $added = 0;
    $updated = 0;
    $skipped = 0;
    $errors = [];

    $pageToken = null;

    do {
        try {
            $response = $drive->files->listFiles([
                'q' => sprintf(
                    "'%s' in parents and mimeType in ('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-excel') and trashed = false",
                    $folderId
                ),
                'fields' => 'files(id, name, size, modifiedTime), nextPageToken',
                'pageSize' => 100,
                'pageToken' => $pageToken,
            ]);
        } catch (Throwable $e) {
            $errors[] = 'Drive API error: ' . $e->getMessage();
            break;
        }

        /** @var array<int, Google_Service_Drive_DriveFile> $files */
        $files = $response->getFiles() ?? [];

        foreach ($files as $file) {
            $fileId = $file->getId();
            $fileName = $file->getName();
            $fileSize = (int) ($file->getSize() ?? 0);

            $gradeLevel = registrar_guess_grade_from_filename($fileName, $gradeLevels);
            if ($gradeLevel === null) {
                $skipped++;
                $errors[] = sprintf('Skipped "%s" (no grade detected in filename).', $fileName);
                continue;
            }

            try {
                $existing = registrar_guides_find_by_drive_id($conn, $fileId);
                if ($existing) {
                    if (
                        registrar_guides_update_drive(
                            $conn,
                            (int) $existing['id'],
                            $gradeLevel,
                            $fileName,
                            $fileSize
                        )
                    ) {
                        $updated++;
                    }
                } else {
                    registrar_guides_insert(
                        $conn,
                        $gradeLevel,
                        'drive:' . $fileId,
                        $fileName,
                        $fileSize,
                        null,
                        'drive',
                        $fileId
                    );
                    $added++;
                }
            } catch (Throwable $e) {
                $errors[] = sprintf('Failed to record "%s": %s', $fileName, $e->getMessage());
            }
        }

        $pageToken = $response->getNextPageToken();
    } while ($pageToken);

    return [
        'added' => $added,
        'updated' => $updated,
        'skipped' => $skipped,
        'errors' => $errors,
    ];
}
