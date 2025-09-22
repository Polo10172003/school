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

// 🔹 Student Info
$lrn         = $data['lrn'] ?? '';
$lastname    = $data['lastname'] ?? '';
$firstname   = $data['firstname'] ?? '';
$middlename  = $data['middlename'] ?? '';
$suffixname  = $data['suffixname'] ?? '';
$yearlevel   = $data['yearlevel'] ?? '';
$course      = $data['course'] ?? '';
$gender      = $data['gender'] ?? '';
$status      = $data['status'] ?? '';
$citizenship = $data['citizenship'] ?? '';
$dob         = $data['dob'] ?? '';
$birthplace  = $data['birthplace'] ?? '';
$religion    = $data['religion'] ?? '';
$emailaddress= $data['emailaddress'] ?? '';
$mobnumber   = $data['mobnumber'] ?? '';
$telnumber   = $data['telnumber'] ?? '';

if (empty($mobnumber)) {
    die("Mobile number is required."); // ✅ prevents NULL insert
}

// 🔹 Current Address
$streetno = $data['streetno'] ?? '';
$street   = $data['street'] ?? '';
$subd     = $data['subd'] ?? '';
$brgy     = $data['brgy'] ?? '';
$city     = $data['city'] ?? '';
$province = $data['province'] ?? '';
$zipcode  = $data['zipcode'] ?? '';

// 🔹 Permanent Address
$p_streetno = $data['p_streetno'] ?? '';
$p_street   = $data['p_street'] ?? '';
$p_subd     = $data['p_subd'] ?? '';
$p_brgy     = $data['p_brgy'] ?? '';
$p_city     = $data['p_city'] ?? '';
$p_province = $data['p_province'] ?? '';
$p_zipcode  = $data['p_zipcode'] ?? '';

// 🔹 Parents/Guardian Info
$father_lastname     = $data['father_lastname'] ?? '';
$father_firstname    = $data['father_firstname'] ?? '';
$father_middlename   = $data['father_middlename'] ?? '';
$father_suffixname   = $data['father_suffixname'] ?? '';
$father_mobnumber    = $data['father_mobnumber'] ?? '';
$father_emailaddress = $data['father_emailaddress'] ?? '';
$father_occupation   = $data['father_occupation'] ?? '';

$mother_lastname     = $data['mother_lastname'] ?? '';
$mother_firstname    = $data['mother_firstname'] ?? '';
$mother_middlename   = $data['mother_middlename'] ?? '';
$mother_suffixname   = $data['mother_suffixname'] ?? '';
$mother_mobnumber    = $data['mother_mobnumber'] ?? '';
$mother_emailaddress = $data['mother_emailaddress'] ?? '';
$mother_occupation   = $data['mother_occupation'] ?? '';

$guardian_lastname     = $data['guardian_lastname'] ?? '';
$guardian_firstname    = $data['guardian_firstname'] ?? '';
$guardian_middlename   = $data['guardian_middlename'] ?? '';
$guardian_suffixname   = $data['guardian_suffixname'] ?? '';
$guardian_mobnumber    = $data['guardian_mobnumber'] ?? '';
$guardian_emailaddress = $data['guardian_emailaddress'] ?? '';
$guardian_occupation   = $data['guardian_occupation'] ?? '';
$guardian_relationship = $data['guardian_relationship'] ?? '';

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

$student_number = null;

// 🔹 Step 3: Save Registration
// 🔹 Insert Query (52 columns)
$sql = "INSERT INTO students_registration 
    (year, course, lrn, lastname, firstname, middlename, suffixname,
     gender, status, citizenship, dob, birthplace, religion,
     streetno, street, subd, brgy, city, province, zipcode,
     p_streetno, p_street, p_subd, p_brgy, p_city, p_province, p_zipcode,
     emailaddress, mobnumber, telnumber,
     father_lastname, father_firstname, father_middlename, father_suffixname, father_mobnumber, father_emailaddress, father_occupation,
     mother_lastname, mother_firstname, mother_middlename, mother_suffixname, mother_mobnumber, mother_emailaddress, mother_occupation,
     guardian_lastname, guardian_firstname, guardian_middlename, guardian_suffixname, guardian_mobnumber, guardian_emailaddress, guardian_occupation, guardian_relationship
    )
    VALUES (" . str_repeat("?,", 51) . "?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// 🔹 Bind 52 variables
$stmt->bind_param(str_repeat("s", 52),
    $yearlevel, $course, $lrn, $lastname, $firstname, $middlename, $suffixname,
    $gender, $status, $citizenship, $dob, $birthplace, $religion,
    $streetno, $street, $subd, $brgy, $city, $province, $zipcode,
    $p_streetno, $p_street, $p_subd, $p_brgy, $p_city, $p_province, $p_zipcode,
    $emailaddress, $mobnumber, $telnumber,
    $father_lastname, $father_firstname, $father_middlename, $father_suffixname, $father_mobnumber, $father_emailaddress, $father_occupation,
    $mother_lastname, $mother_firstname, $mother_middlename, $mother_suffixname, $mother_mobnumber, $mother_emailaddress, $mother_occupation,
    $guardian_lastname, $guardian_firstname, $guardian_middlename, $guardian_suffixname, $guardian_mobnumber, $guardian_emailaddress, $guardian_occupation, $guardian_relationship
);


// 🔹 Email Worker
if ($stmt->execute()) {
    $student_id = $conn->insert_id;
    $stmt->close();

    $php_path = "/Applications/XAMPP/bin/php";
    $worker   = __DIR__ . "/email_worker.php";

    // Escape arguments properly
    $cmd = sprintf(
        '%s %s %d %s %s %s',
        escapeshellcmd($php_path),
        escapeshellarg($worker),
        $student_id,
        escapeshellarg($student_type),
        escapeshellarg($emailaddress),
        escapeshellarg("$firstname $lastname")
    );

    // Capture debug output
    exec($cmd . " 2>&1", $output, $return_var);
    error_log("CMD: " . $cmd);
    error_log("OUTPUT: " . print_r($output, true));
    error_log("RETURN: " . $return_var);

    unset($_SESSION['registration']);
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
