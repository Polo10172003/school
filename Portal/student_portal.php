<?php
session_start();

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

$sql = "SELECT id, firstname, lastname, year, section, adviser, student_type, gender, emailaddress
        FROM students_registration 
        WHERE student_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_number);
$stmt->execute();
$stmt->bind_result($student_id, $firstname, $lastname, $year, $section, $adviser, $student_type, $gender, $student_email_portal);
$stmt->fetch();
$stmt->close();

if (!$student_id) {
    die("Student account not found in students_registration.");
}

$portal_student_name = trim($firstname . ' ' . $lastname);
$header_variant = 'student_portal';

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

    if (strtolower((string) $row['payment_status']) === 'paid') {
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

$previous_grade_key = $previous_grade_label ? cashier_normalize_grade_key((string) $previous_grade_label) : null;

$paid_current_total = isset($paidByGrade[$grade_key]) ? $sumAmounts($paidByGrade[$grade_key]) : 0.0;
$paid_previous_total = ($previous_grade_key && isset($paidByGrade[$previous_grade_key])) ? $sumAmounts($paidByGrade[$previous_grade_key]) : 0.0;
$paid_unassigned_total = $sumAmounts($unassignedPaid);

$paid_other_total = 0.0;
foreach ($paidByGrade as $gradeKey => $rows) {
    if ($gradeKey === $grade_key) {
        continue;
    }
    if ($previous_grade_key && $gradeKey === $previous_grade_key) {
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
        $normalizedPaymentGrade === $grade_key &&
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
    } elseif (
        $previous_grade_key &&
        $normalizedPaymentGrade !== '' &&
        $normalizedPaymentGrade !== $previous_grade_key &&
        $normalizedPaymentGrade !== $grade_key &&
        $remaining_prev_from_other > 0 &&
        $remaining_prev_allocation > 0
    ) {
        $apply_prev = min($amount_remaining, $remaining_prev_from_other, $remaining_prev_allocation);
        if ($apply_prev > 0) {
            $record = $entry;
            $record['applied_amount'] = $apply_prev;
            $record['source_amount'] = $original_amount;
            $record['applied_to'] = $previous_grade_label;
            $record['is_partial'] = $apply_prev < $original_amount;
            $paid_history_previous[] = $record;
            $remaining_prev_from_other -= $apply_prev;
            $remaining_prev_allocation -= $apply_prev;
            $amount_remaining -= $apply_prev;
        }
    }

    if ($amount_remaining <= 0) {
        continue;
    }

    $apply_current = 0.0;
    $applied_to_label = $year;

    if ($normalizedPaymentGrade === $grade_key) {
        $apply_current = min($amount_remaining, $remaining_current_allocation);
    } elseif ($normalizedPaymentGrade === '' && $remaining_unassigned_current > 0) {
        $apply_current = min($amount_remaining, $remaining_unassigned_current, $remaining_current_allocation);
        $remaining_unassigned_current -= $apply_current;
    } elseif ($normalizedPaymentGrade !== '' && $normalizedPaymentGrade !== $previous_grade_key) {
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

$financial_snapshot = cashier_dashboard_build_student_financial($conn, (int) $student_id, [
    'pricing_variant' => $pricing_variant_param,
    'pricing_student' => $student_id,
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
                <?php if ($has_multiple_views): ?>
                    <div class="d-flex justify-content-end">
                        <form method="GET" action="Portal/student_portal.php" class="w-100" style="max-width: 240px;">
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
                                <form action="Portal/choose_payment.php" method="GET" class="ms-lg-auto">
                                    <input type="hidden" name="student_id" value="<?php echo (int) $student_id; ?>">
                                    <input type="hidden" name="pricing_variant" value="<?php echo htmlspecialchars($selected_pricing_key); ?>">
                                    <button type="submit" class="btn btn-success fw-semibold">
                                        <i class="bi bi-credit-card me-2"></i>Pay Now
                                    </button>
                                </form>
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
    document.addEventListener('DOMContentLoacded', function () {
        const changePasswordBtn = document.getElementById('portalChangePassword');
        const inboxToggle = document.getElementById('studentInboxToggle');
        const inboxMenu = document.getElementById('studentInboxMenu');
        const inboxCountEl = document.getElementById('studentInboxCount');
        let inboxLoaded = false;

        if (changePasswordBtn) {
            changePasswordBtn.addEventListener('click', async function () {
                const originalText = changePasswordBtn.textContent;
                changePasswordBtn.disabled = true;
                changePasswordBtn.textContent = 'Sending…';
                try {
                    const response = await fetch('Portal/request_password_reset.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: '{}'
                    });
                    const data = await response.json();
                    if (data && data.success) {
                        alert('A confirmation email has been sent. Please check your inbox within the next 2 minutes.');
                    } else {
                        alert(data && data.error ? data.error : 'Unable to send reset email right now.');
                    }
                } catch (error) {
                    console.error(error);
                    alert('Something went wrong while sending the reset email.');
                } finally {
                    changePasswordBtn.disabled = false;
                    changePasswordBtn.textContent = originalText;
                }
            });
        }

        async function loadInbox() {
            if (inboxLoaded) {
                return;
            }
            try {
                const response = await fetch('Portal/fetch_inbox.php');
                const data = await response.json();
                if (!data || !data.success) {
                    throw new Error(data && data.error ? data.error : 'Unable to load announcements');
                }

                const items = data.items || [];
                inboxMenu.querySelectorAll('[data-empty-state]').forEach(el => el.remove());
                if (items.length === 0) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'dropdown-item-text text-muted small';
                    emptyState.setAttribute('data-empty-state', '');
                    emptyState.textContent = "You're all caught up.";
                    inboxMenu.appendChild(emptyState);
                } else {
                    items.forEach(item => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'dropdown-item-text';
                        wrapper.innerHTML = `<div class="fw-semibold">${item.subject}</div>
                            <div class="small text-muted">${item.sent_at}</div>
                            <div class="small" style="white-space: normal;">${item.body}</div>`;
                        inboxMenu.appendChild(wrapper);
                    });
                }

                if (items.length > 0 && inboxCountEl) {
                    inboxCountEl.textContent = items.length;
                    inboxCountEl.style.display = 'inline-block';
                }

                inboxLoaded = true;
            } catch (error) {
                console.error(error);
                alert(error.message || 'Cannot load announcements right now.');
            }
        }

        if (inboxToggle) {
            inboxToggle.addEventListener('click', function () {
                loadInbox();
            });
        }

        // Auto logout if student navigates away or closes tab (disabled intentionally)
        window.addEventListener('blur', function () {
             fetch('Portal/logout.php')
                 .then(function () {
                     window.location.href = 'Portal/student_login.php';
                 });
        // });
    });
</script>

<?php include '../includes/footer.php'; ?>
