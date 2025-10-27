<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/transaction_logger.php';

/**
 * Ensure the archive_users table exists before storing archived records.
 */
function admin_ensure_archive_users_table(mysqli $conn): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS archive_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    username VARCHAR(191) NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(191) NOT NULL,
    role VARCHAR(50) NOT NULL,
    is_first_login TINYINT(1) NOT NULL DEFAULT 0,
    archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    archived_by_username VARCHAR(191) NULL,
    INDEX idx_archive_users_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($sql)) {
        throw new RuntimeException('Unable to ensure archive storage.');
    }

    $ensured = true;
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

$transactionActive = false;

try {
    admin_ensure_archive_users_table($conn);

    if (!$conn->begin_transaction()) {
        throw new RuntimeException('Failed to start archive transaction.');
    }
    $transactionActive = true;

    $fetch = $conn->prepare('SELECT id, username, password, fullname, role, is_first_login FROM users WHERE id = ? LIMIT 1');
    if (!$fetch) {
        throw new RuntimeException('Unable to prepare user lookup.');
    }
    $fetch->bind_param('i', $userId);
    $fetch->execute();
    $result = $fetch->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $fetch->close();

    if (!$user) {
        $_SESSION['admin_users_error'] = 'User not found or already archived.';
        throw new RuntimeException('User not found for archiving.');
    }

    $actor = transaction_log_resolve_actor($conn, ['context' => 'admin']);
    $archivedByUsername = $actor['username'] ?? null;

    $insert = $conn->prepare('INSERT INTO archive_users (user_id, username, password, fullname, role, is_first_login, archived_at, archived_by_username) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)');
    if (!$insert) {
        throw new RuntimeException('Unable to prepare archive insert.');
    }

    $userIdParam = (int) ($user['id'] ?? 0);
    $usernameParam = (string) ($user['username'] ?? '');
    $passwordParam = (string) ($user['password'] ?? '');
    $fullnameParam = (string) ($user['fullname'] ?? $usernameParam);
    $roleParam = (string) ($user['role'] ?? '');
    $firstLoginParam = (int) ($user['is_first_login'] ?? 0);
    $archiverParam = $archivedByUsername !== null ? (string) $archivedByUsername : null;

    $insert->bind_param(
        'issssis',
        $userIdParam,
        $usernameParam,
        $passwordParam,
        $fullnameParam,
        $roleParam,
        $firstLoginParam,
        $archiverParam
    );

    if (!$insert->execute()) {
        throw new RuntimeException('Unable to archive user record.');
    }

    $insert->close();

    $delete = $conn->prepare('DELETE FROM users WHERE id = ?');
    if (!$delete) {
        throw new RuntimeException('Unable to prepare user removal.');
    }
    $delete->bind_param('i', $userIdParam);
    if (!$delete->execute() || $delete->affected_rows < 1) {
        throw new RuntimeException('User removal failed.');
    }
    $delete->close();

    $conn->commit();
    $transactionActive = false;

    try {
        transaction_log_record($conn, [
            'category'    => 'user_management',
            'action'      => 'user_archived',
            'target_type' => 'user',
            'target_id'   => (string) $userIdParam,
            'description' => sprintf('Archived user account for %s (%s).', $fullnameParam, $roleParam ?: 'unknown'),
            'metadata'    => [
                'username'            => $usernameParam,
                'role'                => $roleParam,
                'archived_by_username'=> $archiverParam,
            ],
            'context'     => 'admin',
        ]);
    } catch (Throwable $logError) {
        error_log('[admin] user archive log failure: ' . $logError->getMessage());
    }

    $_SESSION['admin_users_success'] = 'User archived successfully.';
} catch (Throwable $error) {
    if ($transactionActive) {
        $conn->rollback();
    }
    if (!isset($_SESSION['admin_users_error'])) {
        $_SESSION['admin_users_error'] = 'Failed to archive user. Please try again.';
    }
    error_log('[admin] user archive failure: ' . $error->getMessage());
}

$conn->close();

header('Location: admin_dashboard.php#users');
exit();
