<?php
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include __DIR__ . '/../db_connection.php';

// Get student ID from CLI
if ($argc < 2) exit("No student ID provided\n");
$student_id = intval($argv[1]);

// Fetch student details
$stmt = $conn->prepare("SELECT firstname, lastname, emailaddress, student_number FROM students_registration WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $email, $student_number);
$stmt->fetch();
$stmt->close();

if ($email) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'no-reply@rosariodigital.site';
        $mail->Password = 'Dan@65933';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('no-reply@rosariodigital.site', 'Escuela De Sto. Rosario');
        $mail->addAddress($email, "$firstname $lastname");

        $mail->isHTML(true);
        $mail->Subject = "Your Student Portal Account Has Been Activated";
        $mail->Body = "
            <p>Dear $firstname $lastname,</p>
            <p>Your account for the <strong>Escuela De Sto. Rosario Student Portal</strong> has been successfully activated.</p>
            <p><strong>Login Details:</strong></p>
            <ul>
                <li><strong>Student Number:</strong> $student_number</li>
                <li><strong>Password:</strong> Set on your first login</li>
            </ul>
            <p>You may now log in through the Student Portal using the above details.</p>
            <br>
            <p>Thank you,<br>ESR Registrar’s Office</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        file_put_contents(__DIR__ . "/email_errors.log", "Mail Error for student $student_id: {$mail->ErrorInfo}\n", FILE_APPEND);
    }
}
?>
