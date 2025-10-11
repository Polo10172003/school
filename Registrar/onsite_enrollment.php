<?php
require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/../db_connection.php';

$student_type  = $_GET['student_type']  ?? '';
$student_number= $_GET['student_number']           ?? '';
$payment_type  = $_GET['payment_type']  ?? '';

// ---------------- NEW STUDENT ----------------
if ($student_type === 'new') {
    // Clear any old registration session data for new student
    unset($_SESSION['registration']);
    unset($_SESSION['registration_returning_tag'], $_SESSION['registration_previous_school_year'], $_SESSION['returning_inactive_source_id'], $_SESSION['returning_source_table']);

    // ✅ Preserve the registrar's payment choice for submit_registration.php
    if (!empty($payment_type)) {
        $_SESSION['onsite_payment_type'] = $payment_type; // 'onsite' or 'online'
    }

    // Route straight to the core registration form used online so staff can encode onsite
    $q = !empty($payment_type) ? ('?payment_type=' . urlencode($payment_type)) : '';
    header('Location: ../StudentNoVerification/ineeditNaregistration.php' . $q);
    exit();
}

// ---------------- OLD STUDENT ----------------
if ($student_type === 'old') {
    if (!$student_number) {
        header('Location: registrar_dashboard.php?msg=old_student_not_found');
        exit();
    }

    if (!empty($payment_type)) {
        $_SESSION['onsite_payment_type'] = $payment_type;
    }

    $isInactiveSource = false;
    $stmt = $conn->prepare("SELECT * FROM students_registration WHERE student_number = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $student_number);
    $stmt->execute();
    $result  = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();

    if (!$student) {
        $stmt = $conn->prepare("SELECT * FROM inactive_students WHERE student_number = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("s", $student_number);
        $stmt->execute();
        $result  = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();
        if ($student) {
            $isInactiveSource = true;
        }
    }

    if (!$student) {
        header('Location: registrar_dashboard.php?msg=old_student_not_found');
        exit();
    }

    $studentId       = (int) ($student['id'] ?? 0);
    $currentYear     = trim((string) ($student['year'] ?? ''));
    $academicStatus  = strtolower(trim((string) ($student['academic_status'] ?? '')));

    $nextGrade = static function ($grade) {
        $map = [
            "Pre-Prime 1" => "Pre-Prime 2",
            "Pre-Prime 2" => "Kindergarten",
            "Pre-Prime 1 & 2" => "Kindergarten",
            "Kindergarten" => "Grade 1",
            "Kinder 1" => "Kindergarten",
            "Kinder 2" => "Grade 1",
            "Grade 1" => "Grade 2",
            "Grade 2" => "Grade 3",
            "Grade 3" => "Grade 4",
            "Grade 4" => "Grade 5",
            "Grade 5" => "Grade 6",
            "Grade 6" => "Grade 7",
            "Grade 7" => "Grade 8",
            "Grade 8" => "Grade 9",
            "Grade 9" => "Grade 10",
            "Grade 10" => "Grade 11",
            "Grade 11" => "Grade 12",
            "Grade 12" => "Grade 12"
        ];
        return $map[$grade] ?? $grade;
    };

    if ($academicStatus === 'graduated') {
        header('Location: registrar_dashboard.php?msg=update_status_required');
        exit();
    }

    $suggestedGrade = ($academicStatus === 'passed')
        ? $nextGrade($currentYear)
        : $currentYear;

    $prefill = [
        'school_year'          => $student['school_year'] ?? '',
        'yearlevel'            => $suggestedGrade,
        'course'               => $student['course'] ?? '',
        'lastname'             => $student['lastname'] ?? '',
        'firstname'            => $student['firstname'] ?? '',
        'middlename'           => $student['middlename'] ?? '',
        'gender'               => $student['gender'] ?? '',
        'dob'                  => $student['dob'] ?? '',
        'religion'             => $student['religion'] ?? '',
        'emailaddress'         => $student['emailaddress'] ?? '',
        'telephone'            => $student['telephone'] ?? '',
        'address'              => $student['address'] ?? '',
        'last_school_attended' => $student['last_school_attended'] ?? '',
        'academic_honors'      => $student['academic_honors'] ?? '',
        'father_name'          => $student['father_name'] ?? '',
        'father_occupation'    => $student['father_occupation'] ?? '',
        'mother_name'          => $student['mother_name'] ?? '',
        'mother_occupation'    => $student['mother_occupation'] ?? '',
        'guardian_name'        => $student['guardian_name'] ?? '',
        'guardian_occupation'  => $student['guardian_occupation'] ?? '',
    ];

    if ($isInactiveSource && strcasecmp($academicStatus, 'dropped') === 0) {
        $_SESSION['registration_returning_tag'] = 'Dropped Student';
        $_SESSION['registration_previous_school_year'] = $student['school_year'] ?? '';
        $prefill['school_year'] = '';
        $_SESSION['returning_inactive_source_id'] = $studentId;
        $_SESSION['returning_source_table'] = 'inactive';
    } else {
        unset($_SESSION['registration_previous_school_year']);
        $_SESSION['registration_returning_tag'] = '';
        $_SESSION['returning_inactive_source_id'] = null;
        $_SESSION['returning_source_table'] = 'active';
    }

    $_SESSION['registration'] = $prefill;
    $_SESSION['returning_student_number'] = $student_number;
    $_SESSION['returning_source_id'] = $studentId;

    header('Location: ../StudentNoVerification/ineeditNaregistration.php?returning=1');
    exit();
}
?>
