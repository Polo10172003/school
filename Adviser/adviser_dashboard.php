<?php
require_once __DIR__ . '/../includes/session.php';

if (empty($_SESSION['adviser_username'])) {
    header('Location: adviser_login.php');
    exit;
}

require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../admin_functions.php';
require_once __DIR__ . '/../includes/registrar_guides.php';

if (!function_exists('registrar_format_bytes')) {
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

$adviserDisplayName = $_SESSION['adviser_fullname'] ?? $_SESSION['adviser_username'];

$flashMsg = $_GET['msg'] ?? '';
$flashReason = $_GET['reason'] ?? '';
$flashText = '';
$flashType = 'info';

switch ($flashMsg) {
    case 'guide_uploaded':
        $flashText = 'Workbook uploaded successfully.';
        $flashType = 'success';
        break;
    case 'guide_deleted':
        $flashText = 'The workbook has been removed.';
        $flashType = 'success';
        break;
    case 'guide_upload_error':
        $uploadReasons = [
            'invalid_grade'  => 'Select a grade level before uploading a workbook.',
            'missing_file'   => 'Choose an Excel workbook to upload.',
            'upload_failure' => 'The upload was interrupted. Please try again.',
            'invalid_type'   => 'Only Excel workbooks (.xls, .xlsx) are accepted.',
            'empty_file'     => 'The uploaded file appears to be empty.',
            'move_failed'    => 'Unable to store the workbook on the server. Try again or contact IT.',
            'database_error' => 'Unable to record the workbook. Please retry.',
            'storage_issue'  => 'Storage for advisor uploads is unavailable. Contact the administrator.',
        ];
        $flashText = $uploadReasons[$flashReason] ?? 'Unable to upload the workbook.';
        $flashType = 'danger';
        break;
    case 'guide_delete_error':
        $deleteReasons = [
            'invalid_id'    => 'Select a valid workbook before removing it.',
            'database_error'=> 'Unable to update the dropbox. Please retry.',
            'not_found'     => 'That workbook was already removed or cannot be located.',
            'storage_issue' => 'Unable to remove the workbook from storage. Contact the administrator.',
        ];
        $flashText = $deleteReasons[$flashReason] ?? 'Unable to remove the workbook.';
        $flashType = 'danger';
        break;
}

$gradeLevels = [
    "Preschool","Pre-Prime 1","Pre-Prime 2","Kindergarten",
    "Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6",
    "Grade 7","Grade 8","Grade 9","Grade 10",
    "Grade 11","Grade 12"
];

$selectedGrade = isset($_GET['grade_filter']) && $_GET['grade_filter'] !== '' ? $_GET['grade_filter'] : null;
$guideError = false;
$guideItems = [];

try {
    registrar_guides_ensure_schema($conn);
    $guideItems = registrar_guides_fetch_all($conn, $selectedGrade);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $guideItems = [];
    $guideError = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adviser Dashboard</title>
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
      <span>Adviser Portal</span>
    </div>
    <nav class="dashboard-nav">
      <a href="#dropbox" class="active">Grade Dropbox</a>
    </nav>
    <a href="adviser_logout.php" class="dashboard-logout">Logout</a>
  </aside>

  <main class="dashboard-main">
    <header class="dashboard-header">
      <div class="dashboard-heading">
        <h1>Academic Dropbox</h1>
        <p class="text-muted">Upload grade workbooks to keep the registrar up-to-date.</p>
      </div>
      <div class="dashboard-user-chip" title="Logged in as <?= htmlspecialchars($adviserDisplayName); ?>">
        <span class="chip-label">Logged in as</span>
        <span class="chip-name"><?= htmlspecialchars($adviserDisplayName); ?></span>
        <span class="chip-divider">•</span>
        <span class="chip-role">Adviser</span>
      </div>
    </header>

    <?php if ($flashText !== ''): ?>
      <div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flashText) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <section class="dashboard-card" id="dropbox">
      <span class="dashboard-section-title">Grade Dropbox</span>
      <h2>Academic Performance Dropbox</h2>
      <p class="text-muted">Share Excel workbooks for registrar review. Files stay tied to the selected grade level.</p>

      <form class="dashboard-form grade-dropbox-form" action="upload_guide.php" method="POST" enctype="multipart/form-data">
        <div class="grade-dropbox-grid">
          <div class="grade-dropbox-fields">
            <div>
              <label for="dropbox_grade_level">Grade or Year Level</label>
              <select id="dropbox_grade_level" name="grade_level" required>
                <option value="">Select Grade</option>
                <?php foreach ($gradeLevels as $level): ?>
                  <option value="<?= htmlspecialchars($level) ?>" <?= ($selectedGrade === $level) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($level) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <p class="text-muted dropzone-note">Drag &amp; drop a workbook or use the browse button. Only .xls and .xlsx files are accepted.</p>
            <div class="grade-dropbox-actions">
              <button type="submit" class="dashboard-btn">Upload Workbook</button>
            </div>
          </div>

          <div class="grade-dropbox-upload">
            <input type="file" name="guide_file" id="guide_file" accept=".xls,.xlsx" hidden required>
            <div class="dashboard-dropzone" id="guide-dropzone">
              <div class="dropzone-inner">
                <div class="dropzone-icon" aria-hidden="true">
                  <svg width="46" height="46" viewBox="0 0 24 24" role="presentation">
                    <path fill="currentColor" d="M12 3a1 1 0 0 1 .8.4l4 5a1 1 0 1 1-1.6 1.2L13 6.75V16a1 1 0 0 1-2 0V6.75L8.8 9.6a1 1 0 1 1-1.6-1.2l4-5A1 1 0 0 1 12 3Zm-7 13a1 1 0 0 1 1 1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-1a1 1 0 1 1 2 0v1a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-1a1 1 0 0 1 1-1Z"/>
                  </svg>
                </div>
                <div class="dropzone-copy">
                  <span class="dropzone-title">Drop Excel workbook here</span>
                  <span class="dropzone-subtitle">Drag &amp; drop a file into this area (supports .xls, .xlsx)</span>
                </div>
                <button type="button" class="dropzone-browse" id="guide-browse-btn">Browse files</button>
              </div>
              <p class="dropzone-selected" id="guide-selected" aria-live="polite"></p>
            </div>
          </div>
        </div>
      </form>

      <form class="dashboard-form" method="GET" style="margin-top:24px;">
        <label for="grade_filter">Filter uploaded workbooks by grade</label>
        <select id="grade_filter" name="grade_filter" onchange="this.form.submit()" style="max-width:280px; margin-top:10px;">
          <option value="">All Grades</option>
          <?php foreach ($gradeLevels as $level): ?>
            <option value="<?= htmlspecialchars($level) ?>" <?= ($selectedGrade === $level) ? 'selected' : '' ?>>
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
                  $fileUrl = '../' . registrar_guides_public_path($guide['file_name']);
                  $previewUrl = '../Registrar/preview_guide.php?id=' . urlencode((string) $guide['id']);
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
                    <a href="<?= htmlspecialchars($previewUrl) ?>" target="_blank" rel="noopener noreferrer">Preview</a>
                    <a href="<?= htmlspecialchars($fileUrl) ?>" download="<?= htmlspecialchars($guide['original_name']) ?>">Download</a>
                    <form action="delete_guide.php" method="POST" style="display:inline;" onsubmit="return confirm('Remove this workbook from the dropbox?');">
                      <input type="hidden" name="guide_id" value="<?= (int) $guide['id'] ?>">
                      <button type="submit">Remove</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="dashboard-empty-state">No grade workbooks uploaded yet. Drag one above to get started.</div>
      <?php endif; ?>
    </section>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
  const dropzone = document.getElementById('guide-dropzone');
  const fileInput = document.getElementById('guide_file');
  const selected = document.getElementById('guide-selected');
  const browseBtn = document.getElementById('guide-browse-btn');

  if (!dropzone || !fileInput || !selected) {
    return;
  }

  function updateSelected() {
    if (fileInput.files && fileInput.files.length > 0) {
      selected.textContent = fileInput.files[0].name;
      selected.style.display = 'block';
    } else {
      selected.textContent = '';
      selected.style.display = 'none';
    }
  }

  ['dragenter', 'dragover'].forEach(function(eventName) {
    dropzone.addEventListener(eventName, function(event) {
      event.preventDefault();
      event.stopPropagation();
      dropzone.classList.add('is-dragover');
      if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'copy';
      }
    });
  });

  ['dragleave', 'dragend'].forEach(function(eventName) {
    dropzone.addEventListener(eventName, function(event) {
      event.preventDefault();
      event.stopPropagation();
      dropzone.classList.remove('is-dragover');
    });
  });

  dropzone.addEventListener('drop', function(event) {
    event.preventDefault();
    event.stopPropagation();
    dropzone.classList.remove('is-dragover');
    if (event.dataTransfer && event.dataTransfer.files.length > 0) {
      fileInput.files = event.dataTransfer.files;
      updateSelected();
    }
  });

  if (browseBtn) {
    browseBtn.addEventListener('click', function(event) {
      event.preventDefault();
      fileInput.click();
    });
    browseBtn.addEventListener('keydown', function(event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        fileInput.click();
      }
    });
  }

  fileInput.addEventListener('change', updateSelected);
  updateSelected();
})();
</script>
<?php
$adviserStorageKey = 'esr_session_adviser';
if (!empty($_SESSION['adviser_username'])) {
    $adviserStorageKey .= '_' . preg_replace('/[^a-z0-9_-]/i', '_', $_SESSION['adviser_username']);
}

$sessionMonitorConfig = [
    'pingUrl' => APP_BASE_PATH . 'session_ping.php',
    'redirectUrl' => APP_BASE_PATH . 'Adviser/adviser_login.php',
    'message' => 'Your adviser session ended because this account was used elsewhere.',
    'storageKey' => $adviserStorageKey,
];
$adviserSessionToken = $_SESSION['session_tokens']['adviser'] ?? null;
if (!empty($adviserSessionToken)) {
    $sessionMonitorConfig['sessionToken'] = $adviserSessionToken;
}
$sessionMonitorJson = json_encode($sessionMonitorConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<script>
  window.SESSION_MONITOR = <?= $sessionMonitorJson !== false ? $sessionMonitorJson : 'null'; ?>;
</script>
<script src="../assets/js/session_monitor.js?v=20241018"></script>
<script>
  window.AUTO_LOGOUT_CONFIG = { logoutUrl: 'adviser_logout.php' };
</script>
<script src="../assets/js/tab_auto_logout.js?v=20241019"></script>
</body>
</html>
