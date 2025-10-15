<?php

require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/../db_connection.php';

$username = $_SESSION['registrar_username'] ?? '';
if ($username !== '') {
    session_guard_clear($conn, 'registrar', $username);
}

unset(
    $_SESSION['registrar_username'],
    $_SESSION['registrar_fullname'],
    $_SESSION['registrar_role']
);

session_regenerate_id(true);
$conn->close();

header('Location: registrar_login.php');
exit;
