<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 
include('db_connection.php');

session_start();

$data = $_SESSION['registration'] ?? null;
if (!$data) {
    die("No registration data found. Please complete the registration form.");
}

// Validate email
if (empty($data['emailaddress']) || !filter_var($data['emailaddress'], FILTER_VALIDATE_EMAIL)) {
    die("Invalid or missing email address.");
}

$lrn = $data['lrn'] ?? '';
$lastname = $data['lastname'] ?? '';
$firstname = $data['firstname'] ?? '';
$middlename = $data['middlename'] ?? '';
$specaddress = $data['specaddress'] ?? '';
$brgy = $data['brgy'] ?? '';
$city = $data['city'] ?? '';
$mother = $data['mother'] ?? '';
$father = $data['father'] ?? '';
$gname = $data['gname'] ?? '';
$sex = $data['sex'] ?? '';
$dob = $data['dob'] ?? '';
$religion = $data['religion'] ?? '';
$emailaddress = $data['emailaddress'] ?? '';
$contactno = $data['contactno'] ?? '';
$schooltype = $data['schooltype'] ?? '';
$sname = $data['sname'] ?? '';
$course = $data['course'] ?? ''; // still used for SHS

// 🔹 Step 1: Check if LRN exists (Old student)
$check = $conn->prepare("SELECT year, enrollment_status FROM students_registration WHERE lrn = ? ORDER BY id DESC LIMIT 1");
$check->bind_param("s", $lrn);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // 🔹 Old Student
    $row = $result->fetch_assoc();
    $lastYear = $row['year'];
    $status   = $row['status'] ?? 'Passed'; // assume default Passed if missing

    // Auto-assign year
    if (strtolower($status) === 'passed') {
        $year = getNextGrade($lastYear);
    } else {
        $year = $lastYear; // repeat same grade
    }
    $student_type = 'Old';

} else {
    // 🔹 New Student
    $year = $data['year'] ?? ''; // only New students provide this in form
    $student_type = 'New';
}

// 🔹 Step 2: Save Registration
$stmt = $conn->prepare("INSERT INTO students_registration 
    (student_type, year, course, lrn, lastname, firstname, middlename, specaddress, brgy, city, mother, father, gname, sex, dob, religion, emailaddress, contactno, schooltype, sname) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("ssssssssssssssssssss",
    $student_type, $year, $course, $lrn, $lastname, $firstname, $middlename, $specaddress, $brgy, $city, 
    $mother, $father, $gname, $sex, $dob, $religion, $emailaddress, $contactno, $schooltype, $sname);

if ($stmt->execute()) {
    $stmt->close();
    unset($_SESSION['registration']);

    if ($student_type === 'Old') {
        // Old students → Payment page
        header("Location: choose_payment.php?lrn=" . urlencode($lrn));
        exit();
    } else {
        // New students → Send email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'deadpoolvictorio@gmail.com';
            $mail->Password   = 'ldcmeapjfuonxypu'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
            $mail->addAddress($emailaddress, htmlspecialchars("$firstname $lastname"));

            $mail->isHTML(true);
            $mail->Subject = 'Registration Acknowledgment - Escuela De Sto. Rosario';
            $mail->Body = "
                <p>Dear " . htmlspecialchars($firstname) . " " . htmlspecialchars($lastname) . ",</p>
                <p>Thank you for registering as a <strong>NEW student</strong> at Escuela De Sto. Rosario.</p>
                <p>Please submit the following requirements:</p>
                <ul>
                    <li>Good Moral Certificate</li>
                    <li>PSA Birth Certificate</li>
                </ul>
                <p>You may submit these onsite or send scanned copies via Viber at: <strong>09XX-XXX-XXXX</strong>.</p>
                <br>
                <p>Thank you,<br>Escuela De Sto. Rosario</p>
            ";

            $mail->send();

            echo "<script>
                alert('Registration received. Please submit your documents onsite or via Viber.');
                window.location.href = 'index.php';
            </script>";
            exit();
        } catch (Exception $e) {
            echo "Email could not be sent. PHPMailer Error: {$mail->ErrorInfo}";
        }
    }

} else {
    echo "Error: " . $conn->error;
}

$conn->close();

// 🔹 Helper function to determine next grade
function getNextGrade($current) {
    $levels = [
        "Kinder 1", "Kinder 2",
        "Grade 1", "Grade 2", "Grade 3", "Grade 4", "Grade 5", "Grade 6",
        "Grade 7", "Grade 8", "Grade 9", "Grade 10",
        "Grade 11", "Grade 12"
    ];
    $index = array_search($current, $levels);
    return ($index !== false && isset($levels[$index + 1])) ? $levels[$index + 1] : $current;
}
