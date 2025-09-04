<?php
// Include template helper
require_once 'templates/template_helper.php';

// Set page title
$page_title = 'Escuela de Sto. Rosario - Home';

// Render the page using the template system
renderPage($page_title, function() {
    ob_start();
    ?>
    
    <!-- Jumbotron -->
    <div id="home">
      <a href="early_registration.php" style="text-decoration: none; color: inherit;">
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
          <img src="../esrBanner.jpg" alt="School Banner" class="img-fluid mb-3" style="max-height: 250px; object-fit: cover;">
          <h5 class="fw-bold">Programs</h5>
          <p>Explore our wide range of academic programs designed to prepare students for the future.</p>
        </div>
        <div class="col-md-4">
          <img src="../Esrlogo.png" alt="School Logo" class="img-fluid mb-3" style="max-height: 250px; object-fit: cover;">
          <h5 class="fw-bold">Admissions</h5>
          <p>Join our community and take the first step towards a brighter future with us.</p>
        </div>
        <div class="col-md-4">
          <img src="../Esr_banner.jpg" alt="School Campus" class="img-fluid mb-3" style="max-height: 250px; object-fit: cover;">
          <h5 class="fw-bold">Campus Life</h5>
          <p>Experience a vibrant and supportive campus life that nurtures learning and growth.</p>
        </div>
      </div>
    </div>

    <!-- About Us Section -->
    <div id="aboutus" class="container py-5">
      <h2 class="text-center mb-4 fw-bold">About Us</h2>

      <!-- Sub-nav (like pagination for Vision & Mission) -->
      <ul class="nav nav-pills justify-content-center mb-4" id="aboutTabs" role="tablist">
        <li class="nav-item">
          <button class="nav-link fw-bold active" id="vision-tab" data-bs-toggle="tab" data-bs-target="#vision" type="button" role="tab">Our Vision</button>
        </li>
        <li class="nav-item">
          <button class="nav-link fw-bold" id="mission-tab" data-bs-toggle="tab" data-bs-target="#mission" type="button" role="tab">Our Mission</button>
        </li>
      </ul>

      <!-- Content -->
      <div class="tab-content mx-auto text-center" id="aboutTabsContent" style="max-width: 950px;">
        <div class="tab-pane fade show active" id="vision" role="tabpanel">
          <h3 class="text-success fw-bold">Our Vision</h3>
          <p>
           To train and educate the children regardless of their religious affiliation in response to the needs of the times; hence, Sto. Rosarians education envision integrated moral and family values, social commitment, spiritual and physical growth, and in line with the development of Information Technology to become a better citizen of our nation.
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

    <?php
    return ob_get_clean();
});
?>
