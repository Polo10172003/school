<?php
declare(strict_types=1);

require_once __DIR__ . '/transaction_logger.php';

if (!function_exists('ensure_student_portal_activation_calculate_due')) {
    /**
     * Determine if a student has met the due-upon-enrollment threshold required for portal activation.
     *
     * @param mysqli $conn
     * @param int    $studentId
     * @param array  $studentRow
     *
     * @return array Summary of payment totals, fee info, and eligibility.
     */
    function ensure_student_portal_activation_calculate_due(mysqli $conn, int $studentId, array $studentRow): array
    {
        if (!function_exists('cashier_normalize_grade_key')) {
            require_once __DIR__ . '/../Cashier/cashier_dashboard_logic.php';
        }

        $gradeLevelRaw = (string) ($studentRow['year'] ?? '');
        $schoolYearRaw = trim((string) ($studentRow['school_year'] ?? ''));
        $studentTypeRaw = (string) ($studentRow['student_type'] ?? '');

        $currentGradeKey = cashier_normalize_grade_key($gradeLevelRaw);
        $studentTypeNormalized = strtolower(trim($studentTypeRaw));
        if ($studentTypeNormalized === '') {
            $studentTypeNormalized = 'new';
        }

        $typeCandidates = array_values(array_unique([$studentTypeNormalized, 'new', 'old', 'all']));

        $fee = cashier_fetch_fee($conn, $currentGradeKey, $typeCandidates);
        $feeId = $fee ? (int) ($fee['id'] ?? 0) : null;
        $plans = $fee['plans'] ?? [];
        $planSelection = null;
        $selectedPlanType = null;
        $pricingCategory = null;

        if ($feeId) {
            $planSelection = cashier_dashboard_fetch_selected_plan(
                $conn,
                $studentId,
                $feeId,
                $schoolYearRaw !== '' ? $schoolYearRaw : null
            );
            if ($planSelection) {
                $rawPlanType = (string) ($planSelection['plan_type'] ?? '');
                $selectedPlanType = strtolower(str_replace([' ', '-'], '_', $rawPlanType));
                $selectedPlanType = preg_replace('/_{2,}/', '_', $selectedPlanType);
                $selectedPlanType = trim($selectedPlanType, '_');
                $pricingCategory = $planSelection['pricing_category'] ?? null;

                if ($pricingCategory !== null && $pricingCategory !== '') {
                    $feeWithPricing = cashier_fetch_fee($conn, $currentGradeKey, $typeCandidates, $pricingCategory);
                    if ($feeWithPricing) {
                        $fee = $feeWithPricing;
                        $feeId = (int) ($fee['id'] ?? 0);
                        $plans = $fee['plans'] ?? $plans;
                    }
                }
            }
        }

        $dueThreshold = null;
        foreach ($plans as $planRow) {
            $planTypeRaw = (string) ($planRow['plan_type'] ?? '');
            $planType = strtolower(str_replace([' ', '-'], '_', $planTypeRaw));
            $planType = preg_replace('/_{2,}/', '_', $planType);
            $planType = trim($planType, '_');
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

        if ($dueThreshold === null && $fee) {
            $dueThreshold = (float) ($fee['entrance_fee'] ?? 0)
                + (float) ($fee['miscellaneous_fee'] ?? 0)
                + (float) ($fee['tuition_fee'] ?? 0);
        }

        $paymentsStmt = $conn->prepare('
            SELECT amount, grade_level
            FROM student_payments
            WHERE student_id = ?
              AND LOWER(payment_status) IN (\'paid\', \'completed\', \'approved\', \'cleared\')
        ');

        $gradeTotals = [];
        $totalPaidAll = 0.0;
        $paymentsEvaluated = 0;

        if ($paymentsStmt) {
            $paymentsStmt->bind_param('i', $studentId);
            if ($paymentsStmt->execute()) {
                $resultSet = $paymentsStmt->get_result();
                if ($resultSet) {
                    while ($row = $resultSet->fetch_assoc()) {
                        $amount = (float) ($row['amount'] ?? 0);
                        if ($amount <= 0) {
                            continue;
                        }
                        $paymentsEvaluated++;
                        $totalPaidAll += $amount;
                        $paymentGradeKey = cashier_normalize_grade_key((string) ($row['grade_level'] ?? ''));
                        if ($paymentGradeKey === '') {
                            $paymentGradeKey = $currentGradeKey !== '' ? $currentGradeKey : '__general__';
                        }
                        if (!isset($gradeTotals[$paymentGradeKey])) {
                            $gradeTotals[$paymentGradeKey] = ['count' => 0, 'sum' => 0.0];
                        }
                        $gradeTotals[$paymentGradeKey]['count']++;
                        $gradeTotals[$paymentGradeKey]['sum'] += $amount;
                    }
                }
            }
            $paymentsStmt->close();
        }

        $aggregateTotals = static function (array $map, string $gradeKey, float $fallbackSum, int $fallbackCount): array {
            if (!function_exists('cashier_grade_synonyms')) {
                require_once __DIR__ . '/../Cashier/cashier_dashboard_logic.php';
            }

            if ($gradeKey === '') {
                return ['count' => $fallbackCount, 'sum' => $fallbackSum];
            }

            $keys = cashier_grade_synonyms($gradeKey);
            if (!in_array($gradeKey, $keys, true)) {
                $keys[] = $gradeKey;
            }
            $keys = array_values(array_unique(array_filter($keys)));

            $totalCount = 0;
            $totalSum = 0.0;

            foreach ($keys as $key) {
                if (isset($map[$key])) {
                    $totalCount += (int) ($map[$key]['count'] ?? 0);
                    $totalSum += (float) ($map[$key]['sum'] ?? 0);
                }
            }

            if ($totalSum === 0.0 && isset($map['__general__'])) {
                $totalCount += (int) ($map['__general__']['count'] ?? 0);
                $totalSum += (float) ($map['__general__']['sum'] ?? 0);
            }

            if ($totalSum === 0.0 && $fallbackSum > 0) {
                return ['count' => $fallbackCount, 'sum' => $fallbackSum];
            }

            return ['count' => $totalCount, 'sum' => $totalSum];
        };

        $currentTotals = $aggregateTotals($gradeTotals, $currentGradeKey, $totalPaidAll, $paymentsEvaluated);

        $eligibilityBasis = 'due_threshold';
        if ($dueThreshold === null) {
            $eligibilityBasis = $totalPaidAll > 0 ? 'any_payment' : 'no_payments';
        }

        if ($dueThreshold === null) {
            $eligible = $totalPaidAll > 0;
        } else {
            $eligible = $dueThreshold <= 0.0 || $currentTotals['sum'] >= max(0.0, $dueThreshold - 0.01);
        }

        return [
            'grade_key'                  => $currentGradeKey,
            'student_type'               => $studentTypeNormalized,
            'fee_id'                     => $feeId,
            'due_threshold'              => $dueThreshold,
            'total_paid_all'             => $totalPaidAll,
            'total_payments_considered'  => $paymentsEvaluated,
            'grade_paid_amount'          => $currentTotals['sum'],
            'grade_payment_count'        => $currentTotals['count'],
            'eligible'                   => $eligible,
            'eligibility_basis'          => $eligibilityBasis,
            'selected_plan'              => $selectedPlanType,
            'pricing_category'           => $pricingCategory,
        ];
    }
}

if (!function_exists('ensure_student_portal_activation')) {
    /**
     * Ensure that a student's portal account is activated once the due-upon-enrollment balance has been met.
     *
     * @param mysqli $conn
     * @param int    $studentId
     * @param array  $options
     *
     * @return array Summary of the activation attempt.
     */
    function ensure_student_portal_activation(mysqli $conn, int $studentId, array $options = []): array
    {
        $defaults = [
            'context'     => 'system',
            'payment_id'  => null,
            'send_email'  => true,
        ];
        $options = array_merge($defaults, $options);

        $result = [
            'student_found'           => false,
            'portal_status_before'    => null,
            'portal_status_after'     => null,
            'already_activated'       => false,
            'account_existed'         => null,
            'account_created'         => false,
            'account_updated'         => false,
            'activation_performed'    => false,
            'activation_skipped_reason' => null,
            'email_attempted'         => false,
            'email_dispatched'        => null,
            'due_summary'             => null,
            'errors'                  => [],
        ];

        $debugLogPath = __DIR__ . '/../temp/portal_activation_debug.log';
        $debugAppend = static function (string $message, array $context = []) use ($debugLogPath): void {
            $payload = [
                'timestamp' => date('c'),
                'message'   => $message,
                'context'   => $context,
            ];
            @file_put_contents(
                $debugLogPath,
                json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
                FILE_APPEND
            );
        };

        $studentId = max(0, $studentId);
        if ($studentId <= 0) {
            $result['errors'][] = 'invalid_student_id';
            $debugAppend('invalid_student_id', ['student_id' => $studentId]);
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
                enrollment_status,
                year,
                school_year,
                student_type
            FROM students_registration
            WHERE id = ?
            LIMIT 1
        ');
        if (!$studentStmt) {
            $result['errors'][] = 'student_lookup_prepare_failed';
            $debugAppend('student_lookup_prepare_failed', ['student_id' => $studentId, 'error' => $conn->error]);
            return $result;
        }

        $studentStmt->bind_param('i', $studentId);
        if (!$studentStmt->execute()) {
            $result['errors'][] = 'student_lookup_execute_failed';
            $studentStmt->close();
            $debugAppend('student_lookup_execute_failed', ['student_id' => $studentId, 'error' => $studentStmt->error]);
            return $result;
        }

        $studentRow = $studentStmt->get_result()->fetch_assoc();
        $studentStmt->close();

        if (!$studentRow) {
            $result['errors'][] = 'student_not_found';
            $debugAppend('student_not_found', ['student_id' => $studentId]);
            return $result;
        }

        $result['student_found'] = true;

        $firstname        = trim((string) ($studentRow['firstname'] ?? ''));
        $lastname         = trim((string) ($studentRow['lastname'] ?? ''));
        $email            = trim((string) ($studentRow['emailaddress'] ?? ''));
        $studentNumber    = trim((string) ($studentRow['student_number'] ?? ''));
        $portalStatusRaw  = (string) ($studentRow['portal_status'] ?? '');
        $enrollmentStatus = $studentRow['enrollment_status'] ?? null;

        $normalizedPortalStatus = strtolower(trim($portalStatusRaw));
        $result['portal_status_before'] = $portalStatusRaw;
        $result['portal_status_after'] = $portalStatusRaw;

        $dueSummary = ensure_student_portal_activation_calculate_due($conn, $studentId, $studentRow);
        $result['due_summary'] = $dueSummary;

        if ($normalizedPortalStatus === 'activated') {
            $result['already_activated'] = true;
            $result['activation_skipped_reason'] = 'already_activated';
            $result['email_dispatched'] = false;
            $debugAppend('already_activated', ['student_id' => $studentId]);
            return $result;
        }

        if (!$dueSummary['eligible']) {
            $result['activation_skipped_reason'] = 'due_not_met';
            $result['email_dispatched'] = false;
            $debugAppend('due_not_met', ['student_id' => $studentId, 'due_summary' => $dueSummary]);
            return $result;
        }

        $accountRow = null;

        if ($studentNumber !== '') {
            $accountStmt = $conn->prepare('SELECT id, email, student_number, firstname, lastname FROM student_accounts WHERE student_number = ? LIMIT 1');
            if ($accountStmt) {
                $accountStmt->bind_param('s', $studentNumber);
                if ($accountStmt->execute()) {
                    $accountRow = $accountStmt->get_result()->fetch_assoc();
                }
                $accountStmt->close();
            }
        }

        if (!$accountRow && $email !== '') {
            $accountStmtEmail = $conn->prepare('SELECT id, email, student_number, firstname, lastname FROM student_accounts WHERE email = ? LIMIT 1');
            if ($accountStmtEmail) {
                $accountStmtEmail->bind_param('s', $email);
                if ($accountStmtEmail->execute()) {
                    $accountRow = $accountStmtEmail->get_result()->fetch_assoc();
                }
                $accountStmtEmail->close();
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
            $updateFields = [];
            $updateValues = [];
            $types = '';

            $existingEmail = trim((string) ($accountRow['email'] ?? ''));
            $existingNumber = trim((string) ($accountRow['student_number'] ?? ''));
            $existingFirstname = trim((string) ($accountRow['firstname'] ?? ''));
            $existingLastname = trim((string) ($accountRow['lastname'] ?? ''));

            if ($studentNumber !== '' && $existingNumber === '') {
                $updateFields[] = 'student_number = ?';
                $updateValues[] = $studentNumber;
                $types .= 's';
            }

            if ($email !== '' && $existingEmail === '') {
                $updateFields[] = 'email = ?';
                $updateValues[] = $email;
                $types .= 's';
            }

            if ($firstname !== '' && $existingFirstname === '') {
                $updateFields[] = 'firstname = ?';
                $updateValues[] = $firstname;
                $types .= 's';
            }

            if ($lastname !== '' && $existingLastname === '') {
                $updateFields[] = 'lastname = ?';
                $updateValues[] = $lastname;
                $types .= 's';
            }

            if (!empty($updateFields)) {
                $sql = 'UPDATE student_accounts SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
                $types .= 'i';
                $updateValues[] = $accountId;
                $updateStmt = $conn->prepare($sql);
                if ($updateStmt) {
                    $updateStmt->bind_param($types, ...$updateValues);
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
            $debugAppend('portal_update_prepare_failed', ['student_id' => $studentId, 'error' => $conn->error]);
            return $result;
        }

        $portalStmt->bind_param('i', $studentId);
        if (!$portalStmt->execute()) {
            $result['errors'][] = 'portal_update_execute_failed';
            $portalStmt->close();
            $debugAppend('portal_update_execute_failed', ['student_id' => $studentId, 'error' => $portalStmt->error]);
            return $result;
        }
        $portalStmt->close();

        $result['activation_performed'] = true;
        $result['portal_status_after'] = 'activated';

        $studentFullName = trim($firstname . ' ' . $lastname);
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
                'student_id'             => $studentId,
                'student_number'         => $studentNumber,
                'email'                  => $email,
                'enrollment_status'      => $enrollmentStatus,
                'previous_portal_status' => $portalStatusRaw,
                'account_existed'        => $result['account_existed'],
                'account_created'        => $result['account_created'],
                'account_updated'        => $result['account_updated'],
                'due_summary'            => $dueSummary,
                'trigger'                => [
                    'type'       => $options['context'],
                    'payment_id' => $options['payment_id'],
                ],
            ],
            'context'     => $options['context'],
        ]);
        $debugAppend('portal_activated', [
            'student_id'  => $studentId,
            'student_num' => $studentNumber,
            'due_summary' => $dueSummary,
            'payment_id'  => $options['payment_id'],
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
                $debugAppend('email_worker_dispatched_async', ['student_id' => $studentId]);
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
                    $debugAppend('email_dispatch_failed', ['student_id' => $studentId, 'error' => $emailError->getMessage()]);
                    $emailDispatched = false;
                }
            } else {
                $result['errors'][] = 'email_worker_missing';
                $debugAppend('email_worker_missing', ['student_id' => $studentId]);
            }
        }

        $result['email_dispatched'] = $emailDispatched;

        return $result;
    }
}
