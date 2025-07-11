<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Composer autoload for PHPMailer
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

$student_type = $data['student_type'] ?? '';
$year = $data['year'] ?? '';
$course = $data['course'] ?? '';
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

// Prepare and bind
$stmt = $conn->prepare("INSERT INTO students_registration 
    (student_type, year, course, lrn, lastname, firstname, middlename, specaddress, brgy, city, mother, father, gname, sex, dob, religion, emailaddress, contactno, schooltype, sname) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("ssssssssssssssssssss",
    $student_type, $year, $course, $lrn, $lastname, $firstname, $middlename, $specaddress, $brgy, $city, 
    $mother, $father, $gname, $sex, $dob, $religion, $emailaddress, $contactno, $schooltype, $sname);

if ($stmt->execute()) {
    $stmt->close();
    unset($_SESSION['registration']);

    if (strtolower($student_type) === 'old') {
        header("Location: choose_payment.php?lrn=" . urlencode($lrn));
        exit();
    } else {
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
