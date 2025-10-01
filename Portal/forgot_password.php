<?php
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';
date_default_timezone_set('Asia/Manila');
$msg = '';
$isSuccess = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Check if email exists in student_accounts
    $stmt = $conn->prepare("SELECT * FROM student_accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 120); // 2 minutes from now

        $insert = $conn->prepare("UPDATE student_accounts SET reset_token = ?, token_expiry = ? WHERE email = ?");
        $insert->bind_param('sss', $token, $expiry, $email);
        $insert->execute();
        $insert->close();

        $phpPath = '/Applications/XAMPP/bin/php';
        $worker = __DIR__ . '/forgot_password_worker.php';
        $cmd = escapeshellcmd($phpPath) . ' ' . escapeshellarg($worker) . ' ' . escapeshellarg($email) . ' ' . escapeshellarg($token);
        exec($cmd . ' > /dev/null 2>&1 &');

        $msg = "If the email exists in our records, a reset link has been sent. It expires in 2 minutes.";
        $isSuccess = true;
    } else {
        $msg = "Email not registered.";
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
