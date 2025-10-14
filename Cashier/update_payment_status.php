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
$requested_student_id = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
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

$student_stmt = $conn->prepare('
    SELECT
        sp.student_id AS payment_student_id,
        sr.id AS matched_student_id,
        sr.emailaddress,
        sr.firstname,
        sr.lastname,
        sr.year,
        sp.payment_type,
        sp.amount,
        sp.grade_level
    FROM student_payments sp
    LEFT JOIN students_registration sr ON sr.id = sp.student_id
    WHERE sp.id = ?
    LIMIT 1
');
$student_stmt->bind_param('i', $id);
$student_stmt->execute();
$studentResult = $student_stmt->get_result();
$studentRow = $studentResult ? $studentResult->fetch_assoc() : null;
$student_stmt->close();

$payment_student_id = (int) ($studentRow['payment_student_id'] ?? 0);
$matched_student_id = (int) ($studentRow['matched_student_id'] ?? 0);

$target_student_id = $requested_student_id > 0 ? $requested_student_id : ($matched_student_id ?: $payment_student_id);

if ($target_student_id <= 0 || !$studentRow) {
    echo json_encode(['success' => false, 'error' => 'Payment found, but student not found.']);
    exit();
}

$email = $studentRow['emailaddress'] ?? '';
$firstname = $studentRow['firstname'] ?? '';
$lastname = $studentRow['lastname'] ?? '';
$current_grade_level = $studentRow['year'] ?? null;
$payment_type = $studentRow['payment_type'] ?? '';
$amount = (float) ($studentRow['amount'] ?? 0);
$existing_grade_level = $studentRow['grade_level'] ?? null;

if ($matched_student_id !== $target_student_id) {
    $student_lookup = $conn->prepare('SELECT emailaddress, firstname, lastname, year FROM students_registration WHERE id = ? LIMIT 1');
    $student_lookup->bind_param('i', $target_student_id);
    $student_lookup->execute();
    $student_lookup->bind_result($lookup_email, $lookup_firstname, $lookup_lastname, $lookup_year);
    $hasLookup = $student_lookup->fetch();
    $student_lookup->close();

    if ($hasLookup) {
        $email = $lookup_email ?? $email;
        $firstname = $lookup_firstname ?? $firstname;
        $lastname = $lookup_lastname ?? $lastname;
        $current_grade_level = $lookup_year ?? $current_grade_level;

        $updatePaymentStudent = $conn->prepare('UPDATE student_payments SET student_id = ?, firstname = ?, lastname = ?, grade_level = CASE WHEN grade_level IS NULL OR grade_level = \'\' THEN ? ELSE grade_level END WHERE id = ?');
        if ($updatePaymentStudent) {
            $gradeForUpdate = $current_grade_level ?? '';
            $updatePaymentStudent->bind_param('isssi', $target_student_id, $firstname, $lastname, $gradeForUpdate, $id);
            $updatePaymentStudent->execute();
            $updatePaymentStudent->close();
        }
    } else {
        $target_student_id = $matched_student_id ?: $payment_student_id;
    }
}

$student_id = $target_student_id;

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
