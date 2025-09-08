<?php
require_once 'template_helper.php';
include 'db_connection.php';

$page_title = 'Escuela de Sto. Rosario - LRN Check';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $lrn = trim($_POST['lrn']);

    // Check if LRN exists in DB
    $sql = "SELECT id, year, result, student_type FROM students_registration WHERE lrn = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $lrn);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // ✅ Old student
        $student = $result->fetch_assoc();
        $year = $student['year'];        
        $result_status = $student['result'] ?? "passed"; // default passed if null
        $studentType = strtolower($student['student_type']);

        // Promotion logic
        if ($result_status === "passed") {
            $newYear = promoteYear($year); 
        } else {
            $newYear = $year;
        }

        // Update year + mark as old student
        $update = "UPDATE students_registration SET year = ?, student_type = 'old' WHERE lrn = ?";
        $stmt2 = $conn->prepare($update);
        $stmt2->bind_param("ss", $newYear, $lrn);
        $stmt2->execute();

        $message = "<div class='alert alert-success'>
                        ✅ LRN found. You are now in <b>$newYear</b>. 
                        <br><br>
                        Please proceed to the student portal
                        for payment/update of accounts or go onsite.
                    </div>";
    } else {
        // ❌ New student → redirect to registration form
        header("Location: early_registration.php?new=1&lrn=" . urlencode($lrn));
        exit;

    }
}

// Function to promote year
function promoteYear($year, $status) {
    $grades = [
        "Kinder 1","Kinder 2",
        "Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6",
        "Grade 7","Grade 8","Grade 9","Grade 10",
        "Grade 11","Grade 12"
    ];

    // If Grade 12 and passed → Graduated
    if ($year === "Grade 12" && $status === "Passed") {
        return "Graduated";
    }

    // If Failed → stay in same grade
    if ($status === "Failed") {
        return $year;
    }

    // Otherwise, promote to next grade
    $index = array_search($year, $grades);
    if ($index !== false && $index < count($grades)-1) {
        return $grades[$index+1];
    }

    return $year;
}

// Render page inside template
renderPage($page_title, function() use ($message) {
    ob_start(); ?>
    
    <div class="container mt-5">
        <h2 class="text-center mb-4 fw-bold">LRN Verification</h2>
        <form method="POST" class="p-4 bg-light rounded shadow-sm mx-auto" style="max-width: 500px;">
            <div class="mb-3">
                <label for="lrn" class="form-label fw-bold">Enter Your LRN</label>
                <input type="text" id="lrn" name="lrn" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Check</button>
        </form>

        <?php if ($message): ?>
            <div class="mt-4"><?= $message ?></div>
        <?php endif; ?>
    </div>

    <?php
    return ob_get_clean();
});
