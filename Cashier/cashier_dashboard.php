<?php
session_start();

require __DIR__ . '/../vendor/autoload.php';

include __DIR__ . '/../db_connection.php';
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

require_once __DIR__ . '/cashier_dashboard_logic.php';

$redirect = cashier_dashboard_handle_payment_submission($conn);
if ($redirect) {
    header("Location: $redirect", true, 303);
    exit();
}

$redirect = cashier_dashboard_handle_tuition_fee_form($conn);
if ($redirect) {
    header("Location: $redirect", true, 303);
    exit();
}

$flash = cashier_dashboard_consume_flash();
$flashMessage = $flash['message'] ?? '';
$flashType = $flash['type'] ?? '';

$searchData = cashier_dashboard_prepare_search($conn);
$search_results = $searchData['results'];
$search_financial = $searchData['financial'];
$searchQuery = $searchData['query'];
$searchType = $searchData['type'];
$clearSearchFlag = $searchData['cleared'];

$payments = cashier_dashboard_fetch_payments($conn);
$planOptions = cashier_dashboard_plan_labels();
$tuitionPackages = cashier_dashboard_fetch_tuition_packages($conn);
$pricingCategories = [
  'regular' => 'Regular Students',
  'fwc' => 'FWC Member',
  'esc' => 'ESC / Government Subsidy',
  'other' => 'Other / Custom',
];
$studentTypeLabels = [
  'all' => 'All Students',
  'new' => 'New',
  'old' => 'Old',
];
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
  'grade7' => 'Grade 7',
  'grade8' => 'Grade 8',
  'grade9' => 'Grade 9',
  'grade10' => 'Grade 10',
  'grade11' => 'Grade 11',
  'grade12' => 'Grade 12',
];
?>


<!DOCTYPE html>
<html lang="en">
<head>

  <meta charset="UTF-8">
  <title>CASHIER DASHBOARD</title>
   <link rel="stylesheet" href="cashier_dashboard.css">
</head>
<body>

<div class="sidebar">
  <h1>Cashier Panel</h1>
  <nav>
    <ul class="nav-links">
      <li><a class="nav-link" href="#record">💵 Record Payment</a></li>
      <li><a class="nav-link" href="#records">📑 Payment Records</a></li>
      <li><a class="nav-link" href="#fees">⚙️ Manage Fees</a></li>
    </ul>
  </nav>
  <a href="cashier_login.php" class="logout-btn">Logout</a>
</div>

<div class="main-content">
  <?php if ($flashMessage !== ''): ?>
    <?php $alertClass = ($flashType === 'success') ? 'success' : 'error'; ?>
    <div class="<?= $alertClass ?>"><?= htmlspecialchars($flashMessage) ?></div>
  <?php endif; ?>

  <div class="section-card" id="record">
    <h2>Record Onsite Payment</h2>

    <form method="GET" action="cashier_dashboard.php">
        <label for="search_name">Search Student by Last Name</label>
        <input type="text" name="search_name" placeholder="Enter surname..." value="<?= htmlspecialchars($searchQuery) ?>" required>
        <button type="submit">🔍 Search</button>
    </form>

    <?php if (!empty($search_results) && !$clearSearchFlag): ?>
      <div id="search-results-container">
        <h3>Search Results</h3>
        <ul>
            <?php foreach ($search_results as $s): ?>
                <?php $snapshot = $search_financial[$s['id']] ?? null; ?>
                <li class="search-result-card" style="margin-bottom:20px; padding:20px; border:1px solid #cdd5d2; border-radius:12px; background:#fdfefd;">
                    <div class="search-result-header" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:12px;">
                        <div>
                            <span class="search-result-pill">Portal Account</span>
                            <h3 style="margin:8px 0 4px;">
                                <?= htmlspecialchars($s['lastname']) ?>, <?= htmlspecialchars($s['firstname']) ?>
                                <small style="color:#5d6d6f; font-weight:500;">[<?= $s['student_number'] ?: 'No Student # yet' ?>]</small>
                            </h3>
                            <p style="margin:0; color:#4f5d57;"><?= htmlspecialchars($s['year']) ?> <?= $s['section'] ? '•  ' . htmlspecialchars($s['section']) : '' ?> <?= $s['student_type'] ? '• ' . ucfirst($s['student_type']) . ' student' : '' ?></p>
                            <p style="margin:4px 0 0; color:#8aa19a; font-size:0.9rem;">Enrollment status: <strong><?= htmlspecialchars(ucfirst($s['enrollment_status'] ?? 'pending')) ?></strong></p>
                        </div>
                    </div>

                    <?php if ($snapshot): ?>
                        <?php
                            $views = $snapshot['views'] ?? ['current' => $snapshot];
                            $hasMultipleViews = isset($snapshot['views']) && count($snapshot['views']) > 1;
                            $primaryView = $views['current'] ?? reset($views);
                            $primaryPlanTabs = $primaryView['plan_tabs'] ?? [];
                            $primaryActivePlan = $primaryView['active_plan'] ?? null;
                            $primaryPlanMeta = null;
                            foreach ($primaryPlanTabs as $tabCandidate) {
                                if (!empty($tabCandidate['available']) && $tabCandidate['plan_type'] === $primaryActivePlan) {
                                    $primaryPlanMeta = $tabCandidate;
                                    break;
                                }
                            }
                            if ($primaryPlanMeta === null) {
                                foreach ($primaryPlanTabs as $tabCandidate) {
                                    if (!empty($tabCandidate['available'])) {
                                        $primaryPlanMeta = $tabCandidate;
                                        $primaryActivePlan = $tabCandidate['plan_type'];
                                        break;
                                    }
                                }
                            }

                            $primarySummary = $primaryPlanMeta['summary'] ?? null;
                            $primaryNextRow = $primarySummary['next_due_row'] ?? null;
                            $defaultAmount = '';
                            $amountHintText = '';
                            if ($primaryNextRow) {
                                $nextAmountRaw = null;
                                if (isset($primaryNextRow['amount_outstanding'])) {
                                    $nextAmountRaw = (float) $primaryNextRow['amount_outstanding'];
                                } elseif (isset($primaryNextRow['amount'])) {
                                    $nextAmountRaw = (float) $primaryNextRow['amount'];
                                }
                                if ($nextAmountRaw !== null && $nextAmountRaw > 0.0) {
                                    $defaultAmount = number_format($nextAmountRaw, 2, '.', '');
                                    $hintParts = [];
                                    if (!empty($primaryNextRow['due_date'])) {
                                        $hintParts[] = 'Due ' . $primaryNextRow['due_date'];
                                    }
                                    if (!empty($primaryNextRow['label'])) {
                                        $hintParts[] = $primaryNextRow['label'];
                                    }
                                    if (!empty($primaryNextRow['note']) && (!isset($primaryNextRow['label']) || $primaryNextRow['note'] !== $primaryNextRow['label'])) {
                                        $hintParts[] = $primaryNextRow['note'];
                                    }
                                    $hintParts[] = '₱' . number_format($nextAmountRaw, 2);
                                    $amountHintText = implode(' • ', array_filter(array_unique($hintParts)));
                                }
                            }
                            if ($amountHintText === '' && !empty($primaryPlanMeta['label'])) {
                                $amountHintText = 'Selected plan: ' . $primaryPlanMeta['label'];
                            }
                            $primaryPlanContext = $primaryView['plan_context'] ?? null;
                        ?>

                        <?php if ($hasMultipleViews): ?>
                            <div class="search-subsection search-view-selector">
                                <label>View financial data for</label>
                                <select class="cashier-view-selector" data-student="<?= $s['id'] ?>">
                                    <?php foreach ($views as $viewKey => $viewData): ?>
                                        <option value="<?= htmlspecialchars($viewKey) ?>" <?= !empty($viewData['is_default']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($viewData['label'] ?? ucfirst($viewKey)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($views as $viewKey => $viewData):
                            $isDefaultView = !empty($viewData['is_default']);
                            $viewRemaining = $viewData['remaining_balance'] ?? 0;
                            $viewTotalPaid = $viewData['total_paid'] ?? 0;
                            $viewPendingTotal = $viewData['pending_total'] ?? 0;
                            $viewPlanLabel = $viewData['plan_label'] ?? null;
                            $viewNextDue = $viewData['next_due_row'] ?? null;
                            $viewAlert = $viewData['alert'] ?? null;
                            $planTabs = $viewData['plan_tabs'] ?? [];
                            $planOptions = cashier_dashboard_plan_labels();
                            $activePlanKey = $viewData['active_plan'] ?? null;
                            $viewScheduleRows = [];
                            $viewScheduleMessage = $viewData['schedule_message'] ?? 'No schedule information available.';
                            $viewPendingRows = $viewData['pending_rows'] ?? [];
                            $viewPendingMessage = $viewData['pending_message'] ?? 'No pending payments for this grade.';
                            $viewHistoryRows = $viewData['history_rows'] ?? [];
                            $viewHistoryMessage = $viewData['history_message'] ?? 'No payments recorded yet for this grade.';
                            $viewYearTotal = $viewData['current_year_total'] ?? 0;

                            $nextDueParts = [];
                            if (!empty($viewNextDue)) {
                                if (!empty($viewNextDue['due_date'])) {
                                    $label = 'Next due on ' . $viewNextDue['due_date'];
                                    if (isset($viewNextDue['amount']) || isset($viewNextDue['amount_outstanding'])) {
                                        $amountRaw = (float) ($viewNextDue['amount_outstanding'] ?? $viewNextDue['amount'] ?? 0);
                                        if ($amountRaw > 0) {
                                            $label .= ' • ₱' . number_format($amountRaw, 2);
                                        }
                                    }
                                    $nextDueParts[] = $label;
                                } else {
                                    if (!empty($viewNextDue['label'])) {
                                        $nextDueParts[] = $viewNextDue['label'];
                                    }
                                    if (!empty($viewNextDue['note']) && (!isset($viewNextDue['label']) || $viewNextDue['note'] !== $viewNextDue['label'])) {
                                        $nextDueParts[] = $viewNextDue['note'];
                                    }
                                    if (isset($viewNextDue['amount_outstanding'])) {
                                        $amountRaw = (float) $viewNextDue['amount_outstanding'];
                                        if ($amountRaw > 0) {
                                            $nextDueParts[] = '₱' . number_format($amountRaw, 2);
                                        }
                                    }
                                }
                            }
                            $nextDueDisplay = implode(' • ', array_filter($nextDueParts));
                        ?>
                        <div class="cashier-view" data-student="<?= $s['id'] ?>" data-view="<?= htmlspecialchars($viewKey) ?>" style="<?= $isDefaultView ? '' : 'display:none;' ?>">
                            <div class="search-summary-tiles" style="display:flex; flex-wrap:wrap; gap:12px;">
                                <div class="summary-tile-sm">
                                    <span class="label">Remaining Balance</span>
                                    <strong data-plan-bind="remaining">₱<?= number_format($viewRemaining, 2) ?></strong>
                                </div>
                                <div class="summary-tile-sm">
                                    <span class="label">Total Paid</span>
                                    <strong>₱<?= number_format($viewTotalPaid, 2) ?></strong>
                                </div>
                                <div class="summary-tile-sm">
                                    <span class="label">Pending Review</span>
                                    <strong>₱<?= number_format($viewPendingTotal, 2) ?></strong>
                                </div>
                                <div class="summary-tile-sm">
                                    <span class="label">Tuition Package</span>
                                    <strong data-plan-bind="total">₱<?= number_format($viewYearTotal, 2) ?></strong>
                                </div>
                            </div>

                            <?php if (!empty($viewAlert)): ?>
                                <div class="search-alert" style="margin:18px 0; padding:12px 16px; border-left:4px solid #e59819; background:rgba(251,216,10,0.16); color:#8a6d0a; border-radius:10px;">
                                    Past balance from <?= htmlspecialchars($viewAlert['grade']) ?>: <strong>₱<?= number_format($viewAlert['amount'], 2) ?></strong>
                                </div>
                            <?php endif; ?>

                            <div class="search-subsection" data-plan-container>
                                <h4>Upcoming Tuition Schedule</h4>
                                <?php
                                    $planAvailability = [];
                                    foreach ($planTabs as $planTabInfo) {
                                        $planAvailability[$planTabInfo['plan_type']] = !empty($planTabInfo['available']);
                                    }
                                ?>
                                <div class="mb-3">
                                    <label for="payment-plan-select-<?= $s['id'] ?>" class="form-label">Choose Payment Plan:</label>
                                    <select id="payment-plan-select-<?= $s['id'] ?>" class="form-select payment-plan-select" data-student="<?= $s['id'] ?>">
                                        <?php foreach ($planOptions as $planType => $planLabel):
                                            $isSelected = $planType === $activePlanKey;
                                            $isEnabled = !empty($planAvailability[$planType]);
                                        ?>
                                            <option value="<?= htmlspecialchars($planType) ?>" <?= $isSelected ? 'selected' : '' ?> <?= $isEnabled ? '' : 'disabled' ?>><?= htmlspecialchars($planLabel) ?><?= $isEnabled ? '' : ' (Unavailable)' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <p style="margin:0 0 10px; color:#5d6d6f; font-size:0.9rem;" data-plan-wrapper="summary"<?= ($viewPlanLabel || $nextDueDisplay !== '') ? '' : ' hidden' ?>>
                                    <span data-plan-bind="label" style="font-weight:600; color:#145A32;"></span>
                                    <span data-plan-bind="next-wrapper" style="display:none;">
                                        <span data-plan-bind="next-prefix"></span>
                                        <span data-plan-bind="next-text"></span>
                                    </span>
                                </p>

                                <p class="cashier-schedule-message" data-plan-bind="schedule-message" style="margin:0 0 14px; color:#5d6d6f; font-size:0.9rem;">
                                    <?= htmlspecialchars($viewScheduleMessage) ?>
                                </p>

                                <?php if (!empty($planTabs)): ?>
                                    <div class="plan-tab-group mb-3" role="tablist" data-plan-tablist>
                                        <?php foreach ($planTabs as $tabData):
                                            $planType = $tabData['plan_type'];
                                            $planLabel = $tabData['label'];
                                            $dueAmount = (float) ($tabData['due'] ?? 0);
                                            $isAvailable = !empty($tabData['available']);
                                            $isActive = $planType === $activePlanKey;
                                        ?>
                                            <button type="button"
                                                class="plan-tab<?= $isActive ? ' active' : '' ?>"
                                                data-plan-trigger="<?= htmlspecialchars($planType) ?>"
                                                <?= $isAvailable ? '' : 'disabled' ?>
                                            >
                                                <span class="plan-tab__label"><?= htmlspecialchars($planLabel) ?></span>
                                                <span class="plan-tab__amount">
                                                    <?= $isAvailable ? '₱' . number_format($dueAmount, 2) : 'Unavailable' ?>
                                                </span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="cashier-plan-panels">
                                        <?php foreach ($planTabs as $tabData):
                                            $planType = $tabData['plan_type'];
                                            $planLabel = $tabData['label'];
                                            $dueAmount = (float) ($tabData['due'] ?? 0);
                                            $isAvailable = !empty($tabData['available']);
                                            $entries = $tabData['entries'] ?? [];
                                            $base = $tabData['base'] ?? [];
                                            $notes = $tabData['notes'] ?? '';
                                            $futureTotal = ($base['overall_total'] ?? 0) - ($base['due_total'] ?? 0);
                                            if ($futureTotal < 0) {
                                                $futureTotal = 0;
                                            }
                                            $isActive = $planType === $activePlanKey;
                                        ?>
                                            <div class="plan-panel<?= $isActive ? ' active' : '' ?>" data-plan-panel="<?= htmlspecialchars($planType) ?>">
                                                <div class="row g-3">
                                                    <div class="col-12 col-lg-6">
                                                        <div class="plan-panel-card">
                                                            <h4 class="fw-semibold mb-2">What you pay today</h4>
                                                            <?php if ($isAvailable && $dueAmount > 0): ?>
                                                                <p class="mb-1"><strong>Entrance Fee:</strong> ₱<?= number_format($base['entrance_fee'] ?? 0, 2) ?>
                                                                    <?php if (!empty($base['entrance_note'])): ?>
                                                                        <span class="text-success">(<?= htmlspecialchars($base['entrance_note']) ?>)</span>
                                                                    <?php endif; ?>
                                                                </p>
                                                                <p class="mb-1"><strong>Miscellaneous Fee:</strong> ₱<?= number_format($base['miscellaneous_fee'] ?? 0, 2) ?></p>
                                                                <p class="mb-1"><strong>Tuition Portion:</strong> ₱<?= number_format($base['tuition_fee'] ?? 0, 2) ?></p>
                                                                <p class="mb-0 mt-2"><strong>Due upon enrollment:</strong> ₱<?= number_format($base['due_total'] ?? 0, 2) ?></p>
                                                            <?php else: ?>
                                                                <p class="mb-0">No breakdown available for this plan.</p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-lg-6">
                                                        <div class="plan-panel-card">
                                                            <h4 class="fw-semibold mb-2">Remaining balance</h4>
                                                            <?php if ($isAvailable && !empty($entries)): ?>
                                                                <p class="mb-1">Future payments total: ₱<?= number_format($futureTotal, 2) ?></p>
                                                            <?php elseif ($isAvailable && $dueAmount > 0): ?>
                                                                <p class="mb-1">No remaining payments after enrollment.</p>
                                                            <?php else: ?>
                                                                <p class="mb-1">No breakdown available for this plan.</p>
                                                            <?php endif; ?>
                                                            <p class="mb-0"><strong>Overall program cost:</strong> ₱<?= number_format($base['overall_total'] ?? 0, 2) ?></p>
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
                                                            <?php if ($isAvailable && !empty($entries)): ?>
                                                                <?php foreach ($entries as $entry): ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($planLabel) ?></td>
                                                                        <td>₱<?= number_format((float) $entry['amount'], 2) ?></td>
                                                                        <td><?= $entry['note'] !== '' ? htmlspecialchars($entry['note']) : '—' ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php elseif ($isAvailable && $dueAmount > 0): ?>
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

                                                <?php if ($isAvailable && $notes !== ''): ?>
                                                    <div class="plan-panel-note">
                                                        <strong>Notes:</strong> <?= nl2br(htmlspecialchars($notes)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php foreach ($planTabs as $tabData):
                                        if (empty($tabData['available'])) {
                                            continue;
                                        }
                                        $planKey = $tabData['plan_type'];
                                        $summary = $tabData['summary'] ?? [];
                                        $nextRow = $summary['next_due_row'] ?? [];
                                        $nextAmountRaw = '';
                                        if (isset($nextRow['amount_outstanding'])) {
                                            $nextAmountRaw = number_format((float) $nextRow['amount_outstanding'], 2, '.', '');
                                        } elseif (isset($nextRow['amount'])) {
                                            $nextAmountRaw = number_format((float) $nextRow['amount'], 2, '.', '');
                                        }
                                    ?>
                                        <div class="cashier-plan-meta" hidden data-plan-meta data-plan="<?= htmlspecialchars($planKey) ?>"
                                            data-remaining="<?= number_format((float) ($summary['remaining_with_previous'] ?? $previous_outstanding), 2, '.', '') ?>"
                                            data-total="<?= number_format((float) ($summary['plan_total'] ?? 0.0), 2, '.', '') ?>"
                                            data-plan-label="<?= htmlspecialchars($tabData['label'], ENT_QUOTES) ?>"
                                            data-next-label="<?= htmlspecialchars($nextRow['label'] ?? '', ENT_QUOTES) ?>"
                                            data-next-note="<?= htmlspecialchars($nextRow['note'] ?? '', ENT_QUOTES) ?>"
                                            data-next-amount="<?= $nextAmountRaw ?>"
                                            data-next-due="<?= htmlspecialchars($nextRow['due_date'] ?? '', ENT_QUOTES) ?>"
                                            data-schedule-message="<?= htmlspecialchars($summary['schedule_message'] ?? '', ENT_QUOTES) ?>"
                                            data-plan-due="<?= number_format((float) $tabData['due'], 2, '.', '') ?>"
                                        ></div>
                                    <?php endforeach; ?>
                                <?php elseif (!empty($viewScheduleRows)): ?>
                                    <table class="search-table">
                                        <thead>
                                            <tr>
                                                <th>Due Date</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($viewScheduleRows as $row):
                                                $rowClass = '';
                                                if (!empty($row['row_class'])) {
                                                    $rowClass = $row['row_class'];
                                                } elseif (!empty($row['is_next'])) {
                                                    $rowClass = 'next-due';
                                                }

                                                $dueLabel = isset($row['due_date']) && $row['due_date'] !== ''
                                                    ? $row['due_date']
                                                    : ($row['label'] ?? '—');

                                                $amountOriginal = isset($row['amount_original'])
                                                    ? (float) $row['amount_original']
                                                    : (float) ($row['amount'] ?? 0);
                                                $amountOutstanding = isset($row['amount_outstanding'])
                                                    ? (float) $row['amount_outstanding']
                                                    : $amountOriginal;

                                                if ($amountOutstanding <= 0.009 && $amountOriginal > $amountOutstanding + 0.009) {
                                                    $displayAmount = '<span class="text-success fw-semibold">Paid</span>';
                                                    $displayAmount .= ' <span class="text-muted">(₱' . number_format($amountOriginal, 2) . ')</span>';
                                                } elseif ($amountOriginal > $amountOutstanding + 0.009 && $amountOutstanding > 0.009) {
                                                    $displayAmount = '₱' . number_format($amountOutstanding, 2)
                                                        . ' <span class="text-muted" style="font-size:0.82rem;">of ₱'
                                                        . number_format($amountOriginal, 2)
                                                        . ' remaining</span>';
                                                } else {
                                                    $displayAmount = '₱' . number_format($amountOutstanding > 0 ? $amountOutstanding : $amountOriginal, 2);
                                                }
                                            ?>
                                                <tr class="<?= $rowClass ?>">
                                                    <td><?= htmlspecialchars($dueLabel) ?></td>
                                                    <td class="text-end"><?= $displayAmount ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>

                            <div class="search-subsection">
                                <h4>Pending Payments</h4>
                                <?php if (!empty($viewPendingRows)): ?>
                                    <table class="search-table">
                                        <thead>
                                            <tr>
                                                <th>Payment Type</th>
                                                <th class="text-end">Amount</th>
                                                <th>Status</th>
                                                <th>Submitted</th>
                                                <th>Reference</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($viewPendingRows as $pending_row): ?>
                                                <tr class="<?= !empty($pending_row['is_placeholder']) ? 'row-outstanding' : '' ?>">
                                                    <td><?= htmlspecialchars($pending_row['payment_type_display'] ?? ($pending_row['payment_type'] ?? 'Pending')) ?></td>
                                                    <td class="text-end">₱<?= number_format((float) ($pending_row['amount'] ?? 0), 2) ?></td>
                                                    <td><?= htmlspecialchars($pending_row['payment_status'] ?? 'Pending') ?></td>
                                                    <td><?= htmlspecialchars($pending_row['payment_date'] ?? 'Awaiting review') ?></td>
                                                    <td><?= htmlspecialchars($pending_row['reference_number'] ?? $pending_row['or_number'] ?? 'N/A') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin:0; color:#5d6d6f;"><?= htmlspecialchars($viewPendingMessage) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="search-subsection">
                                <h4>Payment History</h4>
                                <?php if (!empty($viewHistoryRows)): ?>
                                    <table class="search-table">
                                        <thead>
                                            <tr>
                                                <!-- Script moved to end of file for best practice -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-plan-container]').forEach(function (container) {
                const tabs = Array.from(container.querySelectorAll('[data-plan-trigger]'));
                const panels = Array.from(container.querySelectorAll('[data-plan-panel]'));
                const select = container.querySelector('.payment-plan-select');
                const totalEl = container.querySelector('[data-plan-selected-total]');
                const labelEl = container.querySelector('[data-plan-selected-label]');

                function activatePlan(target) {
                    tabs.forEach(function (btn) {
                        btn.classList.toggle('active', btn.getAttribute('data-plan-trigger') === target);
                    });
                    panels.forEach(function (panel) {
                        panel.classList.toggle('active', panel.getAttribute('data-plan-panel') === target);
                    });
                    // Update summary label and next due info
                    const meta = container.querySelector('[data-plan-meta][data-plan="' + target + '"]');
                    if (meta) {
                        // Update label
                        const labelSpan = container.querySelector('[data-plan-bind="label"]');
                        if (labelSpan) labelSpan.textContent = meta.getAttribute('data-plan-label') || '';
                        // Hide next due info for cash plan
                        const nextWrapper = container.querySelector('[data-plan-bind="next-wrapper"]');
                        const nextTextSpan = container.querySelector('[data-plan-bind="next-text"]');
                        if (nextWrapper && nextTextSpan) {
                            if (select.value === 'cash') {
                                nextWrapper.style.display = 'none';
                                nextTextSpan.textContent = '';
                            } else {
                                let nextDue = '';
                                if (meta.getAttribute('data-next-due')) {
                                    nextDue = 'Next due on ' + meta.getAttribute('data-next-due');
                                }
                                if (meta.getAttribute('data-next-amount') && parseFloat(meta.getAttribute('data-next-amount')) > 0) {
                                    nextDue += ' • ₱' + meta.getAttribute('data-next-amount');
                                }
                                nextTextSpan.textContent = nextDue;
                                nextWrapper.style.display = nextDue ? '' : 'none';
                            }
                        }
                        // Update schedule message
                        const scheduleMsg = container.querySelector('[data-plan-bind="schedule-message"]');
                        if (scheduleMsg) {
                            scheduleMsg.textContent = meta.getAttribute('data-schedule-message') || '';
                        }
                    }
                }

                tabs.forEach(function (tab) {
                    tab.addEventListener('click', function () {
                        const target = tab.getAttribute('data-plan-trigger');
                        if (select) {
                            select.value = target;
                        }
                        activatePlan(target);
                    });
                });

                if (select) {
                    select.addEventListener('change', function () {
                        activatePlan(select.value);
                    });
                    // Activate initial plan on load
                    activatePlan(select.value);
                }
            });
        });
    </script>
                                <?php else: ?>
                                    <p style="margin:0; color:#5d6d6f;"><?= htmlspecialchars($viewHistoryMessage) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="search-subsection">
                        <h4>Record Payment</h4>
                        <form class="cash-payment-form cashier-payment-entry" method="POST" action="cashier_dashboard.php" data-student="<?= $s['id'] ?>">
                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                            <input type="hidden" name="firstname" value="<?= htmlspecialchars($s['firstname']) ?>">
                            <input type="hidden" name="lastname" value="<?= htmlspecialchars($s['lastname']) ?>">
                            <input type="hidden" name="payment_status" value="paid">
                            <input type="hidden" name="search_name" value="<?= htmlspecialchars($searchQuery) ?>">
                            <input type="hidden" name="payment_plan" data-plan-field="plan" value="<?= htmlspecialchars($primaryActivePlan ?? '') ?>">
                            <input type="hidden" name="tuition_fee_id" value="<?= $primaryPlanContext['tuition_fee_id'] ?? '' ?>">
                            <input type="hidden" name="plan_school_year" value="<?= htmlspecialchars($primaryPlanContext['school_year'] ?? '') ?>">
                            <input type="hidden" name="plan_grade_level" value="<?= htmlspecialchars($primaryPlanContext['grade_level'] ?? '') ?>">
                            <input type="hidden" name="plan_pricing_category" value="<?= htmlspecialchars($primaryPlanContext['pricing_category'] ?? '') ?>">
                            <input type="hidden" name="plan_student_type" value="<?= htmlspecialchars($primaryPlanContext['student_type'] ?? '') ?>">

                            <div class="form-grid">
                                <div>
                                    <label>Payment Method</label>
                                    <select name="payment_mode" class="payment-mode" data-target="modal-fields-<?= $s['id'] ?>">
                                        <option value="Cash">Cash</option>
                                        <option value="GCash">GCash</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Amount (₱)</label>
                                    <input type="number" name="amount" step="0.01" min="0" required value="<?= htmlspecialchars($defaultAmount) ?>" data-plan-field="amount">
                                    <small data-plan-bind="amount-hint" style="display:<?= $amountHintText !== '' ? 'block' : 'none' ?>; color:#5d6d6f;">
                                        <?= htmlspecialchars($amountHintText) ?>
                                    </small>
                                </div>
                                <div id="modal-fields-<?= $s['id'] ?>" class="payment-fields-group">
                                    <div class="cash-field">
                                        <label>Official Receipt #</label>
                                        <input type="text" name="or_number" placeholder="Enter OR number" required>
                                    </div>
                                    <div class="gcash-field" style="display:none;">
                                        <label>GCash Reference #</label>
                                        <input type="text" name="reference_number" placeholder="Enter reference #">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="record-btn" data-plan-bind="submit-button">
                                <?= $primaryPlanMeta && !empty($primaryPlanMeta['label']) ? 'Record Payment (' . htmlspecialchars($primaryPlanMeta['label']) . ')' : 'Record Payment' ?>
                            </button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>

  <div class="section-card" id="records">
    <div class="records-header">
      <h2>Payment Records</h2>
      <form method="GET" action="cashier_dashboard.php#records" id="filterForm" class="records-filter-bar">
        <div>
          <label>Grade Level</label>
          <select name="filter_grade_level">
            <option value="">All</option>
            <?php
              $grade_levels_result = $conn->query("SELECT DISTINCT year FROM students_registration ORDER BY year");
              while ($gl = $grade_levels_result->fetch_assoc()):
                  $selected = ($_GET['filter_grade_level'] ?? '') == $gl['year'] ? "selected" : "";
                  echo "<option value='{$gl['year']}' $selected>{$gl['year']}</option>";
              endwhile;
            ?>
          </select>
        </div>
        <div>
          <label>Status</label>
          <select name="filter_status">
            <option value="">All</option>
            <option value="paid" <?= ($_GET['filter_status'] ?? '') == "paid" ? "selected" : "" ?>>Paid</option>
            <option value="pending" <?= ($_GET['filter_status'] ?? '') == "pending" ? "selected" : "" ?>>Pending</option>
          </select>
        </div>
        <div class="records-filter-actions">
          <button type="submit">Apply Filter</button>
          <a href="cashier_dashboard.php#records" class="secondary-btn">Reset</a>
        </div>
      </form>
    </div>
    <table id="paymentTable">
      <thead>
        <tr>
        <th>Date</th>
        <th>Student</th>
        <th>Type</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Timestamp</th>
        <th>Payments</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($row = $payments->fetch_assoc()): ?>
      <tr id="payment-row-<?php echo $row['id']; ?>">
        <td><?= date("Y-m-d", strtotime($row['created_at'])) ?></td>
        <td><?= $row['lastname'] ?>, <?= $row['firstname'] ?></td>
        <td><?= $row['payment_type'] ?></td>
        <td>₱ <?= number_format($row['amount'], 2) ?></td>

        <!-- Status Column -->
        <td class="payment-status" id="status-<?php echo $row['id']; ?>">
            <?php 
              if (!empty($row['payment_status'])) {
                  if ($row['payment_status'] === 'declined') {
                      echo "<span style='color: red;'>Declined</span>";
                  } elseif ($row['payment_status'] === 'paid') {
                      echo "<span style='color: green;'>Paid</span>";
                  } else {
                      echo ucfirst(htmlspecialchars($row['payment_status']));
                  }
              } else {
                  echo "Pending";
              }
            ?>
        </td>

        <td><?= $row['created_at'] ?></td>
        
        <td>
        <?php 
  $shotUrl = $row['screenshot_path']; 
  if ($shotUrl && strpos($shotUrl, 'http') !== 0) {
      $shotUrl = "http://localhost" . $shotUrl;
  }
  echo "<!-- DEBUG: $shotUrl -->";  // 👈 check page source
?>

          <button 
            class="view-payment-btn" 
            data-id="<?= $row['id'] ?>" 
            data-student="<?= $row['lastname'] ?>, <?= $row['firstname'] ?>"
            data-type="<?= $row['payment_type'] ?>"
            data-amount="<?= $row['amount'] ?>"
            data-status="<?= ucfirst($row['payment_status']) ?>"
            data-reference="<?= $row['reference_number'] ?>"
            data-or="<?= $row['or_number'] ?>"
            data-screenshot="<?= $shotUrl ?>"   
          >
            View Payment
          </button>

          
        </td>
      </tr>
    <?php endwhile; ?>
</tbody>


</table>
  </div>

  <div class="section-card" id="fees">
    <h2>Manage Tuition Fees</h2>
    <form method="POST" action="cashier_dashboard.php#fees" class="tuition-form">
      <input type="hidden" name="tuition_fee_form" value="1">

      <div class="tuition-field-grid">
        <div>
          <label for="school_year">School Year</label>
          <input type="text" id="school_year" name="school_year" placeholder="e.g., 2025-2026" required>
        </div>

        <div>
          <label for="year">Grade Level</label>
          <select id="year" name="year" required>
            <?php foreach ($gradeOptions as $value => $label): ?>
              <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <input type="hidden" name="student_type" value="all">

        <div>
          <label for="pricing_category">Pricing Variant</label>
          <select id="pricing_category" name="pricing_category">
            <?php foreach ($pricingCategories as $value => $label): ?>
              <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="tuition-field-grid">
        <div>
          <label for="entrance_fee">Entrance Fee</label>
          <input type="number" step="0.01" id="entrance_fee" name="entrance_fee" required oninput="calculatePayment()">
        </div>

        <div>
          <label for="miscellaneous_fee">Miscellaneous Fee</label>
          <input type="number" step="0.01" id="miscellaneous_fee" name="miscellaneous_fee" required oninput="calculatePayment()">
        </div>

        <div>
          <label for="tuition_fee">Tuition Fee</label>
          <input type="number" step="0.01" id="tuition_fee" name="tuition_fee" required oninput="calculatePayment()">
        </div>
      </div>

      <div class="tuition-field-grid">
        <div>
          <label for="payment_schedule">Payment Plan</label>
          <select id="payment_schedule" name="payment_schedule" onchange="calculatePayment()" required>
            <?php foreach ($planOptions as $value => $label): ?>
              <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="total_upon_enrollment">Due Upon Enrollment</label>
          <input type="number" step="0.01" id="total_upon_enrollment" name="total_upon_enrollment" required>
        </div>

        <div>
          <label for="calculated_payment">Plan Preview</label>
          <input type="text" id="calculated_payment" readonly placeholder="Auto suggestion">
          <small class="tuition-hint">Use the preview as guidance, then adjust the due amount to match your matrix.</small>
        </div>
      </div>

      <div class="tuition-field-grid full">
        <div>
          <label for="next_payments">Next payments (one per line)</label>
          <textarea id="next_payments" name="next_payments" rows="3" placeholder="e.g. 16500 | Due 5 January"></textarea>
          <small class="tuition-hint">Format: Example: "16500 | Due on January 5".</small>
        </div>
      </div>

      <div class="tuition-field-grid full">
        <div>
          <label for="plan_notes">Plan notes (optional)</label>
          <textarea id="plan_notes" name="plan_notes" rows="2" placeholder="Add reminders about due dates, discounts, etc."></textarea>
        </div>
      </div>

      <button type="submit">Save Tuition Plan</button>
    </form>

    <?php if (!empty($tuitionPackages)): ?>
      <h3 class="tuition-table-title">Saved tuition matrix</h3>
      <div class="tuition-matrix-wrapper">
        <table class="tuition-matrix-table">
          <thead>
            <tr>
              <th>School Year</th>
              <th>Student Type</th>
              <th>Pricing Variant</th>
              <th>Grade Level</th>
              <th>Base Fees</th>
              <th>Plans</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tuitionPackages as $package): ?>
              <tr>
                <td><?= htmlspecialchars($package['school_year']) ?></td>
                <td><?= htmlspecialchars($studentTypeLabels[$package['student_type']] ?? ucfirst($package['student_type'])) ?></td>
                <td><?= htmlspecialchars($pricingCategories[$package['pricing_category']] ?? ucfirst($package['pricing_category'])) ?></td>
                <?php
                  $gradeLabel = $gradeOptions[$package['grade_level']] ?? ucwords(str_replace('_', ' ', $package['grade_level']));
                  $normalizedGrade = strtolower(str_replace([' ', '_'], '', $package['grade_level']));
                  if (in_array($normalizedGrade, ['kinder1', 'kinder2'], true)) {
                      $gradeLabel = 'Kindergarten';
                  }
                ?>
                <td><?= htmlspecialchars($gradeLabel) ?></td>
                <td>
                  <div>Entrance: ₱<?= number_format((float) $package['entrance_fee'], 2) ?></div>
                  <div>Misc: ₱<?= number_format((float) $package['miscellaneous_fee'], 2) ?></div>
                  <div>Tuition: ₱<?= number_format((float) $package['tuition_fee'], 2) ?></div>
                  <div>Total program: ₱<?= number_format((float) $package['total_upon_enrollment'], 2) ?></div>
                </td>
                <td>
                  <?php if (!empty($package['plans'])): ?>
                    <ul class="tuition-plan-list">
                      <?php foreach ($package['plans'] as $plan): ?>
                        <li>
                          <strong><?= htmlspecialchars($planOptions[$plan['plan_type']] ?? ucfirst($plan['plan_type'])) ?>:</strong>
                          ₱<?= number_format((float) $plan['due_upon_enrollment'], 2) ?> due upon enrollment
                          <?php if (!empty($plan['decoded_breakdown'])): ?>
                            <div class="tuition-plan-breakdown">
                              <?php foreach ($plan['decoded_breakdown'] as $entry): ?>
                                <span>Next: ₱<?= number_format((float) ($entry['amount'] ?? 0), 2) ?><?= !empty($entry['note']) ? ' • ' . htmlspecialchars($entry['note']) : '' ?></span>
                              <?php endforeach; ?>
                            </div>
                          <?php endif; ?>
                          <?php if (!empty($plan['notes'])): ?>
                            <div class="tuition-plan-note"><?= nl2br(htmlspecialchars($plan['notes'])) ?></div>
                          <?php endif; ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php else: ?>
                    <em>No plan saved yet.</em>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="tuition-empty">No tuition plans saved yet. Add one above to populate the matrix.</p>
    <?php endif; ?>
  </div>
</div>
          <!-- Payment Modal (outside the table loop, at the end of body) -->
        <!-- Payment Modal -->
<div id="paymentModal" class="payment-modal">
  <div class="payment-modal__content">
    <!-- Close button -->
    <span id="closeModal" class="payment-modal__close">&times;</span>
    
    <h3>Payment Details</h3>
    <p><strong>Student:</strong> <span id="modalStudent"></span></p>
    <p><strong>Type:</strong> <span id="modalType"></span></p>
    <p><strong>Amount:</strong> ₱<span id="modalAmount"></span></p>
    <p><strong>Status:</strong> <span id="modalStatus"></span></p>
    <p><strong id="modalLabel">Reference #:</strong> <span id="modalRefOr"></span></p>

    <!-- Screenshot (online only) -->
    <div id="screenshotSection" class="payment-modal__screenshot">
      <img id="modalScreenshot" class="payment-modal__screenshot-img" src="" alt="Payment Screenshot">
    </div>

    <!-- Hidden input -->
    <input type="hidden" id="modalPaymentId">

    <!-- Accept / Decline Buttons -->
    <div class="payment-modal__actions">
      <button id="acceptPaymentBtn" class="payment-modal__button payment-modal__button--accept">Accept</button>
      <button id="declinePaymentBtn" class="payment-modal__button payment-modal__button--decline">Decline</button>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="cashier_dashboard.js"></script>

</body>
</html>
