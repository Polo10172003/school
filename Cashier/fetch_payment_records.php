<?php
declare(strict_types=1);

define('SESSION_GUARD_JSON', true);

require_once __DIR__ . '/../includes/session.php';

if (empty($_SESSION['cashier_username'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

include __DIR__ . '/../db_connection.php';
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit();
}

$conn->set_charset('utf8mb4');
@$conn->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_uca1400_ai_ci'");
@$conn->query("SET collation_connection = 'utf8mb4_uca1400_ai_ci'");

require_once __DIR__ . '/cashier_dashboard_logic.php';

$paymentsResult = cashier_dashboard_fetch_payments($conn);
$payments = [];
$pendingCount = 0;
$pendingStatuses = ['pending', 'processing', 'review'];

if ($paymentsResult instanceof mysqli_result) {
    while ($row = $paymentsResult->fetch_assoc()) {
        $statusRaw = strtolower(trim((string) ($row['payment_status'] ?? '')));
        if (in_array($statusRaw, $pendingStatuses, true)) {
            $pendingCount++;
        }

        $payments[] = [
            'id'         => (int) ($row['id'] ?? 0),
            'student_id' => (int) ($row['student_id'] ?? 0),
            'created_at' => isset($row['created_at']) ? date('Y-m-d', strtotime((string) $row['created_at'])) : '',
            'student'    => trim(($row['lastname'] ?? '') . ', ' . ($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? '')),
            'payment_type' => (string) ($row['payment_type'] ?? ''),
            'amount'       => (float) ($row['amount'] ?? 0),
            'status'       => (string) ($row['payment_status'] ?? 'Pending'),
            'or_number'    => $row['or_number'] ?? null,
            'reference_number' => $row['reference_number'] ?? null,
            'screenshot_path'  => $row['screenshot_path'] ?? null,
        ];
    }
    $paymentsResult->close();
}

$defaultLabel = 'View Payment Records' . ($pendingCount > 0 ? " ({$pendingCount} pending)" : '');
$activeLabel = 'Hide Payment Records';

echo json_encode([
    'success' => true,
    'pending_count' => $pendingCount,
    'default_label' => $defaultLabel,
    'active_label'  => $activeLabel,
    'payments'      => $payments,
]);

$conn->close();
