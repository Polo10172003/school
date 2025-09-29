<?php
require '../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';

use PHPMailer\PHPMailer\PHPMailer;

$student_id     = $argv[1] ?? 0;
$payment_type   = $argv[2] ?? '';
$amount         = $argv[3] ?? 0;
$status         = $argv[4] ?? '';


if (!$student_id) exit;

// Fetch student info
$stmt = $conn->prepare("SELECT emailaddress, firstname, lastname, student_number, year, section, schedule_sent_at FROM students_registration WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($email, $firstname, $lastname, $student_number, $grade_level, $student_section, $schedule_sent_at);
$stmt->fetch();
$stmt->close();

if (!$email) exit;
// 🔹 Fetch latest OR number from payments
$or_number = null;
$payStmt = $conn->prepare("SELECT or_number FROM student_payments WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$payStmt->bind_param("i", $student_id);
$payStmt->execute();
$payStmt->bind_result($or_number);
$payStmt->fetch();
$payStmt->close();

$scheduleHtml = '';
$shouldMarkScheduleSent = false;

if (empty($schedule_sent_at)) {
    $sectionForLookup = $student_section !== null && $student_section !== '' ? $student_section : 'ALL';

    $schoolYear = null;
    $yearStmt = $conn->prepare("
        SELECT school_year
        FROM class_schedules
        WHERE grade_level = ? AND (section = ? OR section = 'ALL')
        ORDER BY updated_at DESC
        LIMIT 1
    ");
    if ($yearStmt) {
        $yearStmt->bind_param('ss', $grade_level, $sectionForLookup);
        $yearStmt->execute();
        $yearStmt->bind_result($schoolYear);
        $yearStmt->fetch();
        $yearStmt->close();
    }

    if ($schoolYear) {
        $scheduleStmt = $conn->prepare("
            SELECT section, subject, teacher, day_of_week, start_time, end_time, room
            FROM class_schedules
            WHERE grade_level = ? AND school_year = ? AND (section = ? OR section = 'ALL')
            ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time IS NULL, start_time
        ");
        if ($scheduleStmt) {
            $scheduleStmt->bind_param('sss', $grade_level, $schoolYear, $sectionForLookup);
            $scheduleStmt->execute();
            $result = $scheduleStmt->get_result();
            $entries = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            $scheduleStmt->close();

            if (!empty($entries)) {
                $scheduleHtml .= "<h3 style='color:#2c3e50;margin-top:24px;'>Class Schedule</h3>";
                $scheduleHtml .= "<p style='margin:6px 0 14px;'>Grade: <strong>" . htmlspecialchars($grade_level, ENT_QUOTES) . "</strong> &bull; Section: <strong>" . htmlspecialchars($student_section ?: 'To be assigned', ENT_QUOTES) . "</strong><br>School Year: <strong>" . htmlspecialchars($schoolYear, ENT_QUOTES) . "</strong></p>";

                $dayGroups = [];
                foreach ($entries as $entry) {
                    $day = $entry['day_of_week'];
                    if (!isset($dayGroups[$day])) {
                        $dayGroups[$day] = [];
                    }
                    $dayGroups[$day][] = $entry;
                }

                $scheduleHtml .= "<table style='width:100%; border-collapse:collapse;'>";
                $scheduleHtml .= "<thead><tr>";
                $scheduleHtml .= "<th style='border:1px solid #ccc; padding:8px; background:#145A32; color:#fff;'>Day</th>";
                $scheduleHtml .= "<th style='border:1px solid #ccc; padding:8px; background:#145A32; color:#fff;'>Time</th>";
                $scheduleHtml .= "<th style='border:1px solid #ccc; padding:8px; background:#145A32; color:#fff;'>Subject</th>";
                $scheduleHtml .= "<th style='border:1px solid #ccc; padding:8px; background:#145A32; color:#fff;'>Teacher</th>";
                $scheduleHtml .= "<th style='border:1px solid #ccc; padding:8px; background:#145A32; color:#fff;'>Room</th>";
                $scheduleHtml .= "<th style='border:1px solid #ccc; padding:8px; background:#145A32; color:#fff;'>Applies To</th>";
                $scheduleHtml .= "</tr></thead><tbody>";

                $weekdayOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                foreach ($weekdayOrder as $weekday) {
                    if (!isset($dayGroups[$weekday])) {
                        continue;
                    }
                    $entriesForDay = $dayGroups[$weekday];
                    $rowspan = count($entriesForDay);

                    foreach ($entriesForDay as $index => $entry) {
                        $start = $entry['start_time'] ? date('g:i A', strtotime($entry['start_time'])) : '—';
                        $end   = $entry['end_time'] ? date('g:i A', strtotime($entry['end_time'])) : '—';
                        $timeDisplay = ($start === '—' && $end === '—') ? '—' : trim($start . ($end !== '—' ? ' - ' . $end : ''));
                        $appliesTo = ($entry['section'] === 'ALL') ? 'All Sections' : ($entry['section'] ?: 'Section');

                        $scheduleHtml .= "<tr>";
                        if ($index === 0) {
                            $scheduleHtml .= "<td style='border:1px solid #ccc; padding:8px; font-weight:600;' rowspan='" . intval($rowspan) . "'>" . htmlspecialchars($weekday, ENT_QUOTES) . "</td>";
                        }
                        $scheduleHtml .= "<td style='border:1px solid #ccc; padding:8px;'>" . htmlspecialchars($timeDisplay, ENT_QUOTES) . "</td>";
                        $scheduleHtml .= "<td style='border:1px solid #ccc; padding:8px;'>" . htmlspecialchars($entry['subject'], ENT_QUOTES) . "</td>";
                        $scheduleHtml .= "<td style='border:1px solid #ccc; padding:8px;'>" . htmlspecialchars($entry['teacher'] ?: 'TBA', ENT_QUOTES) . "</td>";
                        $scheduleHtml .= "<td style='border:1px solid #ccc; padding:8px;'>" . htmlspecialchars($entry['room'] ?: 'TBA', ENT_QUOTES) . "</td>";
                        $scheduleHtml .= "<td style='border:1px solid #ccc; padding:8px;'>" . htmlspecialchars($appliesTo, ENT_QUOTES) . "</td>";
                        $scheduleHtml .= "</tr>";
                    }
                }

                $scheduleHtml .= "</tbody></table>";
                $scheduleHtml .= "<p style='margin-top:12px;'>Please keep this schedule for your reference. Any future changes will be communicated by the school.</p>";

                $shouldMarkScheduleSent = true;
            }
        }
    }
}

// Send email
$mail = new PHPMailer(true);
$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'deadpoolvictorio@gmail.com';
$mail->Password = 'ldcmeapjfuonxypu';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
$mail->addAddress($email, "$firstname $lastname");

$mail->isHTML(true);
$mail->Subject = 'Payment Receipt and Portal Access';
$mail->Body = "
    <h2 style='color:#2c3e50;'>Payment Receipt Confirmation</h2>
    <p>Dear <strong>$firstname $lastname</strong>,</p>
    <p>We have received your payment for the following:</p>

    <!-- Official Receipt Style -->
    <div style='border:2px solid #333; padding:15px; border-radius:8px; background:#f9f9f9; margin:20px 0;'>
        <h3 style='text-align:center; margin:0;'>Escuela De Sto. Rosario</h3>
        <h4 style='text-align:center; margin:0;'>Official Receipt</h4>
        <p style='text-align:center; font-size:12px; margin:5px 0 15px;'>This serves as your official proof of payment</p>
        
        <table style='width:100%; border-collapse:collapse;'>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>Student Name</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>$firstname $lastname</td>
            </tr>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>Student Number</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>" . ($student_number ?: 'Pending Assignment') . "</td>
            </tr>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>Payment Type</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>$payment_type</td>
            </tr>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>Amount Paid</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>&#8369;" . number_format($amount, 2) . "</td>
            </tr>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>Status</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>$status</td>
            </tr>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>Date Issued</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>" . date("F j, Y") . "</td>
            </tr>
            <tr>
                <td style='padding:6px; border:1px solid #ccc;'><strong>OR Number</strong></td>
                <td style='padding:6px; border:1px solid #ccc;'>" . ($or_number ?? 'N/A') . "</td>
            </tr>
        </table>

       
    </div>

    <p>Your enrollment is now marked as <strong>ENROLLED</strong>.</p>
    <p><strong>IMPORTANT:</strong> Please wait for activation of your student portal.</p>
";

if ($scheduleHtml !== '') {
    $mail->Body .= $scheduleHtml;
}


try {
    $mail->send();
} catch (Throwable $mailError) {
    $logLine = sprintf(
        "[%s] Email worker error for student_id=%d: %s\n",
        date('Y-m-d H:i:s'),
        $student_id,
        $mailError->getMessage()
    );
    @file_put_contents(__DIR__ . '/../temp/email_worker_errors.log', $logLine, FILE_APPEND);
    exit; // Stop further processing if we cannot send the email.
}

if ($shouldMarkScheduleSent) {
    $updateSchedule = $conn->prepare('UPDATE students_registration SET schedule_sent_at = NOW() WHERE id = ?');
    if ($updateSchedule) {
        $updateSchedule->bind_param('i', $student_id);
        $updateSchedule->execute();
        $updateSchedule->close();
    }
}
