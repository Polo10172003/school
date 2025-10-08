<?php
$page_title = 'Escuela de Sto. Rosario - Early Registration';

require_once __DIR__ . '/../includes/session.php';

require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/cleanup_expired_registrations.php';
cleanupExpiredRegistrations($conn);
$conn->close();

$registration = $_SESSION['registration'] ?? [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'school_year','yearlevel','course','lastname','firstname','middlename','gender','dob',
        'religion','emailaddress','telephone','address','last_school_attended','academic_honors',
        'father_name','father_occupation','mother_name','mother_occupation','guardian_name','guardian_occupation'
    ];

    $payload = [];
    foreach ($fields as $field) {
        $payload[$field] = trim($_POST[$field] ?? '');
    }

    $registration = array_merge($registration, $payload);

    $requiredFields = [
        'school_year'   => 'Please enter the school year.',
        'yearlevel'     => 'Please select a grade level.',
        'lastname'      => 'Please enter the last name.',
        'firstname'     => 'Please enter the first name.',
        'address'       => 'Please provide the address.',
        'telephone'     => 'Please enter the telephone number.',
        'emailaddress'  => 'Please provide a valid email address.',
        'dob'           => 'Please select a birth date.',
        'gender'        => 'Please select a gender.',
        'religion'      => 'Please select a religion.',
        'mother_name'   => "Please enter the mother's name.",
    ];

    if (in_array($registration['yearlevel'] ?? '', ['Grade 11', 'Grade 12'], true)) {
        $requiredFields['course'] = 'Please select a strand.';
    }

    foreach ($requiredFields as $field => $message) {
        $value = $registration[$field] ?? '';

        if ($field === 'emailaddress') {
            if ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = 'Please provide a valid email address.';
            }
            continue;
        }

        if ($value === '') {
            $errors[$field] = $message;
        }
    }

    $_SESSION['registration'] = $registration;

    if (empty($errors)) {
        header('Location: review_registration.php');
        exit();
    }
}

$school_year          = $registration['school_year'] ?? '';
$yearlevel            = $registration['yearlevel'] ?? '';
$course               = $registration['course'] ?? '';
$lastname             = $registration['lastname'] ?? '';
$firstname            = $registration['firstname'] ?? '';
$middlename           = $registration['middlename'] ?? '';
$gender               = $registration['gender'] ?? '';
$dob                  = $registration['dob'] ?? '';
$religion             = $registration['religion'] ?? '';
$emailaddress         = $registration['emailaddress'] ?? '';
$telephone            = $registration['telephone'] ?? '';
$address              = $registration['address'] ?? '';
$last_school_attended = $registration['last_school_attended'] ?? '';
$academic_honors      = $registration['academic_honors'] ?? '';
$father_name          = $registration['father_name'] ?? '';
$father_occupation    = $registration['father_occupation'] ?? '';
$mother_name          = $registration['mother_name'] ?? '';
$mother_occupation    = $registration['mother_occupation'] ?? '';
$guardian_name        = $registration['guardian_name'] ?? '';
$guardian_occupation  = $registration['guardian_occupation'] ?? '';

include '../includes/header.php';
?>

<link href="assets/css/registration.css" rel="stylesheet">

<main class="pt-5 pb-5">
    <div class="form-container main-content">
        <div class="form-header mb-4">
            <h2 class="fw-bold text-success mb-3">Early Registration Form</h2>
            <p class="mb-2">Please fill out the details below to reserve a slot for the upcoming school year.</p>
            <p class="text-muted mb-0">Fields marked with <span class="required">*</span> are required. Optional fields may be left blank.</p>
        </div>

        <form method="POST" novalidate>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    Please fill out the required fields highlighted below.
                </div>
            <?php endif; ?>
            <fieldset class="form-information">
                <h3 class="section-title">Enrollment Details</h3>
                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="school_year">S.Y. <span class="required">*</span></label>
                        <input type="text" id="school_year" name="school_year" placeholder="e.g. 2024-2025" required value="<?= htmlspecialchars($school_year); ?>" class="<?= isset($errors['school_year']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['school_year'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['school_year']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="yearlevel">Grade Level <span class="required">*</span></label>
                        <select id="yearlevel" name="yearlevel" required class="<?= isset($errors['yearlevel']) ? 'is-invalid' : '' ?>">
                            <option value="">Select Grade Level</option>
                            <?php
                            $levels = [
                                'Pre-Prime 1','Pre-Prime 2','Kindergarten','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6',
                                'Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12'
                            ];
                            foreach ($levels as $level):
                                $selected = $yearlevel === $level ? 'selected' : '';
                                echo "<option value=\"{$level}\" {$selected}>{$level}</option>";
                            endforeach;
                            ?>
                        </select>
                        <?php if (isset($errors['yearlevel'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['yearlevel']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row" id="courseGroup" style="display: none;">
                    <div class="form-group">
                        <label for="course">Strand (Grade 11-12 only) <span class="required">*</span></label>
                        <select id="course" name="course" class="<?= isset($errors['course']) ? 'is-invalid' : '' ?>">
                            <option value="">Select Strand</option>
                            <?php
                            $courses = ['ABM','GAS','HUMSS','ICT','TVL'];
                            foreach ($courses as $option):
                                $selected = $course === $option ? 'selected' : '';
                                echo "<option value=\"{$option}\" {$selected}>{$option}</option>";
                            endforeach;
                            ?>
                        </select>
                        <?php if (isset($errors['course'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['course']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-information">
                <h3 class="section-title">I. Information</h3>

                <div class="form-row three-cols">
                    <div class="form-group">
                        <label for="lastname">Last Name <span class="required">*</span></label>
                        <input type="text" id="lastname" name="lastname" required value="<?= htmlspecialchars($lastname); ?>" class="<?= isset($errors['lastname']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['lastname'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['lastname']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="firstname">First Name <span class="required">*</span></label>
                        <input type="text" id="firstname" name="firstname" required value="<?= htmlspecialchars($firstname); ?>" class="<?= isset($errors['firstname']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['firstname'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['firstname']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="middlename">Middle Name</label>
                        <input type="text" id="middlename" name="middlename" value="<?= htmlspecialchars($middlename); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="address">Address <span class="required">*</span></label>
                        <textarea id="address" name="address" rows="2" required class="<?= isset($errors['address']) ? 'is-invalid' : '' ?>"><?= htmlspecialchars($address); ?></textarea>
                        <?php if (isset($errors['address'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['address']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="telephone">Telephone <span class="required">*</span></label>
                        <input type="text" id="telephone" name="telephone" placeholder="e.g. 0917XXXXXXX" required value="<?= htmlspecialchars($telephone); ?>" class="<?= isset($errors['telephone']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['telephone'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['telephone']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="emailaddress">Email Address <span class="required">*</span></label>
                        <input type="email" id="emailaddress" name="emailaddress" required value="<?= htmlspecialchars($emailaddress); ?>" class="<?= isset($errors['emailaddress']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['emailaddress'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['emailaddress']); ?></div>
                        <?php endif; ?>
                        <small class="text-muted mt-1">Announcements will be sent to this email.</small>
                    </div>
                </div>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="dob">Birthday <span class="required">*</span></label>
                        <input type="date" id="dob" name="dob" required value="<?= htmlspecialchars($dob); ?>" class="<?= isset($errors['dob']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['dob'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['dob']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender <span class="required">*</span></label>
                        <select id="gender" name="gender" required class="<?= isset($errors['gender']) ? 'is-invalid' : '' ?>">
                            <option value="">Select Gender</option>
                            <option value="Female" <?= $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Male" <?= $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                        </select>
                        <?php if (isset($errors['gender'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['gender']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="religion">Religion <span class="required">*</span></label>
                        <select id="religion" name="religion" required class="<?= isset($errors['religion']) ? 'is-invalid' : '' ?>">
                            <option value="">Select Religion</option>
                            <?php
                            $religions = [
                                "Roman Catholic",
                                "Aglipay",
                                "Baptist",
                                "Born Again Christian",
                                "Buddhism",
                                "Christian Fellowship",
                                "Church of Christ",
                                "Dating Daan",
                                "Iglesia",
                                "Islam (Muslim)",
                                "Jehovah's Witness",
                                "Mormons",
                                "Seven Day Adventist",
                                "Others"
                            ];
                            foreach ($religions as $option) {
                                $selected = ($religion === $option) ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($option, ENT_QUOTES, 'UTF-8') . "\" {$selected}>" . htmlspecialchars($option, ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            ?>
                        </select>
                        <?php if (isset($errors['religion'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['religion']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="last_school_attended">Last School Attended</label>
                        <input type="text" id="last_school_attended" name="last_school_attended" value="<?= htmlspecialchars($last_school_attended); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="academic_honors">Academic Honors / Awards Received</label>
                        <textarea id="academic_honors" name="academic_honors" rows="2" placeholder="Optional"><?= htmlspecialchars($academic_honors); ?></textarea>
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-information">
                <h3 class="section-title">Parent and Guardian's Information</h3>

                <div class="form-group mb-3">
                    <label for="father_name">Father (Optional)</label>
                    <input type="text" id="father_name" name="father_name" value="<?= htmlspecialchars($father_name); ?>">
                </div>
                <div class="form-group mb-4">
                    <label for="father_occupation">Occupation (Optional)</label>
                    <input type="text" id="father_occupation" name="father_occupation" value="<?= htmlspecialchars($father_occupation); ?>">
                </div>

                <div class="form-group mb-3">
                    <label for="mother_name">Mother <span class="required">*</span></label>
                    <input type="text" id="mother_name" name="mother_name" required value="<?= htmlspecialchars($mother_name); ?>" class="<?= isset($errors['mother_name']) ? 'is-invalid' : '' ?>">
                    <?php if (isset($errors['mother_name'])): ?>
                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['mother_name']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group mb-4">
                    <label for="mother_occupation">Occupation (Optional)</label>
                    <input type="text" id="mother_occupation" name="mother_occupation" value="<?= htmlspecialchars($mother_occupation); ?>">
                </div>

                <div class="form-group mb-3">
                    <label for="guardian_name">Guardian (Optional)</label>
                    <input type="text" id="guardian_name" name="guardian_name" value="<?= htmlspecialchars($guardian_name); ?>">
                </div>
                <div class="form-group">
                    <label for="guardian_occupation">Occupation (Optional)</label>
                    <input type="text" id="guardian_occupation" name="guardian_occupation" value="<?= htmlspecialchars($guardian_occupation); ?>">
                </div>
            </fieldset>

            <button type="submit" class="btn">Review Information</button>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const yearLevel = document.getElementById('yearlevel');
        const courseGroup = document.getElementById('courseGroup');
        const courseSelect = document.getElementById('course');

        function toggleCourseField() {
            const level = yearLevel.value;
            const needsCourse = level === 'Grade 11' || level === 'Grade 12';
            courseGroup.style.display = needsCourse ? 'block' : 'none';
            courseSelect.required = needsCourse;
            if (!needsCourse) {
                courseSelect.value = '';
            }
        }

        yearLevel.addEventListener('change', toggleCourseField);
        toggleCourseField();
    });
</script>

<?php include '../includes/footer.php'; ?>
