<?php
require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/../db_connection.php';

$username = $_SESSION['adviser_username'] ?? '';
if ($username !== '') {
    session_guard_clear($conn, 'adviser', $username);
}

unset($_SESSION['adviser_username'], $_SESSION['adviser_fullname'], $_SESSION['adviser_role']);
session_regenerate_id(true);

$conn->close();
header('Location: adviser_login.php');
exit;
