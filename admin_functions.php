<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function dashboard_fetch_user(mysqli $conn, string $username): ?array
{
    $stmt = $conn->prepare('SELECT fullname, role FROM users WHERE username = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

function admin_get_current_user(mysqli $conn): ?array
{
    $username = $_SESSION['admin_username'] ?? null;
    if (!$username) {
        return null;
    }
    return dashboard_fetch_user($conn, $username);
}

function admin_get_user_list(mysqli $conn): array
{
    $rows = [];
    $result = $conn->query('SELECT id, username, fullname, role FROM users ORDER BY role, fullname');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->close();
    }
    return $rows;
}
