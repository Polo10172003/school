<?php
session_start();

require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include __DIR__ . '/../db_connection.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$shouldClearSearch = false;
$flashMessage = $_SESSION['cashier_flash'] ?? '';
$flashType = $_SESSION['cashier_flash_type'] ?? '';
$clearSearchFlag = isset($_GET['search_cleared']);
if ($flashMessage !== '') {
    unset($_SESSION['cashier_flash']);
}
if ($flashType !== '') {
    unset($_SESSION['cashier_flash_type']);
}
if ($flashType === 'success') {
    $clearSearchFlag = true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["student_id"])) {
  $student_id = intval($_POST["student_id"]);
  $amount = floatval($_POST["amount"] ?? 0);
  $payment_type = $_POST["payment_type"] ?? "Cash";
  $payment_status = $_POST["payment_status"] ?? "paid";
  $payment_date = date("Y-m-d");

  if (isset($_POST["or_number"])) {
      $or_number = $_POST["or_number"];

      // 🔹 Fetch student details
      $stud = $conn->prepare("SELECT firstname, lastname, student_number, emailaddress FROM students_registration WHERE id = ?");
      $stud->bind_param("i", $student_id);
      $stud->execute();
      $stud->bind_result($firstname, $lastname, $student_number, $email);
      $stud->fetch();
      $stud->close();

      // 🔹 Generate student number if this is their first payment
      $new_number = null;
      if (empty($student_number)) {
          do {
              $new_number = "ESR-"  . str_pad(rand(1, 99999), 5, "0", STR_PAD_LEFT);
              $checkNum = $conn->prepare("SELECT id FROM students_registration WHERE student_number = ? LIMIT 1");
              $checkNum->bind_param("s", $new_number);
              $checkNum->execute();
              $checkNum->store_result();
              $exists = $checkNum->num_rows > 0;
              $checkNum->close();
          } while ($exists);

          // Save it
          $updNum = $conn->prepare("UPDATE students_registration SET student_number = ? WHERE id = ?");
          $updNum->bind_param("si", $new_number, $student_id);
          $updNum->execute();
          $updNum->close();

          $student_number = $new_number;
      }

      // 🔹 Check if there's already a pending onsite payment for this student & amount
      $check = $conn->prepare("
      SELECT id 
      FROM student_payments 
      WHERE student_id = ? AND payment_status = 'pending'
      ORDER BY created_at DESC LIMIT 1
      ");
      $check->bind_param("i", $student_id);
      $check->execute();
      $check->bind_result($pending_payment_id);
      $check->fetch();
      $check->close();

      if ($pending_payment_id) {
      // ✅ Update the existing pending payment to paid
      $updPay = $conn->prepare("
          UPDATE student_payments 
          SET payment_status = 'paid', or_number = ?, payment_date = ?
          WHERE id = ?
      ");
      $updPay->bind_param("ssi", $or_number, $payment_date, $pending_payment_id);
      $updPay->execute();
      $updPay->close();

      // Enroll student
      $upd = $conn->prepare("UPDATE students_registration SET enrollment_status = 'enrolled' WHERE id = ?");
      $upd->bind_param("i", $student_id);
      $upd->execute();
      $upd->close();

      $message = "Pending onsite payment updated to Paid. Student enrolled.";
      $shouldClearSearch = true;
      } else {
      // ❌ No pending → insert new payment (normal flow)
      $ins = $conn->prepare("
          INSERT INTO student_payments
              (student_id, firstname, lastname, payment_type, amount, payment_status, payment_date, or_number, created_at)
          VALUES (?,?,?, 'Cash', ?, 'paid', ?, ?, NOW())
      ");
      $ins->bind_param("issdss", $student_id, $firstname, $lastname, $amount, $payment_date, $or_number);

      if ($ins->execute()) {
          // Enroll student
          $upd = $conn->prepare("UPDATE students_registration SET enrollment_status = 'enrolled' WHERE id = ?");
          $upd->bind_param("i", $student_id);
          $upd->execute();
          $upd->close();

          // 🔹 Email via background worker
          $php_path = "/Applications/XAMPP/bin/php";
          $worker   = __DIR__ . "/email_worker.php";

          $cmd = "$php_path $worker $student_id $payment_type $amount paid";
          if ($new_number) {
              $cmd .= " $new_number";
          }
          exec("$cmd > /dev/null 2>&1 &");

          $message = "Cash payment recorded successfully. Student enrolled.";
          $shouldClearSearch = true;
      } else {
          $message = "Error: " . $conn->error;
      }
      $ins->close();
      }
    } else {
        $message = "Official receipt number is required.";
    }
    if ($message !== '') {
        $_SESSION['cashier_flash'] = $message;
        $_SESSION['cashier_flash_type'] = $shouldClearSearch ? 'success' : 'error';
    }
    $redirectUrl = 'cashier_dashboard.php';
    if ($shouldClearSearch) {
        $redirectUrl .= '?search_cleared=1';
    }
    header("Location: $redirectUrl", true, 303);
    exit();
  }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tuition_fee_form'])) {
    $school_year = trim($_POST['school_year'] ?? '');
    $student_type = trim($_POST['student_type'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $entrance_fee = floatval($_POST['entrance_fee'] ?? 0);
    $miscellaneous_fee = floatval($_POST['miscellaneous_fee'] ?? 0);
    $tuition_fee = floatval($_POST['tuition_fee'] ?? 0);
    $total_upon_enrollment = floatval($_POST['total_upon_enrollment'] ?? 0);

    if ($school_year !== '' && $student_type !== '' && $year !== '') {
        $stmt = $conn->prepare("INSERT INTO tuition_fees (school_year, student_type, grade_level, entrance_fee, miscellaneous_fee, tuition_fee, total_upon_enrollment)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param(
                "sssdddd",
                $school_year,
                $student_type,
                $year,
                $entrance_fee,
                $miscellaneous_fee,
                $tuition_fee,
                $total_upon_enrollment
            );

            if ($stmt->execute()) {
                $_SESSION['cashier_flash'] = 'Tuition fee saved successfully.';
                $_SESSION['cashier_flash_type'] = 'success';
            } else {
                $_SESSION['cashier_flash'] = 'Failed to save tuition fee. Please try again.';
                $_SESSION['cashier_flash_type'] = 'error';
            }
            $stmt->close();
        } else {
            $_SESSION['cashier_flash'] = 'Failed to prepare tuition statement.';
            $_SESSION['cashier_flash_type'] = 'error';
        }
    } else {
        $_SESSION['cashier_flash'] = 'Please complete the required tuition fields.';
        $_SESSION['cashier_flash_type'] = 'error';
    }

    header('Location: cashier_dashboard.php#fees', true, 303);
    exit();
}

// 📌 Search functionality
$search_results = [];
$searchQuery = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
if ($clearSearchFlag) {
    $searchQuery = '';
}

if ($searchQuery !== '') {
    $search_name_like = "%" . $conn->real_escape_string($searchQuery) . "%";
    $stmt = $conn->prepare("SELECT id, student_number, firstname, lastname, year FROM students_registration WHERE lastname LIKE ? ORDER BY lastname");
    $stmt->bind_param("s", $search_name_like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $search_results[] = $row;
    }
    $stmt->close();
}

// 📌 Filters
$filter_sql = "WHERE 1=1";
if (!empty($_GET['filter_status'])) {
    $filter_status = $conn->real_escape_string($_GET['filter_status']);
    $filter_sql .= " AND sp.payment_status = '$filter_status'";
}

$payments = $conn->query("
    SELECT sp.*, sr.firstname, sr.lastname
    FROM student_payments sp
    JOIN students_registration sr ON sr.id = sp.student_id  
    $filter_sql
    ORDER BY sp.created_at DESC
");
?>


<!DOCTYPE html>
<html lang="en">
<head>

  <meta charset="UTF-8">
  <title>CASHIER DASHBOARD</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f0f4f3;
      margin: 0;
      color: #333;
      display: flex;
    }

    .sidebar {
      width: 230px;
      background-color: #004d00;
      color: white;
      min-height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      display: flex;
      flex-direction: column;
      padding: 30px 0;
      box-shadow: 4px 0 12px rgba(0, 0, 0, 0.1);
    }

    .sidebar h1 {
      font-size: 20px;
      text-align: center;
      margin: 0 20px 25px;
    }

    .nav-links {
      display: flex;
      flex-direction: column;
      gap: 5px;
      list-style: none;
      padding: 0;
      margin: 0;
      flex-grow: 1;
    }

    .nav-link {
      color: white;
      text-decoration: none;
      padding: 12px 24px;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: background-color 0.3s ease;
    }

    .nav-link:hover {
      background-color: #007f3f;
    }

    .sidebar .logout-btn {
      margin: 20px 20px 0;
      background-color: #a30000;
      color: white;
      text-align: center;
      padding: 10px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }

    .sidebar .logout-btn:hover {
      background-color: #7a0000;
    }

    .main-content {
      margin-left: 230px;
      padding: 30px;
      width: 100%;
      box-sizing: border-box;
    }

    .section-card {
      background: white;
      padding: 30px;
      margin-bottom: 30px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.08);
    }

    h2 {
      color: #007f3f;
      border-bottom: 2px solid #007f3f;
      padding-bottom: 10px;
      margin-top: 0;
    }

    form {
      margin-top: 20px;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }

    select,
    input[type="text"],
    input[type="number"] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }

    button {
      margin-top: 20px;
      background-color: #007f3f;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #004d00;
    }

    .secondary-btn {
      display: inline-block;
      padding: 10px 20px;
      margin-left: 10px;
      background-color: #7a7a7a;
      color: white;
      border-radius: 8px;
      font-weight: bold;
      text-decoration: none;
      transition: background-color 0.3s ease;
    }

    .secondary-btn:hover {
      background-color: #5c5c5c;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    table, th, td {
      border: 1px solid #ccc;
    }

    th, td {
      padding: 12px;
      text-align: left;
    }

    th {
      background-color: #007f3f;
      color: white;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    a {
      color: #007f3f;
      text-decoration: none;
      font-weight: bold;
    }

    a:hover {
      color: #004d00;
    }

    .success {
      background-color: #e0ffe0;
      padding: 10px;
      margin-bottom: 20px;
      border: 1px solid green;
      border-radius: 6px;
    }

    .error {
      background-color: #ffe0e0;
      padding: 10px;
      margin-bottom: 20px;
      border: 1px solid #d9534f;
      border-radius: 6px;
    }

    .paid { color: #27ae60; font-weight: bold; }
    .pending { color: #e74c3c; font-weight: bold; }
  </style>
</head>
<body>

<div class="sidebar">
  <h1>Cashier Panel</h1>
  <nav>
    <ul class="nav-links">
      <li><a class="nav-link" href="#record">💵 Record Payment</a></li>
      <li><a class="nav-link" href="#filters">🧮 Filter Payments</a></li>
      <li><a class="nav-link" href="#records">📑 Payment Records</a></li>
      <li><a class="nav-link" href="#fees">⚙️ Manage Fees</a></li>
    </ul>
  </nav>
  <a href="cashier_login.php" class="logout-btn">Logout</a>
</div>

<div class="main-content">
  <?php if ($flashMessage !== ''): ?>
    <?php $alertClass = ($flashType === 'success') ? 'success' : 'error'; ?>
    <div class="<?= $alertClass ?>"><?= htmlspecialchars($flashMessage) ?></div>
  <?php endif; ?>

  <div class="section-card" id="record">
    <h2>Record Onsite Payment</h2>

    <form method="GET" action="cashier_dashboard.php">
        <label for="search_name">Search Student by Last Name</label>
        <input type="text" name="search_name" placeholder="Enter surname..." value="<?= htmlspecialchars($searchQuery) ?>" required>
        <button type="submit">🔍 Search</button>
    </form>

    <?php if (!empty($search_results) && !$clearSearchFlag): ?>
      <div id="search-results-container">
        <h3>Search Results</h3>
        <ul>
            <?php foreach ($search_results as $s): ?>
                <li style="margin-bottom:20px; padding:10px; border:1px solid #ccc; border-radius:6px;">
                    <strong>[<?= $s['student_number'] ?>]</strong> <?= $s['lastname'] ?>, <?= $s['firstname'] ?> (<?= $s['year'] ?>)

                    <?php
                    $pending = $conn->prepare("
                        SELECT id, amount, created_at 
                        FROM student_payments 
                        WHERE student_id = ? AND payment_status = 'pending'
                        ORDER BY created_at DESC LIMIT 1
                    ");
                    $pending->bind_param("i", $s['id']);
                    $pending->execute();
                    $pendingResult = $pending->get_result();
                    if ($pendingRow = $pendingResult->fetch_assoc()):
                    ?>
    <h4>Pending Enrollment Payment</h4>
    <table style="width:100%; border-collapse: collapse; margin-top:10px; border:1px solid #ccc;">
        <thead>
            <tr style="background:#fff3cd;">
                <th style="padding:8px; border:1px solid #ccc;">Date</th>
                <th style="padding:8px; border:1px solid #ccc;">Amount</th>
                <th style="padding:8px; border:1px solid #ccc;">Status</th>
                <th style="padding:8px; border:1px solid #ccc;">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding:8px; border:1px solid #ccc;"><?= $pendingRow['created_at'] ?></td>
                <td style="padding:8px; border:1px solid #ccc;">₱ <?= number_format($pendingRow['amount'], 2) ?></td>
                <td style="padding:8px; border:1px solid #ccc; color:orange; font-weight:bold;">Pending</td>
                <td style="padding:8px; border:1px solid #ccc;">
                    <form method="POST" action="cashier_dashboard.php" style="display:flex; gap:5px; align-items:center;" class="pending-payment-form">
                        <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                        <input type="hidden" name="amount" value="<?= $pendingRow['amount'] ?>">
                        <input type="hidden" name="payment_type" value="Cash">
                        <input type="hidden" name="payment_status" value="paid">

                        <input type="text" name="or_number" placeholder="OR #" required 
                               style="flex:1; padding:5px; border:1px solid #ccc; border-radius:4px;">

                        <button type="submit" 
                                style="background:green; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer;">
                            ✅ Mark as Paid
                        </button>
                    </form>
                </td>
            </tr>
        </tbody>
    </table>

                    <?php endif; $pending->close(); ?>

                    <div style="margin-top:15px;">
                        <form class="cash-payment-form" method="POST" action="cashier_dashboard.php">
                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                            <input type="hidden" name="firstname" value="<?= $s['firstname'] ?>">
                            <input type="hidden" name="lastname" value="<?= $s['lastname'] ?>">

                            <label for="amount">New Payment Amount (₱)</label>
                            <input type="number" name="amount" step="0.01" required>

                            <label for="or_number">Official Receipt #</label>
                            <input type="text" name="or_number" required>

                            <input type="hidden" name="payment_type" value="Cash">
                            <input type="hidden" name="payment_status" value="paid">

                            <button type="submit">💵 Record Cash Payment</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>

  <div class="section-card" id="filters">
    <h2>Filter Payments</h2>
    <form method="GET" action="cashier_dashboard.php#filters" id="filterForm">
      <label>Grade Level</label>
      <select name="filter_grade_level">
        <option value="">All</option>
        <?php
          $grade_levels_result = $conn->query("SELECT DISTINCT year FROM students_registration ORDER BY year");
          while ($gl = $grade_levels_result->fetch_assoc()):
              $selected = ($_GET['filter_grade_level'] ?? '') == $gl['year'] ? "selected" : "";
              echo "<option value='{$gl['year']}' $selected>{$gl['year']}</option>";
          endwhile;
        ?>
      </select>

      <label>Status</label>
      <select name="filter_status">
        <option value="">All</option>
        <option value="paid" <?= ($_GET['filter_status'] ?? '') == "paid" ? "selected" : "" ?>>Paid</option>
        <option value="pending" <?= ($_GET['filter_status'] ?? '') == "pending" ? "selected" : "" ?>>Pending</option>
      </select>

      <button type="submit">Apply Filter</button>
      <a href="cashier_dashboard.php" class="secondary-btn">Reset</a>
    </form>
  </div>

  <div class="section-card" id="records">
    <h2>Payment Records</h2>
    <table id="paymentTable">
      <thead>
        <tr>
        <th>Date</th>
        <th>Student</th>
        <th>Type</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Timestamp</th>
        <th>Payments</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($row = $payments->fetch_assoc()): ?>
      <tr id="payment-row-<?php echo $row['id']; ?>">
        <td><?= date("Y-m-d", strtotime($row['created_at'])) ?></td>
        <td><?= $row['lastname'] ?>, <?= $row['firstname'] ?></td>
        <td><?= $row['payment_type'] ?></td>
        <td>₱ <?= number_format($row['amount'], 2) ?></td>

        <!-- Status Column -->
        <td class="payment-status" id="status-<?php echo $row['id']; ?>">
            <?php 
              if (!empty($row['payment_status'])) {
                  if ($row['payment_status'] === 'declined') {
                      echo "<span style='color: red;'>Declined</span>";
                  } elseif ($row['payment_status'] === 'paid') {
                      echo "<span style='color: green;'>Paid</span>";
                  } else {
                      echo ucfirst(htmlspecialchars($row['payment_status']));
                  }
              } else {
                  echo "Pending";
              }
            ?>
        </td>

        <td><?= $row['created_at'] ?></td>
        
        <td>
        <?php 
  $shotUrl = $row['screenshot_path']; 
  if ($shotUrl && strpos($shotUrl, 'http') !== 0) {
      $shotUrl = "http://localhost" . $shotUrl;
  }
  echo "<!-- DEBUG: $shotUrl -->";  // 👈 check page source
?>

          <button 
            class="view-payment-btn" 
            data-id="<?= $row['id'] ?>" 
            data-student="<?= $row['lastname'] ?>, <?= $row['firstname'] ?>"
            data-type="<?= $row['payment_type'] ?>"
            data-amount="<?= $row['amount'] ?>"
            data-status="<?= ucfirst($row['payment_status']) ?>"
            data-reference="<?= $row['reference_number'] ?>"
            data-or="<?= $row['or_number'] ?>"
            data-screenshot="<?= $shotUrl ?>"   
          >
            View Payment
          </button>

          
        </td>
      </tr>
    <?php endwhile; ?>
</tbody>


</table>
  </div>

  <div class="section-card" id="fees">
    <h2>Manage Tuition Fees</h2>
    <form method="POST" action="cashier_dashboard.php#fees" class="tuition-form">
      <input type="hidden" name="tuition_fee_form" value="1">

      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div>
          <label for="school_year">School Year</label>
          <input type="text" id="school_year" name="school_year" placeholder="e.g., 2024-2025" required>
        </div>

        <div>
          <label for="year">Grade Level</label>
          <select id="year" name="year" required>
            <?php
              $gradeOptions = [
                'kinder1' => 'Kinder 1',
                'kinder2' => 'Kinder 2',
                'grade1' => 'Grade 1',
                'grade2' => 'Grade 2',
                'grade3' => 'Grade 3',
                'grade4' => 'Grade 4',
                'grade5' => 'Grade 5',
                'grade6' => 'Grade 6',
                'grade7' => 'Grade 7',
                'grade8' => 'Grade 8',
                'grade9' => 'Grade 9',
                'grade10' => 'Grade 10',
                'grade11' => 'Grade 11',
                'grade12' => 'Grade 12',
              ];
              foreach ($gradeOptions as $value => $label) {
                  echo "<option value='{$value}'>{$label}</option>";
              }
            ?>
          </select>
        </div>

        <div>
          <label for="student_type">Student Type</label>
          <select id="student_type" name="student_type" required>
            <option value="new">New</option>
            <option value="old">Old</option>
          </select>
        </div>

        <div>
          <label for="entrance_fee">Entrance Fee</label>
          <input type="number" step="0.01" id="entrance_fee" name="entrance_fee" required oninput="calculatePayment()">
        </div>

        <div>
          <label for="miscellaneous_fee">Miscellaneous Fee</label>
          <input type="number" step="0.01" id="miscellaneous_fee" name="miscellaneous_fee" required oninput="calculatePayment()">
        </div>

        <div>
          <label for="tuition_fee">Tuition Fee</label>
          <input type="number" step="0.01" id="tuition_fee" name="tuition_fee" required oninput="calculatePayment()">
        </div>

        <div>
          <label for="total_upon_enrollment">Total Upon Enrollment</label>
          <input type="number" step="0.01" id="total_upon_enrollment" name="total_upon_enrollment" required>
        </div>

        <div>
          <label for="payment_schedule">Payment Schedule</label>
          <select id="payment_schedule" name="payment_schedule" onchange="calculatePayment()" required>
            <option value="annually">Annually</option>
            <option value="semi-annually">Semi-Annually</option>
            <option value="quarterly">Quarterly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>

        <div>
          <label for="calculated_payment">Calculated Payment</label>
          <input type="text" id="calculated_payment" readonly>
        </div>
      </div>

      <button type="submit">Save Tuition Fee</button>
    </form>
  </div>
</div>
          <!-- Payment Modal (outside the table loop, at the end of body) -->
        <!-- Payment Modal -->
<div id="paymentModal" style="
    display:none; 
    position:fixed; 
    top:0; left:0; 
    width:100%; height:100%;
    background:rgba(0,0,0,0.7); 
    z-index:9999; 
    justify-content:center; 
    align-items:center;
    overflow:auto;  
    padding: 20px;
">
  <div style="
      background:#fff; 
      padding:20px; 
      border-radius:10px; 
      width:500px; 
      max-width:95%; 
      box-sizing:border-box; 
      position:relative;
  ">
    <!-- Close button -->
    <span id="closeModal" style="
        position:absolute; 
        top:10px; right:15px; 
        cursor:pointer; 
        font-weight:bold; 
        font-size:20px;
    ">&times;</span>
    
    <h3>Payment Details</h3>
    <p><strong>Student:</strong> <span id="modalStudent"></span></p>
    <p><strong>Type:</strong> <span id="modalType"></span></p>
    <p><strong>Amount:</strong> ₱<span id="modalAmount"></span></p>
    <p><strong>Status:</strong> <span id="modalStatus"></span></p>
    <p><strong id="modalLabel">Reference #:</strong> <span id="modalRefOr"></span></p>

    <!-- Screenshot (online only) -->
    <div id="screenshotSection" style="text-align:center; margin-bottom:20px;">
      <img id="modalScreenshot" src="" alt="Payment Screenshot" 
          style="max-width:100%; height:auto; border:1px solid #ccc; border-radius:6px;">
    </div>

    <!-- Hidden input -->
    <input type="hidden" id="modalPaymentId">

    <!-- Accept / Decline Buttons -->
    <div style="margin-top:20px; text-align:center;">
      <button id="acceptPaymentBtn" style="
          background-color:green; 
          color:white; 
          padding:10px 20px; 
          border:none; 
          border-radius:8px; 
          cursor:pointer;
      ">Accept</button>
      <button id="declinePaymentBtn" style="
          background-color:red; 
          color:white; 
          padding:10px 20px; 
          border:none; 
          border-radius:8px; 
          cursor:pointer;
      ">Decline</button>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("paymentModal");
  const closeModal = document.getElementById("closeModal");
  const acceptBtn = document.getElementById("acceptPaymentBtn");
  const declineBtn = document.getElementById("declinePaymentBtn");
  const cashForm = document.getElementById("cashPaymentForm");
  const screenshotSection = document.getElementById("screenshotSection");
  const modalScreenshot = document.getElementById("modalScreenshot");
  let currentType = "";
  let activeButton = null;

  const searchContainer = document.getElementById('search-results-container');
  const searchInput = document.querySelector('input[name="search_name"]');
  const clearSearchUI = () => {
    if (searchContainer) {
      searchContainer.innerHTML = '';
    }
    if (searchInput) {
      searchInput.value = '';
    }
  };

  document.querySelectorAll('.cash-payment-form, .pending-payment-form').forEach(form => {
    form.addEventListener('submit', () => {
      clearSearchUI();
    });
  });

  // Open Modal when "View Payment" is clicked
  document.querySelectorAll(".view-payment-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      activeButton = btn;
      document.getElementById("modalStudent").textContent = btn.dataset.student || "";
      document.getElementById("modalType").textContent = btn.dataset.type || "";
      document.getElementById("modalAmount").textContent = (parseFloat(btn.dataset.amount) || 0).toFixed(2);
      document.getElementById("modalStatus").textContent = btn.dataset.status || "";
      document.getElementById("modalPaymentId").value = btn.dataset.id || "";

      // Set screenshot (if any)
      const shot = (btn.dataset.screenshot || "").trim();
        if (shot) {
          modalScreenshot.src = shot;   // ✅ now points to correct /Enrollment/payment_uploads/ file
          screenshotSection.style.display = "block";
        } else {
          screenshotSection.style.display = "none";
        }

  // ✅ Conditional Reference / OR display
    currentType = (btn.dataset.type || "").toLowerCase();
      if (currentType === "cash") {
        document.getElementById("modalLabel").textContent = "Official Receipt #:";
        document.getElementById("modalRefOr").textContent = btn.dataset.or || "N/A";

        acceptBtn.style.display = "none";
        declineBtn.style.display = "none";
      } else {
        document.getElementById("modalLabel").textContent = "Reference #:";
        document.getElementById("modalRefOr").textContent = btn.dataset.reference || "N/A";

        acceptBtn.style.display = "inline-block";
        declineBtn.style.display = "inline-block";
      }

      modal.style.display = "flex";
  });
});
  // Close modal
  if (closeModal) {
    closeModal.addEventListener("click", () => (modal.style.display = "none"));
  }
  window.addEventListener("click", (e) => {
    if (e.target === modal) modal.style.display = "none";
  });

  // Helpers for safe fetch + JSON
  async function postForm(url, obj) {
    const body = new URLSearchParams(obj).toString();
    const res = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body,
    });
    const text = await res.text(); // guard against non-JSON (warnings/notices)
    try {
      return JSON.parse(text);
    } catch {
      // Surface raw server text so you can see any PHP warnings
      alert("Server returned a non-JSON response:\n\n" + text);
      return { success: false, error: "Non-JSON response" };
    }
  }

  // Accept Payment
  if (acceptBtn) {
    acceptBtn.addEventListener("click", async (e) => {
      e.preventDefault();
      const id = document.getElementById("modalPaymentId").value;
      

      const payload = { id, status: "paid" };
      if (currentType === "cash") payload.or_number = document.getElementById("modalRefOr").textContent;

      const data = await postForm("update_payment_status.php", payload);
      if (data && data.success) {
        const statusCell = document.getElementById("status-" + id);
        if (statusCell) {
          statusCell.innerHTML =
            "<span style='color: green;'>Paid" + (currentType === "cash" ? " (Cash)" : "") + "</span>";
        }
        if (activeButton) {
          activeButton.dataset.status = 'Paid';
          if (payload.or_number) {
            activeButton.dataset.or = payload.or_number;
          }
        }
        alert("✅ Student’s payment has been accepted.");
        modal.style.display = "none";
      } else if (data) {
        alert("Error: " + (data.error || "Unknown error"));
      }
    });
  }

  // Decline Payment
  if (declineBtn) {
    declineBtn.addEventListener("click", async (e) => {
      e.preventDefault();
      const id = document.getElementById("modalPaymentId").value;

      const data = await postForm("update_payment_status.php", { id, status: "declined" });
      if (data && data.success) {
        const statusCell = document.getElementById("status-" + id);
        if (statusCell) statusCell.innerHTML = "<span style='color: red;'>Declined</span>";
        if (activeButton) {
          activeButton.dataset.status = 'Declined';
        }
        alert("❌ Student’s payment has been declined.");
        modal.style.display = "none";
      } else if (data) {
        alert("Error: " + (data.error || "Unknown error"));
      }
    });
  }
});
</script>

<script>
function calculatePayment() {
  const tuitionInput = document.getElementById('tuition_fee');
  const miscInput = document.getElementById('miscellaneous_fee');
  const entranceInput = document.getElementById('entrance_fee');
  const scheduleSelect = document.getElementById('payment_schedule');
  const display = document.getElementById('calculated_payment');

  if (!tuitionInput || !miscInput || !entranceInput || !scheduleSelect || !display) {
    return;
  }

  const tuition = parseFloat(tuitionInput.value || '0');
  const misc = parseFloat(miscInput.value || '0');
  const entrance = parseFloat(entranceInput.value || '0');
  const total = tuition + misc + entrance;

  let result = total;
  switch (scheduleSelect.value) {
    case 'semi-annually':
      result = total / 2;
      break;
    case 'quarterly':
      result = total / 4;
      break;
    case 'monthly':
      result = total / 12;
      break;
    default:
      result = total;
  }

  display.value = result.toFixed(2);
}
</script>
<script>
// 🔹 AJAX handler for cash payments
document.querySelectorAll(".cash-payment-form").forEach(form => {
  form.addEventListener("submit", async e => {
    e.preventDefault();
    const formData = new FormData(form);

    const res = await fetch("cashier_dashboard.php", { method: "POST", body: formData });
    const text = await res.text();

    // Try parse JSON, fallback to reload
    try {
      const data = JSON.parse(text);
      if (data.success) {
        alert("✅ Payment recorded successfully.");

        // Append new row in Payment Records instantly
        const table = document.querySelector("#paymentTable tbody");
        table.insertAdjacentHTML("afterbegin", `
          <tr>
            <td>${new Date().toISOString().split('T')[0]}</td>
            <td>${formData.get("lastname")}, ${formData.get("firstname")}</td>
            <td>Cash</td>
            <td>₱ ${parseFloat(formData.get("amount")).toFixed(2)}</td>
            <td><span style="color:green;">Paid</span></td>
            <td>Just Now</td>
          </tr>
        `);

        form.reset();
      }
    } catch {
      // fallback reload if response is not JSON
      location.reload();
    }
  });
});
</script>




          <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        


</body>
</html>
