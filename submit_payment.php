<?php
session_start();
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lrn = $_POST['lrn'] ?? null;
    $reference_number = $_POST['reference_number'] ?? '';
    $amount = $_POST['amount'] ?? null;
    $status = 'Pending';

    if (!$lrn || !$amount) {
        die("LRN and Amount are required.");
    }

    // 🔍 STEP 1: Get student_id and lastname using LRN
    $stmt = $conn->prepare("SELECT id, firstname, lastname FROM students_registration WHERE lrn = ?");
    $stmt->bind_param("s", $lrn);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $student_id = $row['id'];
        $firstname = $row['firstname'];
        $lastname = $row['lastname'];
    } else {
        die("Student with this LRN not found.");
    }
    $stmt->close();

    // 📸 STEP 2: Handle screenshot upload
    if (isset($_FILES["payment_screenshot"]) && $_FILES["payment_screenshot"]["error"] === UPLOAD_ERR_OK) {
        $targetDir = "payment_uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = basename($_FILES["payment_screenshot"]["name"]);
        $targetFilePath = $targetDir . time() . "_" . $fileName;

        if (move_uploaded_file($_FILES["payment_screenshot"]["tmp_name"], $targetFilePath)) {
            // 💾 STEP 3: Insert into student_payments table (now with lastname)
           // Add payment_date
           $payment_date = date("Y-m-d");

           $sql = "INSERT INTO student_payments 
                   (student_id, firstname, lastname, payment_type, reference_number, amount, screenshot_path, payment_status, payment_date) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
           
           $payment_type = "Online";
           
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
