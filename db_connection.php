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
?>
