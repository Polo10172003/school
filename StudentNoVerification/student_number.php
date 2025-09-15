<?php

include '../db_connection.php';

$page_title = 'Escuela de Sto. Rosario - Student Number Check';
$message = "";

/**
 * Normalize grade level.
 */
function normalizeYear($year) {
    $map = [
        "preschool" => "Preschool",
        "k1" => "Kinder 1", "kinder 1" => "Kinder 1",
        "k2" => "Kinder 2", "kinder 2" => "Kinder 2",
    ];
    $y = strtolower(trim((string)$year));
    if (isset($map[$y])) return $map[$y];
    if (preg_match('/^grade\s*([1-9]|1[0-2])$/i', $year, $m)) {
        return "Grade " . $m[1];
    }
    return ucwords($year);
}

/**
 * Compute display grade based on current grade + academic status.
 */
function displayYear($year, $status) {
    $grades = [
        "Preschool","Kinder 1","Kinder 2",
        "Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6",
        "Grade 7","Grade 8","Grade 9","Grade 10",
        "Grade 11","Grade 12"
    ];

    $yearCanon  = normalizeYear($year);
    $statusNorm = strtolower(trim((string)$status));

    if ($statusNorm === "ongoing" || $statusNorm === "pending") {
        return $yearCanon;
    }
    if ($statusNorm === "failed") {
        return $yearCanon;
    }
    if ($statusNorm === "passed") {
        if ($yearCanon === "Grade 12") {
            return "Graduated";
        }
        $idx = array_search($yearCanon, $grades, true);
        if ($idx !== false && $idx < count($grades) - 1) {
            return $grades[$idx + 1];
        }
    }
    if ($statusNorm === "graduated") {
        return "Graduated";
    }

    return $yearCanon; // fallback
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_number = trim($_POST['student_number'] ?? '');
    if ($student_number === '') {
        $message = "<div class='alert alert-danger'>Student Number is required.</div>";
    } else {
        $sql = "SELECT id, year, academic_status 
                  FROM students_registration 
                 WHERE student_number = ? 
              ORDER BY id DESC 
                 LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $student_number);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $student = $result->fetch_assoc();
                $stmt->close();

                $year   = $student['year'];
                $status = $student['academic_status'];

                $display = displayYear($year, $status);

                $message = "<div class='alert alert-success'>
                                ✅ Student Number found. You are now in <b>" . htmlspecialchars($display) . "</b>.
                                <br><br>
                                Please proceed to the student portal for payment/update of accounts or go onsite.
                            </div>";
            } else {
                // Student number not found → Suggest early registration
                $message = "<div class='alert alert-warning'>
                                ❌ Student Number not found.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>DB error: " . htmlspecialchars($conn->error) . "</div>";
        }
    }
}
    include '../includes/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Login</title>
    <style>
        .btn {
            background:  #145A32;
            color: white;
            padding: 6px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            display: block;
            margin-top: 20px;
            border: none;
        }

        .btn:hover {
            background-color: #0f723aff;
            color: #ffffff !important;
            transform: translateY(-1px);
        }

        input[type="text"] {
            width: 100%;
            padding: 6px;
            margin-bottom: 15px;
            border: 1px solid #ccc; 
        }
    </style>
<main>
    <div class="container mt-5" style="padding-top:90px;">
        <h2 class="text-center mb-4 fw-bold">Student Number Verification</h2>
        <form method="POST" class="p-4 bg-light rounded shadow-sm mx-auto" style="max-width: 500px;">
            <div class="mb-3">
                <label for="student_number" class="form-label fw-bold">Enter Your Student Number</label>
                <input type="text" id="student_number" name="student_number" class="form-control" required>
            </div>
            <button type="submit" class="btn w-100">Check</button>
        </form>

        <?php if ($message): ?>
            <div class="mt-4"><?= $message ?></div>
        <?php endif; ?>

        <p class="mt-3 text-center">
            No student number yet? 
            <a href="StudentNoVerification/early_registration.php">Click here to register</a>
        </p>
    </div>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
