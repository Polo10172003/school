<?php
declare(strict_types=1);

define('SESSION_GUARD_SKIP', true);

require_once __DIR__ . '/includes/session.php';
include __DIR__ . '/db_connection.php';

$pending = $_SESSION['staff_first_login'] ?? null;
if (!is_array($pending) || empty($pending['username']) || empty($pending['context'])) {
    $conn->close();
    header('Location: index.php');
    exit;
}

$userId = (int) ($pending['user_id'] ?? 0);
$username = (string) ($pending['username'] ?? '');
$displayName = trim((string) ($pending['fullname'] ?? $username));
$context = (string) ($pending['context'] ?? '');
$redirectPath = (string) ($pending['redirect'] ?? 'index.php');

if ($userId <= 0 || $username === '' || $context === '') {
    unset($_SESSION['staff_first_login']);
    $conn->close();
    header('Location: index.php');
    exit;
}

if (!preg_match('/^[A-Za-z0-9_\-\/\.]+$/', $redirectPath)) {
    $redirectPath = 'index.php';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword === '' || $confirmPassword === '') {
        $error = 'Please enter and confirm your new password.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password = ?, is_first_login = 0 WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('si', $hashed, $userId);
            if ($stmt->execute()) {
                $stmt->close();

                session_regenerate_id(true);

                switch ($context) {
                    case 'admin':
                        $_SESSION['admin_username'] = $username;
                        $_SESSION['admin_fullname'] = $displayName;
                        $_SESSION['admin_role'] = 'Administrator';
                        session_guard_store($conn, 'admin', $username);
                        break;

                    case 'registrar':
                        $_SESSION['registrar_username'] = $username;
                        $_SESSION['registrar_fullname'] = $displayName;
                        $_SESSION['registrar_role'] = 'registrar';
                        session_guard_store($conn, 'registrar', $username);
                        break;

                    case 'cashier':
                        $_SESSION['cashier_username'] = $username;
                        $_SESSION['cashier_fullname'] = $displayName;
                        $_SESSION['cashier_role'] = 'cashier';
                        session_guard_store($conn, 'cashier', $username);
                        break;

                    case 'adviser':
                        $_SESSION['adviser_username'] = $username;
                        $_SESSION['adviser_fullname'] = $displayName;
                        $_SESSION['adviser_role'] = 'adviser';
                        session_guard_store($conn, 'adviser', $username);
                        break;

                    default:
                        $error = 'Unable to determine staff role for login.';
                        break;
                }

                if ($error === '') {
                    unset($_SESSION['staff_first_login']);
                    $conn->close();
                    header('Location: ' . $redirectPath);
                    exit;
                }
            } else {
                $error = 'Unable to update password right now. Please try again.';
                $stmt->close();
            }
        } else {
            $error = 'Unable to prepare password update.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(160deg, rgba(20, 90, 50, 0.12), rgba(255, 255, 255, 0.9));
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            max-width: 420px;
            width: 100%;
            border-radius: 24px;
            box-shadow: 0 30px 50px rgba(15, 59, 37, 0.15);
            border: 1px solid rgba(20, 90, 50, 0.12);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(120deg, rgba(20, 90, 50, 0.15), rgba(251, 216, 10, 0.3));
            padding: 28px 32px 20px;
        }
        .card-header h1 {
            margin: 0;
            font-size: 1.6rem;
            color: #145a32;
        }
        .card-header p {
            margin: 10px 0 0;
            color: #4a5a58;
        }
        .card-body {
            padding: 28px 32px 36px;
            background: #fff;
        }
        .form-label {
            font-weight: 600;
            color: #1d3b2d;
        }
        .btn-primary {
            background: #145a32;
            border-color: #145a32;
            font-weight: 600;
            padding: 10px 16px;
            border-radius: 12px;
        }
        .btn-primary:hover {
            background: #0f4425;
            border-color: #0f4425;
        }
        .alert {
            border-radius: 12px;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <h1>Create Your Password</h1>
        <p>Hi <?= htmlspecialchars($displayName) ?>, please choose a new password to finish signing in.</p>
    </div>
    <div class="card-body">
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required autofocus>
                <div class="form-text">Use at least 8 characters to keep your account secure.</div>
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Save Password &amp; Continue</button>
        </form>
    </div>
</div>
</body>
</html>
