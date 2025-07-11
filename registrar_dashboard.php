<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registrar Dashboard - Escuela De Sto. Rosario</title>
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

<header style = "position: relative;">
  <h1>Escuela De Sto. Rosario</h1>
  <p>Registrar Dashboard</p>
  <a href="registrar_login.php" class="logout-btn">Logout</a>
</header>

<div class="container">
  <h2>Registered Students</h2>

  <?php
include('db_connection.php');

$grade_filter = isset($_GET['grade_filter']) ? $_GET['grade_filter'] : '';
?>

<form method="GET" action="">
  <label for="grade_filter">Filter by Grade Level:</label>
  <select name="grade_filter" id="grade_filter" onchange="this.form.submit()">
    <option value="">-- All Grades --</option>
    <option value="preschool" <?= ($grade_filter == 'preschool') ? 'selected' : '' ?>>Preschool</option>
    <option value="k1" <?= ($grade_filter == 'k1') ? 'selected' : '' ?>>Kinder 1</option>
    <option value="k2" <?= ($grade_filter == 'k2') ? 'selected' : '' ?>>Kinder 2</option>
    <option value="g1" <?= ($grade_filter == 'g1') ? 'selected' : '' ?>>Grade 1</option>
    <option value="g2" <?= ($grade_filter == 'g2') ? 'selected' : '' ?>>Grade 2</option>
    <option value="g3" <?= ($grade_filter == 'g3') ? 'selected' : '' ?>>Grade 3</option>
    <option value="g4" <?= ($grade_filter == 'g4') ? 'selected' : '' ?>>Grade 4</option>
    <option value="g5" <?= ($grade_filter == 'g5') ? 'selected' : '' ?>>Grade 5</option>
    <option value="g6" <?= ($grade_filter == 'g6') ? 'selected' : '' ?>>Grade 6</option>
    <option value="g7" <?= ($grade_filter == 'g7') ? 'selected' : '' ?>>Grade 7</option>
    <option value="g8" <?= ($grade_filter == 'g8') ? 'selected' : '' ?>>Grade 8</option>
    <option value="g9" <?= ($grade_filter == 'g9') ? 'selected' : '' ?>>Grade 9</option>
    <option value="g10" <?= ($grade_filter == 'g10') ? 'selected' : '' ?>>Grade 10</option>
    <option value="g11" <?= ($grade_filter == 'g11') ? 'selected' : '' ?>>Grade 11</option>
    <option value="g12" <?= ($grade_filter == 'g12') ? 'selected' : '' ?>>Grade 12</option>
  </select>
</form>

<?php
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


if ($result->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Name</th><th>Grade Level</th><th>Actions</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row['id'] . "</td>
                <td>" . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . "</td>
                <td>" . htmlspecialchars($row['year']) . "</td>
                <td>
                <a href='edit_student.php?id=" . $row['id'] . "'>Edit</a> |
                <a href='delete_student.php?id=" . $row['id'] . "' onclick='return confirm(\"Are you sure?\")'>Delete</a>" .
                ($row['emailaddress'] ? " | <span style='color:gray;'>Activated</span>" :
                " | <a href='activate_student.php?id=" . $row['id'] . "' onclick='return confirm(\"Activate student account?\")'>Activate</a>") .
            "</td>";
                }
    echo "</table>";
} else {
    echo "<p>No enrolled students found.</p>";
}
$conn->close();
?>

</div>



</body>
</html>
