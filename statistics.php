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

/* ---------------- Monthly Enrollment Trend ---------------- */
$query = "
    SELECT MONTH(created_at) AS month, COUNT(*) AS count
    FROM students_registration
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
    ORDER BY month
";
$result = $conn->query($query);
$months = array_fill(1, 12, 0);
while ($row = $result->fetch_assoc()) {
    $months[(int)$row['month']] = (int)$row['count'];
}
$labels_months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
$data_months = array_values($months);

/* ---------------- Grade Distribution ---------------- */
$query = "
    SELECT year AS grade, COUNT(*) AS count
    FROM students_registration
    WHERE enrollment_status = 'enrolled'
    GROUP BY year
    ORDER BY grade
";
$result = $conn->query($query);
$grades = [];
while ($row = $result->fetch_assoc()) {
    $grades[$row['grade']] = (int)$row['count'];
}
$labels_grades = array_keys($grades);
$data_grades = array_values($grades);

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

        .chart-container {
            width: 45%;
            display: inline-block;
            margin: 20px;
            vertical-align: top;
            background:white;
            padding:20px;
            border-radius:10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
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
<div style="text-align:center; margin:20px;">
  <button onclick="showChart('enrollmentYearChart')">Enrollment by Year</button>
  <button onclick="showChart('monthlyTrendChart')">Monthly Trend</button>
  <button onclick="showChart('gradeDistributionChart')">Grade Distribution</button>
  <button onclick="showChart('paymentOverviewChart')">Payment Overview</button>
</div>

<!-- Chart Containers -->
<div id="enrollmentYearChartDiv" class="chart-container"><canvas id="enrollmentYearChart"></canvas></div>
<div id="monthlyTrendChartDiv" class="chart-container" style="display:none;"><canvas id="monthlyTrendChart"></canvas></div>
<div id="gradeDistributionChartDiv" class="chart-container" style="display:none;"><canvas id="gradeDistributionChart"></canvas></div>
<div id="paymentOverviewChartDiv" class="chart-container" style="display:none;"><canvas id="paymentOverviewChart"></canvas></div>

<script>
function showChart(chartId) {
  document.querySelectorAll('.chart-container').forEach(c => c.style.display = 'none');
  document.getElementById(chartId+'Div').style.display = 'block';
}
</script>


<script>
/* Enrollment by Year (Pie) */
new Chart(document.getElementById('enrollmentYearChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($labels_years); ?>,
        datasets: [{
            data: <?php echo json_encode($data_years); ?>,
            backgroundColor: ['#36A2EB', '#FF6384', '#C9CBCF']
        }]
    },
    options: { plugins: { title: { display: true, text: 'Enrollment by Year' } } }
});

/* Monthly Enrollment Trend (Line) */
new Chart(document.getElementById('monthlyTrendChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels_months); ?>,
        datasets: [{
            label: 'New Enrollments',
            data: <?php echo json_encode($data_months); ?>,
            borderColor: '#36A2EB',
            fill: false,
            tension:0.3
        }]
    },
    options: { plugins: { title: { display: true, text: 'Monthly Enrollment Trend (This Year)' } } }
});

/* Grade Distribution (Bar) */
new Chart(document.getElementById('gradeDistributionChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels_grades); ?>,
        datasets: [{
            label: 'Students Enrolled',
            data: <?php echo json_encode($data_grades); ?>,
            backgroundColor: '#4CAF50'
        }]
    },
    options: { plugins: { title: { display: true, text: 'Students per Grade' } } }
});

/* Payment Overview (Bar) */
new Chart(document.getElementById('paymentOverviewChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels_payments); ?>,
        datasets: [{
            label: 'Total Amount (₱)',
            data: <?php echo json_encode($data_payments); ?>,
            backgroundColor: ['#4CAF50', '#FFC107', '#F44336']
        }]
    },
    options: { plugins: { title: { display: true, text: 'Payment Overview' } } }
});
</script>

</body>
</html>
