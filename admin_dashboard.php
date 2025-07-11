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
    }

    header {
      background-color: #004d00;
      color: white;
      text-align: center;
      padding: 20px 0;
      position: relative;
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

  <!-- Include TinyMCE -->
  <script src="tinymce_7.8.0_dev/tinymce/tinymce.min.js"></script>
  <script>
    tinymce.init({
      selector: 'textarea[name=message]',
      plugins: 'image link media code',
      toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | outdent indent | link image media | code',
      height: 400,
      images_upload_url: 'upload_image.php',
      automatic_uploads: true
    });
  </script>
</head>
<body>

<header>
  <h1>Escuela De Sto. Rosario</h1>
  <p>Admin Dashboard</p>
  <a href="admin_login.php" class="logout-btn">Logout</a>
</header>

<div class="container form-section">
  <h2>Post Announcement</h2>
  <form action="submit_announcement.php" method="POST">
      <label for="subject">Subject:</label>
      <input type="text" name="subject" required>

      <label for="message">Message:</label>
      <textarea name="message" required></textarea>

      <label for="grades">Send To:</label>
      <select name="grades[]" multiple required>
          <option value="everyone">Everyone</option>
          <option value="preschool">Pre-School</option>
          <option value="k1">Kinder-1</option>
          <option value="k2">Kinder-2</option>
          <option value="g1">Grade-1</option>
          <option value="g2">Grade-2</option>
          <option value="g3">Grade-3</option>
          <option value="g4">Grade-4</option>
          <option value="g5">Grade-5</option>
          <option value="g6">Grade-6</option>
          <option value="g7">Grade-7</option>
          <option value="g8">Grade-8</option>
          <option value="g9">Grade-9</option>
          <option value="g10">Grade-10</option>
          <option value="g11">Grade-11</option>
          <option value="g12">Grade-12</option>
      </select>

      <input type="submit" value="Send Announcement">
  </form>
</div>

</body>
</html>
