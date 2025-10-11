<?php
require_once __DIR__ . '/../config/app.php';
require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Asia/Manila');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = $argv[1] ?? '';
$token = $argv[2] ?? '';

if (!$email || !$token) {
    exit(0);
}

$resetLink = APP_BASE_URL . 'Portal/set_student_password.php?token=' . urlencode($token);

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'deadpoolvictorio@gmail.com';
    $mail->Password = 'ldcmeapjfuonxypu';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request';
    $mail->Body = "
        <h3 style='color:#145A32;'>Password Reset</h3>
        <p>We received a request to reset your password. Click the button below to continue:</p>
        <p style='margin:18px 0;'>
            <a href='{$resetLink}' style='display:inline-block; padding:10px 18px; background:#145A32; color:#ffffff; text-decoration:none; border-radius:6px;'>Reset Password</a>
        </p>
        <p>This link expires in <strong>2 minutes</strong>. If it expires, you can request a new one from the portal.</p>
        <p style='font-size:12px; color:#7b8a87;'>If you did not request this change, you can safely ignore this email.</p>
    ";

    $mail->send();
} catch (Exception $e) {
    error_log('Forgot password email failed: ' . $mail->ErrorInfo);
}
