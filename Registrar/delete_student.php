<?php
include __DIR__ . '/../db_connection.php';

$id = $_GET['id'];  // Get the student ID to delete

// Delete query
$sql = "DELETE FROM students_registration WHERE id = $id";

if ($conn->query($sql) === TRUE) {
    echo "Record deleted successfully";
    header("Location: registrar_dashboard.php");
} else {
    echo "Error deleting record: " . $conn->error;
}

$conn->close();
?>
