<?php

require_once __DIR__ . '/../includes/session.php';

unset(
    $_SESSION['cashier_username'],
    $_SESSION['cashier_fullname'],
    $_SESSION['cashier_role'],
    $_SESSION['cashier_flash'],
    $_SESSION['cashier_flash_type']
);

session_regenerate_id(true);

header('Location: cashier_login.php');
exit;
