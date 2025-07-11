<?php
session_start();
if (!isset($_SESSION['student_email'])) {
    header("Location: student_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
</head>
<body>
    <h1>Welcome, <?= $_SESSION['student_email'] ?></h1>
    <p>This is your student portal dashboard.</p>
</body>
</html>
