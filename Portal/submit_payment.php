<?php
session_start();
include __DIR__ . '/../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference_number = $_POST['reference_number'] ?? '';
    $amount = $_POST['amount'] ?? null;
    $status = 'Pending';

    if (!$amount) {
        die("Amount is required.");
    }

    // ✅ STEP 1: Get student info from SESSION
    if (!isset($_SESSION['student_email'])) {
        die("Not logged in. Please log in again.");
    }
    $student_email = $_SESSION['student_email'];

    $stmt = $conn->prepare("SELECT id, firstname, lastname FROM students_registration WHERE emailaddress = ?");
    $stmt->bind_param("s", $student_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $student_id = $row['id'];
        $firstname  = $row['firstname'];
        $lastname   = $row['lastname'];
    } else {
        die("Student not found.");
    }
    $stmt->close();

    // ✅ STEP 2: Handle screenshot upload
    if (isset($_FILES["payment_screenshot"]) && $_FILES["payment_screenshot"]["error"] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . "/../payment_uploads/";   // go up one level from Portal/
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = basename($_FILES["payment_screenshot"]["name"]);
        $targetFilePath = $targetDir . time() . "_" . $fileName;

        if (move_uploaded_file($_FILES["payment_screenshot"]["tmp_name"], $targetFilePath)) {
            $payment_date = date("Y-m-d");
            $payment_type = "Online";

            // ✅ Insert into DB
            $sql = "INSERT INTO student_payments 
                    (student_id, firstname, lastname, payment_type, reference_number, amount, screenshot_path, payment_status, payment_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "isssdssss",
                $student_id,
                $firstname,
                $lastname,
                $payment_type,
                $reference_number,
                $amount,
                $targetFilePath,
                $status,
                $payment_date
            );

            if ($stmt->execute()) {
                header("Location: payment_success.php");
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
