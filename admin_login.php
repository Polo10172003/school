<?php
require_once __DIR__ . '/includes/session.php';
include 'db_connection.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT username, password, fullname FROM users WHERE username = ?');
    if ($stmt) {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($admin) {
            $storedPassword = $admin['password'] ?? '';
            $isHashed = password_get_info((string) $storedPassword)['algo'] !== 0;
            $authenticated = false;

            if ($isHashed && password_verify($password, $storedPassword)) {
                $authenticated = true;
                if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                    $rehash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $conn->prepare('UPDATE users SET password = ? WHERE username = ?');
                    if ($update) {
                        $update->bind_param('ss', $rehash, $username);
                        $update->execute();
                        $update->close();
                    }
                }
            } elseif (!$isHashed && hash_equals((string) $storedPassword, $password)) {
                $authenticated = true;
                $rehash = password_hash($password, PASSWORD_DEFAULT);
                $updateLegacy = $conn->prepare('UPDATE users SET password = ? WHERE username = ?');
                if ($updateLegacy) {
                    $updateLegacy->bind_param('ss', $rehash, $username);
                    $updateLegacy->execute();
                    $updateLegacy->close();
                }
            }

            if ($authenticated) {
                session_regenerate_id(true);
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_fullname'] = $admin['fullname'] ?? $admin['username'];
                $_SESSION['admin_role'] = 'Administrator';
                header("Location: admin_dashboard.php");
                exit();
            }
        }
    }

    $error = "Invalid login credentials.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-brand">
                <img src="Esrlogo.png" alt="ESR Logo">
                <span>Escuela de Sto. Rosario</span>
            </div>
            <h1 class="auth-title">Admin Portal</h1>
            <p class="auth-subtitle">Manage system settings and school-wide updates.</p>
            <?php if (!empty($error)): ?>
                <div class="auth-error"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form class="auth-form" action="admin_login.php" method="POST">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <button type="submit" class="auth-btn">Login</button>
            </form>
            <div class="auth-footer">
                <a href="index.php">&larr; Back to homepage</a>
            </div>
        </div>
    </div>
</body>
</html>
