<?php
require_once __DIR__ . '/../config/app.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mailer.php';
include __DIR__ . '/../db_connection.php';
date_default_timezone_set('Asia/Manila');

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$msg = '';
$isSuccess = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $msg = "Please enter your registered email address.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM student_accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now

            $update = $conn->prepare("UPDATE student_accounts SET reset_token = ?, token_expiry = ? WHERE email = ?");
            $update->bind_param('sss', $token, $expiry, $email);
            $update->execute();
            $update->close();

            $resetLink = APP_BASE_URL . 'Portal/set_student_password.php?token=' . urlencode($token);

            $mail = new PHPMailer(true);
            try {
                $mailerConfig = mailer_apply_defaults($mail);
                $mail->clearAllRecipients();
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "
                    <h3 style='color:#145A32;'>Password Reset</h3>
                    <p>We received a request to reset your password. Click the button below to continue:</p>
                    <p style='margin:18px 0;'>
                        <a href='{$resetLink}' style='display:inline-block; padding:10px 18px; background:#145A32; color:#ffffff; text-decoration:none; border-radius:6px;'>Reset Password</a>
                    </p>
                    <p>This link expires in <strong>10 minutes</strong>. If it expires, you can request a new one from the portal.</p>
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

                $msg = "If the email exists in our records, a reset link has been sent. It expires in 10 minutes.";
                $isSuccess = true;
            } catch (Exception $e) {
                error_log('Forgot password email failed: ' . $e->getMessage());
                $msg = "We couldn't send the reset email. Please try again later or contact support.";
                $isSuccess = false;
            }
        } else {
            $msg = "If the email exists in our records, a reset link has been sent.";
            $isSuccess = true;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-brand">
                <img src="../Esrlogo.png" alt="ESR Logo">
                <span>Escuela de Sto. Rosario</span>
            </div>
            <h1 class="auth-title">Forgot Password</h1>
            <p class="auth-subtitle">Enter your registered email address so we can send you a reset link.</p>
            <?php if ($msg): ?>
                <div class="<?= $isSuccess ? 'auth-notice' : 'auth-error'; ?>"><?= htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            <form class="auth-form" method="POST" action="">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="name@email.com">
                <button type="submit" class="auth-btn">Send Reset Link</button>
            </form>
            <div class="auth-footer">
                <a href="student_login.php">Back to student login</a>
            </div>
        </div>
    </div>
</body>
</html>
