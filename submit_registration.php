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

$lrn         = $data['lrn'] ?? '';
$lastname    = $data['lastname'] ?? '';
$firstname   = $data['firstname'] ?? '';
$middlename  = $data['middlename'] ?? '';
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
    // 🔹 Old Student
    $row     = $result->fetch_assoc();
    $lastYear= $row['year'];
    $status  = $row['academic_status'] ?? 'Passed';

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
$check->close();

// 🔹 Step 2: Save Registration
$stmt = $conn->prepare("INSERT INTO students_registration 
    (student_type, year, course, lrn, lastname, firstname, middlename, specaddress, brgy, city, mother, father, gname, sex, dob, religion, emailaddress, contactno, schooltype, sname) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("ssssssssssssssssssss",
    $student_type, $year, $course, $lrn, $lastname, $firstname, $middlename, $specaddress, $brgy, $city, 
    $mother, $father, $gname, $sex, $dob, $religion, $emailaddress, $contactno, $schooltype, $sname);

if ($stmt->execute()) {
    // ✅ get the new student id BEFORE closing
    $student_id = $conn->insert_id;
    $stmt->close();

    // Clear the big registration payload from session
    unset($_SESSION['registration']);

    // ✅ Payment choice preserved from Registrar (via onsite_enrollment.php)
    $reg_payment_type = $_SESSION['onsite_payment_type'] ?? ($_GET['payment_type'] ?? '');
    unset($_SESSION['onsite_payment_type']); // one-time use

    // If New + Onsite cash → create pending payment for cashier
    if ($student_type === 'New' && $reg_payment_type === 'onsite') {
        // TODO: Dynamically compute fee from tuition_fees by $year
        $amount_due = 5000.00; // placeholder; replace with real fee lookup

        // Example dynamic fee (uncomment and adjust if you have tuition_fees table):
        // $amount_due = 0.00;
        // $feeStmt = $conn->prepare("SELECT amount FROM tuition_fees WHERE year = ? LIMIT 1");
        // $feeStmt->bind_param("s", $year);
        // $feeStmt->execute();
        // $feeStmt->bind_result($amount_found);
        // if ($feeStmt->fetch()) $amount_due = (float)$amount_found;
        // $feeStmt->close();
        // if ($amount_due <= 0) { $amount_due = 5000.00; }

        $ins = $conn->prepare("
            INSERT INTO student_payments
                (student_id, payment_type, amount, payment_status, created_at)
            VALUES (?, 'Cash', ?, 'pending', NOW())
        ");
        $ins->bind_param("id", $student_id, $amount_due);
        $ins->execute();
        $ins->close();

        // Keep enrollment pending until cashier marks payment as PAID
        $upd = $conn->prepare("UPDATE students_registration SET enrollment_status = 'pending' WHERE id = ?");
        $upd->bind_param("i", $student_id);
        $upd->execute();
        $upd->close();

        // Send the registrar back with a friendly message for cashier handoff
        echo "<script>
            alert('Registration saved. A pending CASH payment was created. Please proceed to the Cashier for payment.');
            window.location.href = 'registrar_dashboard.php?msg=pending_cash_created';
        </script>";
        exit();
    }

    // Else: existing behavior
    if ($student_type === 'Old') {
        // Old students → Payment page (online flow)
        header("Location: choose_payment.php?lrn=" . urlencode($lrn));
        exit();
    } else {
        // New students → Send acknowledgment email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'deadpoolvictorio@gmail.com'; // TODO: move to .env
            $mail->Password   = 'ldcmeapjfuonxypu';          // TODO: move to .env
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
