<?php
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../includes/session.php';

require_once __DIR__ . '/cleanup_expired_registrations.php';
require_once __DIR__ . '/email_worker.php';
cleanupExpiredRegistrations($conn);

$data = $_SESSION['registration'] ?? null;
if (!$data) {
    die('No registration data found. Please complete the registration form.');
}

$school_year          = trim($data['school_year'] ?? '');
$yearlevel            = trim($data['yearlevel'] ?? '');
$course               = trim($data['course'] ?? '');
$lastname             = trim($data['lastname'] ?? '');
$firstname            = trim($data['firstname'] ?? '');
$middlename           = trim($data['middlename'] ?? '');
$gender               = trim($data['gender'] ?? '');
$dob                  = trim($data['dob'] ?? '');
$religion             = trim($data['religion'] ?? '');
$emailaddress         = trim($data['emailaddress'] ?? '');
$telephone            = trim($data['telephone'] ?? '');
$address              = trim($data['address'] ?? '');
$last_school_attended = trim($data['last_school_attended'] ?? '');
$academic_honors      = trim($data['academic_honors'] ?? '');
$father_name          = trim($data['father_name'] ?? '');
$father_occupation    = trim($data['father_occupation'] ?? '');
$mother_name          = trim($data['mother_name'] ?? '');
$mother_occupation    = trim($data['mother_occupation'] ?? '');
$guardian_name        = trim($data['guardian_name'] ?? '');
$guardian_occupation  = trim($data['guardian_occupation'] ?? '');

$normalizeName = static function (string $value): string {
    $value = preg_replace('/\s+/', ' ', trim($value));
    if ($value === '') {
        return '';
    }
    if (function_exists('mb_convert_case')) {
        $value = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    } else {
        $value = ucwords(strtolower($value));
    }
    return $value;
};

$lastname = $normalizeName($lastname);
$firstname = $normalizeName($firstname);
$middlename = $normalizeName($middlename);

$required = [
    'school_year' => $school_year,
    'yearlevel' => $yearlevel,
    'lastname' => $lastname,
    'firstname' => $firstname,
    'gender' => $gender,
    'dob' => $dob,
    'religion' => $religion,
    'emailaddress' => $emailaddress,
    'telephone' => $telephone,
    'address' => $address,
    'mother_name' => $mother_name,
];

foreach ($required as $label => $value) {
    if ($value === '') {
        die("Missing required field: {$label}");
    }
}

if (!filter_var($emailaddress, FILTER_VALIDATE_EMAIL)) {
    die('Invalid or missing email address.');
}

if (in_array($yearlevel, ['Grade 11', 'Grade 12'], true) && $course === '') {
    die('Course/strand is required for senior high school applicants.');
}

$student_type = 'New';
$academic_status = 'Ongoing';
$onsitePaymentType = $_SESSION['onsite_payment_type'] ?? '';
$returningSourceId = isset($_SESSION['returning_source_id']) ? (int) $_SESSION['returning_source_id'] : null;
$returningSourceTable = $_SESSION['returning_source_table'] ?? 'active';
$returningInactiveId = isset($_SESSION['returning_inactive_source_id']) ? (int) $_SESSION['returning_inactive_source_id'] : null;
$reactivatedFromInactive = false;
$reactivatedStudentNumber = null;

if ($returningSourceTable === 'inactive' && $returningInactiveId) {
    $student_type = 'Old';
    $reactivatedFromInactive = true;
    $returningSourceId = $returningInactiveId;

    $fetchInactive = $conn->prepare('SELECT student_number FROM inactive_students WHERE id = ? LIMIT 1');
    if ($fetchInactive) {
        $fetchInactive->bind_param('i', $returningInactiveId);
        $fetchInactive->execute();
        $fetchInactive->bind_result($inactiveStudentNumber);
        if ($fetchInactive->fetch()) {
            $reactivatedStudentNumber = $inactiveStudentNumber;
        }
        $fetchInactive->close();
    }

    $existingActive = $conn->prepare('SELECT id FROM students_registration WHERE id = ? LIMIT 1');
    $hasActive = false;
    if ($existingActive) {
        $existingActive->bind_param('i', $returningInactiveId);
        $existingActive->execute();
        $existingActive->store_result();
        $hasActive = $existingActive->num_rows > 0;
        $existingActive->close();
    }

    if (!$hasActive) {
        $copyInactive = $conn->prepare('INSERT INTO students_registration SELECT * FROM inactive_students WHERE id = ?');
        if ($copyInactive) {
            $copyInactive->bind_param('i', $returningInactiveId);
            $copyInactive->execute();
            $copyInactive->close();
        }
        $deleteInactive = $conn->prepare('DELETE FROM inactive_students WHERE id = ?');
        if ($deleteInactive) {
            $deleteInactive->bind_param('i', $returningInactiveId);
            $deleteInactive->execute();
            $deleteInactive->close();
        }
    }
}

if ($returningSourceId) {
    $student_type = 'Old';
} else {
    $lookup = $conn->prepare('SELECT year, academic_status FROM students_registration WHERE firstname = ? AND lastname = ? AND dob = ? ORDER BY id DESC LIMIT 1');
    $lookup->bind_param('sss', $firstname, $lastname, $dob);
    $lookup->execute();
    $result = $lookup->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastYear = $row['year'];
        $lastStatus = strtolower($row['academic_status'] ?? 'ongoing');
        $student_type = 'Old';
        if ($lastStatus === 'passed' || $lastStatus === 'ongoing') {
            $yearlevel = getNextGrade($lastYear);
        } else {
            $yearlevel = $lastYear;
        }
    }
    $lookup->close();
}

if (!in_array($yearlevel, ['Grade 11', 'Grade 12'], true)) {
    $course = '';
}

$student_id = null;

if ($returningSourceId) {
    $updateSql = 'UPDATE students_registration SET school_year = ?, year = ?, course = ?, student_type = ?, lastname = ?, firstname = ?, middlename = ?, gender = ?, dob = ?, religion = ?, emailaddress = ?, telephone = ?, address = ?, last_school_attended = ?, academic_honors = ?, father_name = ?, father_occupation = ?, mother_name = ?, mother_occupation = ?, guardian_name = ?, guardian_occupation = ?, academic_status = ?, section = NULL, adviser = NULL, schedule_sent_at = NULL WHERE id = ?';
    $stmt = $conn->prepare($updateSql);
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $typeString = str_repeat('s', 22) . 'i';
    $stmt->bind_param(
        $typeString,
        $school_year,
        $yearlevel,
        $course,
        $student_type,
        $lastname,
        $firstname,
        $middlename,
        $gender,
        $dob,
        $religion,
        $emailaddress,
        $telephone,
        $address,
        $last_school_attended,
        $academic_honors,
        $father_name,
        $father_occupation,
        $mother_name,
        $mother_occupation,
        $guardian_name,
        $guardian_occupation,
        $academic_status,
        $returningSourceId
    );
    if (!$stmt->execute()) {
        die('Update failed: ' . $stmt->error);
    }
    $stmt->close();
    $student_id = $returningSourceId;
} else {
    $sql = 'INSERT INTO students_registration 
        (school_year, year, course, student_type, lastname, firstname, middlename, gender, dob, religion, emailaddress, telephone, address, last_school_attended, academic_honors, father_name, father_occupation, mother_name, mother_occupation, guardian_name, guardian_occupation, academic_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param(
        'ssssssssssssssssssssss',
        $school_year,
        $yearlevel,
        $course,
        $student_type,
        $lastname,
        $firstname,
        $middlename,
        $gender,
        $dob,
        $religion,
        $emailaddress,
        $telephone,
        $address,
        $last_school_attended,
        $academic_honors,
        $father_name,
        $father_occupation,
        $mother_name,
        $mother_occupation,
        $guardian_name,
        $guardian_occupation,
        $academic_status
    );

    if (!$stmt->execute()) {
        die('Insert failed: ' . $stmt->error);
    }
    $student_id = $conn->insert_id;
    $stmt->close();
}

if ($student_id) {

    if ($reactivatedFromInactive) {
        if ($reactivatedStudentNumber !== null && $reactivatedStudentNumber !== '') {
            $assignNumber = $conn->prepare('UPDATE students_registration SET student_number = ? WHERE id = ?');
            if ($assignNumber) {
                $assignNumber->bind_param('si', $reactivatedStudentNumber, $student_id);
                $assignNumber->execute();
                $assignNumber->close();
            }
        }

        $statusValue = 'ready';
        $updateDropped = $conn->prepare('UPDATE students_registration SET enrollment_status = ?, academic_status = ?, student_type = ?, schedule_sent_at = NULL, section = NULL, adviser = NULL WHERE id = ?');
        if ($updateDropped) {
            $academicLabel = 'Ongoing';
            $studentTypeLabel = 'Old';
            $updateDropped->bind_param('sssi', $statusValue, $academicLabel, $studentTypeLabel, $student_id);
            $updateDropped->execute();
            $updateDropped->close();
        }

        $deleteInactive = $conn->prepare('DELETE FROM inactive_students WHERE id = ?');
        if ($deleteInactive) {
            $deleteInactive->bind_param('i', $returningInactiveId);
            $deleteInactive->execute();
            $deleteInactive->close();
        }


    }

    $workerPath = __DIR__ . '/email_worker.php';
    $disabledRaw = (string) ini_get('disable_functions');
    $disabledList = array_filter(array_map('trim', explode(',', $disabledRaw)));
    $canUseExec = function_exists('exec') && !in_array('exec', $disabledList, true) && is_file($workerPath);

    $emailDispatched = false;

    if ($canUseExec) {
        $phpPath = PHP_BINARY ?: '/usr/bin/php';
        $cmdParts = [
            escapeshellcmd($phpPath),
            escapeshellarg($workerPath),
            escapeshellarg((string) $student_id),
            escapeshellarg($student_type),
            escapeshellarg($emailaddress),
            escapeshellarg($firstname),
            escapeshellarg($lastname),
        ];
        $cmd = implode(' ', $cmdParts);

        exec($cmd . ' 2>&1', $output, $return_var);
        if ($return_var !== 0) {
            error_log('Email worker exec failed. Falling back to inline handler. Output: ' . print_r($output, true));
        } else {
            $emailDispatched = true;
        }
    }

    if (!$emailDispatched) {
        $inlineResult = email_worker_process($student_id, $student_type, $emailaddress, $firstname, $lastname, $conn);
        if (!$inlineResult) {
            error_log('Email worker inline fallback failed for student ID ' . $student_id);
        }
    }

    unset($_SESSION['registration']);
    unset($_SESSION['returning_source_id'], $_SESSION['returning_student_number'], $_SESSION['registration_returning_tag'], $_SESSION['registration_previous_school_year'], $_SESSION['returning_inactive_source_id'], $_SESSION['returning_source_table'], $_SESSION['portal_returning_student_id']);

    if (!$reactivatedFromInactive && ($onsitePaymentType === 'onsite' || $onsitePaymentType === 'online')) {
        $statusStmt = $conn->prepare("UPDATE students_registration SET enrollment_status = 'waiting' WHERE id = ?");
        if ($statusStmt) {
            $statusStmt->bind_param('i', $student_id);
            $statusStmt->execute();
            $statusStmt->close();
        }
    }

    $schoolYearParam = $school_year !== '' ? $school_year : null;
    if ($onsitePaymentType === 'onsite' || $onsitePaymentType === 'online') {
        $studentIdForPayment = (string) $student_id;
        $feeSnapshot = fetchTuitionFeeSnapshot($conn, $yearlevel, $student_type, $schoolYearParam);
        $tuitionFeeId = $feeSnapshot['id'] ?? null;

        $checkPlanTable = $conn->query("SHOW TABLES LIKE 'student_plan_selections'");
        if ($checkPlanTable instanceof mysqli_result) {
            $checkPlanTable->free_result();
        }
    }

    if ($onsitePaymentType === 'onsite') {
        unset($_SESSION['onsite_payment_type']);
        header('Location: ../Registrar/registrar_dashboard.php?msg=student_added_pending_cash');
        exit();
    }

    if ($onsitePaymentType === 'online') {
        unset($_SESSION['onsite_payment_type']);
        header('Location: ../Portal/choose_payment.php?student_id=' . urlencode((string) $student_id));
        exit();
    }

    unset($_SESSION['onsite_payment_type']);
    header('Location: success.php');
    exit();
}

echo 'Error: ' . $conn->error;

$conn->close();

function getNextGrade($current)
{
    $levels = [
        'Kindergarten',
        'Grade 1',
        'Grade 2',
        'Grade 3',
        'Grade 4',
        'Grade 5',
        'Grade 6',
        'Grade 7',
        'Grade 8',
        'Grade 9',
        'Grade 10',
        'Grade 11',
        'Grade 12'
    ];

    $index = array_search($current, $levels, true);
    return ($index !== false && isset($levels[$index + 1])) ? $levels[$index + 1] : $current;
}

function normalizeGradeLabel(string $label): string
{
    $normalized = strtolower(trim($label));
    $normalized = str_replace(['-', '_'], '', $normalized);
    $normalized = preg_replace('/\s+/', '', $normalized);
    return $normalized;
}

function fetchTuitionFeeSnapshot(mysqli $conn, string $gradeLevel, string $studentType, ?string $schoolYear = null): ?array
{
    $normalizedGrade = normalizeGradeLabel($gradeLevel);
    $studentType = strtolower(trim($studentType));

    $sql = "SELECT id, school_year, entrance_fee, tuition_fee, miscellaneous_fee, LOWER(student_type) AS st
            FROM tuition_fees
            WHERE REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') = ?
            ORDER BY school_year DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $normalizedGrade);
    $stmt->execute();
    $result = $stmt->get_result();

    $selectedRow = null;
    while ($row = $result->fetch_assoc()) {
        $rowType = strtolower(trim((string) ($row['st'] ?? '')));
        if ($rowType !== $studentType && $rowType !== 'all') {
            continue;
        }

        $total = 0.0;
        $total += (float) ($row['entrance_fee'] ?? 0);
        $total += (float) ($row['tuition_fee'] ?? 0);
        $total += (float) ($row['miscellaneous_fee'] ?? 0);

        if ($schoolYear !== null && $schoolYear !== '' && isset($row['school_year']) && trim((string) $row['school_year']) !== $schoolYear) {
            if ($selectedRow === null) {
                $selectedRow = ['row' => $row, 'total' => $total];
            }
            continue;
        }

        $selectedRow = ['row' => $row, 'total' => $total];
        break;
    }

    $stmt->close();

    if ($selectedRow === null) {
        return null;
    }

    return [
        'id' => isset($selectedRow['row']['id']) ? (int) $selectedRow['row']['id'] : null,
        'school_year' => $selectedRow['row']['school_year'] ?? null,
        'total' => (float) ($selectedRow['total'] ?? 0),
        'details' => $selectedRow['row'],
    ];
}
