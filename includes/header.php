<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? $page_title : 'Escuela de Sto. Rosario'; ?></title>
  <base href="/Enrollment/">

  <!-- Bootstrap CSS & Icons -->
  <link href="assets/css/global.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>

<style>
    /* Jumbotron */
    .jumbotron {
      background: url('EsrBanner.jpg') center/cover no-repeat;
      color: white;
      height: 800px;  /* full screen height */
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

    .carousel-caption {
      bottom: 20px;  /* para laging may space sa baba */
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
    <div class="container-fluid">
      <a class="navbar-brand d-flex fw-bold align-items-center" href="#home">
        <img src="Esrlogo.png" width="40" height="40" class="me-2">
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
          <?php if (isset($_SESSION['student_email'])): ?>
              <!-- If logged in → show Logout -->
              <li class="nav-item"><a class="nav-link portal-btn fw-bold" href="Portal/logout.php">Logout</a></li>
          <?php else: ?>
              <!-- If not logged in → show Portal -->
              <li class="nav-item"><a class="nav-link portal-btn fw-bold" href="Portal/student_login.php">Portal</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <script>
    const navbar = document.querySelector('.navbar');
    let lastScroll = 0;
    const middlePoint = document.body.scrollHeight / 1; 

    window.addEventListener('scroll', () => {
      const currentScroll = window.scrollY;

      if (currentScroll > middlePoint) {
        // pag lagpas kalahati ng page, ipakita ulit
        navbar.classList.remove('hide');
        navbar.classList.add('animate__animated', 'animate__fadeInDown');
      } else {
        if (currentScroll > lastScroll) {
          // scroll pababa → hide (smooth fade)
          navbar.classList.add('hide');
          navbar.classList.remove('animate__fadeInDown');
        } else {
          // scroll paakyat → show with animation
          navbar.classList.remove('hide');
          navbar.classList.add('animate__animated', 'animate__fadeInDown');
        }
      }

      lastScroll = currentScroll;
    });

    // default visible pag load
    navbar.classList.remove('hide');
  </script>
</body>