<?php
include('db_connection.php');
session_start();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check if student is enrolled
    $checkStudent = $conn->prepare("SELECT * FROM students_registration WHERE emailaddress = ? AND enrollment_status = 'enrolled'");
    if (!$checkStudent) {
        die("Prepare failed (checkStudent): " . $conn->error);
    }
    $checkStudent->bind_param("s", $email);
    $checkStudent->execute();
    $studentResult = $checkStudent->get_result();

    if ($studentResult->num_rows > 0) {
        // Check for existing account
        $checkAccount = $conn->prepare("SELECT * FROM student_accounts WHERE email = ?");
        if (!$checkAccount) {
            die("Prepare failed (checkAccount): " . $conn->error);
        }
        $checkAccount->bind_param("s", $email);
        $checkAccount->execute();
        $accountResult = $checkAccount->get_result();

        if ($accountResult->num_rows == 0) {
            // Create account
            $insertAccount = $conn->prepare("INSERT INTO student_accounts (email) VALUES (?)");
            if (!$insertAccount) {
                die("Prepare failed (insertAccount): " . $conn->error);
            }
            $insertAccount->bind_param("s", $email);
            $insertAccount->execute();
        }

        // Retrieve account safely
        $getAccount = $conn->prepare("SELECT * FROM student_accounts WHERE email = ?");
        if (!$getAccount) {
            die("Prepare failed (getAccount): " . $conn->error);
        }
        $getAccount->bind_param("s", $email);
        $getAccount->execute();
        $accountResult = $getAccount->get_result();
        $account = $accountResult->fetch_assoc();

        if ($account['is_first_login']) {
            $_SESSION['student_email'] = $email;
            header("Location: set_student_password.php");
            exit();
        } else {
            // Verify password
            if (password_verify($password, $account['password'])) {
                $_SESSION['student_email'] = $email;
                header("Location: student_portal.php");
                exit();
            } else {
                $error = "Incorrect password.";
            }
        }

    } else {
        $error = "Email not found or student not enrolled.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; }
        .container {
            width: 400px; margin: 80px auto;
            background: white; padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 10px;
        }
        input[type="email"], input[type="password"] {
            width: 100%; padding: 10px; margin-bottom: 15px;
            border: 1px solid #ccc; border-radius: 5px;
        }
        .btn-submit {
            width: 100%; padding: 10px;
            background: green; color: white; border: none; border-radius: 5px;
        }
        .error { color: red; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <h2>Student Login</h2>
    <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
    <form method="POST" action="student_login.php">
        <input type="email" name="email" placeholder="Student Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <p style="text-align: center; margin-top: 10px;">
    <a href="forgot_password.php" style="color: #007bff; text-decoration: none;">
        <strong>Forgot Password?</strong>
    </a>
</p>

        <button type="submit" class="btn-submit">Login</button>
    </form>
    

</div>
</body>
</html>
