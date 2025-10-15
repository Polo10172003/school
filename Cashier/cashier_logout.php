<?php

require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/../db_connection.php';

$username = $_SESSION['cashier_username'] ?? '';
if ($username !== '') {
    session_guard_clear($conn, 'cashier', $username);
}

unset(
    $_SESSION['cashier_username'],
    $_SESSION['cashier_fullname'],
    $_SESSION['cashier_role'],
    $_SESSION['cashier_flash'],
    $_SESSION['cashier_flash_type']
);

session_regenerate_id(true);
$conn->close();

header('Location: cashier_login.php');
exit;
