<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Escuela de Sto. Rosario</title>
  <base href="/school-main/Enrollment/">

  <!-- Bootstrap CSS & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      font-family: Arial, sans-serif;
    }

    /* Navbar */
    .navbar {
      position: sticky;
      top: 0;
      z-index: 1020; 
      background-color: transparent;
      opacity: 0.95;
      transition: opacity 0.5s ease, background-color 0.5s ease;
    }

    /* Fade effect kapag nag-scroll */
    .navbar.scrolled {
      opacity: 1;
      background-color: #145A32 !important; 
    }
      
    /* Jumbotron */
    .jumbotron {
      background: url('../esrBanner.jpg') center/cover no-repeat;
      color: white;
      height: 700px;  /* full screen height */
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .jumbotron {
      width: 100vw;
      margin-left: calc(50% - 50vw);
      margin-right: calc(50% - 50vw);
    }

    .jumbotron:hover {
      opacity: 0.9;
      transform: scale(1.02);
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    /* Add click effect */
    .jumbotron:active {
      transform: scale(0.98);
      transition: transform 0.1s ease;
    }

    .jumbotron p {
      background: rgba(0,0,0,0.6);
      display: inline-block;
      padding: 15px 25px;
      border-radius: 8px;
      font-size: 1.5rem;
      font-weight: 500;
    }

    /* Footer */
    .footer {
      background-color: #145A32; /* green theme */
      color: #fff;
      font-size: 14px;
      margin-top: 3rem;
    }

    /* General footer links (Helpdesk + Navigation) */
    .footer a {
      color: #ffffff;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: color 0.3s ease;
    }

    .footer a:hover {
      color: #f1c40f;
      text-decoration: underline;
    }
    .footer p a {
      display: inline;      /* gawin siyang inline, hindi block */
      margin-left: 5px;     /* konting pagitan sa text */
    }

    /* Social Media specific hover (icon + text underline + color) */
    .footer .list-unstyled li a {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: color 0.3s ease;
    }

    .footer .list-unstyled li a:hover {
      color: #f1c40f;
      text-decoration: underline;
    }

    .footer h5, .footer h6 {
      margin-bottom: 15px;
    }

    .footer-bottom {
      font-size: 13px;
      color: #ccc;
    }

    .footer-bottom .privacy-link {
      color: #f1c40f;
    }

    .footer-bottom .privacy-link:hover {
      text-decoration: underline;
    }

    /* About Us Tab Buttons - Match School Theme */
    .nav-pills .nav-link {
      color: #145A32;
      background-color: transparent;
      border: 2px solid #145A32;
      margin: 0 5px;
      transition: all 0.3s ease;
    }

    .nav-pills .nav-link:hover {
      background-color: #145A32;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(20, 90, 50, 0.3);
    }

    .nav-pills .nav-link.active {
      background-color: #145A32;
      color: white;
      border-color: #145A32;
      box-shadow: 0 4px 8px rgba(20, 90, 50, 0.3);
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #145A32;">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="#home">
        <img src="../Esrlogo.png" width="40" height="40" class="me-2">
        ESCUELA DE STO. ROSARIO
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#aboutus">About Us</a></li>
          <li class="nav-item"><a class="nav-link" href="tuition_fees.php">Tuition Fees</a></li>
          <li class="nav-item"><a class="nav-link" href="student_login.php">Portal</a></li>
        </ul>
      </div>
    </div>
  </nav>
  <!-- Jumbotron -->
  <div id="home">
    <a href="early_registration.php" style="text-decoration: none; color: inherit;">
      <div class="jumbotron">
        <div class="text-center">
          <h class="display-4">Welcome to Escuela de Sto. Rosario</h>
          <h1 class="lead">Quality education rooted in values and excellence.</h1>
          <p class="early">Early Registration | SY: 2026-2027</p>
        </div>
      </div>
    </a>
  </div>

    <!-- Sample Content -->
    <div class="container mt-5">
      <div class="row">
        <div class="col-md-4">
          <img src="../esrBanner.jpg" alt="School Banner" class="img-fluid mb-3" style="max-height: 250px; object-fit: cover;">
          <h5>Programs</h5>
          <p>Explore our wide range of academic programs designed to prepare students for the future.</p>
        </div>
        <div class="col-md-4">
          <img src="../Esrlogo.png" alt="School Logo" class="img-fluid mb-3" style="max-height: 250px; object-fit: cover;">
          <h5>Admissions</h5>
          <p>Join our community and take the first step towards a brighter future with us.</p>
        </div>
        <div class="col-md-4">
          <img src="../Esr_banner.jpg" alt="School Campus" class="img-fluid mb-3" style="max-height: 250px; object-fit: cover;">
          <h5>Campus Life</h5>
          <p>Experience a vibrant and supportive campus life that nurtures learning and growth.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- About Us Section -->
  <div id="aboutus" class="container py-5">
    <h2 class="text-center mb-4">About Us</h2>

    <!-- Sub-nav (like pagination for Vision & Mission) -->
    <ul class="nav nav-pills justify-content-center mb-4" id="aboutTabs" role="tablist">
      <li class="nav-item">
        <button class="nav-link active" id="vision-tab" data-bs-toggle="tab" data-bs-target="#vision" type="button" role="tab">Our Vision</button>
      </li>
      <li class="nav-item">
        <button class="nav-link" id="mission-tab" data-bs-toggle="tab" data-bs-target="#mission" type="button" role="tab">Our Mission</button>
      </li>
    </ul>

    <!-- Content -->
    <div class="tab-content mx-auto text-center" id="aboutTabsContent" style="max-width: 950px;">
      <div class="tab-pane fade show active" id="vision" role="tabpanel">
        <h3 class="text-success">Our Vision</h3>
        <p>
         To train and educate the children regardless of their religious affiliation in response to the needs of the times; hence, Sto. Rosarians education envision integrated moral and family values, social commitment, spiritual and physical growth, and in line with the development of Information Technology to become a better citizen of our nation.
        </p>
      </div>
      <div class="tab-pane fade" id="mission" role="tabpanel">
        <h3 class="text-success">Our Mission</h3>
        <p>
          To realize this vision, we commit ourselves to:
          <br /><br />
          The pursuit of excellence and socially relevant early childhood education; the recognition of the uniqueness and the potential of each individual; the inculcation of social awareness and responsibility; the promotion of Christian spirits in the context of a global family and continuous spiritual and moral conversation.
          <br /><br />
          We continuously strive to create an environment sustained by quality curricular and co-curricular education programs with the student at the core, enlivened by dedicated administrators, faculties, and staff, supported by involved parents, and equipped with adequate facilities.
        </p>    
      </div>
    </div>
  </div> 
  <!-- Footer -->
  <footer class="footer mt-5">
    <div class="container py-4">
      <div class="row">
        <!-- School Info -->
        <div class="col-md-4 mb-3">
          <h5 class="fw-bold text-warning">Escuela de Sto. Rosario</h5>
          <p><i class="bi bi-geo-alt"></i> Location: 97 Dr. Sixto Antonio Ave., Rosario, Pasig City</p>
          <p><i class="bi bi-telephone"></i> Phone: Smart: (0969)354-2870 / Globe: (0956)351-2764</p>
          <p><i class="bi bi-info-circle"></i> Helpdesk: <a href="#">Click here</a></p>
        </div>

        <!-- Navigation -->
        <div class="col-md-4 mb-3">
          <h6 class="fw-bold text-warning">Navigation</h6>
          <ul class="list-unstyled">
            <li><a href="#">Careers</a></li>
            <li><a href="#">Company Disclosures</a></li>
            <li><a href="#">Alternative Payment Service</a></li>
            <li><a href="#">Campuses</a></li>
          </ul>
        </div>

        <!-- Social Media -->
        <div class="col-md-4 mb-3">
          <h6 class="fw-bold text-warning">Get Regular Updates</h6>
          <ul class="list-unstyled">
            <li><a href="#"><i class="bi bi-facebook"></i> Facebook</a></li>
            <li><a href="#"><i class="bi bi-youtube"></i> YouTube</a></li>
            <li><a href="#"><i class="bi bi-instagram"></i> Instagram</a></li>
            <li><a href="#"><i class="bi bi-twitter-x"></i> Twitter/X</a></li>
            <li><a href="#"><i class="bi bi-tiktok"></i> TikTok</a></li>
          </ul>
        </div>

      <!-- Bottom Bar -->
      <div class="footer-bottom d-flex justify-content-between pt-3 mt-3 border-top">
        <span>© 2025 Escuela de Sto. Rosario. All rights reserved.</span>
        <a href="privacy.php" class="privacy-link">Privacy Policy</a>
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
      window.addEventListener("scroll", function() {
      const navbar = document.querySelector(".navbar");
      if (window.scrollY > 50) {
        navbar.classList.add("scrolled");
      } else {
        navbar.classList.remove("scrolled");
      }
    });
  </script>
</body>
</html>
