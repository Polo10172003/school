<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mailer.php';

if (!function_exists('cashier_normalize_grade_key')) {
    require_once __DIR__ . '/cashier_dashboard_logic.php';
}

if (!function_exists('cashier_email_worker_process')) {
    /**
     * Send cashier payment receipt email (optionally with class schedule) to a student.
     *
     * @param int         $student_id        Student primary key.
     * @param string      $payment_type      Payment type label.
     * @param float|int   $amount            Amount paid.
     * @param string      $status            Payment status string.
     * @param mysqli|null $existingConnection Optional open mysqli connection.
     * @param bool        $appendDebugLog     Write debug info to temp file when true.
     *
     * @return bool True when the email was dispatched (or at least attempted without fatal failure).
     */
    function cashier_email_worker_process(
        int $student_id,
        string $payment_type,
        $amount,
        string $status,
        ?mysqli $existingConnection = null,
        bool $appendDebugLog = false
    ): bool {
        $student_id = max(0, $student_id);
        $payment_type = trim($payment_type);
        $status = trim($status);
        $amountFloat = (float) $amount;

        if ($student_id <= 0) {
            error_log('Cashier email worker: invalid student id.');
            return false;
        }

        $conn = $existingConnection;
        $createdConnection = false;

        if (!$conn instanceof mysqli) {
            include __DIR__ . '/../db_connection.php';
            if (!isset($conn) || !($conn instanceof mysqli)) {
                error_log('Cashier email worker: unable to establish database connection.');
                return false;
            }
            $createdConnection = true;
        }

        $tempDir = __DIR__ . '/../temp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        @file_put_contents(
            $tempDir . '/cashier_worker_trace.log',
            sprintf(
                "[%s] worker start student=%d type=%s amount=%.2f status=%s\n",
                date('c'),
                $student_id,
                $payment_type,
                $amountFloat,
                $status
            ),
            FILE_APPEND
        );

        if ($appendDebugLog) {
            $debugPayload = [
                'timestamp'     => date('c'),
                'student_id'    => $student_id,
                'payment_type'  => $payment_type,
                'amount'        => $amountFloat,
                'status'        => $status,
                'environment'   => php_sapi_name(),
            ];
            @file_put_contents(
                $tempDir . '/cashier_worker_debug.txt',
                print_r($debugPayload, true),
                FILE_APPEND
            );
        }

        $scheduleColumnAvailable = true;
        $stmt = $conn->prepare("
            SELECT emailaddress, firstname, lastname, student_number, year, section, schedule_sent_at, student_type
            FROM students_registration
            WHERE id = ?
            LIMIT 1
        ");
        if (!$stmt) {
            error_log('Cashier email worker: primary student query failed. ' . $conn->error);
            $scheduleColumnAvailable = false;
            $stmt = $conn->prepare("
                SELECT emailaddress, firstname, lastname, student_number, year, section, student_type
                FROM students_registration
                WHERE id = ?
                LIMIT 1
            ");
        }

        if (!$stmt) {
            error_log('Cashier email worker: fallback student query failed. ' . $conn->error);
            @file_put_contents(
                $tempDir . '/cashier_worker_trace.log',
                sprintf('[%s] abort student=%d reason=student_lookup_failed error=%s' . "\n", date('c'), $student_id, $conn->error),
                FILE_APPEND
            );
            if ($createdConnection) {
                $conn->close();
            }
            return false;
        }

        $stmt->bind_param('i', $student_id);
        $stmt->execute();

        $schedule_sent_at_raw = null;
        if ($scheduleColumnAvailable) {
            $stmt->bind_result(
                $email,
                $firstname,
                $lastname,
                $student_number,
                $grade_level,
                $student_section,
                $schedule_sent_at_raw,
                $student_type
            );
        } else {
            $stmt->bind_result(
                $email,
                $firstname,
                $lastname,
                $student_number,
                $grade_level,
                $student_section,
                $student_type
            );
        }
        $stmt->fetch();
        $stmt->close();

        @file_put_contents(
            $tempDir . '/cashier_worker_trace.log',
            sprintf(
                "[%s] resolved student=%d email=%s grade=%s student_type=%s\n",
                date('c'),
                $student_id,
                $email !== null && $email !== '' ? $email : 'N/A',
                (string) $grade_level,
                (string) $student_type
            ),
            FILE_APPEND
        );

        if (!$email) {
            error_log('Cashier email worker: student record missing email.');
            @file_put_contents(
                $tempDir . '/cashier_worker_trace.log',
                sprintf("[%s] abort student=%d reason=missing_email\n", date('c'), $student_id),
                FILE_APPEND
            );
            if ($createdConnection) {
                $conn->close();
            }
            return false;
        }

        $or_number = null;
        $orStmt = $conn->prepare(query: "SELECT or_number FROM student_payments WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
        if ($orStmt) {
            $orStmt->bind_param('i', $student_id);
            $orStmt->execute();
            $orStmt->bind_result($or_number);
            $orStmt->fetch();
            $orStmt->close();
        }

        $scheduleHtml = '';
        $scheduleIncluded = false;
        $scheduleSentNow = null;

        $currentGradeKey = cashier_normalize_grade_key((string) $grade_level);
        $currentGradePaidCount = 0;
        $currentGradePaidSum = 0.0;
        $dueThreshold = null;
        $shouldAttachSchedule = false;

        $schedulePreviouslySent = false;
        if (!$scheduleColumnAvailable) {
            $schedulePreviouslySent = false;
        } elseif ($schedule_sent_at_raw === null || $schedule_sent_at_raw === '' || $schedule_sent_at_raw === '0000-00-00 00:00:00') {
            $schedulePreviouslySent = false;
        } else {
            $normalizedScheduleSent = strtolower(trim((string) $schedule_sent_at_raw));
            $schedulePreviouslySent = $normalizedScheduleSent !== 'pending';
        }

        if (!$schedulePreviouslySent) {
            $totalPaidPerGrade = [];
            $paymentsStmt = $conn->prepare('
                SELECT amount, grade_level, created_at
                FROM student_payments
                WHERE student_id = ?
                  AND LOWER(payment_status) IN ("paid","completed","approved","cleared")
                ORDER BY created_at ASC
            ');
            if ($paymentsStmt) {
                $paymentsStmt->bind_param('i', $student_id);
                $paymentsStmt->execute();
                $result = $paymentsStmt->get_result();
                if ($result) {
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
                }
                $paymentsStmt->close();
            }

            $aggregateTotals = static function (array $map, string $gradeKey): array {
                $totalCount = 0;
                $totalSum   = 0.0;
                $keys = [];

                if ($gradeKey !== '') {
                    $keys = array_merge($keys, cashier_grade_synonyms($gradeKey));
                }
                $keys[] = $gradeKey;
                $keys = array_values(array_unique(array_filter($keys, static function ($key) {
                    return $key !== null && $key !== '';
                })));

                foreach ($keys as $key) {
                    if (isset($map[$key])) {
                        $totalCount += (int) ($map[$key]['count'] ?? 0);
                        $totalSum   += (float) ($map[$key]['sum'] ?? 0);
                    }
                }

                return ['count' => $totalCount, 'sum' => $totalSum];
            };

            $currentTotals = $aggregateTotals($totalPaidPerGrade, $currentGradeKey);
            $currentGradePaidCount = $currentTotals['count'];
            $currentGradePaidSum   = $currentTotals['sum'];

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
                    $gradeToken = strtolower(str_replace([' ', '-', '_'], '', (string) $gradeCandidate));
                    $gradeToken = preg_replace('/[^a-z0-9]/', '', $gradeToken);
                    if ($gradeToken === '') {
                        continue;
                    }

                    $yearStmt = $conn->prepare("
                        SELECT school_year
                        FROM class_schedules
                        WHERE REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') = ?
                           OR INSTR(REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', ''), ?) > 0
                           OR REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') = REPLACE(?, 'primary', 'prime')
                           OR INSTR(REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', ''), REPLACE(?, 'primary', 'prime')) > 0
                        ORDER BY updated_at DESC
                        LIMIT 1
                    ");
                    if (!$yearStmt) {
                        continue;
                    }
                    $gradeTokenAdjusted = str_replace('primary', 'prime', $gradeToken);
                    $yearStmt->bind_param('ssss', $gradeToken, $gradeToken, $gradeTokenAdjusted, $gradeTokenAdjusted);
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
                        WHERE (
                                REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') = ?
                             OR INSTR(REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', ''), ?) > 0
                             OR REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') = REPLACE(?, 'primary', 'prime')
                             OR INSTR(REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', ''), REPLACE(?, 'primary', 'prime')) > 0
                              )
                          AND school_year = ?
                        ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time IS NULL, start_time
                    ");
                    if (!$scheduleStmt) {
                        $schoolYear = null;
                        continue;
                    }
                    $scheduleStmt->bind_param('sssss', $gradeToken, $gradeToken, $gradeTokenAdjusted, $gradeTokenAdjusted, $schoolYear);
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
                        $mutations = [];
                        $mutations[] = preg_replace('/\bsection\b/i', '', $sectionForLookup);
                        $mutations[] = preg_replace('/\bsec\b/i', '', $sectionForLookup);
                        $mutations[] = preg_replace('/(section|sec|sect|section-)/i', '', $sectionForLookup);
                        $mutations[] = str_replace(['section', 'sec', '-'], ' ', $sectionForLookup);

                        foreach ($mutations as $mut) {
                            $mut = strtolower(trim((string) $mut));
                            if ($mut !== '' && !in_array($mut, $sectionCandidates, true)) {
                                $sectionCandidates[] = $mut;
                            }
                            $lettersOnly = preg_replace('/[^a-z0-9]/', '', $mut);
                            if ($lettersOnly !== '' && !in_array($lettersOnly, $sectionCandidates, true)) {
                                $sectionCandidates[] = $lettersOnly;
                            }
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
                        $scheduleHtml .= "<p style='margin:6px 0 14px;'>Grade: <strong>" . htmlspecialchars((string) $grade_level, ENT_QUOTES) . "</strong> &bull; Section: <strong>" . htmlspecialchars($student_section ?: 'To be assigned', ENT_QUOTES) . "</strong><br>School Year: <strong>" . htmlspecialchars((string) $schoolYear, ENT_QUOTES) . "</strong></p>";

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
                                $start = isset($entry['start_time']) && $entry['start_time'] ? date('g:i A', strtotime((string) $entry['start_time'])) : '—';
                                $end   = isset($entry['end_time']) && $entry['end_time'] ? date('g:i A', strtotime((string) $entry['end_time'])) : '—';
                                $timeDisplay = ($start === '—' && $end === '—') ? '—' : trim($start . ($end !== '—' ? ' - ' . $end : ''));
                                $appliesTo = isset($entry['section']) && strtolower((string) $entry['section']) === 'all'
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
                        $scheduleSentNow = date('Y-m-d H:i:s');
                    }
                }
            }
        }

        $debugLogPath = $tempDir . '/email_worker_debug.log';
        $debugMessage = sprintf(
            "[%s] student=%d grade=%s schedule_sent_at=%s paid_count=%d paid_sum=%.2f due_threshold=%s attach=%s included=%s\n",
            date('Y-m-d H:i:s'),
            $student_id,
            $currentGradeKey,
            $scheduleColumnAvailable
                ? ($schedule_sent_at_raw === null ? 'NULL' : (string) $schedule_sent_at_raw)
                : 'column_missing',
            $currentGradePaidCount ?? 0,
            $currentGradePaidSum ?? 0.0,
            $dueThreshold === null ? 'NULL' : number_format((float) $dueThreshold, 2),
            $shouldAttachSchedule ? 'yes' : 'no',
            $scheduleIncluded ? 'yes' : 'no'
        );
        @file_put_contents($debugLogPath, $debugMessage, FILE_APPEND);

        $mail = new PHPMailer(true);
        $mailerConfig = mailer_apply_defaults($mail);
        if ($appendDebugLog) {
            $mail->SMTPDebug = max($mail->SMTPDebug, 3);
            $mail->Debugoutput = static function (string $str, int $level) use ($tempDir): void {
                @file_put_contents(
                    $tempDir . '/cashier_worker_trace.log',
                    sprintf("[%s][smtp:%d] %s\n", date('c'), $level, trim($str)),
                    FILE_APPEND
                );
            };
        }
        $mail->setFrom(
            (string) ($mailerConfig['from_email'] ?? 'no-reply@rosariodigital.site'),
            (string) ($mailerConfig['from_name'] ?? 'Escuela De Sto. Rosario')
        );
        $mail->addAddress($email, trim($firstname . ' ' . $lastname));

        $mail->isHTML(true);
        $mail->Subject = 'Payment Receipt and Portal Access';

        $mail->Body = "
            <h2 style='color:#2c3e50;'>Payment Receipt Confirmation</h2>
            <p>Dear <strong>" . htmlspecialchars($firstname . ' ' . $lastname, ENT_QUOTES) . "</strong>,</p>
            <p>We have received your payment for the following:</p>
            <div style='border:2px solid #333; padding:15px; border-radius:8px; background:#f9f9f9; margin:20px 0;'>
                <h3 style='text-align:center; margin:0;'>Escuela De Sto. Rosario</h3>
                <h4 style='text-align:center; margin:0;'>Official Receipt</h4>
                <p style='text-align:center; font-size:12px; margin:5px 0 15px;'>This serves as your official proof of payment</p>
                <table style='width:100%; border-collapse:collapse;'>
                    <tr>
                        <td style='padding:6px; border:1px solid #ccc;'><strong>Student Name</strong></td>
                        <td style='padding:6px; border:1px solid #ccc;'>" . htmlspecialchars($firstname . ' ' . $lastname, ENT_QUOTES) . "</td>
                    </tr>
                    <tr>
                        <td style='padding:6px; border:1px solid #ccc;'><strong>Student Number</strong></td>
                        <td style='padding:6px; border:1px solid #ccc;'>" . htmlspecialchars($student_number ?: 'Pending Assignment', ENT_QUOTES) . "</td>
                    </tr>
                    <tr>
                        <td style='padding:6px; border:1px solid #ccc;'><strong>Payment Type</strong></td>
                        <td style='padding:6px; border:1px solid #ccc;'>" . htmlspecialchars($payment_type, ENT_QUOTES) . "</td>
                    </tr>
                    <tr>
                        <td style='padding:6px; border:1px solid #ccc;'><strong>Amount Paid</strong></td>
                        <td style='padding:6px; border:1px solid #ccc;'>&#8369;" . number_format($amountFloat, 2) . "</td>
                    </tr>
                    <tr>
                        <td style='padding:6px; border:1px solid #ccc;'><strong>Status</strong></td>
                        <td style='padding:6px; border:1px solid #ccc;'>" . htmlspecialchars($status, ENT_QUOTES) . "</td>
                    </tr>
                    <tr>
                        <td style='padding:6px; border:1px solid #ccc;'><strong>Date Issued</strong></td>
                        <td style='padding:6px; border:1px solid #ccc;'>" . date('F j, Y') . "</td>
                    </tr>
                    <tr>
                        <td style='padding:6px; border:1px solid #ccc;'><strong>OR Number</strong></td>
                        <td style='padding:6px; border:1px solid #ccc;'>" . htmlspecialchars($or_number ?? 'N/A', ENT_QUOTES) . "</td>
                    </tr>
                </table>
            </div>
            <p>Your enrollment is now marked as <strong>ENROLLED</strong>.</p>
            <p><strong>IMPORTANT:</strong> Please wait for activation of your student portal.</p>
        ";

       if ($scheduleHtml !== '') {
           $mail->Body .= $scheduleHtml;
       }

       // Capture the final rendered email for diagnostics.
       $previewPath = $tempDir . '/cashier_last_email.html';
       @file_put_contents(
           $previewPath,
           "<!-- Generated: " . date('c') . " | Student ID: {$student_id} -->\n" . $mail->Body
       );

        if ($scheduleIncluded && !$schedulePreviouslySent && $scheduleColumnAvailable) {
            $updateSchedule = $conn->prepare('UPDATE students_registration SET schedule_sent_at = ? WHERE id = ?');
            if ($updateSchedule) {
                $updateSchedule->bind_param('si', $scheduleSentNow, $student_id);
                if (!$updateSchedule->execute()) {
                    error_log('[cashier] email worker failed to update schedule_sent_at for student ' . $student_id . ': ' . $updateSchedule->error);
                }
                $updateSchedule->close();
            } else {
                error_log('[cashier] email worker could not prepare schedule update for student ' . $student_id . ': ' . $conn->error);
            }
        }

       $smtpLogger = static function (string $line) use ($tempDir, $student_id): void {
           @file_put_contents(
               $tempDir . '/cashier_worker_trace.log',
               sprintf("[%s][student:%d] %s\n", date('c'), $student_id, $line),
               FILE_APPEND
           );
       };

        @file_put_contents(
            $tempDir . '/cashier_worker_trace.log',
            sprintf(
                "[%s] preparing_send student=%d type=%s amount=%.2f status=%s\n",
                date('c'),
                $student_id,
                $payment_type,
                $amountFloat,
                $status
            ),
            FILE_APPEND
        );

       try {
           mailer_send_with_fallback(
               $mail,
               [],
               $smtpLogger,
                (bool) ($mailerConfig['fallback_to_mail'] ?? false)
            );
            @file_put_contents(
                $tempDir . '/cashier_worker_trace.log',
                sprintf("[%s] email sent to %s\n", date('c'), $email),
                FILE_APPEND
            );
        } catch (Exception $mailError) {
            $logLine = sprintf(
                "[%s] Email worker error for student_id=%d: %s\n",
                date('Y-m-d H:i:s'),
                $student_id,
                $mailError->getMessage()
            );
            @file_put_contents($tempDir . '/email_worker_errors.log', $logLine, FILE_APPEND);
            @file_put_contents(
                $tempDir . '/cashier_worker_trace.log',
                sprintf("[%s] send_failed student=%d error=%s\n", date('c'), $student_id, $mailError->getMessage()),
                FILE_APPEND
            );
            if ($createdConnection) {
                $conn->close();
            }
            return false;
        }

        if ($createdConnection) {
            $conn->close();
        }

        return true;
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    global $argv;
    $student_id  = (int) ($argv[1] ?? 0);
    $paymentType = (string) ($argv[2] ?? '');
    $amount      = (float) ($argv[3] ?? 0);
    $status      = (string) ($argv[4] ?? '');

    $result = cashier_email_worker_process($student_id, $paymentType, $amount, $status, null, true);
    if ($result) {
        echo "✅ Cashier email worker completed.\n";
    } else {
        fwrite(STDERR, "❌ Cashier email worker encountered an error.\n");
        exit(1);
    }
}
