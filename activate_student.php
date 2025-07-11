<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('db_connection.php');

if (isset($_GET['id'])) {
    $student_id = $_GET['id'];

    // Get student info
    $stmt = $conn->prepare("SELECT * FROM students_registration WHERE id = ? AND enrollment_status = 'enrolled'");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $email = $student['emailaddress'];
        $name = $student['firstname']; 

        // Check if already has account
        $check = $conn->prepare("SELECT * FROM student_accounts WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows == 0) {
            // Insert new account with is_first_login = 1
            $insert = $conn->prepare("INSERT INTO student_accounts (email, is_first_login) VALUES (?, 1)");
            $insert->bind_param("s", $email);
            if ($insert->execute()) {
                // Send email notification
                $mail = new PHPMailer(true);

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'deadpoolvictorio@gmail.com'; 
                    $mail->Password = 'ldcmeapjfuonxypu';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    // Recipients
                    $mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
                    $mail->addAddress($email, $name);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Your ESR Student Portal Account is Activated';
                    $mail->Body    = "
                        <h3>Hi $name,</h3>
                        <p>Your account for the <strong>Escuela De Sto. Rosario Student Portal</strong> has been successfully activated.</p>
                        <p>You may now log in using your email: <strong>$email</strong></p>
                        <p>On your first login, you will be prompted to set your password.</p>
                        <br>
                        <p>Thank you,<br>ESR Registrar Office</p>
                    ";

                    $mail->send();
                    echo "<script>alert('Student account activated and email sent!'); window.location.href = 'registrar_dashboard.php';</script>";
                } catch (Exception $e) {
                    echo "<script>alert('Account activated, but email could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.location.href = 'registrar_dashboard.php';</script>";
                }
            } else {
                echo "<script>alert('Account activation failed.'); window.location.href = 'registrar_dashboard.php';</script>";
            }
        } else {
            echo "<script>alert('Account already exists.'); window.location.href = 'registrar_dashboard.php';</script>";
        }
    } else {
        echo "<script>alert('Invalid student or not enrolled.'); window.location.href = 'registrar_dashboard.php';</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: registrar_dashboard.php");
    exit();
}
?>
