<?php
require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/../db_connection.php';

$studentNumber = $_SESSION['student_number'] ?? '';
if ($studentNumber !== '') {
    session_guard_clear($conn, 'student', $studentNumber);
}

session_unset();
session_destroy();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

$conn->close();

if (isset($_GET['auto']) && $_GET['auto'] === '1') {
    http_response_code(204);
    exit();
}

header("Location: student_login.php");
exit();
