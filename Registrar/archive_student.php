<?php
include __DIR__ . '/../db_connection.php';

$id = intval($_GET['id']);  // safer than raw $_GET

// ✅ Check if student is graduated
$check = $conn->prepare("SELECT * FROM students_registration WHERE id = ? AND academic_status = 'Graduated'");
$check->bind_param("i", $id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Only graduated students can be archived.'); window.location='registrar_dashboard.php';</script>";
    exit;
}

$student = $result->fetch_assoc();
$check->close();

// ✅ Insert into archived_students
$columns = implode(", ", array_keys($student));
$placeholders = implode(", ", array_fill(0, count($student), "?"));
$types = str_repeat("s", count($student)); // crude: everything as string

$insert = $conn->prepare("INSERT INTO archived_students ($columns) VALUES ($placeholders)");
$insert->bind_param($types, ...array_values($student));
$insert->execute();
$insert->close();

// ✅ Delete from students_registration
$del = $conn->prepare("DELETE FROM students_registration WHERE id = ?");
$del->bind_param("i", $id);
$del->execute();
$del->close();

$conn->close();

header("Location: registrar_dashboard.php?msg=archived");
exit();
?>
