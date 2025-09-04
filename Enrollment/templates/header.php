<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? $page_title : 'Escuela de Sto. Rosario'; ?></title>
  <base href="/school/Enrollment/">

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
      background: url('/school/esrBanner.jpg') center/cover no-repeat;
      color: white;
      height: 700px; 
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
      color: #fbd80a;
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
      color: #fbd80a;
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
      color: #fbd80a;
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

    .portal-btn {
        color: #145A32 !important;
        background-color: #fbd80a;
        border: 2px solid #fbd80a;
        border-radius: 6px;
        padding: 4px 16px;
        margin: 0 5px;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        line-height: 1.2;
        vertical-align: baseline;
        position: relative;
        top: 4px;
    }

    .portal-btn:hover {
        background-color: #fbd80a;
        color: #145A32 !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(251, 216, 10, 0.3);
    }

  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #145A32;">
    <div class="container">
      <a class="navbar-brand d-flex fw-bold align-items-center" href="#home">
        <img src="../Esrlogo.png" width="40" height="40" class="me-2">
        ESCUELA DE STO. ROSARIO
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link fw-bold active" href="#home">Home</a></li>
          <li class="nav-item"><a class="nav-link fw-bold" href="#aboutus">About Us</a></li>
          <li class="nav-item"><a class="nav-link fw-bold" href="tuition_fees.php">Tuition Fees</a></li>
          <li class="nav-item"><a class="nav-link portal-btn fw-bold" href="student_login.php">Portal</a></li>
        </ul>
      </div>
    </div>
  </nav>
