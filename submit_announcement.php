<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include('db_connection.php');

$subject = $_POST['subject'] ?? '';
$message = $_POST['message'] ?? '';
$grades = $_POST['grades'] ?? [];

if (empty($grades)) {
    die("No grades selected.");
}

$mail = new PHPMailer(true);

try {
    // SMTP settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'deadpoolvictorio@gmail.com';
    $mail->Password   = 'ldcmeapjfuonxypu';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = "<p>$message</p>";

    $allEmails = [];

    if (in_array("everyone", $grades)) {
        $sql = "SELECT DISTINCT emailaddress, firstname, lastname FROM students_registration WHERE emailaddress IS NOT NULL AND emailaddress != ''";
    } else {
        // Convert array to SQL-safe string
        $grade_list = "'" . implode("','", $grades) . "'";
        $sql = "SELECT DISTINCT emailaddress, firstname, lastname FROM students_registration WHERE year IN ($grade_list) AND emailaddress IS NOT NULL AND emailaddress != ''";
    }

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $mail->addAddress($row['emailaddress'], $row['firstname'] . ' ' . $row['lastname']);
        }

        $mail->send();
        echo "<script>alert('Announcement sent successfully.'); window.location.href = 'admin_dashboard.php';</script>";
    } else {
        echo "<script>alert('No students found for the selected grade levels.'); window.location.href = 'admin_dashboard.php';</script>";
    }
} catch (Exception $e) {
    echo "Error sending announcement: {$mail->ErrorInfo}";
}

$conn->close();
?>
