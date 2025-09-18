<?php
session_start();

include __DIR__ . '/../db_connection.php';

// Make sure student is logged in
if (!isset($_SESSION['student_email'])) {
        header ("Location: student_login.php");
        exit();
}

$student_email = $_SESSION['student_email'];

$sql = "SELECT id, firstname, lastname, year, section, adviser 
        FROM students_registration 
        WHERE emailaddress = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_email);
$stmt->execute();
$stmt->bind_result($student_id, $firstname, $lastname, $year, $section, $adviser);
$stmt->fetch();
$stmt->close();


if (!$student_id) {
    die("Student account not found in students_registration.");
}

// 🔹 Fetch all payments of this student (linked by student_id for reliability)
$sql = "SELECT payment_type, amount, payment_status, payment_date, reference_number ,or_number
        FROM student_payments 
        WHERE student_id = ?
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Separate paid and pending
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
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f6fa; }
        h2 { color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; background: white; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        th { background: #3498db; color: white; }
        .paid { color: green; font-weight: bold; }
        .pending { color: red; font-weight: bold; }
    </style>
</head>
<body>

<h2>Welcome, <?php echo htmlspecialchars($firstname . " " . $lastname); ?></h2>
<p>Grade: <?php echo htmlspecialchars($year); ?></p>
<p>Section: <?php echo htmlspecialchars($section ?: 'Not Assigned'); ?></p>
<p>Adviser: <?php echo htmlspecialchars($adviser ?: 'TBA'); ?></p>

    <h3>📌 Upcoming Payment Dues</h3>
    <?php if (count($pending) > 0): ?>
        <table>
            <tr>
                <th>Payment Type</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Due Date</th>
            </tr>
            <?php foreach ($pending as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['payment_type']); ?></td>
                <td>₱<?php echo number_format($p['amount'], 2); ?></td>
                <td class="pending"><?php echo ucfirst($p['payment_status']); ?></td>
                <td><?php echo $p['payment_date'] ?: 'N/A'; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>✅ No upcoming dues. All payments are cleared!</p>
    <?php endif; ?>

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

</body>
</html>
