<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';

$tempDir = __DIR__ . '/../temp';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

if (php_sapi_name() === 'cli') {
    global $argv;
    file_put_contents($tempDir . '/worker_debug.txt', print_r($argv, true), FILE_APPEND);

    $student_id   = (int) ($argv[1] ?? 0);
    $student_type = $argv[2] ?? '';
    $email        = $argv[3] ?? '';
    $firstname    = $argv[4] ?? '';
    $lastname     = $argv[5] ?? '';
} else {
    $student_id   = $_GET['id'] ?? 0;
    $student_type = $_GET['type'] ?? 'Test';
    $email        = $_GET['email'] ?? 'test@example.com';
    $firstname    = $_GET['firstname'] ?? 'Test';
    $lastname     = $_GET['lastname'] ?? 'Student';

    file_put_contents($tempDir . '/worker_debug.txt', "Browser mode triggered\n", FILE_APPEND);
}

if (!$email || !$student_id) {
    error_log('❌ Worker started but no email provided');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM students_registration WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    error_log('❌ No student data found for email worker.');
    exit;
}

$studentNumber = $data['student_number'] ?: 'Pending';
$course = $data['course'] ?: 'N/A';
$fatherOccupation = $data['father_occupation'] ?: 'N/A';
$motherOccupation = $data['mother_occupation'] ?: 'N/A';
$guardianName = $data['guardian_name'] ?: 'Not provided';
$guardianOccupation = $data['guardian_occupation'] ?: 'N/A';
$academicHonors = $data['academic_honors'] ?: 'N/A';
$lastSchool = $data['last_school_attended'] ?: 'N/A';
$telephone = $data['telephone'] ?: 'N/A';

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

$pdf_path = $tempDir . '/enrollment_' . time() . '.pdf';
file_put_contents($pdf_path, $pdf_output);

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
    $mail->addAddress($email, "$firstname $lastname");

    $mail->isHTML(true);
    $mail->Subject = 'Registration Acknowledgment - ESR';
    $student_type_label = ucfirst(strtolower($student_type)) === 'Old' ? 'Old' : 'New';
    $mail->Body = "
        <p>Dear <strong>$firstname $lastname</strong>,</p>
        <p>Thank you for registering as a <strong>$student_type_label student</strong> of Escuela De Sto. Rosario.</p>
        <p>Attached is a copy of your submitted enrollment form for your records.</p>
        <p>Please visit the school to submit the following documents for verification:</p>
        <ul>
            <li>Certificate of Good Moral Character</li>
            <li>Birth Certificate (PSA)</li>
            <li>Parents' Marriage Contract</li>
            <li>Student Baptismal Certificate <em>(if the last two are unavailable, kindly inform the registrar)</em></li>
        </ul>
        <p>If you have already submitted these documents, you may disregard this reminder.</p>
        <p>Thank you,<br>Escuela De Sto. Rosario Admissions Office</p>
    ";

    $mail->addAttachment($pdf_path, 'Enrollment_Form.pdf');
    $mail->send();

    unlink($pdf_path);

    if (php_sapi_name() !== 'cli') {
        echo "✅ Test email sent to $email";
    }

} catch (Exception $e) {
    error_log('Mailer Error: ' . $mail->ErrorInfo);
    if (php_sapi_name() !== 'cli') {
        echo '❌ Mailer error: ' . $mail->ErrorInfo;
    }
}
