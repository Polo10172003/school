<?php
// Set page title
$page_title = 'Escuela de Sto. Rosario - Home';

  include 'includes/header.php';
  require_once __DIR__ . '/includes/homepage_images.php';
  $homepageImages = homepage_images_load();
?> 

<style>
  :root {
    --brand-green: #145A32;
    --brand-yellow: #fbd80a;
    --brand-sage: #e7f3ec;
  }

  .hero-banner {
    background-image: linear-gradient(rgba(20, 90, 50, 0.75), rgba(20, 90, 50, 0.75)), url('<?= htmlspecialchars($homepageImages['cards']['campus']); ?>');
    background-size: cover;
    background-position: center;
    position: relative;
  }

  .hero-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.9rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.15);
    font-weight: 600;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    font-size: 0.85rem;
  }

  .hero-lead {
    font-size: 1.15rem;
    max-width: 560px;
  }

  .hero-actions .btn {
    min-width: 220px;
    border-radius: 999px;
    padding: 0.75rem 1.75rem;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
  }

  .hero-actions .btn-outline-light {
    border-width: 2px;
  }

  .hero-badges .badge {
    background: rgba(255, 255, 255, 0.9);
    color: var(--brand-green);
    font-weight: 600;
    border-radius: 10px;
    padding: 0.6rem 1.1rem;
  }

  .hero-card img {
    height: 240px;
    object-fit: cover;
  }

  .hero-card .card-body {
    padding: 1.75rem;
  }

  .info-card img {
    height: 190px;
    object-fit: cover;
    border-radius: 1rem 1rem 0 0;
  }

  .info-card .card-body {
    padding: 1.75rem;
  }

  .feature-card {
    background: var(--brand-sage);
    border: none;
    border-radius: 1.25rem;
    padding: 1.75rem;
    height: 100%;
    box-shadow: 0 12px 30px rgba(20, 90, 50, 0.08);
  }

  .feature-card i {
    font-size: 2.2rem;
    color: var(--brand-green);
  }

  .staff-access {
    background: linear-gradient(120deg, rgba(20, 90, 50, 0.95), rgba(39, 174, 96, 0.9));
    border-radius: 1.75rem;
    color: #fff;
    padding: 2.5rem;
  }

  .staff-btn {
    border-radius: 999px;
    padding: 0.75rem 1.75rem;
    font-weight: 600;
    background: #fff;
    color: var(--brand-green);
    border: none;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.15);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .staff-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.18);
    color: var(--brand-green);
  }

  .soft-section {
    background: #fff;
    border-radius: 2rem;
    box-shadow: 0 20px 40px rgba(20, 90, 50, 0.08);
    padding: 3rem 2rem;
  }

  .section-label {
    font-size: 0.85rem;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: var(--brand-green);
    font-weight: 700;
  }
</style>

<main>
  <section class="hero-banner text-white py-5" id="home">
    <div class="container py-5">
      <div class="row align-items-center">
        <div class="col-lg-7">
          <span class="hero-kicker"><i class="bi bi-stars"></i> Student & Parent Hub</span>
          <h1 class="display-4 fw-bold mt-4">Learning with heart, growing with family.</h1>
          <p class="hero-lead mt-3">Escuela de Sto. Rosario partners with parents to celebrate every learner’s progress—from their first classroom experience to senior high milestones.</p>
          <div class="d-flex flex-wrap gap-3 mt-4 hero-actions">
            <a class="btn btn-warning btn-lg text-dark fw-semibold" href="StudentNoVerification/student_number.php">Start Early Registration</a>
            <a class="btn btn-outline-light btn-lg fw-semibold" href="#family-explore">Explore School Life</a>
          </div>
          <div class="hero-badges d-flex flex-wrap gap-2 mt-4">
            <span class="badge">Kinder to Senior High</span>
            <span class="badge">Family-first Support</span>
            <span class="badge">Guided Enrollment Steps</span>
          </div>
        </div>
        <div class="col-lg-5 mt-5 mt-lg-0">
          <div class="hero-card card border-0 shadow-lg">
            <img src="<?= htmlspecialchars($homepageImages['events']['slide1']); ?>" alt="Students on campus" class="card-img-top">
            <div class="card-body">
              <h5 class="fw-bold text-success">Welcome, parents and guardians!</h5>
              <p class="text-muted mb-0">Check schedules, tuition details, and announcements anytime through our online portal to stay in step with your child’s journey.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-5 bg-light" id="family-explore">
    <div class="container">
      <div class="text-center mb-5">
        <span class="section-label">Family Essentials</span>
        <h2 class="fw-bold text-success mt-3">Everything you need for a confident start</h2>
        <p class="lead text-muted">Quick guides for exploring programs, admissions, and day-to-day life at ESR.</p>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card info-card border-0 shadow-sm h-100">
            <img src="<?= htmlspecialchars($homepageImages['cards']['programs']); ?>" alt="Academic programs" class="w-100">
            <div class="card-body">
              <h5 class="fw-bold text-success">Academic Programs</h5>
              <p class="text-muted">From preschool to senior high, our programs are tailored to each stage of your child’s growth.</p>
              <a href="tuition_fees.php" class="fw-semibold text-decoration-none" style="color: var(--brand-green);">View program tracks <i class="bi bi-arrow-right-short"></i></a>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card info-card border-0 shadow-sm h-100">
            <img src="<?= htmlspecialchars($homepageImages['cards']['admissions']); ?>" alt="Admissions" class="w-100">
            <div class="card-body">
              <h5 class="fw-bold text-success">Admissions Checklist</h5>
              <p class="text-muted">Follow easy steps for document submission, interviews, and payment options.</p>
              <a href="StudentNoVerification/early_registration.php" class="fw-semibold text-decoration-none" style="color: var(--brand-green);">See enrollment steps <i class="bi bi-arrow-right-short"></i></a>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card info-card border-0 shadow-sm h-100">
            <img src="<?= htmlspecialchars($homepageImages['cards']['campus']); ?>" alt="Campus life" class="w-100">
            <div class="card-body">
              <h5 class="fw-bold text-success">Life on Campus</h5>
              <p class="text-muted">Discover clubs, facilities, and activities that keep students inspired daily.</p>
              <a href="#highlights" class="fw-semibold text-decoration-none" style="color: var(--brand-green);">Tour our highlights <i class="bi bi-arrow-right-short"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-5">
    <div class="container soft-section">
      <div class="row g-4 align-items-center">
        <div class="col-lg-4">
          <div class="feature-card text-center">
            <i class="bi bi-backpack-fill"></i>
            <h5 class="fw-bold mt-3">Guided Enrollment Support</h5>
            <p class="text-muted mb-0">Our registrar team walks new families through every requirement with friendly assistance.</p>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="feature-card text-center">
            <i class="bi bi-people-fill"></i>
            <h5 class="fw-bold mt-3">Community-first Culture</h5>
            <p class="text-muted mb-0">Parents, guardians, and teachers collaborate closely to keep every learner thriving.</p>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="feature-card text-center">
            <i class="bi bi-mortarboard-fill"></i>
            <h5 class="fw-bold mt-3">Future-ready Learning</h5>
            <p class="text-muted mb-0">Modern facilities and values-based education help students discover their purpose.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="staff-access text-center shadow-lg">
        <span class="section-label text-white">Staff Access</span>
        <h2 class="fw-bold mt-3">Log in to your workspace</h2>
        <p class="mb-4">Dedicated portals for our cashier, registrar, and admin teams keep operations running smoothly.</p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
          <a class="staff-btn" href="Cashier/cashier_login.php">Cashier Login</a>
          <a class="staff-btn" href="Registrar/registrar_login.php">Registrar Login</a>
          <a class="staff-btn" href="admin_login.php">Admin Login</a>
        </div>
      </div>
    </div>
  </section>

  <div id="aboutus" class="container mb-4 pb-5">
    <p><!-- Invisible spacer to adjust scroll position for About Us tab --></p>
  </div>

    <!-- About Us Section -->
    <div class="container py-5">
      <h2 class="text-center mb-4 fw-bold">About Us</h2>
      <ul class="nav nav-pills justify-content-center mb-4" id="aboutTabs" role="tablist">
        <li class="nav-item">
          <button class="nav-link fw-bold active" id="vision-tab" data-bs-toggle="tab" data-bs-target="#vision" type="button" role="tab">Our Vision</button>
        </li>
        <li class="nav-item">
          <button class="nav-link fw-bold" id="mission-tab" data-bs-toggle="tab" data-bs-target="#mission" type="button" role="tab">Our Mission</button>
        </li>
      </ul>
      <div class="tab-content mx-auto text-center" id="aboutTabsContent" style="max-width: 950px;">
        <div class="tab-pane fade show active" id="vision" role="tabpanel">
          <h3 class="text-success fw-bold">Our Vision</h3>
          <p>To train and educate the children regardless of their religious affiliation in response to the needs of the times; hence, Sto. Rosarians education envision integrated moral and family values, social commitment, spiritual and physical growth, and in line with the development of Information Technology to become a better citizen of our nation.
          </p>
        </div>
        <div class="tab-pane fade" id="mission" role="tabpanel">
          <h3 class="text-success fw-bold">Our Mission</h3>
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

  <!-- Carousels Section -->

  <div id="highlights" class="container pb-5">
      <h2 class="text-center mb-5 fw-bold">School Highlights</h2>
      
      <!-- School Events Carousel -->
      <div class="mb-5">
        <h4 class="text-center mb-3 fw-bold text-success">School Events</h4>
        <div id="eventsCarousel" class="carousel carousel-dark slide" data-bs-ride="carousel">
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#eventsCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#eventsCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#eventsCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
          </div>
          <div class="carousel-inner">
            <div class="carousel-item active" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['events']['slide1']); ?>" class="d-block" alt="School Event 1" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Annual School Fair</h5>
                <p>Join us for our exciting annual school fair with games, food, and entertainment.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['events']['slide2']); ?>" class="d-block" alt="School Event 2" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Graduation Ceremony</h5>
                <p>Celebrating the achievements of our graduating students.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['events']['slide3']); ?>" class="d-block" alt="School Event 3" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Sports Festival</h5>
                <p>Annual sports competition showcasing student athletic talents.</p>
              </div>
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#eventsCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#eventsCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>

      <!-- School Achievements Carousel -->
      <div class="mb-5">
        <h4 class="text-center mb-3 fw-bold text-success">School Achievements</h4>
        <div id="achievementsCarousel" class="carousel carousel-dark slide" data-bs-ride="carousel">
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#achievementsCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#achievementsCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#achievementsCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
          </div>
          <div class="carousel-inner">
            <div class="carousel-item active" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['achievements']['slide1']); ?>" class="d-block" alt="Achievement 1" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Academic Excellence Award</h5>
                <p>Recognized for outstanding academic performance and student achievement.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['achievements']['slide2']); ?>" class="d-block" alt="Achievement 2" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Best Private School</h5>
                <p>Awarded as one of the top private educational institutions in the region.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['achievements']['slide3']); ?>" class="d-block" alt="Achievement 3" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Innovation in Education</h5>
                <p>Recognized for implementing innovative teaching methods and technology.</p>
              </div>
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#achievementsCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#achievementsCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>

      <!-- Primary & Secondary Education Carousel -->
      <div class="mb-5">
        <h4 class="text-center mb-3 fw-bold text-success">Primary & Secondary Education</h4>
        <div id="primarySecondaryCarousel" class="carousel carousel-dark slide" data-bs-ride="carousel">
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#primarySecondaryCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#primarySecondaryCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#primarySecondaryCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
          </div>
          <div class="carousel-inner">
            <div class="carousel-item active" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['primary_secondary']['slide1']); ?>" class="d-block" alt="Primary Education" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Primary Education (K-6)</h5>
                <p>Building strong foundations in reading, writing, and mathematics for young learners.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['primary_secondary']['slide2']); ?>" class="d-block" alt="Secondary Education" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Secondary Education (7-10)</h5>
                <p>Comprehensive curriculum preparing students for higher education and life skills.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['primary_secondary']['slide3']); ?>" class="d-block" alt="Learning Environment" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Modern Learning Environment</h5>
                <p>State-of-the-art facilities and technology-enhanced classrooms.</p>
              </div>
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#primarySecondaryCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#primarySecondaryCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>

      <!-- Junior High School Carousel -->
      <div class="mb-5">
        <h4 class="text-center mb-3 fw-bold text-success">Junior High School</h4>
        <div id="juniorHighCarousel" class="carousel carousel-dark slide" data-bs-ride="carousel">
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#juniorHighCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#juniorHighCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#juniorHighCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
          </div>
          <div class="carousel-inner">
            <div class="carousel-item active" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['junior_high']['slide1']); ?>" class="d-block" alt="JHS Grade 7-8" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Grades 7-8 Program</h5>
                <p>Transitioning students with enhanced academic and social development programs.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['junior_high']['slide2']); ?>" class="d-block" alt="JHS Grade 9-10" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Grades 9-10 Program</h5>
                <p>Advanced curriculum preparing students for senior high school specialization.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['junior_high']['slide3']); ?>" class="d-block" alt="JHS Activities" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Extracurricular Activities</h5>
                <p>Diverse clubs, sports, and leadership opportunities for holistic development.</p>
              </div>
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#juniorHighCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#juniorHighCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>

      <!-- Senior High School Carousel -->
      <div class="mb-5">
        <h4 class="text-center mb-3 fw-bold text-success">Senior High School</h4>
        <div class="row">
        
        <div id="seniorHighCarousel" class="carousel carousel-dark slide" data-bs-ride="carousel">
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#seniorHighCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#seniorHighCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#seniorHighCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
          </div>
          <div class="carousel-inner">
            <div class="carousel-item active" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['senior_high']['slide1']); ?>" class="d-block" alt="STEM Track" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>STEM Track</h5>
                <p>Science, Technology, Engineering, and Mathematics specialization for future innovators.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['senior_high']['slide2']); ?>" class="d-block" alt="ABM Track" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>ABM Track</h5>
                <p>Accountancy, Business, and Management program for future business leaders.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="<?= htmlspecialchars($homepageImages['senior_high']['slide3']); ?>" class="d-block" alt="HUMSS Track" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>HUMSS Track</h5>
                <p>Humanities and Social Sciences for students pursuing liberal arts and social studies.</p>
              </div>
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#seniorHighCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#seniorHighCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Additional Content Carousels - 3 Columns Horizontally -->
    <div class="container mt-5">
      <h2 class="text-center mb-5 fw-bold">School Recognition & Achievements</h2>
      
      <div class="row">
        <!-- Column 1: PAPRISA Carousel -->
        <div class="col-md-4 mb-4">
          <h4 class="text-center mb-3 fw-bold text-success">PAPRISA</h4>
          <div id="paprisaCarousel" class="carousel carousel-dark slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
              <button type="button" data-bs-target="#paprisaCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
              <button type="button" data-bs-target="#paprisaCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
              <button type="button" data-bs-target="#paprisaCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
              <div class="carousel-item active" data-bs-interval="5000">
                <img src="<?= htmlspecialchars($homepageImages['paprisa']['slide1']); ?>" class="d-block w-100" alt="PAPRISA Membership" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>PAPRISA Membership</h5>
                  <p>Philippine Association of Private Schools and Administrators recognition and membership programs.</p>
                </div>
              </div>
              <div class="carousel-item" data-bs-interval="5000">
                <img src="<?= htmlspecialchars($homepageImages['paprisa']['slide2']); ?>" class="d-block w-100" alt="PAPRISA Awards" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>PAPRISA Awards</h5>
                  <p>Recognized for excellence in private education administration and management.</p>
                </div>
              </div>
              <div class="carousel-item" data-bs-interval="5000">
                <img src="<?= htmlspecialchars($homepageImages['paprisa']['slide3']); ?>" class="d-block w-100" alt="PAPRISA Programs" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>PAPRISA Programs</h5>
                  <p>Active participation in PAPRISA educational programs and professional development initiatives.</p>
                </div>
              </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#paprisaCarousel" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#paprisaCarousel" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
          </div>
        </div>

        <!-- Column 2: Board Exam Passers Carousel -->
        <div class="col-md-4 mb-4">
          <h4 class="text-center mb-3 fw-bold text-success">Board Exam Passers</h4>
          <div id="boardExamCarousel" class="carousel carousel-dark slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
              <button type="button" data-bs-target="#boardExamCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
              <button type="button" data-bs-target="#boardExamCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
              <button type="button" data-bs-target="#boardExamCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
              <div class="carousel-item active" data-bs-interval="5000">
                <img src="<?= htmlspecialchars($homepageImages['board']['slide1']); ?>" class="d-block w-100" alt="Licensure Exams" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Professional Licensure</h5>
                  <p>Celebrating our alumni who have successfully passed various professional board examinations.</p>
                </div>
              </div>
              <div class="carousel-item" data-bs-interval="5000">
                <img src="<?= htmlspecialchars($homepageImages['board']['slide2']); ?>" class="d-block w-100" alt="Top Performers" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Top Performers</h5>
                  <p>Alumni who achieved top rankings in their respective professional licensure examinations.</p>
                </div>
              </div>
              <div class="carousel-item" data-bs-interval="5000">
                <img src="<?= htmlspecialchars($homepageImages['board']['slide3']); ?>" class="d-block w-100" alt="Career Success" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Career Success</h5>
                  <p>Our graduates excelling in their chosen professions and contributing to society.</p>
                </div>
              </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#boardExamCarousel" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#boardExamCarousel" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
          </div>
        </div>

        <!-- Column 3: Laudes Carousel -->
        <div class="col-md-4 mb-4">
          <h4 class="text-center mb-3 fw-bold text-success">Laudes</h4>
          <div id="laudesCarousel" class="carousel carousel-dark slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
              <button type="button" data-bs-target="#laudesCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
              <button type="button" data-bs-target="#laudesCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
              <button type="button" data-bs-target="#laudesCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
              <div class="carousel-item active" data-bs-interval="5000">
                <img src="<?= htmlspecialchars($homepageImages['laudes']['slide1']); ?>" class="d-block w-100" alt="Academic Honors" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Academic Honors</h5>
                  <p>Students who have achieved academic excellence and graduated with honors.</p>
                </div>
              </div>
              <div class="carousel-item" data-bs-interval="5000">
                <img src="<?= htmlspecialchars($homepageImages['laudes']['slide2']); ?>" class="d-block w-100" alt="Summa Cum Laude" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Summa Cum Laude</h5>
                  <p>Highest academic distinction awarded to students with exceptional academic performance.</p>
                </div>
              </div>
              <div class="carousel-item" data-bs-interval="5000">
                <img src="<?= htmlspecialchars($homepageImages['laudes']['slide3']); ?>" class="d-block w-100" alt="Magna Cum Laude" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Magna Cum Laude</h5>
                  <p>High academic distinction for students with outstanding scholastic achievements.</p>
                </div>
              </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#laudesCarousel" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#laudesCarousel" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
        <!-- Chatbot -->
        <div id="chatbot-container">
      <button id="chatbot-btn" class="btn btn-success rounded-circle shadow">
        💬
      </button>
      <div id="chatbot-box" class="card shadow d-none">
        <div class="card-header fw-bold text-white bg-success d-flex justify-content-between align-items-center">
          <span>FAQ</span>
          <button id="chatbot-close" class="btn btn-sm btn-light">&times;</button>
        </div>
        <div class="card-body">
          <ul class="list-unstyled">
            <li><a href="#" class="faq-link" data-answer="Enrollment starts every March for the upcoming school year.">When does enrollment start?</a></li>
            <li><a href="#" class="faq-link" data-answer="You can pay via onsite (cash) or online through GCash/Bank Transfer.">What are the payment options?</a></li>
            <li><a href="#" class="faq-link" data-answer="Yes, we offer scholarships based on academic performance and financial need.">Do you offer scholarships?</a></li>
            <li><a href="#" class="faq-link" data-answer="Yes, our campus has WiFi, computer labs, and a library.">Does the school have facilities?</a></li>
            <li><a href="#" class="faq-link" data-answer="You may contact us at 0912-345-6789 or email esr@school.com.">How can I contact the registrar?</a></li>
          </ul>
          <div id="faq-answer" class="mt-3 text-muted small text-center fw-bold"></div>
        </div>
      </div>
    </div>

    <style>
      #chatbot-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
      }
      #chatbot-btn {
        width: 60px;
        height: 60px;
        font-size: 24px;
      }
      #chatbot-box {
        width: 300px;
        margin-bottom: 10px;
      }
      .faq-link {
        display: block;
        margin-bottom: 8px;
        text-decoration: none;
        color: #007bff;
      }
      .faq-link:hover {
        text-decoration: underline;
        color: #0056b3;
      }
      #faq-answer {
        font-size: 14px;
        color: #333;
      }
    </style>

    <script>
      const chatbotBtn = document.getElementById('chatbot-btn');
      const chatbotBox = document.getElementById('chatbot-box');
      const chatbotClose = document.getElementById('chatbot-close');
      const faqAnswer = document.getElementById('faq-answer');

      chatbotBtn.addEventListener('click', function() {
        chatbotBox.classList.toggle('d-none');
      });

      chatbotClose.addEventListener('click', function() {
        chatbotBox.classList.add('d-none');
      });

      document.querySelectorAll('.faq-link').forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          faqAnswer.innerText = this.dataset.answer;
        });
      });
    </script>
