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

  <!-- 🔍 Search Section -->
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
        <li class="search-result-card" style="margin-bottom:24px; padding:24px; border:1px solid #e0e6ed; border-radius:16px; background:#f8fafc; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
          <div class="search-result-header" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:18px;">
            <div>
              <span class="search-result-pill" style="background:#e3f2fd; color:#1976d2; padding:4px 12px; border-radius:20px; font-size:0.95rem; font-weight:600;">Student Portal</span>
              <h3 style="margin:10px 0 6px; font-size:1.35rem; font-weight:700; color:#145A32;">
                <?= htmlspecialchars($s['lastname']) ?>, <?= htmlspecialchars($s['firstname']) ?>
                <small style="color:#5d6d6f; font-weight:500; font-size:0.95rem;">[<?= $s['student_number'] ?: 'No Student # yet' ?>]</small>
              </h3>
              <div style="margin-bottom:6px; color:#4f5d57; font-size:1rem;">
                <span><?= htmlspecialchars($s['year']) ?></span>
                <?php if ($s['section']): ?>
                  <span>• <?= htmlspecialchars($s['section']) ?></span>
                <?php endif; ?>
                <?php if ($s['student_type']): ?>
                  <span>• <?= ucfirst($s['student_type']) ?> student</span>
                <?php endif; ?>
              </div>
              <div style="margin-bottom:2px; color:#8aa19a; font-size:0.98rem;">
                Enrollment status: <strong style="color:#1976d2; font-weight:600;"><?= htmlspecialchars(ucfirst($s['enrollment_status'] ?? 'pending')) ?></strong>
              </div>
            </div>
          </div>

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
                        ?>

            <div class="search-subsection" data-plan-container style="margin-top:18px;">
              <h4 style="font-size:1.15rem; font-weight:600; color:#1976d2; margin-bottom:10px;">Upcoming Tuition Schedule</h4>
              <?php
                $isPending = strtolower($s['enrollment_status'] ?? '') !== 'enrolled';
                $canChoosePlan = $isPending || empty($storedPlanKey);
                $previousOutstanding = (float)($primaryView['previous_outstanding'] ?? 0);
                $previousLabel = $primaryView['previous_grade_label'] ?? '';
                $pricingLocked = !empty($primaryView['pricing_locked']);
              ?>
              <?php if ($previousOutstanding > 0.009): ?>
                <div style="margin-bottom:16px; padding:12px 16px; border-radius:10px; background:#fff0f0; border:1px solid rgba(220,53,69,0.35); color:#842029;">
                  <strong>Past Due:</strong>
                  <?= $previousLabel !== '' ? htmlspecialchars($previousLabel) . ' • ' : '' ?>
                  ₱<?= number_format($previousOutstanding, 2) ?> still outstanding.
                </div>
              <?php endif; ?>

              <?php if ($canChoosePlan): ?>
                <div class="mb-3" style="margin-bottom:18px;">
                  <label style="font-weight:500; color:#145A32;">Choose Payment Plan:</label>
                  <select class="payment-plan-select" data-student="<?= $s['id'] ?>" data-target="payment_plan_<?= $s['id'] ?>" style="padding:6px 12px; border-radius:8px; border:1px solid #cdd5d2; font-size:1rem; margin-left:8px;">
                    <?php foreach ($planOptions as $planType => $planLabel):
                      $isSelected = $planType === $activePlanKey;
                    ?>
                      <option value="<?= htmlspecialchars($planType) ?>" <?= $isSelected ? 'selected' : '' ?>><?= htmlspecialchars($planLabel) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              <?php else: ?>
                <div class="mb-3" style="margin-bottom:18px;">
                  <label style="font-weight:500; color:#145A32;">Payment Plan:</label>
                  <span style="display:inline-block; padding:6px 16px; background:#e3f2fd; color:#1976d2; border-radius:8px; font-weight:600; font-size:1rem; margin-left:8px;">
                    <?= htmlspecialchars($planOptions[$activePlanKey] ?? ucfirst($activePlanKey)) ?>
                  </span>
                </div>
              <?php endif; ?>

                            <!-- 📦 Plan Panels -->
              <div class="cashier-plan-panels" style="margin-top:10px;">
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
                <div class="plan-panel<?= $isActive ? ' active' : '' ?>" data-plan-panel="<?= htmlspecialchars($planType) ?>" style="border:1px solid #e0e6ed; border-radius:12px; background:#fff; margin-bottom:18px; box-shadow:0 1px 4px rgba(0,0,0,0.03); padding:18px;">
                  <div style="display:flex; flex-wrap:wrap; gap:18px; margin-bottom:16px;">
                    <div style="flex:1; min-width:240px; background:#e3f2fd; border-radius:10px; padding:16px;">
                      <h4 style="font-size:1.05rem; font-weight:600; color:#1976d2; margin-bottom:10px;">What you pay today</h4>
                      <dl style="margin:0; color:#145A32; font-size:0.98rem;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                          <dt style="margin:0; font-weight:600;">Entrance Fee</dt>
                          <dd style="margin:0;">₱<?= number_format($base['entrance_fee'] ?? 0, 2) ?></dd>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                          <dt style="margin:0; font-weight:600;">Miscellaneous Fee</dt>
                          <dd style="margin:0;">₱<?= number_format($base['miscellaneous_fee'] ?? 0, 2) ?></dd>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                          <dt style="margin:0; font-weight:600;">Tuition Portion</dt>
                          <dd style="margin:0;">₱<?= number_format($base['tuition_fee'] ?? 0, 2) ?></dd>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-top:12px; border-top:1px dashed rgba(25,118,210,0.25); padding-top:10px; font-size:1.05rem;">
                          <dt style="margin:0; font-weight:700;">Due upon enrollment</dt>
                          <dd style="margin:0; font-weight:700;">₱<?= number_format($base['due_total'] ?? 0, 2) ?></dd>
                        </div>
                      </dl>
                    </div>
                    <div style="flex:1; min-width:240px; background:#f1f8e9; border-radius:10px; padding:16px;">
                      <h4 style="font-size:1.05rem; font-weight:600; color:#388e3c; margin-bottom:10px;">Remaining balance</h4>
                      <dl style="margin:0; color:#145A32; font-size:0.98rem;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                          <dt style="margin:0; font-weight:600;">Future payments total</dt>
                          <dd style="margin:0;">₱<?= number_format($futureTotal, 2) ?></dd>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-top:12px; border-top:1px dashed rgba(56,142,60,0.25); padding-top:10px; font-size:1.05rem;">
                          <dt style="margin:0; font-weight:700;">Overall program cost</dt>
                          <dd style="margin:0; font-weight:700;">₱<?= number_format($base['overall_total'] ?? 0, 2) ?></dd>
                        </div>
                      </dl>
                    </div>
                  </div>

                  <div class="table-fees" style="margin-top:8px;">
                    <table class="table table-bordered align-middle" style="background:#f8fafc;">
                      <thead class="table-success text-center">
                        <tr>
                          <th style="width:28%;">Schedule</th>
                          <th style="width:36%;">Amount</th>
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
                                  <span style="color:#2e7d32;font-weight:600;">Paid</span>
                                  <span class="text-muted" style="font-size:0.92rem;">(₱<?= number_format($originalAmount, 2) ?>)</span>
                                <?php elseif ($isPartial): ?>
                                  <div>₱<?= number_format($remainingAmount, 2) ?> remaining</div>
                                  <div class="text-muted" style="font-size:0.92rem;">Paid so far: ₱<?= number_format($appliedAmount, 2) ?></div>
                                <?php else: ?>
                                  ₱<?= number_format($originalAmount, 2) ?>
                                <?php endif; ?>
                              </td>
                              <td><?= $entry['note'] !== '' ? htmlspecialchars($entry['note']) : '—' ?></td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr><td colspan="3" class="text-center" style="color:#b71c1c;">No breakdown available for this plan.</td></tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                  <?php if ($notes !== ''): ?>
                    <div class="plan-panel-note" style="margin-top:8px; background:#fffde7; border-left:4px solid #ffe082; padding:10px 14px; border-radius:8px; color:#8a6d0a;">
                      <strong>Notes:</strong> <?= nl2br(htmlspecialchars($notes)) ?>
                    </div>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              <h4>Record Payment</h4>
              <div class="cashier-payment-entry" data-student="<?= $s['id'] ?>">
                <form id="record_payment_<?= $s['id'] ?>" method="POST" action="cashier_dashboard.php#record">
                  <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                  <input type="hidden" name="tuition_fee_id" value="<?= $primaryView['plan_context']['tuition_fee_id'] ?? 0 ?>">
                  <input type="hidden" name="plan_school_year" value="<?= $primaryView['plan_context']['school_year'] ?? '' ?>">
                  <input type="hidden" name="plan_grade_level" value="<?= $primaryView['plan_context']['grade_level'] ?? '' ?>">
                  <input type="hidden" name="plan_pricing_category" id="plan_pricing_category_<?= $s['id'] ?>" value="<?= htmlspecialchars($primaryView['plan_context']['pricing_category'] ?? 'regular') ?>">
                  <input type="hidden" name="plan_student_type" value="<?= $primaryView['plan_context']['student_type'] ?? '' ?>">
                  <input type="hidden" name="payment_plan" id="payment_plan_<?= $s['id'] ?>" value="<?= $activePlanKey ?>">

                  <div>
                    <label for="payment_mode_<?= $s['id'] ?>">Payment Mode:</label>
                    <select name="payment_mode" id="payment_mode_<?= $s['id'] ?>" class="payment-mode" data-target="payment-fields-<?= $s['id'] ?>">
                      <option value="Cash">Cash</option>
                      <option value="GCash">GCash</option>
                    </select>
                  </div>

                  <div>
                    <label for="pricing_variant_<?= $s['id'] ?>">Pricing Variant:</label>
                    <select name="pricing_variant" id="pricing_variant_<?= $s['id'] ?>" class="pricing-variant" data-student="<?= $s['id'] ?>" data-target="plan_pricing_category_<?= $s['id'] ?>" <?= $pricingLocked ? 'disabled' : '' ?>>
                      <?php foreach ($availablePricingOptions as $value):
                        $label = $pricingCategories[$value] ?? ucfirst($value);
                        $isSelectedVariant = $value === $selectedPricingKey;
                      ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $isSelectedVariant ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <?php if ($pricingLocked): ?>
                      <div class="text-muted small mt-1">Locked to <?= htmlspecialchars($pricingCategories[$selectedPricingKey] ?? ucfirst($selectedPricingKey)) ?> for this grade.</div>
                    <?php endif; ?>
                  </div>

                  <div>
                    <label>Amount:</label>
                    <input type="number" step="0.01" name="amount" required>
                  </div>

                  <div id="payment-fields-<?= $s['id'] ?>">
                    <div class="cash-field">
                      <label>Official Receipt #:</label>
                      <input type="text" name="or_number">
                    </div>
                    <div class="gcash-field" style="display:none;">
                      <label>Reference #:</label>
                      <input type="text" name="reference_number">
                    </div>
                  </div>

                  <button type="submit">💵 Submit Payment</button>
                </form>
              </div>
              </div>
            </div>
            <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  
  




  <!-- 📑 Records Section -->
  <div class="section-card" id="records">
    <h2>Payment Records</h2>
    <table id="paymentTable">
      <thead><tr><th>Date</th><th>Student</th><th>Type</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
  <?php while ($row = $payments->fetch_assoc()): ?>
    <tr>
      <td><?= date("Y-m-d", strtotime($row['created_at'])) ?></td>
      <td><?= htmlspecialchars($row['lastname']) ?>, <?= htmlspecialchars($row['firstname']) ?> <?= htmlspecialchars($row['middlename']) ?></td>
      <td><?= htmlspecialchars($row['payment_type']) ?></td>
      <td>₱ <?= number_format($row['amount'], 2) ?></td>
      <td id="status-<?= $row['id'] ?>"><?= htmlspecialchars($row['payment_status'] ?? 'Pending') ?></td>
      <td>
        <button class="view-payment-btn"
          data-id="<?= $row['id'] ?>" 
          data-student="<?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname']) ?>" 
          data-type="<?= htmlspecialchars($row['payment_type']) ?>" 
          data-amount="<?= $row['amount'] ?>" 
          data-status="<?= htmlspecialchars($row['payment_status'] ?? 'Pending') ?>" 
          data-ref="<?= htmlspecialchars($row['reference_number'] ?? $row['or_number'] ?? 'N/A') ?>" 
          data-screenshot="<?= htmlspecialchars($row['screenshot_path'] ?? '') ?>">
          🔎 View Payment
        </button>
      </td>
    </tr>
  <?php endwhile; ?>
</tbody>


      <tbody>
        <?php while ($row = $payments->fetch_assoc()): ?>
          <tr>
            <td><?= date("Y-m-d", strtotime($row['created_at'])) ?></td>
            <td><?= $row['lastname'] ?>, <?= $row['firstname'] ?></td>
            <td><?= $row['payment_type'] ?></td>
            <td>₱ <?= number_format($row['amount'], 2) ?></td>
            <td><?= htmlspecialchars($row['payment_status'] ?? 'Pending') ?></td>
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
<script>
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

</body>
</html>
