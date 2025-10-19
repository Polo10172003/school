<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../db_connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, username, password, fullname, role, is_first_login FROM users WHERE username = ? AND role = 'adviser' LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $adviser = $result->fetch_assoc();
        $stmt->close();

        if ($adviser) {
            $isFirstLogin = (int) ($adviser['is_first_login'] ?? 0) === 1;
            if ($isFirstLogin) {
                session_regenerate_id(true);
                $_SESSION['staff_first_login'] = [
                    'user_id'  => (int) ($adviser['id'] ?? 0),
                    'username' => $adviser['username'],
                    'fullname' => $adviser['fullname'] ?? $adviser['username'],
                    'context'  => 'adviser',
                    'redirect' => 'Adviser/adviser_dashboard.php',
                ];
                header('Location: ../set_staff_password.php');
                exit;
            }

            $storedPassword = (string) ($adviser['password'] ?? '');
            $isHashed = password_get_info($storedPassword)['algo'] !== 0;
            $authenticated = false;

            if ($isHashed && password_verify($password, $storedPassword)) {
                $authenticated = true;
                if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                    $rehash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                    if ($update) {
                        $update->bind_param('si', $rehash, $adviser['id']);
                        $update->execute();
                        $update->close();
                    }
                }
            } elseif (!$isHashed && hash_equals($storedPassword, $password)) {
                $authenticated = true;
                $rehash = password_hash($password, PASSWORD_DEFAULT);
                $updateLegacy = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                if ($updateLegacy) {
                    $updateLegacy->bind_param('si', $rehash, $adviser['id']);
                    $updateLegacy->execute();
                    $updateLegacy->close();
                }
            }

            if ($authenticated) {
                session_regenerate_id(true);
                $_SESSION['adviser_username'] = $adviser['username'];
                $_SESSION['adviser_fullname'] = $adviser['fullname'] ?? $adviser['username'];
                $_SESSION['adviser_role'] = $adviser['role'] ?? 'adviser';
                session_guard_store($conn, 'adviser', $adviser['username']);
                header('Location: adviser_dashboard.php');
                exit;
            }
        }
    }

    $error = 'Invalid adviser credentials.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adviser Login</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-brand">
                <img src="../Esrlogo.png" alt="ESR Logo">
                <span>Escuela de Sto. Rosario</span>
            </div>
            <h1 class="auth-title">Adviser Portal</h1>
            <p class="auth-subtitle">Upload grade workbooks for registrar review.</p>
            <?php if (!empty($error)): ?>
                <div class="auth-error"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form class="auth-form" action="adviser_login.php" method="POST">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <button type="submit" class="auth-btn">Login</button>
            </form>
            <div class="auth-footer">
                <a href="../index.php">&larr; Back to homepage</a>
            </div>
        </div>
    </div>
</body>
</html>
