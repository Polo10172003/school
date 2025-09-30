<?php

declare(strict_types=1);

require_once __DIR__ . '/section_assignment.php';

/**
 * Cashier dashboard helper and controller-like functions extracted from the main view file
 * to keep the dashboard leaner and avoid repeated declarations.
 */

/**
 * Consume any flash message stored in the session for the cashier dashboard.
 *
 * @return array{message:string, type:string}
 */
function cashier_dashboard_consume_flash(): array
{
    $message = $_SESSION['cashier_flash'] ?? '';
    $type = $_SESSION['cashier_flash_type'] ?? '';

    if ($message !== '') {
        unset($_SESSION['cashier_flash']);
    }
    if ($type !== '') {
        unset($_SESSION['cashier_flash_type']);
    }

    return ['message' => $message, 'type' => $type];
}

/**
 * Canonical map of tuition plan codes to human readable labels.
 *
 * @return array<string,string>
 */
function cashier_dashboard_plan_labels(): array
{
    return [
        'annually'    => 'Annually',
        'cash'        => 'Cash',
        'semi_annual' => 'Semi-Annual',
        'quarterly'   => 'Quarterly',
        'monthly'     => 'Monthly',
    ];
}

function cashier_dashboard_pricing_labels(): array
{
    return [
        'regular' => 'Regular Students',
        'fwc'     => 'FWC Member',
        'esc'     => 'ESC / Government Subsidy',
        'other'   => 'Other / Custom',
    ];
}

/**
 * Provide a consistent ordering weight for display rows per plan.
 */
function cashier_dashboard_plan_order(string $plan): int
{
    static $order = [
        'annually'    => 0,
        'cash'        => 1,
        'semi_annual' => 2,
        'quarterly'   => 3,
        'monthly'     => 4,
    ];

    $normalized = strtolower(trim($plan));
    return $order[$normalized] ?? 99;
}

/**
 * Default note templates for each payment plan.
 *
 * @return array<int,string>
 */
function cashier_dashboard_default_plan_notes(string $plan): array
{
    switch (strtolower(trim($plan))) {
        case 'monthly':
            return [
                'Next Payment August 5',
                'Next Payment September 5',
                'Next Payment October 5',
                'Next Payment November 5',
                'Next Payment December 5',
                'Next Payment January 5',
                'Next Payment February 5',
                'Next Payment March 5',
            ];
        case 'quarterly':
            return [
                'Next Payment September 5',
                'Next Payment December 5',
                'Next Payment March 5',
            ];
        case 'semi_annual':
            return ['Next Payment December 5'];
        default:
            return [];
    }
}

/**
 * Check if the given table exists in the active database.
 */
function cashier_dashboard_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

function cashier_dashboard_ensure_plan_selection_table(mysqli $conn): bool
{
    static $ensured = false;
    if ($ensured) {
        return true;
    }

    $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS student_plan_selections (
            id INT NOT NULL AUTO_INCREMENT,
            student_id INT NOT NULL,
            tuition_fee_id INT NOT NULL,
            plan_type VARCHAR(32) NOT NULL,
            school_year VARCHAR(32) NOT NULL,
            grade_level VARCHAR(64) NOT NULL,
            pricing_category VARCHAR(50) NOT NULL,
            student_type VARCHAR(50) NOT NULL DEFAULT 'all',
            selected_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_student_fee (student_id, tuition_fee_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL;

    if (!$conn->query($sql)) {
        return false;
    }

    $ensured = true;

    return true;
}

function cashier_dashboard_fetch_selected_plan(mysqli $conn, $studentId, int $tuitionFeeId): ?array
{
    $studentIdInt = (int) $studentId;

    if ($studentIdInt <= 0 || $tuitionFeeId <= 0) {
        return null;
    }

    if (!cashier_dashboard_ensure_plan_selection_table($conn)) {
        return null;
    }

    $stmt = $conn->prepare('SELECT plan_type, pricing_category FROM student_plan_selections WHERE student_id = ? AND tuition_fee_id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ii', $studentIdInt, $tuitionFeeId);
    $stmt->execute();
    $stmt->bind_result($planType, $pricingCategory);
    $found = $stmt->fetch();
    $stmt->close();

    if (!$found) {
        return null;
    }

    return [
        'plan_type' => strtolower((string) $planType),
        'pricing_category' => strtolower((string) ($pricingCategory ?? '')),
    ];
}

function cashier_dashboard_save_plan_selection(mysqli $conn, $studentId, int $tuitionFeeId, string $planType, array $context): void
{
    $studentIdInt = (int) $studentId;

    if ($studentIdInt <= 0 || $tuitionFeeId <= 0 || $planType === '') {
        return;
    }

    if (!cashier_dashboard_ensure_plan_selection_table($conn)) {
        return;
    }

    $planType = strtolower($planType);
    $schoolYear = isset($context['school_year']) ? trim((string) $context['school_year']) : '';
    $gradeLevel = isset($context['grade_level']) ? trim((string) $context['grade_level']) : '';
    $pricingCategory = isset($context['pricing_category']) ? trim((string) $context['pricing_category']) : '';
    $studentType = isset($context['student_type']) ? trim((string) $context['student_type']) : '';

    $stmt = $conn->prepare(
        'INSERT INTO student_plan_selections (student_id, tuition_fee_id, plan_type, school_year, grade_level, pricing_category, student_type)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE plan_type = VALUES(plan_type), school_year = VALUES(school_year), grade_level = VALUES(grade_level), pricing_category = VALUES(pricing_category), student_type = VALUES(student_type)'
    );
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('iisssss', $studentIdInt, $tuitionFeeId, $planType, $schoolYear, $gradeLevel, $pricingCategory, $studentType);
    $stmt->execute();
    $stmt->close();
}

/**
 * Normalise user-supplied amount text into a float.
 */
function cashier_dashboard_parse_amount(string $input): ?float
{
    $sanitised = str_replace(['₱', ',', ' '], '', trim($input));
    if ($sanitised === '') {
        return null;
    }
    if (!is_numeric($sanitised)) {
        return null;
    }

    return round((float) $sanitised, 2);
}

/**
 * Convert textarea input into an array of payment breakdown rows.
 * Each line accepts "amount|note" or just "amount".
 *
 * @return array<int,array{amount:float,note:string}>
 */
function cashier_dashboard_parse_next_payments(string $raw): array
{
    $lines = preg_split('/\r\n|\r|\n/', trim($raw));
    if (!$lines) {
        return [];
    }

    $entries = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }

        $parts = explode('|', $line, 2);
        $amount = cashier_dashboard_parse_amount($parts[0]);
        if ($amount === null) {
            continue;
        }

        $note = isset($parts[1]) ? trim((string) $parts[1]) : '';
        $entries[] = ['amount' => $amount, 'note' => $note];
    }

    return $entries;
}

/**
 * Locate an existing tuition fee package or create a new record if missing.
 *
 * @return int|null The package identifier or null on failure.
 */
function cashier_dashboard_upsert_fee_package(
    mysqli $conn,
    string $school_year,
    string $student_type,
    string $pricing_category,
    string $grade_level,
    float $entrance_fee,
    float $miscellaneous_fee,
    float $tuition_fee,
    float $monthly_payment,
    float $total_upon_enrollment
): ?int {
    $select = $conn->prepare('
        SELECT id
        FROM tuition_fees
        WHERE school_year = ?
          AND student_type = ?
          AND pricing_category = ?
          AND grade_level = ?
        LIMIT 1
    ');
    if (!$select) {
        return null;
    }
    $select->bind_param('ssss', $school_year, $student_type, $pricing_category, $grade_level);
    $select->execute();
    $select->bind_result($existingId);
    $found = $select->fetch();
    $select->close();

    if ($found) {
        $update = $conn->prepare('
            UPDATE tuition_fees
               SET entrance_fee = ?,
                   miscellaneous_fee = ?,
                   tuition_fee = ?,
                   monthly_payment = ?,
                   total_upon_enrollment = ?,
                   updated_at = CURRENT_TIMESTAMP
             WHERE id = ?
        ');
        if (!$update) {
            return null;
        }
        $update->bind_param('dddddi', $entrance_fee, $miscellaneous_fee, $tuition_fee, $monthly_payment, $total_upon_enrollment, $existingId);
        $update->execute();
        $update->close();

        return (int) $existingId;
    }

    $insert = $conn->prepare('
        INSERT INTO tuition_fees
            (school_year, student_type, pricing_category, grade_level, entrance_fee, miscellaneous_fee, tuition_fee, monthly_payment, total_upon_enrollment)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    if (!$insert) {
        return null;
    }
    $insert->bind_param('ssssddddd', $school_year, $student_type, $pricing_category, $grade_level, $entrance_fee, $miscellaneous_fee, $tuition_fee, $monthly_payment, $total_upon_enrollment);
    $ok = $insert->execute();
    $newId = $ok ? (int) $insert->insert_id : null;
    $insert->close();

    return $newId;
}

/**
 * Retrieve all tuition fee packages and their plan breakdowns for dashboard listing.
 *
 * @return array<int,array<string,mixed>>
 */
function cashier_dashboard_fetch_tuition_packages(mysqli $conn): array
{
    $packages = [];

    $result = $conn->query('SELECT * FROM tuition_fees ORDER BY school_year DESC, grade_level ASC');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['plans'] = [];
            $packages[(int) $row['id']] = $row;
        }
        $result->close();
    }

    if (!$packages) {
        return [];
    }

    $ids = implode(',', array_map('intval', array_keys($packages)));
    if ($ids === '') {
        return array_values($packages);
    }

    if (!cashier_dashboard_table_exists($conn, 'tuition_fee_plans')) {
        return array_values($packages);
    }

        $plansSql = "SELECT * FROM tuition_fee_plans WHERE tuition_fee_id IN ($ids) ORDER BY tuition_fee_id ASC, display_order ASC, plan_type ASC";
    $planResult = $conn->query($plansSql);
    if ($planResult) {
        while ($plan = $planResult->fetch_assoc()) {
            $feeId = (int) $plan['tuition_fee_id'];
            if (!isset($packages[$feeId])) {
                continue;
            }
            $plan['decoded_breakdown'] = [];
            if (!empty($plan['next_payment_breakdown'])) {
                $decoded = json_decode($plan['next_payment_breakdown'], true);
                if (is_array($decoded)) {
                    $plan['decoded_breakdown'] = $decoded;
                }
            }
            $plan['decoded_base'] = [];
            if (!empty($plan['base_snapshot'])) {
                $baseDecoded = json_decode($plan['base_snapshot'], true);
                if (is_array($baseDecoded)) {
                    $plan['decoded_base'] = $baseDecoded;
                }
            }
            $packages[$feeId]['plans'][] = $plan;
        }
        $planResult->close();
    }

    return array_values($packages);
}

/**
 * Validate payment to prevent duplicates and ensure proper sequencing.
 *
 * @return array{valid:bool, message:string, suggested_amount:float|null}
 */
function cashier_dashboard_validate_payment(mysqli $conn, $studentIdRaw, float $amount, string $paymentType, ?string $pricingCategory = null): array
{
    $studentId = (int) $studentIdRaw;

    if ($studentId <= 0) {
        return ['valid' => false, 'message' => 'Invalid student identifier.', 'suggested_amount' => null];
    }

    if ($amount <= 0) {
        return ['valid' => false, 'message' => 'Amount must be greater than zero.', 'suggested_amount' => null];
    }

    $stmt = $conn->prepare('SELECT id FROM students_registration WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    if (!$exists) {
        return ['valid' => false, 'message' => 'Student not found.', 'suggested_amount' => null];
    }

    return ['valid' => true, 'message' => '', 'suggested_amount' => null];
}

/**
 * Handle the onsite payment submission form.
 *
 * @return string|null Returns the redirect URL when a submission occurs, otherwise null.
 */
function cashier_dashboard_handle_payment_submission(mysqli $conn): ?string
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['student_id'])) {
        return null;
    }

    // student_id in student_payments is VARCHAR
$student_id = (int) ($_POST['student_id'] ?? 0);
    $student_id_str = trim((string) ($_POST['student_id'] ?? ''));
    if ($student_id_str === '' && $student_id > 0) {
        $student_id_str = (string) $student_id;
    }
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_mode = $_POST['payment_mode'] ?? ($_POST['payment_type'] ?? 'Cash');
    $payment_type = $payment_mode ?: 'Cash';
    $payment_status = $_POST['payment_status'] ?? 'paid';
    $payment_date = date('Y-m-d');
    $or_number = isset($_POST['or_number']) ? trim((string) $_POST['or_number']) : null;
    $reference_number = isset($_POST['reference_number']) ? trim((string) $_POST['reference_number']) : null;
    $posted_plan = isset($_POST['payment_plan']) ? strtolower(trim((string) $_POST['payment_plan'])) : '';

    error_log('[cashier] submission student=' . $student_id_str . ' amount=' . $amount . ' type=' . $payment_type . ' plan=' . $posted_plan);

    $plan_context = [
        'tuition_fee_id'    => (int) ($_POST['tuition_fee_id'] ?? 0),
        'school_year'       => trim((string) ($_POST['plan_school_year'] ?? '')),
        'grade_level'       => trim((string) ($_POST['plan_grade_level'] ?? '')),
        'pricing_category'  => strtolower(trim((string) ($_POST['plan_pricing_category'] ?? ''))),
        'student_type'      => trim((string) ($_POST['plan_student_type'] ?? '')),
    ];

    $pricing_variant_post = isset($_POST['pricing_variant']) ? strtolower(trim((string) $_POST['pricing_variant'])) : '';
    if ($pricing_variant_post !== '') {
        $plan_context['pricing_category'] = $pricing_variant_post;
    }
    if ($plan_context['pricing_category'] === '') {
        $plan_context['pricing_category'] = 'regular';
    }

    if ($or_number === '') $or_number = null;
    if ($reference_number === '') $reference_number = null;

    $message = '';
    $saveSuccess = false;

    // Validation
    if (strcasecmp($payment_type, 'Cash') === 0 && ($or_number === null)) {
        error_log('[cashier] validation failed: missing OR number');
        $message = 'Official receipt number is required for cash payments.';
    } elseif (strcasecmp($payment_type, 'Cash') !== 0 && ($reference_number === null)) {
        error_log('[cashier] validation failed: missing reference number');
        $message = 'Reference number is required for non-cash payments.';
    } else {
        $validation = cashier_dashboard_validate_payment($conn, $student_id, $amount, $payment_type, $plan_context['pricing_category']);
        if (!$validation['valid']) {
            error_log('[cashier] amount validation failed: ' . $validation['message']);
            $message = $validation['message'];
            if ($validation['suggested_amount'] !== null) {
                $message .= ' Suggested amount: ₱' . number_format($validation['suggested_amount'], 2);
            }
        }
    }
    if ($message === '') {
        // Lookup student info
        $stud = $conn->prepare('SELECT firstname, lastname, student_number, emailaddress, year FROM students_registration WHERE id = ?');
        $stud->bind_param('i', $student_id); // id in students_registration is INT
        $stud->execute();
        $stud->bind_result($firstname, $lastname, $student_number, $email, $current_grade_level);
        $stud->fetch();
        $stud->close();
        error_log('[cashier] student lookup firstname=' . ($firstname ?? 'null') . ' lastname=' . ($lastname ?? 'null') . ' student_number=' . ($student_number ?? 'null'));

        $grade_level = $plan_context['grade_level'] !== '' ? $plan_context['grade_level'] : ($current_grade_level ?? null);
        $school_year = $plan_context['school_year'] !== '' ? $plan_context['school_year'] : null;

        // Generate student number if missing
        $new_number = null;
        if (empty($student_number)) {
            do {
                $new_number = 'ESR-' . str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $checkNum = $conn->prepare('SELECT id FROM students_registration WHERE student_number = ? LIMIT 1');
                $checkNum->bind_param('s', $new_number);
                $checkNum->execute();
                $checkNum->store_result();
                $exists = $checkNum->num_rows > 0;
                $checkNum->close();
            } while ($exists);

            $updNum = $conn->prepare('UPDATE students_registration SET student_number = ? WHERE id = ?');
            $updNum->bind_param('ss', $new_number, $student_id);
            $updNum->execute();
            $updNum->close();

            $student_number = $new_number;
        }

        // Check pending payments
        $check = $conn->prepare('
            SELECT id 
            FROM student_payments 
            WHERE student_id = ? AND payment_status = "pending"
            ORDER BY created_at DESC LIMIT 1
        ');
        $check->bind_param('s', $student_id_str); // varchar
        $check->execute();
        $check->bind_result($pending_payment_id);
        $check->fetch();
        $check->close();

        error_log('[cashier] pending payment id = ' . ($pending_payment_id ?? 'none'));

        $recordedPaymentId = null;

        if ($pending_payment_id) {
            if (strcasecmp($payment_type, 'Cash') === 0) {
                $updPay = $conn->prepare('
                    UPDATE student_payments 
                    SET payment_type = ?, payment_status = "paid", or_number = ?, payment_date = ?,
                        grade_level = COALESCE(?, grade_level),
                        school_year = COALESCE(?, school_year)
                    WHERE id = ?
                ');
                $updPay->bind_param('sssssi', $payment_type, $or_number, $payment_date, $grade_level, $school_year, $pending_payment_id);
            } else {
                $updPay = $conn->prepare('
                    UPDATE student_payments 
                    SET payment_type = ?, payment_status = "paid", reference_number = ?, payment_date = ?,
                        grade_level = COALESCE(?, grade_level),
                        school_year = COALESCE(?, school_year)
                    WHERE id = ?
                ');
                $updPay->bind_param('sssssi', $payment_type, $reference_number, $payment_date, $grade_level, $school_year, $pending_payment_id);
            }
            $updPay->execute();
            $updPay->close();
            $saveSuccess = true;
            $message = 'Pending payment updated to Paid. Student enrolled.';
            $recordedPaymentId = (int) $pending_payment_id;
        } else {
            $ins = $conn->prepare('
                INSERT INTO student_payments
                    (student_id, grade_level, school_year, firstname, lastname, payment_type, amount, payment_status, payment_date, or_number, reference_number, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, NOW())
            ');
            $ins->bind_param('ssssssdsss', $student_id_str, $grade_level, $school_year, $firstname, $lastname, $payment_type, $amount, $payment_status, $or_number, $reference_number);

            if ($ins->execute()) {
                $recordedPaymentId = (int) $ins->insert_id;
                error_log('[cashier] inserted payment id=' . $recordedPaymentId);
                $saveSuccess = true;
                $message = ucfirst($payment_type) . ' payment recorded successfully. Student enrolled.';
            } else {
                error_log('[cashier] insert error: ' . $conn->error);
                $message = 'Error: ' . $conn->error;
            }
            $ins->close();
        }

        // ✅ Always update student enrollment status
        if ($saveSuccess) {
            error_log('[cashier] updating enrollment status for student ' . $student_id);
            $upd = $conn->prepare("UPDATE students_registration SET enrollment_status = 'enrolled' WHERE id = ?");
            $upd->bind_param('i', $student_id);
            $upd->execute();
            $upd->close();

            // ✅ Always save plan selection too
            if ($posted_plan !== '' && isset(cashier_dashboard_plan_labels()[$posted_plan])) {
                error_log('[cashier] saving plan selection fee=' . $plan_context['tuition_fee_id'] . ' plan=' . $posted_plan);
                cashier_dashboard_save_plan_selection(
                    $conn,
                    $student_id,
                    $plan_context['tuition_fee_id'],
                    $posted_plan,
                    $plan_context
                );
            }

            cashier_assign_section_if_needed($conn, (int) $student_id);

            if (isset($recordedPaymentId)) {
                $php_path = '/Applications/XAMPP/bin/php';
                $worker = __DIR__ . '/email_worker.php';
                $cmd = escapeshellcmd($php_path) . ' ' . escapeshellarg($worker)
                    . ' ' . escapeshellarg((string) $student_id)
                    . ' ' . escapeshellarg($payment_type)
                    . ' ' . escapeshellarg((string) $amount)
                    . ' ' . escapeshellarg($payment_status);
                exec($cmd . ' > /dev/null 2>&1 &');
                error_log('[cashier] queued email worker for student ' . $student_id . ' payment ' . $recordedPaymentId);
            }
        }
    }

    if ($message !== '') {
        error_log('[cashier] final flash message: ' . $message . ' success=' . ($saveSuccess ? 'yes' : 'no'));
        $_SESSION['cashier_flash'] = $message;
        $_SESSION['cashier_flash_type'] = $saveSuccess ? 'success' : 'error';
    }

    $redirectUrl = 'cashier_dashboard.php';
    $lastSearch = trim($_POST['search_name'] ?? '');
    if ($lastSearch !== '') {
        $redirectUrl .= '?search_name=' . urlencode($lastSearch) . '#record';
    } else {
        $redirectUrl .= '#record';
    }

    return $redirectUrl;
}

/**
 * Handle the tuition fee management form submission.
 *
 * @return string|null Returns the redirect URL on submission, otherwise null.
 */
function cashier_dashboard_handle_tuition_fee_form(mysqli $conn): ?string
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['tuition_fee_form'])) {
        return null;
    }

    $school_year = trim((string) ($_POST['school_year'] ?? ''));
    $student_type = 'all';
    $pricing_category = strtolower(trim((string) ($_POST['pricing_category'] ?? 'regular')));
    $grade_level = trim((string) ($_POST['year'] ?? ''));
    $plan_type_raw = strtolower(trim((string) ($_POST['payment_schedule'] ?? '')));
    $plan_type = str_replace('-', '_', $plan_type_raw);
    $plan_labels = cashier_dashboard_plan_labels();

    $entrance_fee = (float) ($_POST['entrance_fee'] ?? 0);
    $miscellaneous_fee = (float) ($_POST['miscellaneous_fee'] ?? 0);
    $tuition_fee = (float) ($_POST['tuition_fee'] ?? 0);
    $due_upon_enrollment = (float) ($_POST['total_upon_enrollment'] ?? 0);
    $plan_notes = trim((string) ($_POST['plan_notes'] ?? ''));
    $next_payments_raw = (string) ($_POST['next_payments'] ?? '');
    $next_payments = cashier_dashboard_parse_next_payments($next_payments_raw);

    $autoNotes = cashier_dashboard_default_plan_notes($plan_type);
    if (!empty($autoNotes)) {
        if (count($next_payments) === 1 && trim($next_payments[0]['note']) === '') {
            $amountSingle = (float) $next_payments[0]['amount'];
            $next_payments = array_map(static function (string $note) use ($amountSingle): array {
                return ['amount' => $amountSingle, 'note' => $note];
            }, $autoNotes);
        } elseif (!empty($next_payments)) {
            foreach ($next_payments as $idx => &$entry) {
                $entryNote = isset($entry['note']) ? trim((string) $entry['note']) : '';
                if ($entryNote === '' && isset($autoNotes[$idx])) {
                    $entry['note'] = $autoNotes[$idx];
                }
            }
            unset($entry);
        }
    }

    $recurringAmount = null;
    foreach ($next_payments as $entry) {
        $amount = isset($entry['amount']) ? (float) $entry['amount'] : 0.0;
        if ($amount > 0.009) {
            $recurringAmount = $amount;
            break;
        }
    }

    if ($recurringAmount !== null) {
        foreach ($next_payments as &$entry) {
            $amount = isset($entry['amount']) ? (float) $entry['amount'] : 0.0;
            if ($amount <= 0.009) {
                $entry['amount'] = $recurringAmount;
            }
        }
        unset($entry);
    }

    $requiredFields = [$school_year, $student_type, $grade_level, $plan_type];
    $hasEmpty = false;
    foreach ($requiredFields as $value) {
        if ($value === '') {
            $hasEmpty = true;
            break;
        }
    }

    if ($hasEmpty || !isset($plan_labels[$plan_type])) {
        $_SESSION['cashier_flash'] = 'Please complete all required tuition fields and choose a valid payment plan.';
        $_SESSION['cashier_flash_type'] = 'error';
        return 'cashier_dashboard.php#fees';
    }

    if ($entrance_fee < 0 || $miscellaneous_fee < 0 || $tuition_fee < 0 || $due_upon_enrollment < 0) {
        $_SESSION['cashier_flash'] = 'Fee amounts cannot be negative.';
        $_SESSION['cashier_flash_type'] = 'error';
        return 'cashier_dashboard.php#fees';
    }

    $package_total = $entrance_fee + $miscellaneous_fee + $tuition_fee;
    $monthly_payment = 0.0;
    if ($plan_type === 'monthly') {
        if (!empty($next_payments)) {
            $monthly_payment = (float) $next_payments[0]['amount'];
        } elseif ($due_upon_enrollment > ($entrance_fee + $miscellaneous_fee)) {
            $monthly_payment = $due_upon_enrollment - ($entrance_fee + $miscellaneous_fee);
        }
    }

    $next_payments_json = $next_payments ? json_encode($next_payments, JSON_THROW_ON_ERROR) : null;

    $display_order = cashier_dashboard_plan_order($plan_type);
    $base_snapshot = null;
    try {
        $base_snapshot = json_encode([
            'entrance_fee' => $entrance_fee,
            'miscellaneous_fee' => $miscellaneous_fee,
            'tuition_fee' => $tuition_fee,
            'total_program_cost' => $package_total,
        ], JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        $base_snapshot = null;
    }

    $conn->begin_transaction();

    try {
        $packageId = cashier_dashboard_upsert_fee_package(
            $conn,
            $school_year,
            $student_type,
            $pricing_category,
            $grade_level,
            $entrance_fee,
            $miscellaneous_fee,
            $tuition_fee,
            $monthly_payment,
            $package_total
        );

        if (!$packageId) {
            throw new RuntimeException('Unable to save base tuition package.');
        }

        $stmt = $conn->prepare('
            INSERT INTO tuition_fee_plans (tuition_fee_id, plan_type, due_upon_enrollment, next_payment_breakdown, notes, base_snapshot, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                due_upon_enrollment = VALUES(due_upon_enrollment),
                next_payment_breakdown = VALUES(next_payment_breakdown),
                notes = VALUES(notes),
                base_snapshot = VALUES(base_snapshot),
                display_order = VALUES(display_order),
                updated_at = CURRENT_TIMESTAMP
        ');

        if (!$stmt) {
            throw new RuntimeException('Failed to prepare plan statement.');
        }

        $stmt->bind_param(
            'isdsssi',
            $packageId,
            $plan_type,
            $due_upon_enrollment,
            $next_payments_json,
            $plan_notes,
            $base_snapshot,
            $display_order
        );

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Failed to save tuition plan details.');
        }
        $stmt->close();

        $conn->commit();

        $_SESSION['cashier_flash'] = 'Tuition plan saved successfully.';
        $_SESSION['cashier_flash_type'] = 'success';
    } catch (Throwable $e) {
        $conn->rollback();
        $_SESSION['cashier_flash'] = 'Unable to save tuition details: ' . $e->getMessage();
        $_SESSION['cashier_flash_type'] = 'error';
    }

    return 'cashier_dashboard.php#fees';
}

function cashier_previous_grade_label(string $current): ?string
{
    static $map = [
        'preprime12' => 'Preschool',
        'preprime1'  => 'Preschool',
        'preprime2'  => 'Pre-Prime 1',
        'kindergarten' => 'Pre-Prime 2',
        'kinder1'      => 'Pre-Prime 2',
        'kinder2'      => 'Kindergarten',
        'grade1'   => 'Kindergarten',
        'grade2'   => 'Grade 1',
        'grade3'   => 'Grade 2',
        'grade4'   => 'Grade 3',
        'grade5'   => 'Grade 4',
        'grade6'   => 'Grade 5',
        'grade7'   => 'Grade 6',
        'grade8'   => 'Grade 7',
        'grade9'   => 'Grade 8',
        'grade10'  => 'Grade 9',
        'grade11'  => 'Grade 10',
        'grade12'  => 'Grade 11',
    ];

    $normalized = cashier_normalize_grade_key($current);
    if ($normalized === '') {
        return null;
    }

    return $map[$normalized] ?? null;
}

function cashier_normalize_grade_key(string $label): string
{
    $normalized = cashier_resolve_grade_token($label);
    return $normalized;
}

function cashier_roman_to_int(string $roman): ?int
{
    static $map = [
        'i' => 1,
        'ii' => 2,
        'iii' => 3,
        'iv' => 4,
        'v' => 5,
        'vi' => 6,
        'vii' => 7,
        'viii' => 8,
        'ix' => 9,
        'x' => 10,
        'xi' => 11,
        'xii' => 12,
    ];

    $roman = strtolower(trim($roman));

    return $map[$roman] ?? null;
}

function cashier_int_to_roman(int $number): ?string
{
    static $map = [
        1 => 'I',
        2 => 'II',
        3 => 'III',
        4 => 'IV',
        5 => 'V',
        6 => 'VI',
        7 => 'VII',
        8 => 'VIII',
        9 => 'IX',
        10 => 'X',
        11 => 'XI',
        12 => 'XII',
    ];

    return $map[$number] ?? null;
}

function cashier_resolve_grade_token(string $label): string
{
    if ($label === '') {
        return '';
    }

    $normalized = strtolower(trim($label));
    $normalized = str_replace(['–', '—'], '-', $normalized);
    $normalized = preg_replace('/\(.+?\)/', '', $normalized);
    $normalized = preg_replace('/[^a-z0-9&\- ]+/', ' ', $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    $normalized = trim($normalized);

    if ($normalized === '') {
        return '';
    }

    if (preg_match('/grade(?:\s*level)?\s*([ivx]{1,4})\b/', $normalized, $match)) {
        $value = cashier_roman_to_int($match[1]);
        if ($value !== null) {
            return 'grade' . $value;
        }
    }
    if (preg_match('/\bgr?\s*([ivx]{1,4})\b/', $normalized, $match)) {
        $value = cashier_roman_to_int($match[1]);
        if ($value !== null) {
            return 'grade' . $value;
        }
    }
    if (preg_match('/\bg([ivx]{1,4})\b/', $normalized, $match)) {
        $value = cashier_roman_to_int($match[1]);
        if ($value !== null) {
            return 'grade' . $value;
        }
    }

    if (preg_match('/pre\s*prime\s*(1\s*&\s*2|1&2|12)/', $normalized)) {
        return 'preprime12';
    }
    if (preg_match('/pre\s*prime\s*2/', $normalized)) {
        return 'preprime2';
    }
    if (preg_match('/pre\s*prime\s*1/', $normalized)) {
        return 'preprime1';
    }
    if (strpos($normalized, 'preschool') !== false || strpos($normalized, 'nursery') !== false) {
        return 'preschool';
    }

    if (preg_match('/kinder(garten)?\s*2/', $normalized)) {
        return 'kinder2';
    }
    if (preg_match('/kinder(garten)?\s*1/', $normalized)) {
        return 'kinder1';
    }
    if (strpos($normalized, 'kinder') !== false || strpos($normalized, 'kindergarten') !== false) {
        return 'kindergarten';
    }

    if (preg_match('/grade\s*(1[0-2]|[1-9])/', $normalized, $match)) {
        return 'grade' . (int) $match[1];
    }
    if (preg_match('/\bgr?\s*(1[0-2]|[1-9])\b/', $normalized, $match)) {
        return 'grade' . (int) $match[1];
    }
    if (preg_match('/\bg(1[0-2]|[1-9])\b/', $normalized, $match)) {
        return 'grade' . (int) $match[1];
    }

    return str_replace([' ', '-', '_'], '', $normalized);
}

/**
 * Provide alternate normalized grade keys for backward compatibility.
 *
 * @return array<int,string>
 */
function cashier_grade_synonyms(string $normalized): array
{
    $normalized = strtolower($normalized);
    $normalized = str_replace([' ', '-', '_'], '', $normalized);

    $synonyms = [$normalized];

    if ($normalized === 'preprime1') {
        $synonyms[] = 'preprime12';
        return array_values(array_unique($synonyms));
    }
    if ($normalized === 'preprime2') {
        $synonyms[] = 'preprime12';
        return array_values(array_unique($synonyms));
    }
    if ($normalized === 'preprime12') {
        $synonyms[] = 'preprime1';
        $synonyms[] = 'preprime2';
        return array_values(array_unique($synonyms));
    }

    static $kindergartenSet = ['kindergarten', 'kinder1', 'kinder2'];
    if (in_array($normalized, $kindergartenSet, true)) {
        return array_values(array_unique(array_merge($synonyms, $kindergartenSet)));
    }

    if (preg_match('/^grade([0-9]{1,2})$/', $normalized, $match)) {
        $num = (int) $match[1];
        $roman = cashier_int_to_roman($num);
        if ($roman !== null) {
            $synonyms[] = 'grade' . strtolower($roman);
        }
        return array_values(array_unique($synonyms));
    }

    if (preg_match('/^grade([ivx]{1,4})$/', $normalized, $match)) {
        $value = cashier_roman_to_int($match[1]);
        if ($value !== null) {
            $synonyms[] = 'grade' . $value;
            $synonyms[] = 'grade' . strtolower($match[1]);
        }
        return array_values(array_unique($synonyms));
    }

    return array_values(array_unique($synonyms));
}

function cashier_fetch_fee(mysqli $conn, string $normalizedGrade, array $typeCandidates, ?string $pricingCategory = null): ?array
{
    $sql = "SELECT * FROM tuition_fees WHERE REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') = ? AND LOWER(student_type) = ?";
    $hasPricing = $pricingCategory !== null && $pricingCategory !== '';
    if ($hasPricing) {
        $sql .= " AND LOWER(pricing_category) = ?";
    }
    $sql .= " ORDER BY school_year DESC LIMIT 1";
    $gradeCandidates = cashier_grade_synonyms($normalizedGrade);
    $pricingLower = $hasPricing ? strtolower(trim((string) $pricingCategory)) : null;

    foreach ($gradeCandidates as $gradeKey) {
        foreach ($typeCandidates as $candidateType) {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                continue;
            }
            if ($hasPricing) {
                $stmt->bind_param('sss', $gradeKey, $candidateType, $pricingLower);
            } else {
                $stmt->bind_param('ss', $gradeKey, $candidateType);
            }
            $stmt->execute();
            $fee = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($fee) {
                $fee['plans'] = cashier_fetch_plans_for_fee($conn, (int) $fee['id']);
                return $fee;
            }
        }
    }
    return null;
}

function cashier_fetch_plans_for_fee(mysqli $conn, int $feeId): array
{
    if (!cashier_dashboard_table_exists($conn, 'tuition_fee_plans')) {
        return [];
    }

    $stmt = $conn->prepare('SELECT plan_type, due_upon_enrollment, next_payment_breakdown, notes, base_snapshot, display_order FROM tuition_fee_plans WHERE tuition_fee_id = ? ORDER BY display_order ASC, plan_type ASC');
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $feeId);
    $stmt->execute();
    $result = $stmt->get_result();

    $labelMap = cashier_dashboard_plan_labels();
    $plans = [];

    while ($row = $result->fetch_assoc()) {
        $planTypeRaw = trim((string) ($row['plan_type'] ?? ''));
        $planType = strtolower($planTypeRaw);
        if ($planType !== '') {
            $planType = str_replace([' ', '-'], '_', $planType);
            $planType = preg_replace('/_{2,}/', '_', $planType);
            $planType = trim($planType, '_');
        }
        $label = $labelMap[$planType] ?? ucwords(str_replace('_', ' ', $planType));

        $entries = [];
        if (!empty($row['next_payment_breakdown'])) {
            $decoded = json_decode($row['next_payment_breakdown'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $entry) {
                    if (!is_array($entry) || !isset($entry['amount'])) {
                        continue;
                    }
                    $amount = (float) $entry['amount'];
                    if ($amount <= 0.0) {
                        continue;
                    }
                    $note = isset($entry['note']) ? trim((string) $entry['note']) : '';
                    $labelEntry = isset($entry['label']) ? trim((string) $entry['label']) : '';
                    $dueDate = isset($entry['due_date']) ? trim((string) $entry['due_date']) : '';
                    $entries[] = [
                        'amount' => $amount,
                        'note' => $note,
                        'label' => $labelEntry,
                        'due_date' => $dueDate,
                    ];
                }
            }
        }

        $baseSnapshot = null;
        if (!empty($row['base_snapshot'])) {
            $decodedSnapshot = json_decode($row['base_snapshot'], true);
            if (is_array($decodedSnapshot)) {
                $baseSnapshot = $decodedSnapshot;
            }
        }

        $plans[] = [
            'plan_type' => $planType,
            'label' => $label,
            'due_upon_enrollment' => (float) ($row['due_upon_enrollment'] ?? 0),
            'entries' => $entries,
            'notes' => trim((string) ($row['notes'] ?? '')),
            'base_snapshot' => $baseSnapshot,
            'display_order' => (int) ($row['display_order'] ?? 0),
        ];
    }

    $stmt->close();

    return $plans;
}
/**
 * Build a unified plan breakdown for a given tuition fee package.
 * Mirrors tuition_fees.php logic but reusable in cashier dashboard.
 *
 * @return array<string,array>
 */
function cashier_dashboard_fetch_student_plans(mysqli $conn, int $feeId): array
{
    $plansData = [];
    $labelMap = cashier_dashboard_plan_labels();

    $stmt = $conn->prepare('SELECT plan_type, due_upon_enrollment, next_payment_breakdown, notes, base_snapshot, display_order 
                            FROM tuition_fee_plans 
                            WHERE tuition_fee_id = ? 
                            ORDER BY display_order ASC, plan_type ASC');
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $feeId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $planKey = strtolower(trim((string)$row['plan_type']));
        $planKey = str_replace([' ', '-'], '_', $planKey);

        $label = $labelMap[$planKey] ?? ucwords(str_replace('_', ' ', $planKey));
        $due   = (float)($row['due_upon_enrollment'] ?? 0);

        // decode breakdown entries
        $entries = [];
        if (!empty($row['next_payment_breakdown'])) {
            $decoded = json_decode($row['next_payment_breakdown'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $entry) {
                    if (!isset($entry['amount'])) continue;
                    $entries[] = [
                        'amount' => (float)$entry['amount'],
                        'note'   => trim((string)($entry['note'] ?? '')),
                    ];
                }
            }
        }

        // decode base snapshot
        $baseSnapshot = [];
        if (!empty($row['base_snapshot'])) {
            $decodedBase = json_decode($row['base_snapshot'], true);
            if (is_array($decodedBase)) {
                $baseSnapshot = $decodedBase;
            }
        }

        $baseEntrance = $baseSnapshot['entrance_fee'] ?? 0.0;
        $baseMisc     = $baseSnapshot['miscellaneous_fee'] ?? 0.0;
        $baseTuition  = $baseSnapshot['tuition_fee'] ?? 0.0;
        $futureTotal  = array_sum(array_column($entries, 'amount'));
        $overallTotal = $due + $futureTotal;

        $plansData[$planKey] = [
            'label'   => $label,
            'due'     => $due,
            'entries' => $entries,
            'notes'   => trim((string)($row['notes'] ?? '')),
            'base'    => [
                'entrance_fee'   => (float)$baseEntrance,
                'miscellaneous_fee' => (float)$baseMisc,
                'tuition_fee'    => (float)$baseTuition,
                'due_total'      => $due,
                'overall_total'  => $overallTotal,
            ],
        ];
    }

    $stmt->close();
    return $plansData;
}

function cashier_dashboard_build_plan_summaries(array $fee, float $currentPaid, float $previousOutstanding): array
{
    if (empty($fee['plans']) || !is_array($fee['plans'])) {
        return [];
    }

    $summaries = [];
    foreach ($fee['plans'] as $plan) {
        $planType = strtolower((string) ($plan['plan_type'] ?? ''));
        if ($planType === '') {
            continue;
        }

        $label = $plan['label'] ?? cashier_dashboard_plan_labels()[$planType] ?? ucwords(str_replace('_', ' ', $planType));
        $dueEntries = [];

        $dueEntries[] = [
            'label' => 'Upon Enrollment',
            'due_date' => 'Upon Enrollment',
            'amount' => max(0.0, (float) ($plan['due_upon_enrollment'] ?? 0)),
            'note' => 'Due upon enrollment',
        ];

        if (!empty($plan['entries']) && is_array($plan['entries'])) {
            foreach ($plan['entries'] as $idx => $entry) {
                $amount = max(0.0, (float) ($entry['amount'] ?? 0));
                if ($amount <= 0.0) {
                    continue;
                }
                $note = isset($entry['note']) ? trim((string) $entry['note']) : '';
                $labelCandidate = isset($entry['label']) ? trim((string) $entry['label']) : '';
                $labelOverride = $labelCandidate !== '' ? $labelCandidate : ($note !== '' ? $note : 'Next Payment ' . ($idx + 1));
                $dueDate = isset($entry['due_date']) ? trim((string) $entry['due_date']) : '';
                $dueEntries[] = [
                    'label' => $labelOverride,
                    'due_date' => $dueDate,
                    'amount' => $amount,
                    'note' => $note,
                ];
            }
        }

        $planTotal = array_sum(array_map(static function (array $row): float {
            return (float) ($row['amount'] ?? 0);
        }, $dueEntries));

        $remainingPaid = $currentPaid;
        $scheduleRows = [];
        $nextDueRow = null;
        $currentOutstanding = 0.0;

        foreach ($dueEntries as $entry) {
            $amountOriginal = (float) ($entry['amount'] ?? 0);
            if ($amountOriginal <= 0.0) {
                continue;
            }

            $applied = 0.0;
            if ($remainingPaid > 0) {
                $applied = min($remainingPaid, $amountOriginal);
                $remainingPaid -= $applied;
            }

            $outstanding = max($amountOriginal - $applied, 0.0);
            if ($outstanding <= 0.0) {
                continue;
            }

            $row = [
                'label' => $entry['label'] ?? 'Payment',
                'due_date' => $entry['due_date'] ?? '',
                'amount_original' => $amountOriginal,
                'amount_outstanding' => $outstanding,
                'note' => $entry['note'] ?? '',
                'is_next' => false,
                'applied' => $applied,

            ];

            if ($nextDueRow === null) {
                $nextDueRow = $row;
            }

            $scheduleRows[] = $row;
            $currentOutstanding += $outstanding;
        }

        if ($nextDueRow !== null) {
            foreach ($scheduleRows as &$outRow) {
                if ($outRow === $nextDueRow) {
                    $outRow['is_next'] = true;
                    break;
                }
            }
            unset($outRow);
            $nextDueRow['is_next'] = true;
        }

        $scheduleMessage = 'Upcoming dues listed below.';
        if (empty($scheduleRows)) {
            $scheduleMessage = 'All dues for this plan are already covered.';
        }

        $summaries[] = [
            'plan_type' => $planType,
            'label' => $label,
            'plan_total' => $planTotal,
            'remaining_current' => $currentOutstanding,
            'remaining_with_previous' => $previousOutstanding + $currentOutstanding,
            'next_due_row' => $nextDueRow,
            'schedule_rows' => $scheduleRows,
            'schedule_message' => $scheduleMessage,
            'notes' => $plan['notes'] ?? '',
            'tuition_fee_id' => (int) $fee['id'],
        ];
    }

    return $summaries;
}

function cashier_determine_plan_total(array $fee, ?string $planType = null): float
{
    $fallback = (float) ($fee['entrance_fee'] ?? 0) + (float) ($fee['miscellaneous_fee'] ?? 0) + (float) ($fee['tuition_fee'] ?? 0);

    if (empty($fee['plans']) || !is_array($fee['plans'])) {
        return $fallback;
    }

    $normalizedPlan = null;
    if ($planType !== null && $planType !== '') {
        $normalizedPlan = strtolower(str_replace([' ', '-'], '_', trim($planType)));
    }

    $summaries = cashier_dashboard_build_plan_summaries($fee, 0.0, 0.0);
    if (empty($summaries)) {
        return $fallback;
    }

    if ($normalizedPlan !== null) {
        foreach ($summaries as $summary) {
            $summaryType = strtolower((string) ($summary['plan_type'] ?? ''));
            if ($summaryType === $normalizedPlan) {
                return (float) ($summary['plan_total'] ?? $fallback);
            }
        }
    }

    return (float) ($summaries[0]['plan_total'] ?? $fallback);
}

function cashier_generate_schedule(array $fee, string $plan, string $start_date = '2025-06-01'): array
{
    $schedule = [];
    $date = new DateTime($start_date);

    $entrance = (float) $fee['entrance_fee'];
    $misc = (float) $fee['miscellaneous_fee'];
    $tuition = (float) $fee['tuition_fee'];
    $annual = $entrance + $misc + $tuition;

    switch (strtolower($plan)) {
        case 'annually':
            $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => $annual];
            break;
        case 'cash':
            $discountedEntrance = $entrance * 0.9;
            $totalCash = $discountedEntrance + $misc + $tuition;
            $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => $totalCash];
            break;
        case 'semi-annually':
            $upon = $entrance + $misc + ($tuition / 2);
            $next = $tuition / 2;
            $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => $upon];
            $date->modify('+6 months');
            $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => $next];
            break;
        case 'quarterly':
            $upon = $entrance + ($misc / 4) + ($tuition / 4);
            $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => $upon];
            for ($i = 1; $i <= 3; $i++) {
                $date->modify('+3 months');
                $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => ($misc / 4) + ($tuition / 4)];
            }
            break;
        case 'monthly':
            $upon = $entrance + ($misc / 12) + ($tuition / 12);
            $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => $upon];
            for ($i = 1; $i <= 11; $i++) {
                $date->modify('+1 month');
                $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => ($misc / 12) + ($tuition / 12)];
            }
            break;
    }

    return $schedule;
}

/**
 * Prepare search results and associated financial snapshots for the dashboard view.
 *
 * @return array{results:array<int,array>, financial:array<string,array>, query:string, type:string, cleared:bool}
 */
function cashier_dashboard_prepare_search(mysqli $conn): array
{
    $search_results = [];
    $search_financial = [];

    $searchQuery = isset($_GET['search_name']) ? trim((string) $_GET['search_name']) : '';
    $searchType = isset($_GET['search_type']) ? strtolower(trim((string) $_GET['search_type'])) : 'all';
    if (!in_array($searchType, ['new', 'old'], true)) {
        $searchType = 'all';
    }

    $clearSearchFlag = isset($_GET['search_cleared']);
    if ($clearSearchFlag) {
        $searchQuery = '';
    }

    $requestedPricingVariant = isset($_GET['pricing_variant']) ? strtolower(trim((string) $_GET['pricing_variant'])) : null;
    $requestedPricingStudent = isset($_GET['pricing_student']) ? (int) $_GET['pricing_student'] : null;

    if ($searchQuery !== '') {
        $search_name_like = "%" . $conn->real_escape_string($searchQuery) . "%";
        $stmt = $conn->prepare('SELECT id, student_number, firstname, lastname, year, section, adviser, student_type, enrollment_status FROM students_registration WHERE lastname LIKE ? ORDER BY lastname');
        $stmt->bind_param('s', $search_name_like);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rowType = strtolower($row['student_type'] ?? '');
            if ($rowType === '') {
                $rowType = 'new';
            }
            if ($searchType !== 'all' && $rowType !== $searchType) {
                continue;
            }
            $search_results[] = $row;
        }
        $stmt->close();
    }

    if (!empty($search_results)) {
        foreach ($search_results as $result) {
            $studentId = (int) $result['id'];
            $snapshot = cashier_dashboard_build_student_financial($conn, $studentId, [
                'student_row' => $result,
                'pricing_variant' => $requestedPricingVariant,
                'pricing_student' => $requestedPricingStudent,
            ]);
            if ($snapshot) {
                $search_financial[$studentId] = $snapshot;
            }
        }
    }


    return [
        'results' => $search_results,
        'financial' => $search_financial,
        'query' => $searchQuery,
        'type' => $searchType,
        'cleared' => $clearSearchFlag,
    ];
}

/**
 * Fetch payment records for the listing section while honoring optional filters from the query string.
 */

function cashier_dashboard_build_student_financial(mysqli $conn, int $studentId, array $options = []): ?array
{
    $studentRow = $options['student_row'] ?? null;
    if (!$studentRow) {
        $stmt = $conn->prepare('SELECT id, student_number, firstname, lastname, year, section, adviser, student_type, enrollment_status FROM students_registration WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $studentRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$studentRow) {
        return null;
    }

    $gradeLevel = $studentRow['year'];
    $studentType = strtolower($studentRow['student_type'] ?? 'new');
    $normalizedGrade = cashier_normalize_grade_key($gradeLevel);
    $typeCandidates = array_values(array_unique([$studentType, 'new', 'old', 'all']));

    $requestedPricingVariant = isset($options['pricing_variant']) ? strtolower(trim((string) $options['pricing_variant'])) : null;
    $requestedPricingStudent = isset($options['pricing_student']) ? (int) $options['pricing_student'] : null;

    $pricingLabels = cashier_dashboard_pricing_labels();
    $availablePricing = [];
    foreach ($pricingLabels as $pricingKey => $_label) {
        $candidateFee = cashier_fetch_fee($conn, $normalizedGrade, $typeCandidates, $pricingKey);
        if ($candidateFee) {
            $key = strtolower((string) ($candidateFee['pricing_category'] ?? $pricingKey));
            $candidateFee['pricing_category'] = $key;
            $availablePricing[$key] = $candidateFee;
        }
    }

    $storedPlanType = null;
    $storedPricingKey = null;
    foreach ($availablePricing as $pricingKey => $feeCandidate) {
        $selectionRow = cashier_dashboard_fetch_selected_plan($conn, $studentId, (int) $feeCandidate['id']);
        if ($selectionRow) {
            $storedPlanType = $selectionRow['plan_type'] ?? null;
            $storedPricingKey = $selectionRow['pricing_category'] ?: $pricingKey;
            break;
        }
    }

    $enrollmentStatus = strtolower($studentRow['enrollment_status'] ?? '');
    $canChoosePricing = ($enrollmentStatus !== 'enrolled') || empty($storedPricingKey);

    $selectedPricing = null;
    if ($canChoosePricing && $requestedPricingStudent && $requestedPricingStudent === $studentId && $requestedPricingVariant && isset($availablePricing[$requestedPricingVariant])) {
        $selectedPricing = $requestedPricingVariant;
    } elseif ($storedPricingKey && isset($availablePricing[$storedPricingKey])) {
        $selectedPricing = $storedPricingKey;
    } elseif (!empty($availablePricing)) {
        $selectedPricing = array_key_first($availablePricing);
    }

    $fee = ($selectedPricing !== null && isset($availablePricing[$selectedPricing])) ? $availablePricing[$selectedPricing] : (empty($availablePricing) ? null : reset($availablePricing));
    if ($fee) {
        $fee['pricing_category'] = strtolower((string) ($selectedPricing ?? $fee['pricing_category'] ?? 'regular'));
        $selectedPricing = $fee['pricing_category'];
    }

    $stored_plan = ($storedPlanType && $storedPricingKey && $selectedPricing === $storedPricingKey) ? $storedPlanType : null;

    $studentIdStr = (string) $studentId;
    $payments_stmt = $conn->prepare('SELECT id, payment_type, amount, payment_status, payment_date, created_at, grade_level, school_year FROM student_payments WHERE student_id = ? ORDER BY created_at DESC');
    $payments_stmt->bind_param('s', $studentIdStr);
    $payments_stmt->execute();
    $payments_res = $payments_stmt->get_result();
    $paid = [];
    $pending = [];
    $paidByGrade = [];
    $pendingByGrade = [];
    $unassignedPaid = [];
    $unassignedPending = [];
    while ($row = $payments_res->fetch_assoc()) {
        $row['amount'] = (float) ($row['amount'] ?? 0);
        if (empty($row['payment_date']) && !empty($row['created_at'])) {
            $row['payment_date'] = substr($row['created_at'], 0, 10);
        }
        $normalizedPaymentGrade = '';
        if (!empty($row['grade_level'])) {
            $normalizedPaymentGrade = cashier_normalize_grade_key((string) $row['grade_level']);
        }
        if (strtolower($row['payment_status']) === 'paid') {
            $paid[] = $row;
            if ($normalizedPaymentGrade !== '') {
                $paidByGrade[$normalizedPaymentGrade][] = $row;
            } else {
                $unassignedPaid[] = $row;
            }
        } else {
            $pending[] = $row;
            if ($normalizedPaymentGrade !== '') {
                $pendingByGrade[$normalizedPaymentGrade][] = $row;
            } else {
                $unassignedPending[] = $row;
            }
        }
    }
    $payments_stmt->close();

    $previous_label = null;
    $previous_fee = null;
    $grade_key = cashier_normalize_grade_key($gradeLevel);
    $no_previous = ($studentType === 'new') || in_array($grade_key, ['preschool','k1','kinder1','k2','kinder2','kindergarten','kg','grade1'], true);
    if (!$no_previous) {
        $previous_label = cashier_previous_grade_label($gradeLevel);
        if ($previous_label) {
            $normalizedPrev = cashier_normalize_grade_key($previous_label);
            $previous_fee = cashier_fetch_fee($conn, $normalizedPrev, $typeCandidates);
        }
    }

    $previous_stored_plan = null;
    if ($previous_fee) {
        $previous_plan_selection = cashier_dashboard_fetch_selected_plan($conn, $studentId, (int) $previous_fee['id']);
        if ($previous_plan_selection) {
            $previous_stored_plan = strtolower((string) ($previous_plan_selection['plan_type'] ?? ''));
        }
    }

    $previous_grade_total = 0.0;
    if ($previous_fee) {
        $previous_grade_total = cashier_determine_plan_total($previous_fee, $previous_stored_plan);
    }
    $current_grade_total = 0.0;
    if ($fee) {
        $current_grade_total = cashier_determine_plan_total($fee, $storedPlanType);
    }
    $sumAmounts = static function (array $rows): float {
        $total = 0.0;
        foreach ($rows as $entry) {
            $total += (float) ($entry['amount'] ?? 0);
        }
        return $total;
    };

    $current_grade_key = $grade_key;
    $previous_grade_key = $previous_label ? cashier_normalize_grade_key($previous_label) : null;

    $paid_current_total = isset($paidByGrade[$current_grade_key]) ? $sumAmounts($paidByGrade[$current_grade_key]) : 0.0;
    $paid_previous_total = ($previous_grade_key && isset($paidByGrade[$previous_grade_key])) ? $sumAmounts($paidByGrade[$previous_grade_key]) : 0.0;
    $paid_unassigned_total = $sumAmounts($unassignedPaid);

    $paid_other_total = 0.0;

    $remaining_previous_need = $previous_grade_total;

    $allocated_previous_from_previous = min($remaining_previous_need, $paid_previous_total);
    $remaining_previous_need -= $allocated_previous_from_previous;

    $allocated_previous_from_unassigned = min($remaining_previous_need, $paid_unassigned_total);
    $remaining_previous_need -= $allocated_previous_from_unassigned;

    $allocated_previous_from_current = min($remaining_previous_need, $paid_current_total);
    $remaining_previous_need -= $allocated_previous_from_current;

    $allocated_previous_from_other = 0.0;

    $previous_paid_applied = $previous_grade_total - max($remaining_previous_need, 0.0);
    $previous_outstanding = max($remaining_previous_need, 0.0);

    $remaining_current_paid = max($paid_current_total - $allocated_previous_from_current, 0.0);
    $remaining_unassigned_total = max($paid_unassigned_total - $allocated_previous_from_unassigned, 0.0);
    $allocated_current_from_unassigned = $remaining_unassigned_total;

    $current_paid_amount = $remaining_current_paid + $allocated_current_from_unassigned;
    $current_paid_remaining = $current_paid_amount;
    $total_paid = $paid_previous_total + $paid_current_total + $paid_unassigned_total + $paid_other_total;

    $plan_tabs = [];
    $plan_options = [];
    $active_plan_key = null;
    $schedule_rows = [];
    $next_due_row = null;
    $remaining_balance = $previous_outstanding;
    $current_year_total = 0.0;
    $plan_label_display = '';
    $schedule_message = 'Cashier will assign a payment plan once one is confirmed.';

    if ($fee) {
        $plansData = cashier_dashboard_fetch_student_plans($conn, (int)$fee['id']);
        if (!empty($plansData)) {
            $plan_options = cashier_dashboard_build_plan_summaries($fee, $current_paid_amount, $previous_outstanding);
            if ($stored_plan && isset($plansData[$stored_plan])) {
                $active_plan_key = $stored_plan;
            }

            foreach ($plansData as $planType => $plan) {
                $entries = $plan['entries'];
                $remainingPaid = $current_paid_amount;
                $sortedPaid = $paid;
                usort($sortedPaid, function($a, $b) {
                    $timeA = $a['created_at'] ?? $a['payment_date'] ?? '';
                    $timeB = $b['created_at'] ?? $b['payment_date'] ?? '';
                    return strcmp($timeA, $timeB);
                });
                $allocatedPayments = [];
                $dueUponEnrollment = (float)($plan['due'] ?? 0);
                $allocatedToEnrollment = 0;
                if ($dueUponEnrollment > 0 && $remainingPaid > 0) {
                    $allocatedToEnrollment = min($remainingPaid, $dueUponEnrollment);
                    $remainingPaid -= $allocatedToEnrollment;
                    $tempRemaining = $allocatedToEnrollment;
                    foreach ($sortedPaid as $payment) {
                        if ($tempRemaining <= 0) break;
                        $paymentAmount = (float)($payment['amount'] ?? 0);
                        if ($paymentAmount > 0) {
                            $allocatedAmount = min($tempRemaining, $paymentAmount);
                            $allocatedPayments[] = [
                                'payment_id' => $payment['id'] ?? null,
                                'amount' => $allocatedAmount,
                                'allocated_to' => 'enrollment',
                                'payment_date' => $payment['payment_date'] ?? $payment['created_at'] ?? '',
                                'payment_type' => $payment['payment_type'] ?? ''
                            ];
                            $tempRemaining -= $allocatedAmount;
                        }
                    }
                }

                $entries_unified = [];
                if ($dueUponEnrollment > 0) {
                    $entries_unified[] = [
                        'label'              => ucfirst($planType) . ' (Enrollment)',
                        'amount'             => $dueUponEnrollment,
                        'amount_original'    => $dueUponEnrollment,
                        'applied'            => $allocatedToEnrollment,
                        'amount_outstanding' => max($dueUponEnrollment - $allocatedToEnrollment, 0),
                        'paid'               => ($allocatedToEnrollment >= $dueUponEnrollment),
                        'note'               => 'Due upon enrollment',
                    ];
                }

                foreach ($entries as $i => $entry) {
                    $entryAmount = (float) ($entry['amount'] ?? 0);
                    $remaining = $entryAmount;
                    if ($remainingPaid > 0 && $entryAmount > 0) {
                        $allocatedToEntry = min($remainingPaid, $entryAmount);
                        $remaining -= $allocatedToEntry;
                        $remainingPaid -= $allocatedToEntry;
                        $tempRemaining = $allocatedToEntry;
                        foreach ($sortedPaid as $payment) {
                            if ($tempRemaining <= 0) break;
                            $paymentAmount = (float) ($payment['amount'] ?? 0);
                            if ($paymentAmount <= 0) continue;
                            $allocatedAmount = min($tempRemaining, $paymentAmount);
                            $allocatedPayments[] = [
                                'payment_id'   => $payment['id'] ?? null,
                                'amount'       => $allocatedAmount,
                                'allocated_to' => 'monthly_' . $i,
                                'payment_date' => $payment['payment_date'] ?? $payment['created_at'] ?? '',
                                'payment_type' => $payment['payment_type'] ?? '',
                            ];
                            $tempRemaining -= $allocatedAmount;
                        }
                    }
                    $entries_unified[] = [
                        'label'              => $entry['label'] ?? ('Monthly'),
                        'amount'             => $entryAmount,
                        'amount_original'    => $entryAmount,
                        'applied'            => $entryAmount - $remaining,
                        'amount_outstanding' => max($remaining, 0),
                        'paid'               => ($remaining <= 0.01),
                        'note'               => $entry['note'] ?? '',
                    ];
                }

                $plan_tabs[] = [
                    'plan_type' => $planType,
                    'label'     => $plan['label'],
                    'due'       => $plan['due'],
                    'entries'   => $entries_unified,
                    'notes'     => $plan['notes'],
                    'base'      => $plan['base'],
                    'summary'   => null,
                ];
            }

            if ($active_plan_key && ($summary = current(array_filter($plan_options, fn($o) => $o['plan_type'] === $active_plan_key)))) {
                $plan_label_display = $summary['label'] ?? '';
                $remaining_balance  = $summary['remaining_with_previous'] ?? $previous_outstanding;
                $current_year_total = $summary['plan_total'] ?? 0.0;
                $schedule_rows      = $summary['schedule_rows'] ?? [];
                $next_due_row       = $summary['next_due_row'] ?? null;
                $schedule_message   = $summary['schedule_message'] ?? 'Upcoming dues listed below.';
            } elseif (!$active_plan_key) {
                $schedule_rows = [];
                $next_due_row = null;
                $plan_label_display = '';
                $schedule_message = 'Cashier will assign a payment plan once one is confirmed.';
            }
        }
    }

    $selectedPricing = $selectedPricing ?? 'regular';

    $pending_total = array_sum(array_map('floatval', array_column($pending, 'amount')));
    $pending_display = [];
    foreach ($pending as $entry) {
        $entry['payment_type_display'] = $entry['payment_type'] ?? 'Pending';
        $pending_display[] = $entry;
    }
    if ($previous_label && $previous_outstanding > 0) {
        $pending_total += $previous_outstanding;
        $placeholder = [
            'payment_type_display' => 'Past Due for ' . $previous_label,
            'amount' => $previous_outstanding,
            'payment_status' => 'Past Due',
            'payment_date' => 'Previous School Year',
            'reference_number' => null,
            'or_number' => null,
            'is_placeholder' => true,
        ];
        array_unshift($pending_display, $placeholder);
    }

    $paid_chronological = $paid;
    usort($paid_chronological, function (array $a, array $b) {
        $timeA = $a['created_at'] ?? $a['payment_date'] ?? '';
        $timeB = $b['created_at'] ?? $b['payment_date'] ?? '';
        return strcmp((string) $timeA, (string) $timeB);
    });

    $remaining_prev_allocation = $previous_paid_applied;
    $remaining_current_allocation = $current_paid_amount;
    $remaining_unassigned_prev = $allocated_previous_from_unassigned;
    $remaining_unassigned_current = $allocated_current_from_unassigned;
    $remaining_prev_from_previous = $allocated_previous_from_previous;
    $remaining_prev_from_current = $allocated_previous_from_current;
    $remaining_prev_from_other = 0.0;
    $paid_history_previous = [];
    $paid_history_current = [];

    foreach ($paid_chronological as $entry) {
        $amount_remaining = (float) ($entry['amount'] ?? 0);
        if ($amount_remaining <= 0) {
            continue;
        }

        $original_amount = $amount_remaining;
        $normalizedPaymentGrade = '';
        if (!empty($entry['grade_level'])) {
            $normalizedPaymentGrade = cashier_normalize_grade_key((string) $entry['grade_level']);
        }

        if (
            $previous_grade_key &&
            $normalizedPaymentGrade === $previous_grade_key &&
            $remaining_prev_allocation > 0
        ) {
            $apply_prev = min($amount_remaining, $remaining_prev_allocation);
            if ($apply_prev > 0) {
                $record = $entry;
                $record['applied_amount'] = $apply_prev;
                $record['source_amount'] = $original_amount;
                $record['applied_to'] = $previous_label;
                $record['is_partial'] = $apply_prev < $original_amount;
                $paid_history_previous[] = $record;
                $remaining_prev_allocation -= $apply_prev;
                $remaining_prev_from_previous = max($remaining_prev_from_previous - $apply_prev, 0.0);
                $amount_remaining -= $apply_prev;
            }
        } elseif (
            $previous_grade_key &&
            $normalizedPaymentGrade === '' &&
            $remaining_unassigned_prev > 0 &&
            $remaining_prev_allocation > 0
        ) {
            $apply_prev = min($amount_remaining, $remaining_unassigned_prev, $remaining_prev_allocation);
            if ($apply_prev > 0) {
                $record = $entry;
                $record['applied_amount'] = $apply_prev;
                $record['source_amount'] = $original_amount;
                $record['applied_to'] = $previous_label;
                $record['is_partial'] = $apply_prev < $original_amount;
                $paid_history_previous[] = $record;
                $remaining_unassigned_prev -= $apply_prev;
                $remaining_prev_allocation -= $apply_prev;
                $amount_remaining -= $apply_prev;
            }
        } elseif (
            $previous_grade_key &&
            $normalizedPaymentGrade === $current_grade_key &&
            $remaining_prev_from_current > 0 &&
            $remaining_prev_allocation > 0
        ) {
            $apply_prev = min($amount_remaining, $remaining_prev_from_current, $remaining_prev_allocation);
            if ($apply_prev > 0) {
                $record = $entry;
                $record['applied_amount'] = $apply_prev;
                $record['source_amount'] = $original_amount;
                $record['applied_to'] = $previous_label;
                $record['is_partial'] = $apply_prev < $original_amount;
                $paid_history_previous[] = $record;
                $remaining_prev_from_current -= $apply_prev;
                $remaining_prev_allocation -= $apply_prev;
                $amount_remaining -= $apply_prev;
            }
        }

        if ($amount_remaining <= 0) {
            continue;
        }

        $apply_current = 0.0;
        $applied_to_label = $studentRow['year'];

        if ($normalizedPaymentGrade === $current_grade_key) {
            $apply_current = min($amount_remaining, $remaining_current_allocation);
        } elseif ($normalizedPaymentGrade === '' && $remaining_unassigned_current > 0) {
            $apply_current = min($amount_remaining, $remaining_unassigned_current, $remaining_current_allocation);
            $remaining_unassigned_current -= $apply_current;
        } elseif ($normalizedPaymentGrade !== '' && $normalizedPaymentGrade !== $previous_grade_key) {
            $applied_to_label = $entry['grade_level'] ?? $studentRow['year'];
            $apply_current = min($amount_remaining, $remaining_current_allocation);
        } elseif ($remaining_current_allocation > 0) {
            $apply_current = min($amount_remaining, $remaining_current_allocation);
        }

        if ($apply_current > 0) {
            $record = $entry;
            $record['applied_amount'] = $apply_current;
            $record['source_amount'] = $original_amount;
            $record['applied_to'] = $applied_to_label;
            $record['is_partial'] = $apply_current < $original_amount;
            $paid_history_current[] = $record;
            $remaining_current_allocation = max($remaining_current_allocation - $apply_current, 0);
        }
    }

    $historySort = static function (&$list) {
        usort($list, function (array $a, array $b) {
            $timeA = $a['created_at'] ?? $a['payment_date'] ?? '';
            $timeB = $b['created_at'] ?? $b['payment_date'] ?? '';
            return strcmp((string) $timeB, (string) $timeA);
        });
    };

    $historySort($paid_history_previous);
    $historySort($paid_history_current);

    $previous_plan_label = null;
    $previous_schedule_rows = [];
    $previous_schedule_message = null;
    if ($previous_fee) {
        $paid_for_previous_plan = min($previous_paid_applied, $previous_grade_total);
        $previous_plan_summaries = cashier_dashboard_build_plan_summaries($previous_fee, $paid_for_previous_plan, 0.0);
        $targetPlan = $previous_stored_plan;
        $selectedPreviousSummary = null;
        if (!empty($previous_plan_summaries)) {
            if (!empty($targetPlan)) {
                foreach ($previous_plan_summaries as $candidate) {
                    $candidateType = strtolower((string) ($candidate['plan_type'] ?? ''));
                    if ($candidateType === $targetPlan) {
                        $selectedPreviousSummary = $candidate;
                        break;
                    }
                }
            }
            if (!$selectedPreviousSummary) {
                $selectedPreviousSummary = $previous_plan_summaries[0];
            }

            if ($selectedPreviousSummary) {
                $previous_plan_label = $selectedPreviousSummary['label'] ?? null;
                $previous_schedule_rows = $selectedPreviousSummary['schedule_rows'] ?? [];
                $previous_schedule_message = $selectedPreviousSummary['schedule_message'] ?? null;
            }
        }

        if (empty($previous_schedule_rows)) {
            $prev_schedule = cashier_generate_schedule($previous_fee, 'quarterly');
            $prev_paid_remaining = $previous_paid_applied;
            foreach ($prev_schedule as $prevDue) {
                $due_original = (float) $prevDue['Amount'];
                $due_outstanding = $due_original;
                if ($prev_paid_remaining > 0) {
                    $deduct = min($prev_paid_remaining, $due_outstanding);
                    $due_outstanding -= $deduct;
                    $prev_paid_remaining -= $deduct;
                }
                $previous_schedule_rows[] = [
                    'due_date' => $prevDue['Due Date'],
                    'amount_original' => $due_original,
                    'amount_outstanding' => $due_outstanding,
                    'row_class' => $due_outstanding > 0 ? 'row-outstanding' : '',
                ];
            }
        }
    }

    if ($previous_plan_label === null && $previous_fee) {
        $previous_plan_label = 'Past Year Summary';
    }
    if ($previous_schedule_message === null) {
        if ($previous_fee) {
            $previous_schedule_message = $previous_outstanding > 0
                ? 'Outstanding balance carried over from ' . $previous_label . '.'
                : 'No outstanding balance for this grade.';
        } else {
            $previous_schedule_message = 'No tuition fee record saved for ' . $previous_label . ', but payments are retained below.';
        }
    }

    $previous_pending_rows = [];
    if ($previous_outstanding > 0) {
        $previous_pending_rows[] = [
            'payment_type_display' => 'Past Due for ' . $previous_label,
            'amount' => $previous_outstanding,
            'payment_status' => 'Past Due',
            'payment_date' => 'Previous School Year',
            'reference_number' => null,
            'or_number' => null,
            'is_placeholder' => true,
        ];
    }

    $current_view = [
        'key' => 'current',
        'label' => $studentRow['year'] . ' (Current)',
        'remaining_balance' => $remaining_balance,
        'total_paid' => $current_paid_amount,
        'pending_total' => $pending_total,
        'plan_label' => $plan_label_display,
        'pricing_variant' => $selectedPricing,
        'next_due_row' => $next_due_row,
        'alert' => $previous_outstanding > 0 ? [
            'grade' => $previous_label,
            'amount' => $previous_outstanding,
        ] : null,
        'schedule_rows' => $schedule_rows,
        'schedule_message' => $schedule_message,
        'pending_rows' => $pending_display,
        'pending_message' => 'No pending payments right now.',
        'history_rows' => $paid_history_current,
        'history_message' => 'No payments recorded yet for this grade.',
        'current_year_total' => $current_year_total,
        'has_previous_outstanding' => $previous_outstanding > 0,
        'previous_grade_label' => $previous_label,
        'previous_outstanding' => $previous_outstanding,
        'is_default' => true,
        'grade_key' => $current_grade_key,
        'plan_options' => $plan_options,
        'plan_tabs' => $plan_tabs,
        'active_plan' => $active_plan_key,
        'stored_plan' => $stored_plan,
        'stored_pricing' => $storedPricingKey,
        'available_pricing' => array_keys($availablePricing),
        'selected_pricing' => $selectedPricing ?? 'regular',
        'pricing_locked' => (!$canChoosePricing && !empty($storedPricingKey)),
        'plan_context' => $fee ? [
            'tuition_fee_id' => (int) $fee['id'],
            'school_year' => (string) $fee['school_year'],
            'grade_level' => (string) $fee['grade_level'],
            'pricing_category' => (string) $fee['pricing_category'],
            'student_type' => (string) $fee['student_type'],
        ] : null,
    ];

    $views = ['current' => $current_view];

    if ($previous_label && ($previous_fee || $previous_outstanding > 0 || !empty($paid_history_previous))) {
        $views['previous'] = [
            'key' => 'previous',
            'label' => 'Previous - ' . $previous_label,
            'remaining_balance' => $previous_outstanding,
            'total_paid' => $previous_paid_applied,
            'pending_total' => $previous_outstanding,
            'plan_label' => $previous_plan_label,
            'pricing_variant' => $storedPricingKey,
            'next_due_row' => null,
            'alert' => null,
            'schedule_rows' => $previous_schedule_rows,
            'schedule_message' => $previous_schedule_message,
            'pending_rows' => $previous_pending_rows,
            'pending_message' => $previous_outstanding > 0 ? 'Pending balance remains from ' . $previous_label . '.' : 'No pending payments for this grade.',
            'history_rows' => $paid_history_previous,
            'history_message' => 'No payments recorded yet for ' . $previous_label . '.',
            'current_year_total' => $previous_grade_total,
            'has_previous_outstanding' => $previous_outstanding > 0,
            'is_default' => false,
            'grade_key' => $previous_grade_key,
        ];
    }

    $current_summary = $current_view;
    $current_summary['views'] = $views;
    $current_summary['default_view_key'] = 'current';
    $current_summary['has_previous_view'] = isset($views['previous']);

    return $current_summary;
}


function cashier_dashboard_fetch_payments(mysqli $conn): mysqli_result
{
    $filter_sql = 'WHERE 1=1';
    if (!empty($_GET['filter_status'])) {
        $filter_status = $conn->real_escape_string((string) $_GET['filter_status']);
        $filter_sql .= " AND sp.payment_status = '$filter_status'";
    }

    $sql = "
        SELECT sp.*, sr.firstname, sr.lastname, sr.middlename
        FROM student_payments sp
        JOIN students_registration sr ON sr.id = sp.student_id
        $filter_sql
        ORDER BY sp.created_at DESC
    ";

    return $conn->query($sql);
}
