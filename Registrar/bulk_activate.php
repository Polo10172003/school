<?php
include __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../includes/transaction_logger.php';

// Decode JSON request (from fetch)
$input = json_decode(file_get_contents("php://input"), true);
$student_ids = $input['student_ids'] ?? ($_POST['student_ids'] ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($student_ids)) {
    $successCount = 0;
    $errors = [];
    $activatedStudents = [];
    $failedStudents = [];

    foreach ($student_ids as $student_id) {
        $student_id = intval($student_id);

        // ✅ Ensure student is enrolled
        $stmt = $conn->prepare("SELECT * FROM students_registration WHERE id = ? AND enrollment_status = 'enrolled'");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $errors[] = "Invalid or not enrolled (ID: $student_id)";
            $failedStudents[] = [
                'id'     => $student_id,
                'reason' => 'not_enrolled',
            ];
            continue;
        }

        $student = $result->fetch_assoc();
        $stmt->close();

        $email = $student['emailaddress'];
        $year  = $student['year'];
        $strand = $student['course'];
        $studentNumber = $student['student_number'] ?? null;

        // ✅ Ensure student_accounts exists
        $check = $conn->prepare("SELECT id FROM student_accounts WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $checkResult = $check->get_result();
        $accountExisted = $checkResult->num_rows > 0;

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
        $workerPath = __DIR__ . '/email_worker.php';
        $disabledRaw = (string) ini_get('disable_functions');
        $disabledList = array_filter(array_map('trim', explode(',', $disabledRaw)));
        $canUseExec = function_exists('exec') && !in_array('exec', $disabledList, true) && is_file($workerPath);

        $emailDispatched = false;

        if ($canUseExec) {
            $phpPath = getenv('PHP_CLI_PATH') ?: (PHP_BINARY ?: '/usr/bin/php');
            $cmdParts = [
                escapeshellcmd($phpPath),
                escapeshellarg($workerPath),
                escapeshellarg((string) $student_id),
            ];
            $cmd = implode(' ', $cmdParts);
            $execOutput = [];
            $execStatus = 0;
            exec($cmd . ' > /dev/null 2>&1', $execOutput, $execStatus);
            if ($execStatus === 0) {
                $emailDispatched = true;
            }
        }

        if (!$emailDispatched) {
            if (!function_exists('registrar_email_worker_process')) {
                require_once __DIR__ . '/email_worker.php';
            }
            try {
                $emailDispatched = registrar_email_worker_process((int) $student_id, $conn);
            } catch (Throwable $workerError) {
                error_log('[registrar] email worker exception for student ' . $student_id . ': ' . $workerError->getMessage());
            }

            if (!$emailDispatched) {
                error_log('[registrar] email worker inline fallback failed for student ' . $student_id);
            }
        }

        $activatedStudents[] = [
            'id'              => $student_id,
            'email'           => $email,
            'student_number'  => $studentNumber,
            'year_level'      => $year,
            'strand'          => $strand,
            'account_existed' => $accountExisted,
            'email_dispatched'=> $emailDispatched,
        ];


        $successCount++;
    }

    transaction_log_record($conn, [
        'category'    => 'portal',
        'action'      => 'bulk_portal_activation',
        'target_type' => 'student_batch',
        'description' => sprintf('Activated portal access for %d students via bulk action.', $successCount),
        'metadata'    => [
            'requested_ids' => array_map('intval', $student_ids),
            'activated'     => $activatedStudents,
            'errors'        => $errors,
            'failed'        => $failedStudents,
        ],
        'context'     => 'registrar',
    ]);

    echo json_encode([
        "success" => true,
        "activated" => count($student_ids),
        "errors" => $errors
    ]);
    exit();
}

echo json_encode(["success" => false, "error" => "No selection"]);
exit();
?>
