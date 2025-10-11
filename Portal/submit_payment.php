<?php
require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference_number = $_POST['reference_number'] ?? '';
    $amount = $_POST['amount'] ?? null;
    $status = 'Pending';

    if (!$amount) {
        die("Amount is required.");
    }

    $student_id = $_POST['student_id'] ?? null;
    $student_number = $_POST['student_number'] ?? null;

    if (!$student_number && isset($_SESSION['student_number'])) {
        $student_number = $_SESSION['student_number'];
    }

    $grade_level = null;
    if ($student_id) {
        $stmt = $conn->prepare("SELECT id, firstname, lastname, student_number, year FROM students_registration WHERE id = ?");
        $stmt->bind_param("i", $student_id);
    } elseif ($student_number) {
        $stmt = $conn->prepare("SELECT id, firstname, lastname, student_number, year FROM students_registration WHERE student_number = ?");
        $stmt->bind_param("s", $student_number);
    } else {
        die("Not logged in. Please log in again.");
    }

    $stmt->execute();
    $result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $student_id = $row['id'];
    $firstname  = $row['firstname'];
    $lastname   = $row['lastname'];
    $student_number = $row['student_number'];
    $grade_level   = $row['year'] ?? null;
    } else {
        die("Student not found.");
    }
    $stmt->close();

    // ✅ STEP 2: Handle screenshot upload
if (isset($_FILES["payment_screenshot"]) && $_FILES["payment_screenshot"]["error"] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . "/../payment_uploads/";   // filesystem path
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$fileName = time() . "_" . basename($_FILES["payment_screenshot"]["name"]);
$targetFilePath = $targetDir . $fileName;


if (move_uploaded_file($_FILES["payment_screenshot"]["tmp_name"], $targetFilePath)) {
    $webPath = APP_BASE_PATH . "payment_uploads/" . $fileName;  // ✅ web accessible path
    $payment_date = date("Y-m-d");
    $selectedMethod = $_POST['payment_type'] ?? 'online';
    $methodLabels = [
        'gcash' => 'Online - GCash',
        'bank'  => 'Online - Bank Transfer',
    ];
    $payment_type = $methodLabels[$selectedMethod] ?? ucfirst($selectedMethod);

    $sql = "INSERT INTO student_payments 
            (student_id, grade_level, school_year, firstname, lastname, payment_type, reference_number, amount, screenshot_path, payment_status, payment_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $school_year = null; // Online submissions do not capture school year explicitly.
    $stmt->bind_param(
        "issssssdsss",
        $student_id,
        $grade_level,
        $school_year,
        $firstname,
        $lastname,
        $payment_type,
        $reference_number,
        $amount,
        $webPath,   // ✅ save relative path instead of server path
        $status,
        $payment_date
    );

            if ($stmt->execute()) {
                $_SESSION['payment_success'] = true;
                $_SESSION['payment_success_message'] = "Thank you for your payment. Your transaction is now being reviewed by our finance department.\nPlease keep your reference number and wait for confirmation via Email.";
                header("Location: choose_payment.php");
                exit();
            } else {
                echo "Database error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Error uploading screenshot.";
        }
    } else {
        echo "No screenshot uploaded or upload error.";
    }
}
?>
