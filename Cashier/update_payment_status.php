<?php
include __DIR__ . '/../db_connection.php';

header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$status = $_POST['status'] ?? '';
$or_number = isset($_POST['or_number']) ? trim($_POST['or_number']) : null;
if ($or_number === '') {
    $or_number = null;
}

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid payment id.']);
    exit();
}

if (!in_array($status, ['paid', 'declined'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status.']);
    exit();
}

$type_stmt = $conn->prepare('SELECT payment_type FROM student_payments WHERE id = ?');
$type_stmt->bind_param('i', $id);
$type_stmt->execute();
$type_stmt->bind_result($payment_type);
$type_stmt->fetch();
$type_stmt->close();

if (!$payment_type) {
    echo json_encode(['success' => false, 'error' => 'Payment not found.']);
    exit();
}

if ($status === 'paid') {
    $payment_date = date('Y-m-d');

    if (strtolower($payment_type) === 'cash') {
        $stmt = $conn->prepare('UPDATE student_payments SET payment_status = ?, payment_date = ?, or_number = COALESCE(?, or_number) WHERE id = ?');
        $stmt->bind_param('sssi', $status, $payment_date, $or_number, $id);
    } else {
        $stmt = $conn->prepare('UPDATE student_payments SET payment_status = ?, payment_date = ?, reference_number = COALESCE(?, reference_number) WHERE id = ?');
        $stmt->bind_param('sssi', $status, $payment_date, $or_number, $id);
    }
} else {
    $stmt = $conn->prepare('UPDATE student_payments SET payment_status = ?, payment_date = NULL WHERE id = ?');
    $stmt->bind_param('si', $status, $id);
}

if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'Error updating payment.']);
    exit();
}
$stmt->close();

$student_stmt = $conn->prepare('SELECT sr.id, sr.emailaddress, sr.firstname, sr.lastname, sr.year, sp.payment_type, sp.amount, sp.grade_level FROM student_payments sp JOIN students_registration sr ON sr.id = sp.student_id WHERE sp.id = ?');
$student_stmt->bind_param('i', $id);
$student_stmt->execute();
$student_stmt->bind_result($student_id, $email, $firstname, $lastname, $current_grade_level, $payment_type, $amount, $existing_grade_level);
$student_stmt->fetch();
$student_stmt->close();

if (!$student_id) {
    echo json_encode(['success' => false, 'error' => 'Payment found, but student not found.']);
    exit();
}

if ($status === 'paid' && $existing_grade_level === null && !empty($current_grade_level)) {
    $gradeUpdate = $conn->prepare('UPDATE student_payments SET grade_level = ? WHERE id = ?');
    $gradeUpdate->bind_param('si', $current_grade_level, $id);
    $gradeUpdate->execute();
    $gradeUpdate->close();
}

if ($status === 'paid') {
    $enroll_stmt = $conn->prepare("UPDATE students_registration SET enrollment_status = 'enrolled' WHERE id = ?");
    $enroll_stmt->bind_param('i', $student_id);
    $enroll_stmt->execute();
    $enroll_stmt->close();

    $statusCheck = $conn->prepare('SELECT academic_status FROM students_registration WHERE id = ?');
    if ($statusCheck) {
        $statusCheck->bind_param('i', $student_id);
        $statusCheck->execute();
        $statusCheck->bind_result($currentAcademic);
        if ($statusCheck->fetch()) {
            $normalizedAcademic = strtolower(trim((string) $currentAcademic));
            if (in_array($normalizedAcademic, ['failed', 'dropped'], true)) {
                $statusCheck->close();
                $restoreStatus = $conn->prepare("UPDATE students_registration SET academic_status = 'Ongoing' WHERE id = ?");
                if ($restoreStatus) {
                    $restoreStatus->bind_param('i', $student_id);
                    $restoreStatus->execute();
                    $restoreStatus->close();
                }
            } else {
                $statusCheck->close();
            }
        } else {
            $statusCheck->close();
        }
    }
}

if ($status === 'paid') {
    if (!function_exists('cashier_assign_section_if_needed')) {
        require_once __DIR__ . '/section_assignment.php';
    }
    cashier_assign_section_if_needed($conn, (int) $student_id);
}

if (!empty($email)) {
    if (!function_exists('cashier_email_worker_process')) {
        require_once __DIR__ . '/email_worker.php';
    }

    try {
        $inlineResult = cashier_email_worker_process((int) $student_id, (string) $payment_type, (float) $amount, (string) $status, $conn);
        if (!$inlineResult) {
            error_log('[cashier] update_payment_status inline worker failed for payment ' . $id);
        }
    } catch (Throwable $workerError) {
        error_log('[cashier] update_payment_status worker exception for payment ' . $id . ': ' . $workerError->getMessage());
    }

    $workerPath = __DIR__ . '/email_worker.php';
    $disabledRaw = (string) ini_get('disable_functions');
    $disabledList = array_filter(array_map('trim', explode(',', $disabledRaw)));
    $canUseExec = function_exists('exec') && !in_array('exec', $disabledList, true) && is_file($workerPath);

    if ($canUseExec) {
        $phpPath = PHP_BINARY ?: '/usr/bin/php';
        $cmdParts = [
            escapeshellcmd($phpPath),
            escapeshellarg($workerPath),
            escapeshellarg((string) $student_id),
            escapeshellarg($payment_type),
            escapeshellarg((string) $amount),
            escapeshellarg($status),
        ];
        $cmd = implode(' ', $cmdParts);
        exec($cmd . ' > /dev/null 2>&1');
    }
}

$conn->close();
echo json_encode(['success' => true, 'status' => $status]);
