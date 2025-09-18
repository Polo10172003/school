<?php
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include __DIR__ . '/../db_connection.php';

// Get student ID from CLI
if ($argc < 2) exit("No student ID provided\n");
$student_id = intval($argv[1]);

// Fetch student details
$stmt = $conn->prepare("SELECT firstname, lastname, emailaddress FROM students_registration WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $email);
$stmt->fetch();
$stmt->close();

if ($email) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; 
        $mail->Password = 'your-app-password'; 
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('your-email@gmail.com', 'Escuela De Sto. Rosario');
        $mail->addAddress($email, "$firstname $lastname");

        $mail->isHTML(true);
        $mail->Subject = "Your Student Portal Account Has Been Activated";
        $mail->Body    = "
            <p>Dear $firstname $lastname,</p>
            <p>Your account for the <strong>Escuela De Sto. Rosario Student Portal</strong> has been successfully activated.</p>
                        <p>You may now log in using your email: <strong>$email</strong></p>
                        <p>On your first login, you will be prompted to set your password.</p>
                        <br>
                        <p>Thank you,<br>ESR Registrar Office</p>
            <br>
            <p>Thank you,<br>Escuela De Sto. Rosario</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        file_put_contents(__DIR__ . "/email_errors.log", "Mail Error for student $student_id: {$mail->ErrorInfo}\n", FILE_APPEND);
    }
}
?>
