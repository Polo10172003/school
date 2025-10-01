<?php
include 'db_connection.php';
require_once __DIR__ . '/includes/homepage_images.php';

$scheduleMessage = '';
$scheduleError   = '';
$homeImageMessage = '';
$homeImageError   = '';

$homepageImages = homepage_images_load();

$scheduleGradeOptions = [
  'Pre-Prime 1', 'Pre-Prime 2', 'Kindergarten',
  'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
  'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'
];

$scheduleDaysOfWeek = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

$homepageSections = [
  'cards' => [
    'label' => 'Feature Cards',
    'items' => [
      'programs'   => 'Programs Card',
      'admissions' => 'Admissions Card',
      'campus'     => 'Campus Life Card',
    ],
  ],
  'events' => [
    'label' => 'School Events Carousel',
    'items' => [
      'slide1' => 'Slide 1',
      'slide2' => 'Slide 2',
      'slide3' => 'Slide 3',
    ],
  ],
  'achievements' => [
    'label' => 'Achievements Carousel',
    'items' => [
      'slide1' => 'Slide 1',
      'slide2' => 'Slide 2',
      'slide3' => 'Slide 3',
    ],
  ],
  'primary_secondary' => [
    'label' => 'Primary & Secondary Carousel',
    'items' => [
      'slide1' => 'Slide 1',
      'slide2' => 'Slide 2',
      'slide3' => 'Slide 3',
    ],
  ],
  'junior_high' => [
    'label' => 'Junior High Carousel',
    'items' => [
      'slide1' => 'Slide 1',
      'slide2' => 'Slide 2',
      'slide3' => 'Slide 3',
    ],
  ],
  'senior_high' => [
    'label' => 'Senior High Carousel',
    'items' => [
      'slide1' => 'Slide 1',
      'slide2' => 'Slide 2',
      'slide3' => 'Slide 3',
    ],
  ],
  'paprisa' => [
    'label' => 'PAPRISA Carousel',
    'items' => [
      'slide1' => 'Slide 1',
      'slide2' => 'Slide 2',
      'slide3' => 'Slide 3',
    ],
  ],
  'board' => [
    'label' => 'Board Passers Carousel',
    'items' => [
      'slide1' => 'Slide 1',
      'slide2' => 'Slide 2',
      'slide3' => 'Slide 3',
    ],
  ],
  'laudes' => [
    'label' => 'Laudes Carousel',
    'items' => [
      'slide1' => 'Slide 1',
      'slide2' => 'Slide 2',
      'slide3' => 'Slide 3',
    ],
  ],
];

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
      foreach ($sectionData['items'] as $itemKey => $_label) {
        $fieldName = 'homeimg_' . $sectionKey . '_' . $itemKey;
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
          continue;
        }

        $originalName = $_FILES[$fieldName]['name'];
        $tmpPath = $_FILES[$fieldName]['tmp_name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
          $homeImageError = 'Invalid file type uploaded. Allowed: jpg, jpeg, png, gif, webp.';
          break 2;
        }

        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($originalName, PATHINFO_FILENAME));
        if ($safeBase === '') {
          $safeBase = 'image';
        }
        $newFilename = $safeBase . '_' . $timestamp . '_' . $sectionKey . '_' . $itemKey . '.' . $extension;
        $destination = $uploadDir . $newFilename;

        if (!move_uploaded_file($tmpPath, $destination)) {
          $homeImageError = 'Failed to move uploaded file.';
          break 2;
        }

        $relativePath = 'assets/homepage/' . $newFilename;
        homepage_images_set($images, [$sectionKey, $itemKey], $relativePath);
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

$scheduleFilterGrade   = trim((string) ($_GET['schedule_grade_filter'] ?? ''));
$scheduleFilterSection = trim((string) ($_GET['schedule_section_filter'] ?? ''));
$scheduleFilterYear    = trim((string) ($_GET['schedule_year_filter'] ?? ''));

$scheduleRows = [];
$scheduleSql = 'SELECT id, grade_level, section, school_year, subject, teacher, day_of_week, start_time, end_time, room FROM class_schedules WHERE 1=1';
$scheduleTypes = '';
$scheduleParams = [];

if ($scheduleFilterGrade !== '') {
  $scheduleSql   .= ' AND grade_level = ?';
  $scheduleTypes .= 's';
  $scheduleParams[] = $scheduleFilterGrade;
}
if ($scheduleFilterSection !== '') {
  $scheduleSql   .= ' AND section = ?';
  $scheduleTypes .= 's';
  $scheduleParams[] = $scheduleFilterSection;
}
if ($scheduleFilterYear !== '') {
  $scheduleSql   .= ' AND school_year = ?';
  $scheduleTypes .= 's';
  $scheduleParams[] = $scheduleFilterYear;
}

$dayOrdering = "FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')";
$scheduleSql .= " ORDER BY grade_level, section, $dayOrdering, start_time IS NULL, start_time";

if ($stmtSchedules = $conn->prepare($scheduleSql)) {
  if (!empty($scheduleParams)) {
    $stmtSchedules->bind_param($scheduleTypes, ...$scheduleParams);
  }
  if ($stmtSchedules->execute()) {
    $resultSchedules = $stmtSchedules->get_result();
    $scheduleRows = $resultSchedules ? $resultSchedules->fetch_all(MYSQLI_ASSOC) : [];
  }
  $stmtSchedules->close();
}
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
    <a href="admin_login.php" class="dashboard-logout">Logout</a>
  </aside>

  <main class="dashboard-main">
    <header class="dashboard-header">
      <div>
        <h1>Admin Dashboard</h1>
        <p>Oversee school-wide data, publish announcements, and curate the public website content.</p>
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
      <form class="dashboard-form" action="submit_announcement.php" method="POST">
        <label for="subject">Subject</label>
        <input type="text" name="subject" required>

        <label for="message">Message</label>
        <textarea name="message" required></textarea>

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
      <form class="dashboard-form" action="admin_addusers.php" method="POST">
        <label for="fullname">Full Name</label>
        <input type="text" name="fullname" required>

        <label for="username">Username</label>
        <input type="text" name="username" required>

        <label for="password">Password</label>
        <input type="password" name="password" required>

        <label for="role">Role</label>
        <select name="role" required>
          <option value="cashier">Cashier</option>
          <option value="registrar">Registrar</option>
        </select>

        <div class="dashboard-actions">
          <button type="submit" class="dashboard-btn">Add User</button>
        </div>
      </form>
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

      <hr style="margin:32px 0; border:none; border-top:1px solid #d4dce1;">

      <form class="dashboard-form" method="GET" action="#schedules">
      <div class="dashboard-grid two">
        <div>
          <label for="schedule_grade_filter">Grade Filter</label>
          <select name="schedule_grade_filter" id="schedule_grade_filter" onchange="this.form.submit()">
            <option value="">All</option>
            <?php foreach ($scheduleGradeOptions as $gradeOption):
              $sel = ($scheduleFilterGrade === $gradeOption) ? 'selected' : '';
            ?>
              <option value="<?= htmlspecialchars($gradeOption) ?>" <?= $sel ?>><?= htmlspecialchars($gradeOption) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="schedule_section_filter">Section Filter</label>
          <input type="text" name="schedule_section_filter" id="schedule_section_filter" value="<?= htmlspecialchars($scheduleFilterSection) ?>" placeholder="e.g., Section A" onchange="this.form.submit()">
        </div>
        <div>
          <label for="schedule_year_filter">School Year Filter</label>
          <input type="text" name="schedule_year_filter" id="schedule_year_filter" value="<?= htmlspecialchars($scheduleFilterYear) ?>" placeholder="e.g., 2024-2025" onchange="this.form.submit()">
        </div>
      </div>
      <div class="dashboard-actions">
        <a href="admin_dashboard.php#schedules" class="dashboard-btn secondary dashboard-btn--small">Clear Filters</a>
      </div>
      </form>

      <div class="table-responsive">
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
      ?>
        <details style="background:#fff; border:1px solid #d4dce1; border-radius:10px; padding:16px;">
          <summary style="cursor:pointer; font-weight:700; color:#145A32; margin-bottom:12px;"><?= htmlspecialchars($sectionLabel) ?></summary>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
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
