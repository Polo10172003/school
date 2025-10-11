<?php
require_once __DIR__ . '/../includes/session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Successful - Escuela De Sto. Rosario</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f0f4f3;
      color: #333;
    }

    header {
      background: #004d00;
      color: white;
      padding: 15px 0;
      text-align: center;
    }

    nav {
      background: #007f3f;
      text-align: center;
    }

    nav ul {
      list-style: none;
      margin: 0;
      padding: 0;
    }

    nav ul li {
      display: inline-block;
      margin: 0 15px;
    }

    nav ul li a {
      color: white;
      text-decoration: none;
      font-weight: bold;
      padding: 12px 15px;
      display: inline-block;
      transition: background 0.3s ease;
    }

    nav ul li a:hover {
      background-color: #004d00;
      border-radius: 5px;
    }

    .banner {
      text-align: center;
      margin-top: 20px;
    }

    .banner img {
      width: 100%;
      max-width: 1000px;
      height: auto;
      border-radius: 10px;
    }

    .success-box {
      background: white;
      max-width: 600px;
      margin: 40px auto;
      padding: 30px;
      text-align: center;
      box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
      border-radius: 12px;
    }

    .success-box h2 {
      color: #007f3f;
      margin-bottom: 20px;
    }

    .success-box p {
      font-size: 18px;
      margin-bottom: 20px;
    }

    .success-box img {
      width: 80px;
      height: 80px;
      margin-bottom: 20px;
    }

    .btn-primary {
      background-color: #007f3f;
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
      display: inline-block;
      margin-top: 10px;
      transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #004d00;
    }

    footer {
      background: #004d00;
      color: white;
      text-align: center;
      padding: 15px 0;
      margin-top: 50px;
    }
  </style>
</head>
<body>

<header>
  <h1>Escuela De Sto. Rosario</h1>
  <p>Official School Website</p>
</header>

<nav>
  <ul>
    <li><a href="<?php echo APP_BASE_PATH; ?>index.php">Home</a></li>
    <li><a href="#about">About Us</a></li>
    <li><a href="<?php echo APP_BASE_PATH; ?>Portal/student_login.php">Portal</a></li>
    <li><a href="tuition_fees.php">Tuition Fees</a></li>
  </ul>
</nav>

<div class="banner">
  <img src="esrBanner.jpg" alt="ESR Banner">
</div>

<div class="success-box">
  <img src="success_icon.png" alt="Success Icon">
  <h2>Payment Submitted Successfully!</h2>
  <p>Thank you for your payment. Your transaction is now being reviewed by our finance department.</p>
  <p>Please keep your reference number and wait for confirmation via SMS or Email.</p>
  <a href="<?php echo APP_BASE_PATH; ?>index.php" class="btn-primary">Return to Home</a>
</div>

<footer>
  &copy; 2025 Escuela De Sto. Rosario. All Rights Reserved.
</footer>

</body>
</html>
