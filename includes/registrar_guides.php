<?php

declare(strict_types=1);

/**
 * Ensure the upload directory for registrar guides exists and return its absolute path.
 */
function registrar_guides_upload_dir(): string
{
    $uploadDir = __DIR__ . '/../uploads/registrar_guides';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Unable to create registrar guides upload directory.');
        }
    }
    return $uploadDir;
}

/**
 * Build the public path that can be used to download a stored guide.
 */
function registrar_guides_public_path(string $fileName): string
{
    return 'uploads/registrar_guides/' . $fileName;
}

/**
 * Creates the registrar_guides table if it does not already exist.
 */
function registrar_guides_ensure_schema(mysqli $conn): void
{
    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS registrar_guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_level VARCHAR(64) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,w
    file_size BIGINT UNSIGNED NOT NULL,
    uploaded_by VARCHAR(100) DEFAULT NULL,
    source ENUM('manual','drive') NOT NULL DEFAULT 'manual',
    drive_file_id VARCHAR(128) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;

    if (!$conn->query($sql)) {
        throw new RuntimeException('Unable to ensure registrar guides table: ' . $conn->error);
    }
}

/**
 * Insert a manual or drive-linked guide and return its new primary key.
 */
function registrar_guides_insert(
    mysqli $conn,
    string $gradeLevel,
    string $fileName,
    string $originalName,
    int $fileSize,
    ?string $uploadedBy = null,
    string $source = 'manual',
    ?string $driveFileId = null
): int {
    $stmt = $conn->prepare(
        'INSERT INTO registrar_guides (grade_level, file_name, original_name, file_size, uploaded_by, source, drive_file_id)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('sssisss', $gradeLevel, $fileName, $originalName, $fileSize, $uploadedBy, $source, $driveFileId);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Unable to store registrar guide: ' . $error);
    }

    $newId = $stmt->insert_id;
    $stmt->close();

    return (int) $newId;
}

/**
 * Fetch guides, optionally filtered by grade level.
 *
 * @return array<int, array<string, mixed>>
 */
function registrar_guides_fetch_all(mysqli $conn, ?string $gradeLevel = null): array
{
    if ($gradeLevel) {
        $stmt = $conn->prepare(
            'SELECT * FROM registrar_guides WHERE grade_level = ? ORDER BY uploaded_at DESC'
        );
        if (!$stmt) {
            throw new RuntimeException('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param('s', $gradeLevel);
    } else {
        $stmt = $conn->prepare(
            'SELECT * FROM registrar_guides ORDER BY grade_level, uploaded_at DESC'
        );
        if (!$stmt) {
            throw new RuntimeException('Prepare failed: ' . $conn->error);
        }
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Unable to fetch registrar guides: ' . $error);
    }

    $result = $stmt->get_result();
    $files = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $files ?: [];
}

/**
 * Find a guide record by primary key.
 */
function registrar_guides_find(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM registrar_guides WHERE id = ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Unable to fetch registrar guide: ' . $error);
    }

    $result = $stmt->get_result();
    $guide = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $guide ?: null;
}

/**
 * Remove a guide record by id.
 */
function registrar_guides_delete(mysqli $conn, int $id): bool
{
    $stmt = $conn->prepare('DELETE FROM registrar_guides WHERE id = ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $id);
    $deleted = $stmt->execute();
    $stmt->close();

    return (bool) $deleted;
}

/**
 * Locate a guide using a Google Drive file id.
 */
function registrar_guides_find_by_drive_id(mysqli $conn, string $driveFileId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM registrar_guides WHERE drive_file_id = ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('s', $driveFileId);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Unable to locate registrar guide: ' . $error);
    }

    $result = $stmt->get_result();
    $guide = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $guide ?: null;
}

/**
 * Update metadata for an existing Drive-backed guide.
 */
function registrar_guides_update_drive(
    mysqli $conn,
    int $id,
    string $gradeLevel,
    string $originalName,
    int $fileSize
): bool {
    $stmt = $conn->prepare(
        'UPDATE registrar_guides SET grade_level = ?, original_name = ?, file_size = ?, uploaded_at = CURRENT_TIMESTAMP WHERE id = ?'
    );
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('ssii', $gradeLevel, $originalName, $fileSize, $id);
    $updated = $stmt->execute();
    $stmt->close();

    return (bool) $updated;
}
