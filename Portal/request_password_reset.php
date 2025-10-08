<?php
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

include __DIR__ . '/../db_connection.php';

$student_number = $_SESSION['student_number'];

$stmt = $conn->prepare('SELECT email FROM student_accounts WHERE student_number = ?');
$stmt->bind_param('s', $student_number);
$stmt->execute();
$stmt->bind_result($email);
$stmt->fetch();
$stmt->close();

if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Unable to find an email for this account.']);
    exit();
}

try {
    $token = bin2hex(random_bytes(32));
} catch (Throwable $th) {
    echo json_encode(['success' => false, 'error' => 'Unable to generate secure token.']);
    exit();
}

$expiry = date('Y-m-d H:i:s', time() + 120);

$update = $conn->prepare('UPDATE student_accounts SET reset_token = ?, token_expiry = ? WHERE student_number = ?');
$update->bind_param('sss', $token, $expiry, $student_number);
$update->execute();
$update->close();

$phpPath = '/Applications/XAMPP/bin/php';
$worker = __DIR__ . '/forgot_password_worker.php';
$cmd = escapeshellcmd($phpPath) . ' ' . escapeshellarg($worker) . ' ' . escapeshellarg($email) . ' ' . escapeshellarg($token);
exec($cmd . ' > /dev/null 2>&1 &');

echo json_encode(['success' => true]);
