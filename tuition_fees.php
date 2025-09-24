<?php
include 'db_connection.php';

$schoolYearInput = isset($_GET['school_year']) ? htmlspecialchars($_GET['school_year']) : '';
$selectedGrade = $_GET['year'] ?? '';
$selectedType = $_GET['student_type'] ?? 'new';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tuition Fees</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body.fees-body {
            background: linear-gradient(180deg, rgba(20, 90, 50, 0.08) 0%, rgba(255, 255, 255, 0.9) 55%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2f3e46;
        }

        .fees-main {
            padding: 72px 0 96px;
        }

        .hero-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 36px;
            box-shadow: 0 30px 60px rgba(12, 68, 49, 0.12);
            border: 1px solid rgba(20, 90, 50, 0.08);
            margin-bottom: 32px;
        }

        .hero-card h2 {
            font-weight: 700;
            color: #145A32;
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: rgba(20, 90, 50, 0.08);
            border-radius: 999px;
            color: #145A32;
            font-weight: 600;
        }

        .filter-card {
            border-radius: 22px;
            border: 1px solid rgba(20, 90, 50, 0.08);
            box-shadow: 0 18px 40px rgba(12, 68, 49, 0.10);
            overflow: hidden;
            margin-bottom: 32px;
        }

        .filter-card .card-header {
            background: linear-gradient(135deg, rgba(20, 90, 50, 0.12), rgba(251, 216, 10, 0.20));
            color: #145A32;
            font-weight: 700;
        }

        .filter-card label {
            font-weight: 600;
            color: #145A32;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            border-radius: 12px;
            border: 1px solid rgba(20, 90, 50, 0.2);
            padding: 12px 14px;
        }

        .filter-card .btn-filter {
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 600;
            background: #145A32;
            border: none;
        }

        .filter-card .btn-filter:hover {
            background: #0f4f24;
        }

        .fee-result {
            border-radius: 22px;
            overflow: hidden;
        }

        .fee-result .card-header {
            background: linear-gradient(135deg, rgba(20, 90, 50, 0.14), rgba(251, 216, 10, 0.18));
            color: #145A32;
        }

        .fee-result .badge-plan {
            background: rgba(255, 255, 255, 0.25);
            color: #145A32;
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 0.85rem;
        }

        .plan-summary {
            border-radius: 18px;
            border: 1px solid rgba(20, 90, 50, 0.12);
            padding: 18px;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
        }

        .plan-summary h4 {
            font-size: 1.1rem;
            color: #145A32;
            margin-bottom: 4px;
        }

        .plan-summary .sub-text {
            font-size: 0.84rem;
            color: #6c757d;
        }

        .table-fees table thead {
            background: #145A32;
            color: #fff;
        }

        .table-fees table tbody tr:hover {
            background: rgba(20, 90, 50, 0.06);
        }

        .info-alert,
        .empty-alert {
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-alert {
            border: 1px solid rgba(20, 90, 50, 0.15);
            background: rgba(20, 90, 50, 0.05);
            color: #145A32;
        }

        .empty-alert {
            border: 1px solid rgba(231, 76, 60, 0.2);
            background: rgba(231, 76, 60, 0.08);
            color: #c0392b;
        }

        @media (max-width: 767px) {
            .hero-card,
            .filter-card,
            .fee-result {
                border-radius: 18px;
            }

            .hero-card {
                padding: 28px 24px;
            }

            .plan-summary {
                text-align: center;
            }

            .plan-summary .sub-text {
                display: block;
            }
        }
    </style>
</head>
<body class="fees-body">
    <main class="fees-main">
        <div class="container">
            <section class="hero-card">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <span class="hero-pill"><i class="bi bi-graph-up-arrow"></i> Updated tuition matrix</span>
                        <h2 class="mt-3 mb-3">Tuition &amp; Fees Per Grade Level</h2>
                        <p class="mb-0 text-muted">Preview the latest entrance, miscellaneous, and tuition fees per student type. Pick a school year to see the exact breakdown.</p>
                    </div>
                    <div class="text-lg-end">
                        <img src="assets/img/student-neutral.svg" alt="Students" width="120" class="d-none d-lg-block">
                    </div>
                </div>
            </section>

            <div class="card filter-card">
                <div class="card-header py-3 px-4">
                    <i class="bi bi-funnel me-2"></i>Filter fees
                </div>
                <div class="card-body p-4">
                    <form method="GET" class="row g-4 align-items-end">
                        <div class="col-md-4 col-lg-3">
                            <label for="school_year" class="form-label">School Year</label>
                            <input type="text" name="school_year" id="school_year" class="form-control" value="<?= $schoolYearInput ?>" placeholder="e.g., 2024-2025" required>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <label for="year" class="form-label">Grade Level</label>
                            <select name="year" id="year" class="form-select">
                                <option value="kinder1" <?= $selectedGrade === 'kinder1' ? 'selected' : '' ?>>Kinder 1</option>
                                <option value="kinder2" <?= $selectedGrade === 'kinder2' ? 'selected' : '' ?>>Kinder 2</option>
                                <option value="grade1" <?= $selectedGrade === 'grade1' ? 'selected' : '' ?>>Grade 1</option>
                                <option value="grade2" <?= $selectedGrade === 'grade2' ? 'selected' : '' ?>>Grade 2</option>
                                <option value="grade3" <?= $selectedGrade === 'grade3' ? 'selected' : '' ?>>Grade 3</option>
                                <option value="grade4" <?= $selectedGrade === 'grade4' ? 'selected' : '' ?>>Grade 4</option>
                                <option value="grade5" <?= $selectedGrade === 'grade5' ? 'selected' : '' ?>>Grade 5</option>
                                <option value="grade6" <?= $selectedGrade === 'grade6' ? 'selected' : '' ?>>Grade 6</option>
                                <option value="grade7" <?= $selectedGrade === 'grade7' ? 'selected' : '' ?>>Grade 7 (Junior High)</option>
                                <option value="grade8" <?= $selectedGrade === 'grade8' ? 'selected' : '' ?>>Grade 8</option>
                                <option value="grade9" <?= $selectedGrade === 'grade9' ? 'selected' : '' ?>>Grade 9</option>
                                <option value="grade10" <?= $selectedGrade === 'grade10' ? 'selected' : '' ?>>Grade 10</option>
                                <option value="grade11" <?= $selectedGrade === 'grade11' ? 'selected' : '' ?>>Grade 11 (Senior High)</option>
                                <option value="grade12" <?= $selectedGrade === 'grade12' ? 'selected' : '' ?>>Grade 12</option>
                            </select>
                        </div>

                        <div class="col-md-4 col-lg-3">
                            <label for="student_type" class="form-label">Student Type</label>
                            <select name="student_type" id="student_type" class="form-select">
                                <option value="new" <?= $selectedType === 'new' ? 'selected' : '' ?>>New</option>
                                <option value="old" <?= $selectedType === 'old' ? 'selected' : '' ?>>Old</option>
                            </select>
                        </div>

                        <div class="col-md-4 col-lg-3 d-grid">
                            <button type="submit" class="btn btn-filter">
                                <i class="bi bi-search me-2"></i>Apply Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            if (isset($_GET['school_year']) && isset($_GET['student_type']) && isset($_GET['year'])) {
                $school_year = mysqli_real_escape_string($conn, $_GET['school_year']);
                $student_type = mysqli_real_escape_string($conn, $_GET['student_type']);
                $year = $_GET['year'];

                $normalizedYear = strtolower(str_replace([' ', '-', '_'], '', $year));
                $normalizedYearSql = mysqli_real_escape_string($conn, $normalizedYear);

                $query = "SELECT * FROM tuition_fees
                         WHERE school_year='$school_year' 
                         AND student_type='$student_type' 
                         AND REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') = '$normalizedYearSql' 
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

                        $annual_fee   = $total_fee;
                        $cash_total   = ($entrance_fee * 0.9) + $misc_fee + $tuition_fee;

                        $sa_first = $entrance_fee + $misc_fee + ($tuition_fee / 2);
                        $sa_next  = $tuition_fee / 2;

                        $q_first = $entrance_fee + $misc_fee + ($tuition_fee / 4);
                        $q_next  = $tuition_fee / 4;

                        $m_first = $entrance_fee + $misc_fee + round($tuition_fee / 9, 2);
                        $m_next  = round($tuition_fee / 9, 2);

                        $grade_label_raw = $row['grade_level'];
                        $grade_label_pretty = preg_replace('/(?<=\D)(\d+)/', ' $1', $grade_label_raw);
                        $grade_label_pretty = str_replace(['_', '-'], ' ', $grade_label_pretty);
                        $grade_label_pretty = ucwords($grade_label_pretty);
                        $grade_label = htmlspecialchars(trim($grade_label_pretty));

                        $student_label = htmlspecialchars(strtoupper($row['student_type']));
                        $school_year_label = htmlspecialchars($row['school_year']);

                        $entrance_fee_display = number_format($entrance_fee, 2);
                        $entrance_cash_display = number_format($entrance_fee * 0.9, 2);
                        $misc_fee_display = number_format($misc_fee, 2);
                        $tuition_fee_display = number_format($tuition_fee, 2);
                        $tuition_sa_display = number_format($tuition_fee / 2, 2);
                        $tuition_q_display = number_format($tuition_fee / 4, 2);
                        $tuition_m_display = number_format($tuition_fee / 9, 2);

                        $total_fee_display = number_format($total_fee, 2);
                        $annual_fee_display = number_format($annual_fee, 2);
                        $cash_total_display = number_format($cash_total, 2);
                        $sa_first_display = number_format($sa_first, 2);
                        $sa_next_display = number_format($sa_next, 2);
                        $q_first_display = number_format($q_first, 2);
                        $q_next_display = number_format($q_next, 2);
                        $m_first_display = number_format($m_first, 2);
                        $m_next_display = number_format($m_next, 2);

                        echo <<<HTML
                <div class="card fee-result shadow-lg border-0 mb-5">
                    <div class="card-header py-4 px-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                            <div>
                                <span class="badge-plan mb-2"><i class="bi bi-calendar-week me-1"></i>School Year {$school_year_label}</span>
                                <h3 class="h5 fw-bold mb-1">{$grade_label}</h3>
                                <p class="mb-0">Student Type: <strong>{$student_label}</strong></p>
                            </div>
                            <div class="text-lg-end">
                                <span class="display-6 fw-bold text-success">₱{$total_fee_display}</span>
                                <div class="small text-muted">Total Tuition Package</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="row g-3 text-center text-lg-start">
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="plan-summary h-100">
                                    <h4><i class="bi bi-wallet2 me-1 text-success"></i>Annual</h4>
                                    <div class="fs-5 fw-bold">₱{$annual_fee_display}</div>
                                    <div class="sub-text">Single payment upon enrollment</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="plan-summary h-100">
                                    <h4><i class="bi bi-cash-stack me-1 text-success"></i>Cash</h4>
                                    <div class="fs-5 fw-bold">₱{$cash_total_display}</div>
                                    <div class="sub-text">10% entrance fee discount</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="plan-summary h-100">
                                    <h4><i class="bi bi-calendar2-week me-1 text-warning"></i>Semi-Annual</h4>
                                    <div class="fs-5 fw-bold">₱{$sa_first_display}</div>
                                    <div class="sub-text">Next payment ₱{$sa_next_display}</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-3">
                                <div class="plan-summary h-100">
                                    <h4><i class="bi bi-calendar3 me-1 text-danger"></i>Quarterly</h4>
                                    <div class="fs-5 fw-bold">₱{$q_first_display}</div>
                                    <div class="sub-text">Next payments ₱{$q_next_display}</div>
                                </div>
                            </div>
                        </div>

                        <div class="table-fees table-responsive mt-4">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">Fee Type</th>
                                        <th scope="col">Annual (Full)</th>
                                        <th scope="col">Cash (Full)</th>
                                        <th scope="col">Semi-Annual</th>
                                        <th scope="col">Quarterly</th>
                                        <th scope="col">Monthly</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Entrance Fee</td>
                                        <td>₱{$entrance_fee_display}</td>
                                        <td>₱{$entrance_cash_display}</td>
                                        <td>₱{$entrance_fee_display}</td>
                                        <td>₱{$entrance_fee_display}</td>
                                        <td>₱{$entrance_fee_display}</td>
                                    </tr>
                                    <tr>
                                        <td>Miscellaneous Fee</td>
                                        <td>₱{$misc_fee_display}</td>
                                        <td>₱{$misc_fee_display}</td>
                                        <td>₱{$misc_fee_display}</td>
                                        <td>₱{$misc_fee_display}</td>
                                        <td>₱{$misc_fee_display}</td>
                                    </tr>
                                    <tr>
                                        <td>Tuition Fee</td>
                                        <td>₱{$tuition_fee_display}</td>
                                        <td>₱{$tuition_fee_display}</td>
                                        <td>₱{$tuition_sa_display} each</td>
                                        <td>₱{$tuition_q_display} each</td>
                                        <td>₱{$tuition_m_display} each</td>
                                    </tr>
                                    <tr>
                                        <td>Next Payment</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>₱{$sa_next_display}</td>
                                        <td>₱{$q_next_display}</td>
                                        <td>₱{$m_next_display}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Upon Enrollment</strong></td>
                                        <td><strong>₱{$annual_fee_display}</strong></td>
                                        <td><strong>₱{$cash_total_display}</strong></td>
                                        <td><strong>₱{$sa_first_display}</strong></td>
                                        <td><strong>₱{$q_first_display}</strong></td>
                                        <td><strong>₱{$m_first_display}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                HTML;
                    }
                } else {
                    echo "<div class='empty-alert'><i class='bi bi-exclamation-circle'></i> No fee data found for the selected year, grade level, and student type.</div>";
                }
            } else {
                echo "<div class='info-alert'><i class='bi bi-info-circle'></i> Please select a school year, grade level, and student type to view fees.</div>";
            }
            ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
