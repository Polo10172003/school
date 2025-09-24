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

// Make sure student is logged in
if (!isset($_SESSION['student_number'])) {
    header("Location: student_login.php");
    exit();
}
include '../includes/header.php';


$student_number = $_SESSION['student_number'];

$sql = "SELECT id, firstname, lastname, year, section, adviser, student_type, gender
        FROM students_registration 
        WHERE student_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_number);
$stmt->execute();
$stmt->bind_result($student_id, $firstname, $lastname, $year, $section, $adviser, $student_type, $gender);
$stmt->fetch();
$stmt->close();

if (!$student_id) {
    die("Student account not found in students_registration.");
}
// Fetch tuition fee setup from admin
$normalized_year = strtolower(str_replace(' ', '', $year));

$sql = "SELECT * FROM tuition_fees 
        WHERE REPLACE(LOWER(grade_level), ' ', '') = ? 
        AND LOWER(student_type) = ? 
        ORDER BY school_year DESC LIMIT 1";
$fee = null;
$lower_type = strtolower($student_type);
$typeCandidates = [$lower_type, 'new', 'old'];
$typeCandidates = array_values(array_unique($typeCandidates));

foreach ($typeCandidates as $candidateType) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $normalized_year, $candidateType);
    $stmt->execute();
    $fee = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($fee) {
        break;
    }
}

function previousGradeLabel($current)
{
    $map = [
        'Kinder 1' => 'Preschool',
        'Kinder 2' => 'Kinder 1',
        'Grade 1'  => 'Kinder 2',
        'Grade 2'  => 'Grade 1',
        'Grade 3'  => 'Grade 2',
        'Grade 4'  => 'Grade 3',
        'Grade 5'  => 'Grade 4',
        'Grade 6'  => 'Grade 5',
        'Grade 7'  => 'Grade 6',
        'Grade 8'  => 'Grade 7',
        'Grade 9'  => 'Grade 8',
        'Grade 10' => 'Grade 9',
        'Grade 11' => 'Grade 10',
        'Grade 12' => 'Grade 11',
    ];
    return $map[$current] ?? null;
}

$previous_grade_label = previousGradeLabel($year);
$grade_key = strtolower(str_replace(' ', '', $year));
$no_previous = ($lower_type === 'new') || in_array($grade_key, ['preschool', 'kinder1', 'kinder_1', 'kinder-1', 'grade1']);

$previous_fee = null;
if (!$no_previous && $previous_grade_label) {
    $normalized_prev = strtolower(str_replace(' ', '', $previous_grade_label));
    foreach ($typeCandidates as $candidateType) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $normalized_prev, $candidateType);
        $stmt->execute();
        $previous_fee = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($previous_fee) {
            break;
        }
    }
} else {
    $previous_grade_label = null;
}

// Fetch payments
$sql = "SELECT payment_type, amount, payment_status, payment_date, reference_number ,or_number
        FROM student_payments 
        WHERE student_id = ?
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$paid = [];
$pending = [];

while ($row = $result->fetch_assoc()) {
    if (strtolower($row['payment_status']) === 'paid') {
        $paid[] = $row;
    } else {
        $pending[] = $row;
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
$total_paid = array_sum(array_column($paid, 'amount'));

$previous_paid_applied = 0;
$previous_outstanding = 0;
if ($previous_fee) {
    $previous_paid_applied = min($total_paid, $previous_grade_total);
    $previous_outstanding = max($previous_grade_total - $total_paid, 0);
}

$current_paid_amount = max($total_paid - $previous_paid_applied, 0);
$current_paid_remaining = $current_paid_amount;

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

if (!function_exists('generateScheduleFromDB')) {
    /**
     * Build tuition fee schedule based on selected plan.
     */
    function generateScheduleFromDB($fee, $plan, $start_date = '2025-06-01')
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
}

$chosen_plan = $fee ? 'quarterly' : null; // Default plan until saved in enrollment record
$upcoming_rows = [];
$first_unpaid_index = null;
$upcoming_total = 0.0;

if ($fee) {
    $schedule = generateScheduleFromDB($fee, $chosen_plan);
    $remaining = $current_paid_remaining;

    foreach ($schedule as $idx => $due) {
        $due_amount = (float) $due['Amount'];
        if ($remaining > 0) {
            $deduct = min($remaining, $due_amount);
            $due_amount -= $deduct;
            $remaining -= $deduct;
        }

        if ($due_amount <= 0) {
            continue;
        }

        if ($first_unpaid_index === null) {
            $first_unpaid_index = $idx;
        }

        $upcoming_rows[] = [
            'index' => $idx,
            'due_date' => $due['Due Date'],
            'amount' => $due_amount,
        ];
    }

    foreach ($upcoming_rows as &$row) {
        $row['is_next'] = ($row['index'] === $first_unpaid_index);
        $upcoming_total += $row['amount'];
    }
    unset($row);
}

$pending_display = [];
foreach ($pending as $p) {
    $p['payment_type_display'] = $p['payment_type'] ?? 'Pending';
    $p['is_placeholder'] = false;
    $pending_display[] = $p;
}

if ($previous_grade_label && $previous_outstanding > 0 && empty($pending_display)) {
    $pending_display[] = [
        'payment_type_display' => 'Past Due for ' . $previous_grade_label,
        'amount' => $previous_outstanding,
        'payment_status' => 'Past Due',
        'payment_date' => 'Previous School Year',
        'reference_number' => null,
        'or_number' => null,
        'is_placeholder' => true,
    ];
}

$current_year_total = $fee
    ? (float) $fee['entrance_fee'] + (float) $fee['miscellaneous_fee'] + (float) $fee['tuition_fee']
    : 0.0;

$remaining_balance = $previous_outstanding + $upcoming_total;
$has_previous_outstanding = $previous_grade_label && $previous_outstanding > 0;
$next_due_row = null;
foreach ($upcoming_rows as $row) {
    if (!empty($row['is_next'])) {
        $next_due_row = $row;
        break;
    }
}
unset($row);
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
                <div class="card portal-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                            <div>
                                <h3 class="h5 fw-bold mb-1">Payment Overview</h3>
                                <p class="text-muted mb-0">Track your tuition progress for this school year.</p>
                            </div>
                            <?php if ($has_previous_outstanding): ?>
                                <span class="status-pill warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Past balance: ₱<?php echo number_format($previous_outstanding, 2); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-pill safe">
                                    <i class="bi bi-check-circle"></i>
                                    Account in good standing
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-sm-6 col-lg-3">
                                <div class="summary-tile h-100">
                                    <span class="label">Tuition Package</span>
                                    <h4>₱<?php echo number_format($current_year_total, 2); ?></h4>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="summary-tile h-100">
                                    <span class="label">Remaining Balance</span>
                                    <h4>₱<?php echo number_format($remaining_balance, 2); ?></h4>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="summary-tile h-100">
                                    <span class="label">Total Paid</span>
                                    <h4>₱<?php echo number_format($total_paid, 2); ?></h4>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="summary-tile h-100">
                                    <span class="label">Pending Review</span>
                                    <h4>₱<?php echo number_format($pending_total, 2); ?></h4>
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
                                <?php if ($fee): ?>
                                    <div class="text-muted small">
                                        Plan: <strong class="text-success text-uppercase"><?php echo htmlspecialchars($chosen_plan); ?></strong>
                                        <?php if ($next_due_row): ?>· Next due on <strong><?php echo htmlspecialchars($next_due_row['due_date']); ?></strong><?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted small mb-0">No tuition fee configuration is available yet.</p>
                                <?php endif; ?>
                            </div>
                            <form action="Portal/choose_payment.php" method="GET" class="ms-lg-auto">
                                <input type="hidden" name="student_id" value="<?php echo (int) $student_id; ?>">
                                <button type="submit" class="btn btn-success fw-semibold">
                                    <i class="bi bi-credit-card me-2"></i>Pay Now
                                </button>
                            </form>
                        </div>

                        <?php if ($has_previous_outstanding): ?>
                            <div class="alert alert-warning border-0 text-dark">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                Past due from <?php echo htmlspecialchars($previous_grade_label); ?>: <strong>₱<?php echo number_format($previous_outstanding, 2); ?></strong>.
                                This is added to your current schedule.
                            </div>
                        <?php endif; ?>

                        <?php if ($fee && count($upcoming_rows) > 0): ?>
                            <div class="table-responsive">
                                <table class="table portal-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">Due Date</th>
                                            <th scope="col" class="text-end">Amount Due</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcoming_rows as $row): ?>
                                            <tr class="<?php echo !empty($row['is_next']) ? 'next-due' : ''; ?>">
                                                <td class="fw-semibold"><?php echo htmlspecialchars($row['due_date']); ?></td>
                                                <td class="text-end">₱<?php echo number_format($row['amount'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($fee): ?>
                            <div class="empty-state mt-3">
                                <i class="bi bi-check2-circle me-2"></i>All your scheduled payments are covered at the moment.
                            </div>
                        <?php else: ?>
                            <div class="empty-state mt-3">
                                <i class="bi bi-info-circle me-2"></i>Your tuition details will appear here once released by the registrar.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card portal-card">
                    <div class="card-body">
                        <h3 class="h5 fw-bold mb-3">Pending Payments</h3>
                        <?php if (count($pending_display) > 0): ?>
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
                                        <?php foreach ($pending_display as $pending_row): ?>
                                            <?php
                                                $is_placeholder_row = !empty($pending_row['is_placeholder']);
                                            ?>
                                            <tr class="<?php echo $is_placeholder_row ? 'row-outstanding' : ''; ?>">
                                                <td><?php echo htmlspecialchars($pending_row['payment_type_display'] ?? ($pending_row['payment_type'] ?? 'Pending')); ?></td>
                                                <td class="text-end">₱<?php echo number_format((float) $pending_row['amount'], 2); ?></td>
                                                <td><span class="badge bg-warning-subtle text-warning fw-semibold">Pending</span></td>
                                                <td><?php echo htmlspecialchars($pending_row['payment_date'] ?: 'Awaiting review'); ?></td>
                                                <td><?php echo htmlspecialchars($pending_row['reference_number'] ?: 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-check2-circle me-2"></i>No pending payments right now.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card portal-card mb-0">
                    <div class="card-body">
                        <h3 class="h5 fw-bold mb-3">Payment History</h3>
                        <?php if (count($paid) > 0): ?>
                            <div class="table-responsive">
                                <table class="table portal-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">Payment Type</th>
                                            <th scope="col" class="text-end">Amount</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Payment Date</th>
                                            <th scope="col">Reference</th>
                                            <th scope="col">OR Number</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paid as $history): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($history['payment_type']); ?></td>
                                                <td class="text-end">₱<?php echo number_format((float) $history['amount'], 2); ?></td>
                                                <td><span class="badge bg-success-subtle text-success fw-semibold"><?php echo ucfirst($history['payment_status']); ?></span></td>
                                                <td><?php echo htmlspecialchars($history['payment_date'] ?: 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($history['reference_number'] ?: 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($history['or_number'] ?: 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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
    // Auto logout if student navigates away or closes tab
    window.addEventListener('blur', function () {
        fetch('Portal/logout.php')
            .then(function () {
                window.location.href = 'Portal/student_login.php';
            });
    });
</script>

<?php include '../includes/footer.php'; ?>
