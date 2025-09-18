<?php
require '../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;

$student_id     = $argv[1] ?? 0;
$payment_type   = $argv[2] ?? '';
$amount         = $argv[3] ?? 0;
$status         = $argv[4] ?? '';


if (!$student_id) exit;

// Fetch student info
$stmt = $conn->prepare("SELECT emailaddress, firstname, lastname, student_number FROM students_registration WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($email, $firstname, $lastname, $student_number);
$stmt->fetch();
$stmt->close();

if (!$email) exit;
// 🔹 Fetch latest OR number from payments
$or_number = null;
$payStmt = $conn->prepare("SELECT or_number FROM student_payments WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$payStmt->bind_param("i", $student_id);
$payStmt->execute();
$payStmt->bind_result($or_number);
$payStmt->fetch();
$payStmt->close();

// Send email
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'deadpoolvictorio@gmail.com';
$mail->Password = 'ldcmeapjfuonxypu';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
$mail->addAddress($email, "$firstname $lastname");

$mail->isHTML(true);
$mail->Subject = 'Payment Receipt and Portal Access';
$mail->Body = "
    <h2 style='color:#2c3e50;'>Payment Receipt Confirmation</h2>
    <p>Dear <strong>$firstname $lastname</strong>,</p>
    <p>We have received your payment for the following:</p>

    <!-- Official Receipt Style -->
    <div style='border:2px solid #333; padding:15px; border-radius:8px; background:#f9f9f9; margin:20px 0;'>
        <h3 style='text-align:center; margin:0;'>Escuela De Sto. Rosario</h3>
        <h4 style='text-align:center; margin:0;'>Official Receipt</h4>
        <p style='text-align:center; font-size:12px; margin:5px 0 15px;'>This serves as your official proof of payment</p>
        
        <table style='width:100%; border-collapse:collapse;'>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>Student Name</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>$firstname $lastname</td>
            </tr>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>Student Number</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>" . ($student_number ?: 'Pending Assignment') . "</td>
            </tr>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>Payment Type</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>$payment_type</td>
            </tr>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>Amount Paid</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>₱" . number_format($amount, 2) . "</td>
            </tr>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>Status</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>$status</td>
            </tr>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>Date Issued</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>" . date("F j, Y") . "</td>
            </tr>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>OR Number</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>" . ($or_number ?? 'N/A') . "</td>
            </tr>
        </table>

       
    </div>

    <p>Your enrollment is now marked as <strong>ENROLLED</strong>.</p>
    <p><strong>IMPORTANT:</strong> Please wait for activation of your student portal.</p>
";


$mail->send();
