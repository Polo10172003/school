<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mailer.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include __DIR__ . '/../db_connection.php';

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

                 // 🔹 Mark student as activated in registration table
                 $upd = $conn->prepare("UPDATE students_registration SET portal_status = 'activated' WHERE id = ?");
                 $upd->bind_param("i", $student_id);
                 $upd->execute();
                 $upd->close();


                // Send email notification
                $mail = new PHPMailer(true);

                try {
                    $mailerConfig = mailer_apply_defaults($mail);
                    $mail->setFrom(
                        (string) ($mailerConfig['from_email'] ?? 'no-reply@rosariodigital.site'),
                        (string) ($mailerConfig['from_name'] ?? 'Escuela De Sto. Rosario')
                    );
                    $mail->addAddress($email, $name);

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

                    $logMailer = static function (string $line) use ($student_id): void {
                        @file_put_contents(
                            __DIR__ . '/../temp/email_worker_trace.log',
                            sprintf("[%s] [ActivateStudent:%d] %s\n", date('c'), $student_id, $line),
                            FILE_APPEND
                        );
                    };

                    mailer_send_with_fallback(
                        $mail,
                        [],
                        $logMailer,
                        (bool) ($mailerConfig['fallback_to_mail'] ?? false)
                    );
                    echo "<script>alert('Student account activated and email sent!'); window.location.href = 'registrar_dashboard.php';</script>";
                } catch (Exception $e) {
                    $message = addslashes($e->getMessage());
                    echo "<script>alert('Account activated, but email could not be sent. Mailer Error: {$message}'); window.location.href = 'registrar_dashboard.php';</script>";
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
