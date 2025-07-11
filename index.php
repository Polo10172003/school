<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Escuela De Sto. Rosario</title>
  <base href="/Enrollment/">

  <style>
    /* Reset some basics */
    * {
      box-sizing: border-box;
    }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      background-color: #f0f4f3;
      color: #333;
      line-height: 1.6;
    }
    a {
      text-decoration: none;
      color: inherit;
    }

    /* HEADER & NAV */
    header {
      background-color: #004d00;
      padding: 20px 0;
      text-align: center;
      color: white;
      position: sticky;
      top: 0;
      z-index: 999;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    header h1 {
      margin: 0;
      font-size: 2.4rem;
      font-weight: 700;
    }
    header p {
      margin: 4px 0 0;
      font-size: 1.1rem;
      font-weight: 300;
      letter-spacing: 1px;
    }

    nav {
      background-color: #007f3f;
      display: flex;
      justify-content: center;
      box-shadow: inset 0 -3px 0 #004d00;
    }
    nav ul {
      display: flex;
      margin: 0;
      padding: 0;
      list-style: none;
    }
    nav ul li {
      margin: 0 20px;
    }
    nav ul li a {
      display: block;
      padding: 15px 10px;
      color: white;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      transition: background-color 0.3s ease;
    }
    nav ul li a:hover,
    nav ul li a:focus {
      background-color: #004d00;
      border-radius: 5px;
    }

    /* HERO / BANNER */
    .banner {
      position: relative;
      background: url('Esrbanner.jpg') center/cover no-repeat;
      height: 400px;
      border-radius: 12px;
      margin: 30px auto;
      max-width: 1000px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .banner::after {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba(0, 77, 0, 0.5);
      border-radius: 12px;
    }
    .banner-text {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: #fff;
      font-size: 2.2rem;
      font-weight: 700;
      text-align: center;
      z-index: 1;
      padding: 0 20px;
      text-shadow: 0 2px 6px rgba(0,0,0,0.7);
    }

    /* CENTER BUTTON */
    .center-button {
      text-align: center;
      margin: 30px 0 50px;
    }
    .btn-primary {
      background-color: #007f3f;
      color: white;
      padding: 14px 28px;
      font-weight: 700;
      font-size: 1.2rem;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,127,63,0.4);
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      display: inline-block;
      cursor: pointer;
    }
    .btn-primary:hover,
    .btn-primary:focus {
      background-color: #004d00;
      box-shadow: 0 6px 14px rgba(0,77,0,0.7);
      outline: none;
    }

    /* MAIN CONTENT */
    .main-container {
      max-width: 1100px;
      margin: 0 auto 80px;
      display: flex;
      gap: 40px;
      padding: 0 20px;
      flex-wrap: wrap;
      justify-content: center;
    }
    .left, .right {
      background: white;
      border-radius: 12px;
      box-shadow: 0 0 16px rgba(0,0,0,0.08);
      padding: 30px;
      flex: 1 1 420px;
      min-width: 320px;
    }

    /* Contact logo & details */
    .contact-logo {
      display: flex;
      gap: 20px;
      margin-bottom: 30px;
      align-items: center;
    }
    .contact-logo img {
      width: 90px;
      border-radius: 10px;
      box-shadow: 0 0 8px rgba(0,127,63,0.3);
    }
    .contact-logo div p {
      margin: 6px 0;
      font-weight: 600;
      font-size: 1rem;
    }

    /* Section headings */
    h2 {
      color: #007f3f;
      border-bottom: 3px solid #007f3f;
      padding-bottom: 8px;
      margin-bottom: 18px;
      font-weight: 700;
      font-size: 1.8rem;
    }

    /* Paragraph text */
    p {
      font-size: 1rem;
      color: #444;
      margin-bottom: 20px;
    }

    /* Lists */
    ul {
      padding-left: 1.3rem;
      list-style: disc inside;
      margin-bottom: 25px;
    }
    ul li {
      margin-bottom: 12px;
      font-weight: 600;
      font-size: 1rem;
    }
    ul li a {
      color: #007f3f;
      font-weight: 700;
      transition: color 0.2s ease;
    }
    ul li a:hover {
      color: #004d00;
    }

    /* Admin Login Button */
    .btn-secondary {
      background-color: #004d00;
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 700;
      font-size: 1.1rem;
      display: inline-block;
      margin-top: 15px;
      transition: background-color 0.3s ease;
    }
    .btn-secondary:hover {
      background-color: #003300;
    }

    /* FOOTER */
    footer {
      background-color: #004d00;
      color: white;
      text-align: center;
      padding: 20px 10px;
      font-size: 0.9rem;
      letter-spacing: 0.05em;
      font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .main-container {
        flex-direction: column;
        padding: 0 15px;
      }
      nav ul {
        flex-direction: column;
      }
      nav ul li {
        margin: 8px 0;
      }
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
      <li><a href="#home" tabindex="1">Home</a></li>
      <li><a href="#about" tabindex="2">About Us</a></li>
      <li><a href="student_login.php" tabindex="3">Portal</a></li>
      <li><a href="tuition_fees.php" tabindex="4">Tuition Fees</a></li>
    </ul>
  </nav>

  <section class="banner" id="home" role="img" aria-label="School Banner Image">
    <div class="banner-text">
      Welcome to Escuela De Sto. Rosario<br />
      <small style="font-weight:400; font-size:1.1rem;">Shaping the Future, One Student at a Time</small>
    </div>
  </section>

  <div class="center-button">
    <a href="early_registration.php" class="btn-primary" tabindex="5">Early Registration</a>
  </div>

  <main class="main-container">

    <section class="left" id="about" tabindex="6">
      <div class="contact-logo">
        <img src="Esrlogo.png" alt="Escuela De Sto. Rosario Logo" />
        <div>
          <h2>Contact Details</h2>
          <p><strong>Escuela De Sto. Rosario</strong></p>
          <p>97 Dr. Sixto Antonio Ave., Rosario, Pasig City</p>
          <p>📞 Smart: (0969) 354-2870 / Globe: (0956) 351-2764</p>
          <p>📧 Email: esradmission@gmail.com</p>
          <p>🕒 Mon - Fri: 9:00 AM to 5:00 PM</p>
        </div>
      </div>

      <h2>Our Vision</h2>
      <p>
        To train and educate the children regardless of their religious affiliation in response to the needs of the times; hence, Sto. Rosarians education envision integrated moral and family values, social commitment, spiritual and physical growth, and in line with the development of Information Technology to become a better citizen of our nation.
      </p>

      <h2>Our Mission</h2>
      <p>
        To realize this vision, we commit ourselves to:
        <br /><br />
        The pursuit of excellence and socially relevant early childhood education; the recognition of the uniqueness and the potential of each individual; the inculcation of social awareness and responsibility; the promotion of Christian spirits in the context of a global family and continuous spiritual and moral conversation.
        <br /><br />
        We continuously strive to create an environment sustained by quality curricular and co-curricular education programs with the student at the core, enlivened by dedicated administrators, faculties, and staff, supported by involved parents, and equipped with adequate facilities.
      </p>
    </section>

    <section class="right" tabindex="7" aria-label="Courses offered section">
      <h2>Courses Offered</h2>
      <ul>
        <li>Senior High School (Grades 11 & 12)</li>
        <li>Junior High School (Grades 7 to 10)</li>
        <li>Grade School</li>
        <li>Pre-school</li>
      </ul>

      <h2>Admin Portal</h2>
      <p>Authorized personnel can login below to manage the system:</p>
      <a href="admin_login.php" class="btn-secondary" tabindex="8">Admin Login</a>
      <a href="registrar_login.php" class="btn-secondary"tabindex="8">Registrar Login</a>
      <a href="cashier_login.php" class="btn-secondary"tabindex="8">Cashier Login</a>

    </section>

  </main>

  <footer>
    &copy; <?php echo date('Y'); ?> Escuela De Sto. Rosario. All Rights Reserved.
  </footer>

</body>
</html>
