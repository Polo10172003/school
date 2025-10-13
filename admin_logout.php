<?php

require_once __DIR__ . '/includes/session.php';

unset(
    $_SESSION['admin_username'],
    $_SESSION['admin_fullname'],
    $_SESSION['admin_role']
);

session_regenerate_id(true);

header('Location: admin_login.php');
exit;
