<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db_connection.php';
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
  

  <?php if (isset($message)): ?>
    <div class="success"><?= $message ?></div>
  <?php endif; ?>

 

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
      </tr>
    <?php endwhile; ?>
</tbody>


</table>
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
    <p><strong>Reference #:</strong> <span id="modalReference"></span></p>

    <!-- Screenshot (online only) -->
    <div id="screenshotSection" style="text-align:center; margin-bottom:20px;">
      <img id="modalScreenshot" src="" alt="Payment Screenshot" 
          style="max-width:100%; height:auto; border:1px solid #ccc; border-radius:6px;">
    </div>

    <!-- Hidden input -->
    <input type="hidden" id="modalPaymentId">

    <!-- OR Number field for Cash payments -->
    <div id="cashPaymentForm" style="display:none; margin-top:15px;">
      <label for="orNumber"><strong>Official Receipt #:</strong></label>
      <input type="text" id="orNumber" placeholder="Enter OR Number">
    </div>

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

  // Open Modal when "View Payment" is clicked
  document.querySelectorAll(".view-payment-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.getElementById("modalStudent").textContent = btn.dataset.student || "";
      document.getElementById("modalType").textContent = btn.dataset.type || "";
      document.getElementById("modalAmount").textContent = (parseFloat(btn.dataset.amount) || 0).toFixed(2);
      document.getElementById("modalStatus").textContent = btn.dataset.status || "";
      document.getElementById("modalReference").textContent = btn.dataset.reference || "";
      document.getElementById("modalPaymentId").value = btn.dataset.id || "";

      // Set screenshot (if any)
      const shot = (btn.dataset.screenshot || "").trim();
      if (shot) {
        modalScreenshot.src = shot;
        screenshotSection.style.display = "block";
      } else {
        screenshotSection.style.display = "none";
      }

      // Determine type & toggle OR (Cash only)
      currentType = (btn.dataset.type || "").toLowerCase();
      cashForm.style.display = currentType === "cash" ? "block" : "none";

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
      const orNumberInput = document.getElementById("orNumber");
      const orNumber = currentType === "cash" && orNumberInput ? orNumberInput.value.trim() : "";

      const payload = { id, status: "paid" };
      if (currentType === "cash") payload.or_number = orNumber;

      const data = await postForm("update_payment_status.php", payload);
      if (data && data.success) {
        const statusCell = document.getElementById("status-" + id);
        if (statusCell) {
          statusCell.innerHTML =
            "<span style='color: green;'>Paid" + (currentType === "cash" ? " (Cash)" : "") + "</span>";
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
        alert("❌ Student’s payment has been declined.");
        modal.style.display = "none";
      } else if (data) {
        alert("Error: " + (data.error || "Unknown error"));
      }
    });
  }
});
</script>




          <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        


</body>
</html>