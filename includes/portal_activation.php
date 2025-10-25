<?php
declare(strict_types=1);

require_once __DIR__ . '/transaction_logger.php';

if (!function_exists('ensure_student_portal_activation')) {
    /**
     * Ensure a student's portal account exists and is marked as activated.
     * Mirrors the behaviour of the registrar bulk activation flow, but can be
     * invoked from other workflows (e.g., cashier payment processing).
     *
     * @param mysqli $conn
     * @param int    $studentId
     * @param array  $options {
     *   @var string|null context    Log context label (cashier, registrar, etc).
     *   @var int|null    payment_id Optional payment identifier to attach to logs.
     *   @var bool        send_email Dispatch registrar activation email when true.
     * }
     *
     * @return array<string,mixed>
     */
    function ensure_student_portal_activation(mysqli $conn, int $studentId, array $options = []): array
    {
        $defaults = [
            'context'    => 'system',
            'payment_id' => null,
            'send_email' => true,
        ];
        $options = array_merge($defaults, $options);

        $result = [
            'student_found'        => false,
            'already_activated'    => false,
            'activation_performed' => false,
            'account_existed'      => null,
            'account_created'      => false,
            'account_updated'      => false,
            'email_attempted'      => false,
            'email_dispatched'     => null,
            'errors'               => [],
        ];

        $studentId = max(0, $studentId);
        if ($studentId <= 0) {
            $result['errors'][] = 'invalid_student_id';
            return $result;
        }

        $studentStmt = $conn->prepare('
            SELECT
                id,
                firstname,
                lastname,
                emailaddress,
                student_number,
                portal_status,
                enrollment_status
            FROM students_registration
            WHERE id = ?
            LIMIT 1
        ');
        if (!$studentStmt) {
            $result['errors'][] = 'student_lookup_prepare_failed';
            return $result;
        }

        $studentStmt->bind_param('i', $studentId);
        if (!$studentStmt->execute()) {
            $result['errors'][] = 'student_lookup_execute_failed';
            $studentStmt->close();
            return $result;
        }

        $studentRow = $studentStmt->get_result()->fetch_assoc();
        $studentStmt->close();

        if (!$studentRow) {
            $result['errors'][] = 'student_not_found';
            return $result;
        }
        $result['student_found'] = true;

        if (!in_array($studentRow['enrollment_status'] ?? '', ['enrolled', 'ready', 'waiting'], true)) {
            $result['errors'][] = 'student_not_enrolled';
            return $result;
        }

        $firstname      = trim((string) ($studentRow['firstname'] ?? ''));
        $lastname       = trim((string) ($studentRow['lastname'] ?? ''));
        $email          = trim((string) ($studentRow['emailaddress'] ?? ''));
        $studentNumber  = trim((string) ($studentRow['student_number'] ?? ''));
        $portalStatus   = strtolower(trim((string) ($studentRow['portal_status'] ?? '')));

        if ($portalStatus === 'activated') {
            $result['already_activated'] = true;
            return $result;
        }

        $accountRow = null;

        if ($studentNumber !== '') {
            $acctStmt = $conn->prepare('SELECT id, email, student_number, firstname, lastname FROM student_accounts WHERE student_number = ? LIMIT 1');
            if ($acctStmt) {
                $acctStmt->bind_param('s', $studentNumber);
                if ($acctStmt->execute()) {
                    $accountRow = $acctStmt->get_result()->fetch_assoc();
                }
                $acctStmt->close();
            }
        }

        if (!$accountRow && $email !== '') {
            $acctStmtEmail = $conn->prepare('SELECT id, email, student_number, firstname, lastname FROM student_accounts WHERE email = ? LIMIT 1');
            if ($acctStmtEmail) {
                $acctStmtEmail->bind_param('s', $email);
                if ($acctStmtEmail->execute()) {
                    $accountRow = $acctStmtEmail->get_result()->fetch_assoc();
                }
                $acctStmtEmail->close();
            }
        }

        $result['account_existed'] = $accountRow !== null;
        $accountId = $accountRow['id'] ?? null;

        if (!$accountRow) {
            $insert = $conn->prepare('
                INSERT INTO student_accounts (student_number, firstname, lastname, email, is_first_login)
                VALUES (?, ?, ?, ?, 1)
            ');
            if ($insert) {
                $studentNumberParam = $studentNumber !== '' ? $studentNumber : null;
                $firstnameParam = $firstname !== '' ? $firstname : null;
                $lastnameParam = $lastname !== '' ? $lastname : null;
                $emailParam = $email !== '' ? $email : null;
                $insert->bind_param('ssss', $studentNumberParam, $firstnameParam, $lastnameParam, $emailParam);
                if ($insert->execute()) {
                    $result['account_created'] = true;
                    $accountId = (int) $insert->insert_id;
                } else {
                    $result['errors'][] = 'account_insert_failed';
                }
                $insert->close();
            } else {
                $result['errors'][] = 'account_insert_prepare_failed';
            }
        } elseif ($accountId !== null) {
            $updates = [];
            $types = '';
            $values = [];

            $existingNumber = trim((string) ($accountRow['student_number'] ?? ''));
            $existingEmail = trim((string) ($accountRow['email'] ?? ''));
            $existingFirstname = trim((string) ($accountRow['firstname'] ?? ''));
            $existingLastname = trim((string) ($accountRow['lastname'] ?? ''));

            if ($studentNumber !== '' && $existingNumber === '') {
                $updates[] = 'student_number = ?';
                $values[] = $studentNumber;
                $types .= 's';
            }
            if ($email !== '' && $existingEmail === '') {
                $updates[] = 'email = ?';
                $values[] = $email;
                $types .= 's';
            }
            if ($firstname !== '' && $existingFirstname === '') {
                $updates[] = 'firstname = ?';
                $values[] = $firstname;
                $types .= 's';
            }
            if ($lastname !== '' && $existingLastname === '') {
                $updates[] = 'lastname = ?';
                $values[] = $lastname;
                $types .= 's';
            }

            if (!empty($updates)) {
                $types .= 'i';
                $values[] = $accountId;
                $sql = 'UPDATE student_accounts SET ' . implode(', ', $updates) . ' WHERE id = ?';
                $updateStmt = $conn->prepare($sql);
                if ($updateStmt) {
                    $updateStmt->bind_param($types, ...$values);
                    if ($updateStmt->execute()) {
                        $result['account_updated'] = true;
                    } else {
                        $result['errors'][] = 'account_update_failed';
                    }
                    $updateStmt->close();
                } else {
                    $result['errors'][] = 'account_update_prepare_failed';
                }
            }
        }

        $portalStmt = $conn->prepare("UPDATE students_registration SET portal_status = 'activated' WHERE id = ?");
        if (!$portalStmt) {
            $result['errors'][] = 'portal_update_prepare_failed';
            return $result;
        }
        $portalStmt->bind_param('i', $studentId);
        if (!$portalStmt->execute()) {
            $result['errors'][] = 'portal_update_execute_failed';
            $portalStmt->close();
            return $result;
        }
        $portalStmt->close();

        $result['activation_performed'] = true;

        $studentFullName = trim(($firstname !== '' ? $firstname : '') . ' ' . ($lastname !== '' ? $lastname : ''));
        if ($studentFullName === '') {
            $studentFullName = 'Student #' . $studentId;
        }

        $targetIdentifier = $studentNumber !== '' ? $studentNumber : (string) $studentId;

        transaction_log_record($conn, [
            'category'    => 'portal',
            'action'      => 'student_portal_activated',
            'target_type' => 'student',
            'target_id'   => $targetIdentifier,
            'description' => sprintf('Activated portal access for %s via %s workflow.', $studentFullName, $options['context']),
            'metadata'    => [
                'student_id'      => $studentId,
                'student_number'  => $studentNumber,
                'email'           => $email,
                'account_existed' => $result['account_existed'],
                'account_created' => $result['account_created'],
                'account_updated' => $result['account_updated'],
                'trigger'         => [
                    'type'       => $options['context'],
                    'payment_id' => $options['payment_id'],
                ],
            ],
            'context'     => $options['context'],
        ]);

        if (!$options['send_email']) {
            $result['email_dispatched'] = false;
            return $result;
        }

        $result['email_attempted'] = true;

        $workerPath = __DIR__ . '/../Registrar/email_worker.php';
        $emailDispatched = false;

        $disabledRaw = (string) ini_get('disable_functions');
        $disabledList = array_filter(array_map('trim', explode(',', $disabledRaw)));
        $canUseExec = function_exists('exec') && !in_array('exec', $disabledList, true) && is_file($workerPath);

        if ($canUseExec) {
            $phpPath = getenv('PHP_CLI_PATH') ?: (PHP_BINARY ?: '/usr/bin/php');
            $cmdParts = [
                escapeshellcmd($phpPath),
                escapeshellarg($workerPath),
                escapeshellarg((string) $studentId),
            ];
            $cmd = implode(' ', $cmdParts);
            $execStatus = 0;
            $unusedOutput = [];
            exec($cmd . ' > /dev/null 2>&1', $unusedOutput, $execStatus);
            if ($execStatus === 0) {
                $emailDispatched = true;
            }
        }

        if (!$emailDispatched) {
            if (!function_exists('registrar_email_worker_process')) {
                require_once $workerPath;
            }

            if (function_exists('registrar_email_worker_process')) {
                try {
                    $emailDispatched = registrar_email_worker_process($studentId, $conn);
                } catch (Throwable $emailError) {
                    $result['errors'][] = 'email_dispatch_failed';
                    error_log('[portal_activation] Email worker error for student ' . $studentId . ': ' . $emailError->getMessage());
                    $emailDispatched = false;
                }
            } else {
                $result['errors'][] = 'email_worker_missing';
            }
        }

        $result['email_dispatched'] = $emailDispatched;

        return $result;
    }
}
