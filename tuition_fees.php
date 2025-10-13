<?php
$page_title = 'Tuition Fees';
include 'db_connection.php';

$gradeOptions = [
    'preprime1' => 'Pre-Prime 1',
    'preprime2' => 'Pre-Prime 2',
    'kindergarten' => 'Kindergarten',
    'grade1' => 'Grade 1',
    'grade2' => 'Grade 2',
    'grade3' => 'Grade 3',
    'grade4' => 'Grade 4',
    'grade5' => 'Grade 5',
    'grade6' => 'Grade 6',
    'grade7' => 'Grade 7 (Junior High)',
    'grade8' => 'Grade 8',
    'grade9' => 'Grade 9',
    'grade10' => 'Grade 10',
    'grade11' => 'Grade 11 (Senior High)',
    'grade12' => 'Grade 12',
];

$pricingOptions = [
    'regular' => 'Regular Students',
    'fwc' => 'FWC Member',
    'esc' => 'ESC / Government Subsidy',
    'other' => 'Other / Custom',
];

$escOnlyGrades = ['grade7', 'grade8', 'grade9', 'grade10', 'grade11', 'grade12'];
$pricingOptionsList = [];
foreach ($pricingOptions as $value => $label) {
    $pricingOptionsList[] = ['value' => $value, 'label' => $label];
}

$planLabels = [
    'annually' => 'Annually',
    'cash' => 'Cash',
    'semi_annual' => 'Semi-Annual',
    'quarterly' => 'Quarterly',
    'monthly' => 'Monthly',
];

function tuition_grade_synonyms(string $normalized): array
{
    $normalized = strtolower($normalized);
    if ($normalized === 'preprime1') {
        return ['preprime1', 'preprime12'];
    }
    if ($normalized === 'preprime2') {
        return ['preprime2', 'preprime12'];
    }
    if ($normalized === 'preprime12') {
        return ['preprime12', 'preprime1', 'preprime2'];
    }
    $kindergartenSet = ['kindergarten', 'kinder1', 'kinder2'];
    if (in_array($normalized, $kindergartenSet, true)) {
        return $kindergartenSet;
    }
    return [$normalized];
}

$schoolYearInput = isset($_GET['school_year']) ? htmlspecialchars((string) $_GET['school_year']) : '';
$selectedGrade = $_GET['year'] ?? '';
$selectedType = $_GET['student_type'] ?? 'all';
$selectedPricingInput = $_GET['pricing_category'] ?? 'regular';

if (!array_key_exists($selectedGrade, $gradeOptions)) {
    $selectedGrade = 'preprime1';
}
$restrictPricingToEsc = in_array($selectedGrade, $escOnlyGrades, true);

if (!array_key_exists($selectedPricingInput, $pricingOptions)) {
    $selectedPricingInput = 'regular';
}

$selectedPricing = $restrictPricingToEsc ? 'esc' : $selectedPricingInput;
$pricingOptionsForDisplay = $restrictPricingToEsc ? ['esc' => $pricingOptions['esc']] : $pricingOptions;

$studentTypeOptions = [
    'all' => 'All Students',
    'new' => 'New Students',
    'old' => 'Old Students',
];

if (!array_key_exists($selectedType, $studentTypeOptions)) {
    $selectedType = 'all';
}

include 'includes/header.php';
?>
<style>
        body {
            background: linear-gradient(180deg, rgba(20, 90, 50, 0.08) 0%, rgba(255, 255, 255, 0.9) 55%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2f3e46;
        }

        .fees-main {
            padding: 72px 0 96px;
        }

        .hero-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 36px;
            box-shadow: 0 30px 60px rgba(12, 68, 49, 0.12);
            border: 1px solid rgba(20, 90, 50, 0.08);
            margin-bottom: 32px;
        }

        .hero-card h2 {
            font-weight: 700;
            color: #145A32;
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: rgba(20, 90, 50, 0.08);
            border-radius: 999px;
            color: #145A32;
            font-weight: 600;
        }

        .filter-card {
            border-radius: 22px;
            border: 1px solid rgba(20, 90, 50, 0.08);
            box-shadow: 0 18px 40px rgba(12, 68, 49, 0.10);
            overflow: hidden;
            margin-bottom: 32px;
        }

        .filter-card .card-header {
            background: linear-gradient(135deg, rgba(20, 90, 50, 0.12), rgba(251, 216, 10, 0.20));
            color: #145A32;
            font-weight: 700;
        }

        .filter-card label {
            font-weight: 600;
            color: #145A32;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            border-radius: 12px;
            border: 1px solid rgba(20, 90, 50, 0.2);
            padding: 12px 14px;
        }

        .filter-card .btn-filter {
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 600;
            background: #145A32;
            border: none;
        }

        .filter-card .btn-filter:hover {
            background: #0f4f24;
        }

        .fee-result {
            border-radius: 22px;
            overflow: hidden;
        }

        .fee-result .card-header {
            background: linear-gradient(135deg, rgba(20, 90, 50, 0.14), rgba(251, 216, 10, 0.18));
            color: #145A32;
        }

        .fee-result .badge-plan {
            background: rgba(255, 255, 255, 0.25);
            color: #145A32;
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 0.85rem;
        }

        .plan-summary {
            border-radius: 18px;
            border: 1px solid rgba(20, 90, 50, 0.12);
            padding: 18px;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
        }

        .plan-summary h4 {
            font-size: 1.1rem;
            color: #145A32;
            margin-bottom: 4px;
        }

        .plan-summary .sub-text {
            font-size: 0.84rem;
            color: #6c757d;
        }

        .table-fees table thead {
            background: #145A32;
            color: #fff;
        }

        .table-fees table tbody tr:hover {
            background: rgba(20, 90, 50, 0.06);
        }

        .info-alert,
        .empty-alert {
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-alert {
            border: 1px solid rgba(20, 90, 50, 0.15);
            background: rgba(20, 90, 50, 0.05);
            color: #145A32;
        }

        .empty-alert {
            border: 1px solid rgba(231, 76, 60, 0.2);
            background: rgba(231, 76, 60, 0.08);
            color: #c0392b;
        }

        .plan-tab-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .plan-tab {
            border: 1px solid rgba(20, 90, 50, 0.15);
            background: #fff;
            padding: 12px 18px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 140px;
            transition: all 0.2s ease;
        }

        .plan-tab:hover,
        .plan-tab:focus {
            border-color: #0b5d1e;
            background: rgba(20, 90, 50, 0.12);
            box-shadow: 0 12px 22px rgba(12, 68, 49, 0.12);
            outline: none;
        }

        .plan-tab.active {
            border-color: #145A32;
            background: rgba(20, 90, 50, 0.08);
            box-shadow: 0 10px 20px rgba(12, 68, 49, 0.12);
        }

        .plan-tab__label {
            font-weight: 600;
            color: #145A32;
        }

        .plan-tab__amount {
            font-size: 0.9rem;
            color: #2f3e46;
        }

        .plan-panel {
            display: none;
        }

        .plan-panel.active {
            display: block;
        }

        .plan-panel-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(20, 90, 50, 0.15);
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 12px 25px rgba(12, 68, 49, 0.08);
            height: 100%;
        }

        .plan-panel-note {
            margin-top: 16px;
            background: rgba(20, 90, 50, 0.06);
            border-left: 4px solid #145A32;
            padding: 12px 16px;
            border-radius: 10px;
            color: #145A32;
        }

        @media (max-width: 767px) {
            .hero-card,
            .filter-card,
            .fee-result {
                border-radius: 18px;
            }

            .hero-card {
                padding: 28px 24px;
            }

            .plan-summary {
                text-align: center;
            }

            .plan-summary .sub-text {
                display: block;
            }
        }
    </style>
    <main class="fees-main">
        <div class="container">
            <section class="hero-card">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <span class="hero-pill"><i class="bi bi-graph-up-arrow"></i> Updated tuition matrix</span>
                        <h2 class="mt-3 mb-3">Tuition &amp; Fees Per Grade Level</h2>
                        <p class="mb-0 text-muted">Preview the latest entrance, miscellaneous, and tuition fees per student type. Pick a school year to see the exact breakdown.</p>
                    </div>
                    <div class="text-lg-end">
                        <img src="assets/img/student-neutral.svg" alt="Students" width="120" class="d-none d-lg-block">
                    </div>
                </div>
            </section>

            <div class="card filter-card">
                <div class="card-header py-3 px-4">
                    <i class="bi bi-funnel me-2"></i>Filter fees
                </div>
                <div class="card-body p-4">
                    <form method="GET" class="row g-4 align-items-end">
                        <div class="col-md-4 col-lg-3">
                            <label for="school_year" class="form-label">School Year</label>
                            <input type="text" name="school_year" id="school_year" class="form-control" value="<?= $schoolYearInput ?>" placeholder="e.g., 2024-2025" required>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <label for="year" class="form-label">Grade Level</label>
                            <select name="year" id="year" class="form-select">
                                <?php foreach ($gradeOptions as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $selectedGrade === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <label for="student_type" class="form-label">Student Category</label>
                            <select name="student_type" id="student_type" class="form-select">
                                <?php foreach ($studentTypeOptions as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $selectedType === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <label for="pricing_category" class="form-label">Pricing Variant</label>
                            <select name="pricing_category" id="pricing_category" class="form-select">
                                <?php foreach ($pricingOptionsForDisplay as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $selectedPricing === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 col-lg-3 d-grid">
                            <button type="submit" class="btn btn-filter">
                                <i class="bi bi-search me-2"></i>Apply Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            if (isset($_GET['school_year'], $_GET['student_type'], $_GET['year'])) {
                $school_year = trim((string) $_GET['school_year']);
                $student_type = trim((string) $_GET['student_type']);
                $pricing_category = trim((string) $selectedPricing);
                $year = trim((string) $_GET['year']);

                $normalizedYear = strtolower(str_replace([' ', '-', '_'], '', $year));

                $sql = "SELECT * FROM tuition_fees
                        WHERE school_year = ?
                          AND student_type = ?
                          AND pricing_category = ?
                          AND REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') = ?
                        ORDER BY updated_at DESC";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    echo '<div class=\'empty-alert\'><i class=\'bi bi-exclamation-octagon\'></i> Unable to prepare tuition query. Please contact the administrator.</div>';
                } else {
                    $fetchedRows = [];
                    foreach (tuition_grade_synonyms($normalizedYear) as $gradeKeyCandidate) {
                        $stmt->bind_param('ssss', $school_year, $student_type, $pricing_category, $gradeKeyCandidate);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            $fetchedRows[$row['id']] = $row;
                        }
                    }
                    // $stmt->close(); // Removed duplicate close, will close after all processing

                    if (!empty($fetchedRows)) {
                        usort($fetchedRows, static function ($a, $b) {
                            return strcmp($a['grade_level'], $b['grade_level']);
                        });
                        foreach ($fetchedRows as $row) {
                            $plansData = [];
                            $planStmt = $conn->prepare('SELECT plan_type, due_upon_enrollment, next_payment_breakdown, notes, base_snapshot FROM tuition_fee_plans WHERE tuition_fee_id = ? ORDER BY display_order ASC, plan_type ASC');
                            if ($planStmt) {
                                $feeId = (int) $row['id'];
                                $planStmt->bind_param('i', $feeId);
                                $planStmt->execute();
                                $planRes = $planStmt->get_result();
                                while ($plan = $planRes->fetch_assoc()) {
                                    $planKey = $plan['plan_type'];
                                    $label = $planLabels[$planKey] ?? ucwords(str_replace('_', ' ', $planKey));

                                    $entries = [];
                                    $decodedBreakdown = !empty($plan['next_payment_breakdown']) ? json_decode($plan['next_payment_breakdown'], true) : [];
                                    if (is_array($decodedBreakdown)) {
                                        foreach ($decodedBreakdown as $entry) {
                                            if (!isset($entry['amount'])) {
                                                continue;
                                            }
                                            $amount = (float) $entry['amount'];
                                            $note = isset($entry['note']) ? trim((string) $entry['note']) : '';
                                            $entries[] = ['amount' => $amount, 'note' => $note];
                                        }
                                    }

                                    $baseSnapshot = [];
                                    if (!empty($plan['base_snapshot'])) {
                                        $decodedBase = json_decode($plan['base_snapshot'], true);
                                        if (is_array($decodedBase)) {
                                            $baseSnapshot = $decodedBase;
                                        }
                                    }

                                    $baseEntrance = isset($baseSnapshot['entrance_fee']) ? (float) $baseSnapshot['entrance_fee'] : (float) $row['entrance_fee'];
                                    $baseMisc = isset($baseSnapshot['miscellaneous_fee']) ? (float) $baseSnapshot['miscellaneous_fee'] : (float) $row['miscellaneous_fee'];
                                    $baseTuition = isset($baseSnapshot['tuition_fee']) ? (float) $baseSnapshot['tuition_fee'] : (float) $row['tuition_fee'];
                                    $baseDueRaw = $baseEntrance + $baseMisc + $baseTuition;
                                    $planDue = (float) $plan['due_upon_enrollment'];
                                    $diff = $planDue - $baseDueRaw;

                                    $entranceNote = '';
                                    $originalEntrance = (float) $row['entrance_fee'];
                                    $pricingCategory = strtolower((string) $row['pricing_category']);

                                    if (abs($diff) > 0.009) {
                                        if ($planKey === 'cash' && $pricingCategory === 'regular' && $diff < 0) {
                                            $baseEntrance = max(0.0, $baseEntrance + $diff);
                                            $baseDueRaw = $baseEntrance + $baseMisc + $baseTuition;
                                        } else {
                                            $baseTuition += $diff;
                                            $baseDueRaw = $baseEntrance + $baseMisc + $baseTuition;
                                        }
                                    }

                                    $entranceDiff = $originalEntrance - $baseEntrance;
                                    if ($planKey === 'cash' && $pricingCategory === 'regular' && $originalEntrance > 0.009 && $entranceDiff > 0.009) {
                                        $percent = round(($entranceDiff / $originalEntrance) * 100);
                                        if ($percent > 0) {
                                            $entranceNote = $percent . '% discount applied';
                                        }
                                    }

                                    $defaultNotes = [];
                                    if ($planKey === 'monthly') {
                                        $defaultNotes = [
                                            'Next Payment August 5',
                                            'Next Payment September 5',
                                            'Next Payment October 5',
                                            'Next Payment November 5',
                                            'Next Payment December 5',
                                            'Next Payment January 5',
                                            'Next Payment February 5',
                                            'Next Payment March 5',
                                        ];
                                    } elseif ($planKey === 'quarterly') {
                                        $defaultNotes = [
                                            'Next Payment September 5',
                                            'Next Payment December 5',
                                            'Next Payment March 5',
                                        ];
                                    } elseif ($planKey === 'semi_annual') {
                                        $defaultNotes = ['Next Payment December 5'];
                                    }

                                    foreach ($entries as $idx => $entry) {
                                        if ($entry['note'] === '' && isset($defaultNotes[$idx])) {
                                            $entries[$idx]['note'] = $defaultNotes[$idx];
                                        }
                                    }

                                    $futureTotal = 0.0;
                                    foreach ($entries as $entry) {
                                        $futureTotal += (float) $entry['amount'];
                                    }
                                    $overallTotal = $planDue + $futureTotal;

                                    $plansData[$planKey] = [
                                        'label' => $label,
                                        'due' => $planDue,
                                        'entries' => $entries,
                                        'notes' => $plan['notes'] ?? '',
                                        'base' => [
                                            'entrance_fee' => $baseEntrance,
                                            'miscellaneous_fee' => $baseMisc,
                                            'tuition_fee' => $baseTuition,
                                            'due_total' => $planDue,
                                            'overall_total' => $overallTotal,
                                            'entrance_note' => $entranceNote,
                                        ],
                                    ];
                                }
                                $planStmt->close();
                            }

                            if (!$plansData) {
                                echo '<div class="empty-alert"><i class="bi bi-info-circle"></i> No tuition fees recorded yet for the selected filters. Contact the cashier to update the matrix.</div>';
                                continue;
                            }

                            // Always show all plan types, even if missing from DB
                            $allPlanKeys = array_keys($planLabels);
                            $defaultPlanKey = $allPlanKeys[0];
                            $defaultPlan = $plansData[$defaultPlanKey] ?? [
                                'label' => $planLabels[$defaultPlanKey],
                                'due' => 0,
                                'entries' => [],
                                'notes' => '',
                                'base' => [
                                    'entrance_fee' => 0,
                                    'miscellaneous_fee' => 0,
                                    'tuition_fee' => 0,
                                    'due_total' => 0,
                                    'overall_total' => 0,
                                    'entrance_note' => '',
                                ],
                            ];

                            $gradeLabel = $gradeOptions[$row['grade_level']] ?? ucwords(str_replace('_', ' ', $row['grade_level']));
                            $gradeNormalizedDisplay = strtolower(str_replace([' ', '_'], '', $row['grade_level']));
                            if (in_array($gradeNormalizedDisplay, ['kinder1', 'kinder2'], true)) {
                                $gradeLabel = 'Kindergarten';
                            } elseif ($gradeNormalizedDisplay === 'preprime12') {
                                $gradeLabel = 'Pre-Prime 1 & 2';
                            }
                            $pricingLabel = $pricingOptions[$row['pricing_category']] ?? ucwords($row['pricing_category']);
                            $studentLabel = $studentTypeOptions[$row['student_type']] ?? ucwords(str_replace('_', ' ', $row['student_type']));

                            ?>
                            <div class="card fee-result shadow-lg border-0 mb-5" data-plan-container>
                                <div class="card-header py-4 px-4">
                                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                                        <div>
                                            <span class="badge-plan mb-2"><i class="bi bi-calendar-week me-1"></i>School Year <?= htmlspecialchars($row['school_year']) ?></span>
                                            <h3 class="h5 fw-bold mb-1"><?= htmlspecialchars($gradeLabel) ?> • <?= htmlspecialchars($pricingLabel) ?></h3>
                                            <p class="mb-0">Student Category: <strong><?= htmlspecialchars($studentLabel) ?></strong></p>
                                        </div>
                                        <div class="text-lg-end">
                                            <span class="display-6 fw-bold text-success" data-plan-selected-total>₱<?= number_format($defaultPlan['due'], 2) ?></span>
                                            <div class="small text-muted" data-plan-selected-label>Due upon enrollment • <?= htmlspecialchars($defaultPlan['label']) ?></div>
                                            <div class="small text-muted">Updated <?= htmlspecialchars(substr((string) $row['updated_at'], 0, 10)) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body px-4 pb-4">
                                    <div class="plan-tab-group mb-3" role="tablist">
                                        <?php foreach ($allPlanKeys as $planKey):
                                            $planConfig = $plansData[$planKey] ?? [
                                                'label' => $planLabels[$planKey],
                                                'due' => 0,
                                                'entries' => [],
                                                'notes' => '',
                                                'base' => [
                                                    'entrance_fee' => 0,
                                                    'miscellaneous_fee' => 0,
                                                    'tuition_fee' => 0,
                                                    'due_total' => 0,
                                                    'overall_total' => 0,
                                                    'entrance_note' => '',
                                                ],
                                            ];
                                        ?>
                                            <button type="button"
                                                    class="plan-tab <?= $planKey === $defaultPlanKey ? 'active' : '' ?>"
                                                    data-plan-trigger="<?= htmlspecialchars($planKey) ?>"
                                                    data-plan-due="₱<?= number_format($planConfig['due'], 2) ?>"
                                                    data-plan-label="<?= htmlspecialchars('Due upon enrollment • ' . $planConfig['label']) ?>">
                                                <span class="plan-tab__label"><?= htmlspecialchars($planConfig['label']) ?></span>
                                                <span class="plan-tab__amount">₱<?= number_format($planConfig['due'], 2) ?></span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php foreach ($allPlanKeys as $planKey):
                                        $planConfig = $plansData[$planKey] ?? [
                                            'label' => $planLabels[$planKey],
                                            'due' => 0,
                                            'entries' => [],
                                            'notes' => '',
                                            'base' => [
                                                'entrance_fee' => 0,
                                                'miscellaneous_fee' => 0,
                                                'tuition_fee' => 0,
                                                'due_total' => 0,
                                                'overall_total' => 0,
                                                'entrance_note' => '',
                                            ],
                                        ];
                                        $base = $planConfig['base'];
                                        $entries = $planConfig['entries'];
                                    ?>
                                        <div class="plan-panel <?= $planKey === $defaultPlanKey ? 'active' : '' ?>" data-plan-panel="<?= htmlspecialchars($planKey) ?>">
                                            <div class="row g-3">
                                                <div class="col-12 col-lg-6">
                                                    <div class="plan-panel-card">
                                                        <h4 class="fw-semibold mb-2">What you pay today</h4>
                                                        <?php if ($planConfig['due'] > 0): ?>
                                                            <p class="mb-1"><strong>Entrance Fee:</strong> ₱<?= number_format($base['entrance_fee'], 2) ?>
                                                                <?php if (!empty($base['entrance_note'])): ?>
                                                                    <span class="text-success">(<?= htmlspecialchars($base['entrance_note']) ?>)</span>
                                                                <?php endif; ?>
                                                            </p>
                                                            <p class="mb-1"><strong>Miscellaneous Fee:</strong> ₱<?= number_format($base['miscellaneous_fee'], 2) ?></p>
                                                            <p class="mb-1"><strong>Tuition Portion:</strong> ₱<?= number_format($base['tuition_fee'], 2) ?></p>
                                                            <p class="mb-0 mt-2"><strong>Due upon enrollment:</strong> ₱<?= number_format($base['due_total'], 2) ?></p>
                                                        <?php else: ?>
                                                            <p class="mb-0">No breakdown available for this plan.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-lg-6">
                                                    <div class="plan-panel-card">
                                                        <h4 class="fw-semibold mb-2">Remaining balance</h4>
                                                        <?php if (!empty($entries)): ?>
                                                            <p class="mb-1">Future payments total: ₱<?= number_format($base['overall_total'] - $base['due_total'], 2) ?></p>
                                                        <?php elseif ($planConfig['due'] > 0): ?>
                                                            <p class="mb-1">No remaining payments after enrollment.</p>
                                                        <?php else: ?>
                                                            <p class="mb-1">No breakdown available for this plan.</p>
                                                        <?php endif; ?>
                                                        <p class="mb-0"><strong>Overall program cost:</strong> ₱<?= number_format($base['overall_total'], 2) ?></p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="table-fees table-responsive mt-4">
                                                <table class="table table-bordered align-middle">
                                                    <thead class="table-success text-center">
                                                        <tr>
                                                            <th scope="col">Schedule</th>
                                                            <th scope="col">Amount</th>
                                                            <th scope="col">Notes</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (!empty($entries)): ?>
                                                            <?php foreach ($entries as $entry): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($planConfig['label']) ?></td>
                                                                    <td>₱<?= number_format($entry['amount'], 2) ?></td>
                                                                    <td><?= $entry['note'] !== '' ? htmlspecialchars($entry['note']) : '—' ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php elseif ($planConfig['due'] > 0): ?>
                                                            <tr>
                                                                <td colspan="3" class="text-center">No future payments recorded for this plan.</td>
                                                            </tr>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="3" class="text-center">No breakdown available for this plan.</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <?php if (!empty($planConfig['notes'])): ?>
                                                <div class="plan-panel-note">
                                                    <strong>Notes:</strong> <?= nl2br(htmlspecialchars($planConfig['notes'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="empty-alert"><i class="bi bi-info-circle"></i> No tuition fees recorded yet for the selected filters. Contact the cashier to update the matrix.</div>';
                    }

                    $stmt->close();
                }
            } else {
                echo '<div class="info-alert"><i class="bi bi-exclamation-triangle"></i> Choose a school year, grade level, pricing variant, and student category to preview the tuition breakdown.</div>';
            }
            ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const gradeSelect = document.getElementById('year');
            const pricingSelect = document.getElementById('pricing_category');
            const escOnlyGrades = new Set(<?= json_encode($escOnlyGrades) ?>);
            const pricingVariants = <?= json_encode($pricingOptionsList) ?>;
            let lastFreeSelection = 'regular';

            const renderPricingOptions = () => {
                if (!gradeSelect || !pricingSelect) {
                    return;
                }

                const requiresEsc = escOnlyGrades.has(gradeSelect.value);
                let currentSelection = pricingSelect.value || 'regular';

                if (!requiresEsc && currentSelection !== 'esc') {
                    lastFreeSelection = currentSelection;
                }

                if (requiresEsc) {
                    currentSelection = 'esc';
                } else if (currentSelection === 'esc') {
                    currentSelection = lastFreeSelection || 'regular';
                }

                pricingSelect.innerHTML = '';
                const variantsToRender = requiresEsc
                    ? pricingVariants.filter((option) => option.value === 'esc')
                    : pricingVariants;

                variantsToRender.forEach(({ value, label }) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    option.selected = value === currentSelection;
                    pricingSelect.appendChild(option);
                });

                if (!requiresEsc && pricingSelect.value !== 'esc') {
                    lastFreeSelection = pricingSelect.value;
                }
            };

            if (gradeSelect && pricingSelect) {
                if (pricingSelect.value && pricingSelect.value !== 'esc') {
                    lastFreeSelection = pricingSelect.value;
                }

                gradeSelect.addEventListener('change', renderPricingOptions);
                pricingSelect.addEventListener('change', () => {
                    if (!escOnlyGrades.has(gradeSelect.value) && pricingSelect.value !== 'esc') {
                        lastFreeSelection = pricingSelect.value;
                    }
                });

                renderPricingOptions();
            }

            document.querySelectorAll('[data-plan-container]').forEach(function (container) {
                const tabs = Array.from(container.querySelectorAll('[data-plan-trigger]'));
                const panels = Array.from(container.querySelectorAll('[data-plan-panel]'));
                const totalEl = container.querySelector('[data-plan-selected-total]');
                const labelEl = container.querySelector('[data-plan-selected-label]');

                tabs.forEach(function (tab) {
                    tab.addEventListener('click', function () {
                        const target = tab.getAttribute('data-plan-trigger');

                        tabs.forEach(function (btn) {
                            btn.classList.toggle('active', btn === tab);
                        });

                        panels.forEach(function (panel) {
                            panel.classList.toggle('active', panel.getAttribute('data-plan-panel') === target);
                        });

                        if (totalEl && tab.dataset.planDue) {
                            totalEl.textContent = tab.dataset.planDue;
                        }
                        if (labelEl && tab.dataset.planLabel) {
                            labelEl.textContent = tab.dataset.planLabel;
                        }
                    });
                });
            });
        });
    </script>
<?php include 'includes/footer.php'; ?>
