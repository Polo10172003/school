<?php
include __DIR__ . '/../db_connection.php';

// Decode JSON request (from fetch)
$input = json_decode(file_get_contents("php://input"), true);
$student_ids = $input['student_ids'] ?? ($_POST['student_ids'] ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($student_ids)) {
    $successCount = 0;
    $errors = [];

    foreach ($student_ids as $student_id) {
        $student_id = intval($student_id);

        // ✅ Ensure student is enrolled
        $stmt = $conn->prepare("SELECT * FROM students_registration WHERE id = ? AND enrollment_status = 'enrolled'");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $errors[] = "Invalid or not enrolled (ID: $student_id)";
            continue;
        }

        $student = $result->fetch_assoc();
        $stmt->close();

        $email = $student['emailaddress'];
        $year  = $student['year'];
        $strand = $student['course'];

        // ✅ Ensure student_accounts exists
        $check = $conn->prepare("SELECT id FROM student_accounts WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows === 0) {
            // Get student info from students_registration
            $info = $conn->prepare("SELECT student_number, firstname, lastname FROM students_registration WHERE id = ?");
            $info->bind_param("i", $student_id);
            $info->execute();
            $info->bind_result($student_number, $firstname, $lastname);
            $info->fetch();
            $info->close();
        
            // Insert into student_accounts with new fields
            $insert = $conn->prepare("
                INSERT INTO student_accounts (student_number, firstname, lastname, email, is_first_login) 
                VALUES (?, ?, ?, ?, 1)
            ");
            $insert->bind_param("ssss", $student_number, $firstname, $lastname, $email);
            $insert->execute();
            $insert->close();
        }
        $check->close();
        

        // ✅ Mark as activated
        $upd = $conn->prepare("UPDATE students_registration SET portal_status = 'activated' WHERE id = ?");
        $upd->bind_param("i", $student_id);
        $upd->execute();
        $upd->close();

        // ✅ Background email worker (non-blocking)
// 🔹 Email via background worker
        $php_path = getenv('PHP_CLI_PATH') ?: (PHP_BINARY ?: 'php');
        $worker   = __DIR__ . "/email_worker.php";
        exec("$php_path $worker $student_id > /dev/null 2>&1 &");


        $successCount++;
    }

    echo json_encode([
        "success" => true,
        "activated" => count($student_ids),
        "errors" => []
    ]);
    exit();
}

echo json_encode(["success" => false, "error" => "No selection"]);
exit();
?>
