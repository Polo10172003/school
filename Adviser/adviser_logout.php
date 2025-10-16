<?php
require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/../db_connection.php';

$username = $_SESSION['adviser_username'] ?? '';
if ($username !== '') {
    session_guard_clear($conn, 'adviser', $username);
}

unset(
    $_SESSION['adviser_username'],
    $_SESSION['adviser_fullname'],
    $_SESSION['adviser_role']
);

if (isset($_SESSION['session_tokens']['adviser'])) {
    unset($_SESSION['session_tokens']['adviser']);
    if (empty($_SESSION['session_tokens'])) {
        unset($_SESSION['session_tokens']);
    }
}

session_regenerate_id(true);

if (isset($_GET['auto']) && $_GET['auto'] === '1') {
    $conn->close();
    http_response_code(204);
    exit;
}

$conn->close();
header('Location: adviser_login.php');
exit;
