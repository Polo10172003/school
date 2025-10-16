<?php

require_once __DIR__ . '/includes/session.php';
include __DIR__ . '/db_connection.php';

$username = $_SESSION['admin_username'] ?? '';
if ($username !== '') {
    session_guard_clear($conn, 'admin', $username);
}

unset(
    $_SESSION['admin_username'],
    $_SESSION['admin_fullname'],
    $_SESSION['admin_role']
);

if (isset($_SESSION['session_tokens']['admin'])) {
    unset($_SESSION['session_tokens']['admin']);
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
header('Location: admin_login.php');
exit;
