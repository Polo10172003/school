<?php
include 'db_connection.php';

// Query: count enrollments by year where payment_status is paid or pending
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

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Make sure year is integer and count is integer
        $enrollment_data[(int)$row['year']] = (int)$row['count'];
    }
}

$current_year = date("Y");
$current_year_count = $enrollment_data[$current_year] ?? 0;
$previous_year = $current_year - 1;
$previous_year_count = $enrollment_data[$previous_year] ?? 0;

$other_years_count = 0;
foreach ($enrollment_data as $year => $count) {
    if ($year != $current_year && $year != $previous_year) {
        $other_years_count += $count;
    }
}

$labels = ["This Year ($current_year)", "Last Year ($previous_year)", "Other Years"];
$data = [$current_year_count, $previous_year_count, $other_years_count];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Enrollment Statistics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h2>Enrollment Statistics</h2>

<canvas id="enrollmentChart" width="400" height="400"></canvas>

<script>
const ctx = document.getElementById('enrollmentChart').getContext('2d');

const data = {
    labels: <?php echo json_encode($labels); ?>,
    datasets: [{
        label: 'Number of Enrollees',
        data: <?php echo json_encode($data); ?>,
        backgroundColor: [
            'rgba(54, 162, 235, 0.7)',  // This Year
            'rgba(255, 99, 132, 0.7)',  // Last Year
            'rgba(201, 203, 207, 0.7)'  // Other Years
        ],
        borderColor: [
            'rgba(54, 162, 235, 1)',
            'rgba(255, 99, 132, 1)',
            'rgba(201, 203, 207, 1)'
        ],
        borderWidth: 1
    }]
};

const config = {
    type: 'pie',
    data: data,
    options: {
        responsive: false,
        plugins: {
            legend: { position: 'bottom' },
            title: {
                display: true,
                text: 'Enrollment Numbers Comparison (Paid & Pending)'
            }
        }
    },
};

new Chart(ctx, config);
</script>

</body>
</html>
