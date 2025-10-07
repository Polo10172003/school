<?php
session_start();
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['student_number'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = file_get_contents('php://input');
$payload = json_decode($input, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request payload.']);
    exit();
}

$action = $payload['action'] ?? '';
$ids = $payload['ids'] ?? [];

if (!in_array($action, ['mark_read', 'delete'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    exit();
}

if (!is_array($ids) || empty($ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No announcement IDs supplied.']);
    exit();
}

$announcementIds = [];
foreach ($ids as $value) {
    $intValue = (int) $value;
    if ($intValue > 0) {
        $announcementIds[] = $intValue;
    }
}

if (empty($announcementIds)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No valid announcement IDs supplied.']);
    exit();
}

include __DIR__ . '/../db_connection.php';

$conn->query("CREATE TABLE IF NOT EXISTS student_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    target_scope TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS student_announcement_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(64) NOT NULL,
    announcement_id INT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_student_announcement (student_number, announcement_id),
    INDEX idx_student (student_number),
    INDEX idx_announcement (announcement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$studentNumber = $_SESSION['student_number'];

if ($action === 'mark_read') {
    $sql = "INSERT INTO student_announcement_status (student_number, announcement_id, is_read, is_deleted)
            VALUES (?, ?, 1, 0)
            ON DUPLICATE KEY UPDATE is_read = VALUES(is_read), is_deleted = 0";
} else { // delete
    $sql = "INSERT INTO student_announcement_status (student_number, announcement_id, is_read, is_deleted)
            VALUES (?, ?, 1, 1)
            ON DUPLICATE KEY UPDATE is_read = 1, is_deleted = 1";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to prepare statement.']);
    exit();
}

foreach ($announcementIds as $announcementId) {
    $stmt->bind_param('si', $studentNumber, $announcementId);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update announcement status.']);
        $stmt->close();
        $conn->close();
        exit();
    }
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true]);
