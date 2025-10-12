<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mailer.php';

if (!function_exists('registrar_email_worker_process')) {
    /**
     * Send the registrar portal activation email to a student.
     *
     * @param int         $student_id        Student identifier.
     * @param mysqli|null $existingConnection Optional mysqli connection to reuse.
     * @param bool        $appendDebugLog     When true, append debug information to temp logs.
     *
     * @return bool True when the email send completes without throwing.
     */
    function registrar_email_worker_process(
        int $student_id,
        ?mysqli $existingConnection = null,
        bool $appendDebugLog = false
    ): bool {
        $student_id = max(0, $student_id);
        if ($student_id <= 0) {
            error_log('Registrar email worker: invalid student id.');
            return false;
        }

        $conn = $existingConnection;
        $createdConnection = false;

        if (!$conn instanceof mysqli) {
            include __DIR__ . '/../db_connection.php';
            if (!isset($conn) || !($conn instanceof mysqli)) {
                error_log('Registrar email worker: unable to establish database connection.');
                return false;
            }
            $createdConnection = true;
        }

        $tempDir = __DIR__ . '/../temp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        if ($appendDebugLog) {
            $debugContext = sprintf(
                "[%s] worker start student=%d\n",
                date('c'),
                $student_id
            );
            @file_put_contents($tempDir . '/registrar_worker_trace.log', $debugContext, FILE_APPEND);
        }

        $stmt = $conn->prepare(
            'SELECT firstname, lastname, emailaddress, student_number
             FROM students_registration
             WHERE id = ?
             LIMIT 1'
        );
        if (!$stmt) {
            error_log('Registrar email worker: failed to prepare query. ' . $conn->error);
            if ($createdConnection) {
                $conn->close();
            }
            return false;
        }

        $stmt->bind_param('i', $student_id);
        if (!$stmt->execute()) {
            error_log('Registrar email worker: query execution failed. ' . $stmt->error);
            $stmt->close();
            if ($createdConnection) {
                $conn->close();
            }
            return false;
        }

        $stmt->bind_result($firstname, $lastname, $email, $student_number);
        $stmt->fetch();
        $stmt->close();

        $firstname = trim((string) $firstname);
        $lastname = trim((string) $lastname);
        $email = trim((string) $email);
        $student_number = trim((string) $student_number);

        if ($email === '') {
            error_log('Registrar email worker: student record missing email address for id ' . $student_id);
            if ($createdConnection) {
                $conn->close();
            }
            return false;
        }

        $mail = new PHPMailer(true);
        $success = true;

        try {
            $mailerConfig = mailer_apply_defaults($mail);
            $mail->setFrom(
                (string) ($mailerConfig['from_email'] ?? 'no-reply@rosariodigital.site'),
                (string) ($mailerConfig['from_name'] ?? 'Escuela De Sto. Rosario')
            );
            $mail->addAddress($email, trim($firstname . ' ' . $lastname));

            $mail->isHTML(true);
            $mail->Subject = 'Your Student Portal Account Has Been Activated';
            $mail->Body = "
                <p>Dear " . htmlspecialchars($firstname . ' ' . $lastname, ENT_QUOTES) . ",</p>
                <p>Your account for the <strong>Escuela De Sto. Rosario Student Portal</strong> has been successfully activated.</p>
                <p><strong>Login Details:</strong></p>
                <ul>
                    <li><strong>Student Number:</strong> " . htmlspecialchars($student_number ?: 'Pending Assignment', ENT_QUOTES) . "</li>
                    <li><strong>Password:</strong> Set on your first login</li>
                </ul>
                <p>You may now log in through the Student Portal using the above details.</p>
                <br>
                <p>Thank you,<br>ESR Registrar’s Office</p>
            ";

            $logger = static function (string $line) use ($student_id, $tempDir): void {
                @file_put_contents(
                    $tempDir . '/registrar_worker_trace.log',
                    sprintf("[%s] [student:%d] %s\n", date('c'), $student_id, $line),
                    FILE_APPEND
                );
            };

            mailer_send_with_fallback(
                $mail,
                [],
                $logger,
                (bool) ($mailerConfig['fallback_to_mail'] ?? false)
            );
        } catch (Exception $exception) {
            $success = false;
            $logPath = __DIR__ . '/email_errors.log';
            $message = sprintf(
                "[%s] Mail Error for student %d: %s\n",
                date('c'),
                $student_id,
                $exception->getMessage()
            );
            @file_put_contents($logPath, $message, FILE_APPEND);
            error_log('[Registrar] Email worker failed for student ' . $student_id . ': ' . $exception->getMessage());
        }

        if ($createdConnection) {
            $conn->close();
        }

        return $success;
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    global $argv;
    $student_id = (int) ($argv[1] ?? 0);
    $result = registrar_email_worker_process($student_id, null, true);
    if ($result) {
        echo "✅ Registrar email worker completed.\n";
    } else {
        fwrite(STDERR, "❌ Registrar email worker encountered an error.\n");
        exit(1);
    }
}
