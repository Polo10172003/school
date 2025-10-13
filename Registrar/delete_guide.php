<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../includes/registrar_guides.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registrar_dashboard.php');
    exit;
}

/**
 * Redirect helper for deletion flow.
 */
function registrar_guides_delete_redirect(string $msg, ?string $reason = null): void
{
    $location = 'registrar_dashboard.php?msg=' . urlencode($msg);
    if ($reason !== null) {
        $location .= '&reason=' . urlencode($reason);
    }
    header('Location: ' . $location);
    exit;
}

$guideId = isset($_POST['guide_id']) ? (int) $_POST['guide_id'] : 0;

if ($guideId <= 0) {
    registrar_guides_delete_redirect('guide_delete_error', 'invalid_id');
}

try {
    registrar_guides_ensure_schema($conn);
    $guide = registrar_guides_find($conn, $guideId);
} catch (Throwable $e) {
    error_log($e->getMessage());
    registrar_guides_delete_redirect('guide_delete_error', 'database_error');
}

if (!$guide) {
    registrar_guides_delete_redirect('guide_delete_error', 'not_found');
}

// Attempt to delete the stored file if present on disk.
try {
    $uploadDir = registrar_guides_upload_dir();
    $diskPath = $uploadDir . '/' . $guide['file_name'];
    if (is_file($diskPath)) {
        @unlink($diskPath);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    registrar_guides_delete_redirect('guide_delete_error', 'storage_issue');
}

if (!registrar_guides_delete($conn, $guideId)) {
    registrar_guides_delete_redirect('guide_delete_error', 'database_error');
}

registrar_guides_delete_redirect('guide_deleted');
