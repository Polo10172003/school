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

session_regenerate_id(true);
$conn->close();

header('Location: admin_login.php');
exit;
