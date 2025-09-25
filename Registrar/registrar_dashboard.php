<?php
include __DIR__ . '/../db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
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
    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }
    input[type="text"],
    textarea,
    select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
    }
    textarea {
      resize: vertical;
    }
    header {
      background-color: #004d00;
      color: white;
      text-align: center;
      padding: 20px 0;
      position: relative;
    }
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
    input[type="submit"],
    button {
      margin-top: 10px;
      background-color: #007f3f;
      color: white;
      padding: 8px 14px;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
      font-size: 13px;
    }
    input[type="submit"]:hover,
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
    .manage-button:hover {
      background-color: #004d00;
    }
    .form-section {
      margin-top: 50px;
      border-top: 2px solid #ccc;
      padding-top: 30px;
    }
  </style>
</head>
<body>

<header style="position: relative;">
  <h1>Escuela De Sto. Rosario</h1>
  <p>Registrar Dashboard</p>
  <a href="registrar_login.php" class="logout-btn">Logout</a>
</header>


<?php
$grade_filter = isset($_GET['grade_filter']) ? $_GET['grade_filter'] : '';

if ($grade_filter) {
    $stmt = $conn->prepare("
        SELECT sr.*, sa.email AS emailaddress
        FROM students_registration sr
        LEFT JOIN student_accounts sa ON sr.emailaddress = sa.email
        WHERE sr.enrollment_status = 'enrolled' AND sr.year = ?
    ");
    $stmt->bind_param("s", $grade_filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "
        SELECT sr.*, sa.email AS emailaddress
        FROM students_registration sr
        LEFT JOIN student_accounts sa ON sr.emailaddress = sa.email
        WHERE sr.enrollment_status = 'enrolled'
    ";
    $result = $conn->query($sql);
}
?>

<div class="container form-section">
  <h2>Onsite Enrollment</h2>
  <form action="onsite_enrollment.php" method="GET">
      <label for="student_type">Student Type:</label>
      <select id="student_type" name="student_type" required>
          <option value="">Select Type</option>
          <option value="new">New Student</option>
          <option value="old">Old Student</option>
      </select>

      <div id="studentNumberField" style="display:none; margin-top:10px;">
    <label for="student_number">Enter Student Number (for old students):</label>
    <input type="text" id="student_number" name="student_number" placeholder="Enter Student Number">
    </div>


      <!-- ✅ Always show payment type so choice is preserved and used downstream -->
      <div id="paymentTypeField" style="display:block; margin-top:10px;">
          <label for="payment_type">Payment Type:</label>
          <select id="payment_type" name="payment_type">
              <option value="onsite">Onsite Payment</option>
          </select>
      </div>

      <input type="submit" value="Proceed">
  </form>
</div>

<script>
  const studentType = document.getElementById('student_type');
  const studentNumberField = document.getElementById('studentNumberField');
  const paymentTypeField = document.getElementById('paymentTypeField');

  function updateVisibility() {
    if (studentType.value === 'old') {
      studentNumberField.style.display = 'block';
      document.getElementById('student_number').required = true;
    } else {
      studentNumberField.style.display = 'none';
      document.getElementById('student_number').required = false;
    }
    // ✅ keep payment type visible for both new/old
    paymentTypeField.style.display = 'block';
  }

  studentType.addEventListener('change', updateVisibility);
  // initialize on load
  updateVisibility();
</script>

<div class="container">
<h2>Registered Students</h2>

<!-- Filter by Grade -->
<form method="GET" style="margin-bottom:20px;">
    <label for="grade_filter"><b>Filter by Grade:</b></label>
    <select name="grade_filter" id="grade_filter" onchange="this.form.submit()">
        <option value="">All Grades</option>
        <?php 
        $grades = ["Preschool","Pre-Prime 1","Pre-Prime 2","Kindergarten",
                   "Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6",
                   "Grade 7","Grade 8","Grade 9","Grade 10",
                   "Grade 11","Grade 12"];
        foreach ($grades as $g) {
            $sel = ($grade_filter === $g) ? 'selected' : '';
            echo "<option value=\"$g\" $sel>$g</option>";
        }
        ?>
    </select>
</form>

<!-- Bulk promotion form -->
<form method="POST" action="bulk_promote.php">
<?php if ($result && $result->num_rows > 0): ?>
<table>
<tr>
    <th><input type="checkbox" id="checkAll"></th>
    <th>ID</th>
    <th>Name</th>
    <th>Grade Level</th>
    <th>Section</th>
    <th>Adviser</th>
    <th>Academic Status</th>
    <th>Actions</th>
</tr>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td>
        <input type="checkbox" name="student_ids[]" value="<?= $row['id'] ?>">
    </td>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
    <td><?= htmlspecialchars($row['year']) ?></td>
    <td><?= htmlspecialchars($row['section'] ?? 'Not Assigned') ?></td>
    <td><?= htmlspecialchars($row['adviser'] ?? 'Not Assigned') ?></td>
    <td>
        <?php 
        if ($row['year'] === 'Grade 12' && $row['academic_status'] === 'Passed') {
            echo '<span class="badge bg-success">Graduated</span>';
        } else {
            echo !empty($row['academic_status']) ? htmlspecialchars($row['academic_status']) : 'Ongoing';
        }
        ?>
    </td>
    <td>
    <?php if ($row['academic_status'] === 'Graduated'): ?>
        <!-- Actions for Graduated Students -->
        <a href="edit_student.php?id=<?= $row['id'] ?>">Edit</a> |
        <a href="archive_student.php?id=<?= $row['id'] ?>" onclick="return confirm('Archive this student?')">Archive</a> |
        <a href="update_section.php?id=<?= $row['id'] ?>">Change Section</a> |
        <span id="portal-status-<?= $row['id'] ?>" class="status-label" 
              style="color:<?= ($row['portal_status'] === 'activated') ? 'green' : 'red' ?>">
            <?= ($row['portal_status'] === 'activated') ? 'Activated' : 'Not Activated' ?>
        </span>
    <?php else: ?>
        <!-- Actions for Non-Graduated Students -->
        <a href="edit_student.php?id=<?= $row['id'] ?>">Edit</a> |
        <a href="delete_student.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a> |
        <a href="update_section.php?id=<?= $row['id'] ?>">Change Section</a> |
        <span id="portal-status-<?= $row['id'] ?>" class="status-label" 
              style="color:<?= ($row['portal_status'] === 'activated') ? 'green' : 'red' ?>">
            <?= ($row['portal_status'] === 'activated') ? 'Activated' : 'Not Activated' ?>
        </span> |
        <a href="update_student_status.php?id=<?= $row['id'] ?>">Update Status</a> |
        
    <?php endif; ?>
</td>

<?php endwhile; ?>
</table>


<!-- Button to promote selected students -->
<input type="submit" value="Update Selected Status" style="margin-top:15px;">
<!-- Button to activate selected accounts -->
<button type="button" id="bulkActivateBtn" style="margin-left:10px; background-color:#008000; color:white;">
  Activate Selected Accounts
</button>

</form>

<script>
// Check/uncheck all checkboxes
document.getElementById('checkAll').addEventListener('change', function() {
    const boxes = document.querySelectorAll('input[name="student_ids[]"]');
    boxes.forEach(b => b.checked = this.checked);
});
</script>
<script>
  document.getElementById('bulkActivateBtn').addEventListener('click', async () => {
    const checked = [...document.querySelectorAll('input[name="student_ids[]"]:checked')].map(cb => cb.value);
    if (checked.length === 0) {
        alert("Please select at least one student.");
        return;
    }

    const res = await fetch("bulk_activate.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ student_ids: checked })
    });

    const data = await res.json();
    if (data.success) {
        checked.forEach(id => {
            const span = document.getElementById("portal-status-" + id);
            if (span) {
              span.innerText = "Activated";
              span.style.color = "green";     
                   }
        });
        let msg = `✅ ${data.activated} accounts activated.`;
      if (data.errors && data.errors.length > 0) {
          msg += `\n⚠ Some issues:\n- ${data.errors.join("\n- ")}`;
      }
        alert(msg);
    } else {
        alert("❌ Error activating accounts."+ data.error || Unknown);
    }
});

</script>

<?php else: ?>
<p>No enrolled students found.</p>
<?php endif; ?>

<?php $conn->close(); ?>
</div>
</body>
</html>
