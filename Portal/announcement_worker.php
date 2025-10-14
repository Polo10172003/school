<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mailer.php';

if (!function_exists('portal_dispatch_announcement')) {
    /**
     * Map audience codes to grade-level labels stored in the students table.
     *
     * @param string $token
     *
     * @return array<int,string>
     */
    function portal_announcement_grade_variants(string $token): array
    {
        $normalized = strtolower(trim($token));
        $compact = preg_replace('/[^a-z0-9]/', '', $normalized);

        $map = [
            'preschool'   => ['Preschool'],
            'preprime1'   => ['Pre-Prime 1'],
            'pp1'         => ['Pre-Prime 1'],
            'preprime2'   => ['Pre-Prime 2'],
            'pp2'         => ['Pre-Prime 2'],
            'preprime12'  => ['Pre-Prime 1', 'Pre-Prime 2'],
            'pp12'        => ['Pre-Prime 1', 'Pre-Prime 2'],
            'kinder'      => ['Kindergarten', 'Kinder 1', 'Kinder 2'],
            'kindergarten'=> ['Kindergarten', 'Kinder 1', 'Kinder 2'],
            'kinder1'     => ['Kinder 1'],
            'k1'          => ['Kinder 1'],
            'kinder2'     => ['Kinder 2'],
            'k2'          => ['Kinder 2'],
            'kg'          => ['Kindergarten', 'Kinder 1', 'Kinder 2'],
            'grade1'      => ['Grade 1'],
            'g1'          => ['Grade 1'],
            'grade2'      => ['Grade 2'],
            'g2'          => ['Grade 2'],
            'grade3'      => ['Grade 3'],
            'g3'          => ['Grade 3'],
            'grade4'      => ['Grade 4'],
            'g4'          => ['Grade 4'],
            'grade5'      => ['Grade 5'],
            'g5'          => ['Grade 5'],
            'grade6'      => ['Grade 6'],
            'g6'          => ['Grade 6'],
            'grade7'      => ['Grade 7'],
            'g7'          => ['Grade 7'],
            'grade8'      => ['Grade 8'],
            'g8'          => ['Grade 8'],
            'grade9'      => ['Grade 9'],
            'g9'          => ['Grade 9'],
            'grade10'     => ['Grade 10'],
            'g10'         => ['Grade 10'],
            'grade11'     => ['Grade 11'],
            'g11'         => ['Grade 11'],
            'grade12'     => ['Grade 12'],
            'g12'         => ['Grade 12'],
        ];

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }
        if ($compact !== '' && isset($map[$compact])) {
            return $map[$compact];
        }
        if ($normalized === '' || $normalized === 'everyone' || $normalized === 'all') {
            return [];
        }

        return [ucwords($normalized)];
    }

    /**
     * Dispatch announcement emails to the selected scope.
     *
     * @param string      $subject
     * @param string      $body
     * @param string      $scope          Comma separated grade list or 'everyone'.
     * @param int         $announcementId Inserted announcement id (for image lookup).
     * @param string|null $imagePathArg   Optional relative image path provided by caller.
     * @param mysqli|null $existingConn   Optional mysqli connection to reuse.
     * @param bool        $appendDebugLog When true, write verbose logs to temp dir.
     *
     * @return bool True when at least one email batch was queued successfully.
     */
    function portal_dispatch_announcement(
        string $subject,
        string $body,
        string $scope,
        int $announcementId = 0,
        ?string $imagePathArg = null,
        ?mysqli $existingConn = null,
        bool $appendDebugLog = false
    ): bool {
        $subject = trim($subject);
        $body = trim($body);
        $scope = trim($scope);

        if ($subject === '' || $body === '') {
            return false;
        }

        $conn = $existingConn;
        $createdConnection = false;

        if (!$conn instanceof mysqli) {
            include __DIR__ . '/../db_connection.php';
            if (!isset($conn) || !($conn instanceof mysqli)) {
                error_log('[announcement] worker: unable to establish database connection.');
                return false;
            }
            $createdConnection = true;
        }

        $tempDir = __DIR__ . '/../temp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        $traceLog = $tempDir . '/announcement_worker_trace.log';
        if ($appendDebugLog) {
            @file_put_contents(
                $traceLog,
                sprintf("[%s] start scope=%s announcement_id=%d\n", date('c'), $scope, $announcementId),
                FILE_APPEND
            );
        }

        $audienceTokensRaw = array_filter(array_map('trim', explode(',', $scope)), static function ($token): bool {
            return $token !== null && $token !== '';
        });
        $audienceTokens = array_map(static function ($token): string {
            return strtolower(trim((string) $token));
        }, $audienceTokensRaw);

        $recipients = [];
        $targetsEveryone = false;
        foreach ($audienceTokens as $token) {
            if ($token === 'everyone' || $token === 'all') {
                $targetsEveryone = true;
                break;
            }
        }

        if ($targetsEveryone) {
            $stmt = $conn->prepare(
                'SELECT DISTINCT emailaddress, firstname, lastname
                 FROM students_registration
                 WHERE emailaddress IS NOT NULL
                   AND emailaddress <> \'\''
            );
        } else {
            $gradeFilters = [];
            foreach ($audienceTokensRaw as $token) {
                $variants = portal_announcement_grade_variants($token);
                foreach ($variants as $variant) {
                    $variant = trim($variant);
                    if ($variant === '') {
                        continue;
                    }
                    $gradeFilters[strtolower($variant)] = $variant;
                }
            }

            if (empty($gradeFilters)) {
                if ($appendDebugLog) {
                    @file_put_contents(
                        $traceLog,
                        sprintf("[%s] no grade filters matched scope=%s\n", date('c'), $scope),
                        FILE_APPEND
                    );
                }
                if ($createdConnection) {
                    $conn->close();
                }
                return false;
            }

            $gradeValues = array_values($gradeFilters);
            $placeholders = implode(',', array_fill(0, count($gradeValues), '?'));
            $types = str_repeat('s', count($gradeValues));
            $stmt = $conn->prepare(
                "SELECT DISTINCT emailaddress, firstname, lastname
                 FROM students_registration
                 WHERE year IN ($placeholders)
                   AND emailaddress IS NOT NULL
                   AND emailaddress <> ''"
            );
            if ($stmt) {
                $stmt->bind_param($types, ...$gradeValues);
            }
        }

        if (!$stmt) {
            error_log('[announcement] worker: failed to prepare recipient query. ' . $conn->error);
            if ($createdConnection) {
                $conn->close();
            }
            return false;
        }

        if (!$stmt->execute()) {
            error_log('[announcement] worker: recipient query execution failed. ' . $stmt->error);
            $stmt->close();
            if ($createdConnection) {
                $conn->close();
            }
            return false;
        }

        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $email = trim((string) ($row['emailaddress'] ?? ''));
                if ($email === '') {
                    continue;
                }
                $recipients[] = [
                    'email' => $email,
                    'name'  => trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['lastname'] ?? '')),
                ];
            }
            $result->close();
        }
        $stmt->close();

        if ($appendDebugLog) {
            @file_put_contents(
                $traceLog,
                sprintf("[%s] matched recipients=%d\n", date('c'), count($recipients)),
                FILE_APPEND
            );
        }

        if (empty($recipients)) {
            if ($appendDebugLog) {
                @file_put_contents(
                    $traceLog,
                    sprintf("[%s] no recipients found\n", date('c')),
                    FILE_APPEND
                );
            }
            if ($createdConnection) {
                $conn->close();
            }
            return false;
        }

        $imagePath = '';
        if ($announcementId > 0) {
            $imageStmt = $conn->prepare('SELECT image_path FROM student_announcements WHERE id = ? LIMIT 1');
            if ($imageStmt) {
                $imageStmt->bind_param('i', $announcementId);
                if ($imageStmt->execute()) {
                    $imageStmt->bind_result($dbImagePath);
                    if ($imageStmt->fetch()) {
                        $imagePath = trim((string) $dbImagePath);
                    }
                }
                $imageStmt->close();
            }
        }

        if ($imagePath === '' && $imagePathArg !== null && $imagePathArg !== '') {
            $imagePath = trim($imagePathArg);
        }

        $absoluteImagePath = '';
        if ($imagePath !== '') {
            $normalized = ltrim($imagePath, '/');
            $candidate = realpath(__DIR__ . '/../' . $normalized);
            if ($candidate && is_file($candidate)) {
                $absoluteImagePath = $candidate;
            }
        }

        $mailBody = '<div style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6;">'
            . '<p>' . nl2br(htmlspecialchars($body, ENT_QUOTES)) . '</p>';

        $hasEmbeddedImage = false;
        $embeddedCid = null;

        if ($absoluteImagePath !== '') {
            $embeddedCid = 'announcement_image_' . uniqid('', true);
            $mailBody .= '<div style="margin-top:16px; text-align:center;">'
                . '<img src="cid:' . htmlspecialchars($embeddedCid, ENT_QUOTES) . '" alt="Announcement Image" style="max-width:100%; border-radius:8px;" />'
                . '</div>';
            $hasEmbeddedImage = true;
        }

        $mailBody .= '</div>';

        $batchSize = 40;
        $batches = array_chunk($recipients, $batchSize);
        $successfulBatches = 0;

        foreach ($batches as $batchIndex => $batchRecipients) {
            $mail = new PHPMailer(true);
            try {
                $mailerConfig = mailer_apply_defaults($mail);
                $mail->setFrom(
                    (string) ($mailerConfig['from_email'] ?? 'no-reply@rosariodigital.site'),
                    (string) ($mailerConfig['from_name'] ?? 'Escuela De Sto. Rosario')
                );
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $mailBody;
                $mail->AltBody = $absoluteImagePath !== '' ? ($body . "\n\n[See attached image]") : $body;

                if ($hasEmbeddedImage && $embeddedCid !== null && $absoluteImagePath !== '') {
                    $mail->addEmbeddedImage($absoluteImagePath, $embeddedCid, basename($absoluteImagePath));
                }

                if ($absoluteImagePath !== '') {
                    $mail->addAttachment($absoluteImagePath);
                }

                $first = array_shift($batchRecipients);
                if ($first !== null) {
                    $mail->addAddress($first['email'], $first['name']);
                }
                foreach ($batchRecipients as $recipient) {
                    $mail->addBCC($recipient['email'], $recipient['name']);
                }

                if ($appendDebugLog) {
                    @file_put_contents(
                        $traceLog,
                        sprintf("[%s] sending batch %d with %d recipients\n", date('c'), $batchIndex + 1, count($batchRecipients) + 1),
                        FILE_APPEND
                    );
                }

                $logger = static function (string $line) use ($announcementId, $traceLog): void {
                    @file_put_contents(
                        $traceLog,
                        sprintf("[%s] [Announcement:%d] %s\n", date('c'), $announcementId, $line),
                        FILE_APPEND
                    );
                };

                mailer_send_with_fallback(
                    $mail,
                    [],
                    $logger,
                    (bool) ($mailerConfig['fallback_to_mail'] ?? false)
                );
                $successfulBatches++;
            } catch (Exception $exception) {
                $errorMessage = sprintf(
                    '[announcement] batch %d failed: %s',
                    $batchIndex + 1,
                    $exception->getMessage()
                );
                error_log($errorMessage);
                @file_put_contents(
                    $tempDir . '/email_worker_errors.log',
                    sprintf("[%s] %s\n", date('c'), $errorMessage),
                    FILE_APPEND
                );
            }
        }

        if ($createdConnection) {
            $conn->close();
        }

        return $successfulBatches > 0;
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    global $argv;
    $subject = (string) ($argv[1] ?? '');
    $body = (string) ($argv[2] ?? '');
    $scope = (string) ($argv[3] ?? '');
    $announcementId = (int) ($argv[4] ?? 0);
    $imagePath = $argv[5] ?? null;

    $result = portal_dispatch_announcement($subject, $body, $scope, $announcementId, $imagePath, null, true);
    if ($result) {
        echo "✅ Announcement worker completed.\n";
    } else {
        fwrite(STDERR, "❌ Announcement worker encountered an error.\n");
        exit(1);
    }
}
