<?php
require_once __DIR__ . '/../includes/session.php';

// Auto logout after 15 minutes of inactivity
$timeout_duration = 300; // 900 seconds = 15 minutes

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: student_login.php?timeout=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

include __DIR__ . '/../db_connection.php';
include __DIR__ . '/../Cashier/cashier_dashboard_logic.php';

// Make sure student is logged in
if (!isset($_SESSION['student_number'])) {
    header("Location: student_login.php");
    exit();
}

$student_number = $_SESSION['student_number'];

$sql = "SELECT id, firstname, lastname, year, section, adviser, student_type, gender, emailaddress, schedule_sent_at, academic_status, enrollment_status, school_year
        FROM students_registration 
        WHERE student_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_number);
$stmt->execute();
$stmt->bind_result($student_id, $firstname, $lastname, $year, $section, $adviser, $student_type, $gender, $student_email_portal, $schedule_sent_at, $academic_status, $enrollment_status, $student_school_year);
$stmt->fetch();
$stmt->close();

if (!$student_id) {
    die("Student account not found in students_registration.");
}

$portal_student_name = trim($firstname . ' ' . $lastname);
$header_variant = 'student_portal';

$scheduleSentAt = $schedule_sent_at ?? null;
$academicStatus = (string) ($academic_status ?? '');
$enrollmentStatus = (string) ($enrollment_status ?? '');
$student_school_year = (string) ($student_school_year ?? '');

$studentTypeLower = strtolower(trim((string) $student_type));
$academicStatusLower = strtolower(trim($academicStatus));
$enrollmentStatusLower = strtolower(trim($enrollmentStatus));
$sectionTrimmed = trim((string) ($section ?? ''));
$adviserTrimmed = trim((string) ($adviser ?? ''));
$sectionEmpty = ($sectionTrimmed === '' || strcasecmp($sectionTrimmed, 'To be assigned') === 0 || strcasecmp($sectionTrimmed, 'TBA') === 0);
$adviserEmpty = ($adviserTrimmed === '' || strcasecmp($adviserTrimmed, 'To be assigned') === 0 || strcasecmp($adviserTrimmed, 'TBA') === 0);
$scheduleIsEmpty = ($scheduleSentAt === null || $scheduleSentAt === '' || $scheduleSentAt === '0000-00-00 00:00:00');
$canStartPortalEnrollment = ($studentTypeLower === 'old')
    && in_array($academicStatusLower, ['passed', 'ongoing'], true)
    && ($enrollmentStatusLower !== 'enrolled')
    && $sectionEmpty
    && $adviserEmpty
    && $scheduleIsEmpty;

$portalBasePath = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
if ($scriptName !== '') {
    $marker = '/Portal/student_portal.php';
    $markerPos = stripos($scriptName, $marker);
    if ($markerPos !== false) {
        $assetBase = substr($scriptName, 0, $markerPos + 1);
    } else {
        $assetBase = rtrim(dirname($scriptName), '/') . '/';
    }
    if ($assetBase === '') {
        $assetBase = '/';
    } elseif (substr($assetBase, -1) !== '/') {
        $assetBase .= '/';
    }
} else {
    $assetBase = '/';
}

$portalEnrollmentSessionKey = 'portal_enrollment_ready_' . $student_id;
$portalPlanSessionKey = 'portal_selected_plan_' . $student_id;
$portalPricingSessionKey = 'portal_pricing_variant_' . $student_id;

if (!$canStartPortalEnrollment) {
    unset($_SESSION[$portalEnrollmentSessionKey], $_SESSION[$portalPlanSessionKey], $_SESSION[$portalPricingSessionKey]);
}

$portalEnrollmentReady = !empty($_SESSION[$portalEnrollmentSessionKey]);
$portalSelectedPlan = $_SESSION[$portalPlanSessionKey] ?? null;
$portalSelectedPricing = $_SESSION[$portalPricingSessionKey] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_portal_enrollment']) && $canStartPortalEnrollment) {
        $_SESSION[$portalEnrollmentSessionKey] = true;
        $clearPlans = null;

        if ($student_school_year !== '') {
            $clearPlans = $conn->prepare("DELETE FROM student_plan_selections WHERE student_id = ? AND (school_year IS NULL OR school_year = ?)");
            if ($clearPlans) {
                $clearPlans->bind_param('is', $student_id, $student_school_year);
                $clearPlans->execute();
                $clearPlans->close();
            }
        } else {
            $clearPlans = $conn->prepare("DELETE FROM student_plan_selections WHERE student_id = ?");
            if ($clearPlans) {
                $clearPlans->bind_param('i', $student_id);
                $clearPlans->execute();
                $clearPlans->close();
            }
        }

        header('Location: ' . $portalBasePath . 'student_portal.php');
        exit();
    }

    if (isset($_POST['select_portal_plan']) && $canStartPortalEnrollment) {
        $selectedPlanType = strtolower(trim((string) ($_POST['plan_type'] ?? '')));
        $tuitionFeeId = isset($_POST['tuition_fee_id']) ? (int) $_POST['tuition_fee_id'] : 0;
        $planSchoolYear = trim((string) ($_POST['plan_school_year'] ?? ''));
        $planGradeLevel = trim((string) ($_POST['plan_grade_level'] ?? ''));
        $planPricingCategory = trim((string) ($_POST['plan_pricing_category'] ?? ''));
        $planStudentType = trim((string) ($_POST['plan_student_type'] ?? ''));

        if ($selectedPlanType !== '' && $tuitionFeeId > 0) {
            $planContext = [
                'school_year' => $planSchoolYear,
                'grade_level' => $planGradeLevel,
                'pricing_category' => $planPricingCategory,
                'student_type' => $planStudentType,
            ];

            cashier_dashboard_save_plan_selection(
                $conn,
                $student_id,
                $tuitionFeeId,
                $selectedPlanType,
                $planContext
            );

            $_SESSION[$portalPlanSessionKey] = $selectedPlanType;
            if ($planPricingCategory !== '') {
                $_SESSION[$portalPricingSessionKey] = strtolower($planPricingCategory);
            }
        }

        $redirectUrl = $portalBasePath . 'choose_payment.php?student_id=' . urlencode((string) $student_id);
        if ($planPricingCategory !== '') {
            $redirectUrl .= '&pricing_variant=' . urlencode(strtolower($planPricingCategory));
        }
        header('Location: ' . $redirectUrl);
        exit();
    }
}

$portalEnrollmentReady = !empty($_SESSION[$portalEnrollmentSessionKey]);
$portalSelectedPlan = $_SESSION[$portalPlanSessionKey] ?? null;
$portalSelectedPricing = $_SESSION[$portalPricingSessionKey] ?? null;

$portalEnrollmentReadyActive = $portalEnrollmentReady;
$portalSelectedPlanActive = $portalSelectedPlan;
$portalSelectedPricingActive = $portalSelectedPricing;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $portalEnrollmentReadyActive) {
    unset($_SESSION[$portalEnrollmentSessionKey], $_SESSION[$portalPlanSessionKey], $_SESSION[$portalPricingSessionKey]);
}

$portalEnrollmentReady = $portalEnrollmentReadyActive;
$portalSelectedPlan = $portalSelectedPlanActive;
$portalSelectedPricing = $portalSelectedPricingActive;

include '../includes/header.php';
// Fetch tuition fee setup from admin
$normalized_year = cashier_normalize_grade_key((string) $year);

$sql = "SELECT * FROM tuition_fees 
        WHERE REPLACE(LOWER(grade_level), ' ', '') = ? 
        AND LOWER(student_type) = ? 
        ORDER BY school_year DESC LIMIT 1";
$fee = null;
$lower_type = strtolower($student_type);
$typeCandidates = [$lower_type, 'new', 'old', 'all'];
$typeCandidates = array_values(array_unique($typeCandidates));

function gradeSynonyms(string $normalized): array
{
    return cashier_grade_synonyms($normalized);
}

function portal_payment_status_is_paid(?string $status): bool
{
    $normalized = strtolower(trim((string) $status));
    return in_array($normalized, ['paid', 'completed', 'approved', 'cleared'], true);
}

foreach (gradeSynonyms($normalized_year) as $gradeKey) {
    foreach ($typeCandidates as $candidateType) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $gradeKey, $candidateType);
        $stmt->execute();
        $fee = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($fee) {
            break 2;
        }
    }
}

$previous_grade_label = cashier_previous_grade_label($year);
$grade_key = cashier_normalize_grade_key((string) $year);
$no_previous = ($lower_type === 'new') || in_array($grade_key, ['preschool', 'kinder1', 'kinder_1', 'kinder-1', 'kinder2', 'kindergarten', 'kg', 'grade1']);

$previous_fee = null;
if (!$no_previous && $previous_grade_label) {
    $normalized_prev = cashier_normalize_grade_key((string) $previous_grade_label);
    foreach (gradeSynonyms($normalized_prev) as $gradeKeyPrev) {
        foreach ($typeCandidates as $candidateType) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $gradeKeyPrev, $candidateType);
            $stmt->execute();
            $previous_fee = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($previous_fee) {
                break 2;
            }
        }
    }
} else {
    $previous_grade_label = null;
}

// Fetch payments
$sql = "SELECT id, payment_type, amount, payment_status, payment_date, reference_number, or_number, created_at, grade_level, school_year
        FROM student_payments 
        WHERE student_id = ?
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$paid = [];
$pending = [];
$paidByGrade = [];
$pendingByGrade = [];
$unassignedPaid = [];
$unassignedPending = [];

while ($row = $result->fetch_assoc()) {
    $row['amount'] = (float) ($row['amount'] ?? 0);
    if (empty($row['payment_date']) && !empty($row['created_at'])) {
        $row['payment_date'] = substr($row['created_at'], 0, 10);
    }

    $normalizedPaymentGrade = '';
    if (!empty($row['grade_level'])) {
        $normalizedPaymentGrade = cashier_normalize_grade_key((string) $row['grade_level']);
    }

    if (portal_payment_status_is_paid($row['payment_status'] ?? '')) {
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
$stmt->close();

$pending_total = 0;
foreach ($pending as $entry) {
    $pending_total += floatval($entry['amount']);
}

$previous_grade_total = 0;
if ($previous_fee) {
    $previous_grade_total = floatval($previous_fee['entrance_fee'])
        + floatval($previous_fee['miscellaneous_fee'])
        + floatval($previous_fee['tuition_fee']);
}
$current_grade_total = 0.0;
if ($fee) {
    $current_grade_total = floatval($fee['entrance_fee'])
        + floatval($fee['miscellaneous_fee'])
        + floatval($fee['tuition_fee']);
}
$sumAmounts = static function (array $rows): float {
    $total = 0.0;
    foreach ($rows as $entry) {
        $total += (float) ($entry['amount'] ?? 0);
    }
    return $total;
};

$sumByGrade = static function (array $map, string $gradeKey) use ($sumAmounts): float {
    if ($gradeKey === '') {
        return isset($map['']) ? $sumAmounts($map['']) : 0.0;
    }

    $total = 0.0;
    foreach (cashier_grade_synonyms($gradeKey) as $synonym) {
        if (isset($map[$synonym])) {
            $total += $sumAmounts($map[$synonym]);
        }
    }

    return $total;
};

$previous_grade_key = $previous_grade_label ? cashier_normalize_grade_key((string) $previous_grade_label) : null;
$current_synonyms = $grade_key !== '' ? cashier_grade_synonyms($grade_key) : [];
$previous_synonyms = $previous_grade_key ? cashier_grade_synonyms($previous_grade_key) : [];

$paid_current_total = $grade_key !== '' ? $sumByGrade($paidByGrade, $grade_key) : $sumAmounts($paidByGrade[''] ?? []);
$paid_previous_total = ($previous_grade_key) ? $sumByGrade($paidByGrade, $previous_grade_key) : 0.0;
$paid_unassigned_total = $sumAmounts($unassignedPaid);

$paid_other_total = 0.0;
foreach ($paidByGrade as $gradeKey => $rows) {
    if ($gradeKey === '' && $grade_key === '') {
        continue;
    }
    if (in_array($gradeKey, $current_synonyms, true)) {
        continue;
    }
    if ($previous_grade_key && in_array($gradeKey, $previous_synonyms, true)) {
        continue;
    }
    $paid_other_total += $sumAmounts($rows);
}

$remaining_previous_need = $previous_grade_total;

$allocated_previous_from_previous = min($remaining_previous_need, $paid_previous_total);
$remaining_previous_need -= $allocated_previous_from_previous;

$allocated_previous_from_unassigned = min($remaining_previous_need, $paid_unassigned_total);
$remaining_previous_need -= $allocated_previous_from_unassigned;

$allocated_previous_from_current = min($remaining_previous_need, $paid_current_total);
$remaining_previous_need -= $allocated_previous_from_current;

$allocated_previous_from_other = min($remaining_previous_need, $paid_other_total);
$remaining_previous_need -= $allocated_previous_from_other;

$previous_paid_applied = $previous_grade_total - max($remaining_previous_need, 0.0);
$previous_outstanding = max($remaining_previous_need, 0.0);

$remaining_current_paid = max($paid_current_total - $allocated_previous_from_current, 0.0);
$remaining_unassigned_total = max($paid_unassigned_total - $allocated_previous_from_unassigned, 0.0);
$allocated_current_from_unassigned = $remaining_unassigned_total;

$current_paid_amount = $remaining_current_paid + $allocated_current_from_unassigned;
$current_paid_remaining = $current_paid_amount;
$total_paid = $paid_previous_total + $paid_current_total + $paid_unassigned_total + $paid_other_total;

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
$remaining_prev_from_other = $allocated_previous_from_other;
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

    $matchesCurrentGrade = $normalizedPaymentGrade !== '' && in_array($normalizedPaymentGrade, $current_synonyms, true);
    $matchesPreviousGrade = $previous_grade_key && $normalizedPaymentGrade !== '' && in_array($normalizedPaymentGrade, $previous_synonyms, true) && !$matchesCurrentGrade;

    if (
        $previous_grade_key &&
        $matchesPreviousGrade &&
        $remaining_prev_allocation > 0
    ) {
        $apply_prev = min($amount_remaining, $remaining_prev_allocation);
        if ($apply_prev > 0) {
            $record = $entry;
            $record['applied_amount'] = $apply_prev;
            $record['source_amount'] = $original_amount;
            $record['applied_to'] = $previous_grade_label;
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
            $record['applied_to'] = $previous_grade_label;
            $record['is_partial'] = $apply_prev < $original_amount;
            $paid_history_previous[] = $record;
            $remaining_unassigned_prev -= $apply_prev;
            $remaining_prev_allocation -= $apply_prev;
            $amount_remaining -= $apply_prev;
        }
    } elseif (
        $previous_grade_key &&
        $matchesCurrentGrade &&
        $remaining_prev_from_current > 0 &&
        $remaining_prev_allocation > 0
    ) {
        $apply_prev = min($amount_remaining, $remaining_prev_from_current, $remaining_prev_allocation);
        if ($apply_prev > 0) {
            $record = $entry;
            $record['applied_amount'] = $apply_prev;
            $record['source_amount'] = $original_amount;
            $record['applied_to'] = $previous_grade_label;
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
    $applied_to_label = $year;

    if ($matchesCurrentGrade) {
        $apply_current = min($amount_remaining, $remaining_current_allocation);
    } elseif ($normalizedPaymentGrade === '' && $remaining_unassigned_current > 0) {
        $apply_current = min($amount_remaining, $remaining_unassigned_current, $remaining_current_allocation);
        $remaining_unassigned_current -= $apply_current;
    } elseif ($normalizedPaymentGrade !== '' && !$matchesPreviousGrade) {
        $applied_to_label = $entry['grade_level'] ?? $year;
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

$historySort = function (&$list) {
    usort($list, function (array $a, array $b) {
        $timeA = $a['created_at'] ?? $a['payment_date'] ?? '';
        $timeB = $b['created_at'] ?? $b['payment_date'] ?? '';
        return strcmp((string) $timeB, (string) $timeA);
    });
};

$historySort($paid_history_previous);
$historySort($paid_history_current);

$full_name = trim($firstname . ' ' . $lastname);
$display_section = $section ?: 'Not Assigned';
$display_adviser = $adviser ?: 'TBA';
$student_type_label = $student_type ? ucwords($student_type) : null;

$gender_normalized = strtolower(trim($gender ?? ''));
$avatar_variant = 'neutral';
if ($gender_normalized === 'male') {
    $avatar_variant = 'male';
} elseif ($gender_normalized === 'female') {
    $avatar_variant = 'female';
}



$pricing_variant_param = isset($_GET['pricing_variant']) ? strtolower(trim((string) $_GET['pricing_variant'])) : null;
$escGrades = ['grade7','grade8','grade9','grade10','grade11','grade12'];
if ($pricing_variant_param === null) {
    if (!empty($portalSelectedPricing)) {
        $pricing_variant_param = strtolower(trim($portalSelectedPricing));
    } elseif (in_array($grade_key, $escGrades, true)) {
        $pricing_variant_param = 'esc';
    }
}

$studentRowData = [
    'id' => $student_id,
    'student_number' => $student_number,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'year' => $year,
    'section' => $section,
    'adviser' => $adviser,
    'student_type' => $student_type,
    'enrollment_status' => $enrollmentStatus,
    'academic_status' => $academicStatus,
    'school_year' => $student_school_year,
];

$financial_snapshot = cashier_dashboard_build_student_financial($conn, (int) $student_id, [
    'pricing_variant' => $pricing_variant_param,
    'pricing_student' => $student_id,
    'student_row' => $studentRowData,
    'require_explicit_plan' => ($portalEnrollmentReady && !$portalSelectedPlan && $canStartPortalEnrollment),
]);

$finance_views = [];
$available_pricing_options = array_keys(cashier_dashboard_pricing_labels());
$selected_pricing_key = 'regular';
$pricing_labels_map = cashier_dashboard_pricing_labels();
$plan_tabs = [];
$active_plan_key = null;

if ($financial_snapshot) {
    $finance_views = array_values($financial_snapshot['views'] ?? []);
    $available_pricing_options = $financial_snapshot['available_pricing'] ?? $available_pricing_options;
    if (empty($available_pricing_options)) {
        $available_pricing_options = array_keys($pricing_labels_map);
    }
    $selected_pricing_key = $financial_snapshot['selected_pricing'] ?? $selected_pricing_key;
    $plan_tabs = $financial_snapshot['plan_tabs'] ?? [];
    $active_plan_key = $financial_snapshot['active_plan'] ?? null;
    $stored_pricing_key = $financial_snapshot['stored_pricing'] ?? null;
} else {
    $stored_pricing_key = null;
}

$storedPlanCurrent = null;
foreach ($finance_views as $candidateView) {
    if (($candidateView['key'] ?? '') === 'current') {
        $storedPlanCurrent = isset($candidateView['stored_plan']) ? strtolower((string) $candidateView['stored_plan']) : null;
        break;
    }
}

if ($portalSelectedPlan && ($storedPlanCurrent === null || $storedPlanCurrent !== strtolower((string) $portalSelectedPlan))) {
    unset($_SESSION[$portalPlanSessionKey]);
    $portalSelectedPlan = null;
}

if ($portalSelectedPricing && !in_array(strtolower($portalSelectedPricing), array_map('strtolower', $available_pricing_options), true)) {
    unset($_SESSION[$portalPricingSessionKey]);
    $portalSelectedPricing = null;
}

if ($portalSelectedPricing) {
    $selected_pricing_key = strtolower($portalSelectedPricing);
} elseif (!empty($stored_pricing_key)) {
    $selected_pricing_key = strtolower((string) $stored_pricing_key);
}

$selected_view_key = $_GET['finance_view'] ?? ($financial_snapshot['default_view_key'] ?? 'current');
$available_view_keys = array_column($finance_views, 'key');
if (!$selected_view_key || !in_array($selected_view_key, $available_view_keys, true)) {
    $selected_view_key = $available_view_keys[0] ?? null;
}
foreach ($finance_views as &$finance_view_ref) {
    $finance_view_ref['is_default'] = ($finance_view_ref['key'] === $selected_view_key);
}
unset($finance_view_ref);

$has_multiple_views = count($finance_views) > 1;
$selected_view_key = $_GET['finance_view'] ?? null;
$available_view_keys = array_column($finance_views, 'key');

if (!$selected_view_key || !in_array($selected_view_key, $available_view_keys, true)) {
    $selected_view_key = $available_view_keys[0] ?? null;
}

foreach ($finance_views as &$finance_view_ref) {
    $finance_view_ref['is_default'] = ($finance_view_ref['key'] === $selected_view_key);
}
unset($finance_view_ref);

$has_multiple_views = count($finance_views) > 1;
$student_type_lower = strtolower($student_type);

foreach ($finance_views as &$finance_view_ref) {
    if (($finance_view_ref['key'] ?? '') !== 'previous') {
        continue;
    }

    $previous_grade_key_snapshot = $finance_view_ref['grade_key'] ?? null;
    if (!$previous_grade_key_snapshot && !empty($financial_snapshot['previous_grade_label'])) {
        $previous_grade_key_snapshot = cashier_normalize_grade_key((string) $financial_snapshot['previous_grade_label']);
    }

    if (!$previous_grade_key_snapshot) {
        continue;
    }

    $typeCandidates = array_values(array_unique([$student_type_lower, 'old', 'new', 'all']));
    $previous_fee_portal = cashier_fetch_fee($conn, $previous_grade_key_snapshot, $typeCandidates);
    if (!$previous_fee_portal) {
        continue;
    }

    $previous_selection_portal = cashier_dashboard_fetch_selected_plan($conn, (int) $student_id, (int) $previous_fee_portal['id']);
    $previous_plan_type = strtolower(trim((string) ($previous_selection_portal['plan_type'] ?? '')));

    $previous_total_paid = (float) ($finance_view_ref['total_paid'] ?? 0.0);
    $previous_remaining = (float) ($finance_view_ref['remaining_balance'] ?? 0.0);

    $previous_summaries_portal = cashier_dashboard_build_plan_summaries($previous_fee_portal, $previous_total_paid, $previous_remaining);
    if (empty($previous_summaries_portal)) {
        continue;
    }

    $selected_previous_summary_portal = null;
    if ($previous_plan_type !== '') {
        foreach ($previous_summaries_portal as $summaryCandidate) {
            if (strtolower((string) ($summaryCandidate['plan_type'] ?? '')) === $previous_plan_type) {
                $selected_previous_summary_portal = $summaryCandidate;
                break;
            }
        }
    }
    if (!$selected_previous_summary_portal) {
        $selected_previous_summary_portal = $previous_summaries_portal[0];
    }

    if ($selected_previous_summary_portal) {
        $finance_view_ref['plan_label'] = $selected_previous_summary_portal['label'] ?? ($finance_view_ref['plan_label'] ?? '');
        $finance_view_ref['schedule_rows'] = $selected_previous_summary_portal['schedule_rows'] ?? ($finance_view_ref['schedule_rows'] ?? []);
        $finance_view_ref['schedule_message'] = $selected_previous_summary_portal['schedule_message'] ?? ($finance_view_ref['schedule_message'] ?? '');
    }
}
unset($finance_view_ref);
?>
<style>
    .portal-main {
        background: linear-gradient(180deg, rgba(20, 90, 50, 0.1) 0%, rgba(20, 90, 50, 0) 180px);
        padding-top: 120px;
        padding-bottom: 80px;
    }

    .portal-card {
        border: none;
        border-radius: 18px;
        box-shadow: 0 18px 40px rgba(12, 68, 49, 0.08);
        overflow: hidden;
    }

    .profile-card .profile-avatar {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background-size: cover;
        background-position: center;
        border: 4px solid rgba(20, 90, 50, 0.15);
        box-shadow: 0 10px 20px rgba(20, 90, 50, 0.12);
    }

    .profile-avatar.male { background-image: url('assets/img/student-male.svg'); }
    .profile-avatar.female { background-image: url('assets/img/student-female.svg'); }
    .profile-avatar.neutral { background-image: url('assets/img/student-neutral.svg'); }

    .profile-meta li {
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.95rem;
    }

    .profile-meta li i {
        font-size: 1.1rem;
        color: #145A32;
    }

    .summary-tile {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 14px;
        padding: 16px 18px;
        border: 1px solid rgba(20, 90, 50, 0.08);
    }

    .summary-tile .label {
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.06em;
        color: #5d6d6f;
        display: block;
    }

    .summary-tile h4 {
        margin: 6px 0 0;
        font-weight: 700;
        color: #145A32;
    }

    .portal-table thead th {
        background: #145A32;
        color: #ffffff;
        border: none;
        font-weight: 600;
    }

    .portal-table tbody tr:hover {
        background: rgba(20, 90, 50, 0.06);
    }

    .portal-table .next-due {
        background: rgba(251, 216, 10, 0.22);
    }

    .portal-table .row-outstanding {
        background: rgba(231, 76, 60, 0.12);
    }

    .portal-plan-card {
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .portal-plan-card.is-active {
        border-width: 2px;
    }

    .portal-plan-details {
        display: none;
        margin-top: 18px;
    }

    .portal-plan-card.is-active .portal-plan-details {
        display: block;
    }

    .portal-plan-header .form-check-input {
        width: 1.35rem;
        height: 1.35rem;
        border: 2px solid rgba(20, 90, 50, 0.55);
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.9);
    }

    .portal-plan-header .form-check-input:checked {
        background-color: #145A32;
        border-color: #145A32;
        box-shadow: 0 0 0 2px rgba(20, 90, 50, 0.25);
    }

    .portal-plan-header .form-check-input:focus {
        border-color: #145A32;
        box-shadow: 0 0 0 4px rgba(20, 90, 50, 0.25);
    }

    .portal-plan-header .form-check-label {
        font-size: 1.05rem;
    }

    .portal-plan-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(20, 90, 50, 0.12);
        color: #145A32;
        font-weight: 600;
        white-space: nowrap;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .status-pill.warning {
        background: rgba(251, 216, 10, 0.22);
        color: #8a6d0a;
    }

    .status-pill.safe {
        background: rgba(39, 174, 96, 0.18);
        color: #1e8449;
    }

    .empty-state {
        border-radius: 12px;
        background: rgba(20, 90, 50, 0.05);
        padding: 20px;
        text-align: center;
        color: #4a5a58;
    }
</style>

<main class="portal-main">
    <div class="container">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-4">
                <div class="card portal-card profile-card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center gap-3">
                            <div class="profile-avatar <?php echo htmlspecialchars($avatar_variant); ?>"></div>
                            <div>
                                <p class="text-muted mb-1 small">Welcome back</p>
                                <h2 class="h4 mb-0 fw-bold"><?php echo htmlspecialchars($full_name); ?></h2>
                                <?php if ($student_type_label): ?>
                                    <span class="badge rounded-pill bg-success-subtle text-success fw-semibold mt-2">
                                        <?php echo htmlspecialchars($student_type_label); ?> Student
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <hr class="my-4">
                        <ul class="list-unstyled profile-meta flex-grow-1">
                            <li><i class="bi bi-mortarboard"></i> <span>Grade Level: <strong><?php echo htmlspecialchars($year); ?></strong></span></li>
                            <li><i class="bi bi-people"></i> <span>Section: <strong><?php echo htmlspecialchars($display_section); ?></strong></span></li>
                            <li><i class="bi bi-person-badge"></i> <span>Adviser: <strong><?php echo htmlspecialchars($display_adviser); ?></strong></span></li>
                            <?php if ($gender_normalized): ?>
                                <li><i class="bi bi-gender-<?php echo $gender_normalized === 'female' ? 'female' : 'male'; ?>"></i> <span>Gender: <strong><?php echo htmlspecialchars(ucfirst($gender_normalized)); ?></strong></span></li>
                            <?php endif; ?>
                        </ul>
                        <div class="mt-auto">
                            <a href="Portal/logout.php" class="btn btn-outline-danger w-100">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 d-flex flex-column gap-4">
                    <?php if ($canStartPortalEnrollment): ?>
                        <?php if (!$portalEnrollmentReady): ?>
                            <div class="card portal-card">
                                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                    <div>
                                        <h3 class="h5 fw-bold mb-1">Ready to Enroll for the New School Year?</h3>
                                        <p class="text-muted mb-0">Click below to review tuition plans and start your online enrollment.</p>
                                    </div>
                                    <form method="POST" action="<?= htmlspecialchars($assetBase . 'Portal/student_portal.php', ENT_QUOTES, 'UTF-8'); ?>" class="ms-md-auto">
                                        <input type="hidden" name="start_portal_enrollment" value="1">
                                        <button type="submit" class="btn btn-success fw-semibold">
                                            <i class="bi bi-calendar-check me-2"></i>Enroll Now
                                        </button>
                                    </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card portal-card">
                            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h3 class="h5 fw-bold mb-1">Select Your Payment Plan</h3>
                                    <p class="text-muted mb-0">Choose a plan below and proceed to online payment. Your submission will remain pending until the cashier verifies it.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($has_multiple_views): ?>
                    <div class="d-flex justify-content-end">
                        <form method="GET" action="<?= htmlspecialchars($assetBase . 'Portal/student_portal.php', ENT_QUOTES, 'UTF-8'); ?>" class="w-100" style="max-width: 240px;">
                            <label for="financeViewSelector" class="form-label small text-muted mb-1">View financial data for</label>
                            <select id="financeViewSelector" name="finance_view" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php foreach ($finance_views as $view_option): ?>
                                    <option value="<?php echo htmlspecialchars($view_option['key']); ?>" <?php echo $view_option['is_default'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($view_option['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                <?php endif; ?>

                <?php foreach ($finance_views as $view):
                    $view_key = $view['key'];
                    $is_default = $view['is_default'];
                    $view_remaining = $view['remaining_balance'];
                    $view_total_paid = $view['total_paid'];
                    $view_pending_total = $view['pending_total'];
                    $view_plan_label = $view['plan_label'];
                    $view_pricing_variant = $view['pricing_variant'] ?? $selected_pricing_key;
                    $view_pricing_label = $view_pricing_variant ? ($pricing_labels_map[$view_pricing_variant] ?? ucfirst($view_pricing_variant)) : null;
                    $view_next_due = $view['next_due_row'];
                    $view_alert = $view['alert'];
                    $view_schedule_rows = $view['schedule_rows'];
                    $view_schedule_message = $view['schedule_message'];
                    $view_pending_rows = $view['pending_rows'];
                    $view_pending_message = $view['pending_message'];
                    $view_year_total = $view['current_year_total'];
                    $view_history_rows = $view['history_rows'] ?? [];
                    $view_history_message = $view['history_message'] ?? 'No payments recorded yet.';
                    $view_plan_tabs = $view['plan_tabs'] ?? [];
                    $view_plan_context = $view['plan_context'] ?? null;
                    $view_active_plan = $view['active_plan'] ?? null;
                    if ($portalEnrollmentReady && $view_key === 'current' && $portalSelectedPlan) {
                        $view_active_plan = strtolower($portalSelectedPlan);
                    }
                    $showingPlanSelection = ($portalEnrollmentReady && $view_key === 'current');
                    $hidePlanDetails = (($canStartPortalEnrollment && $view_key === 'current') && !$portalSelectedPlan);
                    if ($hidePlanDetails) {
                        $view_plan_label = '';
                        $view_schedule_rows = [];
                        $view_schedule_message = 'Select a payment plan to view the schedule.';
                        $view_next_due = null;
                        $view_pricing_label = null;
                    }
                ?>
                <div class="finance-view" data-view="<?php echo htmlspecialchars($view_key); ?>" style="<?php echo $is_default ? '' : 'display:none;'; ?>">
                <div class="finance-view-content d-flex flex-column gap-4">
                    <div class="card portal-card">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <div>
                                    <h3 class="h5 fw-bold mb-1">Payment Overview</h3>
                                    <p class="text-muted mb-0">Track your tuition progress for this grade.</p>
                                </div>
                                <?php if ($view_alert): ?>
                                    <span class="status-pill warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Past due from <?php echo htmlspecialchars($view_alert['grade']); ?>: ₱<?php echo number_format($view_alert['amount'], 2); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill safe">
                                        <i class="bi bi-check-circle"></i>
                                        <?php echo $view_key === 'previous' ? 'Past year summary' : 'Account in good standing'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-sm-6 col-lg-3">
                                    <div class="summary-tile h-100">
                                        <span class="label">Tuition Package</span>
                                        <h4>₱<?php echo number_format($view_year_total, 2); ?></h4>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="summary-tile h-100">
                                        <span class="label">Remaining Balance</span>
                                        <h4>₱<?php echo number_format($view_remaining, 2); ?></h4>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="summary-tile h-100">
                                        <span class="label">Total Paid</span>
                                        <h4>₱<?php echo number_format($view_total_paid, 2); ?></h4>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="summary-tile h-100">
                                        <span class="label">Pending Review</span>
                                        <h4>₱<?php echo number_format($view_pending_total, 2); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($portalEnrollmentReady && $view_key === 'current'): ?>
                        <?php if (!empty($view_plan_tabs) && !empty($view_plan_context)): ?>
                            <div class="card portal-card">
                                <div class="card-body">
                                    <h3 class="h5 fw-bold mb-3">Choose a Payment Plan</h3>
                                    <form method="POST" action="<?= htmlspecialchars($assetBase . 'Portal/student_portal.php', ENT_QUOTES, 'UTF-8'); ?>" class="d-flex flex-column gap-3">
                                        <input type="hidden" name="select_portal_plan" value="1">
                                        <input type="hidden" name="tuition_fee_id" value="<?php echo (int) ($view_plan_context['tuition_fee_id'] ?? 0); ?>">
                                        <input type="hidden" name="plan_school_year" value="<?php echo htmlspecialchars($view_plan_context['school_year'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="plan_grade_level" value="<?php echo htmlspecialchars($view_plan_context['grade_level'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="plan_pricing_category" value="<?php echo htmlspecialchars($view_plan_context['pricing_category'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="plan_student_type" value="<?php echo htmlspecialchars($view_plan_context['student_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                                        <div class="row g-3">
                                            <?php foreach ($view_plan_tabs as $planTab):
                                                $planType = strtolower((string) ($planTab['plan_type'] ?? ''));
                                                if ($planType === '') {
                                                    continue;
                                                }
                                                $planLabel = $planTab['label'] ?? strtoupper($planType);
                                                $baseSnapshot = $planTab['base'] ?? [];
                                                $entriesSnapshot = $planTab['entries'] ?? [];
                                                $isChecked = ($view_active_plan === $planType);
                                                $inputId = 'portal-plan-' . $planType;
                                            ?>
                                            <div class="col-12">
                                                <?php
                                                    $dueNow = (float) ($baseSnapshot['due_total'] ?? 0);
                                                ?>
                                                <div class="portal-plan-card border <?php echo $isChecked ? 'border-success shadow-sm is-active' : 'border-light'; ?> rounded-4 p-3 p-lg-4" data-plan-card="<?php echo htmlspecialchars($planType); ?>">
                                                    <div class="form-check d-flex align-items-center justify-content-between flex-wrap gap-3 portal-plan-header">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <input class="form-check-input" type="radio" name="plan_type" id="<?php echo htmlspecialchars($inputId); ?>" value="<?php echo htmlspecialchars($planType); ?>" <?php echo $isChecked ? 'checked' : ''; ?> required>
                                                            <label class="form-check-label fw-bold mb-0" for="<?php echo htmlspecialchars($inputId); ?>">
                                                                <?php echo htmlspecialchars($planLabel); ?>
                                                            </label>
                                                        </div>
                                                        <span class="portal-plan-chip">
                                                            Due now: <strong>₱<?php echo number_format($dueNow, 2); ?></strong>
                                                        </span>
                                                    </div>
                                                    <div class="portal-plan-details">
                                                        <div class="row mt-3 g-4">
                                                            <div class="col-md-6">
                                                                <span class="text-uppercase text-muted small fw-semibold">What you pay today</span>
                                                                <ul class="list-unstyled mb-0 small mt-2">
                                                                    <li>Entrance Fee: <strong>₱<?php echo number_format((float) ($baseSnapshot['entrance_fee'] ?? 0), 2); ?></strong></li>
                                                                    <li>Miscellaneous Fee: <strong>₱<?php echo number_format((float) ($baseSnapshot['miscellaneous_fee'] ?? 0), 2); ?></strong></li>
                                                                    <li>Tuition Portion: <strong>₱<?php echo number_format((float) ($baseSnapshot['tuition_fee'] ?? 0), 2); ?></strong></li>
                                                                    <li class="mt-2">Due upon enrollment: <strong class="text-success">₱<?php echo number_format((float) ($baseSnapshot['due_total'] ?? 0), 2); ?></strong></li>
                                                                </ul>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <span class="text-uppercase text-muted small fw-semibold">Remaining Balance</span>
                                                                <p class="small mb-2 mt-2">Future payments total: <strong>₱<?php echo number_format(max(0.0, (float) ($baseSnapshot['overall_total'] ?? 0) - (float) ($baseSnapshot['due_total'] ?? 0)), 2); ?></strong></p>
                                                                <p class="small mb-0">Overall program cost: <strong>₱<?php echo number_format((float) ($baseSnapshot['overall_total'] ?? 0), 2); ?></strong></p>
                                                            </div>
                                                        </div>
                                                        <?php if (!empty($entriesSnapshot)): ?>
                                                            <div class="table-responsive mt-3">
                                                                <table class="table table-sm mb-0">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Schedule</th>
                                                                            <th class="text-end">Amount</th>
                                                                            <th>Notes</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($entriesSnapshot as $entry):
                                                                            $entryLabel = $entry['label'] ?? ($entry['note'] ?? 'Next Payment');
                                                                            $entryAmount = (float) ($entry['amount_original'] ?? $entry['amount'] ?? 0);
                                                                            $entryNote = $entry['note'] ?? '';
                                                                        ?>
                                                                        <tr>
                                                                            <td><?php echo htmlspecialchars($entryLabel); ?></td>
                                                                            <td class="text-end">₱<?php echo number_format($entryAmount, 2); ?></td>
                                                                            <td><?php echo $entryNote !== '' ? htmlspecialchars($entryNote) : '—'; ?></td>
                                                                        </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($planTab['notes'])): ?>
                                                            <div class="alert alert-info mt-3 mb-0 py-2 small">
                                                                <i class="bi bi-info-circle me-2"></i><?php echo nl2br(htmlspecialchars($planTab['notes'])); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-success fw-semibold">
                                                Continue to Online Payment
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php elseif ($portalEnrollmentReady && $view_key === 'current'): ?>
                            <div class="card portal-card">
                                <div class="card-body">
                                    <div class="empty-state">
                                        <i class="bi bi-info-circle me-2"></i>Tuition plans are not configured yet. Please contact the cashier.
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                        <div class="card portal-card">
                            <div class="card-body">
                                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                                    <div>
                                        <h3 class="h5 fw-bold mb-1">Upcoming Payment Schedule</h3>
                                        <?php if ($view_plan_label): ?>
                                            <div class="text-muted small">
                                                Plan: <strong class="text-success text-uppercase"><?php echo htmlspecialchars($view_plan_label); ?></strong>
                                                <?php if ($view_pricing_label): ?>· Pricing: <strong><?php echo htmlspecialchars($view_pricing_label); ?></strong><?php endif; ?>
                                                <?php if ($view_next_due): ?>· Next due on <strong><?php echo htmlspecialchars($view_next_due['label'] ?? $view_next_due['due_date'] ?? ''); ?></strong><?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted small mb-0">No tuition fee configuration is available yet.</p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!($portalEnrollmentReady && $view_key === 'current')): ?>
                                        <form action="<?= htmlspecialchars($assetBase . 'Portal/choose_payment.php', ENT_QUOTES, 'UTF-8'); ?>" method="GET" class="ms-lg-auto">
                                            <input type="hidden" name="student_id" value="<?php echo (int) $student_id; ?>">
                                            <input type="hidden" name="pricing_variant" value="<?php echo htmlspecialchars($selected_pricing_key); ?>">
                                            <button type="submit" class="btn btn-success fw-semibold">
                                                <i class="bi bi-credit-card me-2"></i>Pay Now
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                            <?php if (!empty($view_alert) && $view_key === 'current'): ?>
                                <div class="alert alert-warning border-0 text-dark">
                                    <i class="bi bi-exclamation-circle me-2"></i>
                                    Past due from <?php echo htmlspecialchars($view_alert['grade']); ?>: <strong>₱<?php echo number_format($view_alert['amount'], 2); ?></strong>.
                                    This is added to your current schedule.
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($view_schedule_rows)): ?>
                                <div class="table-responsive">
                                    <table class="table portal-table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th scope="col">Schedule</th>
                                                <th scope="col" class="text-end">Amount</th>
                                                <th scope="col">Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($view_schedule_rows as $row):
                                                $row_class = '';
                                                if (!empty($row['row_class'])) {
                                                    $row_class = $row['row_class'];
                                                } elseif (!empty($row['is_next'])) {
                                                    $row_class = 'next-due';
                                                }

                                                $display_amount = '';
                                                if (array_key_exists('amount_outstanding', $row)) {
                                                    $originalRaw = (float) ($row['amount_original'] ?? 0);
                                                    $outstandingRaw = (float) ($row['amount_outstanding'] ?? 0);
                                                    $original = round($originalRaw, 2);
                                                    $outstanding = round($outstandingRaw, 2);

                                                    if ($outstanding <= 0.009) {
                                                        $display_amount = '<span class="text-success fw-semibold">Paid</span> (₱' . number_format($original, 2) . ')';
                                                    } elseif ($original > $outstanding + 0.009) {
                                                        $display_amount = '₱' . number_format($outstanding, 2) . ' of ₱' . number_format($original, 2) . ' remaining';
                                                    } else {
                                                        $display_amount = '₱' . number_format($outstanding, 2);
                                                    }
                                                } else {
                                                    $display_amount = '₱' . number_format((float) ($row['amount'] ?? 0), 2);
                                                }
                                            ?>
                                                <tr class="<?php echo $row_class; ?>">
                                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['label'] ?? ($row['due_date'] ?? '')); ?></td>
                                                    <td class="text-end"><?php echo $display_amount; ?></td>
                                                    <td><?php echo isset($row['note']) && $row['note'] !== '' ? htmlspecialchars($row['note']) : '—'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state mt-3">
                                    <i class="bi bi-info-circle me-2"></i><?php echo htmlspecialchars($view_schedule_message); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card portal-card">
                        <div class="card-body">
                            <h3 class="h5 fw-bold mb-3">Pending Payments</h3>
                            <?php if (!empty($view_pending_rows)): ?>
                                <div class="table-responsive">
                                    <table class="table portal-table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th scope="col">Payment Type</th>
                                                <th scope="col" class="text-end">Amount</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Submitted On</th>
                                                <th scope="col">Reference</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($view_pending_rows as $pending_row):
                                                $row_class = '';
                                                if (!empty($pending_row['row_class'])) {
                                                    $row_class = $pending_row['row_class'];
                                                } elseif (!empty($pending_row['is_placeholder'])) {
                                                    $row_class = 'row-outstanding';
                                                }
                                            ?>
                                                <tr class="<?php echo $row_class; ?>">
                                                    <td><?php echo htmlspecialchars($pending_row['payment_type_display'] ?? ($pending_row['payment_type'] ?? 'Pending')); ?></td>
                                                    <td class="text-end">₱<?php echo number_format((float) ($pending_row['amount'] ?? 0), 2); ?></td>
                                                    <td><span class="badge bg-warning-subtle text-warning fw-semibold"><?php echo htmlspecialchars($pending_row['payment_status'] ?? 'Pending'); ?></span></td>
                                                    <td><?php echo htmlspecialchars($pending_row['payment_date'] ?? 'Awaiting review'); ?></td>
                                                    <td><?php echo htmlspecialchars($pending_row['reference_number'] ?? $pending_row['or_number'] ?? 'N/A'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-check2-circle me-2"></i><?php echo htmlspecialchars($view_pending_message); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                </div>
                <?php endforeach; ?>

                <div class="card portal-card mb-0">
                    <div class="card-body">
                        <h3 class="h5 fw-bold mb-3">Payment History</h3>
                        <?php $has_history = !empty($paid_history_current) || !empty($paid_history_previous); ?>
                        <?php if ($has_history): ?>
                            <?php if (!empty($paid_history_current)): ?>
                                <div class="mb-4">
                                    <h4 class="h6 text-uppercase text-muted mb-2">Current Grade - <?php echo htmlspecialchars($year); ?></h4>
                                    <div class="table-responsive">
                                        <table class="table portal-table align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Date</th>
                                                    <th scope="col">Method</th>
                                                    <th scope="col" class="text-end">Amount Applied</th>
                                                    <th scope="col">References</th>
                                                    <th scope="col">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($paid_history_current as $history_row):
                                                    $history_amount = (float) ($history_row['applied_amount'] ?? $history_row['amount'] ?? 0);
                                                    $history_source = (float) ($history_row['source_amount'] ?? $history_amount);
                                                    $is_partial = !empty($history_row['is_partial']) && $history_source > 0 && abs($history_amount - $history_source) > 0.009;
                                                    $reference_number = $history_row['reference_number'] ?? null;
                                                    $or_number = $history_row['or_number'] ?? null;
                                                    $date_display = $history_row['payment_date'] ?? ($history_row['created_at'] ?? '--');
                                                    $status_label = ucfirst(strtolower((string) ($history_row['payment_status'] ?? 'Paid')));
                                                ?>
                                                    <tr>
                                                        <td class="fw-semibold"><?php echo htmlspecialchars($date_display); ?></td>
                                                        <td><?php echo htmlspecialchars(ucfirst((string) ($history_row['payment_type'] ?? 'N/A'))); ?></td>
                                                        <td class="text-end">
                                                            ₱<?php echo number_format($history_amount, 2); ?>
                                                            <?php if ($is_partial): ?>
                                                                <div class="text-muted small">From ₱<?php echo number_format($history_source, 2); ?></div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($reference_number): ?>
                                                                <div>Ref: <?php echo htmlspecialchars($reference_number); ?></div>
                                                            <?php endif; ?>
                                                            <?php if ($or_number): ?>
                                                                <div>OR: <?php echo htmlspecialchars($or_number); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!$reference_number && !$or_number): ?>
                                                                <span class="text-muted">N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><span class="badge bg-success-subtle text-success fw-semibold"><?php echo htmlspecialchars($status_label); ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($paid_history_previous)): ?>
                                <div class="<?php echo !empty($paid_history_current) ? 'mt-4' : ''; ?>">
                                    <h4 class="h6 text-uppercase text-muted mb-2">Previous Grade<?php echo $previous_grade_label ? ' - ' . htmlspecialchars($previous_grade_label) : ''; ?></h4>
                                    <div class="table-responsive">
                                        <table class="table portal-table align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Date</th>
                                                    <th scope="col">Method</th>
                                                    <th scope="col" class="text-end">Amount Applied</th>
                                                    <th scope="col">References</th>
                                                    <th scope="col">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($paid_history_previous as $history_row):
                                                    $history_amount = (float) ($history_row['applied_amount'] ?? $history_row['amount'] ?? 0);
                                                    $history_source = (float) ($history_row['source_amount'] ?? $history_amount);
                                                    $is_partial = !empty($history_row['is_partial']) && $history_source > 0 && abs($history_amount - $history_source) > 0.009;
                                                    $reference_number = $history_row['reference_number'] ?? null;
                                                    $or_number = $history_row['or_number'] ?? null;
                                                    $date_display = $history_row['payment_date'] ?? ($history_row['created_at'] ?? '--');
                                                    $status_label = ucfirst(strtolower((string) ($history_row['payment_status'] ?? 'Paid')));
                                                ?>
                                                    <tr>
                                                        <td class="fw-semibold"><?php echo htmlspecialchars($date_display); ?></td>
                                                        <td><?php echo htmlspecialchars(ucfirst((string) ($history_row['payment_type'] ?? 'N/A'))); ?></td>
                                                        <td class="text-end">
                                                            ₱<?php echo number_format($history_amount, 2); ?>
                                                            <?php if ($is_partial): ?>
                                                                <div class="text-muted small">From ₱<?php echo number_format($history_source, 2); ?></div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($reference_number): ?>
                                                                <div>Ref: <?php echo htmlspecialchars($reference_number); ?></div>
                                                            <?php endif; ?>
                                                            <?php if ($or_number): ?>
                                                                <div>OR: <?php echo htmlspecialchars($or_number); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!$reference_number && !$or_number): ?>
                                                                <span class="text-muted">N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><span class="badge bg-success-subtle text-success fw-semibold"><?php echo htmlspecialchars($status_label); ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php elseif ($previous_grade_label): ?>
                                <div class="empty-state">
                                    <i class="bi bi-info-circle me-2"></i>No recorded payments for <?php echo htmlspecialchars($previous_grade_label); ?> yet.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-info-circle me-2"></i>No payments recorded yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const changePasswordBtn = document.getElementById('portalChangePassword');
        const changePasswordModalEl = document.getElementById('changePasswordModal');
        const changePasswordForm = document.getElementById('changePasswordForm');
        const changePasswordAlert = document.getElementById('changePasswordAlert');
        const inboxToggle = document.getElementById('studentInboxToggle');
        const inboxMenu = document.getElementById('studentInboxMenu');
        const inboxCountEl = document.getElementById('studentInboxCount');
        const inboxHeaderEl = inboxMenu ? inboxMenu.querySelector('.dropdown-header') : null;
        const announcementModalEl = document.getElementById('announcementModal');
        const announcementModalTitle = document.getElementById('announcementModalTitle');
        const announcementModalTime = document.getElementById('announcementModalTime');
        const announcementModalBody = document.getElementById('announcementModalBody');

        const portalPlanCards = Array.from(document.querySelectorAll('.portal-plan-card'));
        const portalPlanInputs = Array.from(document.querySelectorAll('input[name="plan_type"]'));

        if (portalPlanCards.length && portalPlanInputs.length) {
            const setActivePortalPlan = function (selectedValue) {
                const normalized = (selectedValue || '').toString().toLowerCase();
                portalPlanCards.forEach(function (card) {
                    const cardKey = (card.getAttribute('data-plan-card') || '').toLowerCase();
                    const isActive = normalized !== '' && cardKey === normalized;
                    card.classList.toggle('is-active', isActive);
                    card.classList.toggle('border-success', isActive);
                    card.classList.toggle('shadow-sm', isActive);
                    card.classList.toggle('border-light', !isActive);
                });
            };

            portalPlanInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    if (input.checked) {
                        setActivePortalPlan(input.value);
                    }
                });
            });

            const initiallyChecked = portalPlanInputs.find(function (input) {
                return input.checked;
            });
            if (initiallyChecked) {
                setActivePortalPlan(initiallyChecked.value);
            } else {
                setActivePortalPlan('');
            }
        }

        let inboxLoaded = false;
        let markingInboxRead = false;
        let inboxState = { items: [], unreadCount: 0 };
        let changePasswordModal = null;
        let announcementModal = null;

        if (changePasswordModalEl && typeof bootstrap !== 'undefined') {
            changePasswordModal = new bootstrap.Modal(changePasswordModalEl);
        }

        if (announcementModalEl && typeof bootstrap !== 'undefined') {
            announcementModal = new bootstrap.Modal(announcementModalEl);
        }

        function showChangePasswordAlert(message, type) {
            if (!changePasswordAlert) {
                return;
            }
            changePasswordAlert.textContent = message;
            changePasswordAlert.className = 'alert alert-' + type;
            changePasswordAlert.style.display = 'block';
        }

        function clearChangePasswordAlert() {
            if (changePasswordAlert) {
                changePasswordAlert.textContent = '';
                changePasswordAlert.style.display = 'none';
            }
        }

        if (changePasswordBtn && changePasswordModal) {
            changePasswordBtn.addEventListener('click', function () {
                clearChangePasswordAlert();
                changePasswordForm.reset();
                changePasswordModal.show();
            });
        }

        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                clearChangePasswordAlert();

                const formData = new FormData(changePasswordForm);
                const currentPassword = formData.get('current_password') || '';
                const newPassword = formData.get('new_password') || '';
                const confirmPassword = formData.get('confirm_password') || '';

                if (newPassword !== confirmPassword) {
                    showChangePasswordAlert('New password and confirmation do not match.', 'danger');
                    return;
                }
                if (newPassword.length < 8) {
                    showChangePasswordAlert('Password must be at least 8 characters long.', 'danger');
                    return;
                }

                const submitBtn = changePasswordForm.querySelector('button[type="submit"]');
                const originalLabel = submitBtn ? submitBtn.textContent : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Updating…';
                }

                try {
                    const response = await fetch('Portal/change_password.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            current_password: currentPassword,
                            new_password: newPassword
                        })
                    });
                    const data = await response.json();

                    if (!data || !data.success) {
                        throw new Error(data && data.error ? data.error : 'Unable to update password right now.');
                    }

                    showChangePasswordAlert('Password updated successfully.', 'success');
                    setTimeout(function () {
                        if (changePasswordModal) {
                            changePasswordModal.hide();
                        }
                    }, 1500);
                } catch (error) {
                    console.error(error);
                    showChangePasswordAlert(error.message || 'Unable to update password right now.', 'danger');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalLabel;
                    }
                }
            });
        }

        function updateInboxBadge() {
            if (!inboxCountEl) {
                return;
            }
            if (inboxState.unreadCount > 0) {
                inboxCountEl.textContent = String(inboxState.unreadCount);
                inboxCountEl.style.display = 'inline-block';
            } else {
                inboxCountEl.textContent = '0';
                inboxCountEl.style.display = 'none';
            }
        }

        function clearInboxMenu() {
            if (!inboxMenu) {
                return;
            }
            Array.from(inboxMenu.children).forEach(child => {
                if (child === inboxHeaderEl) {
                    return;
                }
                child.remove();
            });
        }

        function buildPreview(text) {
            if (!text) {
                return '';
            }
            const normalized = String(text).replace(/\s+/g, ' ').trim();
            if (normalized.length <= 100) {
                return normalized;
            }
            return normalized.slice(0, 100) + '…';
        }

        const portalBasePath = (() => {
            const path = window.location.pathname || '/';
            const marker = '/Portal/student_portal.php';
            const idx = path.toLowerCase().indexOf(marker.toLowerCase());
            if (idx !== -1) {
                return path.substring(0, idx + 1);
            }
            const lastSlash = path.lastIndexOf('/');
            return lastSlash >= 0 ? path.substring(0, lastSlash + 1) : '/';
        })();

        function resolveAnnouncementImage(path) {
            if (!path) {
                return '';
            }
            const trimmed = String(path).trim();
            if (trimmed === '') {
                return '';
            }
            if (/^https?:\/\//i.test(trimmed)) {
                return trimmed;
            }
            if (trimmed.startsWith('/')) {
                return trimmed;
            }
            return portalBasePath + trimmed.replace(/^\/+/g, '');
        }

        function appendAnnouncementBody(contentContainer, item) {
            contentContainer.innerHTML = item.body_html || '<p class="mb-0">No content available.</p>';
            if (item.image_url) {
                const wrapper = document.createElement('div');
                wrapper.className = 'mt-3 text-center';
                const imageEl = document.createElement('img');
                imageEl.src = item.image_url;
                imageEl.alt = 'Announcement image';
                imageEl.className = 'img-fluid rounded';
                wrapper.appendChild(imageEl);
                contentContainer.appendChild(wrapper);
            }
        }

        function isValidAnnouncementId(value) {
            return typeof value === 'number' && isFinite(value) && value > 0;
        }

        function renderInbox() {
            if (!inboxMenu) {
                return;
            }

            clearInboxMenu();

            if (inboxState.items.length === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'dropdown-item-text text-muted small';
                emptyState.setAttribute('data-empty-state', '');
                emptyState.textContent = "You're all caught up.";
                inboxMenu.appendChild(emptyState);
                return;
            }

            inboxState.items.forEach(item => {
                const itemWrapper = document.createElement('div');
                itemWrapper.className = 'dropdown-item-text portal-inbox-item py-2 border-bottom';
                itemWrapper.dataset.id = String(item.id);
                if (!item.is_read) {
                    itemWrapper.classList.add('portal-inbox-unread', 'bg-light');
                }

                const row = document.createElement('div');
                row.className = 'd-flex align-items-start';

                const textCol = document.createElement('div');
                textCol.className = 'flex-grow-1 me-2';

                const subjectEl = document.createElement('div');
                subjectEl.className = 'fw-semibold';
                if (!item.is_read) {
                    subjectEl.classList.add('text-success');
                }
                subjectEl.textContent = item.subject || 'Announcement';
                textCol.appendChild(subjectEl);

                const timeEl = document.createElement('div');
                timeEl.className = 'small text-muted';
                timeEl.textContent = item.sent_at || '';
                textCol.appendChild(timeEl);

                const snippetEl = document.createElement('div');
                snippetEl.className = 'small text-body-secondary';
                snippetEl.style.whiteSpace = 'normal';
                snippetEl.textContent = buildPreview(item.body_plain || '');
                textCol.appendChild(snippetEl);

                if (item.image_url) {
                    const attachmentNote = document.createElement('div');
                    attachmentNote.className = 'small text-primary fst-italic';
                    attachmentNote.textContent = 'Contains image';
                    textCol.appendChild(attachmentNote);
                }

                row.appendChild(textCol);

                const actionsCol = document.createElement('div');
                actionsCol.className = 'd-flex flex-column gap-1 align-items-end text-nowrap';

                const viewBtn = document.createElement('button');
                viewBtn.type = 'button';
                viewBtn.className = 'btn btn-sm btn-link p-0 portal-inbox-view';
                viewBtn.dataset.action = 'view';
                viewBtn.textContent = 'View';
                viewBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    openAnnouncementModal(item);
                });
                actionsCol.appendChild(viewBtn);

                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'btn btn-sm btn-link text-danger p-0 portal-inbox-delete';
                deleteBtn.dataset.action = 'delete';
                deleteBtn.textContent = 'Delete';
                deleteBtn.addEventListener('click', async function (event) {
                    event.preventDefault();
                    await handleDelete(item.id);
                });
                actionsCol.appendChild(deleteBtn);

                row.appendChild(actionsCol);
                itemWrapper.appendChild(row);
                inboxMenu.appendChild(itemWrapper);
            });
        }

        async function updateInboxStatus(action, ids) {
            const response = await fetch('Portal/update_inbox.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action, ids })
            });
            const data = await response.json();
            if (!data || !data.success) {
                throw new Error(data && data.error ? data.error : 'Unable to update inbox right now.');
            }
            return data;
        }

        async function markInboxItemsRead(ids) {
            if (!Array.isArray(ids) || ids.length === 0) {
                return;
            }
            try {
                await updateInboxStatus('mark_read', ids);
                let changed = false;
                inboxState.items = inboxState.items.map(item => {
                    if (ids.includes(item.id) && !item.is_read) {
                        changed = true;
                        return { ...item, is_read: true };
                    }
                    return item;
                });

                if (changed) {
                    inboxState.unreadCount = inboxState.items.reduce((count, item) => count + (item.is_read ? 0 : 1), 0);
                    updateInboxBadge();
                    renderInbox();
                }
            } catch (error) {
                console.error(error);
            }
        }

        async function handleDelete(id) {
            if (!id) {
                return;
            }
            if (!window.confirm('Delete this message?')) {
                return;
            }
            try {
                await updateInboxStatus('delete', [id]);
                const wasUnread = inboxState.items.some(item => item.id === id && !item.is_read);
                inboxState.items = inboxState.items.filter(item => item.id !== id);
                if (wasUnread && inboxState.unreadCount > 0) {
                    inboxState.unreadCount -= 1;
                }
                updateInboxBadge();
                renderInbox();
            } catch (error) {
                console.error(error);
                alert(error.message || 'Unable to delete this message right now.');
            }
        }

        function openAnnouncementModal(item) {
            if (!item) {
                return;
            }

            if (!item.is_read) {
                markInboxItemsRead([item.id]);
            }

            if (inboxToggle && typeof bootstrap !== 'undefined') {
                const dropdownInstance = bootstrap.Dropdown.getInstance(inboxToggle);
                if (dropdownInstance) {
                    dropdownInstance.hide();
                }
            }

            if (announcementModal) {
                announcementModalTitle.textContent = item.subject || 'Announcement';
                announcementModalTime.textContent = item.sent_at || '';
                announcementModalBody.innerHTML = '';
                appendAnnouncementBody(announcementModalBody, item);
                announcementModal.show();
            } else {
                let message = (item.subject || 'Announcement') + '\n\n' + (item.body_plain || '');
                if (item.image_url) {
                    message += '\n\n[Image attached] ' + item.image_url;
                }
                alert(message);
            }
        }

        async function loadInbox(options = {}) {
            if (!inboxMenu) {
                return;
            }

            try {
                const response = await fetch('Portal/fetch_inbox.php');
                const data = await response.json();
                if (!data || !data.success) {
                    throw new Error(data && data.error ? data.error : 'Unable to load announcements');
                }

                const items = Array.isArray(data.items) ? data.items : [];
                inboxState.items = items.map(raw => ({
                    id: Number(raw.id),
                    subject: raw.subject || 'Announcement',
                    body_html: raw.body_html || '',
                    body_plain: typeof raw.body_plain === 'string' ? raw.body_plain : '',
                    sent_at: raw.sent_at || '',
                    is_read: Boolean(Number(raw.is_read ?? 0)),
                    image_url: resolveAnnouncementImage(raw.image_path || '')
                })).filter(item => isValidAnnouncementId(item.id));
                inboxState.unreadCount = Number(data.unread_count || 0);

                updateInboxBadge();
                renderInbox();
                inboxLoaded = true;
            } catch (error) {
                console.error(error);
                if (!options.silent) {
                    alert(error.message || 'Cannot load announcements right now.');
                }
            }
        }

        async function markAllInboxAsRead() {
            if (markingInboxRead) {
                return;
            }
            const unreadIds = inboxState.items.filter(item => !item.is_read).map(item => item.id);
            if (unreadIds.length === 0) {
                return;
            }
            markingInboxRead = true;
            try {
                await markInboxItemsRead(unreadIds);
            } finally {
                markingInboxRead = false;
            }
        }

        if (inboxToggle) {
            inboxToggle.addEventListener('show.bs.dropdown', async function () {
                await loadInbox({ silent: true });
                await markAllInboxAsRead();
            });

            inboxToggle.addEventListener('click', function () {
                if (!inboxLoaded) {
                    loadInbox();
                }
            });
        }

        loadInbox();
    });
</script>

<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="changePasswordAlert" class="alert" style="display:none;"></div>
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                        <div class="form-text">Must be at least 8 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="announcementModalTitle">Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted" id="announcementModalTime"></div>
                <div class="mt-3" id="announcementModalBody"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
