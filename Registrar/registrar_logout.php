<?php

require_once __DIR__ . '/../includes/session.php';

unset(
    $_SESSION['registrar_username'],
    $_SESSION['registrar_fullname'],
    $_SESSION['registrar_role']
);

session_regenerate_id(true);

header('Location: registrar_login.php');
exit;
