<?php
session_start();
$student_number = $_GET['sn'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registration Successful</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f6fa;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .card {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      text-align: center;
      max-width: 500px;
      width: 100%;
    }
    h2 {
      color: #27ae60;
    }
    .loader {
      border: 6px solid #f3f3f3;
      border-top: 6px solid #3498db;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
      margin: 20px auto;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .small {
      font-size: 14px;
      color: #555;
      margin-top: 10px;
    }
    .hidden {
      display: none;
    }
  </style>
</head>
<body>
  <div class="card">
    <h2>✅ Registration Successful!</h2>
    <p>Your Student Number is:</p>
    <h3><strong><?= htmlspecialchars($student_number) ?></strong></h3>

    <!-- Step 1: Loader -->
    <div id="loading-section">
      <div class="loader"></div>
      <p class="small">We’re sending a confirmation email to your inbox...</p>
    </div>

    <!-- Step 2: Email Sent Message -->
    <div id="done-section" class="hidden">
      <h3 style="color: #27ae60;">📧 Email Sent!</h3>
      <p class="small">You may now check your inbox for confirmation details.</p>
    </div>
  </div>

  <script>
    // After 5 seconds, switch from loading → done
    setTimeout(() => {
      document.getElementById("loading-section").classList.add("hidden");
      document.getElementById("done-section").classList.remove("hidden");
    }, 5000);
  </script>
</body>
</html>

<?php
// 🔹 Trigger background worker for email
if (!empty($_SESSION['email_job'])) {
    $payload = http_build_query($_SESSION['email_job']);
    exec("php " . __DIR__ . "/email_worker.php '$payload' > /dev/null 2>&1 &");
    unset($_SESSION['email_job']);
}
?>
