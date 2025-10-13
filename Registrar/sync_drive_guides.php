<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../includes/google_drive_client.php';
require_once __DIR__ . '/../includes/google_drive_sync.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registrar_dashboard.php');
    exit;
}

$gradeLevels = [
    "Preschool","Pre-Prime 1","Pre-Prime 2","Kindergarten",
    "Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6",
    "Grade 7","Grade 8","Grade 9","Grade 10",
    "Grade 11","Grade 12"
];

try {
    $config = google_drive_config();
    $folderId = $config['folder_id'] ?? '';
    if ($folderId === '') {
        throw new RuntimeException('Google Drive folder_id is missing from config/google_drive.php.');
    }

    $driveService = google_drive_service();
    $summary = registrar_sync_drive_guides($conn, $driveService, $folderId, $gradeLevels);

    $_SESSION['guide_sync_summary'] = $summary;
    header('Location: registrar_dashboard.php?msg=guide_sync_success');
    exit;
} catch (Throwable $e) {
    error_log('Drive sync failed: ' . $e->getMessage());
    $_SESSION['guide_sync_error'] = $e->getMessage();
    header('Location: registrar_dashboard.php?msg=guide_sync_error');
    exit;
}

