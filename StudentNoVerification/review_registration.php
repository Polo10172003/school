<?php
session_start();

$data = $_SESSION['registration'] ?? null;

if (!$data) {
    header('Location: ineeditNaregistration.php');
    exit();
}

function value($array, $key) {
    return htmlspecialchars($array[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

$page_title = 'Review Registration';
include '../includes/header.php';
?>

<style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f3;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .review-wrapper {
            max-width: 1100px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.08);
        }
        h1 {
            color: #0b5f27;
            margin-bottom: 10px;
        }
        p.subtext {
            margin-top: 0;
            color: #555;
        }
        .section {
            margin-top: 30px;
        }
        .section h2 {
            font-size: 20px;
            color: #0b5f27;
            border-bottom: 2px solid #0b5f27;
            padding-bottom: 8px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px 24px;
            margin-top: 15px;
        }
        .detail-item {
            background: #f7faf9;
            border: 1px solid #dbe5df;
            border-radius: 8px;
            padding: 12px 16px;
        }
        .detail-item span {
            display: block;
        }
        .label {
            font-size: 12px;
            text-transform: uppercase;
            color: #5d6c61;
            letter-spacing: 0.5px;
        }
        .value {
            font-size: 15px;
            font-weight: 600;
            color: #1d3127;
            margin-top: 4px;
        }
        .actions {
            margin-top: 35px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .btn {
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-secondary {
            background: #7a7a7a;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #626262;
        }
        .btn-primary {
            background: #0b5f27;
            color: #fff;
        }
        .btn-primary:hover {
            background: #094c1f;
        }
    </style>
</style>

<main>
    <div class="review-wrapper">
        <h1>Review Your Registration</h1>
        <p class="subtext">Please verify the details below before submitting your registration. If you need to make corrections, choose "Edit Information" to return to the form.</p>

        <div class="section">
            <h2>Admission Details</h2>
            <div class="details-grid">
                <div class="detail-item"><span class="label">LRN</span><span class="value"><?= value($data, 'lrn'); ?></span></div>
                <div class="detail-item"><span class="label">Desired Grade Level</span><span class="value"><?= value($data, 'yearlevel'); ?></span></div>
                <div class="detail-item"><span class="label">Strand</span><span class="value"><?= value($data, 'course'); ?></span></div>
            </div>
        </div>

        <div class="section">
            <h2>Student Information</h2>
            <div class="details-grid">
                <div class="detail-item"><span class="label">Last Name</span><span class="value"><?= value($data, 'lastname'); ?></span></div>
                <div class="detail-item"><span class="label">First Name</span><span class="value"><?= value($data, 'firstname'); ?></span></div>
                <div class="detail-item"><span class="label">Middle Name</span><span class="value"><?= value($data, 'middlename'); ?></span></div>
                <div class="detail-item"><span class="label">Suffix</span><span class="value"><?= value($data, 'suffixname'); ?></span></div>
                <div class="detail-item"><span class="label">Gender</span><span class="value"><?= value($data, 'gender'); ?></span></div>
                <div class="detail-item"><span class="label">Civil Status</span><span class="value"><?= value($data, 'status'); ?></span></div>
                <div class="detail-item"><span class="label">Citizenship</span><span class="value"><?= value($data, 'citizenship'); ?></span></div>
                <div class="detail-item"><span class="label">Date of Birth</span><span class="value"><?= value($data, 'dob'); ?></span></div>
                <div class="detail-item"><span class="label">Birthplace</span><span class="value"><?= value($data, 'birthplace'); ?></span></div>
                <div class="detail-item"><span class="label">Religion</span><span class="value"><?= value($data, 'religion'); ?></span></div>
                <div class="detail-item"><span class="label">Email Address</span><span class="value"><?= value($data, 'emailaddress'); ?></span></div>
                <div class="detail-item"><span class="label">Mobile Number</span><span class="value"><?= value($data, 'mobnumber'); ?></span></div>
                <div class="detail-item"><span class="label">Telephone Number</span><span class="value"><?= value($data, 'telnumber'); ?></span></div>
            </div>
        </div>

        <div class="section">
            <h2>Current Address</h2>
            <div class="details-grid">
                <div class="detail-item"><span class="label">House No.</span><span class="value"><?= value($data, 'streetno'); ?></span></div>
                <div class="detail-item"><span class="label">Street</span><span class="value"><?= value($data, 'street'); ?></span></div>
                <div class="detail-item"><span class="label">Subdivision</span><span class="value"><?= value($data, 'subd'); ?></span></div>
                <div class="detail-item"><span class="label">Barangay</span><span class="value"><?= value($data, 'brgy'); ?></span></div>
                <div class="detail-item"><span class="label">City / Municipality</span><span class="value"><?= value($data, 'city'); ?></span></div>
                <div class="detail-item"><span class="label">Province</span><span class="value"><?= value($data, 'province'); ?></span></div>
                <div class="detail-item"><span class="label">ZIP Code</span><span class="value"><?= value($data, 'zipcode'); ?></span></div>
            </div>
        </div>

        <div class="section">
            <h2>Permanent Address</h2>
            <div class="details-grid">
                <div class="detail-item"><span class="label">House No.</span><span class="value"><?= value($data, 'p_streetno'); ?></span></div>
                <div class="detail-item"><span class="label">Street</span><span class="value"><?= value($data, 'p_street'); ?></span></div>
                <div class="detail-item"><span class="label">Subdivision</span><span class="value"><?= value($data, 'p_subd'); ?></span></div>
                <div class="detail-item"><span class="label">Barangay</span><span class="value"><?= value($data, 'p_brgy'); ?></span></div>
                <div class="detail-item"><span class="label">City / Municipality</span><span class="value"><?= value($data, 'p_city'); ?></span></div>
                <div class="detail-item"><span class="label">Province</span><span class="value"><?= value($data, 'p_province'); ?></span></div>
                <div class="detail-item"><span class="label">ZIP Code</span><span class="value"><?= value($data, 'p_zipcode'); ?></span></div>
            </div>
        </div>

        <div class="section">
            <h2>Parent / Guardian Information</h2>
            <div class="details-grid">
                <div class="detail-item"><span class="label">Father</span><span class="value"><?= trim(value($data, 'father_firstname') . ' ' . value($data, 'father_middlename') . ' ' . value($data, 'father_lastname') . ' ' . value($data, 'father_suffixname')); ?></span></div>
                <div class="detail-item"><span class="label">Father Mobile</span><span class="value"><?= value($data, 'father_mobnumber'); ?></span></div>
                <div class="detail-item"><span class="label">Father Email</span><span class="value"><?= value($data, 'father_emailaddress'); ?></span></div>
                <div class="detail-item"><span class="label">Father Occupation</span><span class="value"><?= value($data, 'father_occupation'); ?></span></div>

                <div class="detail-item"><span class="label">Mother</span><span class="value"><?= trim(value($data, 'mother_firstname') . ' ' . value($data, 'mother_middlename') . ' ' . value($data, 'mother_lastname') . ' ' . value($data, 'mother_suffixname')); ?></span></div>
                <div class="detail-item"><span class="label">Mother Mobile</span><span class="value"><?= value($data, 'mother_mobnumber'); ?></span></div>
                <div class="detail-item"><span class="label">Mother Email</span><span class="value"><?= value($data, 'mother_emailaddress'); ?></span></div>
                <div class="detail-item"><span class="label">Mother Occupation</span><span class="value"><?= value($data, 'mother_occupation'); ?></span></div>

                <div class="detail-item"><span class="label">Guardian</span><span class="value"><?= trim(value($data, 'guardian_firstname') . ' ' . value($data, 'guardian_middlename') . ' ' . value($data, 'guardian_lastname') . ' ' . value($data, 'guardian_suffixname')); ?></span></div>
                <div class="detail-item"><span class="label">Guardian Mobile</span><span class="value"><?= value($data, 'guardian_mobnumber'); ?></span></div>
                <div class="detail-item"><span class="label">Guardian Email</span><span class="value"><?= value($data, 'guardian_emailaddress'); ?></span></div>
                <div class="detail-item"><span class="label">Relationship to Student</span><span class="value"><?= value($data, 'guardian_relationship'); ?></span></div>
            </div>
        </div>

        <div class="actions">
            <a href="StudentNoVerification/ineeditNaregistration.php" class="btn btn-secondary">Edit Information</a>
            <form method="POST" action="StudentNoVerification/submit_registration.php" style="margin:0;">
                <button type="submit" class="btn btn-primary">Submit Registration</button>
            </form>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
