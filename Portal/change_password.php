<?php
define('SESSION_GUARD_JSON', true);
require_once __DIR__ . '/../includes/session.php';
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

if (!isset($_SESSION['student_number'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You need to be signed in.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$currentPassword = trim($input['current_password'] ?? '');
$newPassword = trim($input['new_password'] ?? '');

if ($currentPassword === '' || $newPassword === '') {
    echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
    exit();
}

if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long.']);
    exit();
}

include __DIR__ . '/../db_connection.php';

$studentNumber = $_SESSION['student_number'];

$stmt = $conn->prepare('SELECT password FROM student_accounts WHERE student_number = ?');
$stmt->bind_param('s', $studentNumber);
$stmt->execute();
$stmt->bind_result($hashedPassword);
$stmt->fetch();
$stmt->close();

if (!$hashedPassword) {
    echo json_encode(['success' => false, 'error' => 'Unable to verify credentials.']);
    exit();
}

if (!password_verify($currentPassword, $hashedPassword)) {
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']);
    exit();
}

$newHashed = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $conn->prepare('UPDATE student_accounts SET password = ?, is_first_login = 0, reset_token = NULL, token_expiry = NULL WHERE student_number = ?');
$update->bind_param('ss', $newHashed, $studentNumber);
if (!$update->execute()) {
    $update->close();
    echo json_encode(['success' => false, 'error' => 'Unable to update password at the moment.']);
    exit();
}
$update->close();

echo json_encode(['success' => true]);
