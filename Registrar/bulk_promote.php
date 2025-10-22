<?php
include __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../includes/transaction_logger.php';

// Promotion map
function nextYear($year) {
    $map = [
        "Pre-Prime 1"  => "Pre-Prime 2",
        "Pre-Prime 2"  => "Kindergarten",
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

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['student_ids'])) {
    $promotedStudents = [];
    $skippedStudents = [];
    foreach ($_POST['student_ids'] as $id) {
        $id = intval($id);

        // Fetch current year/status
        $stmt = $conn->prepare("SELECT year, academic_status, student_type FROM students_registration WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$student) {
            $skippedStudents[] = [
                'id'     => $id,
                'reason' => 'not_found',
            ];
            continue;
        }

        $current_year = $student['year'];
        $status       = $student['academic_status'];
        $current_type = $student['student_type'] ?? 'new';

        // Logic: if already Failed, skip bulk promotion
        if ($status === "Failed") {
            $skippedStudents[] = [
                'id'     => $id,
                'reason' => 'failed_status',
            ];
            continue;
        }

        if ($current_year === "Grade 12") {
            $next_year = "Graduated";
            $academic_status = "Graduated";
        } else {
            $next_year = nextYear($current_year);
            $academic_status = "Ongoing"; // reset after promotion
        }

        $new_student_type = 'old';
        $resetSchedule = ($next_year !== $current_year);
        $new_enrollment_status = null;
        if ($next_year === $current_year) {
            // No promotion happened (e.g., failed); retain existing type
            $new_student_type = $current_type;
        }

        if ($resetSchedule) {
            if (strcasecmp($next_year, 'Graduated') === 0) {
                $new_enrollment_status = 'graduated';
            } else {
                $new_enrollment_status = 'ready';
            }
        }

        if ($resetSchedule) {
            $toBeAssigned = 'To be assigned';
            if ($new_enrollment_status !== null) {
                $stmt = $conn->prepare("UPDATE students_registration SET year = ?, academic_status = ?, student_type = ?, enrollment_status = ?, schedule_sent_at = NULL, section = ?, adviser = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $next_year, $academic_status, $new_student_type, $new_enrollment_status, $toBeAssigned, $toBeAssigned, $id);
            } else {
                $stmt = $conn->prepare("UPDATE students_registration SET year = ?, academic_status = ?, student_type = ?, schedule_sent_at = NULL, section = ?, adviser = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $next_year, $academic_status, $new_student_type, $toBeAssigned, $toBeAssigned, $id);
            }
        } else {
            if ($new_enrollment_status !== null) {
                $stmt = $conn->prepare("UPDATE students_registration SET year = ?, academic_status = ?, student_type = ?, enrollment_status = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $next_year, $academic_status, $new_student_type, $new_enrollment_status, $id);
            } else {
                $stmt = $conn->prepare("UPDATE students_registration SET year = ?, academic_status = ?, student_type = ? WHERE id = ?");
                $stmt->bind_param("sssi", $next_year, $academic_status, $new_student_type, $id);
            }
        }
        $stmt->execute();
        $stmt->close();

        $promotedStudents[] = [
            'id'                    => $id,
            'from_year'             => $current_year,
            'to_year'               => $next_year,
            'new_academic_status'   => $academic_status,
            'new_enrollment_status' => $new_enrollment_status,
            'reset_schedule'        => $resetSchedule,
        ];
    }

    transaction_log_record($conn, [
        'category'    => 'student_status',
        'action'      => 'bulk_promotion',
        'target_type' => 'student_batch',
        'description' => sprintf('Bulk promoted %d students.', count($promotedStudents)),
        'metadata'    => [
            'requested_ids' => array_map('intval', $_POST['student_ids']),
            'promoted'      => $promotedStudents,
            'skipped'       => $skippedStudents,
        ],
        'context'     => 'registrar',
    ]);

    echo "<script>
            alert('Selected students promoted successfully!');
            window.location.href='registrar_dashboard.php';
          </script>";
    exit;
} else {
    header("Location: registrar_dashboard.php");
    exit;
}
?>
