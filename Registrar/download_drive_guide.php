<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../includes/google_drive_client.php';
require_once __DIR__ . '/../includes/registrar_guides.php';

$driveId = $_GET['id'] ?? '';

if ($driveId === '') {
    http_response_code(400);
    echo 'Missing Google Drive file id.';
    exit;
}

try {
    registrar_guides_ensure_schema($conn);
    $guide = registrar_guides_find_by_drive_id($conn, $driveId);
    if (!$guide) {
        throw new RuntimeException('Guide not found.');
    }

    $config = google_drive_config();
    $driveService = google_drive_service();

    $response = $driveService->files->get($driveId, [
        'alt' => 'media',
    ]);

    $contentType = $response->getHeaderLine('Content-Type') ?: 'application/octet-stream';
    $fileName = $guide['original_name'] ?? ($driveId . '.xlsx');

    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');

    echo $response->getBody();
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Unable to download file: ' . htmlspecialchars($e->getMessage());
}

