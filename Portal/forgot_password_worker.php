<?php
require_once __DIR__ . '/../config/app.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mailer.php';

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
    $mailerConfig = mailer_apply_defaults($mail);
    $mail->setFrom(
        (string) ($mailerConfig['from_email'] ?? 'no-reply@rosariodigital.site'),
        (string) ($mailerConfig['from_name'] ?? 'Escuela De Sto. Rosario')
    );
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

    $logAttempt = static function (string $line) use ($email): void {
        @file_put_contents(
            __DIR__ . '/../temp/email_worker_trace.log',
            sprintf("[%s] [ForgotPassword:%s] %s\n", date('c'), $email, $line),
            FILE_APPEND
        );
    };

    mailer_send_with_fallback(
        $mail,
        [],
        $logAttempt,
        (bool) ($mailerConfig['fallback_to_mail'] ?? false)
    );
} catch (Exception $e) {
    error_log('Forgot password email failed: ' . $e->getMessage());
}
