<?php
include __DIR__ . '/../db_connection.php';
session_start();

$error = '';

// If already logged in, redirect to portal
if (isset($_SESSION['student_number'])) {
    header("Location: student_portal.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_number = trim($_POST['student_number']);
    $password = $_POST['password'];

    // Check if student is enrolled using student_number
    $checkStudent = $conn->prepare("SELECT * FROM students_registration WHERE student_number = ? AND enrollment_status = 'enrolled'");
    if (!$checkStudent) {
        die("Prepare failed (checkStudent): " . $conn->error);
    }
    $checkStudent->bind_param("s", $student_number);
    $checkStudent->execute();
    $studentResult = $checkStudent->get_result();

    if ($studentResult->num_rows > 0) {
        // Check for existing account in student_accounts
        $checkAccount = $conn->prepare("SELECT * FROM student_accounts WHERE student_number = ?");
        if (!$checkAccount) {
            die("Prepare failed (checkAccount): " . $conn->error);
        }
        $checkAccount->bind_param("s", $student_number);
        $checkAccount->execute();
        $accountResult = $checkAccount->get_result();

        if ($accountResult->num_rows == 0) {
            // Create account if missing
            $insertAccount = $conn->prepare("INSERT INTO student_accounts (student_number) VALUES (?)");
            if (!$insertAccount) {
                die("Prepare failed (insertAccount): " . $conn->error);
            }
            $insertAccount->bind_param("s", $student_number);
            $insertAccount->execute();
        }

        // Retrieve account safely
        $getAccount = $conn->prepare("SELECT * FROM student_accounts WHERE student_number = ?");
        if (!$getAccount) {
            die("Prepare failed (getAccount): " . $conn->error);
        }
        $getAccount->bind_param("s", $student_number);
        $getAccount->execute();
        $accountResult = $getAccount->get_result();
        $account = $accountResult->fetch_assoc();

        if ($account['is_first_login']) {
            $_SESSION['student_number'] = $student_number;
            header("Location: set_student_password.php");
            exit();
        } else {
            // Verify password
            if (password_verify($password, $account['password'])) {
                $_SESSION['student_number'] = $student_number;
                header('Location: student_portal.php');
                exit();
            } else {
                $error = "Incorrect password.";
            }
        }

    } else {
        $error = "Student number not found or student not enrolled.";
    }
}

include '../includes/header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Login</title>
    <style>
        .login-container {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            width: 400px; 
            margin: 80px auto;
            background: white; 
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
            border-radius: 10px;
        }
        input[type="text"], input[type="password"] {
            width: 100%; 
            padding: 10px; 
            margin-bottom: 15px;
            border: 1px solid #ccc; 
            border-radius: 5px;
        }
        .btn-submit {
            width: 100%; padding: 10px;
            background: #145A32; color: white; border: none; border-radius: 5px;
        }
        .btn-submit:hover {
            background-color: #0f723aff;
            transform: translateY(-1px);
        }
        .error { color: red; text-align: center; }
    </style>
</head>
<main>
    <div class="login-container">
        <h2 class="text-center mb-4 fw-bold">Student Login</h2>
        <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <input type="text" name="student_number" placeholder="Student Number" required>
            <input type="password" name="password" placeholder="Password" required>
            <p style="text-align: center; margin-top: 10px;">
                <a href="Portal/forgot_password.php" style="color: #007bff; text-decoration: none;">
                    <strong>Forgot Password?</strong>
                </a>
            </p>
            <button type="submit" class="btn-submit w-100">Login</button>
        </form>
    </div>
</main>
</html>
<?php include '../includes/footer.php'; ?>
