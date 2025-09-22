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

$sql = "SELECT id, firstname, lastname, year, section, adviser , student_type
        FROM students_registration 
        WHERE student_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_number);
$stmt->execute();
$stmt->bind_result($student_id, $firstname, $lastname, $year, $section, $adviser, $student_type);
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
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $normalized_year, $student_type);

$stmt->execute();
$fee = $stmt->get_result()->fetch_assoc();
$stmt->close();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal - Payments</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            background: #f4f6f9;
            color: #2c3e50;
        }
        /* Navbar */
        .navbar {
            background: #2c3e50;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-weight: 500;
            transition: 0.3s;
        }
        .navbar a:hover {
            color: #1abc9c;
        }
        /* Container */
        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        h2, h3 {
            margin-bottom: 15px;
            color: #34495e;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            border: 1px solid #eee;
            text-align: center;
        }
        th {
            background: #3498db;
            color: white;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .paid { color: #27ae60; font-weight: bold; }
        .pending { color: #e74c3c; font-weight: bold; }
        .btn {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn:hover {
            background: #219150;
        }
        
    </style>
</head>
<body>

    <div><a href="Portal/logout.php">🚪 Logout</a></div>

<div class="container">

    <div class="card">
        <h2>Welcome, <?php echo htmlspecialchars($firstname . " " . $lastname); ?></h2>
        <p>Grade: <?php echo htmlspecialchars($year); ?></p>
        <p>Section: <?php echo htmlspecialchars($section ?: 'Not Assigned'); ?></p>
        <p>Adviser: <?php echo htmlspecialchars($adviser ?: 'TBA'); ?></p>
    </div>

    <div class="card">
    <h3>📅 Full Payment Schedule</h3>
    <?php
// Total already paid
$total_paid = array_sum(array_column($paid, 'amount'));
function generateScheduleFromDB($fee, $plan, $start_date = "2025-06-01") {
    $schedule = [];
    $date = new DateTime($start_date);

    $entrance = $fee['entrance_fee'];
    $misc = $fee['miscellaneous_fee'];
    $tuition = $fee['tuition_fee'];
    $annual = $entrance + $misc + $tuition;

    switch (strtolower($plan)) {
        case 'annually':
            $schedule[] = ["Due Date" => $date->format("Y-m-d"), "Amount" => $annual];
            break;

        case 'cash':
            // 10% discount on entrance fee
            $discountedEntrance = $entrance * 0.9;
            $totalCash = $discountedEntrance + $misc + $tuition;
            $schedule[] = ["Due Date" => $date->format("Y-m-d"), "Amount" => $totalCash];
            break;

        case 'semi-annually':
            $upon = $entrance + $misc + ($tuition / 2);
            $next = $tuition / 2;
            $schedule[] = ["Due Date" => $date->format("Y-m-d"), "Amount" => $upon];
            $date->modify("+6 months");
            $schedule[] = ["Due Date" => $date->format("Y-m-d"), "Amount" => $next];
            break;

        case 'quarterly':
            $upon = $entrance + ($misc / 4) + ($tuition / 4);
            $schedule[] = ["Due Date" => $date->format("Y-m-d"), "Amount" => $upon];
            for ($i = 1; $i <= 3; $i++) {
                $date->modify("+3 months");
                $schedule[] = ["Due Date" => $date->format("Y-m-d"), "Amount" => ($misc / 4) + ($tuition / 4)];
            }
            break;

        case 'monthly':
            $upon = $entrance + ($misc / 12) + ($tuition / 12);
            $schedule[] = ["Due Date" => $date->format("Y-m-d"), "Amount" => $upon];
            for ($i = 1; $i <= 11; $i++) {
                $date->modify("+1 month");
                $schedule[] = ["Due Date" => $date->format("Y-m-d"), "Amount" => ($misc / 12) + ($tuition / 12)];
            }
            break;
    }

    return $schedule;
}

if ($fee) {
    $chosen_plan = "quarterly"; // You can change based on enrollment record later
    $schedule = generateScheduleFromDB($fee, $chosen_plan);

    echo "<div class='card'><h3>📌 Upcoming Payment Dues</h3>";
    echo "<table>
            <tr><th>Due Date</th><th>Amount Due</th></tr>";

    foreach ($schedule as $due) {
        $due_amount = $due['Amount'];

        // Deduct what was already paid
        if ($total_paid > 0) {
            if ($total_paid >= $due_amount) {
                $total_paid -= $due_amount;
                continue; // This due is already covered
            } else {
                $due_amount -= $total_paid;
                $total_paid = 0;
            }
        }

        // Show only unpaid/remaining
        echo "<tr>
                <td>{$due['Due Date']}</td>
                <td>₱" . number_format($due_amount, 2) . "</td>
              </tr>";
    }

    echo "</table></div>";
} else {
    echo "<p>No tuition fee setup found for your grade level and student type.</p>";
}
?>


        <form action="Portal/choose_payment.php" method="GET" style="margin-top:15px; text-align:center;">
            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
            <button type="submit" class="btn">💳 Pay Now</button>
        </form>
    </div>

    <div class="card">
        <h3>💰 Payment History</h3>
        <?php if (count($paid) > 0): ?>
            <table>
                <tr>
                    <th>Payment Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th>Reference</th>
                    <th>OR Number</th>
                </tr>
                <?php foreach ($paid as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['payment_type']); ?></td>
                    <td>₱<?php echo number_format($p['amount'], 2); ?></td>
                    <td class="paid"><?php echo ucfirst($p['payment_status']); ?></td>
                    <td><?php echo $p['payment_date'] ?: 'N/A'; ?></td>
                    <td><?php echo $p['reference_number'] ?: 'N/A'; ?></td>
                    <td><?php echo $p['or_number'] ?: 'N/A'; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>ℹ️ No payments have been made yet.</p>
        <?php endif; ?>
    </div>

</div>

<script>
// Auto logout if student navigates away or closes tab
window.addEventListener("blur", function () {
    fetch("logout.php") // Call logout silently
        .then(() => {
            window.location.href = "student_login.php";
        });
});
</script>

</body>
</html>
