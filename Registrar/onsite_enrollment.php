<?php
session_start();
include __DIR__ . '/../db_connection.php';

$student_type  = $_GET['student_type']  ?? '';
$student_number= $_GET['student_number']           ?? '';
$payment_type  = $_GET['payment_type']  ?? '';

// ---------------- NEW STUDENT ----------------
if ($student_type === 'new') {
    // Clear any old registration session data for new student
    unset($_SESSION['registration']);

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
        echo "Student Number is required for old students.";
        exit();
    }

    // Fetch old student data
    $stmt = $conn->prepare("SELECT * FROM students_registration WHERE student_number = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $student_number);
    $stmt->execute();
    $result  = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();

    if (!$student) {
        echo "Student with Student Number {$student_number} not found.";
        exit();
    }

    // Determine next grade
    $currentYear     = $student['year'];
    $academic_status = $student['academic_status'] ?? 'Passed';

    function nextGrade($grade) {
        $map = [
            "Kinder 1"=>"Kinder 2","Kinder 2"=>"Grade 1","Grade 1"=>"Grade 2","Grade 2"=>"Grade 3",
            "Grade 3"=>"Grade 4","Grade 4"=>"Grade 5","Grade 5"=>"Grade 6","Grade 6"=>"Grade 7",
            "Grade 7"=>"Grade 8","Grade 8"=>"Grade 9","Grade 9"=>"Grade 10","Grade 10"=>"Grade 11",
            "Grade 11"=>"Grade 12","Grade 12"=>"Grade 12"
        ];
        return $map[$grade] ?? $grade;
    }

    $suggestedGrade = (strtolower($academic_status) === 'passed') ? nextGrade($currentYear) : $currentYear;

    // Store in session for prefill in registration form
    $_SESSION['registration'] = $student;
    $_SESSION['registration']['yearlevel'] = $suggestedGrade;

    // ----------------- ONSITE PAYMENT HANDLING (OLD) -----------------
if ($payment_type === 'onsite') {
    $q = $conn->prepare("SELECT id, firstname, lastname FROM students_registration WHERE student_number = ? ORDER BY id DESC LIMIT 1");
    $q->bind_param("s", $student_number);
    $q->execute();
    $q->bind_result($student_id_db, $firstname_db, $lastname_db);
    $q->fetch();
    $q->close();

    if (!$student_id_db) {
        die("Could not resolve student by Student Number for onsite payment.");
    }

    $student_id = (string)$student_id_db;       // student_payments.student_id is VARCHAR(20)
    $firstname  = $firstname_db ?? '';
    $lastname   = $lastname_db  ?? '';

    // TODO: compute correct fee for the grade level
    $total_amount_due = 9110.00;

    // 1) Insert pending CASH payment
    // 1) Insert pending CASH payment
$ins = $conn->prepare("
INSERT INTO student_payments
    (student_id, firstname, lastname, payment_type, amount, payment_status, created_at)
VALUES (?, ?, ?, 'Cash', ?, 'pending', NOW())
");
$ins->bind_param("sssd", $student_id, $firstname, $lastname, $total_amount_due);
if (!$ins->execute()) {
die('Insert error (student_payments): ' . $ins->error);
}
$payment_row_id = $conn->insert_id; // <-- new row's primary key
$ins->close();

/* 🔧 Force-fill names in case they came out NULL for any reason.
No JOIN, we just write the values we already fetched from students_registration. */
$fix = $conn->prepare("
UPDATE student_payments
   SET firstname = ?, lastname = ?
 WHERE id = ?
");
$fix->bind_param("ssi", $firstname, $lastname, $payment_row_id);
$fix->execute();
$fix->close();


    // 3) Keep enrollment pending until cashier confirms
    $upd = $conn->prepare("UPDATE students_registration SET enrollment_status = 'pending' WHERE id = ?");
    $upd->bind_param("i", $student_id);
    $upd->execute();
    $upd->close();

    header('Location: registrar_dashboard.php?msg=student_added_pending_cash');
    exit();
}


    // ----------------- ONLINE PAYMENT HANDLING (OLD) -----------------
    elseif ($payment_type === 'online') {
        header("Location: choose_payment.php?student_number=" . urlencode($student_number));
        exit();
    } 
    // ----------------- DEFAULT / NO PAYMENT TYPE -----------------
    else {
        header('Location: early_registration.php');
        exit();
    }
}
?>
