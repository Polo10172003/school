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
$saveSuccess = false;
$flashMessage = $_SESSION['cashier_flash'] ?? '';
$flashType = $_SESSION['cashier_flash_type'] ?? '';
$clearSearchFlag = isset($_GET['search_cleared']);
if ($flashMessage !== '') {
    unset($_SESSION['cashier_flash']);
}
if ($flashType !== '') {
    unset($_SESSION['cashier_flash_type']);
}
// We keep search results visible after actions; cashiers can clear manually.

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["student_id"])) {
  $student_id = intval($_POST["student_id"]);
  $amount = floatval($_POST["amount"] ?? 0);
  $payment_mode = $_POST["payment_mode"] ?? ($_POST["payment_type"] ?? "Cash");
  $payment_type = $payment_mode ?: 'Cash';
  $payment_status = $_POST["payment_status"] ?? "paid";
  $payment_date = date("Y-m-d");
  $or_number = isset($_POST["or_number"]) ? trim($_POST["or_number"]) : null;
  $reference_number = isset($_POST["reference_number"]) ? trim($_POST["reference_number"]) : null;
  if ($or_number === '') {
      $or_number = null;
  }
  if ($reference_number === '') {
      $reference_number = null;
  }

  if (strcasecmp($payment_type, 'Cash') === 0 && ($or_number === null || $or_number === '')) {
      $message = "Official receipt number is required for cash payments.";
  } elseif (strcasecmp($payment_type, 'Cash') !== 0 && ($reference_number === null || $reference_number === '')) {
      $message = "Reference number is required for non-cash payments.";
  }

  if ($message === "") {

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
      if (strcasecmp($payment_type, 'Cash') === 0) {
          $updPay = $conn->prepare("
              UPDATE student_payments 
              SET payment_type = ?, payment_status = 'paid', or_number = ?, payment_date = ?
              WHERE id = ?
          ");
          $updPay->bind_param("sssi", $payment_type, $or_number, $payment_date, $pending_payment_id);
      } else {
          $updPay = $conn->prepare("
              UPDATE student_payments 
              SET payment_type = ?, payment_status = 'paid', reference_number = ?, payment_date = ?
              WHERE id = ?
          ");
          $updPay->bind_param("sssi", $payment_type, $reference_number, $payment_date, $pending_payment_id);
      }
      $updPay->execute();
      $updPay->close();

      // Enroll student
      $upd = $conn->prepare("UPDATE students_registration SET enrollment_status = 'enrolled' WHERE id = ?");
      $upd->bind_param("i", $student_id);
      $upd->execute();
      $upd->close();

      $message = "Pending payment updated to Paid. Student enrolled.";
      $saveSuccess = true;
      } else {
      // ❌ No pending → insert new payment (normal flow)
      $ins = $conn->prepare("
          INSERT INTO student_payments
              (student_id, firstname, lastname, payment_type, amount, payment_status, payment_date, or_number, reference_number, created_at)
          VALUES (?,?,?,?,?, 'paid', ?, ?, ?, NOW())
      ");
      $ins->bind_param("isssdsss", $student_id, $firstname, $lastname, $payment_type, $amount, $payment_date, $or_number, $reference_number);

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

          $message = ucfirst($payment_type) . " payment recorded successfully. Student enrolled.";
          $saveSuccess = true;
      } else {
          $message = "Error: " . $conn->error;
      }
      $ins->close();
      }
    }
  if ($message !== '') {
      $_SESSION['cashier_flash'] = $message;
      $_SESSION['cashier_flash_type'] = $saveSuccess ? 'success' : 'error';
  }

  $redirectUrl = 'cashier_dashboard.php';
  $lastSearch = trim($_POST['search_name'] ?? '');
  if ($lastSearch !== '') {
      $redirectUrl .= '?search_name=' . urlencode($lastSearch) . '#record';
  } else {
      $redirectUrl .= '#record';
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
    $stmt = $conn->prepare("SELECT id, student_number, firstname, lastname, year, section, adviser, student_type, enrollment_status FROM students_registration WHERE lastname LIKE ? ORDER BY lastname");
    $stmt->bind_param("s", $search_name_like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $search_results[] = $row;
    }
    $stmt->close();
}

if (!function_exists('cashier_previous_grade_label')) {
    function cashier_previous_grade_label($current)
    {
        static $map = [
            'Kinder 1' => 'Preschool',
            'Kinder 2' => 'Kinder 1',
            'Grade 1'  => 'Kinder 2',
            'Grade 2'  => 'Grade 1',
            'Grade 3'  => 'Grade 2',
            'Grade 4'  => 'Grade 3',
            'Grade 5'  => 'Grade 4',
            'Grade 6'  => 'Grade 5',
            'Grade 7'  => 'Grade 6',
            'Grade 8'  => 'Grade 7',
            'Grade 9'  => 'Grade 8',
            'Grade 10' => 'Grade 9',
            'Grade 11' => 'Grade 10',
            'Grade 12' => 'Grade 11',
        ];
        return $map[$current] ?? null;
    }
}

if (!function_exists('cashier_normalize_grade_key')) {
    function cashier_normalize_grade_key($label)
    {
        return strtolower(str_replace([' ', '-', '_'], '', $label));
    }
}

if (!function_exists('cashier_fetch_fee')) {
    function cashier_fetch_fee(mysqli $conn, $normalizedGrade, array $typeCandidates)
    {
        $sql = "SELECT * FROM tuition_fees WHERE REPLACE(REPLACE(REPLACE(LOWER(grade_level), ' ', ''), '-', ''), '_', '') = ? AND LOWER(student_type) = ? ORDER BY school_year DESC LIMIT 1";
        foreach ($typeCandidates as $candidateType) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $normalizedGrade, $candidateType);
            $stmt->execute();
            $fee = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($fee) {
                return $fee;
            }
        }
        return null;
    }
}

if (!function_exists('cashier_generate_schedule')) {
    function cashier_generate_schedule($fee, $plan, $start_date = '2025-06-01')
    {
        $schedule = [];
        $date = new DateTime($start_date);

        $entrance = (float) $fee['entrance_fee'];
        $misc = (float) $fee['miscellaneous_fee'];
        $tuition = (float) $fee['tuition_fee'];
        $annual = $entrance + $misc + $tuition;

        switch (strtolower($plan)) {
            case 'annually':
                $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => $annual];
                break;
            case 'cash':
                $discountedEntrance = $entrance * 0.9;
                $totalCash = $discountedEntrance + $misc + $tuition;
                $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => $totalCash];
                break;
            case 'semi-annually':
                $upon = $entrance + $misc + ($tuition / 2);
                $next = $tuition / 2;
                $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => $upon];
                $date->modify('+6 months');
                $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => $next];
                break;
            case 'quarterly':
                $upon = $entrance + ($misc / 4) + ($tuition / 4);
                $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => $upon];
                for ($i = 1; $i <= 3; $i++) {
                    $date->modify('+3 months');
                    $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => ($misc / 4) + ($tuition / 4)];
                }
                break;
            case 'monthly':
                $upon = $entrance + ($misc / 12) + ($tuition / 12);
                $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => $upon];
                for ($i = 1; $i <= 11; $i++) {
                    $date->modify('+1 month');
                    $schedule[] = ['Due Date' => $date->format('Y-m-d'), 'Amount' => ($misc / 12) + ($tuition / 12)];
                }
                break;
        }

        return $schedule;
    }
}

$search_financial = [];
if (!empty($search_results)) {
    foreach ($search_results as $result) {
        $studentId = (int) $result['id'];
        $gradeLevel = $result['year'];
        $studentType = strtolower($result['student_type'] ?? 'new');
        $normalizedGrade = cashier_normalize_grade_key($gradeLevel);
        $typeCandidates = array_values(array_unique([$studentType, 'new', 'old']));

        $fee = cashier_fetch_fee($conn, $normalizedGrade, $typeCandidates);

        $previous_label = cashier_previous_grade_label($gradeLevel);
        $previous_fee = null;
        if ($previous_label) {
            $normalizedPrev = cashier_normalize_grade_key($previous_label);
            $previous_fee = cashier_fetch_fee($conn, $normalizedPrev, $typeCandidates);
        }

        $payments_stmt = $conn->prepare("SELECT payment_type, amount, payment_status, payment_date, reference_number, or_number FROM student_payments WHERE student_id = ? ORDER BY created_at DESC");
        $payments_stmt->bind_param('i', $studentId);
        $payments_stmt->execute();
        $payments_res = $payments_stmt->get_result();
        $paid = [];
        $pending = [];
        while ($row = $payments_res->fetch_assoc()) {
            if (strtolower($row['payment_status']) === 'paid') {
                $paid[] = $row;
            } else {
                $pending[] = $row;
            }
        }
        $payments_stmt->close();

        $previous_grade_total = 0.0;
        if ($previous_fee) {
            $previous_grade_total = (float) $previous_fee['entrance_fee'] + (float) $previous_fee['miscellaneous_fee'] + (float) $previous_fee['tuition_fee'];
        }

        $total_paid = array_sum(array_map('floatval', array_column($paid, 'amount')));

        $previous_paid_applied = 0.0;
        $previous_outstanding = 0.0;
        if ($previous_fee) {
            $previous_paid_applied = min($total_paid, $previous_grade_total);
            $previous_outstanding = max($previous_grade_total - $total_paid, 0.0);
        }

        $current_paid_amount = max($total_paid - $previous_paid_applied, 0.0);

        $schedule_rows = [];
        $next_due_row = null;
        $remaining_balance = $previous_outstanding;
        $current_year_total = 0.0;

        if ($fee) {
            $current_year_total = (float) $fee['entrance_fee'] + (float) $fee['miscellaneous_fee'] + (float) $fee['tuition_fee'];
            $schedule = cashier_generate_schedule($fee, 'quarterly');
            $remaining = $current_paid_amount;
            $first_unpaid_index = null;

            foreach ($schedule as $idx => $due) {
                $due_amount = (float) $due['Amount'];
                if ($remaining > 0) {
                    $deduct = min($remaining, $due_amount);
                    $due_amount -= $deduct;
                    $remaining -= $deduct;
                }
                if ($due_amount <= 0) {
                    continue;
                }
                if ($first_unpaid_index === null) {
                    $first_unpaid_index = $idx;
                }
                $schedule_rows[] = [
                    'due_date' => $due['Due Date'],
                    'amount' => $due_amount,
                    'is_next' => false,
                ];
                $remaining_balance += $due_amount;
            }

            if ($first_unpaid_index !== null) {
                $schedule_rows = array_values($schedule_rows);
                if (!empty($schedule_rows)) {
                    $schedule_rows[0]['is_next'] = true;
                    $next_due_row = $schedule_rows[0];
                }
            }
        }

        $pending_total = array_sum(array_map('floatval', array_column($pending, 'amount')));

        $pending_display = [];
        foreach ($pending as $entry) {
            $entry['payment_type_display'] = $entry['payment_type'] ?? 'Pending';
            $pending_display[] = $entry;
        }
        if ($previous_label && $previous_outstanding > 0 && empty($pending_display)) {
            $pending_display[] = [
                'payment_type_display' => 'Past Due for ' . $previous_label,
                'amount' => $previous_outstanding,
                'payment_status' => 'Past Due',
                'payment_date' => 'Previous School Year',
                'reference_number' => null,
                'or_number' => null,
                'is_placeholder' => true,
            ];
        }

        $search_financial[$studentId] = [
            'remaining_balance' => $remaining_balance,
            'total_paid' => $total_paid,
            'pending_total' => $pending_total,
            'previous_outstanding' => $previous_outstanding,
            'previous_grade_label' => $previous_label,
            'schedule_rows' => $schedule_rows,
            'pending_rows' => $pending_display,
            'next_due_row' => $next_due_row,
            'current_year_total' => $current_year_total,
            'has_previous_outstanding' => $previous_outstanding > 0,
        ];
    }
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

    .search-result-card h4 {
      margin-top: 24px;
      margin-bottom: 10px;
      color: #0f4f24;
    }

    .search-result-card {
      list-style: none;
    }

    .search-result-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(20, 90, 50, 0.12);
      color: #145A32;
      padding: 6px 14px;
      border-radius: 999px;
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .summary-tile-sm {
      background: rgba(255, 255, 255, 0.95);
      border: 1px solid rgba(20, 90, 50, 0.12);
      border-radius: 12px;
      padding: 12px 16px;
      min-width: 160px;
      box-shadow: 0 6px 18px rgba(12, 68, 49, 0.09);
    }

    .summary-tile-sm .label {
      display: block;
      font-size: 0.75rem;
      text-transform: uppercase;
      color: #5d6d6f;
      letter-spacing: 0.06em;
      margin-bottom: 4px;
    }

    .summary-tile-sm strong {
      font-size: 1.1rem;
      color: #145A32;
    }

    .search-subsection {
      margin-top: 22px;
    }

    .search-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      border: 1px solid #ced6d1;
      background: #ffffff;
    }

    .search-table th,
    .search-table td {
      padding: 10px 12px;
      border: 1px solid #d4ddd8;
    }

    .search-table th {
      background: #145A32;
      color: #ffffff;
      text-align: left;
      font-weight: 600;
    }

    .search-table .text-end {
      text-align: right;
    }

    .next-due {
      background: rgba(251, 216, 10, 0.22);
      font-weight: 600;
    }

    .row-outstanding {
      background: rgba(231, 76, 60, 0.12);
    }

    .search-subsection small {
      color: #5d6d6f;
    }

    .form-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      margin-top: 12px;
    }

    .form-grid > div {
      flex: 1 1 220px;
    }

    .payment-fields-group {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
    }

    .payment-fields-group .cash-field,
    .payment-fields-group .gcash-field {
      flex: 1 1 220px;
    }

    .payment-fields-group label {
      margin-top: 0;
    }

    .record-btn {
      margin-top: 18px;
      background: linear-gradient(135deg, #145A32, #0d3b22);
      color: #fff;
      padding: 10px 22px;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .record-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 24px rgba(12, 68, 49, 0.18);
    }

    .cashier-payment-entry select,
    .cashier-payment-entry input {
      width: 100%;
      border-radius: 8px;
    }

    .search-alert strong {
      color: #784700;
    }

    .search-result-card small {
      color: #6f7a76;
    }
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
                <?php $snapshot = $search_financial[$s['id']] ?? null; ?>
                <li class="search-result-card" style="margin-bottom:20px; padding:20px; border:1px solid #cdd5d2; border-radius:12px; background:#fdfefd;">
                    <div class="search-result-header" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:12px;">
                        <div>
                            <span class="search-result-pill">Portal Account</span>
                            <h3 style="margin:8px 0 4px;">
                                <?= htmlspecialchars($s['lastname']) ?>, <?= htmlspecialchars($s['firstname']) ?>
                                <small style="color:#5d6d6f; font-weight:500;">[<?= $s['student_number'] ?: 'No Student # yet' ?>]</small>
                            </h3>
                            <p style="margin:0; color:#4f5d57;">Grade <?= htmlspecialchars($s['year']) ?> <?= $s['section'] ? '• Section ' . htmlspecialchars($s['section']) : '' ?> <?= $s['student_type'] ? '• ' . ucfirst($s['student_type']) . ' student' : '' ?></p>
                            <p style="margin:4px 0 0; color:#8aa19a; font-size:0.9rem;">Enrollment status: <strong><?= htmlspecialchars(ucfirst($s['enrollment_status'] ?? 'pending')) ?></strong></p>
                        </div>
                        <?php if ($snapshot): ?>
                            <div class="search-summary-tiles" style="display:flex; flex-wrap:wrap; gap:12px;">
                                <div class="summary-tile-sm">
                                    <span class="label">Remaining Balance</span>
                                    <strong>₱<?= number_format($snapshot['remaining_balance'], 2) ?></strong>
                                </div>
                                <div class="summary-tile-sm">
                                    <span class="label">Total Paid</span>
                                    <strong>₱<?= number_format($snapshot['total_paid'], 2) ?></strong>
                                </div>
                                <div class="summary-tile-sm">
                                    <span class="label">Pending Review</span>
                                    <strong>₱<?= number_format($snapshot['pending_total'], 2) ?></strong>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($snapshot && $snapshot['has_previous_outstanding']): ?>
                        <div class="search-alert" style="margin:18px 0; padding:12px 16px; border-left:4px solid #e59819; background:rgba(251,216,10,0.16); color:#8a6d0a; border-radius:10px;">
                            Outstanding balance from <?= htmlspecialchars($snapshot['previous_grade_label']); ?>: <strong>₱<?= number_format($snapshot['previous_outstanding'], 2) ?></strong>
                        </div>
                    <?php endif; ?>

                    <?php if ($snapshot && !empty($snapshot['schedule_rows'])): ?>
                        <div class="search-subsection">
                            <h4>Upcoming Tuition Schedule</h4>
                            <table class="search-table">
                                <thead>
                                    <tr>
                                        <th>Due Date</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($snapshot['schedule_rows'] as $row): ?>
                                        <tr class="<?= !empty($row['is_next']) ? 'next-due' : '' ?>">
                                            <td><?= htmlspecialchars($row['due_date']) ?></td>
                                            <td class="text-end">₱<?= number_format($row['amount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($snapshot && $snapshot['current_year_total'] > 0): ?>
                        <p style="margin:16px 0 0; color:#5d6d6f;">All dues for this plan are already covered.</p>
                    <?php else: ?>
                        <p style="margin:16px 0 0; color:#5d6d6f;">Tuition fee setup not found for this student yet.</p>
                    <?php endif; ?>

                    <?php if ($snapshot && !empty($snapshot['pending_rows'])): ?>
                        <div class="search-subsection">
                            <h4>Pending Payments</h4>
                            <table class="search-table">
                                <thead>
                                    <tr>
                                        <th>Payment Type</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($snapshot['pending_rows'] as $pending_row): ?>
                                        <tr class="<?= !empty($pending_row['is_placeholder']) ? 'row-outstanding' : '' ?>">
                                            <td><?= htmlspecialchars($pending_row['payment_type_display'] ?? ($pending_row['payment_type'] ?? 'Pending')) ?></td>
                                            <td class="text-end">₱<?= number_format((float) ($pending_row['amount'] ?? 0), 2) ?></td>
                                            <td><?= htmlspecialchars($pending_row['payment_status'] ?? 'Pending') ?></td>
                                            <td><?= htmlspecialchars($pending_row['payment_date'] ?? 'Awaiting review') ?></td>
                                            <td><?= htmlspecialchars($pending_row['reference_number'] ?? $pending_row['or_number'] ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="search-subsection">
                        <h4>Record Payment</h4>
                        <form class="cash-payment-form cashier-payment-entry" method="POST" action="cashier_dashboard.php" data-student="<?= $s['id'] ?>">
                            <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                            <input type="hidden" name="firstname" value="<?= htmlspecialchars($s['firstname']) ?>">
                            <input type="hidden" name="lastname" value="<?= htmlspecialchars($s['lastname']) ?>">
                            <input type="hidden" name="payment_status" value="paid">
                            <input type="hidden" name="search_name" value="<?= htmlspecialchars($searchQuery) ?>">

                            <div class="form-grid">
                                <div>
                                    <label>Payment Method</label>
                                    <select name="payment_mode" class="payment-mode" data-target="modal-fields-<?= $s['id'] ?>">
                                        <option value="Cash">Cash</option>
                                        <option value="GCash">GCash</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Amount (₱)</label>
                                    <input type="number" name="amount" step="0.01" min="0" required value="<?= $snapshot && $snapshot['next_due_row'] ? number_format($snapshot['next_due_row']['amount'], 2, '.', '') : '' ?>">
                                    <?php if ($snapshot && $snapshot['next_due_row']): ?>
                                        <small style="display:block; color:#5d6d6f;">Next due on <?= htmlspecialchars($snapshot['next_due_row']['due_date']) ?> for ₱<?= number_format($snapshot['next_due_row']['amount'], 2) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div id="modal-fields-<?= $s['id'] ?>" class="payment-fields-group">
                                    <div class="cash-field">
                                        <label>Official Receipt #</label>
                                        <input type="text" name="or_number" placeholder="Enter OR number" required>
                                    </div>
                                    <div class="gcash-field" style="display:none;">
                                        <label>GCash Reference #</label>
                                        <input type="text" name="reference_number" placeholder="Enter reference #">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="record-btn">Record Payment</button>
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

<style>
  #paymentModal.modal-processing {
    cursor: wait;
  }
  #acceptPaymentBtn.btn-loading,
  #declinePaymentBtn.btn-loading,
  #acceptPaymentBtn:disabled,
  #declinePaymentBtn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("paymentModal");
  const closeModal = document.getElementById("closeModal");
  const acceptBtn = document.getElementById("acceptPaymentBtn");
  const declineBtn = document.getElementById("declinePaymentBtn");
  const screenshotSection = document.getElementById("screenshotSection");
  const modalScreenshot = document.getElementById("modalScreenshot");
  let currentType = "";
  let activeButton = null;

  const searchContainer = document.getElementById('search-results-container');
  const searchInput = document.querySelector('input[name="search_name"]');
  document.querySelectorAll('.payment-mode').forEach(select => {
    const containerId = select.dataset.target;
    const fieldsContainer = containerId ? document.getElementById(containerId) : null;
    if (!fieldsContainer) {
      return;
    }
    const cashField = fieldsContainer.querySelector('.cash-field');
    const gcashField = fieldsContainer.querySelector('.gcash-field');
    const orInput = fieldsContainer.querySelector('input[name="or_number"]');
    const refInput = fieldsContainer.querySelector('input[name="reference_number"]');

    const syncFields = () => {
      const mode = select.value;
      if (mode === 'Cash') {
        if (cashField) cashField.style.display = 'block';
        if (gcashField) gcashField.style.display = 'none';
        if (orInput) orInput.required = true;
        if (refInput) refInput.required = false;
      } else {
        if (cashField) cashField.style.display = 'none';
        if (gcashField) gcashField.style.display = 'block';
        if (orInput) orInput.required = false;
        if (refInput) refInput.required = true;
      }
    };

    select.addEventListener('change', syncFields);
    syncFields();
  });

  function setProcessingState(isProcessing, action) {
    const targetBtn = action === 'accept' ? acceptBtn : declineBtn;
    const otherBtn = action === 'accept' ? declineBtn : acceptBtn;

    if (!targetBtn || !otherBtn) {
      return;
    }

    targetBtn.disabled = isProcessing;
    otherBtn.disabled = isProcessing;
    targetBtn.dataset.originalText = targetBtn.dataset.originalText || targetBtn.textContent;
    otherBtn.dataset.originalText = otherBtn.dataset.originalText || otherBtn.textContent;

    if (isProcessing) {
      targetBtn.textContent = 'Processing…';
      targetBtn.classList.add('btn-loading');
      modal.classList.add('modal-processing');
    } else {
      targetBtn.textContent = targetBtn.dataset.originalText;
      otherBtn.textContent = otherBtn.dataset.originalText;
      targetBtn.classList.remove('btn-loading');
      modal.classList.remove('modal-processing');
      targetBtn.disabled = false;
      otherBtn.disabled = false;
    }
  }

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
      setProcessingState(true, 'accept');

      const payload = { id, status: "paid" };
      if (currentType === "cash") payload.or_number = document.getElementById("modalRefOr").textContent;

      try {
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
          document.getElementById("modalStatus").textContent = 'Paid';
          alert("✅ Student’s payment has been accepted.");
          modal.style.display = "none";
        } else if (data) {
          alert("Error: " + (data.error || "Unknown error"));
        }
      } catch (err) {
        alert('Error communicating with server. Please try again.');
        console.error(err);
      } finally {
        setProcessingState(false, 'accept');
      }
    });
  }

  // Decline Payment
  if (declineBtn) {
    declineBtn.addEventListener("click", async (e) => {
      e.preventDefault();
      const id = document.getElementById("modalPaymentId").value;

      setProcessingState(true, 'decline');

      try {
        const data = await postForm("update_payment_status.php", { id, status: "declined" });
        if (data && data.success) {
          const statusCell = document.getElementById("status-" + id);
          if (statusCell) statusCell.innerHTML = "<span style='color: red;'>Declined</span>";
          if (activeButton) {
            activeButton.dataset.status = 'Declined';
          }
          document.getElementById("modalStatus").textContent = 'Declined';
          alert("❌ Student’s payment has been declined.");
          modal.style.display = "none";
        } else if (data) {
          alert("Error: " + (data.error || "Unknown error"));
        }
      } catch (err) {
        alert('Error communicating with server. Please try again.');
        console.error(err);
      } finally {
        setProcessingState(false, 'decline');
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
          <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        


</body>
</html>
