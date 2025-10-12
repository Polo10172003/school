<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mailer.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$mode = isset($_GET['mode']) && $_GET['mode'] === 'registrar' ? 'registrar' : null;
$registrarId = $_SESSION['registrar_edit_student_id'] ?? null;
$fromRegistrar = $mode === 'registrar' || ($registrarId !== null);
$returningTag = $_SESSION['registration_returning_tag'] ?? '';
$previousSchoolYear = $_SESSION['registration_previous_school_year'] ?? '';

if ($fromRegistrar) {
    include __DIR__ . '/../db_connection.php';

    $studentId = $registrarId;
    if (!$studentId) {
        header('Location: ../Registrar/registrar_dashboard.php?msg=student_missing');
        exit;
    }

    $data = $_SESSION['registration'] ?? null;

    if (!$data) {
        $stmt = $conn->prepare('SELECT id, school_year, year, course, student_type, lastname, firstname, middlename, gender, dob, religion, emailaddress, telephone, address, last_school_attended, academic_honors, father_name, father_occupation, mother_name, mother_occupation, guardian_name, guardian_occupation, academic_status FROM students_registration WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $studentRow = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($studentRow) {
                $data = [
                    'school_year'          => $studentRow['school_year'] ?? '',
                    'yearlevel'            => $studentRow['year'] ?? '',
                    'course'               => $studentRow['course'] ?? '',
                    'lastname'             => $studentRow['lastname'] ?? '',
                    'firstname'            => $studentRow['firstname'] ?? '',
                    'middlename'           => $studentRow['middlename'] ?? '',
                    'gender'               => $studentRow['gender'] ?? '',
                    'dob'                  => $studentRow['dob'] ?? '',
                    'religion'             => $studentRow['religion'] ?? '',
                    'emailaddress'         => $studentRow['emailaddress'] ?? '',
                    'telephone'            => $studentRow['telephone'] ?? '',
                    'address'              => $studentRow['address'] ?? '',
                    'last_school_attended' => $studentRow['last_school_attended'] ?? '',
                    'academic_honors'      => $studentRow['academic_honors'] ?? '',
                    'father_name'          => $studentRow['father_name'] ?? '',
                    'father_occupation'    => $studentRow['father_occupation'] ?? '',
                    'mother_name'          => $studentRow['mother_name'] ?? '',
                    'mother_occupation'    => $studentRow['mother_occupation'] ?? '',
                    'guardian_name'        => $studentRow['guardian_name'] ?? '',
                    'guardian_occupation'  => $studentRow['guardian_occupation'] ?? '',
                    'student_type'         => $studentRow['student_type'] ?? '',
                    'academic_status'      => $studentRow['academic_status'] ?? '',
                ];
                $_SESSION['registration'] = $data;
                $_SESSION['registrar_edit_original'] = $studentRow;
                $_SESSION['registrar_edit_student_id'] = (int) $studentRow['id'];
            }
        }
    }

    if (!$data) {
        header('Location: ../Registrar/registrar_dashboard.php?msg=student_missing');
        exit;
    }

    $gradeLevels = [
        'Pre-Prime 1',
        'Pre-Prime 2',
        'Kindergarten',
        'Grade 1',
        'Grade 2',
        'Grade 3',
        'Grade 4',
        'Grade 5',
        'Grade 6',
        'Grade 7',
        'Grade 8',
        'Grade 9',
        'Grade 10',
        'Grade 11',
        'Grade 12',
    ];
    $studentTypes = ['New', 'Old'];
    $courses = ['ABM', 'GAS', 'HUMSS', 'ICT', 'TVL', 'STEM'];
    $genders = ['Female', 'Male'];
    $religions = ['Roman Catholic', 'Christian', 'Iglesia Ni Cristo', 'Islam', 'Others'];
    $academicStatuses = ['Pending', 'Ongoing', 'Ready', 'Waiting', 'Enrolled', 'Graduated', 'Failed'];

    $errors = [];
    $isEditMode = isset($_GET['edit']) && $_GET['edit'] === '1';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_edit'])) {
        $isEditMode = true;

        $fields = [
            'school_year'          => trim($_POST['school_year'] ?? ''),
            'year'                 => trim($_POST['year'] ?? ''),
            'course'               => trim($_POST['course'] ?? ''),
            'student_type'         => trim($_POST['student_type'] ?? ''),
            'lastname'             => trim($_POST['lastname'] ?? ''),
            'firstname'            => trim($_POST['firstname'] ?? ''),
            'middlename'           => trim($_POST['middlename'] ?? ''),
            'gender'               => trim($_POST['gender'] ?? ''),
            'dob'                  => trim($_POST['dob'] ?? ''),
            'religion'             => trim($_POST['religion'] ?? ''),
            'emailaddress'         => trim($_POST['emailaddress'] ?? ''),
            'telephone'            => trim($_POST['telephone'] ?? ''),
            'address'              => trim($_POST['address'] ?? ''),
            'last_school_attended' => trim($_POST['last_school_attended'] ?? ''),
            'academic_honors'      => trim($_POST['academic_honors'] ?? ''),
            'father_name'          => trim($_POST['father_name'] ?? ''),
            'father_occupation'    => trim($_POST['father_occupation'] ?? ''),
            'mother_name'          => trim($_POST['mother_name'] ?? ''),
            'mother_occupation'    => trim($_POST['mother_occupation'] ?? ''),
            'guardian_name'        => trim($_POST['guardian_name'] ?? ''),
            'guardian_occupation'  => trim($_POST['guardian_occupation'] ?? ''),
            'academic_status'      => trim($_POST['academic_status'] ?? ''),
        ];

        $required = [
            'school_year'     => 'School Year',
            'year'            => 'Grade Level',
            'student_type'    => 'Student Type',
            'lastname'        => 'Last Name',
            'firstname'       => 'First Name',
            'gender'          => 'Gender',
            'dob'             => 'Birthday',
            'religion'        => 'Religion',
            'emailaddress'    => 'Email Address',
            'telephone'       => 'Telephone',
            'address'         => 'Address',
            'academic_status' => 'Academic Status',
        ];

        foreach ($required as $key => $label) {
            if ($fields[$key] === '') {
                $errors[$key] = $label . ' is required.';
            }
        }

        if ($fields['year'] !== '' && !in_array($fields['year'], $gradeLevels, true)) {
            $errors['year'] = 'Select a valid grade level.';
        }

        if ($fields['student_type'] !== '' && !in_array($fields['student_type'], $studentTypes, true)) {
            $errors['student_type'] = 'Select a valid student type.';
        }

        if ($fields['gender'] !== '' && !in_array($fields['gender'], $genders, true)) {
            $errors['gender'] = 'Select a valid gender option.';
        }

        if ($fields['religion'] !== '' && !in_array($fields['religion'], $religions, true)) {
            $errors['religion'] = 'Select a valid religion option.';
        }

        if ($fields['academic_status'] !== '' && !in_array($fields['academic_status'], $academicStatuses, true)) {
            $errors['academic_status'] = 'Select a valid academic status option.';
        }

        if ($fields['emailaddress'] !== '' && !filter_var($fields['emailaddress'], FILTER_VALIDATE_EMAIL)) {
            $errors['emailaddress'] = 'Provide a valid email address.';
        }

        $isSeniorHigh = ($fields['year'] === 'Grade 11' || $fields['year'] === 'Grade 12');
        if ($isSeniorHigh && $fields['course'] === '') {
            $errors['course'] = 'Select a strand for senior high students.';
        }

        if (empty($errors)) {
            $updateSql = 'UPDATE students_registration SET school_year = ?, year = ?, course = ?, student_type = ?, lastname = ?, firstname = ?, middlename = ?, gender = ?, dob = ?, religion = ?, emailaddress = ?, telephone = ?, address = ?, last_school_attended = ?, academic_honors = ?, father_name = ?, father_occupation = ?, mother_name = ?, mother_occupation = ?, guardian_name = ?, guardian_occupation = ?, academic_status = ? WHERE id = ?';
            $stmt = $conn->prepare($updateSql);

            if (!$stmt) {
                $errors['general'] = 'Unable to prepare the update statement. ' . $conn->error;
            } else {
                $stmt->bind_param(
                    str_repeat('s', 22) . 'i',
                    $fields['school_year'],
                    $fields['year'],
                    $fields['course'],
                    $fields['student_type'],
                    $fields['lastname'],
                    $fields['firstname'],
                    $fields['middlename'],
                    $fields['gender'],
                    $fields['dob'],
                    $fields['religion'],
                    $fields['emailaddress'],
                    $fields['telephone'],
                    $fields['address'],
                    $fields['last_school_attended'],
                    $fields['academic_honors'],
                    $fields['father_name'],
                    $fields['father_occupation'],
                    $fields['mother_name'],
                    $fields['mother_occupation'],
                    $fields['guardian_name'],
                    $fields['guardian_occupation'],
                    $fields['academic_status'],
                    $studentId
                );

                if ($stmt->execute()) {
                    $original = $_SESSION['registrar_edit_original'] ?? [];
                    $mapKeys = array_keys($fields);
                    $hasChanges = false;

                    foreach ($mapKeys as $key) {
                        $originalValue = isset($original[$key]) ? trim((string) $original[$key]) : '';
                        if ($key === 'year') {
                            $originalValue = isset($original['year']) ? trim((string) $original['year']) : $originalValue;
                        }
                        $newValue = trim((string) $fields[$key]);
                        if ($originalValue !== $newValue) {
                            $hasChanges = true;
                            break;
                        }
                    }

                    if ($hasChanges && $fields['emailaddress'] !== '') {
                        try {
                            $mailer = new PHPMailer(true);
                            $mailerConfig = mailer_apply_defaults($mailer);
                            $mailer->setFrom(
                                (string) ($mailerConfig['from_email'] ?? 'no-reply@rosariodigital.site'),
                                (string) ($mailerConfig['from_name'] ?? 'Escuela De Sto. Rosario')
                            );
                            $mailer->addAddress($fields['emailaddress'], trim($fields['firstname'] . ' ' . $fields['lastname']));
                            $mailer->isHTML(true);
                            $mailer->Subject = 'Student Information Updated - Escuela De Sto. Rosario';
                            $mailer->Body = '<p>Dear ' . htmlspecialchars(trim($fields['firstname'] . ' ' . $fields['lastname']), ENT_QUOTES, 'UTF-8') . ',</p>' .
                                '<p>Your student information has been <strong>successfully updated</strong> by the registrar.</p>' .
                                '<p>If any detail looks incorrect, please contact the school immediately so we can assist you.</p>' .
                                '<br><p>Thank you,<br>Escuela De Sto. Rosario</p>';

                            $logMailer = static function (string $line) use ($studentId): void {
                                @file_put_contents(
                                    __DIR__ . '/../temp/email_worker_trace.log',
                                    sprintf("[%s] [RegistrarEdit:%d] %s\n", date('c'), $studentId, $line),
                                    FILE_APPEND
                                );
                            };

                            mailer_send_with_fallback(
                                $mailer,
                                [],
                                $logMailer,
                                (bool) ($mailerConfig['fallback_to_mail'] ?? false)
                            );
                        } catch (Exception $mailError) {
                            error_log('Registrar edit email error: ' . $mailError->getMessage());
                        }
                    }

                    $stmt->close();

                    $_SESSION['registration'] = array_merge($data, [
                        'school_year'          => $fields['school_year'],
                        'yearlevel'            => $fields['year'],
                        'course'               => $fields['course'],
                        'lastname'             => $fields['lastname'],
                        'firstname'            => $fields['firstname'],
                        'middlename'           => $fields['middlename'],
                        'gender'               => $fields['gender'],
                        'dob'                  => $fields['dob'],
                        'religion'             => $fields['religion'],
                        'emailaddress'         => $fields['emailaddress'],
                        'telephone'            => $fields['telephone'],
                        'address'              => $fields['address'],
                        'last_school_attended' => $fields['last_school_attended'],
                        'academic_honors'      => $fields['academic_honors'],
                        'father_name'          => $fields['father_name'],
                        'father_occupation'    => $fields['father_occupation'],
                        'mother_name'          => $fields['mother_name'],
                        'mother_occupation'    => $fields['mother_occupation'],
                        'guardian_name'        => $fields['guardian_name'],
                        'guardian_occupation'  => $fields['guardian_occupation'],
                        'student_type'         => $fields['student_type'],
                        'academic_status'      => $fields['academic_status'],
                    ]);
                    $_SESSION['registrar_edit_original'] = array_merge($_SESSION['registrar_edit_original'] ?? [], $fields, ['year' => $fields['year']]);

                    header('Location: ../Registrar/registrar_dashboard.php?msg=student_updated');
                    exit;
                }

                $errors['general'] = 'Unable to update student. ' . $stmt->error;
                $stmt->close();
            }

            $data = array_merge($data, [
                'school_year'          => $fields['school_year'],
                'yearlevel'            => $fields['year'],
                'course'               => $fields['course'],
                'lastname'             => $fields['lastname'],
                'firstname'            => $fields['firstname'],
                'middlename'           => $fields['middlename'],
                'gender'               => $fields['gender'],
                'dob'                  => $fields['dob'],
                'religion'             => $fields['religion'],
                'emailaddress'         => $fields['emailaddress'],
                'telephone'            => $fields['telephone'],
                'address'              => $fields['address'],
                'last_school_attended' => $fields['last_school_attended'],
                'academic_honors'      => $fields['academic_honors'],
                'father_name'          => $fields['father_name'],
                'father_occupation'    => $fields['father_occupation'],
                'mother_name'          => $fields['mother_name'],
                'mother_occupation'    => $fields['mother_occupation'],
                'guardian_name'        => $fields['guardian_name'],
                'guardian_occupation'  => $fields['guardian_occupation'],
                'student_type'         => $fields['student_type'],
                'academic_status'      => $fields['academic_status'],
            ]);
        }
    }
} else {
    $data = $_SESSION['registration'] ?? null;
    if (!$data) {
        header('Location: ineeditNaregistration.php');
        exit;
    }
}

function value(array $array, string $key): string
{
    return htmlspecialchars($array[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

$page_title = $fromRegistrar ? 'Student Record Overview' : 'Review Registration';
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
        max-width: 960px;
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
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
        display: inline-flex;
        align-items: center;
        gap: 8px;
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
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
        margin-top: 20px;
    }
    .form-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-field label {
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #5d6c61;
        font-weight: 600;
    }
    .form-field input,
    .form-field select,
    .form-field textarea {
        border: 1px solid #cfd8d3;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 15px;
        font-family: inherit;
        background: #f7faf9;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
        outline: none;
        border-color: #0b5f27;
        box-shadow: 0 0 0 3px rgba(11, 95, 39, 0.15);
        background: #fff;
    }
    .form-field textarea {
        min-height: 90px;
        resize: vertical;
    }
    .error-list {
        margin: 20px 0 0;
        padding: 16px 20px;
        background: rgba(220, 53, 69, 0.12);
        border-left: 4px solid #dc3545;
        border-radius: 10px;
    }
    .error-list strong {
        display: block;
        margin-bottom: 6px;
        color: #a71d2a;
    }
    .error-list ul {
        margin: 0;
        padding-left: 18px;
        color: #6b0f1a;
    }
</style>

<main>
    <div class="review-wrapper">
        <?php if ($fromRegistrar && $returningTag !== ''): ?>
            <div class="alert alert-warning" role="alert" style="margin-top: -10px;">
                <strong><?= htmlspecialchars($returningTag); ?></strong>
                <?php if ($previousSchoolYear !== ''): ?>
                    — last enrolled S.Y.: <?= htmlspecialchars($previousSchoolYear); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($fromRegistrar && isset($isEditMode) && $isEditMode): ?>
            <h1>Edit Student Record</h1>
            <p class="subtext">Update the student's information below. Parents will receive a confirmation when changes are saved.</p>

            <?php if (!empty($errors)): ?>
                <div class="error-list">
                    <strong>We found a few issues:</strong>
                    <ul>
                        <?php foreach ($errors as $message): ?>
                            <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="registrar_edit" value="1">

                <div class="section">
                    <h2>Enrollment Details</h2>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="school_year">School Year<span class="text-danger">*</span></label>
                            <input type="text" id="school_year" name="school_year" placeholder="e.g. 2024-2025" value="<?= value($data, 'school_year'); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="student_type">Student Type<span class="text-danger">*</span></label>
                            <select id="student_type" name="student_type" required>
                                <option value="">Select Type</option>
                                <?php foreach ($studentTypes as $type): ?>
                                    <option value="<?= $type; ?>" <?= ($data['student_type'] ?? '') === $type ? 'selected' : ''; ?>><?= $type; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="year">Grade Level<span class="text-danger">*</span></label>
                            <select id="year" name="year" required>
                                <option value="">Select Grade Level</option>
                                <?php foreach ($gradeLevels as $level): ?>
                                    <option value="<?= $level; ?>" <?= ($data['yearlevel'] ?? '') === $level ? 'selected' : ''; ?>><?= $level; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field" id="strandField">
                            <label for="course">Strand / Track</label>
                            <select id="course" name="course">
                                <option value="">Select Strand</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course; ?>" <?= ($data['course'] ?? '') === $course ? 'selected' : ''; ?>><?= $course; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2>Student Information</h2>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="lastname">Last Name<span class="text-danger">*</span></label>
                            <input type="text" id="lastname" name="lastname" value="<?= value($data, 'lastname'); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="firstname">First Name<span class="text-danger">*</span></label>
                            <input type="text" id="firstname" name="firstname" value="<?= value($data, 'firstname'); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="middlename">Middle Name</label>
                            <input type="text" id="middlename" name="middlename" value="<?= value($data, 'middlename'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="gender">Gender<span class="text-danger">*</span></label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <?php foreach ($genders as $gender): ?>
                                    <option value="<?= $gender; ?>" <?= ($data['gender'] ?? '') === $gender ? 'selected' : ''; ?>><?= $gender; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="dob">Birthday<span class="text-danger">*</span></label>
                            <input type="date" id="dob" name="dob" value="<?= value($data, 'dob'); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="religion">Religion<span class="text-danger">*</span></label>
                            <select id="religion" name="religion" required>
                                <option value="">Select Religion</option>
                                <?php foreach ($religions as $religion): ?>
                                    <option value="<?= $religion; ?>" <?= ($data['religion'] ?? '') === $religion ? 'selected' : ''; ?>><?= $religion; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="telephone">Telephone<span class="text-danger">*</span></label>
                            <input type="text" id="telephone" name="telephone" value="<?= value($data, 'telephone'); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="emailaddress">Email Address<span class="text-danger">*</span></label>
                            <input type="email" id="emailaddress" name="emailaddress" value="<?= value($data, 'emailaddress'); ?>" required>
                        </div>
                    </div>
                    <div class="form-grid" style="grid-template-columns: 1fr;">
                        <div class="form-field">
                            <label for="address">Address<span class="text-danger">*</span></label>
                            <textarea id="address" name="address" rows="3" required><?= value($data, 'address'); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2>Academic Background</h2>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="last_school_attended">Last School Attended</label>
                            <input type="text" id="last_school_attended" name="last_school_attended" value="<?= value($data, 'last_school_attended'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="academic_honors">Academic Honors / Awards</label>
                            <input type="text" id="academic_honors" name="academic_honors" value="<?= value($data, 'academic_honors'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="academic_status">Academic Status<span class="text-danger">*</span></label>
                            <select id="academic_status" name="academic_status" required>
                                <option value="">Select Status</option>
                                <?php foreach ($academicStatuses as $status): ?>
                                    <option value="<?= $status; ?>" <?= ($data['academic_status'] ?? '') === $status ? 'selected' : ''; ?>><?= $status; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2>Parent / Guardian Information</h2>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="father_name">Father</label>
                            <input type="text" id="father_name" name="father_name" value="<?= value($data, 'father_name'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="father_occupation">Father's Occupation</label>
                            <input type="text" id="father_occupation" name="father_occupation" value="<?= value($data, 'father_occupation'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="mother_name">Mother</label>
                            <input type="text" id="mother_name" name="mother_name" value="<?= value($data, 'mother_name'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="mother_occupation">Mother's Occupation</label>
                            <input type="text" id="mother_occupation" name="mother_occupation" value="<?= value($data, 'mother_occupation'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="guardian_name">Guardian</label>
                            <input type="text" id="guardian_name" name="guardian_name" value="<?= value($data, 'guardian_name'); ?>">
                        </div>
                        <div class="form-field">
                            <label for="guardian_occupation">Guardian's Occupation</label>
                            <input type="text" id="guardian_occupation" name="guardian_occupation" value="<?= value($data, 'guardian_occupation'); ?>">
                        </div>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="StudentNoVerification/review_registration.php?mode=registrar" class="btn btn-secondary">Cancel</a>
                </div>
            </form>

            <script>
                const gradeSelect = document.getElementById('year');
                const strandField = document.getElementById('strandField');
                const courseSelect = document.getElementById('course');

                function toggleStrandField() {
                    const grade = gradeSelect.value;
                    const needsStrand = grade === 'Grade 11' || grade === 'Grade 12';
                    strandField.style.display = needsStrand ? 'block' : 'none';
                    if (!needsStrand) {
                        courseSelect.value = '';
                    }
                }

                gradeSelect.addEventListener('change', toggleStrandField);
                toggleStrandField();
            </script>
        <?php else: ?>
            <h1><?= $fromRegistrar ? 'Student Record Overview' : 'Review Your Registration'; ?></h1>
            <p class="subtext">
                <?= $fromRegistrar
                    ? 'Review the student details below. Use the edit button to make updates.'
                    : 'Please verify the details below before submitting your registration. If you need to make corrections, choose "Edit Information" to return to the form.'; ?>
            </p>

            <div class="section">
                <h2>Enrollment Details</h2>
                <div class="details-grid">
                    <div class="detail-item"><span class="label">School Year</span><span class="value"><?= value($data, 'school_year'); ?></span></div>
                    <div class="detail-item"><span class="label">Grade Level</span><span class="value"><?= value($data, 'yearlevel'); ?></span></div>
                <div class="detail-item"><span class="label">Strand</span><span class="value"><?= value($data, 'course'); ?></span></div>
                <?php if ($fromRegistrar): ?>
                    <div class="detail-item"><span class="label">Student Type</span><span class="value"><?= value($data, 'student_type'); ?></span></div>
                    <div class="detail-item"><span class="label">Academic Status</span><span class="value"><?= value($data, 'academic_status'); ?></span></div>
                <?php endif; ?>
            </div>
            </div>

            <div class="section">
                <h2>Student Information</h2>
                <div class="details-grid">
                    <div class="detail-item"><span class="label">Last Name</span><span class="value"><?= value($data, 'lastname'); ?></span></div>
                    <div class="detail-item"><span class="label">First Name</span><span class="value"><?= value($data, 'firstname'); ?></span></div>
                    <div class="detail-item"><span class="label">Middle Name</span><span class="value"><?= value($data, 'middlename'); ?></span></div>
                    <div class="detail-item"><span class="label">Birthday</span><span class="value"><?= value($data, 'dob'); ?></span></div>
                    <div class="detail-item"><span class="label">Gender</span><span class="value"><?= value($data, 'gender'); ?></span></div>
                    <div class="detail-item"><span class="label">Religion</span><span class="value"><?= value($data, 'religion'); ?></span></div>
                    <div class="detail-item"><span class="label">Email Address</span><span class="value"><?= value($data, 'emailaddress'); ?></span></div>
                    <div class="detail-item"><span class="label">Telephone</span><span class="value"><?= value($data, 'telephone'); ?></span></div>
                    <div class="detail-item" style="grid-column: 1 / -1;"><span class="label">Address</span><span class="value"><?= value($data, 'address'); ?></span></div>
                </div>
            </div>

            <div class="section">
                <h2>Academic Background</h2>
                <div class="details-grid">
                    <div class="detail-item"><span class="label">Last School Attended</span><span class="value"><?= value($data, 'last_school_attended'); ?></span></div>
                    <div class="detail-item"><span class="label">Academic Honors / Awards</span><span class="value"><?= value($data, 'academic_honors'); ?></span></div>
                </div>
            </div>

            <div class="section">
                <h2>Parent / Guardian Information</h2>
                <div class="details-grid">
                    <div class="detail-item"><span class="label">Father</span><span class="value"><?= value($data, 'father_name'); ?></span></div>
                    <div class="detail-item"><span class="label">Father Occupation</span><span class="value"><?= value($data, 'father_occupation'); ?></span></div>
                    <div class="detail-item"><span class="label">Mother</span><span class="value"><?= value($data, 'mother_name'); ?></span></div>
                    <div class="detail-item"><span class="label">Mother Occupation</span><span class="value"><?= value($data, 'mother_occupation'); ?></span></div>
                    <div class="detail-item"><span class="label">Guardian</span><span class="value"><?= value($data, 'guardian_name'); ?></span></div>
                    <div class="detail-item"><span class="label">Guardian Occupation</span><span class="value"><?= value($data, 'guardian_occupation'); ?></span></div>
                </div>
            </div>

            <div class="actions">
                <?php if ($fromRegistrar): ?>
                    <a class="btn btn-primary" href="StudentNoVerification/review_registration.php?mode=registrar&edit=1">Edit Student Information</a>
                    <a class="btn btn-secondary" href="Registrar/registrar_dashboard.php">Back to Dashboard</a>
                <?php else: ?>
                    <a href="StudentNoVerification/ineeditNaregistration.php" class="btn btn-secondary">Edit Information</a>
                    <form method="POST" action="StudentNoVerification/submit_registration.php" style="margin:0;">
                        <button type="submit" class="btn btn-primary">Submit Registration</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
