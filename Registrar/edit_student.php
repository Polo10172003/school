<?php
require_once __DIR__ . '/../includes/session.php';

include __DIR__ . '/../db_connection.php';

$studentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($studentId <= 0) {
    header('Location: registrar_dashboard.php?msg=student_missing');
    exit;
}

$sql = 'SELECT id, school_year, year, course, student_type, lastname, firstname, middlename, gender, dob, religion, emailaddress, telephone, address, last_school_attended, academic_honors, father_name, father_occupation, mother_name, mother_occupation, guardian_name, guardian_occupation, academic_status FROM students_registration WHERE id = ? LIMIT 1';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Location: registrar_dashboard.php?msg=student_missing');
    exit;
}
$stmt->bind_param('i', $studentId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$student) {
    header('Location: registrar_dashboard.php?msg=student_missing');
    exit;
}

$registrationData = [
    'school_year'          => $student['school_year'] ?? '',
    'yearlevel'            => $student['year'] ?? '',
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
    'student_type'         => $student['student_type'] ?? '',
    'academic_status'      => $student['academic_status'] ?? '',
];

$_SESSION['registration'] = $registrationData;
$_SESSION['registrar_edit_student_id'] = $studentId;
$_SESSION['registrar_edit_original'] = $student;

header('Location: ../StudentNoVerification/review_registration.php?mode=registrar');
exit;
