<?php
include __DIR__ . '/../db_connection.php';

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
    foreach ($_POST['student_ids'] as $id) {
        $id = intval($id);

        // Fetch current year/status
        $stmt = $conn->prepare("SELECT year, academic_status, student_type FROM students_registration WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$student) continue;

        $current_year = $student['year'];
        $status       = $student['academic_status'];
        $current_type = $student['student_type'] ?? 'new';

        // Logic: if already Failed, skip bulk promotion
        if ($status === "Failed") continue;

        if ($current_year === "Grade 12") {
            $next_year = "Graduated";
            $academic_status = "Graduated";
        } else {
            $next_year = nextYear($current_year);
            $academic_status = "Ongoing"; // reset after promotion
        }

        $new_student_type = 'old';
        $resetSchedule = ($next_year !== $current_year);
        if ($next_year === $current_year) {
            // No promotion happened (e.g., failed); retain existing type
            $new_student_type = $current_type;
        }

        if ($resetSchedule) {
            $toBeAssigned = 'To be assigned';
            $stmt = $conn->prepare("UPDATE students_registration SET year = ?, academic_status = ?, student_type = ?, schedule_sent_at = NULL, section = ?, adviser = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $next_year, $academic_status, $new_student_type, $toBeAssigned, $toBeAssigned, $id);
        } else {
            $stmt = $conn->prepare("UPDATE students_registration SET year = ?, academic_status = ?, student_type = ? WHERE id = ?");
            $stmt->bind_param("sssi", $next_year, $academic_status, $new_student_type, $id);
        }
        $stmt->execute();
        $stmt->close();
    }

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
