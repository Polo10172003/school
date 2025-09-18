<?php
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include __DIR__ . '/../db_connection.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

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
      } else {
          $message = "Error: " . $conn->error;
      }
      $ins->close();
      }
    }
  }



// 📌 Search functionality
$search_results = [];
if (!empty($_GET['search_name'])) {
    $search_name = "%" . $conn->real_escape_string($_GET['search_name']) . "%";
    $stmt = $conn->prepare("SELECT id, student_number, firstname, lastname, year FROM students_registration WHERE lastname LIKE ? ORDER BY lastname");
    $stmt->bind_param("s", $search_name);
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
  <h2>Record Onsite Payment</h2>
  
  <!-- Search Form -->
  <form method="GET" action="cashier_dashboard.php">
      <label for="search_name">Search Student by Last Name</label>
      <input type="text" name="search_name" placeholder="Enter surname..." required>
      <button type="submit">🔍 Search</button>
  </form>

  <?php if (!empty($search_results)): ?>
    <h3>Search Results</h3>
    <ul>
        <?php foreach ($search_results as $s): ?>
            <li style="margin-bottom:20px; padding:10px; border:1px solid #ccc; border-radius:6px;">
                <strong>[<?= $s['student_number'] ?>]</strong> <?= $s['lastname'] ?>, <?= $s['firstname'] ?> (<?= $s['year'] ?>)

                <?php
                // 🔎 Check if this student has a pending enrollment payment
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
                <form method="POST" action="cashier_dashboard.php" style="display:flex; gap:5px; align-items:center;">
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

                <!-- Regular cash payment form (for 2nd, 3rd, etc. payments) -->
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
            data-or="<?= $row['or_number'] ?>"
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

  // Open Modal when "View Payment" is clicked
  document.querySelectorAll(".view-payment-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.getElementById("modalStudent").textContent = btn.dataset.student || "";
      document.getElementById("modalType").textContent = btn.dataset.type || "";
      document.getElementById("modalAmount").textContent = (parseFloat(btn.dataset.amount) || 0).toFixed(2);
      document.getElementById("modalStatus").textContent = btn.dataset.status || "";
      document.getElementById("modalPaymentId").value = btn.dataset.id || "";

      // Set screenshot (if any)
      const shot = (btn.dataset.screenshot || "").trim();
      if (shot) {
        modalScreenshot.src = shot;
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