<?php
// Set page title
$page_title = 'Escuela de Sto. Rosario - Home';

  include 'includes/header.php';
  require_once __DIR__ . '/includes/homepage_images.php';
  $homepageImages = homepage_images_load();

  function render_homepage_carousel(
    string $id,
    array $slides,
    array $captions = [],
    string $imageClass = 'd-block w-100',
    string $imageStyle = '',
    string $altPrefix = 'Slide'
  ): void {
    $slides = is_array($slides) ? array_filter($slides, static function ($path) {
      return is_string($path) && $path !== '';
    }) : [];
    if (empty($slides)) {
      echo '<p class="text-muted text-center mb-0">No images configured yet.</p>';
      return;
    }

    ?>
    <div id="<?= htmlspecialchars($id) ?>" class="carousel carousel-dark slide" data-bs-ride="carousel">
      <div class="carousel-indicators">
        <?php $i = 0; foreach ($slides as $_key => $_path): ?>
          <button type="button" data-bs-target="#<?= htmlspecialchars($id) ?>" data-bs-slide-to="<?= $i ?>" <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?> aria-label="Slide <?= $i + 1 ?>"></button>
        <?php $i++; endforeach; ?>
      </div>
      <div class="carousel-inner">
        <?php $i = 0; foreach ($slides as $_path): ?>
          <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>" data-bs-interval="5000">
            <?php $resolvedPath = homepage_image_url($_path); ?>
            <img src="<?= htmlspecialchars($resolvedPath) ?>"<?php if ($imageClass !== ''): ?> class="<?= htmlspecialchars($imageClass) ?>"<?php endif; ?> alt="<?= htmlspecialchars($altPrefix . ' ' . ($i + 1)) ?>"<?php if ($imageStyle !== ''): ?> style="<?= htmlspecialchars($imageStyle, ENT_QUOTES) ?>"<?php endif; ?>>
            <?php if (!empty($captions[$i]) && (isset($captions[$i]['title']) || isset($captions[$i]['text']))): ?>
              <div class="carousel-caption caption-overlay d-none d-md-block">
                <?php if (!empty($captions[$i]['title'])): ?><h5><?= htmlspecialchars($captions[$i]['title']) ?></h5><?php endif; ?>
                <?php if (!empty($captions[$i]['text'])): ?><p><?= htmlspecialchars($captions[$i]['text']) ?></p><?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php $i++; endforeach; ?>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#<?= htmlspecialchars($id) ?>" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#<?= htmlspecialchars($id) ?>" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>
    <?php
  }

  $carouselCaptions = [
    'events' => [
      ['title' => 'Annual School Fair', 'text' => 'Join us for our exciting annual school fair with games, food, and entertainment.'],
      ['title' => 'Graduation Ceremony', 'text' => 'Celebrating the achievements of our graduating students.'],
      ['title' => 'Sports Festival', 'text' => 'Annual sports competition showcasing student athletic talents.'],
    ],
    'achievements' => [
      ['title' => 'Academic Excellence Award', 'text' => 'Recognized for outstanding academic performance and student achievement.'],
      ['title' => 'Best Private School', 'text' => 'Awarded as one of the top private educational institutions in the region.'],
      ['title' => 'Innovation in Education', 'text' => 'Recognized for implementing innovative teaching methods and technology.'],
    ],
    'primary_secondary' => [
      ['title' => 'Primary Education (K-6)', 'text' => 'Building strong foundations in reading, writing, and mathematics for young learners.'],
      ['title' => 'Secondary Education (7-10)', 'text' => 'Comprehensive curriculum preparing students for higher education and life skills.'],
      ['title' => 'Modern Learning Environment', 'text' => 'State-of-the-art facilities and technology-enhanced classrooms.'],
    ],
    'junior_high' => [
      ['title' => 'Grades 7-8 Program', 'text' => 'Transitioning students with enhanced academic and social development programs.'],
      ['title' => 'Grades 9-10 Program', 'text' => 'Advanced curriculum preparing students for senior high school specialization.'],
      ['title' => 'Extracurricular Activities', 'text' => 'Diverse clubs, sports, and leadership opportunities for holistic development.'],
    ],
    'senior_high' => [
      ['title' => 'STEM Track', 'text' => 'Science, Technology, Engineering, and Mathematics specialization for future innovators.'],
      ['title' => 'ABM Track', 'text' => 'Accountancy, Business, and Management program for future business leaders.'],
      ['title' => 'HUMSS Track', 'text' => 'Humanities and Social Sciences for students pursuing liberal arts and social studies.'],
    ],
    'paprisa' => [
      ['title' => 'PAPRISA Membership', 'text' => 'Philippine Association of Private Schools and Administrators recognition and membership programs.'],
      ['title' => 'PAPRISA Awards', 'text' => 'Recognized for excellence in private education administration and management.'],
      ['title' => 'PAPRISA Programs', 'text' => 'Active participation in PAPRISA educational programs and professional development initiatives.'],
    ],
    'board' => [
      ['title' => 'Professional Licensure', 'text' => 'Celebrating our alumni who have successfully passed various professional board examinations.'],
      ['title' => 'Top Performers', 'text' => 'Alumni who achieved top rankings in their respective professional licensure examinations.'],
      ['title' => 'Career Success', 'text' => 'Our graduates excelling in their chosen professions and contributing to society.'],
    ],
    'laudes' => [
      ['title' => 'Academic Honors', 'text' => 'Students who have achieved academic excellence and graduated with honors.'],
      ['title' => 'Summa Cum Laude', 'text' => 'Highest academic distinction awarded to students with exceptional academic performance.'],
      ['title' => 'Magna Cum Laude', 'text' => 'High academic distinction for students with outstanding scholastic achievements.'],
    ],
  ];
?> 

<style>
  :root {
    --brand-green: #145A32;
    --brand-yellow: #fbd80a;
    --brand-sage: #e7f3ec;
  }

  .hero-banner {
    background-image: linear-gradient(rgba(20, 90, 50, 0.75), rgba(20, 90, 50, 0.75)), url('<?= htmlspecialchars(homepage_image_url($homepageImages['cards']['campus'] ?? '')); ?>');
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

  .carousel-caption.caption-overlay {
    background: linear-gradient(135deg, rgba(20, 90, 50, 0.85), rgba(0, 0, 0, 0.75));
    border-radius: 1.25rem;
    padding: 1.75rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
    backdrop-filter: blur(6px);
  }

  .carousel-caption.caption-overlay h5,
  .carousel-caption.caption-overlay p {
    color: #fff;
    text-shadow: 0 3px 12px rgba(0, 0, 0, 0.65);
  }

  .carousel-caption.caption-overlay h5 {
    font-size: 1.55rem;
    font-weight: 700;
  }

  .carousel-caption.caption-overlay p {
    font-size: 1.05rem;
    margin-bottom: 0;
  }

  .carousel-indicators [data-bs-target] {
    background-color: rgba(255, 255, 255, 0.9);
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
            <img src="<?= htmlspecialchars(homepage_image_url($homepageImages['events']['slide1'] ?? '')); ?>" alt="Students on campus" class="card-img-top">
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
      <div class="col-md-4">
          <div class="card info-card border-0 shadow-sm h-100">
            <img src="<?= htmlspecialchars(homepage_image_url($homepageImages['cards']['campus'] ?? '')); ?>" alt="Campus life" class="w-100">
            <div class="card-body">
              <h5 class="fw-bold text-success">Life on Campus</h5>
              <p class="text-muted">Discover clubs, facilities, and activities that keep students inspired daily.</p>
              <a href="#highlights" class="fw-semibold text-decoration-none" style="color: var(--brand-green);">Tour our highlights <i class="bi bi-arrow-right-short"></i></a>
            </div>
          </div>
        </div>
      <div class="row g-4">
        <div class="col-md-4">
            <div class="card info-card border-0 shadow-sm h-100">
            <img src="<?= htmlspecialchars(homepage_image_url($homepageImages['cards']['programs'] ?? '')); ?>" alt="Academic progress" class="w-100">
            <div class="card-body">
              <h5 class="fw-bold text-success">Academic Progress</h5>
              <p class="text-muted">Explore milestones, strands, and support that guide students from preschool foundations to senior high achievements.</p>
              <a href="#primarySecondaryCarousel" class="fw-semibold text-decoration-none" style="color: var(--brand-green);">View academic tracks <i class="bi bi-arrow-right-short"></i></a>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="card info-card border-0 shadow-sm h-100">
            <img src="<?= htmlspecialchars(homepage_image_url($homepageImages['cards']['admissions'] ?? '')); ?>" alt="Admissions" class="w-100">
            <div class="card-body">
              <h5 class="fw-bold text-success">How to Enroll</h5>
              <p class="text-muted">Follow easy steps for document submission, interviews, and payment options.</p>
              <a href="enrollment_steps.php" class="fw-semibold text-decoration-none" style="color: var(--brand-green);">See enrollment steps <i class="bi bi-arrow-right-short"></i></a>
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
        <?php
          render_homepage_carousel(
            'eventsCarousel',
            $homepageImages['events'] ?? [],
            $carouselCaptions['events'] ?? [],
            'd-block',
            'height: 400px; object-fit: cover; width: 70%; margin: 0 auto;',
            'School Event'
          );
        ?>
      </div>

      <!-- School Achievements Carousel -->
      <div class="mb-5">
        <h4 class="text-center mb-3 fw-bold text-success">School Achievements</h4>
        <?php
          render_homepage_carousel(
            'achievementsCarousel',
            $homepageImages['achievements'] ?? [],
            $carouselCaptions['achievements'] ?? [],
            'd-block',
            'height: 400px; object-fit: cover; width: 70%; margin: 0 auto;',
            'Achievement'
          );
        ?>
      </div>

      <!-- Primary & Secondary Education Carousel -->
      <div class="mb-5">
        <h4 class="text-center mb-3 fw-bold text-success">Primary & Secondary Education</h4>
        <?php
          render_homepage_carousel(
            'primarySecondaryCarousel',
            $homepageImages['primary_secondary'] ?? [],
            $carouselCaptions['primary_secondary'] ?? [],
            'd-block',
            'height: 400px; object-fit: cover; width: 70%; margin: 0 auto;',
            'Primary & Secondary'
          );
        ?>
      </div>

      <!-- Junior High School Carousel -->
      <div class="mb-5">
        <h4 class="text-center mb-3 fw-bold text-success">Junior High School</h4>
        <?php
          render_homepage_carousel(
            'juniorHighCarousel',
            $homepageImages['junior_high'] ?? [],
            $carouselCaptions['junior_high'] ?? [],
            'd-block',
            'height: 400px; object-fit: cover; width: 70%; margin: 0 auto;',
            'Junior High Highlight'
          );
        ?>
      </div>

      <!-- Senior High School Carousel -->
      <div class="mb-5">
        <h4 class="text-center mb-3 fw-bold text-success">Senior High School</h4>
        <div class="row">
        
        <?php
          render_homepage_carousel(
            'seniorHighCarousel',
            $homepageImages['senior_high'] ?? [],
            $carouselCaptions['senior_high'] ?? [],
            'd-block',
            'height: 400px; object-fit: cover; width: 70%; margin: 0 auto;',
            'Senior High Track'
          );
        ?>
      </div>
    </div>

    <!-- Additional Content Carousels - 3 Columns Horizontally -->
    <div class="container mt-5">
      <h2 class="text-center mb-5 fw-bold">School Recognition & Achievements</h2>
      
      <div class="row">
        <!-- Column 1: PAPRISA Carousel -->
        <div class="col-md-4 mb-4">
          <h4 class="text-center mb-3 fw-bold text-success">PAPRISA</h4>
          <?php
            render_homepage_carousel(
              'paprisaCarousel',
              $homepageImages['paprisa'] ?? [],
              $carouselCaptions['paprisa'] ?? [],
              'd-block w-100',
              'height: 300px; object-fit: cover;',
              'PAPRISA Highlight'
            );
          ?>
        </div>

        <!-- Column 2: Board Exam Passers Carousel -->
        <div class="col-md-4 mb-4">
          <h4 class="text-center mb-3 fw-bold text-success">Board Exam Passers</h4>
          <?php
            render_homepage_carousel(
              'boardExamCarousel',
              $homepageImages['board'] ?? [],
              $carouselCaptions['board'] ?? [],
              'd-block w-100',
              'height: 300px; object-fit: cover;',
              'Board Exam Highlight'
            );
          ?>
        </div>

        <!-- Column 3: Laudes Carousel -->
        <div class="col-md-4 mb-4">
          <h4 class="text-center mb-3 fw-bold text-success">Laudes</h4>
          <?php
            render_homepage_carousel(
              'laudesCarousel',
              $homepageImages['laudes'] ?? [],
              $carouselCaptions['laudes'] ?? [],
              'd-block w-100',
              'height: 300px; object-fit: cover;',
              'Laudes Highlight'
            );
          ?>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
        <!-- Chatbot -->
        <div id="chatbot-container">
      <button id="chatbot-btn" class="chatbot-btn shadow-lg">
        <span class="chatbot-btn__icon">💬</span>
        <span class="chatbot-btn__label">Need help?</span>
      </button>
      <div id="chatbot-box" class="chatbot-card shadow-lg d-none">
        <div class="chatbot-header">
          <div class="d-flex align-items-center gap-3">
            <div class="chatbot-avatar">
              <i class="bi bi-robot"></i>
            </div>
            <div>
              <h6 class="mb-0 fw-bold text-white">ESR Assistant</h6>
              <small class="text-white-50">Quick answers to common questions</small>
            </div>
          </div>
          <button id="chatbot-close" class="btn btn-sm btn-outline-light rounded-pill px-3">Close</button>
        </div>
        <div class="chatbot-body">
          <div class="chatbot-messages" id="chatbot-messages">
            <div class="chatbot-message bot">
              <span class="chatbot-message__text">Hello! Select a question below to learn more about Escuela de Sto. Rosario.</span>
            </div>
          </div>
          <div class="chatbot-quick-actions">
            <span class="text-uppercase small fw-bold text-muted">Frequently Asked</span>
            <div class="chatbot-quick-list">
              <button type="button" class="chatbot-chip" data-answer="Enrollment starts every March for the upcoming school year.">When does enrollment start?</button>
              <button type="button" class="chatbot-chip" data-answer="You can pay via onsite (cash) or online through GCash/Bank Transfer.">What are the payment options?</button>
              <button type="button" class="chatbot-chip" data-answer="Yes, we offer scholarships based on academic performance and financial need.">Do you offer scholarships?</button>
              <button type="button" class="chatbot-chip" data-answer="Yes, our campus has WiFi, computer labs, and a library.">Does the school have facilities?</button>
              <button type="button" class="chatbot-chip" data-answer="You may contact us at 0912-345-6789 or email registrar@rosariodigital.site.">How can I contact the registrar?</button>
            </div>
          </div>
          <div class="chatbot-footnote text-center text-muted small">
            Need something else? Call <strong>(0969) 354-2870</strong> Monday–Friday, 8 AM – 5 PM.
          </div>
        </div>
      </div>
    </div>

    <style>
      #chatbot-container {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 12px;
      }
      .chatbot-btn {
        background: linear-gradient(135deg, #145A32, #1d7c46);
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: 12px 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }
      .chatbot-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(20, 90, 50, 0.25);
      }
      .chatbot-btn__icon {
        font-size: 1.25rem;
      }
      .chatbot-btn__label {
        font-size: 0.95rem;
      }
      .chatbot-card {
        width: min(320px, 90vw);
        border: none;
        border-radius: 20px;
        overflow: hidden;
        background: #ffffff;
        animation: chatbot-slide-up 0.3s ease;
      }
      .chatbot-header {
        background: linear-gradient(135deg, #145A32, #0f4f24);
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      .chatbot-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: #fff;
      }
      .chatbot-body {
        padding: 18px 18px 16px;
        display: flex;
        flex-direction: column;
        gap: 16px;
      }
      .chatbot-messages {
        max-height: 220px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding-right: 4px;
      }
      .chatbot-message {
        display: inline-flex;
        align-items: flex-start;
        gap: 10px;
        animation: chatbot-fade-in 0.25s ease;
      }
      .chatbot-message__text {
        background: rgba(20, 90, 50, 0.08);
        color: #145A32;
        padding: 10px 14px;
        border-radius: 14px 14px 14px 4px;
        font-size: 0.9rem;
        line-height: 1.4;
        display: inline-block;
      }
      .chatbot-message.user .chatbot-message__text {
        background: #145A32;
        color: #fff;
        border-radius: 14px 14px 4px 14px;
        align-self: flex-end;
      }
      .chatbot-quick-actions {
        background: rgba(20, 90, 50, 0.04);
        border-radius: 14px;
        padding: 14px 12px;
        display: flex;
        flex-direction: column;
        gap: 10px;
      }
      .chatbot-quick-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
      }
      .chatbot-chip {
        border: 1px solid rgba(20, 90, 50, 0.2);
        background: #fff;
        border-radius: 999px;
        padding: 8px 14px;
        font-size: 0.82rem;
        color: #145A32;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .chatbot-chip:hover,
      .chatbot-chip:focus {
        background: #145A32;
        color: #fff;
        box-shadow: 0 10px 18px rgba(20, 90, 50, 0.18);
      }
      .chatbot-footnote {
        font-size: 0.78rem;
      }
      @keyframes chatbot-slide-up {
        from { transform: translateY(15px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
      }
      @keyframes chatbot-fade-in {
        from { transform: translateY(8px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
      }
      @media (max-width: 576px) {
        .chatbot-btn__label {
          display: none;
        }
        .chatbot-btn {
          padding: 12px 14px;
        }
      }
    </style>

    <script>
      (function () {
        const chatbotBtn = document.getElementById('chatbot-btn');
        const chatbotBox = document.getElementById('chatbot-box');
        const chatbotClose = document.getElementById('chatbot-close');
        const messagesContainer = document.getElementById('chatbot-messages');

        const toggleChatbot = () => {
          chatbotBox.classList.toggle('d-none');
          if (!chatbotBox.classList.contains('d-none')) {
            chatbotBox.classList.add('chatbot-active');
          }
        };

        const appendMessage = (text, type = 'bot') => {
          const wrapper = document.createElement('div');
          wrapper.className = `chatbot-message ${type}`;
          const inner = document.createElement('span');
          inner.className = 'chatbot-message__text';
          inner.textContent = text;
          wrapper.appendChild(inner);
          messagesContainer.appendChild(wrapper);
          messagesContainer.scrollTop = messagesContainer.scrollHeight;
        };

        chatbotBtn.addEventListener('click', toggleChatbot);
        chatbotClose.addEventListener('click', () => chatbotBox.classList.add('d-none'));

        document.querySelectorAll('.chatbot-chip').forEach((chip) => {
          chip.addEventListener('click', () => {
            const question = chip.textContent.trim();
            const answer = chip.getAttribute('data-answer') || '';

            appendMessage(question, 'user');
            setTimeout(() => appendMessage(answer, 'bot'), 200);
          });
        });
      })();
    </script>
