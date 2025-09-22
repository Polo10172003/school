<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';

// Ensure temp folder exists
$tempDir = __DIR__ . '/../temp';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// Detect if running via CLI or Browser
if (php_sapi_name() === 'cli') {
    global $argv;
    file_put_contents($tempDir . '/worker_debug.txt', print_r($argv, true), FILE_APPEND);

    $student_id   = $argv[1] ?? 0;
    $student_type = $argv[2] ?? '';
    $email        = $argv[3] ?? '';
    $firstname    = $argv[4] ?? '';
    $lastname     = $argv[5] ?? '';
} else {
    // Debug mode when opened via browser
    $student_id   = $_GET['id'] ?? 0;
    $student_type = $_GET['type'] ?? 'Test';
    $email        = $_GET['email'] ?? 'test@example.com';
    $firstname    = $_GET['firstname'] ?? 'Test';
    $lastname     = $_GET['lastname'] ?? 'Student';

    file_put_contents($tempDir . '/worker_debug.txt', "Browser mode triggered\n", FILE_APPEND);
}

if (!$email) {
    error_log("❌ Worker started but no email provided");
    exit;
}

// 🔹 Fetch student details
$stmt = $conn->prepare("SELECT * FROM students_registration WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ✅ Build enrollment form in HTML
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
    <tr><th colspan='4' class='section-title'>Admission Information</th></tr>
    <tr>
        <td><strong>Student Number</strong></td><td>" . ($data['student_number'] ?? 'Pending') . "</td>
        <td><strong>LRN</strong></td><td>{$data['lrn']}</td>
    </tr>
    <tr>
        <td><strong>Year Level</strong></td><td>{$data['year']}</td>
        <td><strong>Strand</strong></td><td>{$data['course']}</td>
    </tr>

    <tr><th colspan='4' class='section-title'>Student's Information</th></tr>
    <tr>
        <td><strong>Full Name</strong></td>
        <td>{$data['firstname']} {$data['middlename']} {$data['lastname']} {$data['suffixname']}</td>
        <td><strong>Gender</strong></td><td>{$data['gender']}</td>
    </tr>
    <tr>
        <td><strong>Civil Status</strong></td><td>{$data['status']}</td>
        <td><strong>Citizenship</strong></td><td>{$data['citizenship']}</td>
    </tr>
    <tr>
        <td><strong>Date of Birth</strong></td><td>{$data['dob']}</td>
        <td><strong>Birthplace</strong></td><td>{$data['birthplace']}</td>
    </tr>
    <tr>
        <td><strong>Religion</strong></td><td>{$data['religion']}</td>
        <td><strong>Email</strong></td><td>{$data['emailaddress']}</td>
    </tr>
    <tr>
        <td><strong>Mobile Number</strong></td><td>{$data['mobnumber']}</td>
        <td><strong>Telephone</strong></td><td>{$data['telnumber']}</td>
    </tr>

    <tr><th colspan='4' class='section-title'>Current Address</th></tr>
    <tr>
        <td colspan='4'>
            {$data['streetno']} {$data['street']} {$data['subd']}, 
            {$data['brgy']}, {$data['city']}, {$data['province']} {$data['zipcode']}
        </td>
    </tr>

    <tr><th colspan='4' class='section-title'>Permanent Address</th></tr>
    <tr>
        <td colspan='4'>
            {$data['p_streetno']} {$data['p_street']} {$data['p_subd']}, 
            {$data['p_brgy']}, {$data['p_city']}, {$data['p_province']} {$data['p_zipcode']}
        </td>
    </tr>

    <tr><th colspan='4' class='section-title'>Father's Information</th></tr>
    <tr>
        <td><strong>Name</strong></td>
        <td>{$data['father_firstname']} {$data['father_middlename']} {$data['father_lastname']} {$data['father_suffixname']}</td>
        <td><strong>Occupation</strong></td><td>{$data['father_occupation']}</td>
    </tr>
    <tr>
        <td><strong>Mobile</strong></td><td>{$data['father_mobnumber']}</td>
        <td><strong>Email</strong></td><td>{$data['father_emailaddress']}</td>
    </tr>

    <tr><th colspan='4' class='section-title'>Mother's Information</th></tr>
    <tr>
        <td><strong>Name</strong></td>
        <td>{$data['mother_firstname']} {$data['mother_middlename']} {$data['mother_lastname']} {$data['mother_suffixname']}</td>
        <td><strong>Occupation</strong></td><td>{$data['mother_occupation']}</td>
    </tr>
    <tr>
        <td><strong>Mobile</strong></td><td>{$data['mother_mobnumber']}</td>
        <td><strong>Email</strong></td><td>{$data['mother_emailaddress']}</td>
    </tr>

    <tr><th colspan='4' class='section-title'>Guardian's Information</th></tr>
    <tr>
        <td><strong>Name</strong></td>
        <td>{$data['guardian_firstname']} {$data['guardian_middlename']} {$data['guardian_lastname']} {$data['guardian_suffixname']}</td>
        <td><strong>Relationship</strong></td><td>{$data['guardian_relationship']}</td>
    </tr>
    <tr>
        <td><strong>Mobile</strong></td><td>{$data['guardian_mobnumber']}</td>
        <td><strong>Email</strong></td><td>{$data['guardian_emailaddress']}</td>
    </tr>
    <tr>
        <td colspan='4'><strong>Occupation:</strong> {$data['guardian_occupation']}</td>
    </tr>
</table>
";


// ✅ Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($pdf_html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdf_output = $dompdf->output();

$pdf_path = $tempDir . "/enrollment_" . time() . ".pdf";
file_put_contents($pdf_path, $pdf_output);

// ✅ Send email
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'deadpoolvictorio@gmail.com';
    $mail->Password   = 'ldcmeapjfuonxypu'; // Gmail app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
    $mail->addAddress($email, "$firstname $lastname");

    $mail->isHTML(true);
    $mail->Subject = "Registration Acknowledgment - ESR";
    $mail->Body = "
        <p>Dear $lastname $firstname </p>
        <p>Thank you for registering as a <strong>$student_type student</strong>.</p>
        <p>We attached a PDF copy of your enrollment form for your reference.</p>
        <p>Thank you,<br>Escuela De Sto. Rosario</p>
    ";

    $mail->addAttachment($pdf_path, "Enrollment_Form.pdf");
    $mail->send();

    unlink($pdf_path);

    if (php_sapi_name() !== 'cli') {
        echo "✅ Test email sent to $email";
    }

} catch (Exception $e) {
    error_log("Mailer Error: {$mail->ErrorInfo}");
    if (php_sapi_name() !== 'cli') {
        echo "❌ Mailer error: " . $mail->ErrorInfo;
    }
}
