<?php
require '../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/cashier_dashboard_logic.php';

use PHPMailer\PHPMailer\PHPMailer;

$student_id     = $argv[1] ?? 0;
$payment_type   = $argv[2] ?? '';
$amount         = $argv[3] ?? 0;
$status         = $argv[4] ?? '';

if (!$student_id) {
    exit;
}

// Fetch student info
$stmt = $conn->prepare("SELECT emailaddress, firstname, lastname, student_number, year, section, schedule_sent_at, student_type FROM students_registration WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($email, $firstname, $lastname, $student_number, $grade_level, $student_section, $schedule_sent_at, $student_type);
$stmt->fetch();
$stmt->close();

if (!$email) {
    exit;
}

// Fetch latest OR number from payments
$or_number = null;
$payStmt = $conn->prepare("SELECT or_number FROM student_payments WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$payStmt->bind_param("i", $student_id);
$payStmt->execute();
$payStmt->bind_result($or_number);
$payStmt->fetch();
$payStmt->close();

$scheduleHtml = '';
$scheduleIncluded = false;

$currentGradeKey = cashier_normalize_grade_key((string) $grade_level);
$currentGradePaidCount = 0;
$currentGradePaidSum = 0.0;
$dueThreshold = null;
$shouldAttachSchedule = false;

if (empty($schedule_sent_at)) {
    // Build cumulative totals of paid receipts per grade so we can tell when the current grade crosses due-upon-enrollment
    $totalPaidPerGrade = [];
    $paymentsStmt = $conn->prepare('SELECT amount, grade_level, created_at FROM student_payments WHERE student_id = ? AND LOWER(payment_status) = "paid" ORDER BY created_at ASC');
    if ($paymentsStmt) {
        $paymentsStmt->bind_param('i', $student_id);
        $paymentsStmt->execute();
        $result = $paymentsStmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $normalizedGrade = cashier_normalize_grade_key((string) ($row['grade_level'] ?? ''));
            if ($normalizedGrade === '') {
                $normalizedGrade = $currentGradeKey;
            }
            if (!isset($totalPaidPerGrade[$normalizedGrade])) {
                $totalPaidPerGrade[$normalizedGrade] = ['count' => 0, 'sum' => 0.0];
            }
            $totalPaidPerGrade[$normalizedGrade]['count']++;
            $totalPaidPerGrade[$normalizedGrade]['sum'] += (float) ($row['amount'] ?? 0);
        }
        $paymentsStmt->close();
    }

    $currentGradePaidCount = $totalPaidPerGrade[$currentGradeKey]['count'] ?? 0;
    $currentGradePaidSum   = $totalPaidPerGrade[$currentGradeKey]['sum'] ?? 0.0;

    if ($currentGradePaidCount > 0) {
        $studentTypeNormalized = strtolower(trim((string) $student_type));
        if ($studentTypeNormalized === '') {
            $studentTypeNormalized = 'new';
        }
        $typeCandidates = array_values(array_unique([$studentTypeNormalized, 'new', 'old', 'all']));
        $fee = cashier_fetch_fee($conn, $currentGradeKey, $typeCandidates);

        if ($fee) {
            $plans = $fee['plans'] ?? [];
            $selectedPlanType = null;
            $planSelection = cashier_dashboard_fetch_selected_plan($conn, $student_id, (int) $fee['id']);
            if ($planSelection) {
                $selectedPlanType = strtolower((string) ($planSelection['plan_type'] ?? ''));
            }

            foreach ($plans as $planRow) {
                $planType = strtolower(str_replace([' ', '-'], '_', (string) ($planRow['plan_type'] ?? '')));
                $dueValue = isset($planRow['due_upon_enrollment']) ? (float) $planRow['due_upon_enrollment'] : null;
                if ($dueValue === null) {
                    continue;
                }
                if ($selectedPlanType && $planType === $selectedPlanType) {
                    $dueThreshold = $dueValue;
                    break;
                }
                if ($dueThreshold === null || $dueValue < $dueThreshold) {
                    $dueThreshold = $dueValue;
                }
            }

            if ($dueThreshold === null) {
                $dueThreshold = (float) ($fee['entrance_fee'] ?? 0)
                    + (float) ($fee['miscellaneous_fee'] ?? 0)
                    + (float) ($fee['tuition_fee'] ?? 0);
            }

            if ($currentGradePaidSum >= max(0.0, $dueThreshold - 0.01)) {
                $shouldAttachSchedule = true;
            }
        } else {
            // No tuition fee configuration for this grade – still send the schedule after the first paid receipt
            $shouldAttachSchedule = true;
        }
    }

    if ($shouldAttachSchedule) {
        $sectionForLookup = $student_section !== null && $student_section !== '' ? strtolower($student_section) : null;

        $gradeCandidates = cashier_grade_synonyms($currentGradeKey);
        if (!in_array($currentGradeKey, $gradeCandidates, true)) {
            $gradeCandidates[] = $currentGradeKey;
        }

        $scheduleEntries = [];
        $schoolYear = null;

        foreach ($gradeCandidates as $gradeCandidate) {
            $gradeToken = strtolower(str_replace([' ', '-', '_'], '', $gradeCandidate));

            $yearStmt = $conn->prepare("
                SELECT school_year
                FROM class_schedules
                WHERE REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') = ?
                ORDER BY updated_at DESC
                LIMIT 1
            ");
            if (!$yearStmt) {
                continue;
            }
            $yearStmt->bind_param('s', $gradeToken);
            $yearStmt->execute();
            $yearStmt->bind_result($foundYear);
            if ($yearStmt->fetch()) {
                $schoolYear = $foundYear;
            }
            $yearStmt->close();

            if (!$schoolYear) {
                continue;
            }

            $scheduleStmt = $conn->prepare("
                SELECT section, subject, teacher, day_of_week, start_time, end_time, room
                FROM class_schedules
                WHERE REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') = ?
                  AND school_year = ?
                ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time IS NULL, start_time
            ");
            if (!$scheduleStmt) {
                $schoolYear = null;
                continue;
            }
            $scheduleStmt->bind_param('ss', $gradeToken, $schoolYear);
            $scheduleStmt->execute();
            $result = $scheduleStmt->get_result();
            if ($result) {
                $scheduleEntries = $result->fetch_all(MYSQLI_ASSOC);
            }
            $scheduleStmt->close();

            if (!empty($scheduleEntries)) {
                break;
            }

            $schoolYear = null;
        }

        if (!empty($scheduleEntries) && $schoolYear) {
            $sectionCandidates = [];
            if ($sectionForLookup !== null && $sectionForLookup !== '') {
                $sectionCandidates[] = $sectionForLookup;
                $stripped = preg_replace('/\bsection\b/i', '', $sectionForLookup);
                $stripped = preg_replace('/\bsec\b/i', '', $stripped);
                $stripped = trim($stripped);
                if ($stripped !== '' && $stripped !== $sectionForLookup) {
                    $sectionCandidates[] = $stripped;
                }
                $lettersOnly = preg_replace('/[^a-z0-9]/', '', $stripped);
                if ($lettersOnly !== '' && !in_array($lettersOnly, $sectionCandidates, true)) {
                    $sectionCandidates[] = $lettersOnly;
                }
            }

            if (!empty($sectionCandidates)) {
                $filtered = [];
                foreach ($scheduleEntries as $entry) {
                    $entrySection = strtolower(trim((string) ($entry['section'] ?? '')));
                    if ($entrySection === '' || $entrySection === 'all') {
                        $filtered[] = $entry;
                        continue;
                    }
                    foreach ($sectionCandidates as $candidate) {
                        if ($entrySection === $candidate) {
                            $filtered[] = $entry;
                            break;
                        }
                    }
                }
                if (!empty($filtered)) {
                    $scheduleEntries = $filtered;
                }
            }

            if (!empty($scheduleEntries)) {
                $scheduleHtml .= "<h3 style='color:#2c3e50;margin-top:24px;'>Class Schedule</h3>";
                $scheduleHtml .= "<p style='margin:6px 0 14px;'>Grade: <strong>" . htmlspecialchars($grade_level, ENT_QUOTES) . "</strong> &bull; Section: <strong>" . htmlspecialchars($student_section ?: 'To be assigned', ENT_QUOTES) . "</strong><br>School Year: <strong>" . htmlspecialchars($schoolYear, ENT_QUOTES) . "</strong></p>";

                $dayGroups = [];
                foreach ($scheduleEntries as $entry) {
                    $day = $entry['day_of_week'] ?? 'TBA';
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
                    if (empty($dayGroups[$weekday])) {
                        continue;
                    }
                    $entriesForDay = $dayGroups[$weekday];
                    $rowspan = count($entriesForDay);

                    foreach ($entriesForDay as $index => $entry) {
                        $start = isset($entry['start_time']) && $entry['start_time'] ? date('g:i A', strtotime($entry['start_time'])) : '—';
                        $end   = isset($entry['end_time']) && $entry['end_time'] ? date('g:i A', strtotime($entry['end_time'])) : '—';
                        $timeDisplay = ($start === '—' && $end === '—') ? '—' : trim($start . ($end !== '—' ? ' - ' . $end : ''));
                        $appliesTo = isset($entry['section']) && strtolower($entry['section']) === 'all'
                            ? 'All Sections'
                            : ($entry['section'] ?: ($student_section ?: 'Section'));

                        $scheduleHtml .= '<tr>';
                        if ($index === 0) {
                            $scheduleHtml .= "<td style='border:1px solid #ccc; padding:8px; font-weight:600;' rowspan='" . intval($rowspan) . "'>" . htmlspecialchars($weekday, ENT_QUOTES) . "</td>";
                        }
                        $scheduleHtml .= "<td style='border:1px solid #ccc; padding:8px;'>" . htmlspecialchars($timeDisplay, ENT_QUOTES) . "</td>";
                        $scheduleHtml .= "<td style='border:1px solid #ccc; padding:8px;'>" . htmlspecialchars($entry['subject'] ?? 'Subject', ENT_QUOTES) . "</td>";
                        $scheduleHtml .= "<td style='border:1px solid #ccc; padding:8px;'>" . htmlspecialchars($entry['teacher'] ?? 'TBA', ENT_QUOTES) . "</td>";
                        $scheduleHtml .= "<td style='border:1px solid #ccc; padding:8px;'>" . htmlspecialchars($entry['room'] ?? 'TBA', ENT_QUOTES) . "</td>";
                        $scheduleHtml .= "<td style='border:1px solid #ccc; padding:8px;'>" . htmlspecialchars($appliesTo ?: 'Section', ENT_QUOTES) . "</td>";
                        $scheduleHtml .= '</tr>';
                    }
                }

                $scheduleHtml .= "</tbody></table>";
                $scheduleHtml .= "<p style='margin-top:12px;'>Please keep this schedule for your reference. Any future changes will be communicated by the school.</p>";

                $scheduleIncluded = true;
            }
        }
    }
}

# Debug logging to trace schedule decisions
$debugLogPath = __DIR__ . '/../temp/email_worker_debug.log';
$debugMessage = sprintf(
    "[%s] student=%d grade=%s schedule_sent_at=%s paid_count=%d paid_sum=%.2f due_threshold=%s attach=%s included=%s\n",
    date('Y-m-d H:i:s'),
    $student_id,
    $currentGradeKey,
    $schedule_sent_at === null ? 'NULL' : $schedule_sent_at,
    $currentGradePaidCount ?? 0,
    $currentGradePaidSum ?? 0.0,
    $dueThreshold === null ? 'NULL' : number_format((float)$dueThreshold, 2),
    isset($shouldAttachSchedule) && $shouldAttachSchedule ? 'yes' : 'no',
    $scheduleIncluded ? 'yes' : 'no'
);
@file_put_contents($debugLogPath, $debugMessage, FILE_APPEND);

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
    exit;
}

if ($scheduleIncluded) {
    $updateSchedule = $conn->prepare('UPDATE students_registration SET schedule_sent_at = NOW() WHERE id = ?');
    if ($updateSchedule) {
        $updateSchedule->bind_param('i', $student_id);
        $updateSchedule->execute();
        $updateSchedule->close();
    }
}
