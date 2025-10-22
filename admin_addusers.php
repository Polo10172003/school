<?php
include('db_connection.php');
require_once __DIR__ . '/includes/transaction_logger.php';

function admin_ensure_user_schema(mysqli $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $column = $conn->query("SHOW COLUMNS FROM users LIKE 'is_first_login'");
    if ($column instanceof mysqli_result) {
        $exists = $column->num_rows > 0;
        $column->close();
        if (!$exists) {
            $conn->query("ALTER TABLE users ADD COLUMN is_first_login TINYINT(1) NOT NULL DEFAULT 0 AFTER role");
        }
    }

    $checked = true;
}

admin_ensure_user_schema($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = trim($_POST['role'] ?? '');

    if ($fullname === '' || $username === '' || $role === '') {
        die("All fields are required.");
    }

    $initialPassword = '';
    $isFirstLogin = 1;

    $sql = "INSERT INTO users (username, password, fullname, role, is_first_login) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssssi", $username, $initialPassword, $fullname, $role, $isFirstLogin);

    if ($stmt->execute()) {
        transaction_log_record($conn, [
            'category'    => 'user_management',
            'action'      => 'user_created',
            'target_type' => 'user',
            'target_id'   => (string) $stmt->insert_id,
            'description' => sprintf('Created user account for %s (%s).', $fullname, $role),
            'metadata'    => [
                'username'      => $username,
                'role'          => $role,
                'is_first_login'=> true,
            ],
            'context'     => 'admin',
        ]);
        echo "<script>alert('User added successfully! The user will be asked to set a password on first login.'); window.location.href='admin_dashboard.php#users';</script>";
    } else {
        die("Execute failed: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
}
?>
