<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_dashboard.php#users');
    exit();
}

$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
if ($userId <= 0) {
    $_SESSION['admin_users_error'] = 'Invalid user selected.';
    header('Location: admin_dashboard.php#users');
    exit();
}

include 'db_connection.php';

$stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
if (!$stmt) {
    $_SESSION['admin_users_error'] = 'Unable to delete user right now. Please try again later.';
    header('Location: admin_dashboard.php#users');
    exit();
}

$stmt->bind_param('i', $userId);
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['admin_users_success'] = 'User removed successfully.';
    } else {
        $_SESSION['admin_users_error'] = 'User not found or already removed.';
    }
} else {
    $_SESSION['admin_users_error'] = 'Failed to delete user. Please try again.';
}

$stmt->close();
$conn->close();

header('Location: admin_dashboard.php#users');
exit();
