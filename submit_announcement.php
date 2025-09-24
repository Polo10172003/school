<?php
include('db_connection.php');

$subject = $_POST['subject'] ?? '';
$message = $_POST['message'] ?? '';
$grades = $_POST['grades'] ?? [];

if (empty($grades)) {
    die("No grades selected.");
}

$conn->query("CREATE TABLE IF NOT EXISTS student_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    target_scope TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$normalizedScope = in_array('everyone', $grades, true)
    ? 'everyone'
    : implode(',', array_map('trim', $grades));

$storeAnnouncement = $conn->prepare('INSERT INTO student_announcements (subject, body, target_scope) VALUES (?, ?, ?)');
$storeAnnouncement->bind_param('sss', $subject, $message, $normalizedScope);
$storeAnnouncement->execute();
$storeAnnouncement->close();

$php_path = '/Applications/XAMPP/bin/php';
$worker = __DIR__ . '/Portal/announcement_worker.php';

$cmd = escapeshellcmd($php_path) . ' ' . escapeshellarg($worker)
    . ' ' . escapeshellarg($subject)
    . ' ' . escapeshellarg($message)
    . ' ' . escapeshellarg($normalizedScope);
exec($cmd . ' > /dev/null 2>&1 &');

echo "<script>alert('Announcement queued successfully. Emails will be delivered in the background.'); window.location.href = 'admin_dashboard.php#announcements';</script>";

$conn->close();
?>
