<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$conn = new mysqli("localhost", "root", "", "student_enrollmentform");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Insert new payment
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = $_POST["student_id"];
    $payment_type = $_POST["payment_type"];
    $amount = $_POST["amount"];
    $payment_status = $_POST["payment_status"];

// update from pending to paid on the database
$payment_date = date("Y-m-d");
$status = "paid";

// Update the existing record for that student & reference number
$sql = "UPDATE student_payments 
        SET payment_status = ?, payment_date = ? 
        WHERE student_id = ? AND payment_status = 'pending'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $status, $payment_date, $student_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "Payment status updated to PAID.";
    } else {
        echo "No pending payment found for this student.";
    }
} else {
    echo "Error: " . $stmt->error;
}


    if ($stmt->execute()) {
      // Removes the pending
      if ($payment_status === 'paid') {
        // Update enrollment_status
        $update = $conn->prepare("UPDATE students_registration SET enrollment_status = 'enrolled' WHERE id = ?");
        $update->bind_param("i", $student_id);
        $update->execute();
        $update->close();

        // Fetch student info for email
        $email_stmt = $conn->prepare("SELECT emailaddress, firstname, lastname FROM students_registration WHERE id = ?");
        $email_stmt->bind_param("i", $student_id);
        $email_stmt->execute();
        $email_stmt->bind_result($email, $firstname, $lastname);
        $email_stmt->fetch();
        $email_stmt->close();

        // Now send the email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'deadpoolvictorio@gmail.com';
            $mail->Password = 'ldcmeapjfuonxypu';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
            $mail->addAddress($email, "$firstname $lastname");

            $mail->isHTML(true);
            $mail->Subject = 'Payment Receipt and Portal Access';
            $mail->Body = "
                <h2>Payment Receipt Confirmation</h2>
                <p>Dear <strong>$firstname $lastname</strong>,</p>
                <p>We have received your payment for the following:</p>
                <ul>
                    <li><strong>Payment Type:</strong> $payment_type</li>
                    <li><strong>Amount:</strong> ₱" . number_format($amount, 2) . "</li>
                    <li><strong>Status:</strong> $payment_status</li>
                </ul>
                <p>Your enrollment is now marked as <strong>ENROLLED</strong>.</p>
                <hr>
                <p><strong>IMPORTANT:</strong> Please wait for the activation of your student portal with your email address: <strong>$email</strong>.</p>
                <br>
                <p>Best regards,<br>Escuela De Sto. Rosario</p>
            ";
            $mail->send();
            $message = "Payment recorded, enrollment updated, and email sent successfully.";
        } catch (Exception $e) {
            $message = "Payment recorded, but email failed: {$mail->ErrorInfo}";
        }
    } else {
        $message = "Error: " . $stmt->error;
    }
}
}

// Fetch students
$students = $conn->query("SELECT id, firstname, lastname FROM students_registration");

// Fetch payment types
$types_result = $conn->query("SELECT DISTINCT payment_type FROM student_payments");
$types = [];
while ($row = $types_result->fetch_assoc()) {
    $types[] = $row['payment_type'];
}

// Handle filters
$filter_sql = "WHERE 1=1";

if (!empty($_GET['filter_student_id'])) {
    $filter_student_id = intval($_GET['filter_student_id']);
    $filter_sql .= " AND sp.student_id = $filter_student_id";
}

if (!empty($_GET['filter_type'])) {
    $filter_type = $conn->real_escape_string($_GET['filter_type']);
    $filter_sql .= " AND sp.payment_type = '$filter_type'";
}

if (!empty($_GET['filter_status'])) {
    $filter_status = $conn->real_escape_string($_GET['filter_status']);
    $filter_sql .= " AND sp.payment_status = '$filter_status'";
}

if (!empty($_GET['filter_grade_level'])) {
  $filter_grade_level = $conn->real_escape_string($_GET['filter_grade_level']);
  // Change from grade_level to year
  $filter_sql .= " AND sr.year = '$filter_grade_level'";
}


// Fetch payment records
$payments = $conn->query("
    SELECT sp.*, sr.firstname, sr.lastname
    FROM student_payments sp
    JOIN students_registration sr ON sr.id = sp.student_id  
    $filter_sql
    ORDER BY sp.created_at DESC
");

// Calculate total amount
$total_query = $conn->query("
    SELECT SUM(sp.amount) AS total
    FROM student_payments sp
    JOIN students_registration sr ON sr.id = sp.student_id
    $filter_sql
");
$total_row = $total_query->fetch_assoc();
$total_amount = $total_row['total'] ?? 0;
?>


<!DOCTYPE html>
<html lang="en">
<head>
<script src="scroll-position.js"></script>

  <meta charset="UTF-8">
  <title>CASHIER DASHBOARD</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f0f4f3;
      margin: 0;
      padding: 0;
      color: #333;
    }

  

    .container {
      width: 90%;
      max-width: 1000px;
      margin: 30px auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.08);
    }

    h2 {
      color: #007f3f;
      border-bottom: 2px solid #007f3f;
      padding-bottom: 10px;
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

    form {
      margin-top: 30px;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }

    select, input[type="text"], input[type="number"] {
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

    .manage-button {
      display: inline-block;
      background-color: #007f3f;
      color: white;
      padding: 12px 24px;
      margin-bottom: 20px;
      text-decoration: none;
      font-size: 16px;
      font-weight: bold;
      border-radius: 8px;
      transition: background-color 0.3s ease, transform 0.2s ease;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    header {
    background-color: #004d00;
    color: white;
    text-align: center;
    padding: 20px 0;
    position: relative; /* <-- Add this line */
  }

  /* Logout button styling */
  .logout-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    background-color: #a30000;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease;
    text-decoration: none;
    font-size: 14px;
  }
  .logout-btn:hover {
    background-color: #7a0000;
  }


    .success {
      background-color: #e0ffe0;
      padding: 10px;
      margin-top: 10px;
      border: 1px solid green;
    }
  </style>
</head>
<body>

<header style="position: relative;">
  <h1>Escuela De Sto. Rosario</h1>
  <h2>Cashier Dashboard</h2>
  <a href="admin_manage_fee.php" class="manage-button">Manage Fees</a>
  <a href="cashier_login.php" class="logout-btn">Logout</a>
</header>


<div class="container">
  <h2>Student Payments</h2>
  

  <?php if (isset($message)): ?>
    <div class="success"><?= $message ?></div>
  <?php endif; ?>

  <!-- Payment Form -->
  <form method="POST">
    <label>Student</label>
    <select name="student_id" required>
      <?php
      $students->data_seek(0);
      while ($s = $students->fetch_assoc()):
          echo "<option value='{$s['id']}'>{$s['lastname']}, {$s['firstname']}</option>";
      endwhile;
      ?>
    </select>

    <label>Payment Type</label>
    <select name="payment_type" required>
      <option value="">Select Type</option>
      <option value="Enrollment Fee">Enrollment Fee</option>
      <option value="Miscellaneous Fee">Miscellaneous Fee</option>
      <option value="Tuition Fee">Tuition Fee</option>
      <option value="Monthly Payment">Monthly Payment</option>
      <option value="Other">Other</option>
    </select>

    <label>Amount</label>
    <input type="number" name="amount" step="0.01" required>

    <label>Status</label>
    <select name="payment_status" required>
      <option value="paid">Paid</option>
      <option value="pending">Pending</option>
    </select>

    <button type="submit">Add Payment</button>
  </form>

  <!-- Filter Section --> 
  <h2>Filter Payments</h2>
  <form method="GET" action="cashier_dashboard.php#filterForm" id="filterForm">
  <label>Grade Level</label>
  <select name="filter_grade_level">
  <option value="">All</option>
  <?php
    // Fetch distinct years for the filter dropdown
  $grade_levels_result = $conn->query("SELECT DISTINCT year FROM students_registration ORDER BY year");
  while ($gl = $grade_levels_result->fetch_assoc()):
      $selected = ($_GET['filter_grade_level'] ?? '') == $gl['year'] ? "selected" : "";
      echo "<option value='{$gl['year']}' $selected>{$gl['year']}</option>";
  endwhile;

  ?>
</select>



    <label>Type</label>
    <select name="filter_type">
      <option value="">All</option>
      <?php foreach ($types as $type): ?>
        <option value="<?= $type ?>" <?= ($_GET['filter_type'] ?? '') == $type ? "selected" : "" ?>>
          <?= ucfirst(str_replace("_", " ", $type)) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Status</label>
    <select name="filter_status">
      <option value="">All</option>
      <option value="paid" <?= ($_GET['filter_status'] ?? '') == "paid" ? "selected" : "" ?>>Paid</option>
      <option value="pending" <?= ($_GET['filter_status'] ?? '') == "pending" ? "selected" : "" ?>>Pending</option>
    </select>

    <button type="submit">Apply Filter</button>
    <a href="cashier_dashboard.php" class="manage-button" style="background-color: gray;">Reset</a>
  </form>

  <!-- Payment Table -->
  <h2>Payment Records</h2>
  <table>
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
        <tr>
          <td><?= date("Y-m-d", strtotime($row['created_at'])) ?></td>
          <td><?= $row['lastname'] ?>, <?= $row['firstname'] ?></td>
          <td><?= $row['payment_type'] ?></td>
          <td>₱ <?= number_format($row['amount'], 2) ?></td>
          <td><?= ucfirst($row['payment_status']) ?></td>
          <td><?= $row['created_at'] ?></td>
          <td>
            <button 
                class="view-payment-btn" 
                data-id="<?= $row['id'] ?>" 
                data-student="<?= $row['lastname'] ?>, <?= $row['firstname'] ?>"
                data-type="<?= $row['payment_type'] ?>"
                data-amount="<?= $row['amount'] ?>"
                data-status="<?= ucfirst($row['payment_status']) ?>"
                data-reference="<?= $row['reference_number'] ?>"
                data-screenshot="<?= $row['screenshot_path'] ?>"
              >
                View Payment
            </button>
          </td>
          <!-- Payment Modal -->
          <div id="paymentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
          background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center;">
            <div style="background:#fff; padding:20px; border-radius:10px; width:500px; position:relative;">
              <span id="closeModal" style="position:absolute; top:10px; right:15px; cursor:pointer; font-weight:bold;">&times;</span>
              <h3>Payment Details</h3>
              <p><strong>Student:</strong> <span id="modalStudent"></span></p>
              <p><strong>Type:</strong> <span id="modalType"></span></p>
              <p><strong>Amount:</strong> ₱<span id="modalAmount"></span></p>
              <p><strong>Status:</strong> <span id="modalStatus"></span></p>
              <p><strong>Reference #:</strong> <span id="modalReference"></span></p>
              <p><strong>Screenshot:</strong></p>
              <img id="modalScreenshot" src="" alt="Payment Screenshot" style="max-width:100%; border:1px solid #ccc; border-radius:6px;">
            </div>
          </div>


        </tr>

      <?php endwhile; ?>
    </tbody>
    
  </table>
</div>
          <script>
          document.querySelectorAll('.view-payment-btn').forEach(btn => {
            btn.addEventListener('click', () => {
              document.getElementById('modalStudent').textContent = btn.dataset.student;
              document.getElementById('modalType').textContent = btn.dataset.type;
              document.getElementById('modalAmount').textContent = parseFloat(btn.dataset.amount).toFixed(2);
              document.getElementById('modalStatus').textContent = btn.dataset.status;
              document.getElementById('modalReference').textContent = btn.dataset.reference;
              document.getElementById('modalScreenshot').src = btn.dataset.screenshot;

              document.getElementById('paymentModal').style.display = 'flex';
            });
          });

          document.getElementById('closeModal').addEventListener('click', () => {
            document.getElementById('paymentModal').style.display = 'none';
          });

          window.onclick = function(event) {
            if (event.target == document.getElementById('paymentModal')) {
              document.getElementById('paymentModal').style.display = 'none';
            }
          };
          </script>

</body>
</html>