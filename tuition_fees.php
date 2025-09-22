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
                    <option value="kinder1">Kinder 1</option>
                    <option value="kinder2">Kinder 2</option>
                    <option value="grade1">Grade 1</option>
                    <option value="grade2">Grade 2</option>
                    <option value="grade3">Grade 3</option>
                    <option value="grade4">Grade 4</option>
                    <option value="grade5">Grade 5</option>
                    <option value="grade6">Grade 6</option>
                    <option value="grade7">Grade 7 (Junior High)</option>
                    <option value="grade8">Grade 8</option>
                    <option value="grade9">Grade 9</option>
                    <option value="grade10">Grade 10</option>
                    <option value="grade11">Grade 11 (Senior High)</option>
                    <option value="grade12">Grade 12</option>
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

            $query = "SELECT * FROM tuition_fees
                     WHERE school_year='$school_year' 
                     AND student_type='$student_type' 
                     AND grade_level='$year' 
                     ORDER BY grade_level ASC";
            $result = mysqli_query($conn, $query);

            if (!$result) {
                die("Query failed: " . mysqli_error($conn));
            }
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $entrance_fee = $row['entrance_fee'];
                    $misc_fee     = $row['miscellaneous_fee'];
                    $tuition_fee  = $row['tuition_fee'];
                    $total_fee    = $entrance_fee + $misc_fee + $tuition_fee;

                    // ---- Payment Schedules ----
                    $annual_fee   = $total_fee;
                    $cash_total   = ($entrance_fee * 0.9) + $misc_fee + $tuition_fee;

                    $sa_first = $entrance_fee + $misc_fee + ($tuition_fee / 2);
                    $sa_next  = $tuition_fee / 2;

                    $q_first = $entrance_fee + $misc_fee + ($tuition_fee / 4);
                    $q_next  = $tuition_fee / 4;

                    $m_first = $entrance_fee + $misc_fee + round($tuition_fee / 9, 2);
                    $m_next  = round($tuition_fee / 9, 2);

                    echo "<div class='table-responsive'>
                        <table class='table table-bordered table-striped'>
                            <thead class='table-dark'>
                                <tr>
                                    <th>Category</th>
                                    <th>Annually</th>
                                    <th>Cash (10% off Entrance)</th>
                                    <th>Semi-Annually</th>
                                    <th>Quarterly</th>
                                    <th>Monthly</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Annual Fee</td>
                                    <td>₱" . number_format($total_fee, 2) . "</td>
                                    <td>₱" . number_format($total_fee, 2) . "</td>
                                    <td>₱" . number_format($total_fee, 2) . "</td>
                                    <td>₱" . number_format($total_fee, 2) . "</td>
                                    <td>₱" . number_format($total_fee, 2) . "</td>
                                </tr>
                                <tr>
                                    <td>Entrance Fee</td>
                                    <td>₱" . number_format($entrance_fee, 2) . "</td>
                                    <td>₱" . number_format($entrance_fee * 0.9, 2) . "</td>
                                    <td>₱" . number_format($entrance_fee, 2) . "</td>
                                    <td>₱" . number_format($entrance_fee, 2) . "</td>
                                    <td>₱" . number_format($entrance_fee, 2) . "</td>
                                </tr>
                                <tr>
                                    <td>Miscellaneous Fee</td>
                                    <td>₱" . number_format($misc_fee, 2) . "</td>
                                    <td>₱" . number_format($misc_fee, 2) . "</td>
                                    <td>₱" . number_format($misc_fee, 2) . "</td>
                                    <td>₱" . number_format($misc_fee, 2) . "</td>
                                    <td>₱" . number_format($misc_fee, 2) . "</td>
                                </tr>
                                <tr>
                                    <td>Tuition Fee</td>
                                    <td>₱" . number_format($tuition_fee, 2) . "</td>
                                    <td>₱" . number_format($tuition_fee, 2) . "</td>
                                    <td>₱" . number_format($tuition_fee / 2, 2) . " each</td>
                                    <td>₱" . number_format($tuition_fee / 4, 2) . " each</td>
                                    <td>₱" . number_format($tuition_fee / 9, 2) . " each</td>
                                </tr>
                                <tr>
                                    <td>Next Payment</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>₱" . number_format($sa_next, 2) . "</td>
                                    <td>₱" . number_format($q_next, 2) . "</td>
                                    <td>₱" . number_format($m_next, 2) . "</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Upon Enrollment</strong></td>
                                    <td><strong>₱" . number_format($annual_fee, 2) . "</strong></td>
                                    <td><strong>₱" . number_format($cash_total, 2) . "</strong></td>
                                    <td><strong>₱" . number_format($sa_first, 2) . "</strong></td>
                                    <td><strong>₱" . number_format($q_first, 2) . "</strong></td>
                                    <td><strong>₱" . number_format($m_first, 2) . "</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>";
                }
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
