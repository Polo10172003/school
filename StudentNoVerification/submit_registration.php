<?php
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';

session_start();

$data = $_SESSION['registration'] ?? null;
if (!$data) {
    die("No registration data found. Please complete the registration form.");
}

// Validate email
if (empty($data['emailaddress']) || !filter_var($data['emailaddress'], FILTER_VALIDATE_EMAIL)) {
    die("Invalid or missing email address.");
}

$lrn         = $data['lrn'] ?? '';
$lastname    = $data['lastname'] ?? '';
$firstname   = $data['firstname'] ?? '';
$middlename  = $data['middlename'] ?? '';
$yearlevel   = $data['yearlevel'] ?? '';
$specaddress = $data['specaddress'] ?? '';
$brgy        = $data['brgy'] ?? '';
$city        = $data['city'] ?? '';
$mother      = $data['mother'] ?? '';
$father      = $data['father'] ?? '';
$gname       = $data['gname'] ?? '';
$sex         = $data['sex'] ?? '';
$dob         = $data['dob'] ?? '';
$religion    = $data['religion'] ?? '';
$emailaddress= $data['emailaddress'] ?? '';
$contactno   = $data['contactno'] ?? '';
$schooltype  = $data['schooltype'] ?? '';
$sname       = $data['sname'] ?? '';
$course      = $data['course'] ?? ''; // for SHS

// 🔹 Step 1: Check if LRN exists (Old student)
$check = $conn->prepare("SELECT year, academic_status FROM students_registration WHERE lrn = ? ORDER BY id DESC LIMIT 1");
$check->bind_param("s", $lrn);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $row     = $result->fetch_assoc();
    $lastYear= $row['year'];
    $status  = $row['academic_status'] ?? 'Passed';
    $yearlevel = (strtolower($status) === 'passed') ? getNextGrade($lastYear) : $lastYear;
    $student_type = 'Old';
} else {
    $student_type = 'New';
}
$check->close();

// 🔹 Step 2: Generate Student Number for new students
$student_number = null;
if ($student_type === 'New') {
    do {
        $student_number = "ESR-" . date("Y") . "-" . str_pad(rand(1, 99999), 5, "0", STR_PAD_LEFT);
        $checkNum = $conn->prepare("SELECT id FROM students_registration WHERE student_number = ? LIMIT 1");
        $checkNum->bind_param("s", $student_number);
        $checkNum->execute();
        $checkNum->store_result();
        $exists = $checkNum->num_rows > 0;
        $checkNum->close();
    } while ($exists);
}

// 🔹 Step 3: Save Registration
$stmt = $conn->prepare("INSERT INTO students_registration 
    (student_number, student_type, year, course, lrn, lastname, firstname, middlename, specaddress, brgy, city, mother, father, gname, sex, dob, religion, emailaddress, contactno, schooltype, sname, portal_status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

$stmt->bind_param("sssssssssssssssssssss",
    $student_number, $student_type, $yearlevel, $course, $lrn, $lastname, $firstname, $middlename, $specaddress, $brgy, $city, 
    $mother, $father, $gname, $sex, $dob, $religion, $emailaddress, $contactno, $schooltype, $sname);

if ($stmt->execute()) {
    $student_id = $conn->insert_id;
    $stmt->close();

    // ✅ Prepare email data in session for worker
    $_SESSION['email_job'] = [
        'email' => $emailaddress,
        'name' => "$firstname $lastname",
        'student_number' => $student_number,
        'student_type' => $student_type
    ];

    // ✅ Redirect instantly
    header("Location: success.php?sn=" . urlencode($student_number));
    exit();
} else {
    echo "Error: " . $conn->error;
}

$conn->close();

// 🔹 Helper
function getNextGrade($current) {
    $levels = [
        "Kinder 1","Kinder 2","Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6",
        "Grade 7","Grade 8","Grade 9","Grade 10","Grade 11","Grade 12"
    ];
    $index = array_search($current, $levels);
    return ($index !== false && isset($levels[$index + 1])) ? $levels[$index + 1] : $current;
}
