<?php
include 'db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tuition Fees</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Tuition & Fees Per Grade Level</h2>
            <p class="text-muted">School Year-Based Pricing for Kinder to Senior High</p>
        </div>

        <form method="GET" class="row mb-4">
            <div class="col-md-4">
                <label for="school_year" class="form-label">School Year:</label>
                <input type="text" name="school_year" id="school_year" class="form-control" 
                    value="<?= isset($_GET['school_year']) ? htmlspecialchars($_GET['school_year']) : '' ?>" 
                    placeholder="Enter School Year (e.g., 2024-2025)" required>
            </div>
            
            <div class="col-md-4">
                <label for="year" class="form-label">Grade Level:</label>
                <select name="year" id="year" class="form-select">
                    <option value="kinder1" <?= (isset($_GET['year']) && $_GET['year'] == 'kinder1') ? 'selected' : '' ?>>Kinder 1</option>
                    <option value="kinder2" <?= (isset($_GET['year']) && $_GET['year'] == 'kinder2') ? 'selected' : '' ?>>Kinder 2</option>
                    <option value="grade1" <?= (isset($_GET['year']) && $_GET['year'] == 'grade1') ? 'selected' : '' ?>>Grade 1</option>
                    <option value="grade2" <?= (isset($_GET['year']) && $_GET['year'] == 'grade2') ? 'selected' : '' ?>>Grade 2</option>
                    <option value="grade3" <?= (isset($_GET['year']) && $_GET['year'] == 'grade3') ? 'selected' : '' ?>>Grade 3</option>
                    <option value="grade4" <?= (isset($_GET['year']) && $_GET['year'] == 'grade4') ? 'selected' : '' ?>>Grade 4</option>
                    <option value="grade5" <?= (isset($_GET['year']) && $_GET['year'] == 'grade5') ? 'selected' : '' ?>>Grade 5</option>
                    <option value="grade6" <?= (isset($_GET['year']) && $_GET['year'] == 'grade6') ? 'selected' : '' ?>>Grade 6</option>
                    <option value="grade7" <?= (isset($_GET['year']) && $_GET['year'] == 'grade7') ? 'selected' : '' ?>>Grade 7 (Junior High)</option>
                    <option value="grade8" <?= (isset($_GET['year']) && $_GET['year'] == 'grade8') ? 'selected' : '' ?>>Grade 8</option>
                    <option value="grade9" <?= (isset($_GET['year']) && $_GET['year'] == 'grade9') ? 'selected' : '' ?>>Grade 9</option>
                    <option value="grade10" <?= (isset($_GET['year']) && $_GET['year'] == 'grade10') ? 'selected' : '' ?>>Grade 10</option>
                    <option value="grade11" <?= (isset($_GET['year']) && $_GET['year'] == 'grade11') ? 'selected' : '' ?>>Grade 11 (Senior High)</option>
                    <option value="grade12" <?= (isset($_GET['year']) && $_GET['year'] == 'grade12') ? 'selected' : '' ?>>Grade 12</option>
                </select>
            </div>

            <div class="col-md-4">
                <label for="student_type" class="form-label">Student Type:</label>
                <select name="student_type" id="student_type" class="form-select">
                    <option value="new" <?= (isset($_GET['student_type']) && $_GET['student_type'] == 'new') ? 'selected' : '' ?>>New</option>
                    <option value="old" <?= (isset($_GET['student_type']) && $_GET['student_type'] == 'old') ? 'selected' : '' ?>>Old</option>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <?php
        if (isset($_GET['school_year']) && isset($_GET['student_type']) && isset($_GET['year'])) {
            $school_year = $_GET['school_year'];
            $student_type = $_GET['student_type'];
            $year = $_GET['year'];

            $query = "SELECT * FROM tuition_fees WHERE school_year='$school_year' AND student_type='$student_type' AND year='$year' ORDER BY year ASC";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) > 0) {
                echo "<div class='table-responsive'><table class='table table-striped table-bordered'>
                    <thead class='table-dark'>
                        <tr>
                            <th>Grade Level</th>
                            <th>Annual Fee</th>
                            <th>Cash</th>
                            <th>Semi-Annually</th>
                            <th>Quarterly</th>
                            <th>Monthly</th>
                        </tr>
                    </thead>
                    <tbody>";

                while ($row = mysqli_fetch_assoc($result)) {
                    $entrance_fee = $row['entrance_fee'];
                    $miscellaneous_fee = $row['miscellaneous_fee'];
                    $tuition_fee = $row['tuition_fee'];
                    $total_fee = $entrance_fee + $miscellaneous_fee + $tuition_fee;

                    // Applying logic for payment schedules
                    $cash_payment = $total_fee;
                    $semi_annually = $total_fee / 2;
                    $quarterly = $total_fee / 4;
                    $monthly = $total_fee / 12;
                    $annual_fee = $total_fee;

                    echo "<tr>
                        <td>{$row['year']}</td>
                        <td>₱" . number_format($annual_fee, 2) . "</td>
                        <td>₱" . number_format($cash_payment, 2) . "</td>
                        <td>₱" . number_format($semi_annually, 2) . "</td>
                        <td>₱" . number_format($quarterly, 2) . "</td>
                        <td>₱" . number_format($monthly, 2) . "</td>
                    </tr>";
                }
                echo "</tbody></table></div>";
            } else {
                echo "<div class='alert alert-warning'>No fee data found for the selected year, grade level, and student type.</div>";
            }
        } else {
            echo "<div class='alert alert-info'>Please select a school year, grade level, and student type to view fees.</div>";
        }
        ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
