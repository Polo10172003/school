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

$paymentRows = cashier_dashboard_fetch_payments($conn);
$payments = [];
$pendingCount = 0;
$pendingStatuses = ['pending', 'processing', 'review'];

foreach ($paymentRows as $row) {
    $statusRaw = strtolower(trim((string) ($row['status_normalized'] ?? $row['payment_status'] ?? '')));
    if (in_array($statusRaw, $pendingStatuses, true)) {
        $pendingCount++;
    }

    $studentLabel = trim(($row['lastname'] ?? '') . ', ' . ($row['firstname'] ?? '') . ' ' . ($row['middlename'] ?? ''));
    $payments[] = [
        'record_category'  => $row['record_category'] ?? 'tuition',
        'id'               => (int) ($row['id'] ?? 0),
        'student_id'       => (int) ($row['student_id'] ?? 0),
        'created_at'       => $row['created_at_display'] ?? '',
        'student'          => $studentLabel,
        'payment_type'     => $row['payment_type'] ?? '',
        'display_type'     => $row['display_type'] ?? ($row['payment_type'] ?? ''),
        'amount'           => (float) ($row['amount'] ?? 0),
        'status'           => $row['payment_status'] ?? 'Pending',
        'or_number'        => $row['or_number'] ?? null,
        'reference_number' => $row['reference_number'] ?? null,
        'screenshot_path'  => $row['screenshot_path'] ?? null,
        'other_label'      => $row['other_label'] ?? null,
    ];
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
