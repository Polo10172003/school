<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';

if (!isset($_SESSION['registrar_username'])) {
    header('Location: registrar_login.php');
    exit();
}

require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../Cashier/cashier_dashboard_logic.php';

if (!function_exists('portal_stmt_fetch_all')) {
    /**
     * Fetch all rows from a prepared mysqli statement without relying on mysqlnd.
     *
     * @return array<int,array<string,mixed>>
     */
    function portal_stmt_fetch_all(mysqli_stmt $stmt): array
    {
        $rows = [];
        $meta = $stmt->result_metadata();
        if (!$meta) {
            return $rows;
        }

        $fields = [];
        while ($field = $meta->fetch_field()) {
            $fields[] = $field->name;
        }
        $meta->free();

        if (empty($fields)) {
            return $rows;
        }

        $data = [];
        $binds = [];
        foreach ($fields as $name) {
            $data[$name] = null;
            $binds[] = &$data[$name];
        }

        call_user_func_array([$stmt, 'bind_result'], $binds);

        while ($stmt->fetch()) {
            $row = [];
            foreach ($fields as $name) {
                $row[$name] = $data[$name];
            }
            $rows[] = $row;
        }

        return $rows;
    }
}

if (!function_exists('portal_stmt_fetch_assoc')) {
    /**
     * Fetch the first row from a prepared statement as an associative array.
     *
     * @return array<string,mixed>|null
     */
    function portal_stmt_fetch_assoc(mysqli_stmt $stmt): ?array
    {
        $rows = portal_stmt_fetch_all($stmt);
        return $rows[0] ?? null;
    }
}

if (!function_exists('portal_fetch_class_schedule')) {
    /**
     * Fetch class schedule rows and school year similar to the portal view.
     *
     * @return array{entries:array<int,array<string,mixed>>,school_year:?string}
     */
    function portal_fetch_class_schedule(
        mysqli $conn,
        string $gradeLevel,
        string $normalizedGradeKey,
        ?string $section,
        ?string $studentSchoolYear
    ): array {
        $result = [
            'entries' => [],
            'school_year' => null,
        ];

        $gradeLevel = trim($gradeLevel);
        $normalizedGradeKey = trim($normalizedGradeKey);

        if ($gradeLevel === '' && $normalizedGradeKey === '') {
            return $result;
        }

        $gradeCandidates = [];
        if ($normalizedGradeKey !== '') {
            $gradeCandidates = cashier_grade_synonyms($normalizedGradeKey);
            if (!in_array($normalizedGradeKey, $gradeCandidates, true)) {
                $gradeCandidates[] = $normalizedGradeKey;
            }
        }
        if ($gradeLevel !== '' && !in_array($gradeLevel, $gradeCandidates, true)) {
            $gradeCandidates[] = $gradeLevel;
        }
        $gradeCandidates = array_values(array_unique(array_filter($gradeCandidates, static function ($value) {
            return trim((string) $value) !== '';
        })));

        if (empty($gradeCandidates)) {
            return $result;
        }

        $scheduleEntries = [];
        $resolvedSchoolYear = null;
        $sectionLookup = $section !== null ? strtolower(trim($section)) : null;

        foreach ($gradeCandidates as $candidate) {
            $gradeToken = strtolower(str_replace([' ', '-', '_'], '', (string) $candidate));
            $gradeToken = preg_replace('/[^a-z0-9]/', '', $gradeToken);
            if ($gradeToken === '') {
                continue;
            }

            $gradeTokenAdjusted = str_replace('primary', 'prime', $gradeToken);

            $yearSql = "
                SELECT school_year
                FROM class_schedules
                WHERE (
                        REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') COLLATE utf8mb4_uca1400_ai_ci = ?
                     OR INSTR(REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') COLLATE utf8mb4_uca1400_ai_ci, ?) > 0
                     OR REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') COLLATE utf8mb4_uca1400_ai_ci = ?
                     OR INSTR(REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') COLLATE utf8mb4_uca1400_ai_ci, ?) > 0
                    )
                ORDER BY updated_at DESC
                LIMIT 1
            ";
            $yearStmt = $conn->prepare($yearSql);
            if ($yearStmt) {
                $yearStmt->bind_param('ssss', $gradeToken, $gradeToken, $gradeTokenAdjusted, $gradeTokenAdjusted);
                if ($yearStmt->execute()) {
                    $row = portal_stmt_fetch_assoc($yearStmt);
                    if ($row && !empty($row['school_year'])) {
                        $resolvedSchoolYear = (string) $row['school_year'];
                    }
                }
                $yearStmt->close();
            }

            if (!$resolvedSchoolYear) {
                continue;
            }

            $scheduleSql = "
                SELECT section, subject, teacher, day_of_week, start_time, end_time, room
                FROM class_schedules
                WHERE (
                        REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') COLLATE utf8mb4_uca1400_ai_ci = ?
                     OR INSTR(REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') COLLATE utf8mb4_uca1400_ai_ci, ?) > 0
                     OR REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') COLLATE utf8mb4_uca1400_ai_ci = ?
                     OR INSTR(REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') COLLATE utf8mb4_uca1400_ai_ci, ?) > 0
                    )
                  AND school_year = ?
                ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
                         start_time IS NULL, start_time
            ";
            $scheduleStmt = $conn->prepare($scheduleSql);
            if ($scheduleStmt) {
                $scheduleStmt->bind_param('sssss', $gradeToken, $gradeToken, $gradeTokenAdjusted, $gradeTokenAdjusted, $resolvedSchoolYear);
                if ($scheduleStmt->execute()) {
                    $entries = portal_stmt_fetch_all($scheduleStmt);
                    if (!empty($entries)) {
                        $scheduleEntries = $entries;
                        break;
                    }
                }
                $scheduleStmt->close();
            }

            $resolvedSchoolYear = null;
        }

        if (empty($scheduleEntries) && $gradeLevel !== '') {
            $fallbackSql = "
                SELECT subject, teacher, day_of_week, start_time, end_time, room, section, school_year
                FROM class_schedules
                WHERE grade_level = ?
            ";
            $conditions = [$gradeLevel];
            $types = 's';

            if ($studentSchoolYear !== null && $studentSchoolYear !== '') {
                $fallbackSql .= " AND (school_year = ? OR school_year IS NULL OR school_year = '')";
                $conditions[] = $studentSchoolYear;
                $types .= 's';
            }

            $fallbackSql .= "
                ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
                         start_time IS NULL, start_time
            ";

            $fallbackStmt = $conn->prepare($fallbackSql);
            if ($fallbackStmt) {
                $fallbackStmt->bind_param($types, ...$conditions);
                if ($fallbackStmt->execute()) {
                    $fallbackEntries = portal_stmt_fetch_all($fallbackStmt);
                    if (!empty($fallbackEntries)) {
                        $scheduleEntries = $fallbackEntries;
                        if (!$resolvedSchoolYear) {
                            foreach ($scheduleEntries as $row) {
                                $rowYear = trim((string) ($row['school_year'] ?? ''));
                                if ($rowYear !== '') {
                                    $resolvedSchoolYear = $rowYear;
                                    break;
                                }
                            }
                        }
                    }
                }
                $fallbackStmt->close();
            }
        }

        if (empty($scheduleEntries)) {
            if ($studentSchoolYear !== null && $studentSchoolYear !== '') {
                $resolvedSchoolYear = $studentSchoolYear;
            }
            return $result;
        }

        if ($sectionLookup !== null && $sectionLookup !== '') {
            $sectionCandidates = [$sectionLookup];
            $mutations = [];
            $mutations[] = preg_replace('/\bsection\b/i', '', $sectionLookup);
            $mutations[] = preg_replace('/\bsec\b/i', '', $sectionLookup);
            $mutations[] = preg_replace('/(section|sec|sect|section-)/i', '', $sectionLookup);
            $mutations[] = str_replace(['section', 'sec', '-'], ' ', $sectionLookup);

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

            if (!in_array('all', $sectionCandidates, true)) {
                $sectionCandidates[] = 'all';
            }

            $filtered = [];
            foreach ($scheduleEntries as $entry) {
                $entrySection = strtolower(trim((string) ($entry['section'] ?? '')));
                if ($entrySection === '' || in_array($entrySection, $sectionCandidates, true)) {
                    $filtered[] = $entry;
                }
            }

            if (!empty($filtered)) {
                $scheduleEntries = $filtered;
            }
        }

        $result['entries'] = array_values($scheduleEntries);
        $result['school_year'] = $resolvedSchoolYear ?? $studentSchoolYear;

        return $result;
    }
}

$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
$studentNumberParam = isset($_GET['student_number']) ? trim((string) $_GET['student_number']) : '';

if ($studentId <= 0 && $studentNumberParam !== '') {
    $lookup = $conn->prepare('SELECT id FROM students_registration WHERE student_number = ? LIMIT 1');
    if ($lookup) {
        $lookup->bind_param('s', $studentNumberParam);
        $lookup->execute();
        $rowLookup = portal_stmt_fetch_assoc($lookup);
        if ($rowLookup) {
            $studentId = (int) ($rowLookup['id'] ?? 0);
        }
        $lookup->close();
    }
}

if ($studentId <= 0) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Student Not Found</title></head><body><p>Missing or invalid student identifier.</p></body></html>';
    exit();
}

$studentStmt = $conn->prepare('SELECT id, student_number, firstname, lastname, year, section, adviser, student_type, enrollment_status, academic_status, school_year, lrn FROM students_registration WHERE id = ? LIMIT 1');
if (!$studentStmt) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body><p>Unable to prepare student lookup.</p></body></html>';
    exit();
}
$studentStmt->bind_param('i', $studentId);
$studentStmt->execute();
$studentRow = portal_stmt_fetch_assoc($studentStmt);
$studentStmt->close();

if (!$studentRow) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Student Not Found</title></head><body><p>Student record could not be located.</p></body></html>';
    exit();
}

$studentSchoolYear = trim((string) ($studentRow['school_year'] ?? ''));
$gradeLevel = (string) ($studentRow['year'] ?? '');
$normalizedGradeKey = cashier_normalize_grade_key($gradeLevel);

$financialSnapshot = cashier_dashboard_build_student_financial($conn, (int) $studentRow['id'], [
    'student_row' => $studentRow,
    'require_explicit_plan' => false,
    'pricing_student' => $studentRow['id'],
]);

$currentView = null;
$planTabs = [];
$activePlanKey = null;
$planStoredKey = null;
$selectedPlanSummary = null;
$scheduleRowsView = [];
$paidHistoryCurrent = [];
$pendingRowsView = [];
$currentYearTotal = 0.0;
$totalPaid = 0.0;
$remainingBalance = 0.0;
$previousOutstanding = 0.0;
$planLabelDisplay = '';

if ($financialSnapshot) {
    $planTabs = $financialSnapshot['plan_tabs'] ?? [];
    $planStoredKey = !empty($financialSnapshot['stored_plan']) ? strtolower((string)$financialSnapshot['stored_plan']) : null;
    $views = $financialSnapshot['views'] ?? [];
    foreach ($views as $view) {
        if (($view['key'] ?? '') === 'current') {
            $currentView = $view;
            break;
        }
    }
}

if ($currentView) {
    $planLabelDisplay = (string) ($currentView['plan_label'] ?? '');
    $planTabs = $currentView['plan_tabs'] ?? $planTabs;
    $activePlanKey = !empty($currentView['active_plan']) ? strtolower((string)$currentView['active_plan']) : $planStoredKey;
    $scheduleRowsView = $currentView['schedule_rows'] ?? [];
    $pendingRowsView = $currentView['pending_rows'] ?? [];
    $paidHistoryCurrent = $currentView['history_rows'] ?? [];
    $currentYearTotal = (float) ($currentView['current_year_total'] ?? 0.0);
    $totalPaid = (float) ($currentView['total_paid'] ?? 0.0);
    $remainingBalance = (float) ($currentView['remaining_balance'] ?? 0.0);
    $previousOutstanding = (float) ($currentView['previous_outstanding'] ?? 0.0);
}

if (empty($planTabs) && $financialSnapshot && !empty($financialSnapshot['plan_tabs'])) {
    $planTabs = $financialSnapshot['plan_tabs'];
}

if (!empty($planTabs)) {
    if ($activePlanKey) {
        foreach ($planTabs as $candidatePlan) {
            $candidateKey = strtolower((string) ($candidatePlan['plan_type'] ?? ''));
            if ($candidateKey !== '' && $candidateKey === $activePlanKey) {
                $selectedPlanSummary = $candidatePlan;
                break;
            }
        }
    }
    if ($selectedPlanSummary === null) {
        $selectedPlanSummary = $planTabs[0];
    }
}

$planBase = $selectedPlanSummary['base'] ?? [];
$planEntries = $selectedPlanSummary['entries'] ?? [];
$planTypeLabel = $selectedPlanSummary['label'] ?? ($selectedPlanSummary['plan_type'] ?? 'Payment Plan');
$dueUponEnrollment = (float) ($planBase['due_total'] ?? 0.0);
$overallAssessment = (float) ($planBase['overall_total'] ?? ($currentYearTotal ?: 0.0));

if ($overallAssessment <= 0.0) {
    $overallAssessment = $currentYearTotal > 0 ? $currentYearTotal : $dueUponEnrollment;
}

$feeBreakdown = [
    'Entrance Fee' => (float) ($planBase['entrance_fee'] ?? 0.0),
    'Miscellaneous Fee' => (float) ($planBase['miscellaneous_fee'] ?? 0.0),
    'Tuition Fee' => (float) ($planBase['tuition_fee'] ?? 0.0),
];

$paymentsRows = [];
$paymentStmt = $conn->prepare('SELECT payment_date, created_at, payment_type, payment_status, amount, reference_number, or_number, grade_level, school_year FROM student_payments WHERE student_id = ? ORDER BY created_at ASC');
if ($paymentStmt) {
    $paymentStmt->bind_param('i', $studentRow['id']);
    if ($paymentStmt->execute()) {
        $paymentsRows = portal_stmt_fetch_all($paymentStmt);
    }
    $paymentStmt->close();
}

$gradeScope = [];
if ($normalizedGradeKey !== '') {
    $gradeScope = cashier_grade_synonyms($normalizedGradeKey);
    if (!in_array($normalizedGradeKey, $gradeScope, true)) {
        $gradeScope[] = $normalizedGradeKey;
    }
}
$normalizedGradeLevel = cashier_normalize_grade_key($gradeLevel);
if ($normalizedGradeLevel !== '' && !in_array($normalizedGradeLevel, $gradeScope, true)) {
    $gradeScope[] = $normalizedGradeLevel;
}
$gradeScope = array_values(array_unique(array_filter($gradeScope, static function ($value): bool {
    return $value !== '';
})));

$paymentsFiltered = [];
$paymentsTotal = 0.0;
foreach ($paymentsRows as $paymentRow) {
    $paymentAmount = (float) ($paymentRow['amount'] ?? 0.0);
    if ($paymentAmount <= 0.0) {
        continue;
    }

    $paymentGradeRaw = (string) ($paymentRow['grade_level'] ?? '');
    $paymentGradeNormalized = cashier_normalize_grade_key($paymentGradeRaw);
    $paymentSchoolYear = trim((string) ($paymentRow['school_year'] ?? ''));
    $matchesScope = false;

    if (!empty($gradeScope) && $paymentGradeNormalized !== '') {
        $matchesScope = in_array($paymentGradeNormalized, $gradeScope, true);
    } elseif ($paymentGradeNormalized === '' && $paymentGradeRaw !== '') {
        $matchesScope = strcasecmp($paymentGradeRaw, (string) $gradeLevel) === 0;
    }

    if (!$matchesScope && $paymentGradeNormalized === '' && $paymentGradeRaw === '') {
        if ($studentSchoolYear !== '' && $paymentSchoolYear !== '') {
            $matchesScope = strcasecmp($paymentSchoolYear, $studentSchoolYear) === 0;
        } elseif ($studentSchoolYear === '') {
            $matchesScope = true;
        }
    }

    if (!$matchesScope) {
        continue;
    }

    $paymentsFiltered[] = $paymentRow;
    $paymentsTotal += $paymentAmount;
}
$paymentsRows = $paymentsFiltered;

$netAssessment = max($overallAssessment - $paymentsTotal, 0.0);
$netAssessment = $remainingBalance > 0 ? $remainingBalance : $netAssessment;

$scheduleData = portal_fetch_class_schedule(
    $conn,
    $gradeLevel,
    $normalizedGradeKey,
    $studentRow['section'] ?? null,
    $studentSchoolYear !== '' ? $studentSchoolYear : null
);
$classScheduleRows = $scheduleData['entries'];
$classScheduleYear = $scheduleData['school_year'];

$studentFullName = trim(($studentRow['firstname'] ?? '') . ' ' . ($studentRow['lastname'] ?? ''));
$studentFullNameUpper = strtoupper($studentFullName);
$studentNumber = $studentRow['student_number'] ?? '';
$lrn = $studentRow['lrn'] ?? '';
$sectionLabel = $studentRow['section'] ?? 'Not Assigned';
$studentType = ucfirst((string) ($studentRow['student_type'] ?? ''));
$assessmentDate = strtoupper(date('M-d-Y'));
$assessmentDateLong = date('F d, Y');
$registrarName = $_SESSION['registrar_fullname'] ?? ($_SESSION['registrar_username'] ?? 'Registrar');

$weekdayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'TBA'];
$groupedSchedule = [];
foreach ($classScheduleRows as $entry) {
    $dayKey = trim((string) ($entry['day_of_week'] ?? ''));
    $dayKey = $dayKey !== '' ? ucwords(strtolower($dayKey)) : 'TBA';
    $groupedSchedule[$dayKey][] = $entry;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Assessment - <?= htmlspecialchars($studentFullName) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        :root {
            --brand-green: #145A32;
            --brand-gold: #fbd80a;
            --text-muted: #4a5a58;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f1f5f9;
            color: #1f2937;
            margin: 0;
            padding: 20px;
        }
        .assessment-wrapper {
            max-width: 960px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }
        header.assessment-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 24px 32px;
            border-bottom: 6px solid var(--brand-green);
            position: relative;
        }
        header.assessment-header img {
            width: 76px;
            height: 76px;
            object-fit: contain;
        }
        header.assessment-header .header-text {
            flex: 1;
        }
        header.assessment-header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
        }
        header.assessment-header h2 {
            margin: 2px 0 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--brand-green);
        }
        header.assessment-header p {
            margin: 6px 0 0;
            font-size: 12px;
            color: var(--text-muted);
        }
        .print-actions {
            text-align: right;
            margin-bottom: 16px;
        }
        .print-actions button {
            background: var(--brand-green);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 10px 18px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 8px 16px rgba(20, 90, 50, 0.18);
        }
        .assessment-body {
            padding: 32px;
        }
        .section-title {
            margin: 28px 0 14px;
            font-size: 18px;
            font-weight: 700;
            color: var(--brand-green);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px 24px;
            margin-bottom: 16px;
        }
        .info-grid div {
            font-size: 13px;
            color: #1f2937;
        }
        .info-grid span {
            display: block;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 11px;
        }
        table.assessment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 13px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .empty-state {
            border: 1px dashed rgba(20, 90, 50, 0.3);
            border-radius: 8px;
            padding: 14px 16px;
            text-align: center;
            color: var(--text-muted);
            font-size: 12px;
        }
        table.assessment-table th,
        table.assessment-table td {
            border: 1px solid #d0d7de;
            padding: 10px 12px;
        }
        table.assessment-table th {
            background: rgba(20, 90, 50, 0.08);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 12px;
            color: #0f172a;
            text-align: left;
        }
        table.assessment-table td.amount {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .summary-row {
            background: rgba(20, 90, 50, 0.08);
            font-weight: 700;
        }
        .muted-note {
            margin: 12px 0 0;
            font-size: 12px;
            color: var(--text-muted);
        }
        .signature-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(180px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        .signature-line {
            border-top: 1px solid #1f2937;
            padding-top: 8px;
            text-align: center;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #1f2937;
        }
        .signature-subtext {
            font-size: 11px;
            color: var(--text-muted);
        }
        @media print {
            body {
                padding: 0;
                background: #ffffff;
            }
            .assessment-wrapper {
                box-shadow: none;
                border-radius: 0;
            }
            .print-actions {
                display: none;
            }
            .assessment-body {
                padding: 28px;
            }
            header.assessment-header {
                border-bottom: 4px solid var(--brand-green);
            }
        }
    </style>
</head>
<body>
    <div class="print-actions no-print">
        <button type="button" onclick="window.print()">Print Assessment</button>
    </div>
    <div class="assessment-wrapper">
        <header class="assessment-header">
            <img src="../Esrlogo.png" alt="Escuela de Sto. Rosario Logo">
            <div class="header-text">
                <h1>Escuela de Sto. Rosario</h1>
                <h2>Registration Assessment Form</h2>
                <p>97 Dr. Sixto Antonio Ave., Rosario, Pasig City &nbsp;•&nbsp; Tel. No. (0969) 354-2870</p>
                <p><strong>Generated:</strong> <?= htmlspecialchars($assessmentDateLong) ?> &nbsp;|&nbsp; <strong>Registrar:</strong> <?= htmlspecialchars($registrarName) ?></p>
            </div>
            <div style="text-align:right;">
                <div style="font-size:14px; font-weight:700; color:var(--brand-green);">Student ID</div>
                <div style="font-size:20px; font-weight:700;"><?= htmlspecialchars($studentNumber ?: 'N/A') ?></div>
            </div>
        </header>

        <div class="assessment-body">
            <div class="section-title">Learner Information</div>
            <div class="info-grid">
                <div><span>Student Name</span><?= htmlspecialchars($studentFullNameUpper) ?></div>
                <div><span>Grade / Level</span><?= htmlspecialchars($gradeLevel ?: 'Not Assigned') ?></div>
                <div><span>Section</span><?= htmlspecialchars($sectionLabel) ?></div>
                <div><span>School Year</span><?= htmlspecialchars($studentSchoolYear !== '' ? $studentSchoolYear : 'TBA') ?></div>
                <div><span>Student Type</span><?= htmlspecialchars($studentType !== '' ? $studentType : 'N/A') ?></div>
                <div><span>LRN</span><?= htmlspecialchars($lrn !== '' ? $lrn : 'Pending') ?></div>
                <div><span>Enrollment Status</span><?= htmlspecialchars($studentRow['enrollment_status'] ?? 'N/A') ?></div>
                <div><span>Assessment Date</span><?= htmlspecialchars($assessmentDate) ?></div>
            </div>

            <div class="section-title">Assessment Summary</div>
            <table class="assessment-table">
                <thead>
                    <tr>
                        <th style="width:60%;">Particulars</th>
                        <th style="width:40%;" class="amount">Amount (PHP)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feeBreakdown as $label => $amount): ?>
                        <?php if ($amount > 0.009): ?>
                            <tr>
                                <td><?= htmlspecialchars($label) ?></td>
                                <td class="amount"><?= number_format($amount, 2) ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($dueUponEnrollment > 0.009 && $dueUponEnrollment !== ($feeBreakdown['Entrance Fee'] + $feeBreakdown['Miscellaneous Fee'])): ?>
                        <tr>
                            <td>Due Upon Enrollment (<?= htmlspecialchars($planTypeLabel) ?>)</td>
                            <td class="amount"><?= number_format($dueUponEnrollment, 2) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="summary-row">
                        <td>Gross Assessment</td>
                        <td class="amount"><?= number_format($overallAssessment, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Total Payments & Adjustments</td>
                        <td class="amount">-<?= number_format($paymentsTotal, 2) ?></td>
                    </tr>
                    <?php if ($previousOutstanding > 0.009): ?>
                        <tr>
                            <td>Balance Brought Forward</td>
                            <td class="amount"><?= number_format($previousOutstanding, 2) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="summary-row">
                        <td>Net Assessment</td>
                        <td class="amount"><?= number_format($netAssessment, 2) ?></td>
                    </tr>
                </tbody>
            </table>
            <p class="muted-note">Payment Plan: <?= htmlspecialchars($planLabelDisplay !== '' ? $planLabelDisplay : $planTypeLabel) ?></p>

            <?php if (!empty($planEntries)): ?>
                <div class="section-title">Payment Schedule</div>
                <table class="assessment-table">
                    <thead>
                        <tr>
                            <th style="width:40%;">Description</th>
                            <th style="width:20%;">Due Date</th>
                            <th style="width:40%;" class="amount">Amount (PHP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($planEntries as $entry): ?>
                            <?php
                                $entryLabel = $entry['label'] ?? ($entry['note'] ?? 'Payment');
                                $entryAmount = (float) ($entry['amount_original'] ?? $entry['amount'] ?? 0);
                                $entryDue = $entry['due_date'] ?? '';
                                if ($entryDue === '' && !empty($entry['note'])) {
                                    $entryDue = $entry['note'];
                                }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($entryLabel) ?></td>
                                <td><?= htmlspecialchars($entryDue !== '' ? $entryDue : '—') ?></td>
                                <td class="amount"><?= number_format($entryAmount, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($paymentsRows)): ?>
                <div class="section-title">Payments & Adjustments</div>
                <table class="assessment-table">
                    <thead>
                        <tr>
                            <th style="width:20%;">Date</th>
                            <th style="width:20%;">Method</th>
                            <th style="width:20%;">Reference</th>
                            <th style="width:20%;">OR Number</th>
                            <th style="width:20%;" class="amount">Amount (PHP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentsRows as $entry): ?>
                            <?php
                                $dateDisplay = $entry['payment_date'] ?? ($entry['created_at'] ?? '');
                                if ($dateDisplay !== '') {
                                    $dateDisplay = date('M d, Y', strtotime($dateDisplay));
                                }
                                $methodLabel = $entry['payment_type'] ?? 'Payment';
                                $methodLabel = ucwords(str_replace('_', ' ', strtolower((string) $methodLabel)));
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($dateDisplay !== '' ? $dateDisplay : '—') ?></td>
                                <td><?= htmlspecialchars($methodLabel) ?></td>
                                <td><?= htmlspecialchars($entry['reference_number'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($entry['or_number'] ?? '—') ?></td>
                                <td class="amount"><?= number_format((float) ($entry['amount'] ?? 0), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="section-title">Class Schedule</div>
            <?php if (!empty($classScheduleRows)): ?>
                <p class="text-muted text-center small" style="margin-top:-10px; margin-bottom:18px;">
                    School Year: <strong><?= htmlspecialchars($classScheduleYear ?? ($studentSchoolYear !== '' ? $studentSchoolYear : 'TBA')) ?></strong>
                </p>
                <div class="table-responsive">
                    <table class="assessment-table" style="text-align:center;">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Room</th>
                                <th>Section</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weekdayOrder as $weekday):
                                if (empty($groupedSchedule[$weekday])) {
                                    continue;
                                }
                                $rowsForDay = $groupedSchedule[$weekday];
                                $rowspan = count($rowsForDay);
                                foreach ($rowsForDay as $index => $entry):
                                    $startRaw = trim((string) ($entry['start_time'] ?? ''));
                                    $endRaw = trim((string) ($entry['end_time'] ?? ''));
                                    $startDisplay = $startRaw !== '' ? date('g:i A', strtotime($startRaw)) : '—';
                                    $endDisplay = $endRaw !== '' ? date('g:i A', strtotime($endRaw)) : '—';
                                    if ($startDisplay === '—' && $endDisplay === '—') {
                                        $timeDisplay = 'TBA';
                                    } elseif ($endDisplay === '—') {
                                        $timeDisplay = $startDisplay;
                                    } elseif ($startDisplay === '—') {
                                        $timeDisplay = 'Until ' . $endDisplay;
                                    } else {
                                        $timeDisplay = $startDisplay . ' - ' . $endDisplay;
                                    }
                                    $subjectLabel = trim((string) ($entry['subject'] ?? ''));
                                    if ($subjectLabel === '') {
                                        $subjectLabel = 'TBA';
                                    }
                                    $teacherLabel = trim((string) ($entry['teacher'] ?? ''));
                                    if ($teacherLabel === '') {
                                        $teacherLabel = 'TBA';
                                    }
                                    $roomLabel = trim((string) ($entry['room'] ?? ''));
                                    if ($roomLabel === '') {
                                        $roomLabel = 'TBA';
                                    }
                                    $entrySection = trim((string) ($entry['section'] ?? ''));
                                    if ($entrySection === '') {
                                        $entrySection = $sectionLabel;
                                    } elseif (strcasecmp($entrySection, 'ALL') === 0) {
                                        $entrySection = 'All Sections';
                                    }
                            ?>
                            <tr>
                                <?php if ($index === 0): ?>
                                    <td rowspan="<?= (int) $rowspan ?>" style="font-weight:600; color:var(--brand-green);"><?= htmlspecialchars($weekday) ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($timeDisplay) ?></td>
                                <td><?= htmlspecialchars($subjectLabel) ?></td>
                                <td><?= htmlspecialchars($teacherLabel) ?></td>
                                <td><?= htmlspecialchars($roomLabel) ?></td>
                                <td><?= htmlspecialchars($entrySection) ?></td>
                            </tr>
                            <?php endforeach; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state" style="margin-top:-8px;">
                    <i class="bi bi-info-circle me-2"></i>Class schedule will be provided once assigned by the registrar.
                </div>
            <?php endif; ?>

            <div class="signature-grid">
                <div>
                    <div class="signature-line">Parent / Guardian</div>
                    <div class="signature-subtext">Name and Signature</div>
                </div>
                <div>
                    <div class="signature-line">Student</div>
                    <div class="signature-subtext">Name and Signature</div>
                </div>
                <div>
                    <div class="signature-line">Prepared By</div>
                    <div class="signature-subtext"><?= htmlspecialchars($registrarName) ?> &mdash; <?= htmlspecialchars($assessmentDateLong) ?></div>
                </div>
            </div>

            <p class="muted-note" style="margin-top:30px;">* This is a system-generated document. Please coordinate with the registrar for any clarifications or updates.</p>
        </div>
    </div>
</body>
</html>
