<?php
require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/../db_connection.php';

$error = '';

if (isset($_SESSION['student_number'])) {
    header("Location: student_portal.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_number = trim($_POST['student_number']);
    $password = $_POST['password'];

    $checkStudent = $conn->prepare("SELECT * FROM students_registration WHERE student_number = ? AND enrollment_status = 'enrolled'");
    if (!$checkStudent) {
        die("Prepare failed (checkStudent): " . $conn->error);
    }
    $checkStudent->bind_param("s", $student_number);
    $checkStudent->execute();
    $studentResult = $checkStudent->get_result();

    if ($studentResult->num_rows > 0) {
        $checkAccount = $conn->prepare("SELECT * FROM student_accounts WHERE student_number = ?");
        if (!$checkAccount) {
            die("Prepare failed (checkAccount): " . $conn->error);
        }
        $checkAccount->bind_param("s", $student_number);
        $checkAccount->execute();
        $accountResult = $checkAccount->get_result();

        if ($accountResult->num_rows == 0) {
            $insertAccount = $conn->prepare("INSERT INTO student_accounts (student_number) VALUES (?)");
            if (!$insertAccount) {
                die("Prepare failed (insertAccount): " . $conn->error);
            }
            $insertAccount->bind_param("s", $student_number);
            $insertAccount->execute();
        }

        $getAccount = $conn->prepare("SELECT * FROM student_accounts WHERE student_number = ?");
        if (!$getAccount) {
            die("Prepare failed (getAccount): " . $conn->error);
        }
        $getAccount->bind_param("s", $student_number);
        $getAccount->execute();
        $accountResult = $getAccount->get_result();
        $account = $accountResult->fetch_assoc();

        if ($account['is_first_login']) {
            session_regenerate_id(true);
            $_SESSION['student_number'] = $student_number;
            header("Location: set_student_password.php");
            exit();
        }

        $storedPassword = $account['password'] ?? '';
        $isHashed = password_get_info((string) $storedPassword)['algo'] !== 0;
        $authenticated = false;

        if ($isHashed && password_verify($password, $storedPassword)) {
            $authenticated = true;

            if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                $rehash = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare('UPDATE student_accounts SET password = ? WHERE student_number = ?');
                if ($update) {
                    $update->bind_param('ss', $rehash, $student_number);
                    $update->execute();
                    $update->close();
                }
            }
        } elseif (!$isHashed && hash_equals((string) $storedPassword, $password)) {
            $authenticated = true;

            $rehash = password_hash($password, PASSWORD_DEFAULT);
            $updateLegacy = $conn->prepare('UPDATE student_accounts SET password = ? WHERE student_number = ?');
            if ($updateLegacy) {
                $updateLegacy->bind_param('ss', $rehash, $student_number);
                $updateLegacy->execute();
                $updateLegacy->close();
            }
        }

        if ($authenticated) {
            session_regenerate_id(true);
            $_SESSION['student_number'] = $student_number;
            header('Location: student_portal.php');
            exit();
        }

        $error = "Incorrect password.";
    } else {
        $error = "Student number not found or student not enrolled.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-brand">
                <img src="../Esrlogo.png" alt="ESR Logo">
                <span>Escuela de Sto. Rosario</span>
            </div>
            <h1 class="auth-title">Student Portal</h1>
            <p class="auth-subtitle">Log in to follow your enrollment status, announcements, and payments.</p>
            <?php if (!empty($error)): ?>
                <div class="auth-error"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form class="auth-form" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <label for="student_number">Student Number</label>
                <input type="text" id="student_number" name="student_number" required autofocus>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <button type="submit" class="auth-btn">Login</button>
            </form>
            <div class="auth-footer">
                <a href="forgot_password.php">Forgot password?</a>
                <br>
                <a href="../index.php">&larr; Back to homepage</a>
            </div>
        </div>
    </div>
</body>
</html>
