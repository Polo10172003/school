<?php
require_once __DIR__ . '/../includes/session.php';

unset($_SESSION['adviser_username'], $_SESSION['adviser_fullname'], $_SESSION['adviser_role']);
session_regenerate_id(true);

header('Location: adviser_login.php');
exit;
