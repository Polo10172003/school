<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $year_level = $_POST['year_level'];

    $sql = "UPDATE students_registration SET name = '$name', year_level = '$year_level' WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "Student updated successfully.";
        header("Location: registrar_dashboard.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
