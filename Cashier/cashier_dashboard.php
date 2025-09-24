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
                            $viewScheduleRows = $viewData['schedule_rows'] ?? [];
                            $viewScheduleMessage = $viewData['schedule_message'] ?? 'No schedule information available.';
                            $viewPendingRows = $viewData['pending_rows'] ?? [];
                            $viewPendingMessage = $viewData['pending_message'] ?? 'No pending payments for this grade.';
                            $viewHistoryRows = $viewData['history_rows'] ?? [];
                            $viewHistoryMessage = $viewData['history_message'] ?? 'No payments recorded yet for this grade.';
                            $viewYearTotal = $viewData['current_year_total'] ?? 0;
                        ?>
                        <div class="cashier-view" data-student="<?= $s['id'] ?>" data-view="<?= htmlspecialchars($viewKey) ?>" style="<?= $isDefaultView ? '' : 'display:none;' ?>">
                            <div class="search-summary-tiles" style="display:flex; flex-wrap:wrap; gap:12px;">
                                <div class="summary-tile-sm">
                                    <span class="label">Remaining Balance</span>
                                    <strong>₱<?= number_format($viewRemaining, 2) ?></strong>
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
                                    <strong>₱<?= number_format($viewYearTotal, 2) ?></strong>
                                </div>
                            </div>

                            <?php if (!empty($viewAlert)): ?>
                                <div class="search-alert" style="margin:18px 0; padding:12px 16px; border-left:4px solid #e59819; background:rgba(251,216,10,0.16); color:#8a6d0a; border-radius:10px;">
                                    Past balance from <?= htmlspecialchars($viewAlert['grade']) ?>: <strong>₱<?= number_format($viewAlert['amount'], 2) ?></strong>
                                </div>
                            <?php endif; ?>

                            <div class="search-subsection">
                                <h4>Upcoming Tuition Schedule</h4>
                                <?php if (!empty($viewPlanLabel) || $viewNextDue): ?>
                                    <p style="margin:0 0 10px; color:#5d6d6f; font-size:0.9rem;">
                                        <?php if ($viewPlanLabel): ?>Plan: <strong><?= htmlspecialchars($viewPlanLabel) ?></strong><?php endif; ?>
                                        <?php if ($viewNextDue): ?>
                                            <span>• Next due on <strong><?= htmlspecialchars($viewNextDue['due_date']) ?></strong></span>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($viewScheduleRows)): ?>
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

                                                $displayAmount = '';
                                                if (array_key_exists('amount_outstanding', $row)) {
                                                    $originalRaw = (float) ($row['amount_original'] ?? 0);
                                                    $outstandingRaw = (float) ($row['amount_outstanding'] ?? 0);
                                                    $original = round($originalRaw, 2);
                                                    $outstanding = round($outstandingRaw, 2);

                                                    if ($outstanding <= 0.009) {
                                                        $displayAmount = '<span class="text-success fw-semibold">Paid</span>';
                                                        if ($original > 0) {
                                                            $displayAmount .= ' <span class="text-muted">(₱' . number_format($original, 2) . ')</span>';
                                                        }
                                                    } elseif ($original > $outstanding + 0.009) {
                                                        $displayAmount = '₱' . number_format($outstanding, 2) . ' <span class="text-muted" style="font-size:0.82rem;">of ₱' . number_format($original, 2) . ' remaining</span>';
                                                    } else {
                                                        $displayAmount = '₱' . number_format($outstanding, 2);
                                                    }
                                                } else {
                                                    $displayAmount = '₱' . number_format((float) ($row['amount'] ?? 0), 2);
                                                }
                                            ?>
                                                <tr class="<?= $rowClass ?>">
                                                    <td><?= htmlspecialchars($row['due_date']) ?></td>
                                                    <td class="text-end"><?= $displayAmount ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin:16px 0 0; color:#5d6d6f;"><?= htmlspecialchars($viewScheduleMessage) ?></p>
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
                                                <th>Date</th>
                                                <th>Method</th>
                                                <th class="text-end">Amount Applied</th>
                                                <th>Reference</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($viewHistoryRows as $history_row):
                                                $historyAmount = (float) ($history_row['applied_amount'] ?? $history_row['amount'] ?? 0);
                                                $historySource = (float) ($history_row['source_amount'] ?? $historyAmount);
                                                $isPartial = !empty($history_row['is_partial']) && $historySource > 0 && abs($historyAmount - $historySource) > 0.009;
                                                $referenceVal = $history_row['reference_number'] ?? $history_row['or_number'] ?? null;
                                                $dateDisplay = $history_row['payment_date'] ?? ($history_row['created_at'] ?? '--');
                                                $statusLabel = ucfirst(strtolower((string) ($history_row['payment_status'] ?? 'Paid')));
                                            ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($dateDisplay) ?></td>
                                                    <td><?= htmlspecialchars(ucfirst((string) ($history_row['payment_type'] ?? 'N/A'))) ?></td>
                                                    <td class="text-end">
                                                        ₱<?= number_format($historyAmount, 2) ?>
                                                        <?php if ($isPartial): ?>
                                                            <div style="color:#7a8a84; font-size:0.8rem;">From ₱<?= number_format($historySource, 2) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($referenceVal ?: 'N/A') ?></td>
                                                    <td><span style="color:#1e8449; font-weight:600;"><?= htmlspecialchars($statusLabel) ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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
                                    <input type="number" name="amount" step="0.01" min="0" required value="<?= $snapshot && $snapshot['next_due_row'] ? number_format($snapshot['next_due_row']['amount'], 2, '.', '') : '' ?>">
                                    <?php if ($snapshot && $snapshot['next_due_row']): ?>
                                        <small style="display:block; color:#5d6d6f;">Next due on <?= htmlspecialchars($snapshot['next_due_row']['due_date']) ?> for ₱<?= number_format($snapshot['next_due_row']['amount'], 2) ?></small>
                                    <?php endif; ?>
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

                            <button type="submit" class="record-btn">Record Payment</button>
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

      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div>
          <label for="school_year">School Year</label>
          <input type="text" id="school_year" name="school_year" placeholder="e.g., 2024-2025" required>
        </div>

        <div>
          <label for="year">Grade Level</label>
          <select id="year" name="year" required>
            <?php
              $gradeOptions = [
                'kinder1' => 'Kinder 1',
                'kinder2' => 'Kinder 2',
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
              foreach ($gradeOptions as $value => $label) {
                  echo "<option value='{$value}'>{$label}</option>";
              }
            ?>
          </select>
        </div>

        <div>
          <label for="student_type">Student Type</label>
          <select id="student_type" name="student_type" required>
            <option value="new">New</option>
            <option value="old">Old</option>
          </select>
        </div>

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

        <div>
          <label for="total_upon_enrollment">Total Upon Enrollment</label>
          <input type="number" step="0.01" id="total_upon_enrollment" name="total_upon_enrollment" required>
        </div>

        <div>
          <label for="payment_schedule">Payment Schedule</label>
          <select id="payment_schedule" name="payment_schedule" onchange="calculatePayment()" required>
            <option value="annually">Annually</option>
            <option value="semi-annually">Semi-Annually</option>
            <option value="quarterly">Quarterly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>

        <div>
          <label for="calculated_payment">Calculated Payment</label>
          <input type="text" id="calculated_payment" readonly>
        </div>
      </div>

      <button type="submit">Save Tuition Fee</button>
    </form>
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
