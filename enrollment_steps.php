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
            <a class="btn btn-outline-light btn-lg fw-semibold" href="#old-online">Old Students Online</a>
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
                <li><strong>Complete the early registration form.</strong> Fill out learner, parent, and previous school details.</li>
                <li><strong>Visit the school for interview and document check.</strong> Bring the original certificates, IDs, and report cards for validation during the onsite interview.</li>
                <li><strong>Settle tuition at the cashier.</strong> After the Admin approves your interview, pay the reservation or tuition fee with the cashier team.</li>
                <li><strong>Wait for registrar activation.</strong> The Registrar finalizes your student record, activates your portal account, and emails the permanent student number.</li>
              </ol>
              <p class="mb-0 text-muted"><i class="bi bi-info-circle-fill text-success me-2"></i>Keep your orignal birth certificate, latest report card, and parent/guardian ID upon registration.</p>
            </div>
          </div>

          <div class="col-lg-6" id="new-onsite">
            <div class="step-card h-100">
              <span class="badge mb-3">New Students</span>
              <h3 class="h4">Onsite Enrollment</h3>
              <ol class="mt-4">
                <li><strong>Visit the Registrar’s Office.</strong> Bring your child’s PSA birth certificate, latest report card, certificate of good moral, and two 1x1 ID photos.</li>
                <li><strong>Complete forms and interview.</strong> Fill out the learner profile with our staff and join the short interview that confirms grade placement and readiness.</li>
                <li><strong>Submit requirements for verification.</strong> The Registrar reviews originals, keeps photocopies, and issues the approved enrollment checklist.</li>
                <li><strong>Proceed to the cashier.</strong> Settle the reservation or tuition fee at the cashier counter to secure your slot.</li>
                <li><strong>Wait for registrar activation.</strong> The Registrar activates your official student account and releases the permanent student number with next steps.</li>
              </ol>
              <p class="mb-0 text-muted"><i class="bi bi-building-check text-success me-2"></i>Office hours are Monday to Friday, 8:00 AM – 4:00 PM.</p>
            </div>
          </div>

          <div class="col-lg-6" id="old-online">
            <div class="step-card h-100">
              <span class="badge mb-3">Old Students</span>
              <h3 class="h4">Online Re-enrollment</h3>
              <ol class="mt-4">
                <li><strong>Log in to the student portal.</strong> Go to <a href="Portal/student_login.php">Student Portal</a> using your current credentials, or reset through <a href="Portal/forgot_password.php">Forgot Password</a> if needed.</li>
                <li><strong>Pick your payment plan.</strong> Click Enroll Now select full or installment, and proceed with the online channel you prefer.</li>
                <li><strong>Pay and upload proof.</strong> Complete the online payment then attach the digital receipt or reference number inside the portal.</li>
                <li><strong>Monitor validation.</strong> Track the status inside the Student Portal until the cashier clears your payment and labeled as Officially Enrolled.</li>
              </ol>
              <p class="mb-0 text-muted"><i class="bi bi-shield-check text-success me-2"></i>Old students should finish tuition payment online; status updates and receipts appear instantly inside the portal.</p>
            </div>
          </div>

          <div class="col-lg-6" id="old-onsite">
            <div class="step-card h-100">
              <span class="badge mb-3">Old Students</span>
              <h3 class="h4">Onsite Assistance</h3>
              <ol class="mt-4">
                <li><strong>Confirm learner status.</strong> Coordinate with the Registrar or message through the portal inbox to ensure there are no pending requirements.</li>
                <li><strong>Select a plan online.</strong> Staff can help you log in and complete the <a href="Portal/choose_payment.php">Choose Payment</a> flow if you need assistance onsite.</li>
                <li><strong>Complete the online payment.</strong> Use the school’s digital payment channels (GCash, bank transfer, etc.) while onsite or beforehand, then upload the receipt.</li>
                <li><strong>Verify payment posting.</strong> Present the reference number to the cashier team so they can confirm it in the system.</li>
                <li><strong>Payment received.</strong> After validation, wait for any registrar advisory on class schedules.</li>
              </ol>
              <p class="mb-0 text-muted"><i class="bi bi-calendar-check text-success me-2"></i>Need help with the online payment flow? Our cashier and registrar staff can guide you at the onsite support desks.</p>
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
