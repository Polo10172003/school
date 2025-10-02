<?php
$page_title = 'Escuela de Sto. Rosario - Early Registration';

session_start();

$registration = $_SESSION['registration'] ?? [];

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

    $_SESSION['registration'] = array_merge($registration, $payload);

    header('Location: review_registration.php');
    exit();
}

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
            <fieldset class="form-information">
                <h3 class="section-title">Enrollment Details</h3>
                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="school_year">S.Y. <span class="required">*</span></label>
                        <input type="text" id="school_year" name="school_year" placeholder="e.g. 2024-2025" required value="<?= htmlspecialchars($school_year); ?>">
                    </div>
                    <div class="form-group">
                        <label for="yearlevel">Grade Level <span class="required">*</span></label>
                        <select id="yearlevel" name="yearlevel" required>
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
                    </div>
                </div>

                <div class="form-row" id="courseGroup" style="display: none;">
                    <div class="form-group">
                        <label for="course">Strand (Grade 11-12 only) <span class="required">*</span></label>
                        <select id="course" name="course">
                            <option value="">Select Strand</option>
                            <?php
                            $courses = ['ABM','GAS','HUMSS','ICT','TVL'];
                            foreach ($courses as $option):
                                $selected = $course === $option ? 'selected' : '';
                                echo "<option value=\"{$option}\" {$selected}>{$option}</option>";
                            endforeach;
                            ?>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-information">
                <h3 class="section-title">I. Information</h3>

                <div class="form-row three-cols">
                    <div class="form-group">
                        <label for="lastname">Last Name <span class="required">*</span></label>
                        <input type="text" id="lastname" name="lastname" required value="<?= htmlspecialchars($lastname); ?>">
                    </div>
                    <div class="form-group">
                        <label for="firstname">First Name <span class="required">*</span></label>
                        <input type="text" id="firstname" name="firstname" required value="<?= htmlspecialchars($firstname); ?>">
                    </div>
                    <div class="form-group">
                        <label for="middlename">Middle Name</label>
                        <input type="text" id="middlename" name="middlename" value="<?= htmlspecialchars($middlename); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="address">Address <span class="required">*</span></label>
                        <textarea id="address" name="address" rows="2" required><?= htmlspecialchars($address); ?></textarea>
                    </div>
                </div>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="telephone">Telephone <span class="required">*</span></label>
                        <input type="text" id="telephone" name="telephone" placeholder="e.g. 0917XXXXXXX" required value="<?= htmlspecialchars($telephone); ?>">
                    </div>
                    <div class="form-group">
                        <label for="emailaddress">Email Address <span class="required">*</span></label>
                        <input type="email" id="emailaddress" name="emailaddress" required value="<?= htmlspecialchars($emailaddress); ?>">
                        <small class="text-muted mt-1">Announcements will be sent to this email.</small>
                    </div>
                </div>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="dob">Birthday <span class="required">*</span></label>
                        <input type="date" id="dob" name="dob" required value="<?= htmlspecialchars($dob); ?>">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender <span class="required">*</span></label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Female" <?= $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Male" <?= $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                        </select>
                    </div>
                </div>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="religion">Religion <span class="required">*</span></label>
                        <select id="religion" name="religion" required>
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
                    <label for="father_name">Father <span class="required">*</span></label>
                    <input type="text" id="father_name" name="father_name" required value="<?= htmlspecialchars($father_name); ?>">
                </div>
                <div class="form-group mb-4">
                    <label for="father_occupation">Occupation (Optional)</label>
                    <input type="text" id="father_occupation" name="father_occupation" value="<?= htmlspecialchars($father_occupation); ?>">
                </div>

                <div class="form-group mb-3">
                    <label for="mother_name">Mother <span class="required">*</span></label>
                    <input type="text" id="mother_name" name="mother_name" required value="<?= htmlspecialchars($mother_name); ?>">
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
