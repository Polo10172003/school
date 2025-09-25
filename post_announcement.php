<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Post Announcement</title>
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
    }

    .container {
      width: 90%;
      max-width: 700px;
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

    input[type="submit"] {
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

    input[type="submit"]:hover {
      background-color: #004d00;
    }

    .center-button {
      text-align: center;
      margin-top: 20px;
    }

    .manage-button {
      display: inline-block;
      background-color: #007f3f;
      color: white;
      padding: 12px 24px;
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
  </style>
<script src="tinymce_7.8.0_dev/tinymce/tinymce.min.js"></script>
<script>
tinymce.init({
  selector: 'textarea[name=message]',
  plugins: 'image link media code',
  toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | outdent indent | link image media | code',
  height: 400,
  // Optional: to allow image upload handler (we'll discuss this later)
  images_upload_url: 'upload_image.php',
  automatic_uploads: true
});
</script>



</head>
<body>

<header>
  <h1>Escuela De Sto. Rosario</h1>
  <p>Post an Announcement</p>
</header>

<div class="center-button">
  <a href="admin_dashboard.php" class="manage-button">Back to Dashboard</a>
</div>

<div class="container">
  <h2>Send Announcement</h2>
  <form action="submit_announcement.php" method="POST">
      <label for="subject">Subject:</label>
      <input type="text" name="subject" required>

    <label for="message">Message:</label><br>
    <textarea name="message" required></textarea><br><br>


      <label for="grades">Send To:</label>
      <select name="grades[]" multiple required>
          <option value="everyone">Everyone</option>
          <option value="preschool">Pre-School</option>
          <option value="pp1">Pre-Prime 1</option>
          <option value="pp2">Pre-Prime 2</option>
          <option value="pp12">Pre-Prime 1 &amp; 2</option>
          <option value="kg">Kindergarten</option>
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
