<?php
require_once __DIR__ . '/../includes/session.php';

require __DIR__ . '/../vendor/autoload.php';

include __DIR__ . '/../db_connection.php';
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

require_once __DIR__ . '/cashier_dashboard_logic.php';
require_once __DIR__ . '/../admin_functions.php';

$cashierUsername = $_SESSION['cashier_username'] ?? null;
$cashierDisplayName = $_SESSION['cashier_fullname'] ?? ($cashierUsername ?? 'Cashier');
$cashierRoleLabel = ucwords($_SESSION['cashier_role'] ?? 'cashier');
if ($cashierUsername) {
    $cashierRow = dashboard_fetch_user($conn, $cashierUsername);
    if ($cashierRow) {
        if (!empty($cashierRow['fullname'])) {
            $_SESSION['cashier_fullname'] = $cashierRow['fullname'];
            $cashierDisplayName = $cashierRow['fullname'];
        }
        if (!empty($cashierRow['role'])) {
            $_SESSION['cashier_role'] = $cashierRow['role'];
            $cashierRoleLabel = ucwords($cashierRow['role']);
        }
    }
}

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
$paymentRows = [];
if ($payments instanceof mysqli_result) {
    while ($row = $payments->fetch_assoc()) {
        $paymentRows[] = $row;
    }
}
$pendingPaymentCount = 0;
foreach ($paymentRows as $row) {
    $status = strtolower(trim((string) ($row['payment_status'] ?? '')));
    if (in_array($status, ['pending', 'processing', 'review'], true)) {
        $pendingPaymentCount++;
    }
}
$paymentToggleLabel = 'View Payment Records' . ($pendingPaymentCount > 0 ? " ({$pendingPaymentCount} pending)" : '');
$paymentToggleActiveLabel = 'Hide Payment Records';
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
  <title>Cashier Dashboard</title>
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="cashier_dashboard.css">
</head>
<body class="dashboard-body">
<button class="dashboard-toggle" type="button" aria-label="Toggle navigation" onclick="document.querySelector('.dashboard-sidebar').classList.toggle('is-open');">☰</button>
<div class="dashboard-shell">
  <aside class="dashboard-sidebar">
    <div class="dashboard-brand">
      <img src="../Esrlogo.png" alt="ESR Logo">
      <span>Cashier Portal</span>
    </div>
    <nav class="dashboard-nav">
      <a href="#record">Record Payment</a>
      <a href="#records">Payment Records<?php if ($pendingPaymentCount > 0): ?><span class="nav-indicator"><?= $pendingPaymentCount ?></span><?php endif; ?></a>
      <a href="#fees">Manage Fees</a>
    </nav>
    <a href="cashier_login.php" class="dashboard-logout">Logout</a>
  </aside>
  <main class="dashboard-main">
    <header class="dashboard-header">
      <div>
        <h1>Cashier Dashboard</h1>
        <p>Record onsite payments, verify online submissions, and keep tuition fees up to date.</p>
      </div>
      <div class="dashboard-user-chip" title="Logged in as <?= htmlspecialchars($cashierDisplayName); ?>">
        <span class="chip-label">Logged in as</span>
        <span class="chip-name"><?= htmlspecialchars($cashierDisplayName); ?></span>
        <span class="chip-divider">•</span>
        <span class="chip-role"><?= htmlspecialchars($cashierRoleLabel); ?></span>
      </div>
    </header>

    <?php if ($flashMessage !== ''): ?>
      <?php $alertClass = ($flashType === 'success') ? 'success' : 'error'; ?>
      <div class="dashboard-alert <?= $alertClass ?>"><?= htmlspecialchars($flashMessage) ?></div>
    <?php endif; ?>

    <!-- 🔍 Search Section -->
    <section class="dashboard-card" id="record">
      <span class="dashboard-section-title">Onsite Payments</span>
      <h2>Record Onsite Payment</h2>

      <form class="dashboard-form cashier-search-form" method="GET" action="cashier_dashboard.php">
        <label for="search_name">Find student by last name</label>
        <input type="text" name="search_name" id="search_name" placeholder="Enter surname" value="<?= htmlspecialchars($searchQuery) ?>" required>
        <div class="dashboard-actions">
          <button type="submit" class="dashboard-btn">Search Student</button>
          <?php if (!empty($searchQuery)): ?>
            <a class="dashboard-btn secondary" href="cashier_dashboard.php#record">Clear</a>
          <?php endif; ?>
        </div>
      </form>

      <?php if (!empty($search_results) && !$clearSearchFlag): ?>
        <div class="table-responsive cashier-search-summary">
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>Grade / Section</th>
                <th>Student #</th>
                <th>Status</th>
                <th class="text-center">View</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($search_results as $s): ?>
                <tr>
                  <td><?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname']) ?></td>
                  <td><?= htmlspecialchars(trim($s['year'] . ($s['section'] ? ' • ' . $s['section'] : ''))) ?></td>
                  <td><?= htmlspecialchars($s['student_number'] ?: '—') ?></td>
                  <td><?= htmlspecialchars(ucfirst($s['enrollment_status'] ?? 'pending')) ?></td>
                  <td class="text-center"><a class="dashboard-btn secondary dashboard-btn--small" href="#student-<?= $s['id'] ?>">Details</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="cashier-search-results">
          <?php foreach ($search_results as $s): ?>
            <?php $snapshot = $search_financial[$s['id']] ?? null; ?>
            <article class="cashier-search-card" id="student-<?= $s['id'] ?>">
              <header class="cashier-search-card__header">
                <span class="search-result-pill">Student Portal</span>
                <div class="cashier-search-card__heading">
                  <h3><?= htmlspecialchars($s['lastname']) ?>, <?= htmlspecialchars($s['firstname']) ?></h3>
                  <small>[<?= $s['student_number'] ?: 'No Student # yet' ?>]</small>
                </div>
                <p class="cashier-search-card__meta">
                  <?= htmlspecialchars($s['year']) ?>
                  <?php if ($s['section']): ?>
                    • <?= htmlspecialchars($s['section']) ?>
                  <?php endif; ?>
                  <?php if ($s['student_type']): ?>
                    • <?= ucfirst($s['student_type']) ?> student
                  <?php endif; ?>
                </p>
                <p class="cashier-search-card__status">Enrollment status:
                  <strong><?= htmlspecialchars(ucfirst($s['enrollment_status'] ?? 'pending')) ?></strong>
                </p>
              </header>

              <?php if ($snapshot): ?>
                <?php
                  $views = $snapshot['views'] ?? ['current' => $snapshot];
                  $primaryView = $views['current'] ?? reset($views);
                  $planTabs = $primaryView['plan_tabs'] ?? [];
                  $activePlanKey = $primaryView['active_plan'] ?? null;
                  $storedPlanKey = $primaryView['stored_plan'] ?? null;
                  $storedPricingKey = $primaryView['stored_pricing'] ?? null;
                  $availablePricingOptions = $primaryView['available_pricing'] ?? array_keys($pricingCategories);
                  if (empty($availablePricingOptions)) {
                    $availablePricingOptions = array_keys($pricingCategories);
                  }
                  $selectedPricingKey = $primaryView['selected_pricing'] ?? ($primaryView['plan_context']['pricing_category'] ?? 'regular');
                  $next_due_row = $primaryView['next_due_row'] ?? null;
                  $isPending = strtolower($s['enrollment_status'] ?? '') !== 'enrolled';
                  $canChoosePlan = $isPending || empty($storedPlanKey);
                  $previousOutstanding = (float)($primaryView['previous_outstanding'] ?? 0);
                  $previousLabel = $primaryView['previous_grade_label'] ?? '';
                  $pricingLocked = !empty($primaryView['pricing_locked']);
                ?>

                <div class="cashier-search-card__section" data-plan-container>
                  <h4>Upcoming Tuition Schedule</h4>
                  <?php if ($previousOutstanding > 0.009): ?>
                    <div class="cashier-alert cashier-alert--warning">
                      <strong>Past Due:</strong>
                      <?= $previousLabel !== '' ? htmlspecialchars($previousLabel) . ' • ' : '' ?>
                      ₱<?= number_format($previousOutstanding, 2) ?> still outstanding.
                    </div>
                  <?php endif; ?>

                  <?php if ($canChoosePlan): ?>
                    <div class="cashier-inline-field">
                      <label>Choose Payment Plan</label>
                      <?php
                        $selectWrapperClass = 'cashier-select';
                        if (empty($activePlanKey)) {
                          $selectWrapperClass .= ' cashier-select--placeholder';
                        }
                      ?>
                      <div class="<?= $selectWrapperClass ?>">
                        <select class="payment-plan-select" data-student="<?= $s['id'] ?>" data-target="payment_plan_<?= $s['id'] ?>">
                          <?php if (empty($activePlanKey)): ?>
                            <option value="" selected disabled>-- Select a payment plan --</option>
                          <?php endif; ?>
                          <?php foreach ($planOptions as $planType => $planLabel):
                            $isSelected = $planType === $activePlanKey;
                          ?>
                            <option value="<?= htmlspecialchars($planType) ?>" <?= $isSelected ? 'selected' : '' ?>><?= htmlspecialchars($planLabel) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <span class="cashier-select__chevron" aria-hidden="true"></span>
                      </div>
                    </div>
                  <?php else: ?>
                    <div class="cashier-inline-field">
                      <label>Payment Plan</label>
                      <span class="cashier-pill">
                        <?= htmlspecialchars($planOptions[$activePlanKey] ?? ucfirst($activePlanKey)) ?>
                      </span>
                    </div>
                  <?php endif; ?>

                  <div class="cashier-plan-panels">
                    <?php foreach ($planTabs as $tabData):
                      $planType = $tabData['plan_type'];
                      $planLabel = $tabData['label'];
                      $dueAmount = (float) ($tabData['due'] ?? 0);
                      $entries = $tabData['entries'] ?? [];
                      $base = $tabData['base'] ?? [];
                      $notes = $tabData['notes'] ?? '';
                      $isActive = $planType === $activePlanKey;
                      $futureTotal = ($base['overall_total'] ?? 0) - ($base['due_total'] ?? 0);
                      if ($futureTotal < 0) $futureTotal = 0;
                    ?>
                      <div class="plan-panel<?= $isActive ? ' active' : '' ?>" data-plan-panel="<?= htmlspecialchars($planType) ?>">
                        <div class="cashier-plan-summary">
                          <div class="cashier-plan-summary__column cashier-plan-summary__column--today">
                            <h5>What you pay today</h5>
                            <dl>
                              <div><dt>Entrance Fee</dt><dd>₱<?= number_format($base['entrance_fee'] ?? 0, 2) ?></dd></div>
                              <div><dt>Miscellaneous Fee</dt><dd>₱<?= number_format($base['miscellaneous_fee'] ?? 0, 2) ?></dd></div>
                              <div><dt>Tuition Portion</dt><dd>₱<?= number_format($base['tuition_fee'] ?? 0, 2) ?></dd></div>
                              <div class="cashier-plan-summary__total"><dt>Due upon enrollment</dt><dd>₱<?= number_format($base['due_total'] ?? 0, 2) ?></dd></div>
                            </dl>
                          </div>
                          <div class="cashier-plan-summary__column cashier-plan-summary__column--future">
                            <h5>Remaining balance</h5>
                            <dl>
                              <div><dt>Future payments total</dt><dd>₱<?= number_format($futureTotal, 2) ?></dd></div>
                              <div class="cashier-plan-summary__total"><dt>Overall program cost</dt><dd>₱<?= number_format($base['overall_total'] ?? 0, 2) ?></dd></div>
                            </dl>
                          </div>
                        </div>

                        <div class="table-responsive">
                          <table>
                            <thead>
                              <tr>
                                <th>Schedule</th>
                                <th>Amount</th>
                                <th>Notes</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php if (!empty($entries)): ?>
                                <?php foreach ($entries as $entry): ?>
                                  <?php
                                    $originalAmount = (float)($entry['amount_original'] ?? 0);
                                    $appliedAmount = (float)($entry['applied'] ?? 0);
                                    $remainingAmount = max($originalAmount - $appliedAmount, 0);
                                    $isPaid = $appliedAmount >= ($originalAmount - 0.01);
                                    $isPartial = !$isPaid && $appliedAmount > 0.01;
                                  ?>
                                  <tr>
                                    <td><?= htmlspecialchars($entry['label'] ?? $planLabel) ?></td>
                                    <td>
                                      <?php if ($isPaid): ?>
                                        <span class="paid">Paid</span>
                                        <span class="text-muted">(₱<?= number_format($originalAmount, 2) ?>)</span>
                                      <?php elseif ($isPartial): ?>
                                        <div>₱<?= number_format($remainingAmount, 2) ?> remaining</div>
                                        <div class="text-muted">Paid so far: ₱<?= number_format($appliedAmount, 2) ?></div>
                                      <?php else: ?>
                                        ₱<?= number_format($originalAmount, 2) ?>
                                      <?php endif; ?>
                                    </td>
                                    <td><?= $entry['note'] !== '' ? htmlspecialchars($entry['note']) : '—' ?></td>
                                  </tr>
                                <?php endforeach; ?>
                              <?php else: ?>
                                <tr><td colspan="3" class="text-center">No breakdown available for this plan.</td></tr>
                              <?php endif; ?>
                            </tbody>
                          </table>
                        </div>
                        <?php if ($notes !== ''): ?>
                          <div class="cashier-plan-note">
                            <strong>Notes:</strong> <?= nl2br(htmlspecialchars($notes)) ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>

                  <?php
                    $pendingRows = $primaryView['pending_rows'] ?? [];
                    $pendingMessage = $primaryView['pending_message'] ?? 'No pending payments right now.';
                    $pendingTotal = (float)($primaryView['pending_total'] ?? 0);
                  ?>
                  <div class="cashier-pending-wrapper">
                    <div class="cashier-pending-wrapper__header">
                      <h5>Pending & Past Due</h5>
                      <span>Total: ₱<?= number_format($pendingTotal, 2) ?></span>
                    </div>
                    <?php if (!empty($pendingRows)): ?>
                      <div class="table-responsive">
                        <table>
                          <thead>
                            <tr>
                              <th>Type</th>
                              <th>Amount</th>
                              <th>Status</th>
                              <th>Details</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($pendingRows as $pendingRow): ?>
                              <tr>
                                <td><?= htmlspecialchars($pendingRow['payment_type_display'] ?? ($pendingRow['payment_type'] ?? 'Pending')) ?></td>
                                <td>₱<?= number_format((float)($pendingRow['amount'] ?? 0), 2) ?></td>
                                <td><?= htmlspecialchars($pendingRow['payment_status'] ?? 'Pending') ?></td>
                                <td class="cashier-pending-notes">
                                  <?php if (!empty($pendingRow['payment_date'])): ?>
                                    <div>Date: <?= htmlspecialchars($pendingRow['payment_date']) ?></div>
                                  <?php endif; ?>
                                  <?php if (!empty($pendingRow['reference_number'])): ?>
                                    <div>Ref: <?= htmlspecialchars($pendingRow['reference_number']) ?></div>
                                  <?php endif; ?>
                                  <?php if (!empty($pendingRow['or_number'])): ?>
                                    <div>OR#: <?= htmlspecialchars($pendingRow['or_number']) ?></div>
                                  <?php endif; ?>
                                  <?php if (!empty($pendingRow['notes'])): ?>
                                    <div><?= nl2br(htmlspecialchars($pendingRow['notes'])) ?></div>
                                  <?php endif; ?>
                                  <?php if (!empty($pendingRow['is_placeholder'])): ?>
                                    <div class="text-danger fw-semibold">Carry-over balance</div>
                                  <?php endif; ?>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php else: ?>
                      <div class="cashier-alert cashier-alert--muted">
                        <?= htmlspecialchars($pendingMessage) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>

              <div class="cashier-search-card__section">
                <h4>Record Payment</h4>
                <form class="dashboard-form cashier-payment-entry" id="record_payment_<?= $s['id'] ?>" method="POST" action="cashier_dashboard.php#record" data-student="<?= $s['id'] ?>">
                  <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                  <input type="hidden" name="tuition_fee_id" value="<?= $primaryView['plan_context']['tuition_fee_id'] ?? 0 ?>">
                  <input type="hidden" name="plan_school_year" value="<?= $primaryView['plan_context']['school_year'] ?? '' ?>">
                  <input type="hidden" name="plan_grade_level" value="<?= $primaryView['plan_context']['grade_level'] ?? '' ?>">
                  <input type="hidden" name="plan_pricing_category" id="plan_pricing_category_<?= $s['id'] ?>" value="<?= htmlspecialchars($primaryView['plan_context']['pricing_category'] ?? 'regular') ?>">
                  <input type="hidden" name="plan_student_type" value="<?= $primaryView['plan_context']['student_type'] ?? '' ?>">
                  <input type="hidden" name="payment_plan" id="payment_plan_<?= $s['id'] ?>" value="<?= $activePlanKey ?>">

                  <div class="cashier-payment-grid">
                    <div>
                      <label for="payment_mode_<?= $s['id'] ?>">Payment Mode</label>
                      <select name="payment_mode" id="payment_mode_<?= $s['id'] ?>" class="payment-mode" data-target="payment-fields-<?= $s['id'] ?>">
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                      </select>
                    </div>

                    <div>
                      <label for="pricing_variant_<?= $s['id'] ?>">Pricing Variant</label>
                      <select name="pricing_variant" id="pricing_variant_<?= $s['id'] ?>" class="pricing-variant" data-student="<?= $s['id'] ?>" data-target="plan_pricing_category_<?= $s['id'] ?>" <?= $pricingLocked ? 'disabled' : '' ?>>
                        <?php foreach ($availablePricingOptions as $value):
                          $label = $pricingCategories[$value] ?? ucfirst($value);
                          $isSelectedVariant = $value === $selectedPricingKey;
                        ?>
                          <option value="<?= htmlspecialchars($value) ?>" <?= $isSelectedVariant ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <?php if ($pricingLocked): ?>
                        <div class="text-muted">Locked to <?= htmlspecialchars($pricingCategories[$selectedPricingKey] ?? ucfirst($selectedPricingKey)) ?></div>
                      <?php endif; ?>
                    </div>

                    <div>
                      <label>Amount</label>
                      <input type="number" step="0.01" name="amount" required>
                    </div>
                  </div>

                  <div id="payment-fields-<?= $s['id'] ?>" class="cashier-payment-grid">
                    <div class="cash-field">
                      <label>Official Receipt #</label>
                      <input type="text" name="or_number">
                    </div>
                    <div class="gcash-field" style="display:none;">
                      <label>Reference #</label>
                      <input type="text" name="reference_number">
                    </div>
                  </div>

                  <div class="dashboard-actions">
                    <button type="submit" class="dashboard-btn">Submit Payment</button>
                  </div>
                </form>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>


  <!-- 📑 Records Section -->
    <section class="dashboard-card" id="records">
      <span class="dashboard-section-title">Payments Oversight</span>
      <h2>Payment Records</h2>
      <p class="text-muted">Review online submissions and onsite receipts queued for verification.</p>
      <?php if ($pendingPaymentCount > 0): ?>
        <div class="dashboard-alert warning" style="margin-bottom:12px;">
          <?= $pendingPaymentCount ?> payment<?= $pendingPaymentCount > 1 ? 's' : '' ?> awaiting review.
        </div>
      <?php endif; ?>

      <?php if (!empty($paymentRows)): ?>
        <button type="button" class="dashboard-btn secondary dashboard-btn--small" data-toggle-label="<?= htmlspecialchars($paymentToggleLabel, ENT_QUOTES, 'UTF-8'); ?>" data-toggle-active-label="<?= htmlspecialchars($paymentToggleActiveLabel, ENT_QUOTES, 'UTF-8'); ?>" data-toggle-target="paymentRecords" onclick="toggleSection(this);" style="margin-bottom:16px;">
          <?= htmlspecialchars($paymentToggleLabel); ?>
        </button>
        <div class="table-responsive collapsible" id="paymentRecords" style="display:none;">
          <table id="paymentTable">
            <thead>
              <tr>
                <th>Date</th>
                <th>Student</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Status</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($paymentRows as $row): ?>
                <tr>
                  <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                  <td><?= htmlspecialchars($row['lastname']) ?>, <?= htmlspecialchars($row['firstname']) ?> <?= htmlspecialchars($row['middlename']) ?></td>
                  <td><?= htmlspecialchars($row['payment_type']) ?></td>
                  <td>₱ <?= number_format($row['amount'], 2) ?></td>
                  <td id="status-<?= $row['id'] ?>"><?= htmlspecialchars($row['payment_status'] ?? 'Pending') ?></td>
                  <td class="text-center">
                    <button type="button" class="dashboard-btn secondary dashboard-btn--small view-payment-btn"
                      data-id="<?= $row['id'] ?>"
                      data-student="<?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname']) ?>"
                      data-type="<?= htmlspecialchars($row['payment_type']) ?>"
                      data-amount="<?= $row['amount'] ?>"
                      data-status="<?= htmlspecialchars($row['payment_status'] ?? 'Pending') ?>"
                      data-ref="<?= htmlspecialchars($row['reference_number'] ?? $row['or_number'] ?? 'N/A') ?>"
                      data-screenshot="<?= htmlspecialchars($row['screenshot_path'] ?? '') ?>">
                      View Payment
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="dashboard-empty-state">No payments recorded yet.</div>
      <?php endif; ?>
    </section>

    <section class="dashboard-card" id="fees">
      <span class="dashboard-section-title">Tuition Packages</span>
      <h2>Manage Tuition Fees</h2>
    <form method="POST" action="cashier_dashboard.php#fees" class="dashboard-form tuition-form">
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

      <div class="dashboard-actions">
        <button type="submit" class="dashboard-btn">Save Tuition Plan</button>
      </div>
    </form>

    <?php if (!empty($tuitionPackages)): ?>
      <button type="button" class="dashboard-btn secondary dashboard-btn--small" data-toggle-label="View Saved Tuition Matrix" data-toggle-active-label="Hide Saved Tuition Matrix" data-toggle-target="tuitionMatrix" onclick="toggleSection(this);" style="margin:16px 0;">
        View Saved Tuition Matrix
      </button>
      <div class="tuition-matrix-wrapper collapsible" id="tuitionMatrix" style="display:none;">
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
    </section>
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
<script>
function toggleSection(button) {
  if (!button) return;
  var targetId = button.getAttribute('data-toggle-target');
  if (!targetId) return;
  var target = document.getElementById(targetId);
  if (!target) return;

  var isHidden = target.style.display === 'none' || getComputedStyle(target).display === 'none';
  target.style.display = isHidden ? '' : 'none';

  var defaultLabel = button.getAttribute('data-toggle-label') || button.dataset.toggleLabel || button.textContent || 'View';
  var activeLabel = button.getAttribute('data-toggle-active-label') || button.dataset.toggleActiveLabel || 'Hide';
  button.textContent = isHidden ? activeLabel : defaultLabel;
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.payment-plan-select').forEach(function(select) {
    var targetId = select.getAttribute('data-target');
    var hiddenInput = targetId ? document.getElementById(targetId) : null;
    if (hiddenInput) {
      hiddenInput.value = select.value;
      select.addEventListener('change', function () {
        hiddenInput.value = select.value;
      });
    }
  });

  document.querySelectorAll('.pricing-variant').forEach(function(select) {
    var targetId = select.getAttribute('data-target');
    var hiddenInput = targetId ? document.getElementById(targetId) : null;
    if (hiddenInput) {
      hiddenInput.value = select.value;
    }
    select.addEventListener('change', function () {
      if (select.disabled) {
        return;
      }
      if (hiddenInput) {
        hiddenInput.value = select.value;
      }
      var studentId = select.getAttribute('data-student') || '';
      var url = new URL(window.location.href);
      if (studentId) {
        url.searchParams.set('pricing_student', studentId);
      } else {
        url.searchParams.delete('pricing_student');
      }
      url.searchParams.set('pricing_variant', select.value);
      window.location.href = url.toString().split('#')[0] + '#record';
    });
  });
});
</script>

  </main>
</div>

</body>
</html>
