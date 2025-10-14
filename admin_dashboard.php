<?php
require_once __DIR__ . '/includes/session.php';
include 'db_connection.php';
require_once __DIR__ . '/admin_functions.php';
require_once __DIR__ . '/includes/adviser_assignments.php';

$userManagementMessage = $_SESSION['admin_users_success'] ?? '';
$userManagementError   = $_SESSION['admin_users_error'] ?? '';
if ($userManagementMessage !== '') {
  unset($_SESSION['admin_users_success']);
}
if ($userManagementError !== '') {
  unset($_SESSION['admin_users_error']);
}

$currentAdmin = admin_get_current_user($conn);
if ($currentAdmin) {
  if (!empty($currentAdmin['fullname'])) {
    $_SESSION['admin_fullname'] = $currentAdmin['fullname'];
  }
  if (!empty($currentAdmin['role']) && empty($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = ucwords($currentAdmin['role']);
  }
}

$userRows = admin_get_user_list($conn);

$adminDisplayName = $_SESSION['admin_fullname'] ?? ($_SESSION['admin_username'] ?? 'Administrator');
$adminRoleLabel = $_SESSION['admin_role'] ?? 'Administrator';
require_once __DIR__ . '/includes/homepage_images.php';

$scheduleMessage = '';
$scheduleError   = '';
$adviserMessage  = '';
$adviserError    = '';
$homeImageMessage = '';
$homeImageError   = '';

$homepageImages = homepage_images_load();

$scheduleGradeOptions = [
  'Pre-Prime 1', 'Pre-Prime 2', 'Kindergarten',
  'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
  'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'
];

$scheduleDaysOfWeek = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

adviser_assignments_ensure_table($conn);

$homepageSections = [
  'cards' => [
    'label' => 'Feature Cards',
    'type'  => 'static',
    'items' => [
      'programs'   => 'Programs Card',
      'admissions' => 'Admissions Card',
      'campus'     => 'Campus Life Card',
    ],
  ],
  'events' => [
    'label' => 'School Events Carousel',
    'type'  => 'carousel',
    'item_label' => 'Slide',
  ],
  'achievements' => [
    'label' => 'Achievements Carousel',
    'type'  => 'carousel',
    'item_label' => 'Slide',
  ],
  'primary_secondary' => [
    'label' => 'Primary & Secondary Carousel',
    'type'  => 'carousel',
    'item_label' => 'Slide',
  ],
  'junior_high' => [
    'label' => 'Junior High Carousel',
    'type'  => 'carousel',
    'item_label' => 'Slide',
  ],
  'senior_high' => [
    'label' => 'Senior High Carousel',
    'type'  => 'carousel',
    'item_label' => 'Slide',
  ],
  'paprisa' => [
    'label' => 'PAPRISA Carousel',
    'type'  => 'carousel',
    'item_label' => 'Slide',
  ],
  'board' => [
    'label' => 'Board Passers Carousel',
    'type'  => 'carousel',
    'item_label' => 'Slide',
  ],
  'laudes' => [
    'label' => 'Laudes Carousel',
    'type'  => 'carousel',
    'item_label' => 'Slide',
  ],
];

function homepage_next_slide_key(array $slides): string
{
  $maxIndex = 0;
  foreach (array_keys($slides) as $key) {
    if (preg_match('/^slide(\d+)$/', (string) $key, $matches)) {
      $index = (int) $matches[1];
      if ($index > $maxIndex) {
        $maxIndex = $index;
      }
    }
  }

  return 'slide' . ($maxIndex + 1);
}

function homepage_process_upload(array $fileInfo, string $sectionKey, string $itemKey, string $uploadDir, array $allowedExtensions, int $timestamp, array &$images, string &$homeImageError): void
{
  if ($homeImageError !== '') {
    return;
  }

  $error = $fileInfo['error'] ?? UPLOAD_ERR_NO_FILE;
  if ($error !== UPLOAD_ERR_OK) {
    return;
  }

  $originalName = $fileInfo['name'] ?? '';
  $tmpPath      = $fileInfo['tmp_name'] ?? '';
  if ($originalName === '' || $tmpPath === '') {
    return;
  }

  $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
  if (!in_array($extension, $allowedExtensions, true)) {
    $homeImageError = 'Invalid file type uploaded. Allowed: jpg, jpeg, png, gif, webp.';
    return;
  }

  $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($originalName, PATHINFO_FILENAME));
  if ($safeBase === '') {
    $safeBase = 'image';
  }

  $newFilename = $safeBase . '_' . $timestamp . '_' . $sectionKey . '_' . $itemKey . '.' . $extension;
  $destination = $uploadDir . $newFilename;

  if (!move_uploaded_file($tmpPath, $destination)) {
    $homeImageError = 'Failed to move uploaded file.';
    return;
  }

  $relativePath = 'assets/homepage/' . $newFilename;
  homepage_images_set($images, [$sectionKey, $itemKey], $relativePath);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_form'])) {
  $gradeLevel = trim((string) ($_POST['schedule_grade'] ?? ''));
  $sectionRaw = trim((string) ($_POST['schedule_section'] ?? ''));
  $allSections = isset($_POST['schedule_all_sections']);
  $section    = $allSections ? 'ALL' : ($sectionRaw !== '' ? $sectionRaw : 'ALL');
  $schoolYear = trim((string) ($_POST['schedule_school_year'] ?? ''));
  $subject    = trim((string) ($_POST['schedule_subject'] ?? ''));
  $teacher    = trim((string) ($_POST['schedule_teacher'] ?? ''));
  $dayOfWeek  = trim((string) ($_POST['schedule_day'] ?? ''));
  $startTime  = trim((string) ($_POST['schedule_start'] ?? ''));
  $endTime    = trim((string) ($_POST['schedule_end'] ?? ''));
  $room       = trim((string) ($_POST['schedule_room'] ?? ''));

  if ($gradeLevel === '' || $schoolYear === '' || $subject === '' || $dayOfWeek === '') {
    $scheduleError = 'Grade level, school year, subject, and day are required.';
  } elseif (!in_array($gradeLevel, $scheduleGradeOptions, true)) {
    $scheduleError = 'Invalid grade level selected.';
  } elseif (!in_array($dayOfWeek, $scheduleDaysOfWeek, true)) {
    $scheduleError = 'Invalid day of week selected.';
  } else {
    $startSql = null;
    if ($startTime !== '') {
      $startObj = DateTime::createFromFormat('H:i', $startTime);
      if ($startObj) {
        $startSql = $startObj->format('H:i:s');
      } else {
        $scheduleError = 'Invalid start time format.';
      }
    }

    $endSql = null;
    if ($scheduleError === '' && $endTime !== '') {
      $endObj = DateTime::createFromFormat('H:i', $endTime);
      if ($endObj) {
        $endSql = $endObj->format('H:i:s');
      } else {
        $scheduleError = 'Invalid end time format.';
      }
    }

    if ($scheduleError === '') {
      $insert = $conn->prepare('INSERT INTO class_schedules (grade_level, section, school_year, subject, teacher, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
      if ($insert) {
        $teacherValue = $teacher !== '' ? $teacher : null;
        $roomValue    = $room !== '' ? $room : null;
        $insert->bind_param(
          'sssssssss',
          $gradeLevel,
          $section,
          $schoolYear,
          $subject,
          $teacherValue,
          $dayOfWeek,
          $startSql,
          $endSql,
          $roomValue
        );
        if ($insert->execute()) {
          $scheduleMessage = 'Schedule entry saved successfully.';
        } else {
          $scheduleError = 'Failed to save schedule: ' . $conn->error;
        }
        $insert->close();
      } else {
        $scheduleError = 'Database error. Please try again later.';
      }
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adviser_form'])) {
  $action = $_POST['adviser_action'] ?? 'save';
  $assignmentId = isset($_POST['assignment_id']) ? (int) $_POST['assignment_id'] : 0;
  $gradeLevel = trim((string) ($_POST['adviser_grade'] ?? ''));
  $section    = trim((string) ($_POST['adviser_section'] ?? ''));
  $adviser    = trim((string) ($_POST['adviser_name'] ?? ''));

  if ($action === 'delete') {
    if ($assignmentId > 0) {
      $current = adviser_assignments_get($conn, $assignmentId);
      if ($current && adviser_assignments_delete($conn, $assignmentId)) {
        adviser_assignments_update_students($conn, $current['grade_level'], $current['section'], 'To be assigned');
        $adviserMessage = 'Adviser assignment removed.';
      } else {
        $adviserError = 'Unable to remove adviser assignment.';
      }
    } else {
      $adviserError = 'Invalid adviser assignment specified.';
    }
  } elseif ($action === 'update') {
    if ($assignmentId <= 0) {
      $adviserError = 'Invalid adviser assignment specified.';
    } else {
      $error = null;
      if (adviser_assignments_update($conn, $assignmentId, $gradeLevel, $section, $adviser, $error)) {
        $adviserMessage = 'Adviser assignment updated.';
      } else {
        $adviserError = $error ?? 'Unable to update adviser assignment.';
      }
    }
  } else {
    if (adviser_assignments_upsert($conn, $gradeLevel, $section, $adviser)) {
      $adviserMessage = 'Adviser assignment saved.';
    } else {
      $adviserError = 'Please provide a grade level, section, and adviser name.';
    }
  }

  unset($_POST['adviser_grade'], $_POST['adviser_section'], $_POST['adviser_name']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_homepage_images'])) {
  $defaults = homepage_images_defaults();
  if (homepage_images_save($defaults)) {
    $homepageImages = $defaults;
    $homeImageMessage = 'Homepage images reset to defaults.';
  } else {
    $homeImageError = 'Unable to reset homepage images. Check file permissions.';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['homepage_images_form'])) {
  $images = homepage_images_load();
  $uploadDir = __DIR__ . '/assets/homepage/';
  if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
      $homeImageError = 'Unable to create homepage image directory.';
    }
  }

  if ($homeImageError === '') {
    $allowedExtensions = ['jpg','jpeg','png','gif','webp'];
    $timestamp = time();

    foreach ($homepageSections as $sectionKey => $sectionData) {
      if ($homeImageError !== '') {
        break;
      }

      $sectionType = $sectionData['type'] ?? 'static';

      if ($sectionType === 'static') {
        foreach ($sectionData['items'] as $itemKey => $_label) {
          if (!isset($_FILES['homeimg_' . $sectionKey . '_' . $itemKey])) {
            continue;
          }
          homepage_process_upload(
            $_FILES['homeimg_' . $sectionKey . '_' . $itemKey],
            $sectionKey,
            $itemKey,
            $uploadDir,
            $allowedExtensions,
            $timestamp,
            $images,
            $homeImageError
          );
          if ($homeImageError !== '') {
            break;
          }
        }
        continue;
      }

      if (!isset($images[$sectionKey]) || !is_array($images[$sectionKey])) {
        $images[$sectionKey] = [];
      }

      $deleteField = 'homeimg_' . $sectionKey . '_delete';
      if (isset($_POST[$deleteField]) && is_array($_POST[$deleteField])) {
        foreach ($_POST[$deleteField] as $deleteKey) {
          $deleteKey = (string) $deleteKey;
          if (isset($images[$sectionKey][$deleteKey])) {
            unset($images[$sectionKey][$deleteKey]);
          }
        }
      }

      foreach (array_keys($images[$sectionKey]) as $itemKey) {
        if (!isset($_FILES['homeimg_' . $sectionKey . '_' . $itemKey])) {
          continue;
        }
        homepage_process_upload(
          $_FILES['homeimg_' . $sectionKey . '_' . $itemKey],
          $sectionKey,
          $itemKey,
          $uploadDir,
          $allowedExtensions,
          $timestamp,
          $images,
          $homeImageError
        );
        if ($homeImageError !== '') {
          break;
        }
      }

      if ($homeImageError !== '') {
        break;
      }

      $newField = 'homeimg_' . $sectionKey . '_new';
      if (isset($_FILES[$newField]) && is_array($_FILES[$newField]['error'])) {
        $fileCount = count($_FILES[$newField]['error']);
        for ($i = 0; $i < $fileCount; $i++) {
          if ($homeImageError !== '') {
            break;
          }
          if ($_FILES[$newField]['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
          }

          $nextKey = homepage_next_slide_key($images[$sectionKey]);
          $fileInfo = [
            'name'     => $_FILES[$newField]['name'][$i] ?? '',
            'tmp_name' => $_FILES[$newField]['tmp_name'][$i] ?? '',
            'error'    => $_FILES[$newField]['error'][$i],
          ];

          homepage_process_upload(
            $fileInfo,
            $sectionKey,
            $nextKey,
            $uploadDir,
            $allowedExtensions,
            $timestamp,
            $images,
            $homeImageError
          );
        }
      }
    }

    if ($homeImageError === '') {
      if (homepage_images_save($images)) {
        $homeImageMessage = 'Homepage images updated successfully.';
        $homepageImages = $images;
      } else {
        $homeImageError = 'Unable to save homepage image settings.';
      }
    }
  }
}

$scheduleRows = [];
$dayOrdering = "FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')";
$scheduleSql = "SELECT id, grade_level, section, school_year, subject, teacher, day_of_week, start_time, end_time, room
               FROM class_schedules
               ORDER BY grade_level, section, $dayOrdering, start_time IS NULL, start_time";

if ($resultSchedules = $conn->query($scheduleSql)) {
  $scheduleRows = $resultSchedules->fetch_all(MYSQLI_ASSOC);
  $resultSchedules->close();
}

$adviserAssignments = adviser_assignments_fetch($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Escuela De Sto. Rosario</title>
  <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body class="dashboard-body">
<button class="dashboard-toggle" type="button" aria-label="Toggle navigation" onclick="document.querySelector('.dashboard-sidebar').classList.toggle('is-open');">☰</button>
<div class="dashboard-shell">
  <aside class="dashboard-sidebar">
    <div class="dashboard-brand">
      <img src="Esrlogo.png" alt="ESR Logo">
      <span>Admin Portal</span>
    </div>
    <nav class="dashboard-nav">
      <a href="#stats">Statistics</a>
      <a href="#announcements">Announcements</a>
      <a href="#users">User Management</a>
      <a href="#schedules">Class Schedules</a>
      <a href="#homepage-images">Homepage Images</a>
      <a href="#students">Student Tools</a>
    </nav>
    <a href="admin_logout.php" class="dashboard-logout">Logout</a>
  </aside>

  <main class="dashboard-main">
    <header class="dashboard-header">
      <div>
        <h1>Admin Dashboard</h1>
        <p>Oversee school-wide data, publish announcements, and curate the public website content.</p>
      </div>
      <div class="dashboard-user-chip" title="Logged in as <?= htmlspecialchars($adminDisplayName); ?>">
        <span class="chip-label">Logged in as</span>
        <span class="chip-name"><?= htmlspecialchars($adminDisplayName); ?></span>
        <span class="chip-divider">•</span>
        <span class="chip-role"><?= htmlspecialchars($adminRoleLabel); ?></span>
      </div>
    </header>

    <section class="dashboard-card" id="stats">
      <span class="dashboard-section-title">At A Glance</span>
      <h2>Statistics</h2>
      <?php include 'statistics.php'; ?>
    </section>

    <section class="dashboard-card" id="announcements">
      <span class="dashboard-section-title">Communication</span>
      <h2>Post Announcement</h2>
      <form class="dashboard-form" action="submit_announcement.php" method="POST" enctype="multipart/form-data">
        <label for="subject">Subject</label>
        <input type="text" name="subject" required>

        <label for="message">Message</label>
        <textarea name="message" required></textarea>

        <label for="announcement_image">Attach Image (optional)</label>
        <input type="file" name="announcement_image" accept="image/*">

        <label for="grades">Send To</label>
        <div class="dashboard-checkbox-grid" style="margin-top:10px;">
          <?php
          $audienceOptions = [
              'everyone'  => 'Everyone',
              'preschool' => 'Pre-School',
              'pp1'       => 'Pre-Prime 1',
              'pp2'       => 'Pre-Prime 2',
              'pp12'      => 'Pre-Prime 1 & 2',
              'kg'        => 'Kindergarten',
              'g1'        => 'Grade-1',
              'g2'        => 'Grade-2',
              'g3'        => 'Grade-3',
              'g4'        => 'Grade-4',
              'g5'        => 'Grade-5',
              'g6'        => 'Grade-6',
              'g7'        => 'Grade-7',
              'g8'        => 'Grade-8',
              'g9'        => 'Grade-9',
              'g10'       => 'Grade-10',
              'g11'       => 'Grade-11',
              'g12'       => 'Grade-12',
          ];

          foreach ($audienceOptions as $code => $label) {
              echo "<label><input type=\"checkbox\" name=\"grades[]\" value=\"$code\"> $label</label>";
          }
          ?>
        </div>

        <div class="dashboard-actions">
          <button type="submit" class="dashboard-btn">Send Announcement</button>
        </div>
      </form>
    </section>

    <section class="dashboard-card" id="users">
      <span class="dashboard-section-title">Access Control</span>
      <h2>Add New User</h2>
      <?php if ($userManagementMessage !== ''): ?>
        <div class="dashboard-alert success"><?= htmlspecialchars($userManagementMessage) ?></div>
      <?php endif; ?>
      <?php if ($userManagementError !== ''): ?>
        <div class="dashboard-alert error"><?= htmlspecialchars($userManagementError) ?></div>
      <?php endif; ?>
      <form class="dashboard-form" action="admin_addusers.php" method="POST">
        <label for="fullname">Full Name</label>
        <input type="text" name="fullname" required>

        <label for="username">Username</label>
        <input type="text" name="username" required>

        <label for="password">Password</label>
        <input type="password" name="password" required>

        <label for="role">Role</label>
        <select name="role" required>
          <option value="admin">Administrator</option>
          <option value="registrar">Registrar</option>
          <option value="cashier">Cashier</option>
          <option value="adviser">Adviser</option>
        </select>

        <div class="dashboard-actions">
          <button type="submit" class="dashboard-btn">Add User</button>
        </div>
      </form>
      <?php if (!empty($userRows)): ?>
        <hr style="margin:24px 0; border:none; border-top:1px solid #d4dce1;">
        <button type="button" class="dashboard-btn secondary dashboard-btn--small" data-toggle-label="View Users" data-toggle-active-label="Hide Users" data-toggle-target="userList" onclick="toggleSection(this);">
          View Users
        </button>
        <div class="table-responsive collapsible" id="userList" style="display:none; margin-top:16px;">
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Role</th>
                <th style="width:120px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($userRows as $user): ?>
                <tr>
                  <td><?= htmlspecialchars($user['fullname'] ?? '') ?></td>
                  <td><?= htmlspecialchars($user['username'] ?? '') ?></td>
                  <td style="text-transform:capitalize;"><?= htmlspecialchars($user['role'] ?? '') ?></td>
                  <td>
                    <form action="admin_delete_user.php" method="POST" onsubmit="return confirm('Remove this user?');" style="display:inline;">
                      <input type="hidden" name="user_id" value="<?= (int) ($user['id'] ?? 0) ?>">
                      <button type="submit" class="dashboard-btn secondary" style="padding:6px 12px;">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted" style="margin-top:16px;">No users have been added yet.</p>
      <?php endif; ?>
    </section>
    <section class="dashboard-card" id="schedules">
      <span class="dashboard-section-title">Schedule Planner</span>
      <h2>Class Schedules</h2>
      <?php if ($scheduleMessage !== ''): ?>
        <div class="dashboard-alert success">
          <?= htmlspecialchars($scheduleMessage) ?>
        </div>
      <?php endif; ?>
      <?php if ($scheduleError !== ''): ?>
        <div class="dashboard-alert error">
          <?= htmlspecialchars($scheduleError) ?>
        </div>
      <?php endif; ?>

      <form class="dashboard-form" method="POST" action="#schedules">
      <input type="hidden" name="schedule_form" value="1">
      <div class="dashboard-grid two">
      <div>
        <label for="schedule_grade">Grade Level</label>
        <select name="schedule_grade" id="schedule_grade" required>
          <option value="">Select grade</option>
          <?php foreach ($scheduleGradeOptions as $gradeOption):
            $sel = (($_POST['schedule_grade'] ?? '') === $gradeOption) ? 'selected' : '';
          ?>
            <option value="<?= htmlspecialchars($gradeOption) ?>" <?= $sel ?>><?= htmlspecialchars($gradeOption) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="schedule_school_year">School Year</label>
        <input type="text" name="schedule_school_year" id="schedule_school_year" placeholder="e.g., 2024-2025" required value="<?= htmlspecialchars($_POST['schedule_school_year'] ?? '') ?>">
      </div>
      <div>
        <label for="schedule_section">Section (optional)</label>
        <input type="text" name="schedule_section" id="schedule_section" placeholder="e.g., Section A" value="<?= htmlspecialchars($_POST['schedule_section'] ?? '') ?>">
        <label class="dashboard-checkbox-inline">
          <input type="checkbox" name="schedule_all_sections" <?= isset($_POST['schedule_all_sections']) ? 'checked' : '' ?>> Apply to all sections
        </label>
      </div>
      <div>
        <label for="schedule_subject">Subject</label>
        <input type="text" name="schedule_subject" id="schedule_subject" required value="<?= htmlspecialchars($_POST['schedule_subject'] ?? '') ?>">
      </div>
      <div>
        <label for="schedule_teacher">Teacher (optional)</label>
        <input type="text" name="schedule_teacher" id="schedule_teacher" value="<?= htmlspecialchars($_POST['schedule_teacher'] ?? '') ?>">
      </div>
      <div>
        <label for="schedule_day">Day of Week</label>
        <select name="schedule_day" id="schedule_day" required>
          <option value="">Select day</option>
          <?php foreach ($scheduleDaysOfWeek as $dayOption):
            $sel = (($_POST['schedule_day'] ?? '') === $dayOption) ? 'selected' : '';
          ?>
            <option value="<?= htmlspecialchars($dayOption) ?>" <?= $sel ?>><?= htmlspecialchars($dayOption) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="schedule_start">Start Time (optional)</label>
        <input type="time" name="schedule_start" id="schedule_start" value="<?= htmlspecialchars($_POST['schedule_start'] ?? '') ?>">
      </div>
      <div>
        <label for="schedule_end">End Time (optional)</label>
        <input type="time" name="schedule_end" id="schedule_end" value="<?= htmlspecialchars($_POST['schedule_end'] ?? '') ?>">
      </div>
      <div>
        <label for="schedule_room">Room (optional)</label>
        <input type="text" name="schedule_room" id="schedule_room" value="<?= htmlspecialchars($_POST['schedule_room'] ?? '') ?>">
      </div>
      </div>
      <div class="dashboard-actions">
        <button type="submit" class="dashboard-btn">Save Schedule</button>
      </div>
      </form>

      <div class="dashboard-subsection" id="adviserAssignments" style="margin-top:32px;">
        <h3 style="margin-bottom:16px;">Section Adviser Assignments</h3>
        <p class="text-muted" style="margin-bottom:20px;">Link a section to its adviser so automatic placements and registrar tools stay in sync. Provide the grade, then type the section name and adviser.</p>

        <?php if ($adviserMessage !== ''): ?>
          <div class="dashboard-alert success">
            <?= htmlspecialchars($adviserMessage) ?>
          </div>
        <?php endif; ?>
        <?php if ($adviserError !== ''): ?>
          <div class="dashboard-alert error">
            <?= htmlspecialchars($adviserError) ?>
          </div>
        <?php endif; ?>

        <form class="dashboard-form" method="POST" action="#adviserAssignments" style="margin-bottom:24px;">
          <input type="hidden" name="adviser_form" value="1">
          <input type="hidden" name="adviser_action" value="save">
          <div class="dashboard-grid three">
            <div>
              <label for="adviser_grade">Grade Level</label>
              <select name="adviser_grade" id="adviser_grade" required>
                <option value="">Select grade</option>
                <?php foreach ($scheduleGradeOptions as $gradeOption): ?>
                  <option value="<?= htmlspecialchars($gradeOption) ?>" <?= (($_POST['adviser_grade'] ?? '') === $gradeOption) ? 'selected' : '' ?>><?= htmlspecialchars($gradeOption) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="adviser_section">Section</label>
              <input type="text" name="adviser_section" id="adviser_section" placeholder="e.g., Section A" value="<?= htmlspecialchars($_POST['adviser_section'] ?? '') ?>" required>
            </div>
            <div>
              <label for="adviser_name">Adviser</label>
              <input type="text" name="adviser_name" id="adviser_name" placeholder="e.g., Mrs. dela Cruz" value="<?= htmlspecialchars($_POST['adviser_name'] ?? '') ?>" required>
            </div>
          </div>
          <div class="dashboard-actions">
            <button type="submit" class="dashboard-btn">Save Adviser</button>
          </div>
        </form>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th style="width:18%;">Grade</th>
                <th style="width:28%;">Section</th>
                <th style="width:28%;">Adviser</th>
                <th style="width:26%;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($adviserAssignments)): ?>
                <tr>
                  <td colspan="4" style="text-align:center;">No adviser assignments yet. Use the form above to add one.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($adviserAssignments as $assignment): 
                  $formId = 'update-adviser-' . (int) $assignment['id'];
                  $deleteFormId = 'delete-adviser-' . (int) $assignment['id'];
                ?>
                  <tr>
                    <td>
                      <form id="<?= $formId ?>" method="POST" action="#adviserAssignments">
                        <input type="hidden" name="adviser_form" value="1">
                        <input type="hidden" name="adviser_action" value="update">
                        <input type="hidden" name="assignment_id" value="<?= (int) $assignment['id'] ?>">
                      </form>
                      <select name="adviser_grade" form="<?= $formId ?>" required>
                        <?php foreach ($scheduleGradeOptions as $gradeOption): ?>
                          <option value="<?= htmlspecialchars($gradeOption) ?>" <?= ($assignment['grade_level'] === $gradeOption) ? 'selected' : '' ?>><?= htmlspecialchars($gradeOption) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td>
                      <input type="text" name="adviser_section" form="<?= $formId ?>" value="<?= htmlspecialchars($assignment['section']) ?>" required>
                    </td>
                    <td>
                      <input type="text" name="adviser_name" form="<?= $formId ?>" value="<?= htmlspecialchars($assignment['adviser']) ?>" required>
                    </td>
                    <td>
                      <button type="submit" class="dashboard-btn secondary dashboard-btn--small" form="<?= $formId ?>">Update</button>
                      <form id="<?= $deleteFormId ?>" method="POST" action="#adviserAssignments" style="display:inline;">
                        <input type="hidden" name="adviser_form" value="1">
                        <input type="hidden" name="adviser_action" value="delete">
                        <input type="hidden" name="assignment_id" value="<?= (int) $assignment['id'] ?>">
                      </form>
                      <button type="submit" class="dashboard-btn secondary dashboard-btn--small" form="<?= $deleteFormId ?>" style="background:#fbeaea;color:#c0392b;" onclick="return confirm('Remove this adviser assignment?');">Delete</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <hr style="margin:32px 0; border:none; border-top:1px solid #d4dce1;">

      <button type="button" class="dashboard-btn secondary dashboard-btn--small" data-toggle-label="View Schedule List" data-toggle-active-label="Hide Schedule List" data-toggle-target="scheduleList" onclick="toggleSection(this);" style="margin-bottom:16px;">
        View Schedule List
      </button>
      <div class="table-responsive collapsible" id="scheduleList" style="display:none;">
        <table>
        <thead>
          <tr>
            <th>Grade</th>
            <th>Section</th>
            <th>School Year</th>
            <th>Day</th>
            <th>Time</th>
            <th>Subject</th>
            <th>Teacher</th>
            <th>Room</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($scheduleRows)): ?>
            <tr><td colspan="8" style="text-align:center;">No schedules configured yet.</td></tr>
          <?php else: ?>
            <?php foreach ($scheduleRows as $row):
              $start = $row['start_time'] ? date('g:i A', strtotime($row['start_time'])) : '—';
              $end   = $row['end_time'] ? date('g:i A', strtotime($row['end_time'])) : '—';
              $timeDisplay = ($start === '—' && $end === '—') ? '—' : trim($start . ($end !== '—' ? ' - ' . $end : ''));
            ?>
              <tr>
                <td><?= htmlspecialchars($row['grade_level']) ?></td>
                <td><?= htmlspecialchars($row['section']) ?></td>
                <td><?= htmlspecialchars($row['school_year']) ?></td>
                <td><?= htmlspecialchars($row['day_of_week']) ?></td>
                <td><?= htmlspecialchars($timeDisplay) ?></td>
                <td><?= htmlspecialchars($row['subject']) ?></td>
                <td><?= htmlspecialchars($row['teacher'] ?? '—') ?></td>
                <td><?= htmlspecialchars($row['room'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        </table>
      </div>
    </section>
  <?php
function normalizeEarlyGrade(string $grade): string {
    $grade = trim($grade);
    if (in_array($grade, ['Kinder 1', 'Kinder 2'], true)) {
        return 'Kindergarten';
    }
    return $grade;
}

function gradeSynonyms(string $grade): array {
    $normalized = normalizeEarlyGrade($grade);
    if ($normalized === 'Kindergarten') {
        return ['Kindergarten', 'Kinder 1', 'Kinder 2'];
    }
    return [$normalized];
}

// Helper function for section counts
function getSectionCounts($conn, $grade) {
    $sections = [];
    $normalizedGrade = normalizeEarlyGrade($grade);

    if (in_array($normalizedGrade, ['Pre-Prime 1','Pre-Prime 2','Kindergarten'], true)) {
        $sections = ["Hershey" => ["count" => 0, "max" => 20],
                     "Kisses"  => ["count" => 0, "max" => 20]];
    } elseif (in_array($normalizedGrade, ['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'], true)) {
        $sections = ["Section A" => ["count" => 0, "max" => 30],
                     "Section B" => ["count" => 0, "max" => 30]];
    } elseif (in_array($normalizedGrade, ['Grade 7','Grade 8','Grade 9','Grade 10'], true)) {
        $sections = ["Section A" => ["count" => 0, "max" => 40],
                     "Section B" => ["count" => 0, "max" => 40]];
    } elseif (in_array($normalizedGrade, ['Grade 11','Grade 12'], true)) {
        $sections = ["ABM - Section 1"   => ["count" => 0, "max" => 50],
                     "GAS - Section 1"   => ["count" => 0, "max" => 50],
                     "HUMMS - Section 1" => ["count" => 0, "max" => 50],
                     "ICT - Section 1"   => ["count" => 0, "max" => 50],
                     "TVL - Section 1"   => ["count" => 0, "max" => 50]];
    }

    $gradeVariants = gradeSynonyms($grade);

    foreach ($sections as $sec => $data) {
        $count = 0;
        foreach ($gradeVariants as $variant) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM students_registration WHERE year = ? AND section = ?");
            $stmt->bind_param("ss", $variant, $sec);
            $stmt->execute();
            $partial = 0;
            $stmt->bind_result($partial);
            $stmt->fetch();
            $stmt->close();
            $count += (int) $partial;
        }

        $sections[$sec]['count'] = $count;
    }
    return $sections;
}

// Build query for students
$grade_filter   = $_GET['grade']   ?? '';
$section_filter = $_GET['section'] ?? '';

$sql = "SELECT * FROM students_registration WHERE enrollment_status = 'enrolled'";
$params = [];
$types  = "";

if (!empty($grade_filter)) {
    $variants = gradeSynonyms($grade_filter);
    if (count($variants) === 1) {
        $sql .= " AND year = ?";
        $params[] = $variants[0];
        $types   .= "s";
    } else {
        $placeholders = implode(',', array_fill(0, count($variants), '?'));
        $sql .= " AND year IN ($placeholders)";
        foreach ($variants as $variant) {
            $params[] = $variant;
            $types   .= "s";
        }
    }
}
if (!empty($section_filter)) {
    $sql .= " AND section = ?";
    $params[] = $section_filter;
    $types   .= "s";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$studentsRows = [];
if ($result = $stmt->get_result()) {
    $studentsRows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}
$stmt->close();

$rosters = [];
foreach ($studentsRows as $row) {
    $gradeKey = trim((string)($row['year'] ?? ''));
    $gradeKey = $gradeKey !== '' ? $gradeKey : 'Unassigned';
    $sectionKey = trim((string)($row['section'] ?? ''));
    $sectionKey = $sectionKey !== '' ? $sectionKey : 'Unassigned';
    $rosters[$gradeKey][$sectionKey][] = $row;
}
ksort($rosters);
foreach ($rosters as &$sections) {
    ksort($sections);
}
unset($sections);
?>

    <section class="dashboard-card" id="homepage-images">
      <span class="dashboard-section-title">Public Site</span>
      <h2>Homepage Images</h2>
      <?php if ($homeImageMessage !== ''): ?>
        <div class="dashboard-alert success">
          <?= htmlspecialchars($homeImageMessage) ?>
        </div>
      <?php endif; ?>
      <?php if ($homeImageError !== ''): ?>
        <div class="dashboard-alert error">
          <?= htmlspecialchars($homeImageError) ?>
        </div>
      <?php endif; ?>

      <form class="dashboard-form" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:18px;">
      <input type="hidden" name="homepage_images_form" value="1">
      <?php foreach ($homepageSections as $sectionKey => $sectionData):
        $sectionLabel = $sectionData['label'];
        $sectionType  = $sectionData['type'] ?? 'static';
        $isCarousel   = $sectionType === 'carousel';
      ?>
        <details style="background:#fff; border:1px solid #d4dce1; border-radius:10px; padding:16px;">
          <summary style="cursor:pointer; font-weight:700; color:#145A32; margin-bottom:12px;"><?= htmlspecialchars($sectionLabel) ?></summary>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
            <?php if ($isCarousel): ?>
              <?php
                $slides = isset($homepageImages[$sectionKey]) && is_array($homepageImages[$sectionKey])
                  ? $homepageImages[$sectionKey]
                  : [];
                $itemLabelPrefix = $sectionData['item_label'] ?? 'Slide';
                $slideNumber = 1;
              ?>
              <?php foreach ($slides as $itemKey => $currentImage):
                $fieldName = 'homeimg_' . $sectionKey . '_' . $itemKey;
                $displayLabel = $itemLabelPrefix . ' ' . $slideNumber;
                $slideNumber++;
              ?>
                <div style="border:1px solid #e0e6ed; border-radius:10px; padding:12px; background:#f8fafc; position:relative;">
                  <div style="font-weight:600; margin-bottom:8px; color:#145A32;"><?= htmlspecialchars($displayLabel) ?></div>
                  <div style="margin-bottom:10px;">
                    <img src="<?= htmlspecialchars($currentImage) ?>" alt="<?= htmlspecialchars($displayLabel) ?>" style="max-width:100%; max-height:150px; object-fit:cover; border-radius:8px; border:1px solid #d4dce1;">
                  </div>
                  <label style="display:block; font-weight:500; margin-bottom:6px;">Replace image</label>
                  <input type="file" name="<?= htmlspecialchars($fieldName) ?>" accept="image/*">
                  <p style="font-size:12px; color:#5d6d6f; margin-top:6px;">Current: <?= htmlspecialchars($currentImage) ?></p>
                  <label style="display:flex; gap:6px; align-items:center; font-size:13px; margin-top:8px; color:#5d6d6f;">
                    <input type="checkbox" name="homeimg_<?= htmlspecialchars($sectionKey) ?>_delete[]" value="<?= htmlspecialchars($itemKey) ?>">
                    Remove this slide
                  </label>
                </div>
              <?php endforeach; ?>
              <div style="border:1px dashed #b8c6cc; border-radius:10px; padding:12px; background:#fdfefe; display:flex; flex-direction:column; justify-content:center; gap:8px;">
                <div style="font-weight:600; color:#145A32;">Add new slides</div>
                <p style="font-size:12px; color:#5d6d6f; margin:0;">Upload one or more images to append to this carousel.</p>
                <input type="file" name="homeimg_<?= htmlspecialchars($sectionKey) ?>_new[]" accept="image/*" multiple>
                <p style="font-size:12px; color:#5d6d6f; margin:0;">Tip: hold Ctrl/Cmd to select multiple files.</p>
              </div>
            <?php else: ?>
              <?php foreach ($sectionData['items'] as $itemKey => $itemLabel):
                $currentImage = homepage_images_get($homepageImages, [$sectionKey, $itemKey]);
                $fieldName = 'homeimg_' . $sectionKey . '_' . $itemKey;
              ?>
                <div style="border:1px solid #e0e6ed; border-radius:10px; padding:12px; background:#f8fafc;">
                  <div style="font-weight:600; margin-bottom:8px; color:#145A32;"><?= htmlspecialchars($itemLabel) ?></div>
                  <div style="margin-bottom:10px;">
                    <img src="<?= htmlspecialchars($currentImage) ?>" alt="<?= htmlspecialchars($itemLabel) ?>" style="max-width:100%; max-height:150px; object-fit:cover; border-radius:8px; border:1px solid #d4dce1;">
                  </div>
                  <label style="display:block; font-weight:500; margin-bottom:6px;">Upload new image</label>
                  <input type="file" name="<?= htmlspecialchars($fieldName) ?>" accept="image/*">
                  <p style="font-size:12px; color:#5d6d6f; margin-top:6px;">Current: <?= htmlspecialchars($currentImage) ?></p>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </details>
      <?php endforeach; ?>
      <div class="dashboard-actions" style="justify-content:flex-end;">
        <button type="submit" class="dashboard-btn">Save Images</button>
      </div>
      </form>

      <form class="dashboard-form" method="POST" style="margin-top:12px; text-align:right;">
      <input type="hidden" name="reset_homepage_images" value="1">
      <div class="dashboard-actions" style="justify-content:flex-end;">
        <button type="submit" class="dashboard-btn secondary">Reset to Defaults</button>
      </div>
      </form>
    </section>

    <section class="dashboard-card" id="students">
      <span class="dashboard-section-title">Student Tools</span>
      <h2>Manage Students</h2>
      <form class="dashboard-form" method="GET" style="margin-bottom:20px; display:flex; gap:20px; align-items:center;">
      <div>
        <label><b>Grade:</b></label>
        <select name="grade" id="gradeSelect" onchange="this.form.submit()">
          <option value="">All</option>
          <?php 
          $grades = ["Pre-Prime 1","Pre-Prime 2","Kindergarten","Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6",
                     "Grade 7","Grade 8","Grade 9","Grade 10","Grade 11","Grade 12"];
          foreach ($grades as $g) {
              $sel = ($_GET['grade'] ?? '') === $g ? "selected" : "";
              echo "<option value='$g' $sel>$g</option>";
          }
          ?>
        </select>
      </div>

      <div>
        <label><b>Section:</b></label>
        <select name="section" id="sectionSelect" onchange="this.form.submit()">
          <option value="">All</option>
          <?php 
          if (!empty($_GET['grade'])) {
              $sections = getSectionCounts($conn, $_GET['grade']);
              foreach ($sections as $sec => $info) {
                  $sel = ($_GET['section'] ?? '') === $sec ? "selected" : "";
                  echo "<option value='$sec' $sel>$sec ({$info['count']}/{$info['max']})</option>";
              }
          }
          ?>
        </select>
      </div>
    </form>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Student Number</th>
              <th>Name</th>
              <th>Grade</th>
              <th>Section</th>
              <th>Adviser</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($studentsRows)): ?>
              <?php foreach ($studentsRows as $row): ?>
                <tr>
                  <td><?= $row['id'] ?></td>
                  <td><?= htmlspecialchars($row['student_number']) ?></td>
                  <td><?= htmlspecialchars($row['firstname'] . " " . $row['lastname']) ?></td>
                  <td><?= htmlspecialchars($row['year']) ?></td>
                  <td><?= htmlspecialchars($row['section'] ?: "Unassigned") ?></td>
                  <td><?= htmlspecialchars($row['adviser'] ?: "TBA") ?></td>
                  <td><?= htmlspecialchars($row['academic_status'] ?? $row['enrollment_status']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="7" class="text-center">No enrolled students found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <?php if (!empty($rosters)): ?>
      <section class="dashboard-card" id="print-rosters">
        <span class="dashboard-section-title">Print Lists</span>
        <h2>Section Checklists</h2>
        <p class="text-muted">Generate first-day adviser sheets for each grade and section.</p>
        <?php foreach ($rosters as $gradeLabel => $sections): ?>
          <div class="roster-grade">
            <h3><?= htmlspecialchars($gradeLabel) ?></h3>
            <?php foreach ($sections as $sectionLabel => $students):
              $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $gradeLabel . '-' . $sectionLabel));
              $slug = trim($slug, '-');
              $targetId = 'print-roster-' . $slug;
            ?>
              <div class="roster-section">
                <div class="roster-section__header">
                  <h4><?= htmlspecialchars($sectionLabel) ?></h4>
                  <button type="button" class="dashboard-btn secondary dashboard-btn--small" onclick="printRoster('<?= $targetId ?>')">Print</button>
                </div>
                <div class="roster-print-block" id="<?= $targetId ?>">
                  <div class="roster-print-heading">
                    <h2>Escuela de Sto. Rosario</h2>
                    <p><strong>Grade:</strong> <?= htmlspecialchars($gradeLabel) ?> &nbsp;|&nbsp; <strong>Section:</strong> <?= htmlspecialchars($sectionLabel) ?></p>
                    <p><strong>Prepared:</strong> <?= date('F j, Y') ?></p>
                  </div>
                  <table class="roster-print-table">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Student Number</th>
                        <th>Student Name</th>
                        <th>Adviser</th>
                        <th>Signature</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($students as $index => $student):
                        $fullName = trim(($student['lastname'] ?? '') . ', ' . ($student['firstname'] ?? '') . ' ' . ($student['middlename'] ?? ''));
                      ?>
                        <tr>
                          <td><?= $index + 1 ?></td>
                          <td><?= htmlspecialchars($student['student_number'] ?? '—') ?></td>
                          <td><?= htmlspecialchars($fullName) ?></td>
                          <td><?= htmlspecialchars($student['adviser'] ?? 'TBA') ?></td>
                          <td></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>

  </main>
</div>

<?php $conn->close(); ?>

<script>
  function toggleSection(button) {
    if (!button) return;
    var targetId = button.getAttribute('data-toggle-target');
    if (!targetId) return;
    var target = document.getElementById(targetId);
    if (!target) return;

    var isHidden = target.style.display === 'none' || getComputedStyle(target).display === 'none';
    target.style.display = isHidden ? '' : 'none';

    var defaultLabel = button.getAttribute('data-toggle-label') || button.textContent || 'View';
    var activeLabel = button.getAttribute('data-toggle-active-label') || 'Hide';
    button.textContent = isHidden ? activeLabel : defaultLabel;
  }

  function printRoster(targetId) {
    var block = document.getElementById(targetId);
    if (!block) {
      return;
    }
    var printWindow = window.open('', '_blank', 'width=900,height=650');
    printWindow.document.write('<html><head><title>Class List</title>');
    printWindow.document.write('<style>body{font-family:\'Segoe UI\',Tahoma,Arial,sans-serif;margin:24px;}h2{margin:0 0 8px;}p{margin:4px 0;}table{width:100%;border-collapse:collapse;margin-top:16px;}th,td{border:1px solid #555;padding:8px;text-align:left;font-size:13px;}th{text-transform:uppercase;font-size:12px;}@media print{body{margin:12mm;}}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(block.outerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
  }
</script>

</body>
</html>
