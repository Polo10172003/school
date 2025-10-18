<?php
include __DIR__ . '/../db_connection.php';

/**
 * Move a student record into the archived_students table and remove related portal access.
 *
 * @throws RuntimeException|Throwable when archiving fails
 */
function registrar_auto_archive_student(mysqli $conn, int $id): void
{
    if (!$conn->begin_transaction()) {
        throw new RuntimeException('Failed to start archive transaction.');
    }

    try {
        $fetch = $conn->prepare('SELECT * FROM students_registration WHERE id = ? LIMIT 1');
        if (!$fetch) {
            throw new RuntimeException('Failed to prepare student lookup.');
        }
        $fetch->bind_param('i', $id);
        $fetch->execute();
        $result = $fetch->get_result();
        $student = $result ? $result->fetch_assoc() : null;
        $fetch->close();

        if (!$student) {
            throw new RuntimeException('Student record not found for archiving.');
        }

        if (array_key_exists('portal_status', $student)) {
            $student['portal_status'] = 'pending';
        }
        if (array_key_exists('academic_status', $student)) {
            $student['academic_status'] = 'archived';
        }

        $columns = array_keys($student);
        if (count($columns) === 0) {
            throw new RuntimeException('No columns available for archiving.');
        }

        $columnList = implode(', ', array_map(
            static function (string $column): string {
                return '`' . str_replace('`', '``', $column) . '`';
            },
            $columns
        ));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $values = array_values($student);
        $types = str_repeat('s', count($values));

        $insert = $conn->prepare("INSERT INTO archive_students ($columnList) VALUES ($placeholders)");
        if (!$insert) {
            throw new RuntimeException('Failed to prepare archive insert.');
        }
        $params = array_merge([$types], $values);
        $refs = [];
        foreach ($params as $index => $value) {
            $refs[$index] = &$params[$index];
        }
        call_user_func_array([$insert, 'bind_param'], $refs);
        $insert->execute();
        $insert->close();

        $emails = [];
        if (!empty($student['emailaddress'])) {
            $emails[] = (string) $student['emailaddress'];
        }
        if (!empty($student['email'])) {
            $emails[] = (string) $student['email'];
        }
        $emails = array_unique(array_filter($emails));

        foreach ($emails as $email) {
            $deleteAccount = $conn->prepare('DELETE FROM student_accounts WHERE email = ?');
            if ($deleteAccount) {
                $deleteAccount->bind_param('s', $email);
                $deleteAccount->execute();
                $deleteAccount->close();
            }
        }

        if (!empty($student['student_number'])) {
            $studentNumber = (string) $student['student_number'];
            $deleteByNumber = $conn->prepare('DELETE FROM student_accounts WHERE student_number = ?');
            if ($deleteByNumber) {
                $deleteByNumber->bind_param('s', $studentNumber);
                $deleteByNumber->execute();
                $deleteByNumber->close();
            }
        }

        $delete = $conn->prepare('DELETE FROM students_registration WHERE id = ?');
        if (!$delete) {
            throw new RuntimeException('Failed to prepare student removal.');
        }
        $delete->bind_param('i', $id);
        $delete->execute();
        $delete->close();

        $conn->commit();
    } catch (Throwable $archiveError) {
        $conn->rollback();
        throw $archiveError;
    }
}

// Promotion map
function nextYear($year) {
    $map = [
        "Preschool" => "Pre-Prime 1",
        "Pre-Prime 1" => "Pre-Prime 2",
        "Pre-Prime 2" => "Kindergarten",
        "Pre-Prime 1 & 2" => "Kindergarten",
        "Kindergarten"  => "Grade 1",
        "Kinder 1"  => "Kindergarten",
        "Kinder 2"  => "Grade 1",
        "Grade 1"   => "Grade 2",
        "Grade 2"   => "Grade 3",
        "Grade 3"   => "Grade 4",
        "Grade 4"   => "Grade 5",
        "Grade 5"   => "Grade 6",
        "Grade 6"   => "Grade 7",
        "Grade 7"   => "Grade 8",
        "Grade 8"   => "Grade 9",
        "Grade 9"   => "Grade 10",
        "Grade 10"  => "Grade 11",
        "Grade 11"  => "Grade 12",
        "Grade 12"  => "Graduated"
    ];
    return $map[$year] ?? $year;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Fetch current student info
    $stmt = $conn->prepare("SELECT `year`, `academic_status`, `student_type`, `school_year` FROM students_registration WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) die("Student not found!");

    $current_year = $student['year'];
    $current_status = $student['academic_status'];
    $current_type = $student['student_type'];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id     = intval($_POST['id']);
    $status = $_POST['status'];

    // Fetch current year again (safety)
$stmt = $conn->prepare("SELECT `year`, `student_type`, `school_year`, `firstname`, `lastname` FROM students_registration WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $current_year = $row['year'];
    $current_type = $row['student_type'];
    $current_school_year = $row['school_year'] ?? '';
    $current_firstname = $row['firstname'] ?? '';
    $current_lastname = $row['lastname'] ?? '';

    // --- PROMOTION / FAIL LOGIC ---
    $enrollment_status = null;
    $moveToInactive = false;

    $next_year = $current_year;
    $academic_status = $row['academic_status'] ?? 'Ongoing';
    $isGrade12Graduate = false;

    if ($status === "Passed") {
        if ($current_year === "Grade 12") {
            $next_year = "Graduated";
            $academic_status = "Graduated";
            $enrollment_status = '';
            $isGrade12Graduate = true;
        } else {
            $next_year = nextYear($current_year);
            $academic_status = "Ongoing"; // always reset to Ongoing after promotion
            $enrollment_status = 'ready';
        }
    } elseif ($status === "Failed") {
        $next_year = $current_year;     // stay same grade
        $academic_status = "Failed";    // mark Failed
        $enrollment_status = 'waiting';
        $new_student_type = 'old';
    } elseif ($status === "Dropped") {
        $next_year = $current_year;
        $academic_status = "Dropped";
        $enrollment_status = 'dropped';
        $moveToInactive = true;
    }

    $new_student_type = $current_type;
    if ($status === 'Failed') {
        $new_student_type = 'old';
    }
    $resetSchedule = false;
    if ($status === 'Passed' && $next_year !== $current_year) {
        $new_student_type = 'old';
        $resetSchedule = true;
    }

    if ($next_year !== $current_year) {
        $resetSchedule = true;
    }

    $setClauses = [
        'year = ?',
        'academic_status = ?',
        'student_type = ?',
    ];
    $bindValues = [$next_year, $academic_status, $new_student_type];
    $bindTypes = 'sss';

    if ($enrollment_status !== null) {
        $setClauses[] = 'enrollment_status = ?';
        $bindValues[] = $enrollment_status;
        $bindTypes .= 's';
    }

    if ($resetSchedule) {
        $toBeAssigned = 'To be assigned';
        $setClauses[] = 'schedule_sent_at = NULL';
        $setClauses[] = 'section = ?';
        $bindValues[] = $toBeAssigned;
        $bindTypes .= 's';
        $setClauses[] = 'adviser = ?';
        $bindValues[] = $toBeAssigned;
        $bindTypes .= 's';
    }

    if ($isGrade12Graduate) {
        $setClauses[] = 'portal_status = ?';
        $bindValues[] = 'pending';
        $bindTypes .= 's';
    }

    $setSql = implode(', ', $setClauses);
    $updateSql = "UPDATE students_registration SET {$setSql} WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }

    $bindValues[] = $id;
    $bindTypes .= 'i';

    $params = array_merge([$bindTypes], $bindValues);
    $refs = [];
    foreach ($params as $index => $value) {
        $refs[$index] = &$params[$index];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);

    if ($stmt->execute()) {
        $stmt->close();
        $shouldClearPlanSelections = $moveToInactive || ($status === 'Passed' && $resetSchedule);

        if ($shouldClearPlanSelections) {
            $toBeAssignedLabel = 'To be assigned';
            // Reset placement details
            $clearPlacement = $conn->prepare('UPDATE students_registration SET schedule_sent_at = NULL, section = ?, adviser = ? WHERE id = ?');
            if ($clearPlacement) {
                $clearPlacement->bind_param('ssi', $toBeAssignedLabel, $toBeAssignedLabel, $id);
                $clearPlacement->execute();
                $clearPlacement->close();
            }

            // Remove any saved payment plan selections so the cashier can choose again
            $hasPlanTable = $conn->query("SHOW TABLES LIKE 'student_plan_selections'");
            if ($hasPlanTable && $hasPlanTable->num_rows > 0) {
                $deletePlan = $conn->prepare('DELETE FROM student_plan_selections WHERE student_id = ?');
                if ($deletePlan) {
                    $deletePlan->bind_param('i', $id);
                    $deletePlan->execute();
                    $deletePlan->close();
                }
            }
            if ($hasPlanTable instanceof mysqli_result) {
                $hasPlanTable->close();
            }
        }

        if ($isGrade12Graduate) {
            try {
                registrar_auto_archive_student($conn, $id);
                echo "<script>
                        alert('Student marked as graduated and archived automatically.');
                        window.location.href='registrar_dashboard.php?msg=archived';
                      </script>";
            } catch (Throwable $archiveFailure) {
                error_log('[registrar] auto archive failed: ' . $archiveFailure->getMessage());
                echo "<script>
                        alert('Student status updated, but automatic archiving failed. Please archive manually.');
                        window.location.href='registrar_dashboard.php';
                      </script>";
            }
            exit();
        }


        if ($moveToInactive) {
            $conn->begin_transaction();
            try {
                $copyStmt = $conn->prepare('INSERT INTO inactive_students SELECT * FROM students_registration WHERE id = ?');
                if (!$copyStmt) {
                    throw new Exception('Unable to copy student to inactive list.');
                }
                $copyStmt->bind_param('i', $id);
                if (!$copyStmt->execute()) {
                    throw new Exception('Unable to copy student to inactive list.');
                }
                $copyStmt->close();

                $deleteStmt = $conn->prepare('DELETE FROM students_registration WHERE id = ?');
                if (!$deleteStmt) {
                    throw new Exception('Unable to remove student from active list.');
                }
                $deleteStmt->bind_param('i', $id);
                if (!$deleteStmt->execute()) {
                    throw new Exception('Unable to remove student from active list.');
                }
                $deleteStmt->close();

                $conn->commit();
                echo "<script>
                        alert('Student marked as dropped and moved to inactive records.');
                        window.location.href='registrar_dashboard.php';
                      </script>";
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                echo "<script>
                        alert('" . addslashes($e->getMessage()) . "');
                        window.location.href='registrar_dashboard.php';
                      </script>";
                exit();
            }
        }

        echo "<script>
                alert('Student status updated successfully!');
                window.location.href='registrar_dashboard.php';
              </script>";
        exit();
    } else {
        die("Error: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Student Status</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body.update-status-body {
            min-height: 100vh;
            margin: 0;
            background: #f5f7fb;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
            color: #1f2937;
        }

        .update-status-card {
            width: 100%;
            max-width: 520px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 16px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.1);
            overflow: hidden;
            background: #ffffff;
        }

        .update-status-card .card-header {
            background: linear-gradient(135deg, #114b72, #0f3057);
            padding: 22px 26px;
        }

        .update-status-card .card-header h4 {
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .update-status-card .card-body {
            padding: 26px;
        }

        .update-status-card .form-label {
            font-weight: 600;
            color: #223046;
        }

        .update-status-card .form-select {
            padding: 12px;
            border-radius: 10px;
            border: 1px solid rgba(51, 65, 85, 0.22);
            box-shadow: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background-color: #f8fafc;
        }

        .update-status-card .form-select:focus {
            border-color: #114b72;
            box-shadow: 0 0 0 3px rgba(17, 75, 114, 0.18);
            background-color: #ffffff;
        }

        .update-status-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 26px;
        }

        .update-status-actions .btn {
            flex: 1 1 auto;
            min-width: 140px;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .update-status-actions .btn-primary {
            background: linear-gradient(135deg, #114b72, #0f3057);
            border: none;
        }

        .update-status-actions .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(17, 75, 114, 0.28);
        }

        .update-status-actions .btn-outline-secondary {
            border-color: rgba(37, 50, 87, 0.35);
            color: #253257;
        }

        .update-status-actions .btn-outline-secondary:hover {
            background-color: rgba(17, 75, 114, 0.08);
            border-color: rgba(17, 75, 114, 0.45);
            color: #114b72;
        }

        .update-status-card .card-footer {
            background: rgba(15, 48, 87, 0.05);
        }

        @media (max-width: 575.98px) {
            .update-status-card .card-body {
                padding: 22px 18px;
            }

            .update-status-actions .btn {
                min-width: 100%;
            }
        }
    </style>
</head>
<body class="update-status-body">
<div class="update-status-card card">
    <div class="card-header text-white">
        <h4 class="mb-0 text-uppercase">Update Student Status</h4>
    </div>
    <div class="card-body">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="mb-3">
                    <label for="status" class="form-label">Select Status</label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="Passed" <?= ($current_status === 'Ongoing' || $current_status === 'Passed') ? 'selected' : '' ?>>Passed</option>
                        <option value="Failed" <?= ($current_status === 'Failed') ? 'selected' : '' ?>>Failed</option>
                        <option value="Dropped" <?= ($current_status === 'Dropped') ? 'selected' : '' ?>>Dropped</option>
                    </select>
                </div>

                <div class="update-status-actions">
                    <button type="submit" class="btn btn-success">Update Status</button>
                    <a href="registrar_dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <div class="card-footer bg-transparent text-center py-3">
            <small class="text-muted">Review carefully before confirming the student's new status.</small>
        </div>
    </div>
</body>
</html>
