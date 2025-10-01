<?php
include __DIR__ . '/../db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registrar Dashboard</title>
  <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="dashboard-body">
<button class="dashboard-toggle" type="button" aria-label="Toggle navigation" onclick="document.querySelector('.dashboard-sidebar').classList.toggle('is-open');">☰</button>
<div class="dashboard-shell">
  <aside class="dashboard-sidebar">
    <div class="dashboard-brand">
      <img src="../Esrlogo.png" alt="ESR Logo">
      <span>Registrar Portal</span>
    </div>
    <nav class="dashboard-nav">
      <a href="#onsite-enrollment">Onsite Enrollment</a>
      <a href="#students">Registered Students</a>
    </nav>
    <a href="registrar_login.php" class="dashboard-logout">Logout</a>
  </aside>

  <main class="dashboard-main">
    <header class="dashboard-header">
      <div>
        <h1>Registrar Dashboard</h1>
        <p>Track enrollment records, promote students in batches, and onboard families who register onsite.</p>
      </div>
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

    <section class="dashboard-card" id="onsite-enrollment">
      <span class="dashboard-section-title">Onsite Assistance</span>
      <h2>Onsite Enrollment</h2>
      <p class="text-muted">Guide walk-in parents or guardians through registration and payment capture.</p>
      <form class="dashboard-form" action="onsite_enrollment.php" method="GET">
        <label for="student_type">Student Type</label>
        <select id="student_type" name="student_type" required>
          <option value="">Select Type</option>
          <option value="new">New Student</option>
          <option value="old">Old Student</option>
        </select>

        <div id="studentNumberField" style="display:none; margin-top:10px;">
          <label for="student_number">Student Number (for returning students)</label>
          <input type="text" id="student_number" name="student_number" placeholder="e.g. ESR-2026-0001">
        </div>

        <div id="paymentTypeField" style="display:block; margin-top:10px;">
          <label for="payment_type">Payment Type</label>
          <select id="payment_type" name="payment_type">
            <option value="onsite">Onsite Payment</option>
          </select>
        </div>

        <div class="dashboard-actions">
          <button type="submit" class="dashboard-btn">Proceed</button>
        </div>
      </form>
    </section>

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
    <section class="dashboard-card" id="students">
      <span class="dashboard-section-title">Student Registry</span>
      <h2>Enrolled Students</h2>

      <form class="dashboard-form" method="GET" style="margin-bottom:12px;">
        <label for="grade_filter">Filter by Grade</label>
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

      <form class="dashboard-form" method="POST" action="bulk_promote.php">
        <?php if ($result && $result->num_rows > 0): ?>
          <div class="table-responsive">
            <table>
              <thead>
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
              </thead>
              <tbody>
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
                          echo '<span class="dashboard-status-pill success">Graduated</span>';
                      } else {
                          echo !empty($row['academic_status']) ? htmlspecialchars($row['academic_status']) : 'Ongoing';
                      }
                      ?>
                    </td>
                    <td class="dashboard-table-actions">
                      <?php if ($row['academic_status'] === 'Graduated'): ?>
                        <a href="edit_student.php?id=<?= $row['id'] ?>">Edit</a>
                        <a href="archive_student.php?id=<?= $row['id'] ?>" onclick="return confirm('Archive this student?')">Archive</a>
                        <a href="update_section.php?id=<?= $row['id'] ?>">Change Section</a>
                      <?php else: ?>
                        <a href="edit_student.php?id=<?= $row['id'] ?>">Edit</a>
                        <a href="delete_student.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                        <a href="update_section.php?id=<?= $row['id'] ?>">Change Section</a>
                        <a href="update_student_status.php?id=<?= $row['id'] ?>">Update Status</a>
                      <?php endif; ?>
                      <span id="portal-status-<?= $row['id'] ?>" class="dashboard-status-pill <?= ($row['portal_status'] === 'activated') ? 'success' : 'pending' ?>">
                        <?= ($row['portal_status'] === 'activated') ? 'Activated' : 'Not Activated' ?>
                      </span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>

          <div class="dashboard-actions">
            <button type="submit" class="dashboard-btn">Update Selected Status</button>
            <button type="button" id="bulkActivateBtn" class="dashboard-btn secondary">Activate Selected Accounts</button>
          </div>
        <?php else: ?>
          <div class="dashboard-empty-state">No enrolled students found.</div>
        <?php endif; ?>
      </form>
    </section>

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
              span.classList.remove('pending');
              span.classList.add('success');
            }
        });
        let msg = `✅ ${data.activated} accounts activated.`;
      if (data.errors && data.errors.length > 0) {
          msg += `\n⚠ Some issues:\n- ${data.errors.join("\n- ")}`;
      }
        alert(msg);
    } else {
        alert("❌ Error activating accounts." + (data.error || ' Unknown error.'));
    }
});

</script>
<?php $conn->close(); ?>
  </main>
</div>

</body>
</html>
