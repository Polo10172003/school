<?php
include 'db_connection.php';

/* ---------------- Summary Cards ---------------- */
$total_students = $conn->query("SELECT COUNT(*) AS c FROM students_registration")->fetch_assoc()['c'];
$total_enrolled = $conn->query("SELECT COUNT(*) AS c FROM students_registration WHERE enrollment_status='enrolled'")->fetch_assoc()['c'];
$total_paid = $conn->query("SELECT SUM(amount) AS t FROM student_payments WHERE payment_status='paid'")->fetch_assoc()['t'] ?? 0;
$total_pending = $conn->query("SELECT SUM(amount) AS t FROM student_payments WHERE payment_status='pending'")->fetch_assoc()['t'] ?? 0;

/* ---------------- Enrollment by Year ---------------- */
$query = "
    SELECT YEAR(created_at) AS year, COUNT(*) AS count
    FROM student_payments
    WHERE payment_status IN ('paid', 'pending') 
      AND payment_date IS NOT NULL
    GROUP BY year
    ORDER BY year DESC
";
$result = $conn->query($query);
$enrollment_data = [];
while ($row = $result->fetch_assoc()) {
    $enrollment_data[(int)$row['year']] = (int)$row['count'];
}
$current_year = date("Y");
$current_year_count = $enrollment_data[$current_year] ?? 0;
$previous_year = $enrollment_data[$current_year - 1] ?? 0;
$other_years_count = array_sum($enrollment_data) - $current_year_count - $previous_year;
$labels_years = ["This Year ($current_year)", "Last Year (" . ($current_year - 1) . ")", "Other Years"];
$data_years = [$current_year_count, $previous_year, $other_years_count];

/* ---------------- Payment Overview ---------------- */
$query = "
    SELECT payment_status, SUM(amount) AS total
    FROM student_payments
    GROUP BY payment_status
";
$result = $conn->query($query);
$payments = [];
while ($row = $result->fetch_assoc()) {
    $payments[$row['payment_status']] = (float)$row['total'];
}
$labels_payments = ["Paid", "Pending", "Declined"];
$data_payments = [
    $payments['paid'] ?? 0,
    $payments['pending'] ?? 0,
    $payments['declined'] ?? 0
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard Statistics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background:#f4f6f9; margin:0; padding:20px; }
        h2 { color:#004d00; }

        .cards {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }
        .card {
            flex: 1;
            margin: 10px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card h3 { margin: 10px 0; font-size: 28px; }
        .card p { color: #666; margin: 0; }

        .chart-toggle {
            text-align: center;
            margin: 20px 0 30px;
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .chart-toggle button {
            padding: 10px 20px;
            border-radius: 999px;
            border: 2px solid #145A32;
            background: transparent;
            color: #145A32;
            font-weight: 600;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .chart-toggle button.active,
        .chart-toggle button:hover {
            background: linear-gradient(135deg, rgba(20, 90, 50, 0.85), rgba(20, 90, 50, 0.65));
            color: #ffffff;
        }

        .chart-container {
            width: 100%;
            max-width: 620px;
            margin: 0 auto 30px;
            background:white;
            padding:24px;
            border-radius:16px;
            box-shadow: 0 12px 25px rgba(12, 68, 49, 0.12);
        }
    </style>
</head>
<body>

<h2>📊 Admin Statistics Dashboard</h2>

<!-- ✅ Summary Cards -->
<div class="cards">
    <div class="card">
        <h3><?= number_format($total_students) ?></h3>
        <p>Total Students</p>
    </div>
    <div class="card">
        <h3><?= number_format($total_enrolled) ?></h3>
        <p>Enrolled Students</p>
    </div>
    <div class="card">
        <h3>₱<?= number_format($total_paid, 2) ?></h3>
        <p>Total Paid</p>
    </div>
    <div class="card">
        <h3>₱<?= number_format($total_pending, 2) ?></h3>
        <p>Total Pending</p>
    </div>
</div>

<!-- Chart Toggle Buttons -->
<div class="chart-toggle">
  <button class="active" data-chart="enrollmentYearChart" onclick="showChart(event, 'enrollmentYearChart')">Enrollment by Year</button>
  <button data-chart="paymentOverviewChart" onclick="showChart(event, 'paymentOverviewChart')">Payment Overview</button>
</div>

<!-- Chart Containers -->
<div id="enrollmentYearChartDiv" class="chart-container"><canvas id="enrollmentYearChart"></canvas></div>
<div id="paymentOverviewChartDiv" class="chart-container" style="display:none;"><canvas id="paymentOverviewChart"></canvas></div>

<script>
function showChart(evt, chartId) {
  document.querySelectorAll('.chart-container').forEach(c => c.style.display = 'none');
  var target = document.getElementById(chartId + 'Div');
  if (target) {
    target.style.display = 'block';
  }
  document.querySelectorAll('.chart-toggle button').forEach(btn => btn.classList.remove('active'));
  if (evt && evt.currentTarget) {
    evt.currentTarget.classList.add('active');
  }
}
</script>


<script>
/* Enrollment by Year (Doughnut) */
new Chart(document.getElementById('enrollmentYearChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($labels_years); ?>,
        datasets: [{
            data: <?php echo json_encode($data_years); ?>,
            backgroundColor: ['#2ecc71', '#3498db', '#95a5a6']
        }]
    },
    options: {
        plugins: {
            title: { display: true, text: 'Enrollment by Year' },
            legend: { position: 'bottom' }
        }
    }
});

/* Payment Overview (Bar) */
new Chart(document.getElementById('paymentOverviewChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels_payments); ?>,
        datasets: [{
            label: 'Total Amount (₱)',
            data: <?php echo json_encode($data_payments); ?>,
            backgroundColor: ['#27ae60', '#f1c40f', '#e74c3c']
        }]
    },
    options: {
        plugins: {
            title: { display: true, text: 'Payment Overview' },
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

</body>
</html>
