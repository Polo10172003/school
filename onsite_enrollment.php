<?php
session_start();
include('db_connection.php');

$student_type = $_GET['student_type'] ?? '';
$lrn = $_GET['lrn'] ?? '';
$payment_type = $_GET['payment_type'] ?? '';

if ($student_type === 'new') {
    // Clear any old registration session data for new student
    unset($_SESSION['registration']);
    
    // Redirect to registration form
    header('Location: early_registration.php');
    exit();
}

if ($student_type === 'old') {
    if (!$lrn) {
        echo "LRN is required for old students.";
        exit();
    }

    // Fetch old student data
    $stmt = $conn->prepare("SELECT * FROM students_registration WHERE lrn = ?");
    $stmt->bind_param("s", $lrn);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if (!$student) {
        echo "Student with LRN {$lrn} not found.";
        exit();
    }

    // Determine next grade
    $currentYear = $student['year'];
    $academic_status = $student['academic_status'];

    function nextGrade($grade) {
        $map = [
            "Kinder 1"=>"Kinder 2","Kinder 2"=>"Grade 1","Grade 1"=>"Grade 2","Grade 2"=>"Grade 3",
            "Grade 3"=>"Grade 4","Grade 4"=>"Grade 5","Grade 5"=>"Grade 6","Grade 6"=>"Grade 7",
            "Grade 7"=>"Grade 8","Grade 8"=>"Grade 9","Grade 9"=>"Grade 10","Grade 10"=>"Grade 11",
            "Grade 11"=>"Grade 12","Grade 12"=>"Grade 12"
        ];
        return $map[$grade] ?? $grade;
    }

    $suggestedGrade = ($academic_status === 'Passed') ? nextGrade($currentYear) : $currentYear;

    // Store in session for prefill in registration form
    $_SESSION['registration'] = $student;
    $_SESSION['registration']['yearlevel'] = $suggestedGrade;

    // ----------------- ONSITE PAYMENT HANDLING -----------------
    if ($payment_type === 'onsite') {

        // Calculate total amount due (replace with actual calculation from tuition/fees table)
        $total_amount_due = 5000; // Example placeholder, change as needed

        $student_id = $student['id'];
        $payment_date = date("Y-m-d");

        // Insert cash payment as paid
        $stmt_cash = $conn->prepare("
            INSERT INTO student_payments (student_id, payment_type, amount, payment_status, payment_date) 
            VALUES (?, 'cash', ?, 'paid', ?)
        ");
        $stmt_cash->bind_param("ids", $student_id, $total_amount_due, $payment_date);
        $stmt_cash->execute();

        // Update enrollment status to enrolled
        $update = $conn->prepare("UPDATE students_registration SET enrollment_status='enrolled' WHERE id=?");
        $update->bind_param("i", $student_id);
        $update->execute();

        // Store student ID in session for cashier dashboard if needed
        $_SESSION['cashier_student_id'] = $student_id;

        // Redirect to cashier dashboard
        header('Location: cashier_dashboard.php');
        exit();
    }
    // ----------------- ONLINE PAYMENT HANDLING -----------------
    elseif ($payment_type === 'online') {
        header("Location: choose_payment.php?lrn={$lrn}");
        exit();
    } 
    // ----------------- DEFAULT / NO PAYMENT TYPE -----------------
    else {
        header('Location: early_registration.php');
        exit();
    }
}
?>
