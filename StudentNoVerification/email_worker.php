<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// Decode payload
parse_str($argv[1] ?? '', $job);
$email = $job['email'] ?? '';
$name  = $job['name'] ?? '';
$student_number = $job['student_number'] ?? '';
$student_type   = $job['student_type'] ?? '';

if (!$email) exit;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'deadpoolvictorio@gmail.com';
    $mail->Password   = 'ldcmeapjfuonxypu'; // Gmail app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
    $mail->addAddress($email, $name);

    $mail->isHTML(true);
    $mail->Subject = "Registration Acknowledgment - ESR";
    $mail->Body = "
    <p>Dear $name,</p>
    <p>Thank you for registering as a <strong>$student_type student</strong>.</p>
    <p>Please submit the following requirements:</p>
    <ul>
        <li>Good Moral Certificate</li>
        <li>PSA Birth Certificate</li>
    </ul>
    <p>You may submit these onsite or send scanned copies via Viber at: <strong>09XX-XXX-XXXX</strong>.</p>
    <br>
    <p>Thank you,<br>Escuela De Sto. Rosario</p>
";


    $mail->send();
} catch (Exception $e) {
    error_log("Mailer Error: {$mail->ErrorInfo}");
}
