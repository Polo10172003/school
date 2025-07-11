<?php
// Include database connection
include('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture the form data from POST
    $school_year = $_POST['school_year'];
    $student_type = $_POST['student_type'];
    $year = $_POST['year'];
    $entrance_fee = $_POST['entrance_fee'];
    $miscellaneous_fee = $_POST['miscellaneous_fee'];
    $tuition_fee = $_POST['tuition_fee'];
    
    $total_upon_enrollment = $_POST['total_upon_enrollment'];

    // Insert the fee data into the tuition_fees table
    $stmt = $conn->prepare("INSERT INTO tuition_fees (school_year, student_type, year, entrance_fee, miscellaneous_fee, tuition_fee, total_upon_enrollment) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $school_year, $student_type, $year, $entrance_fee, $miscellaneous_fee, $tuition_fee,  $total_upon_enrollment);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Tuition fee data saved successfully.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error saving tuition fee data.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tuition Fees</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function calculatePayment() {
            let tuitionFee = parseFloat(document.getElementById('tuition_fee').value);
            let miscFee = parseFloat(document.getElementById('miscellaneous_fee').value);
            let enrollmentFee = parseFloat(document.getElementById('entrance_fee').value);
            let totalFee = tuitionFee + miscFee + enrollmentFee;
            
            let paymentSchedule = document.getElementById('payment_schedule').value;
            let result;

            switch(paymentSchedule) {
                case 'annually':
                    result = totalFee;
                    break;
                case 'semi-annually':
                    result = totalFee / 2;
                    break;
                case 'quarterly':
                    result = totalFee / 4;
                    break;
                case 'monthly':
                    result = totalFee / 12;
                    break;
                default:
                    result = 0;
                    break;
            }

            document.getElementById('calculated_payment').value = result.toFixed(2);
        }
    </script>
</head>
<body>
    <div class="container my-5">
        <h2 class="fw-bold text-center">Manage Tuition Fees</h2>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <!-- Admin Fee Form -->
        <form method="POST" class="row">
            <!-- Fee Fields (Entrance Fee, Miscellaneous Fee, etc.) -->

            <div class="col-md-4 mb-3">
                <label for="school_year" class="form-label">School Year:</label>
                <input type="text" name="school_year" id="school_year" class="form-control" placeholder="e.g., 2024-2025" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="year" class="form-label">Grade Level:</label>
                <select name="year" id="year" class="form-select">
                    <option value="kinder1">Kinder 1</option>
                    <option value="kinder2">Kinder 2</option>
                    <option value="grade1">Grade 1</option>
                    <option value="grade2">Grade 2</option>
                    <option value="grade3">Grade 3</option>
                    <option value="grade4">Grade 4</option>
                    <option value="grade5">Grade 5</option>
                    <option value="grade6">Grade 6</option>
                    <option value="grade7">Grade 7</option>
                    <option value="grade8">Grade 8</option>
                    <option value="grade9">Grade 9</option>
                    <option value="grade10">Grade 10</option>
                    <option value="grade11">Grade 11</option>
                    <option value="grade12">Grade 12</option>
                </select>
            </div>


            <div class="col-md-4 mb-3">
                <label for="student_type" class="form-label">Student Type:</label>
                <select name="student_type" id="student_type" class="form-select" required>
                    <option value="new">New</option>
                    <option value="old">Old</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="entrance_fee" class="form-label">Entrance Fee:</label>
                <input type="number" step="0.01" name="entrance_fee" id="entrance_fee" class="form-control" required oninput="calculatePayment()">
            </div>

            <div class="col-md-4 mb-3">
                <label for="miscellaneous_fee" class="form-label">Miscellaneous Fee:</label>
                <input type="number" step="0.01" name="miscellaneous_fee" id="miscellaneous_fee" class="form-control" required oninput="calculatePayment()">
            </div>

            <div class="col-md-4 mb-3">
                <label for="tuition_fee" class="form-label">Tuition Fee:</label>
                <input type="number" step="0.01" name="tuition_fee" id="tuition_fee" class="form-control" required oninput="calculatePayment()">
            </div>


            <div class="col-md-4 mb-3">
                <label for="total_upon_enrollment" class="form-label">Total Upon Enrollment:</label>
                <input type="number" step="0.01" name="total_upon_enrollment" id="total_upon_enrollment" class="form-control" required oninput="calculatePayment()">
            </div>

            <!-- Payment Schedule Fields -->
            <div class="col-md-4 mb-3">
                <label for="payment_schedule" class="form-label">Payment Schedule:</label>
                <select name="payment_schedule" id="payment_schedule" class="form-select" required onchange="calculatePayment()">
                    <option value="annually">Annually</option>
                    <option value="semi-annually">Semi-Annually</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="calculated_payment" class="form-label">Calculated Payment:</label>
                <input type="text" id="calculated_payment" class="form-control" readonly>
            </div>

            <!-- Other Fields -->
            

           

            
        

            <div class="col-md-12 text-center">
                <button type="submit" class="btn btn-primary">Save Fee</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
