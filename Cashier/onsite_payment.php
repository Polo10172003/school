<?php
require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/../db_connection.php';

if (!isset($_GET['id'], $_GET['type'])) {
    die("Missing parameters.");
}

$student_id = $_GET['id'];
$payment_type = $_GET['type']; // 'onsite' or 'online'

// For onsite, just record as pending payment
if ($payment_type === 'onsite') {
    $stmt = $conn->prepare("INSERT INTO student_payments (student_id, payment_type, amount, status) VALUES (?, ?, ?, ?)");
    $amount = 0; // You can compute based on tuition fees table
    $status = "pending";
    $stmt->bind_param("isss", $student_id, $payment_type, $amount, $status);
    $stmt->execute();
    $stmt->close();

    // Redirect to cashier dashboard
    header("Location: cashier_dashboard.php");
    exit();
}

// For online, redirect to payment page
if ($payment_type === 'online') {
    $lrn = $_GET['lrn'] ?? '';
    header("Location: choose_payment.php?lrn=$lrn");
    exit();
}

$conn->close();
?>
