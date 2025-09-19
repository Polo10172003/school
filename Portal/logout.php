<?php
session_start();
session_unset();
session_destroy();
header("Location: student_login.php"); // balik sa homepage after logout
exit();
?>