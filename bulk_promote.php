<?php
include 'db_connection.php';

// Promotion map
function nextYear($year) {
    $map = [
        "Preschool" => "Kinder 1",
        "Kinder 1"  => "Kinder 2",
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
        $stmt = $conn->prepare("SELECT year, academic_status FROM students_registration WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$student) continue;

        $current_year = $student['year'];
        $status       = $student['academic_status'];

        // Logic: if already Failed, skip bulk promotion
        if ($status === "Failed") continue;

        if ($current_year === "Grade 12") {
            $next_year = "Graduated";
            $academic_status = "Graduated";
        } else {
            $next_year = nextYear($current_year);
            $academic_status = "Ongoing"; // reset after promotion
        }

        $stmt = $conn->prepare("UPDATE students_registration SET year = ?, academic_status = ? WHERE id = ?");
        $stmt->bind_param("ssi", $next_year, $academic_status, $id);
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
