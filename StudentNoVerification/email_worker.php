<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/../vendor/autoload.php';

if (!function_exists('email_worker_process')) {
    /**
     * Generate the enrollment PDF and send the acknowledgment email.
     *
     * @param int         $student_id        ID of the student in students_registration.
     * @param string      $student_type      Student type label (New/Old).
     * @param string      $email             Destination email address.
     * @param string      $firstname         Student first name.
     * @param string      $lastname          Student last name.
     * @param mysqli|null $existingConnection Optional existing mysqli connection to reuse.
     * @param bool        $appendDebugLog     When true, append debug info in temp/worker_debug.txt.
     *
     * @return bool True when the email was sent (or attempted without fatal errors).
     */
    function email_worker_process(
        int $student_id,
        string $student_type,
        string $email,
        string $firstname,
        string $lastname,
        ?mysqli $existingConnection = null,
        bool $appendDebugLog = false
    ): bool {
        $student_id = max($student_id, 0);
        $email = trim($email);
        $firstname = trim($firstname);
        $lastname = trim($lastname);
        $student_type = trim($student_type);

        if ($student_id <= 0 || $email === '') {
            error_log('Email worker: missing student id or email address.');
            return false;
        }

        $connection = $existingConnection;
        $createdConnection = false;

        if (!$connection instanceof mysqli) {
            include __DIR__ . '/../db_connection.php';
            if (!isset($conn) || !($conn instanceof mysqli)) {
                error_log('Email worker: unable to establish database connection.');
                return false;
            }
            $connection = $conn;
            $createdConnection = true;
        }

        $tempDir = __DIR__ . '/../temp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        if ($appendDebugLog) {
            $debugInfo = [
                'timestamp'     => date('c'),
                'student_id'    => $student_id,
                'student_type'  => $student_type,
                'email'         => $email,
                'environment'   => php_sapi_name(),
            ];
            @file_put_contents($tempDir . '/worker_debug.txt', print_r($debugInfo, true), FILE_APPEND);
        }

        $stmt = $connection->prepare('SELECT * FROM students_registration WHERE id = ? LIMIT 1');
        if (!$stmt) {
            error_log('Email worker: prepare failed - ' . $connection->error);
            if ($createdConnection) {
                $connection->close();
            }
            return false;
        }

        $stmt->bind_param('i', $student_id);
        if (!$stmt->execute()) {
            error_log('Email worker: execute failed - ' . $stmt->error);
            $stmt->close();
            if ($createdConnection) {
                $connection->close();
            }
            return false;
        }

        $result = $stmt->get_result();
        $data = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$data) {
            error_log('Email worker: no student data found.');
            if ($createdConnection) {
                $connection->close();
            }
            return false;
        }

        $studentNumber      = $data['student_number'] ?: 'Pending';
        $course             = $data['course'] ?: 'N/A';
        $fatherOccupation   = $data['father_occupation'] ?: 'N/A';
        $motherOccupation   = $data['mother_occupation'] ?: 'N/A';
        $guardianName       = $data['guardian_name'] ?: 'Not provided';
        $guardianOccupation = $data['guardian_occupation'] ?: 'N/A';
        $academicHonors     = $data['academic_honors'] ?: 'N/A';
        $lastSchool         = $data['last_school_attended'] ?: 'N/A';
        $telephone          = $data['telephone'] ?: 'N/A';

        $pdf_html = "
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    h2, h3 { text-align: center; margin: 5px 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    td, th { border: 1px solid #333; padding: 6px; vertical-align: top; }
    th { background: #f0f0f0; text-align: left; }
    .section-title { background: #ddd; font-weight: bold; text-align: center; }
</style>

<h2>Escuela De Sto. Rosario</h2>
<h3>Enrollment Form</h3>
<hr>

<table>
    <tr><th colspan='4' class='section-title'>Enrollment Details</th></tr>
    <tr>
        <td><strong>Student Number</strong></td><td>{$studentNumber}</td>
        <td><strong>School Year</strong></td><td>{$data['school_year']}</td>
    </tr>
    <tr>
        <td><strong>Grade Level</strong></td><td>{$data['year']}</td>
        <td><strong>Strand</strong></td><td>{$course}</td>
    </tr>

    <tr><th colspan='4' class='section-title'>Student Information</th></tr>
    <tr>
        <td><strong>Full Name</strong></td>
        <td>{$data['firstname']} {$data['middlename']} {$data['lastname']}</td>
        <td><strong>Gender</strong></td><td>{$data['gender']}</td>
    </tr>
    <tr>
        <td><strong>Birthday</strong></td><td>{$data['dob']}</td>
        <td><strong>Religion</strong></td><td>{$data['religion']}</td>
    </tr>
    <tr>
        <td><strong>Email</strong></td><td>{$data['emailaddress']}</td>
        <td><strong>Telephone</strong></td><td>{$telephone}</td>
    </tr>
    <tr>
        <td colspan='4'><strong>Address:</strong> {$data['address']}</td>
    </tr>

    <tr><th colspan='4' class='section-title'>School Background</th></tr>
    <tr>
        <td><strong>Last School Attended</strong></td><td>{$lastSchool}</td>
        <td><strong>Academic Honors / Awards</strong></td><td>{$academicHonors}</td>
    </tr>

    <tr><th colspan='4' class='section-title'>Parent & Guardian Information</th></tr>
    <tr>
        <td><strong>Father</strong></td><td>{$data['father_name']}</td>
        <td><strong>Occupation</strong></td><td>{$fatherOccupation}</td>
    </tr>
    <tr>
        <td><strong>Mother</strong></td><td>{$data['mother_name']}</td>
        <td><strong>Occupation</strong></td><td>{$motherOccupation}</td>
    </tr>
    <tr>
        <td><strong>Guardian</strong></td><td>{$guardianName}</td>
        <td><strong>Occupation</strong></td><td>{$guardianOccupation}</td>
    </tr>
</table>
";

        $dompdf = new Dompdf();
        $dompdf->loadHtml($pdf_html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf_output = $dompdf->output();

        $pdf_path = $tempDir . '/enrollment_' . $student_id . '_' . time() . '.pdf';
        if (file_put_contents($pdf_path, $pdf_output) === false) {
            error_log('Email worker: failed to write PDF file.');
            if ($createdConnection) {
                $connection->close();
            }
            return false;
        }

        $mail = new PHPMailer(true);
        $success = true;

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'deadpoolvictorio@gmail.com';
            $mail->Password   = 'ldcmeapjfuonxypu';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
            $mail->addAddress($email, trim($firstname . ' ' . $lastname));

            $mail->isHTML(true);
            $mail->Subject = 'Registration Acknowledgment - ESR';

            $student_type_label = ucfirst(strtolower($student_type)) === 'Old' ? 'Old' : 'New';
            $requirementsSection = '';
            if (strtolower($student_type_label) === 'new') {
                $requirementsSection = "
        <p>Please visit the school to submit the following documents for verification:</p>
        <ul>
            <li>Certificate of Good Moral Character</li>
            <li>Birth Certificate (PSA)</li>
            <li>Parents' Marriage Contract</li>
            <li>Student Baptismal Certificate <em>(if the last two are unavailable, kindly inform the registrar)</em></li>
        </ul>
        ";
            }

            $mail->Body = "
        <p>Dear <strong>$firstname $lastname</strong>,</p>
        <p>Thank you for registering as a <strong>$student_type_label student</strong> of Escuela De Sto. Rosario.</p>
        <p>Attached is a copy of your submitted enrollment form for your records.</p>
        {$requirementsSection}
        <p><strong>Important:</strong> Early registration records remain active for 14 days. If you are unable to visit the school to submit the requirements and complete the interview within that period, your application will be automatically removed from our system.</p>
        <p>If you have already submitted these documents, you may disregard this reminder.</p>
        <p>Thank you,<br>Escuela De Sto. Rosario Admissions Office</p>
    ";

            $mail->addAttachment($pdf_path, 'Enrollment_Form.pdf');
            $mail->send();
        } catch (Exception $e) {
            $success = false;
            error_log('Mailer Error: ' . $mail->ErrorInfo);
        }

        if (is_file($pdf_path)) {
            @unlink($pdf_path);
        }

        if ($createdConnection) {
            $connection->close();
        }

        return $success;
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    global $argv;
    $student_id   = (int) ($argv[1] ?? 0);
    $student_type = (string) ($argv[2] ?? '');
    $email        = (string) ($argv[3] ?? '');
    $firstname    = (string) ($argv[4] ?? '');
    $lastname     = (string) ($argv[5] ?? '');

    $result = email_worker_process($student_id, $student_type, $email, $firstname, $lastname, null, true);
    if ($result) {
        echo "✅ Email worker completed.\n";
    } else {
        fwrite(STDERR, "❌ Email worker encountered an error.\n");
        exit(1);
    }
}
