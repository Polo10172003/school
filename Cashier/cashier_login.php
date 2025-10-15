<?php
require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/../db_connection.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'cashier'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $cashier = $result->fetch_assoc();

    if ($cashier) {
        $storedPassword = $cashier['password'] ?? '';
        $isHashed = password_get_info((string) $storedPassword)['algo'] !== 0;
        $authenticated = false;

        if ($isHashed && password_verify($password, $storedPassword)) {
            $authenticated = true;
            if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                $rehash = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                if ($update) {
                    $update->bind_param('si', $rehash, $cashier['id']);
                    $update->execute();
                    $update->close();
                }
            }
        } elseif (!$isHashed && hash_equals((string) $storedPassword, $password)) {
            $authenticated = true;
            $rehash = password_hash($password, PASSWORD_DEFAULT);
            $updateLegacy = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            if ($updateLegacy) {
                $updateLegacy->bind_param('si', $rehash, $cashier['id']);
                $updateLegacy->execute();
                $updateLegacy->close();
            }
        }

        if ($authenticated) {
            session_regenerate_id(true);
            $_SESSION['cashier_username'] = $cashier['username'];
            $_SESSION['cashier_fullname'] = $cashier['fullname'] ?? $cashier['username'];
            $_SESSION['cashier_role'] = $cashier['role'] ?? 'cashier';
            session_guard_store($conn, 'cashier', $cashier['username']);
            header("Location: cashier_dashboard.php");
            exit;
        }
    }

    $error = "Invalid cashier credentials.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Login</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-brand">
                <img src="../Esrlogo.png" alt="ESR Logo">
                <span>Escuela de Sto. Rosario</span>
            </div>
            <h1 class="auth-title">Cashier Portal</h1>
            <p class="auth-subtitle">Sign in to process student payments and receipts.</p>
            <?php if (!empty($error)): ?>
                <div class="auth-error"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form class="auth-form" action="cashier_login.php" method="POST">
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
    <script src="../assets/js/auth_forms.js"></script>
</body>
</html>
