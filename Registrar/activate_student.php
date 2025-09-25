<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('db_connection.php');

function normalizeEarlyGrade(string $grade): string
{
    $grade = trim($grade);
    if (in_array($grade, ['Kinder 1', 'Kinder 2'], true)) {
        return 'Kindergarten';
    }
    return $grade;
}

function gradeSynonyms(string $grade): array
{
    $normalized = normalizeEarlyGrade($grade);
    if ($normalized === 'Kindergarten') {
        return ['Kindergarten', 'Kinder 1', 'Kinder 2'];
    }
    return [$normalized];
}

function sumSectionCount(mysqli $conn, array $grades, string $section): int
{
    $total = 0;
    foreach ($grades as $variant) {
        $countQuery = $conn->prepare("SELECT COUNT(*) FROM students_registration WHERE year = ? AND section = ?");
        $countQuery->bind_param("ss", $variant, $section);
        $countQuery->execute();
        $countQuery->bind_result($partial);
        $countQuery->fetch();
        $countQuery->close();
        $total += (int) $partial;
    }
    return $total;
}

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


                 // Sectioning logic
                $year = $student['year']; // Grade level from students_registration
                $section = null;
                $adviser = null;

                // Pre-Prime & Kindergarten group (legacy Kinder 1/2 included): first 20 Hershey, 21+ Kisses
                $normalizedYear = normalizeEarlyGrade($year);
                $yearVariants = gradeSynonyms($year);

                if (in_array($normalizedYear, ['Pre-Prime 1','Pre-Prime 2','Kindergarten'], true)) {
                    $hersheyCount = sumSectionCount($conn, $yearVariants, 'Hershey');

                    if ($hersheyCount < 20) {
                        $section = "Hershey";
                        $adviser = "Ms. Cruz";
                    } else {
                        $section = "Kisses";
                        $adviser = "Mr. Reyes";
                    }
                }

                // Grade 1–6: first 30 per section
                elseif (in_array($normalizedYear, ['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'], true)) {
                    $secACount = sumSectionCount($conn, $yearVariants, 'Section A');

                    if ($secACount < 30) {
                        $section = "Section A";
                        $adviser = "Ms. Santos";
                    } else {
                        $section = "Section B";
                        $adviser = "Mr. Dela Cruz";
                    }
                }

                // Grade 7–10: first 40 per section
                elseif (in_array($normalizedYear, ['Grade 7','Grade 8','Grade 9','Grade 10'], true)) {
                    $secACount = sumSectionCount($conn, $yearVariants, 'Section A');

                    if ($secACount < 40) {
                        $section = "Section A";
                        $adviser = "Ms. Gonzales";
                    } else {
                        $section = "Section B";
                        $adviser = "Mr. Lopez";
                    }
                }

                // SHS (Grade 11–12): assign 1 section per strand
                elseif (in_array($normalizedYear, ['Grade 11','Grade 12'], true)) {
                    $strand = $student['course']; // e.g. ABM, GAS, HUMMS, ICT, TVL
                    $section = $strand . " - Section 1";
                    switch($strand) {
                        case "ABM": $adviser = "Sir Mendoza"; break;
                        case "GAS": $adviser = "Ma’am Ramirez"; break;
                        case "HUMMS": $adviser = "Sir Villanueva"; break;
                        case "ICT": $adviser = "Ma’am Bautista"; break;
                        case "TVL": $adviser = "Ma’am Ortega"; break;
                        default: $adviser = "To be assigned";
                    }
                }

                // ✅ Save assigned section
                if ($section) {
                    $updateSec = $conn->prepare("UPDATE students_registration SET section = ?, adviser = ? WHERE id = ?");
                    $updateSec->bind_param("ssi", $section, $adviser, $student_id);
                    $updateSec->execute();
                    $updateSec->close();
                }

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
