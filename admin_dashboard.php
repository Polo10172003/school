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
        <label><input type="checkbox" name="grades[]" value="everyone"> Everyone</label>
        <label><input type="checkbox" name="grades[]" value="preschool"> Pre-School</label>
        <label><input type="checkbox" name="grades[]" value="k1"> Kinder-1</label>
        <label><input type="checkbox" name="grades[]" value="k2"> Kinder-2</label>
        <label><input type="checkbox" name="grades[]" value="g1"> Grade-1</label>
        <label><input type="checkbox" name="grades[]" value="g2"> Grade-2</label>
        <label><input type="checkbox" name="grades[]" value="g3"> Grade-3</label>
        <label><input type="checkbox" name="grades[]" value="g4"> Grade-4</label>
        <label><input type="checkbox" name="grades[]" value="g5"> Grade-5</label>
        <label><input type="checkbox" name="grades[]" value="g6"> Grade-6</label>
        <label><input type="checkbox" name="grades[]" value="g7"> Grade-7</label>
        <label><input type="checkbox" name="grades[]" value="g8"> Grade-8</label>
        <label><input type="checkbox" name="grades[]" value="g9"> Grade-9</label>
        <label><input type="checkbox" name="grades[]" value="g10"> Grade-10</label>
        <label><input type="checkbox" name="grades[]" value="g11"> Grade-11</label>
        <label><input type="checkbox" name="grades[]" value="g12"> Grade-12</label>
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
  <?php
include('db_connection.php');

// Helper function for section counts
function getSectionCounts($conn, $grade) {
    $sections = [];

    if (in_array($grade, ['Kinder 1','Kinder 2'])) {
        $sections = ["Hershey" => ["count" => 0, "max" => 20],
                     "Kisses"  => ["count" => 0, "max" => 20]];
    } elseif (in_array($grade, ['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'])) {
        $sections = ["Section A" => ["count" => 0, "max" => 30],
                     "Section B" => ["count" => 0, "max" => 30]];
    } elseif (in_array($grade, ['Grade 7','Grade 8','Grade 9','Grade 10'])) {
        $sections = ["Section A" => ["count" => 0, "max" => 40],
                     "Section B" => ["count" => 0, "max" => 40]];
    } elseif (in_array($grade, ['Grade 11','Grade 12'])) {
        $sections = ["ABM - Section 1"   => ["count" => 0, "max" => 50],
                     "GAS - Section 1"   => ["count" => 0, "max" => 50],
                     "HUMMS - Section 1" => ["count" => 0, "max" => 50],
                     "ICT - Section 1"   => ["count" => 0, "max" => 50],
                     "TVL - Section 1"   => ["count" => 0, "max" => 50]];
    }

    foreach ($sections as $sec => $data) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM students_registration WHERE year = ? AND section = ?");
        $stmt->bind_param("ss", $grade, $sec);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

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
    $sql .= " AND year = ?";
    $params[] = $grade_filter;
    $types   .= "s";
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
          $grades = ["Kinder 1","Kinder 2","Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6",
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
