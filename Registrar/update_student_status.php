<?php
include __DIR__ . '/../db_connection.php';

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

    if ($status === "Passed") {
        if ($current_year === "Grade 12") {
            $next_year = "Graduated";
            $academic_status = "Graduated";
        } else {
            $next_year = nextYear($current_year);
            $academic_status = "Ongoing"; // always reset to Ongoing after promotion
        }
        $enrollment_status = 'ready';
    } elseif ($status === "Failed") {
        $next_year = $current_year;     // stay same grade
        $academic_status = "Failed";    // mark Failed
        $enrollment_status = 'waiting';
    } elseif ($status === "Dropped") {
        $next_year = $current_year;
        $academic_status = "Dropped";
        $enrollment_status = 'dropped';
        $moveToInactive = true;
    }

    $new_student_type = $current_type;
    $resetSchedule = false;
    if ($status === 'Passed' && $next_year !== $current_year) {
        $new_student_type = 'old';
        $resetSchedule = true;
    }

    if ($next_year !== $current_year) {
        $resetSchedule = true;
    }

    if ($resetSchedule) {
        $toBeAssigned = 'To be assigned';
        if ($enrollment_status === null) {
            $stmt = $conn->prepare("UPDATE students_registration SET year = ?, academic_status = ?, student_type = ?, schedule_sent_at = NULL, section = ?, adviser = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $next_year, $academic_status, $new_student_type, $toBeAssigned, $toBeAssigned, $id);
        } else {
            $stmt = $conn->prepare("UPDATE students_registration SET year = ?, academic_status = ?, student_type = ?, enrollment_status = ?, schedule_sent_at = NULL, section = ?, adviser = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $next_year, $academic_status, $new_student_type, $enrollment_status, $toBeAssigned, $toBeAssigned, $id);
        }
    } else {
        if ($enrollment_status === null) {
            $stmt = $conn->prepare("UPDATE students_registration SET year = ?, academic_status = ?, student_type = ? WHERE id = ?");
            $stmt->bind_param("sssi", $next_year, $academic_status, $new_student_type, $id);
        } else {
            $stmt = $conn->prepare("UPDATE students_registration SET year = ?, academic_status = ?, student_type = ?, enrollment_status = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $next_year, $academic_status, $new_student_type, $enrollment_status, $id);
        }
    }
    if ($stmt->execute()) {
        $shouldClearPlanSelections = ($status === 'Failed') || (($status !== 'Dropped') && $moveToInactive) || ($status === 'Passed' && $resetSchedule);

        if ($shouldClearPlanSelections) {
            // Reset placement details
            $clearPlacement = $conn->prepare('UPDATE students_registration SET schedule_sent_at = NULL, section = NULL, adviser = NULL WHERE id = ?');
            if ($clearPlacement) {
                $clearPlacement->bind_param('i', $id);
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
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Update Student Status</h4>
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

                <button type="submit" class="btn btn-success">Update</button>
                <a href="registrar_dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
