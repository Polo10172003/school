<?php
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';

session_start();

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
    'father_name' => $father_name,
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

if (!in_array($yearlevel, ['Grade 11', 'Grade 12'], true)) {
    $course = '';
}

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

if ($stmt->execute()) {
    $student_id = $conn->insert_id;
    $stmt->close();

    $php_path = '/Applications/XAMPP/bin/php';
    $worker   = __DIR__ . '/email_worker.php';

    $cmdParts = [
        escapeshellcmd($php_path),
        escapeshellarg($worker),
        escapeshellarg((string) $student_id),
        escapeshellarg($student_type),
        escapeshellarg($emailaddress),
        escapeshellarg($firstname),
        escapeshellarg($lastname)
    ];
    $cmd = implode(' ', $cmdParts);

    exec($cmd . ' 2>&1', $output, $return_var);
    error_log('CMD: ' . $cmd);
    error_log('OUTPUT: ' . print_r($output, true));
    error_log('RETURN: ' . $return_var);

    unset($_SESSION['registration']);
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
