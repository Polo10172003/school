<?php
require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/../db_connection.php';
date_default_timezone_set('Asia/Manila');

$error = '';
$success = '';
$email = '';
$studentNumber = '';
$studentName = '';
$mode = '';

if (isset($_GET['token'])) {
    $mode = 'reset';
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT email FROM student_accounts WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $email = $row['email'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPass = $_POST['new_password'];
            $confirmPass = $_POST['confirm_password'];

            if ($newPass === $confirmPass) {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE student_accounts SET password = ?, reset_token = NULL, token_expiry = NULL WHERE email = ?");
                $update->bind_param("ss", $hashed, $email);
                $update->execute();
                $success = "Your password has been reset. You may now <a href='student_login.php'>log in</a>.";
            } else {
                $error = 'Passwords do not match!';
            }
        }
    } else {
        $error = 'Invalid or expired reset link.';
    }
} elseif (isset($_SESSION['student_number'])) {
    $mode = 'first_login';
    $studentNumber = $_SESSION['student_number'];

    $acctStmt = $conn->prepare("SELECT email, is_first_login FROM student_accounts WHERE student_number = ?");
    $acctStmt->bind_param("s", $studentNumber);
    $acctStmt->execute();
    $account = $acctStmt->get_result()->fetch_assoc();

    if ($account) {
        $email = $account['email'] ?? '';
        $infoStmt = $conn->prepare("SELECT firstname, lastname FROM students_registration WHERE student_number = ?");
        $infoStmt->bind_param("s", $studentNumber);
        $infoStmt->execute();
        if ($info = $infoStmt->get_result()->fetch_assoc()) {
            $studentName = trim(($info['firstname'] ?? '') . ' ' . ($info['lastname'] ?? ''));
        }
        $infoStmt->close();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPass = $_POST['new_password'];
            $confirmPass = $_POST['confirm_password'];

            if ($newPass === $confirmPass) {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE student_accounts SET password = ?, is_first_login = 0 WHERE student_number = ?");
                $update->bind_param("ss", $hashed, $studentNumber);
                if ($update->execute()) {
                    session_regenerate_id(true);
                    header('Location: student_portal.php');
                    exit();
                }
                $error = 'Unable to update password right now. Please try again.';
            } else {
                $error = 'Passwords do not match!';
            }
        }
    } else {
        $error = 'Student account not found. Please contact the registrar.';
    }
} elseif (isset($_SESSION['student_email'])) {
    $mode = 'first_login_email';
    $email = $_SESSION['student_email'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];

        if ($newPass === $confirmPass) {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE student_accounts SET password = ?, is_first_login = 0 WHERE email = ?");
            $stmt->bind_param("ss", $hashed, $email);
            $stmt->execute();
            session_regenerate_id(true);
            header('Location: student_portal.php');
            exit();
        } else {
            $error = 'Passwords do not match!';
        }
    }
} else {
    header('Location: student_login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body.set-password-body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(160deg, rgba(20, 90, 50, 0.18), rgba(255, 255, 255, 0.9));
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .set-password-card {
            max-width: 500px;
            width: 100%;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 35px 60px rgba(12, 68, 49, 0.15);
            border: 1px solid rgba(20, 90, 50, 0.1);
            overflow: hidden;
        }

        .set-password-card header {
            padding: 32px 36px 24px;
            background: linear-gradient(120deg, rgba(20, 90, 50, 0.15), rgba(251, 216, 10, 0.25));
        }

        .set-password-card header h1 {
            margin: 0;
            font-weight: 700;
            color: #145A32;
        }

        .set-password-card header p {
            margin: 12px 0 0;
            color: #4a5a58;
        }

        .set-password-card .card-body {
            padding: 32px 36px;
        }

        .welcome-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(20, 90, 50, 0.1);
            color: #145A32;
            font-size: 0.85rem;
            font-weight: 600;
        }

        label.form-label {
            font-weight: 600;
            color: #145A32;
        }

        input.form-control {
            border-radius: 12px;
            border: 1px solid rgba(20, 90, 50, 0.25);
            padding: 12px 14px;
        }

        input.form-control:focus {
            border-color: #145A32;
            box-shadow: 0 0 0 0.2rem rgba(20, 90, 50, 0.15);
        }

        .alert-note {
            border-radius: 12px;
            padding: 16px 18px;
            border-left: 4px solid #f1c40f;
            background: rgba(251, 216, 10, 0.18);
            color: #8a6d0a;
            font-weight: 600;
        }

        .btn-submit {
            width: 100%;
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 600;
            background: #145A32;
            border: none;
        }

        .btn-submit:hover {
            background: #0f4f24;
        }

        .feedback {
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 600;
        }

        .feedback.error {
            background: rgba(231, 76, 60, 0.12);
            color: #c0392b;
        }

        .feedback.success {
            background: rgba(39, 174, 96, 0.12);
            color: #1e8449;
        }

        .requirements {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body class="set-password-body">
    <div class="set-password-card">
        <header>
            <div class="welcome-chip"><i class="bi bi-lock-fill"></i> Secure your account</div>
            <h1><?= $mode === 'reset' ? 'Reset your password' : 'Create your password' ?></h1>
            <?php if ($mode !== 'reset' && $studentName): ?>
                <p>Hi <?= htmlspecialchars($studentName); ?>, set a password to access your student portal.</p>
            <?php elseif ($mode === 'reset'): ?>
                <p>Enter a new password below to regain access to your portal.</p>
            <?php else: ?>
                <p>Enter a new password to continue to your student portal.</p>
            <?php endif; ?>
        </header>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="feedback error mb-3"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="feedback success mb-3"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form method="POST" class="row g-3">
                    <div class="col-12">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" id="new_password" placeholder="Enter new password" required>
                    </div>
                    <div class="col-12">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Re-type new password" required>
                        <div class="requirements mt-2"><i class="bi bi-info-circle"></i> Password must match exactly.</div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-submit">Save Password</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
