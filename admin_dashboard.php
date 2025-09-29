<?php
include 'db_connection.php';

$scheduleMessage = '';
$scheduleError   = '';

$scheduleGradeOptions = [
  'Pre-Prime 1', 'Pre-Prime 2', 'Kindergarten',
  'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
  'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'
];

$scheduleDaysOfWeek = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

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
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f0f4f3;
      margin: 0;
      padding: 0;
      color: #333;
      display: flex;
    }

    /* Sidebar */
    .sidebar {
      width: 220px;
      background-color: #004d00;
      color: white;
      height: 100vh;
      padding-top: 30px;
      position: fixed;
      left: 0;
      top: 0;
      display: flex;
      flex-direction: column;
      transition: width 0.3s ease;
      overflow: hidden;
    }
    .sidebar.collapsed {
      width: 70px;
    }
    .sidebar h2 {
      text-align: center;
      margin-bottom: 20px;
      font-size: 18px;
      transition: opacity 0.3s ease;
    }
    .sidebar.collapsed h2 {
      opacity: 0;
      pointer-events: none;
    }
    .sidebar a {
      color: white;
      padding: 12px 20px;
      text-decoration: none;
      display: flex;
      align-items: center;
      font-weight: bold;
      transition: background-color 0.3s;
      white-space: nowrap;
    }
    .sidebar a:hover {
      background-color: #007f3f;
    }
    .sidebar i {
      margin-right: 10px;
      font-size: 18px;
      min-width: 20px;
      text-align: center;
    }
    .sidebar.collapsed a span {
      display: none;
    }

    /* Collapse toggle button */
    .toggle-btn {
      background-color: #003300;
      color: white;
      border: none;
      padding: 10px;
      cursor: pointer;
      font-size: 18px;
      width: 100%;
      transition: background-color 0.3s;
    }
    .toggle-btn:hover {
      background-color: #007f3f;
    }

    /* Main content */
    .main-content {
      margin-left: 240px;
      padding: 20px;
      flex-grow: 1;
      transition: margin-left 0.3s ease;
    }
    .sidebar.collapsed + .main-content {
      margin-left: 90px;
    }

    .logout-btn {
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
      margin: 10px auto;
      text-align: center;
      width: 80%;
    }
    .logout-btn:hover {
      background-color: #7a0000;
    }

    .container {
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

    input[type="submit"],
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

    input[type="submit"]:hover,
    button:hover {
      background-color: #004d00;
    }

    label {
      font-weight: bold;
      margin-top: 10px;
      display: block;
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
  <h2>Admin Panel</h2>
  <a href="#stats"><i>📊</i> <span>Statistics</span></a>
  <a href="#announcements"><i>📢</i> <span>Post Announcement</span></a>
  <a href="#users"><i>👥</i> <span>Add New User</span></a>
  <a href="#students"><i>🎓</i> <span>Manage Students</span></a>
  <a href="admin_login.php" class="logout-btn">Logout</a>
</div>

<!-- Main content -->
<div class="main-content">
  <div class="container" id="stats">
    <h2>Statistics</h2>
    <?php include 'statistics.php'; ?>
  </div>

  <div class="container" id="announcements">
    <h2>Post Announcement</h2>
    <form action="submit_announcement.php" method="POST">
      <label for="subject">Subject:</label>
      <input type="text" name="subject" required>

      <label for="message">Message:</label>
      <textarea name="message" required></textarea>

      <label for="grades">Send To:</label>
      <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top:10px;">
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
      <input type="submit" value="Send Announcement">
    </form>
  </div>

  <div class="container" id="users">
    <h2>Add New User</h2>
    <form action="admin_addusers.php" method="POST">
      <label for="fullname">Full Name:</label>
      <input type="text" name="fullname" required>

      <label for="username">Username:</label>
      <input type="text" name="username" required>

      <label for="password">Password:</label>
      <input type="password" name="password" required>

      <label for="role">Role:</label>
      <select name="role" required>
        <option value="cashier">Cashier</option>
        <option value="registrar">Registrar</option>
      </select>

      <input type="submit" value="Add User">
    </form>
  </div>
  <div class="container" id="class-schedules">
    <h2>Class Schedules</h2>
    <?php if ($scheduleMessage !== ''): ?>
      <div style="padding:12px 16px; border-radius:8px; background:#e8f5e9; color:#256029; margin-bottom:18px; font-weight:600;">
        <?= htmlspecialchars($scheduleMessage) ?>
      </div>
    <?php endif; ?>
    <?php if ($scheduleError !== ''): ?>
      <div style="padding:12px 16px; border-radius:8px; background:#fdecea; color:#c62828; margin-bottom:18px; font-weight:600;">
        <?= htmlspecialchars($scheduleError) ?>
      </div>
    <?php endif; ?>

    <form method="POST" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:18px;">
      <input type="hidden" name="schedule_form" value="1">
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
        <label style="margin-top:6px; font-weight:500;">
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
      <div style="grid-column: 1 / -1; text-align:right;">
        <button type="submit">Save Schedule</button>
      </div>
    </form>

    <hr style="margin:32px 0; border:none; border-top:1px solid #d4dce1;">

    <form method="GET" style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end; margin-bottom:18px;">
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
      <div>
        <a href="admin_dashboard.php#class-schedules" style="display:inline-block; margin-top:24px; text-decoration:none; background:#6c757d; color:#fff; padding:9px 16px; border-radius:8px;">Clear Filters</a>
      </div>
    </form>

    <div style="overflow-x:auto;">
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
  </div>
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
$result = $stmt->get_result();
?>

  <div class="container" id="students">
    <h2>Manage Students</h2>
<!-- Filters -->
<form method="GET" style="margin-bottom:20px; display:flex; gap:20px; align-items:center;">
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

    <!-- Students Table -->
    <table>
      <tr>
        <th>ID</th>
        <th>Student Number</th>
        <th>Name</th>
        <th>Grade</th>
        <th>Section</th>
        <th>Adviser</th>
        <th>Status</th>
      </tr>
      <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['student_number']) ?></td>
        <td><?= htmlspecialchars($row['firstname'] . " " . $row['lastname']) ?></td>
        <td><?= htmlspecialchars($row['year']) ?></td>
        <td><?= htmlspecialchars($row['section'] ?: "Unassigned") ?></td>
        <td><?= htmlspecialchars($row['adviser'] ?: "TBA") ?></td>
        <td><?= htmlspecialchars($row['academic_status']) ?></td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>
</div>

<script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
  }
</script>

</body>
</html>
