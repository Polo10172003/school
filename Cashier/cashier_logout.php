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

if (isset($_SESSION['session_tokens']['cashier'])) {
    unset($_SESSION['session_tokens']['cashier']);
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
header('Location: cashier_login.php');
exit;
