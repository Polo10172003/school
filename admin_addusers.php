<?php
include('db_connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];


    $sql = "INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error); // show if query is invalid
    }

    $stmt->bind_param("ssss", $username, $password, $fullname, $role);

    if ($stmt->execute()) {
        echo "<script>alert('User added successfully!'); window.location.href='admin_dashboard.php';</script>";
    } else {
        die("Execute failed: " . $stmt->error); // show DB error
    }

    $stmt->close();
    $conn->close();
}
?>
