<?php
// Set page title
$page_title = 'Escuela de Sto. Rosario - Home';

  include 'includes/header.php';?> 
  
    <!-- Jumbotron -->
<main>
    <div id="home">
      <a href="StudentNoVerification/student_number.php" style="text-decoration: none; color: inherit;">
        <div class="jumbotron">
          <div class="text-center">
            <h class="display-4 fw-bold">Welcome to Escuela de Sto. Rosario</h>
            <h1 class="lead fw-bold">Quality education rooted in values and excellence.</h1>
            <p class="early">Early Registration | SY: 2026-2027</p>
          </div>
        </div>
      </a>
    </div>

    <!-- Sample Content -->
    <div class="container mt-5">
      <div class="row">
        <div class="col-md-4">
          <img src="EsrBanner.jpg" alt="School Banner" class="img-fluid mb-3" style="max-height: 250px; object-fit: cover;">
          <h5 class="fw-bold">Programs</h5>
          <p>Explore our wide range of academic programs designed to prepare students for the future.</p>
        </div>
        <div id="admission" class="col-md-4">
          <img src="Esrlogo.png" alt="School Logo" class="img-fluid mb-3" style="max-height: 250px; object-fit: cover;">
          <h5 class="fw-bold">Admissions</h5>
          <p>Join our community and take the first step towards a brighter future with us.</p>
        </div>
        <div class="col-md-4">
          <img src="EsrBanner.jpg" alt="School Campus" class="img-fluid mb-3" style="max-height: 250px; object-fit: cover;">
          <h5 class="fw-bold">Campus Life</h5>
          <p>Experience a vibrant and supportive campus life that nurtures learning and growth.</p>
        </div>
      </div>
    </div>

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
              <img src="EsrBanner.jpg" class="d-block" alt="School Event 1" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Annual School Fair</h5>
                <p>Join us for our exciting annual school fair with games, food, and entertainment.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="Esrlogo.png" class="d-block" alt="School Event 2" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Graduation Ceremony</h5>
                <p>Celebrating the achievements of our graduating students.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="EsrBanner.jpg" class="d-block" alt="School Event 3" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
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
              <img src="EsrBanner.jpg" class="d-block" alt="Achievement 1" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Academic Excellence Award</h5>
                <p>Recognized for outstanding academic performance and student achievement.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="Esrlogo.png" class="d-block" alt="Achievement 2" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Best Private School</h5>
                <p>Awarded as one of the top private educational institutions in the region.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="EsrBanner.jpg" class="d-block" alt="Achievement 3" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
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
              <img src="EsrBanner.jpg" class="d-block" alt="Primary Education" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Primary Education (K-6)</h5>
                <p>Building strong foundations in reading, writing, and mathematics for young learners.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="Esrlogo.png" class="d-block" alt="Secondary Education" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Secondary Education (7-10)</h5>
                <p>Comprehensive curriculum preparing students for higher education and life skills.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="EsrBanner.jpg" class="d-block" alt="Learning Environment" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
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
              <img src="EsrBanner.jpg" class="d-block" alt="JHS Grade 7-8" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Grades 7-8 Program</h5>
                <p>Transitioning students with enhanced academic and social development programs.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="Esrlogo.png" class="d-block" alt="JHS Grade 9-10" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>Grades 9-10 Program</h5>
                <p>Advanced curriculum preparing students for senior high school specialization.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="EsrBanner.jpg" class="d-block" alt="JHS Activities" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
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
              <img src="EsrBanner.jpg" class="d-block" alt="STEM Track" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>STEM Track</h5>
                <p>Science, Technology, Engineering, and Mathematics specialization for future innovators.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="Esrlogo.png" class="d-block" alt="ABM Track" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
              <div class="carousel-caption d-none d-md-block">
                <h5>ABM Track</h5>
                <p>Accountancy, Business, and Management program for future business leaders.</p>
              </div>
            </div>
            <div class="carousel-item" data-bs-interval="5000">
              <img src="EsrBanner.jpg" class="d-block" alt="HUMSS Track" style="height: 400px; object-fit: cover; width: 70%; margin: 0 auto;">
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
                <img src="EsrBanner.jpg" class="d-block w-100" alt="PAPRISA Membership" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>PAPRISA Membership</h5>
                  <p>Philippine Association of Private Schools and Administrators recognition and membership programs.</p>
                </div>
              </div>
              <div class="carousel-item" data-bs-interval="5000">
                <img src="Esrlogo.png" class="d-block w-100" alt="PAPRISA Awards" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>PAPRISA Awards</h5>
                  <p>Recognized for excellence in private education administration and management.</p>
                </div>
              </div>
              <div class="carousel-item" data-bs-interval="5000">
                <img src="EsrBanner.jpg" class="d-block w-100" alt="PAPRISA Programs" style="height: 300px; object-fit: cover;">
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
                <img src="Esrlogo.png" class="d-block w-100" alt="Licensure Exams" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Professional Licensure</h5>
                  <p>Celebrating our alumni who have successfully passed various professional board examinations.</p>
                </div>
              </div>
              <div class="carousel-item" data-bs-interval="5000">
                <img src="EsrBanner.jpg" class="d-block w-100" alt="Top Performers" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Top Performers</h5>
                  <p>Alumni who achieved top rankings in their respective professional licensure examinations.</p>
                </div>
              </div>
              <div class="carousel-item" data-bs-interval="5000">
                <img src="Esrlogo.png" class="d-block w-100" alt="Career Success" style="height: 300px; object-fit: cover;">
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
                <img src="EsrBanner.jpg" class="d-block w-100" alt="Academic Honors" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Academic Honors</h5>
                  <p>Students who have achieved academic excellence and graduated with honors.</p>
                </div>
              </div>
              <div class="carousel-item" data-bs-interval="5000">
                <img src="Esrlogo.png" class="d-block w-100" alt="Summa Cum Laude" style="height: 300px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block">
                  <h5>Summa Cum Laude</h5>
                  <p>Highest academic distinction awarded to students with exceptional academic performance.</p>
                </div>
              </div>
              <div class="carousel-item" data-bs-interval="5000">
                <img src="EsrBanner.jpg" class="d-block w-100" alt="Magna Cum Laude" style="height: 300px; object-fit: cover;">
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


