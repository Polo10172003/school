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

if (isset($_SESSION['session_tokens']['registrar'])) {
    unset($_SESSION['session_tokens']['registrar']);
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
header('Location: registrar_login.php');
exit;
