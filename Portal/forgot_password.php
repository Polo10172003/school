<?php
require '../vendor/autoload.php';
require '../db_connection.php';
date_default_timezone_set('Asia/Manila'); // Or your actual timezone


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Check if email exists in student_accounts
    $stmt = $conn->prepare("SELECT * FROM student_accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(50)); // generate secure token
        $expiry = date("Y-m-d H:i:s", strtotime("+2 minutes"));

        // Store token in forgot_password table
        $insert = $conn->prepare("UPDATE student_accounts SET reset_token = ?, token_expiry = ? WHERE email = ?");
        $insert->bind_param("sss", $token, $expiry, $email);
        $insert->execute();

        // Send reset email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';  // Your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'deadpoolvictorio@gmail.com'; // Your Gmail
            $mail->Password = 'ldcmeapjfuonxypu';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Email content
            $mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $resetLink = "http://localhost/Enrollment/set_student_password.php?token=$token";
            $mail->Body = "
                <h3>Password Reset</h3>
                <p>Click the link below to reset your password:</p>
                <a href='$resetLink'>Reset Password</a><br><br>
                <small>This link will expire in 2 mins.</small>
            ";

            $mail->send();
            $msg = "A password reset link has been sent to your email.";
        } catch (Exception $e) {
            $msg = "Failed to send email. Error: {$mail->ErrorInfo}";
        }
    } else {
        $msg = "Email not found in student accounts.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        body {
            font-family: Arial;
            background: #f0f0f0;
        }
        .container {
            max-width: 400px;
            margin: 80px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0,0,0,0.1);
        }
        input[type=email] {
            width: 100%;
            padding: 10px;
            margin: 12px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: #007bff;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .message {
            margin-top: 15px;
            color: green;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Forgot Password</h2>
    <form method="POST">
        <label>Enter your registered email:</label>
        <input type="email" name="email" required placeholder="example@gmail.com">
        <button type="submit">Send Reset Link</button>
    </form>
    <?php if ($msg): ?>
        <p class="message"><?= $msg ?></p>
    <?php endif; ?>
</div>
</body>
</html>
