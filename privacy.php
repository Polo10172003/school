<?php
$page_title = 'Data Privacy Notice';
$disable_privacy_notice = true;
require_once __DIR__ . '/includes/session.php';
include __DIR__ . '/includes/header.php';
?>

<style>
  .privacy-wrapper {
    max-width: 960px;
    margin: 40px auto;
    padding: 32px;
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 18px 36px rgba(12, 53, 27, 0.08);
    color: #1f2f28;
    line-height: 1.6;
  }

  .privacy-wrapper h1 {
    font-size: 2rem;
    color: #0b5f27;
    margin-bottom: 16px;
  }

  .privacy-wrapper h2 {
    font-size: 1.25rem;
    margin-top: 28px;
    color: #114d23;
  }

  .privacy-wrapper p,
  .privacy-wrapper ul {
    margin-bottom: 16px;
  }

  .privacy-wrapper ul {
    padding-left: 1.2rem;
  }

  .privacy-wrapper .highlight {
    background: #f3f9f4;
    border-left: 4px solid #0b5f27;
    padding: 16px;
    border-radius: 12px;
    margin: 24px 0;
  }

  @media (max-width: 768px) {
    .privacy-wrapper {
      margin: 24px 16px;
      padding: 24px;
    }
  }
</style>

<main class="py-5" style="background: linear-gradient(180deg,#f5f9f6 0%, #ffffff 60%);">
  <div class="privacy-wrapper">
    <h1>Data Privacy Notice</h1>
    <p>
      Escuela de Sto. Rosario (ESR) values your trust. This notice explains how we collect, use, and protect
      the personal data shared through our website, online registration forms, and student portals in compliance
      with the Philippine Data Privacy Act of 2012 (RA 10173).
    </p>

    <div class="highlight">
      <strong>Scope.</strong> This notice covers all information submitted by students, parents/guardians,
      and website visitors through ESR&rsquo;s public portals and online registration facilities.
    </div>

    <h2>1. Information We Collect</h2>
    <p>Depending on your transaction with the school, we may collect:</p>
    <ul>
      <li>Identification details (name, birthdate, gender, learner reference number, previous school)</li>
      <li>Contact information (home address, email address, mobile or landline number)</li>
      <li>Family background (parent/guardian names, occupations, emergency contacts)</li>
      <li>Academic records (current grade level, strand, awards, enrollment history)</li>
      <li>Financial and payment references necessary to process tuition and fees</li>
    </ul>

    <h2>2. How We Use Your Information</h2>
    <p>
      Personal data is processed only for legitimate school purposes, including:
    </p>
    <ul>
      <li>Evaluating early-registration submissions and official enrollment</li>
      <li>Creating and maintaining student records and school accounts</li>
      <li>Facilitating payments, receipting, and financial clearances</li>
      <li>Communicating important academic, administrative, and emergency announcements</li>
      <li>Generating statistical and compliance reports required by law or regulators</li>
    </ul>

    <h2>3. Sharing and Disclosure</h2>
    <p>
      ESR does not sell personal data. Information is shared only with:
    </p>
    <ul>
      <li>Authorized school personnel (registrar, cashier, administrators, advisers) performing official duties</li>
      <li>Government agencies when mandated by law (e.g., DepEd, CHED, TESDA, BIR)</li>
      <li>Accredited service providers who support ESR&rsquo;s systems under strict confidentiality obligations</li>
    </ul>

    <h2>4. Data Protection and Retention</h2>
    <p>
      We implement physical, organizational, and technical safeguards to protect your data from unauthorized access,
      alteration, or misuse. Records are retained only while necessary for school operations, legal compliance,
      or legitimate archival purposes. Outdated records are securely disposed of.
    </p>

    <h2>5. Your Rights</h2>
    <p>You have the right to:</p>
    <ul>
      <li>Be informed about how your data is processed</li>
      <li>Access and request corrections to your records</li>
      <li>Object to or request deletion of data that is no longer necessary</li>
      <li>Withdraw consent for optional processing, subject to applicable policies</li>
      <li>File a complaint before the National Privacy Commission (NPC)</li>
    </ul>

    <h2>6. Contact Us</h2>
    <p>
      For questions or privacy-related requests, you may reach the ESR Data Protection Officer via:
    </p>
    <ul>
      <li>Email: <a href="mailto:esr.dpo@escoladestorosario.edu.ph">esr.dpo@escoladestorosario.edu.ph</a></li>
      <li>Office Address: 97 Dr. Sixto Antonio Ave., Rosario, Pasig City</li>
      <li>Telephone: Smart (0969) 354-2870 / Globe (0956) 351-2764</li>
    </ul>

    <p class="mb-0">
      By using our online services, you acknowledge that you have read and understood this notice.
      ESR may update this policy when necessary; significant changes will be announced through our official channels.
    </p>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
