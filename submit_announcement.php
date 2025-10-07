<?php
include('db_connection.php');

$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$grades = $_POST['grades'] ?? [];
$imagePath = null;

if (empty($grades)) {
    die("No grades selected.");
}

$conn->query("CREATE TABLE IF NOT EXISTS student_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    target_scope TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$columnCheck = $conn->query("SHOW COLUMNS FROM student_announcements LIKE 'image_path'");
if ($columnCheck && $columnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE student_announcements ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER target_scope");
}
if ($columnCheck) {
    $columnCheck->close();
}

if (!empty($_FILES['announcement_image']['name']) && is_uploaded_file($_FILES['announcement_image']['tmp_name'])) {
    $fileError = $_FILES['announcement_image']['error'] ?? UPLOAD_ERR_OK;
    if ($fileError !== UPLOAD_ERR_OK) {
        die('Failed to upload image.');
    }

    $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp'];
    $fileType = mime_content_type($_FILES['announcement_image']['tmp_name']);
    if (!in_array($fileType, $allowedTypes, true)) {
        die('Unsupported image format.');
    }

    $maxFileSize = 5 * 1024 * 1024; // 5MB
    if (($_FILES['announcement_image']['size'] ?? 0) > $maxFileSize) {
        die('Image exceeds maximum size of 5MB.');
    }

    $uploadDir = __DIR__ . '/announcement_uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $extension = strtolower(pathinfo($_FILES['announcement_image']['name'], PATHINFO_EXTENSION));
    try {
        $randomToken = bin2hex(random_bytes(4));
    } catch (Throwable $e) {
        $randomToken = bin2hex(openssl_random_pseudo_bytes(4));
        if ($randomToken === false) {
            $randomToken = uniqid();
        }
    }
    $filename = 'announcement_' . time() . '_' . $randomToken . '.' . $extension;
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($_FILES['announcement_image']['tmp_name'], $destination)) {
        die('Unable to save uploaded image.');
    }

    $imagePath = 'announcement_uploads/' . $filename;
}

$normalizedScope = in_array('everyone', $grades, true)
    ? 'everyone'
    : implode(',', array_map('trim', $grades));

$storeAnnouncement = $conn->prepare('INSERT INTO student_announcements (subject, body, target_scope, image_path) VALUES (?, ?, ?, ?)');
$storeAnnouncement->bind_param('ssss', $subject, $message, $normalizedScope, $imagePath);
$storeAnnouncement->execute();
$announcementId = $conn->insert_id;
$storeAnnouncement->close();

$php_path = '/Applications/XAMPP/bin/php';
$worker = __DIR__ . '/Portal/announcement_worker.php';

$cmd = escapeshellcmd($php_path) . ' ' . escapeshellarg($worker)
    . ' ' . escapeshellarg($subject)
    . ' ' . escapeshellarg($message)
    . ' ' . escapeshellarg($normalizedScope)
    . ' ' . escapeshellarg((string) $announcementId)
    . ' ' . escapeshellarg($imagePath ?? '');
exec($cmd . ' > /dev/null 2>&1 &');

echo "<script>alert('Announcement queued successfully. Emails will be delivered in the background.'); window.location.href = 'admin_dashboard.php#announcements';</script>";

$conn->close();
?>
