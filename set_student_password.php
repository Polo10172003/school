<?php
include('db_connection.php');
date_default_timezone_set('Asia/Manila'); // Or your actual timezone

session_start();

$error = '';
$success = '';
$email = '';

// CASE 1: Password reset via token (forgot password)
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if token is valid and not expired
    $stmt = $conn->prepare("SELECT email FROM student_accounts WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $email = $row['email'];

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $newPass = $_POST['new_password'];
            $confirmPass = $_POST['confirm_password'];

            if ($newPass === $confirmPass) {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE student_accounts SET password = ?, reset_token = NULL, token_expiry = NULL WHERE email = ?");
                $update->bind_param("ss", $hashed, $email);
                $update->execute();

                $success = "Your password has been reset. You may now <a href='student_login.php'>log in</a>.";
            } else {
                $error = "Passwords do not match!";
            }
        }
    } else {
        $error = "Invalid or expired token.";
    }
}
// CASE 2: First-time login
elseif (isset($_SESSION['student_email'])) {
    $email = $_SESSION['student_email'];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];

        if ($newPass === $confirmPass) {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE student_accounts SET password = ?, is_first_login = 0 WHERE email = ?");
            $stmt->bind_param("ss", $hashed, $email);
            $stmt->execute();

            // Redirect to dashboard
            header("Location: student_portal.php");
            exit();
        } else {
            $error = "Passwords do not match!";
        }
    }
}
// No token or session — invalid access
else {
    header("Location: student_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Set New Password</title>
    <style>
        .container { width: 400px; margin: 80px auto; background: #fff; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 10px; }
        input { width: 100%; padding: 10px; margin-bottom: 15px; }
        .btn { width: 100%; padding: 10px; background: #007BFF; color: white; border: none; border-radius: 5px; }
        .error { color: red; text-align: center; }
        .success { color: green; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <h2>Set Your New Password</h2>
    <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success"><?= $success ?></p><?php endif; ?>
    <?php if (!$success): ?>
    <form method="POST">
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button class="btn" type="submit">Set Password</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
