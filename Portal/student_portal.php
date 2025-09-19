<?php
session_start();

include __DIR__ . '/../db_connection.php';
include '../includes/header.php';

// Make sure student is logged in
if (!isset($_SESSION['student_email'])) {
    header("Location: student_login.php");
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

<div class="container">

    <div class="card">
        <h2>Welcome, <?php echo htmlspecialchars($firstname . " " . $lastname); ?></h2>
        <p>Grade: <?php echo htmlspecialchars($year); ?></p>
        <p>Section: <?php echo htmlspecialchars($section ?: 'Not Assigned'); ?></p>
        <p>Adviser: <?php echo htmlspecialchars($adviser ?: 'TBA'); ?></p>
    </div>

    <div class="card">
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

        <form action="choose_payment.php" method="GET" style="margin-top:15px; text-align:center;">
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
</body>
</html>
