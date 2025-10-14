<?php
$page_title = 'Escuela de Sto. Rosario - Enrollment Steps';
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
    background-image: linear-gradient(rgba(20, 90, 50, 0.82), rgba(20, 90, 50, 0.82)), url('<?= htmlspecialchars(homepage_image_url($homepageImages['cards']['campus'] ?? '')); ?>');
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
    max-width: 580px;
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

  .step-section {
    background: #fff;
    border-radius: 2rem;
    box-shadow: 0 24px 48px rgba(20, 90, 50, 0.08);
    padding: 3rem 2.5rem;
  }

  .section-label {
    font-size: 0.85rem;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: var(--brand-green);
    font-weight: 700;
  }

  .step-card {
    background: var(--brand-sage);
    border-radius: 1.75rem;
    padding: 2.25rem;
    height: 100%;
    box-shadow: 0 18px 40px rgba(20, 90, 50, 0.08);
    border: none;
  }

  .step-card .badge {
    background: rgba(20, 90, 50, 0.15);
    color: var(--brand-green);
    font-weight: 600;
    border-radius: 999px;
    padding: 0.45rem 1rem;
    letter-spacing: 0.05em;
  }

  .step-card h3 {
    font-weight: 700;
    color: var(--brand-green);
  }

  .step-card ol {
    padding-left: 1.1rem;
    margin-bottom: 1.5rem;
    color: #444;
  }

  .step-card li + li {
    margin-top: 0.4rem;
  }

  .step-card li strong {
    color: var(--brand-green);
  }

  .support-card {
    background: linear-gradient(135deg, rgba(20, 90, 50, 0.95), rgba(39, 174, 96, 0.9));
    border-radius: 1.75rem;
    color: #fff;
    padding: 2.5rem;
    box-shadow: 0 18px 40px rgba(20, 90, 50, 0.18);
  }

  .support-card a {
    color: var(--brand-yellow);
    font-weight: 600;
  }
</style>

<main>
  <section class="hero-banner text-white py-5">
    <div class="container py-5">
      <div class="row justify-content-between align-items-center">
        <div class="col-lg-7">
          <span class="hero-kicker"><i class="bi bi-list-check"></i> Enrollment Guide</span>
          <h1 class="display-5 fw-bold mt-4">Follow the path that fits your family.</h1>
          <p class="hero-lead mt-3">Whether you are joining ESR for the first time or returning for another year, choose the steps for online or onsite enrollment and stay on track with every requirement.</p>
          <div class="d-flex flex-wrap gap-3 mt-4 hero-actions">
            <a class="btn btn-warning btn-lg text-dark fw-semibold" href="#new-online">New Students Online</a>
            <a class="btn btn-outline-light btn-lg fw-semibold" href="#returning-online">Returning Students Online</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-5 bg-light">
    <div class="container">
      <div class="step-section">
        <div class="text-center mb-5">
          <span class="section-label text-uppercase fw-bold" style="letter-spacing: 0.3em; color: var(--brand-green);">Start Confidently</span>
          <h2 class="fw-bold text-success mt-3">Step-by-step guidance for every enrollment path</h2>
          <p class="lead text-muted mb-0">Review the checklists below then follow the portal links to upload documents, monitor approval, and confirm payment.</p>
        </div>

        <div class="row g-4">
          <div class="col-lg-6" id="new-online">
            <div class="step-card h-100">
              <span class="badge mb-3">New Students</span>
              <h3 class="h4">Online Enrollment</h3>
              <ol class="mt-4">
                <li><strong>Secure a temporary student number.</strong> Launch the digital form and request for a student number through <a href="StudentNoVerification/student_number.php">Student Number Request</a>.</li>
                <li><strong>Complete the early registration form.</strong> Fill out all learner, parent, and previous school fields then upload required IDs and report cards.</li>
                <li><strong>Await registrar review.</strong> Watch for status updates via email and the confirmation page while the Registrar validates your submission.</li>
                <li><strong>Choose your payment option.</strong> Once approved, open the portal link sent to your email and proceed to <a href="Portal/choose_payment.php">Choose Payment</a> to reserve your slot.</li>
                <li><strong>Receive your official portal access.</strong> After payment is validated the school will send your permanent student number and login instructions.</li>
              </ol>
              <p class="mb-0 text-muted"><i class="bi bi-info-circle-fill text-success me-2"></i>Keep clear photos or PDFs of your birth certificate, latest report card, and parent/guardian ID ready before you start.</p>
            </div>
          </div>

          <div class="col-lg-6" id="new-onsite">
            <div class="step-card h-100">
              <span class="badge mb-3">New Students</span>
              <h3 class="h4">Onsite Enrollment</h3>
              <ol class="mt-4">
                <li><strong>Visit the Registrar’s Office.</strong> Bring your child’s PSA birth certificate, latest report card, certificate of good moral, and two 1x1 ID photos.</li>
                <li><strong>Fill out the registration packets.</strong> Complete the learner profile, parent information sheet, and consent forms with the assistance of our staff.</li>
                <li><strong>Submit requirements for verification.</strong> The registrar reviews originals and keeps photocopies for school records.</li>
                <li><strong>Proceed to the cashier.</strong> Settle the reservation or enrollment fee at the cashier counter or coordinate deferred payment plans.</li>
                <li><strong>Claim your welcome kit.</strong> Receive your official student number, uniform guidelines, and class schedule release date.</li>
              </ol>
              <p class="mb-0 text-muted"><i class="bi bi-building-check text-success me-2"></i>Office hours are Monday to Friday, 8:00 AM – 4:00 PM. Call ahead to book a queue number during peak weeks.</p>
            </div>
          </div>

          <div class="col-lg-6" id="returning-online">
            <div class="step-card h-100">
              <span class="badge mb-3">Returning Students</span>
              <h3 class="h4">Online Re-enrollment</h3>
              <ol class="mt-4">
                <li><strong>Log in to the student portal.</strong> Go to <a href="Portal/student_login.php">Student Portal</a> using the credentials you set last year. Reset through <a href="Portal/forgot_password.php">Forgot Password</a> if needed.</li>
                <li><strong>Confirm your profile details.</strong> Update contact numbers, guardian information, and preferred strand or section if changes apply.</li>
                <li><strong>Pick a payment plan.</strong> Open <a href="Portal/choose_payment.php">Choose Payment</a> to select full, installment, or onsite settlement. Upload the proof of payment if you pay online.</li>
                <li><strong>Track approval.</strong> Monitor the status inside <a href="Portal/student_portal.php">My Enrollment</a> or wait for the confirmation email from the cashier team.</li>
                <li><strong>Download your enrollment slip.</strong> Once marked paid and validated, print or save the enrollment slip for the first-day requirements.</li>
              </ol>
              <p class="mb-0 text-muted"><i class="bi bi-shield-check text-success me-2"></i>Returning parents may still visit the cashier to settle fees in person—just present the digital confirmation once payment is posted.</p>
            </div>
          </div>

          <div class="col-lg-6" id="returning-onsite">
            <div class="step-card h-100">
              <span class="badge mb-3">Returning Students</span>
              <h3 class="h4">Onsite Re-enrollment</h3>
              <ol class="mt-4">
                <li><strong>Confirm learner status.</strong> Drop by the Registrar or message through the portal inbox to ensure your student is cleared of pending requirements.</li>
                <li><strong>Visit the cashier window.</strong> Bring the latest report card or enrollment slip from the previous year plus any scholarship documents.</li>
                <li><strong>Select the payment scheme.</strong> Our cashier will review tuition matrices, issue the official assessment, and note your preferred schedule.</li>
                <li><strong>Submit proof of payment if banked.</strong> Hand over deposit slips or GCash screenshots so the cashier can issue receipts immediately.</li>
                <li><strong>Collect updated documents.</strong> Receive the stamped enrollment slip, class assignment info, and reminders for orientation or bridge programs.</li>
              </ol>
              <p class="mb-0 text-muted"><i class="bi bi-calendar-check text-success me-2"></i>Walk-ins are welcome, but scheduling through the Registrar helps minimize wait times, especially during opening week.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="pb-5">
    <div class="container">
      <div class="support-card">
        <div class="row align-items-center g-4">
          <div class="col-lg-8">
            <h2 class="h4 fw-bold mb-3">Need assistance at any step?</h2>
            <p class="mb-2">Reach the Registrar’s Office at <a href="tel:+63454341234">(045) 434-1234</a> or email <a href="mailto:registrar@esr.edu.ph">registrar@esr.edu.ph</a>. For payment clarifications, contact the Cashier at <a href="mailto:cashier@esr.edu.ph">cashier@esr.edu.ph</a>.</p>
            <p class="mb-0">You can also message us through the portal inbox and expect a reply within one business day.</p>
          </div>
          <div class="col-lg-4 text-lg-end">
            <a href="index.php#family-explore" class="btn btn-light btn-lg fw-semibold" style="border-radius: 999px;">Back to Home</a>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
