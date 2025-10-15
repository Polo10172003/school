<?php
require_once __DIR__ . '/../includes/session.php';
include __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../admin_functions.php';
require_once __DIR__ . '/../includes/registrar_guides.php';
$pusherConfig = require __DIR__ . '/../config/pusher.php';
$pusherClientConfig = [
    'key' => $pusherConfig['key'] ?? '',
    'cluster' => $pusherConfig['cluster'] ?? '',
    'channel' => $pusherConfig['registrar_channel'] ?? 'registrar-enrollments',
    'event' => $pusherConfig['registrar_event'] ?? 'student-enrolled',
    'forceTLS' => array_key_exists('use_tls', $pusherConfig) ? (bool) $pusherConfig['use_tls'] : true,
];

if (!function_exists('registrar_format_bytes')) {
    /**
     * Convert file size in bytes to a human-readable string.
     */
    function registrar_format_bytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / (1024 ** $power);
        $precision = $power === 0 ? 0 : 2;

        return number_format($value, $precision) . ' ' . $units[$power];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registrar Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
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
      <a href="#grade-dropbox">Grade Dropbox</a>
      <a href="#students">Registered Students</a>
    </nav>
    <a href="registrar_login.php" class="dashboard-logout">Logout</a>
  </aside>

  <main class="dashboard-main">
    <?php
      $registrarDisplayName = $_SESSION['registrar_fullname'] ?? ($_SESSION['registrar_username'] ?? 'Registrar');
      $registrarRoleLabel = ucwords($_SESSION['registrar_role'] ?? 'registrar');
      $registrarUsername = $_SESSION['registrar_username'] ?? null;
      if ($registrarUsername) {
        $registrarRow = dashboard_fetch_user($conn, $registrarUsername);
      if ($registrarRow) {
        if (!empty($registrarRow['fullname'])) {
          $_SESSION['registrar_fullname'] = $registrarRow['fullname'];
          $registrarDisplayName = $registrarRow['fullname'];
        }
        if (!empty($registrarRow['role'])) {
          $roleKey = strtolower($registrarRow['role']);
          $roleMap = [
            'registrar' => 'Registrar',
            'cashier' => 'Cashier',
            'admin' => 'Administrator',
          ];
          $registrarRoleLabel = $roleMap[$roleKey] ?? ucwords($registrarRow['role']);
        }
      }
    }
    ?>
    <header class="dashboard-header">
      <div>
        <h1>Registrar Dashboard</h1>
        <p>Track enrollment records, promote students in batches, and onboard families who register onsite.</p>
      </div>
      <div class="dashboard-user-chip" title="Logged in as <?= htmlspecialchars($registrarDisplayName); ?>">
        <span class="chip-label">Logged in as</span>
        <span class="chip-name"><?= htmlspecialchars($registrarDisplayName); ?></span>
        <span class="chip-divider">•</span>
        <span class="chip-role"><?= htmlspecialchars($registrarRoleLabel); ?></span>
      </div>
    </header>

    <?php
    $flashMsg = $_GET['msg'] ?? '';
    $flashReason = $_GET['reason'] ?? '';
    $flashText = '';
    $flashType = 'info';
    switch ($flashMsg) {
        case 'student_added_pending_cash':
            $flashText = 'Student marked for onsite payment. Please coordinate with the cashier to complete enrollment.';
            $flashType = 'success';
            break;
        case 'no_payment_option_selected':
            $flashText = 'Select a payment option when processing onsite enrollment for returning students.';
            $flashType = 'warning';
            break;
        case 'payment_record_error':
            $flashText = 'Unable to prepare the onsite payment record. Please retry or contact the administrator.';
            $flashType = 'danger';
            break;
        case 'student_updated':
            $flashText = 'Student details were updated successfully.';
            $flashType = 'success';
            break;
        case 'student_missing':
            $flashText = 'Unable to locate that student record. Please refresh the list and try again.';
            $flashType = 'danger';
            break;
        case 'old_student_not_found':
            $flashText = 'No record matched that student number. Please double-check and try again.';
            $flashType = 'danger';
            break;
        case 'update_status_required':
            $flashText = 'Please run Update Student Status for this learner before processing onsite enrollment.';
            $flashType = 'warning';
            break;
        case 'guide_upload_disabled':
            $flashText = 'Advisers now handle grade uploads. Please coordinate with them for any updates.';
            $flashType = 'info';
            break;
        case 'guide_delete_disabled':
            $flashText = 'Registrars can no longer remove adviser uploads. Contact the original uploader if a revision is needed.';
            $flashType = 'info';
            break;
    }
    if ($flashText !== ''): ?>
        <div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flashText) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
<?php endif; ?>


<?php
$gradeLevels = [
    "Preschool","Pre-Prime 1","Pre-Prime 2","Kindergarten",
    "Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6",
    "Grade 7","Grade 8","Grade 9","Grade 10",
    "Grade 11","Grade 12"
];

$grade_filter = isset($_GET['grade_filter']) ? $_GET['grade_filter'] : '';

$guideError = false;
$guideItems = [];
try {
    registrar_guides_ensure_schema($conn);
    $guideItems = registrar_guides_fetch_all($conn, $grade_filter !== '' ? $grade_filter : null);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $guideItems = [];
    $guideError = true;
}

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
    $stmt->close();
} else {
    $sql = "
        SELECT sr.*, sa.email AS emailaddress
        FROM students_registration sr
        LEFT JOIN student_accounts sa ON sr.emailaddress = sa.email
        WHERE sr.enrollment_status = 'enrolled'
    ";
    $result = $conn->query($sql);
}

$enrolledStudents = [];
if ($result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $enrolledStudents[] = $row;
    }
    $result->free();
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
    <section class="dashboard-card" id="grade-dropbox">
      <span class="dashboard-section-title">Grade Dropbox</span>
      <h2>Academic Performance Dropbox</h2>
      <p class="text-muted">Workbooks uploaded by advisers appear below. Need updates? Ask the assigned adviser to submit a new file through their portal.</p>
      <div class="grade-dropbox-callout">
      </div>

      <form class="dashboard-form" method="GET" style="margin-top:20px;">
        <label for="dropbox_grade_filter">Filter by Grade</label>
        <select id="dropbox_grade_filter" name="grade_filter" style="max-width:280px; margin-top:10px;">
          <option value="">All Grades</option>
          <?php foreach ($gradeLevels as $level): ?>
            <option value="<?= htmlspecialchars($level) ?>" <?= ($grade_filter === $level) ? 'selected' : '' ?>>
              <?= htmlspecialchars($level) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>

      <?php if ($guideError): ?>
        <div class="dashboard-empty-state">Unable to load the dropbox items right now. Refresh the page or try again later.</div>
      <?php elseif (!empty($guideItems)): ?>
        <div class="table-responsive" style="margin-top:18px;">
          <table>
            <thead>
              <tr>
                <th>Grade</th>
                <th>Workbook</th>
                <th>Uploaded</th>
                <th>Contributor</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($guideItems as $guide): ?>
                <?php
                  $uploadedAt = $guide['uploaded_at'] ? date('M j, Y g:i A', strtotime($guide['uploaded_at'])) : '—';
                  $rawContributor = !empty($guide['uploaded_by']) ? $guide['uploaded_by'] : 'Adviser';
                ?>
                <tr>
                  <td><?= htmlspecialchars($guide['grade_level']) ?></td>
                  <td>
                    <?= htmlspecialchars($guide['original_name']) ?>
                    <span class="text-muted">· <?= htmlspecialchars(registrar_format_bytes((int) $guide['file_size'])) ?></span>
                  </td>
                  <td><?= htmlspecialchars($uploadedAt) ?></td>
                  <td><?= htmlspecialchars($rawContributor) ?></td>
                  <td class="dashboard-table-actions">
                    <?php
                      $fileUrl = '../' . registrar_guides_public_path($guide['file_name']);
                      $previewUrl = 'preview_guide.php?id=' . urlencode((string) $guide['id']);
                    ?>
                    <a href="<?= htmlspecialchars($previewUrl) ?>" target="_blank" rel="noopener noreferrer">Preview</a>
                    <a href="<?= htmlspecialchars($fileUrl) ?>" download="<?= htmlspecialchars($guide['original_name']) ?>">Download</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="dashboard-empty-state">No grade workbooks uploaded yet for the selected grade.</div>
      <?php endif; ?>
    </section>
    <section class="dashboard-card" id="students">
      <span class="dashboard-section-title">Student Registry</span>
      <h2>Enrolled Students</h2>

      <form class="dashboard-form" method="GET" style="margin-bottom:12px;">
        <label for="grade_filter">Filter by Grade</label>
        <select name="grade_filter" id="grade_filter">
          <option value="">All Grades</option>
          <?php foreach ($gradeLevels as $g): ?>
            <option value="<?= htmlspecialchars($g) ?>" <?= ($grade_filter === $g) ? 'selected' : '' ?>>
              <?= htmlspecialchars($g) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>

      <form class="dashboard-form" method="POST" action="bulk_promote.php">
        <div class="table-responsive" id="enrolledTableWrapper" style="<?= empty($enrolledStudents) ? 'display:none;' : '' ?>">
          <table id="enrolledStudentsTable">
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
            <tbody id="enrolledTableBody">
              <?php foreach ($enrolledStudents as $row): ?>
                <tr data-student-row="<?= (int) $row['id'] ?>">
                  <td>
                    <input type="checkbox" name="student_ids[]" value="<?= (int) $row['id'] ?>">
                  </td>
                  <td><?= (int) $row['id'] ?></td>
                  <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                  <td><?= htmlspecialchars($row['year']) ?></td>
                  <td><?= htmlspecialchars($row['section'] ?? 'Not Assigned') ?></td>
                  <td><?= htmlspecialchars($row['adviser'] ?? 'Not Assigned') ?></td>
                  <td>
                    <?php
                    if (($row['year'] ?? '') === 'Grade 12' && ($row['academic_status'] ?? '') === 'Passed') {
                        echo '<span class="dashboard-status-pill success">Graduated</span>';
                    } else {
                        $statusLabel = !empty($row['academic_status']) ? htmlspecialchars($row['academic_status']) : 'Ongoing';
                        echo $statusLabel;
                    }
                    ?>
                  </td>
                  <td class="dashboard-table-actions">
                    <?php if (($row['academic_status'] ?? '') === 'Graduated'): ?>
                      <a href="edit_student.php?id=<?= (int) $row['id'] ?>">Edit</a>
                      <a href="archive_student.php?id=<?= (int) $row['id'] ?>" onclick="return confirm('Archive this student?')">Archive</a>
                      <a href="update_section.php?id=<?= (int) $row['id'] ?>">Change Section</a>
                    <?php else: ?>
                      <a href="edit_student.php?id=<?= (int) $row['id'] ?>">Edit</a>
                      <a href="delete_student.php?id=<?= (int) $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                      <a href="update_section.php?id=<?= (int) $row['id'] ?>">Change Section</a>
                      <a href="update_student_status.php?id=<?= (int) $row['id'] ?>">Update Status</a>
                    <?php endif; ?>
                    <span id="portal-status-<?= (int) $row['id'] ?>" class="dashboard-status-pill <?= ($row['portal_status'] === 'activated') ? 'success' : 'pending' ?>">
                      <?= ($row['portal_status'] === 'activated') ? 'Activated' : 'Not Activated' ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="dashboard-actions" id="enrolledActions" style="<?= empty($enrolledStudents) ? 'display:none;' : '' ?>">
          <button type="submit" class="dashboard-btn">Update Selected Status</button>
          <button type="button" id="bulkActivateBtn" class="dashboard-btn secondary">Activate Selected Accounts</button>
        </div>

        <div class="dashboard-empty-state" id="enrolledEmptyState" style="<?= empty($enrolledStudents) ? '' : 'display:none;' ?>">No enrolled students found.</div>
      </form>
    </section>

<script>
  (function () {
    function setupGradeFilter(selectId, anchorId) {
      const select = document.getElementById(selectId);
      if (!select) {
        return;
      }

      const anchor = anchorId && anchorId.charAt(0) === '#' ? anchorId : '#' + anchorId;
      select.addEventListener('change', function (event) {
        event.preventDefault();

        const url = new URL(window.location.href);
        const paramName = select.name || 'grade_filter';
        const params = new URLSearchParams(url.search);

        if (select.value) {
          params.set(paramName, select.value);
        } else {
          params.delete(paramName);
        }

        const nextSearch = params.toString();
        url.search = nextSearch;
        url.hash = anchor;

        window.location.assign(url.toString());
      });
    }

    document.addEventListener('DOMContentLoaded', function () {
      setupGradeFilter('dropbox_grade_filter', 'grade-dropbox');
      setupGradeFilter('grade_filter', 'students');

      if (window.location.hash) {
        const target = document.querySelector(window.location.hash);
        if (target && typeof target.scrollIntoView === 'function') {
          target.scrollIntoView({ behavior: 'auto', block: 'start' });
        }
      }
    });
  })();
</script>

<script>
// Check/uncheck all checkboxes
const masterCheckbox = document.getElementById('checkAll');
const studentCheckboxes = () => document.querySelectorAll('input[name="student_ids[]"]');

function clearSelections() {
    if (masterCheckbox) {
        masterCheckbox.checked = false;
    }
    studentCheckboxes().forEach(cb => cb.checked = false);
}

if (masterCheckbox) {
    masterCheckbox.addEventListener('change', function() {
        studentCheckboxes().forEach(b => b.checked = this.checked);
    });
}
</script>
<script>
  document.getElementById('bulkActivateBtn').addEventListener('click', async () => {
    const checkedBoxes = [...studentCheckboxes()].filter(cb => cb.checked);
    const checked = checkedBoxes.map(cb => cb.value);
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
        clearSelections();
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
<?php
$pusherClientJson = json_encode($pusherClientConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<script>
  window.PUSHER_CONFIG = <?= $pusherClientJson !== false ? $pusherClientJson : 'null'; ?>;
</script>
<script src="https://js.pusher.com/8.4/pusher.min.js"></script>
<script src="registrar_dashboard.js?v=20241017"></script>
<?php $conn->close(); ?>
  </main>
</div>

</body>
</html>
