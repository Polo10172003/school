<?php
include('db_connection.php');
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
  
  <!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="UTF-8">
  <title>Registrar Dashboard - Escuela De Sto. Rosario</title>
  <style>
  /* Your CSS here */
  </style>
  </head>
  <body>
  <div class="container">
  <h2>Registered Students</h2>
  
  <?php if ($result && $result->num_rows > 0): ?>
  <table>
  <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Grade Level</th>
      <th>Academic Status</th>
      <th>Actions</th>
  </tr>
  <?php while ($row = $result->fetch_assoc()): ?>
  <tr>
      <td><?= $row['id'] ?></td>
      <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
      <td><?= htmlspecialchars($row['year']) ?></td>
      <td>
          <?php 
          // If Grade 12 and Passed → show Graduated
          if ($row['year'] === 'Grade 12' && $row['academic_status'] === 'Passed') {
              echo '<span class="badge bg-success">Graduated</span>';
          } else {
              echo !empty($row['academic_status']) ? htmlspecialchars($row['academic_status']) : 'Pending';
          }
          ?>
      </td>
      <td>
    <!-- Edit / Delete -->
    <a href="edit_student.php?id=<?= $row['id'] ?>">Edit</a> |
    <a href="delete_student.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>

    <!-- Activation Status -->
    <?php if (!empty($row['emailaddress'])): ?>
        | <span style="color:gray;">Activated</span>
    <?php else: ?>
        | <a href="activate_student.php?id=<?= $row['id'] ?>" onclick="return confirm('Activate student account?')">Activate</a>
    <?php endif; ?>

    <!-- Academic Status / Update Link -->
    <?php if ($row['academic_status'] === 'Graduated'): ?>
        | <span class="badge bg-success">Graduated</span>
    <?php else: ?>
        | <a href="update_student_status.php?id=<?= $row['id'] ?>">Update Status</a>
    <?php endif; ?>
</td>

  </tr>
  <?php endwhile; ?>
  </table>
  <?php else: ?>
  <p>No enrolled students found.</p>
  <?php endif; ?>
  
  <?php $conn->close(); ?>
  </div>
  </body>
  </html>