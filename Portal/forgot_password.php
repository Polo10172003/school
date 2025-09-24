<?php
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';
include '../includes/header.php';
date_default_timezone_set('Asia/Manila');
$msg = '';

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
    } else {
        $msg = "Email not registered.";
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
        .pass-container {
            max-width: 400px;
            margin: 80px auto;
            background: white;
            padding: 20px;
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
        .btn-submit {
            width: 100%; padding: 10px;
            background: #145A32; color: white; border: none; border-radius: 5px;
        }
        .btn-submit:hover {
            background-color: #0f723aff;
            color: #ffffff !important;
            transform: translateY(-1px);
        }
        .error { color: red; text-align: center; 
        }
        .message {
            margin-top: 15px;
            color: red;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="pass-container">
    <h2 class="text-center mb-4 fw-bold">Forgot Password</h2>
    <form method="POST">
        <label>Enter your registered email:</label>
        <input type="email" name="email" required placeholder="example@gmail.com">
        <button type="submit" class="btn-submit w-100">Send Reset Link</button>
    </form>
    <?php if ($msg): ?>
        <p class="message"><?= $msg ?></p>
    <?php endif; ?>
</div>
</body>
</html>
<?php
   include '../includes/footer.php';
?>
