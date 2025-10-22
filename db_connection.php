<?php
// Database connection
$servername = "127.0.0.1";
$username = "u368533303_Polo";
$password = "Dan65933";
$dbname = "u368533303_ESR";  // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
$conn->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_uca1400_ai_ci'");
$conn->query("SET collation_connection = 'utf8mb4_uca1400_ai_ci'");

require_once __DIR__ . '/includes/session_guard.php';
session_guard_auto_check($conn);
?>
