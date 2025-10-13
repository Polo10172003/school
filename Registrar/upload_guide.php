<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../includes/registrar_guides.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registrar_dashboard.php');
    exit;
}

/**
 * Redirect helper with message parameters.
 */
function registrar_guides_redirect(string $msg, ?string $reason = null): void
{
    $location = 'registrar_dashboard.php?msg=' . urlencode($msg);
    if ($reason !== null) {
        $location .= '&reason=' . urlencode($reason);
    }
    header('Location: ' . $location);
    exit;
}

$gradeLevel = trim($_POST['grade_level'] ?? '');
$file = $_FILES['guide_file'] ?? null;

if ($gradeLevel === '') {
    registrar_guides_redirect('guide_upload_error', 'invalid_grade');
}

if (!$file || !isset($file['error'])) {
    registrar_guides_redirect('guide_upload_error', 'missing_file');
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    registrar_guides_redirect('guide_upload_error', 'upload_failure');
}

$originalName = $file['name'] ?? 'uploaded-file';
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedExtensions = ['xls', 'xlsx'];

if (!in_array($extension, $allowedExtensions, true)) {
    registrar_guides_redirect('guide_upload_error', 'invalid_type');
}

$allowedMimeTypes = [
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/octet-stream',
];

$finfoMime = mime_content_type($file['tmp_name']);
if ($finfoMime && !in_array($finfoMime, $allowedMimeTypes, true)) {
    registrar_guides_redirect('guide_upload_error', 'invalid_type');
}

$fileSize = (int) ($file['size'] ?? 0);
if ($fileSize <= 0) {
    registrar_guides_redirect('guide_upload_error', 'empty_file');
}

try {
    registrar_guides_ensure_schema($conn);
    $uploadDir = registrar_guides_upload_dir();
} catch (Throwable $e) {
    error_log($e->getMessage());
    registrar_guides_redirect('guide_upload_error', 'storage_issue');
}

$uniqueName = 'guide_' . bin2hex(random_bytes(8)) . '.' . $extension;
$targetPath = $uploadDir . '/' . $uniqueName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    registrar_guides_redirect('guide_upload_error', 'move_failed');
}

$uploadedBy = $_SESSION['registrar_username'] ?? null;

try {
    registrar_guides_insert(
        $conn,
        $gradeLevel,
        $uniqueName,
        $originalName,
        $fileSize,
        $uploadedBy,
        'manual',
        null
    );
} catch (Throwable $e) {
    error_log($e->getMessage());
    @unlink($targetPath);
    registrar_guides_redirect('guide_upload_error', 'database_error');
}

registrar_guides_redirect('guide_uploaded');

