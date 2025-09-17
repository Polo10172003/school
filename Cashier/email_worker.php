<?php
require '../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;

$student_id     = $argv[1] ?? 0;
$payment_type   = $argv[2] ?? '';
$amount         = $argv[3] ?? 0;
$status         = $argv[4] ?? '';
$student_number = $argv[5] ?? null; // <-- optional


if (!$student_id) exit;

// Fetch student info
$stmt = $conn->prepare("SELECT emailaddress, firstname, lastname FROM students_registration WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($email, $firstname, $lastname);
$stmt->fetch();
$stmt->close();

if (!$email) exit;

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
    <h2>Payment Receipt Confirmation</h2>
    <p>Dear <strong>$firstname $lastname</strong>,</p>
    <p>We have received your payment for the following:</p>
    <ul>
        <li><strong>Payment Type:</strong> $payment_type</li>
        <li><strong>Amount:</strong> ₱" . number_format($amount, 2) . "</li>
        <li><strong>Status:</strong> $status</li>
    </ul>
    <p>Your enrollment is now marked as <strong>ENROLLED</strong>.</p>
    " . ($student_number ? "<p>Your Student Number is: <strong>$student_number</strong></p>" : "") . "
    <hr>
    <p><strong>IMPORTANT:</strong> Please wait for activation of your student portal.</p>
";

$mail->send();
