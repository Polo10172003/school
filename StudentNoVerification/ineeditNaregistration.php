<?php 

$page_title = 'Escuela de Sto. Rosario - Early Registration';

session_start();

// Prefill variables from session
$registration = $_SESSION['registration'] ?? [];
$lrn = $registration['lrn'] ?? '';
$firstname = $registration['firstname'] ?? '';
$lastname = $registration['lastname'] ?? '';
$middlename = $registration['middlename'] ?? '';
$suffixname = $registration['suffixname'] ?? '';
$yearlevel = $registration['yearlevel'] ?? '';
$gender = $registration['gender'] ?? '';
$status = $registration['status'] ?? '';
$citizenship = $registration['citizenship'] ?? '';
$dob = $registration['dob'] ?? '';
$birthplace = $registration['birthplace'] ?? '';
$religion = $registration['religion'] ?? '';
$telnumber = $registration['telnumber'] ?? '';
$mobnumber = $registration['mobnumber'] ?? '';
$emailaddress = $registration['emailaddress'] ?? '';
$streetno = $registration['streetno'] ?? '';
$street = $registration['street'] ?? '';
$subd = $registration['subd'] ?? '';
$brgy = $registration['brgy'] ?? '';
$city = $registration['city'] ?? '';
$province = $registration['province'] ?? '';
$zipcode = $registration['zipcode'] ?? '';
$mother = $registration['mother'] ?? '';
$father = $registration['father'] ?? '';
$gname = $registration['gname'] ?? '';
$occupation = $registration['occupation'] ?? '';
$relationship = $registration['relationship'] ?? '';

// Father's info
$father_lastname = $registration['father_lastname'] ?? '';
$father_firstname = $registration['father_firstname'] ?? '';
$father_middlename = $registration['father_middlename'] ?? '';
$father_suffixname = $registration['father_suffixname'] ?? '';
$father_mobnumber = $registration['father_mobnumber'] ?? '';
$father_emailaddress = $registration['father_emailaddress'] ?? '';
$father_occupation = $registration['father_occupation'] ?? '';

// Mother's info
$mother_lastname = $registration['mother_lastname'] ?? '';
$mother_firstname = $registration['mother_firstname'] ?? '';
$mother_middlename = $registration['mother_middlename'] ?? '';
$mother_suffixname = $registration['mother_suffixname'] ?? '';
$mother_mobnumber = $registration['mother_mobnumber'] ?? '';
$mother_emailaddress = $registration['mother_emailaddress'] ?? '';
$mother_occupation = $registration['mother_occupation'] ?? '';

// Guardian's info
$guardian_lastname = $registration['guardian_lastname'] ?? '';
$guardian_firstname = $registration['guardian_firstname'] ?? '';
$guardian_middlename = $registration['guardian_middlename'] ?? '';
$guardian_suffixname = $registration['guardian_suffixname'] ?? '';
$guardian_mobnumber = $registration['guardian_mobnumber'] ?? '';
$guardian_emailaddress = $registration['guardian_emailaddress'] ?? '';
$guardian_occupation = $registration['guardian_occupation'] ?? '';
$guardian_relationship = $registration['guardian_relationship'] ?? '';

if (isset($_POST['sameAddress'])) {
    $_POST['p_streetno'] = $_POST['streetno'];
    $_POST['p_street']   = $_POST['street'];
    $_POST['p_subd']     = $_POST['subd'];
    $_POST['p_brgy']     = $_POST['brgy'];
    $_POST['p_city']     = $_POST['city'];
    $_POST['p_province'] = $_POST['province'];
    $_POST['p_zipcode']  = $_POST['zipcode'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['registration'] = array_merge($_SESSION['registration'] ?? [], $_POST);

    header("Location: submit_registration.php");
    exit();
}

include '../includes/header.php';
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Early Registration Form</title>
    <link href="assets/css/registration.css" rel="stylesheet">
<body>
<main>
    <!-- Main Content -->
    <div class="form-container mt-5 main-content">
        <div class="form-header">
            <h2>Early Registration Form</h2>
            <p>We extend our welcome to primary and secondary students, junior high school completers, and prospective senior high school students who wish to join our academic community.</p>
            <p>To ensure an efficient admissions process, applicants are advised to complete the early registration form.</p>
        </div>
        <div class="form-content">
            <form action="" method="POST">

                <!-- Admission Information -->
                <fieldset>
                    <div class="form-admission">
                        <h3 class="section-title">Admission Information</h3>
                            <div class="form-row">
                                <!-- LRN -->
                                <div class="form-group">
                                    <label for="lrn">LRN <span class="required">*</span></label>
                                    <input type="text" id="lrn" name="lrn" value="<?php echo htmlspecialchars($lrn); ?>" placeholder="Enter LRN">
                                </div>

                                <!-- Year Level Dropdown -->
                                <div class="form-group" id="yearLevelGroup">
                                    <label for="yearlevel">Year Level</label>
                                    <select id="yearlevel" name="yearlevel" required>
                                        <option value="">Select Year Level</option>
                                        <option value="Kinder 1" <?= $yearlevel=='Kinder 1'?'selected':''; ?>>Kinder 1</option>
                                        <option value="Kinder 2" <?= $yearlevel=='Kinder 2'?'selected':''; ?>>Kinder 2</option>
                                        <option value="Grade 1" <?= $yearlevel=='Grade 1'?'selected':''; ?>>Grade 1</option>
                                        <option value="Grade 2" <?= $yearlevel=='Grade 2'?'selected':''; ?>>Grade 2</option>
                                        <option value="Grade 3" <?= $yearlevel=='Grade 3'?'selected':''; ?>>Grade 3</option>
                                        <option value="Grade 4" <?= $yearlevel=='Grade 4'?'selected':''; ?>>Grade 4</option>
                                        <option value="Grade 5" <?= $yearlevel=='Grade 5'?'selected':''; ?>>Grade 5</option>
                                        <option value="Grade 6" <?= $yearlevel=='Grade 6'?'selected':''; ?>>Grade 6</option>
                                        <option value="Grade 7" <?= $yearlevel=='Grade 7'?'selected':''; ?>>Grade 7</option>
                                        <option value="Grade 8" <?= $yearlevel=='Grade 8'?'selected':''; ?>>Grade 8</option>
                                        <option value="Grade 9" <?= $yearlevel=='Grade 9'?'selected':''; ?>>Grade 9</option>
                                        <option value="Grade 10" <?= $yearlevel=='Grade 10'?'selected':''; ?>>Grade 10</option>
                                        <option value="Grade 11" <?= $yearlevel=='Grade 11'?'selected':''; ?>>Grade 11</option>
                                        <option value="Grade 12" <?= $yearlevel=='Grade 12'?'selected':''; ?>>Grade 12</option>
                                    </select>
                                </div>

                                <!-- Course/Strand Dropdown -->
                                <div class="form-group" id="courseGroup" style="display:none;">
                                    <label for="course">Strand</label>
                                    <select id="course" name="course">
                                        <option value="">Select Strand</option>
                                        <option value="ABM" <?= ($registration['course'] ?? '')=='ABM'?'selected':''; ?>>ABM</option>
                                        <option value="GAS" <?= ($registration['course'] ?? '')=='GAS'?'selected':''; ?>>GAS</option>
                                        <option value="HUMMS" <?= ($registration['course'] ?? '')=='HUMMS'?'selected':''; ?>>HUMMS</option>
                                        <option value="TVL" <?= ($registration['course'] ?? '')=='TVL'?'selected':''; ?>>TVL</option>
                                        <option value="ICT" <?= ($registration['course'] ?? '')=='ICT'?'selected':''; ?>>ICT</option>
                                    </select>
                                </div>
                            </div>
                    </div>

                    <!-- Student Information -->
                    <div class="form-information">
                        <h3 class="section-title">Student's Information</h3>
                        
                        <div class="form-row four-cols">
                            <div class="form-group">
                                <label for="lastname">Last Name <span class="required">*</span></label>
                                <input type="text" id="lastname" name="lastname" required value="<?php echo htmlspecialchars($lastname); ?>"
                                placeholder ="Surname">
                            </div>
                            <div class="form-group">
                                <label for="firstname">First Name <span class="required">*</span></label>
                                <input type="text" id="firstname" name="firstname" required value="<?php echo htmlspecialchars($firstname); ?>"
                                placeholder ="Given Name">
                            </div>
                            <div class="form-group">
                                <label for="middlename">Middle Name <span class="required">*</span></label>
                                <input type="text" id="middlename" name="middlename" required value="<?php echo htmlspecialchars($middlename); ?>"
                                placeholder ="Middle Name">
                            </div>
                            <div class="form-group">
                                <label for="suffixname">Suffix Name</label>
                                <input type="text" id="suffixname" name="suffixname" value="<?php echo htmlspecialchars($suffixname); ?>"
                                placeholder ="(e.g. JR.)">
                            </div>
                        </div>     
                        <div class="form-row four-cols">     
                            <div class="form-group">
                                <label for="gender">Gender <span class="required">*</span></label>
                                <select id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Female" <?php echo ($gender=='Female')?'selected':''; ?>>Female</option>
                                    <option value="Male" <?php echo ($gender=='Male')?'selected':''; ?>>Male</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status">Status <span class="required">*</span></label>
                                <select id="status" name="status" required>
                                <option value="">Civil Status</option>
                                    <option value="single" <?php echo ($status=='singlet')?'selected':''; ?>>Single</option>
                                    <option value="married" <?php echo ($status=='married')?'selected':''; ?>>Married</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="citizenship">Citizenship <span class="required">*</span></label>
                                <input type="text" id="citizenship" name="citizenship" required value="<?php echo htmlspecialchars($citizenship); ?>"
                                placeholder ="e.g. Filipino">
                            </div>
                            
                            <div class="form-group">
                                <label for="dob">Date of Birth <span class="required">*</span></label>
                                <input type="date" id="dob" name="dob" required value="<?php echo htmlspecialchars($dob); ?>">
                            </div>
                        </div>

                        <div class="form-row two-cols" >
                            <div class="form-group">
                                <label for="birthplace">Birthplace <span class="required">*</span></label>
                                <input type="text" id="birthplace" name="birthplace" required value="<?php echo htmlspecialchars($birthplace); ?>"
                                placeholder="Birthplace">
                            </div>
                            <div class="form-group">
                                <label for="religion">Religion <span class="required">*</span></label>
                                <select id="religion" name="religion" required>
                                    <option value="">Select Religion</option>
                                    <option value="romCat" <?php echo ($religion=='romCat')?'selected':''; ?>>Roman Catholic</option>
                                    <option value="aglipay" <?php echo ($religion=='aglipay')?'selected':''; ?>>Aglipay</option>
                                    <option value="baptist" <?php echo ($religion=='baptist')?'selected':''; ?>>Baptist</option>
                                    <option value="bornagain" <?php echo ($religion=='bornagain')?'selected':''; ?>>Born Again Christian</option>
                                    <option value="buddhism" <?php echo ($religion=='buddhism')?'selected':''; ?>>Buddhism</option>
                                    <option value="christianfellowship" <?php echo ($religion=='christianfellowship')?'selected':''; ?>>Christian Fellowship</option>
                                    <option value="coc" <?php echo ($religion=='coc')?'selected':''; ?>>Church of Christ</option>
                                    <option value="datingdaan" <?php echo ($religion=='datingdaan')?'selected':''; ?>>Dating Daan</option>
                                    <option value="Iglesia" <?php echo ($religion=='Iglesia')?'selected':''; ?>>Iglesia</option>
                                    <option value="islammuslim" <?php echo ($religion=='islammuslim')?'selected':''; ?>>Islam (Muslim)</option>
                                    <option value="jw" <?php echo ($religion=='jw')?'selected':''; ?>>Jehovah's Witness</option>
                                    <option value="mormons" <?php echo ($religion=='mormons')?'selected':''; ?>>Mormons</option>
                                    <option value="svd" <?php echo ($religion=='svd')?'selected':''; ?>>Seven Day Adventist</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Current Address -->
                    <div class="form-information">
                        <h3 class="section-title">Current Address</h3>
                    
                        <div class="form-row four-cols">
                            <div class="form-group">
                                <label for="streetno">Street # / Unit #:. <span class="required">*</span></label>
                                <input type="text" id="streetno" name="streetno" required value="<?php echo htmlspecialchars($streetno); ?>">
                            </div>
                            <div class="form-group">
                                <label for="street">Street <span class="required">*</span></label>
                                <input type="text" id="street" name="street" required value="<?php echo htmlspecialchars($street); ?>">
                            </div>
                            <div class="form-group">
                                <label for="subd">Subdivision / Village / Bldg.: </label>
                                <input type="text" id="subd" name="subd" value="<?php echo htmlspecialchars($subd); ?>">
                            </div>
                            <div class="form-group">
                                <label for="brgy">Barangay:</label>
                                <input type="text" id="brgy" name="brgy" value="<?php echo htmlspecialchars($brgy); ?>">
                            </div>
                        </div>
                        <div class="form-row three-cols">
                            <div class="form-group">
                                <label for="city">City / Municipality: <span class="required">*</span></label>
                                <input type="text" id="city" name="city" required value="<?php echo htmlspecialchars($city); ?>">
                            </div>
                            <div class="form-group">
                                <label for="province">Province: </label>
                                <input type="text" id="province" name="province" value="<?php echo htmlspecialchars($province); ?>">  
                            </div>
                            <div class="form-group">
                                <label for="zipcode">Zip Code: </label>
                                <input type="text" id="zipcode" name="zipcode" value="<?php echo htmlspecialchars($zipcode); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Permanent Address -->
                    <div class="form-information">
                        <form onsubmit="return validateParentsForm();">
                        <h3 class="section-title">Permanent Address</h3>
                        <label>
                            <input type="checkbox" id="sameAddress"> Same as Current Address
                        </label>

                        <div class="form-row four-cols">
                            <div class="form-group">
                                <label for="p_streetno">Street # / Unit #.: <span class="required">*</span></label>
                                <input type="text" id="p_streetno" name="p_streetno">
                            </div>
                            <div class="form-group">
                                <label for="p_street">Street: <span class="required">*</span></label>
                                <input type="text" id="p_street" name="p_street">
                            </div>
                            <div class="form-group">
                                <label for="p_subd">Subdivision / Village / Bldg.:</label>
                                <input type="text" id="p_subd" name="p_subd">
                            </div>
                            <div class="form-group">
                                <label for="p_brgy">Barangay:</label>
                                <input type="text" id="p_brgy" name="p_brgy">
                            </div>
                        </div>
                        <div class="form-row three-cols">
                            <div class="form-group">
                                <label for="p_city">City / Municipality: <span class="required">*</span></label>
                                <input type="text" id="p_city" name="p_city">
                            </div>
                            <div class="form-group">
                                <label for="p_province">Province:</label>
                                <input type="text" id="p_province" name="p_province">
                            </div>
                            <div class="form-group">
                                <label for="p_zipcode">Zip Code:</label>
                                <input type="text" id="p_zipcode" int="p_zipcode">
                            </div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div class="form-information">
                        <h3 class="section-title">Contact Details</h3>

                        <div class="form-row three-cols">
                            <div class="form-group">
                                <label for="telnumber">Telephone No.</label>
                                <input type="number" id="telnumber" name="telnumber" value="<?php echo htmlspecialchars($telnumber); ?>">
                            </div>
                            <div class="form-group">
                                <label for="mobnumber">Mobile Number <span class="required">*</span></label>
                                <input type="number" id="mobnumber" name="mobnumber" required value="<?php echo htmlspecialchars($mobnumber); ?>"
                                placeholder="09XXXXXXXXX">
                            </div>
                            <div class="form-group">
                                <label for="emailaddress">Email Address <span class="required">*</span></label>
                                <input type="email" id="emailaddress" name="emailaddress" required value="<?php echo htmlspecialchars($emailaddress); ?>"
                                placeholder="example@domain.com">
                            </div>
                        </div>
                    </div>

                    <!-- Parents Information -->
                    <div class="form-information">
                        <h3 class="section-title">Parents / Guardian's Information</h3>

                        <!-- Father's Information -->
                        <label class="text">Father's Name</label>
                        <div class="form-row four-cols">
                            <div class="form-group">
                                <label for="father_lastname">Last Name <span class="required">*</span></label>
                                <input type="text" id="father_lastname" name="father_lastname" value="<?php echo htmlspecialchars($father_lastname ?? ''); ?>" 
                                    required placeholder="Surname">
                            </div>
                            <div class="form-group">
                                <label for="father_firstname">First Name <span class="required">*</span></label>
                                <input type="text" id="father_firstname" name="father_firstname" value="<?php echo htmlspecialchars($father_firstname ?? ''); ?>" 
                                    required placeholder="Given Name">
                            </div>
                            <div class="form-group">
                                <label for="father_middlename">Middle Initial</label>
                                <input type="text" id="father_middlename" name="father_middlename" value="<?php echo htmlspecialchars($father_middlename ?? ''); ?>" 
                                    placeholder="Middle Initial">
                            </div>
                            <div class="form-group">
                                <label for="father_suffixname">Suffix Name</label>
                                <input type="text" id="father_suffixname" name="father_suffixname" value="<?php echo htmlspecialchars($father_suffixname ?? ''); ?>" 
                                    placeholder="Suffix">
                            </div>
                        </div>
                        <div class="form-row three-cols">
                            <div class="form-group">
                                <label for="father_mobnumber">Mobile Number <span class="required">*</span></label>
                                <input type="text" id="father_mobnumber" name="father_mobnumber" value="<?php echo htmlspecialchars($father_mobnumber ?? ''); ?>" 
                                    required placeholder="09XXXXXXXXX">
                            </div>
                            <div class="form-group">
                                <label for="father_emailaddress">Email <span class="required">*</span></label>
                                <input type="email" id="father_emailaddress" name="father_emailaddress" value="<?php echo htmlspecialchars($father_emailaddress ?? ''); ?>" 
                                    required placeholder="Email Address">
                            </div>
                            <div class="form-group">
                                <label for="father_occupation">Occupation</label>
                                <input type="text" id="father_occupation" name="father_occupation" value="<?php echo htmlspecialchars($father_occupation ?? ''); ?>" 
                                    placeholder="Occupation">
                            </div>
                        </div>

                        <!-- Mother's Information -->
                        <label class="text">Mother's Name</label>
                        <div class="form-row four-cols">
                            <div class="form-group">
                                <label for="mother_lastname">Last Name <span class="required">*</span></label>
                                <input type="text" id="mother_lastname" name="mother_lastname" value="<?php echo htmlspecialchars($mother_lastname ?? ''); ?>" 
                                    required placeholder="Surname">
                            </div>
                            <div class="form-group">
                                <label for="mother_firstname">First Name <span class="required">*</span></label>
                                <input type="text" id="mother_firstname" name="mother_firstname" value="<?php echo htmlspecialchars($mother_firstname ?? ''); ?>" 
                                    required placeholder="Given Name">
                            </div>
                            <div class="form-group">
                                <label for="mother_middlename">Middle Initial</label>
                                <input type="text" id="mother_middlename" name="mother_middlename" value="<?php echo htmlspecialchars($mother_middlename ?? ''); ?>" 
                                    placeholder="Middle Initial">
                            </div>
                            <div class="form-group">
                                <label for="mother_suffixname">Suffix Name</label>
                                <input type="text" id="mother_suffixname" name="mother_suffixname" value="<?php echo htmlspecialchars($mother_suffixname ?? ''); ?>" 
                                    placeholder="Suffix">
                            </div>
                        </div>
                        <div class="form-row three-cols">
                            <div class="form-group">
                                <label for="mother_mobnumber">Mobile Number <span class="required">*</span></label>
                                <input type="text" id="mother_mobnumber" name="mother_mobnumber" value="<?php echo htmlspecialchars($mother_mobnumber ?? ''); ?>" 
                                    required placeholder="09XXXXXXXXX">
                            </div>
                            <div class="form-group">
                                <label for="mother_emailaddress">Email <span class="required">*</span></label>
                                <input type="email" id="mother_emailaddress" name="mother_emailaddress" value="<?php echo htmlspecialchars($mother_emailaddress ?? ''); ?>" 
                                    required placeholder="Email Address">
                            </div>
                            <div class="form-group">
                                <label for="mother_occupation">Occupation</label>
                                <input type="text" id="mother_occupation" name="mother_occupation" value="<?php echo htmlspecialchars($mother_occupation ?? ''); ?>" 
                                    placeholder="Occupation">
                            </div>
                        </div>

                        <!-- Guardian's Information -->
                        <label class="text">Guardian's Name</label>
                        <div class="form-row four-cols">
                            <div class="form-group">
                                <label for="guardian_lastname">Last Name </label>
                                <input type="text" id="guardian_lastname" name="guardian_lastname" value="<?php echo htmlspecialchars($guardian_lastname ?? ''); ?>" 
                                    required placeholder="Surname">
                            </div>
                            <div class="form-group">
                                <label for="guardian_firstname">First Name </label>
                                <input type="text" id="guardian_firstname" name="guardian_firstname" value="<?php echo htmlspecialchars($guardian_firstname ?? ''); ?>" 
                                    required placeholder="Given Name">
                            </div>
                            <div class="form-group">
                                <label for="guardian_middlename">Middle Initial</label>
                                <input type="text" id="guardian_middlename" name="guardian_middlename" value="<?php echo htmlspecialchars($guardian_middlename ?? ''); ?>" 
                                    placeholder="Middle Initial">
                            </div>
                            <div class="form-group">
                                <label for="guardian_suffixname">Suffix Name</label>
                                <input type="text" id="guardian_suffixname" name="guardian_suffixname" value="<?php echo htmlspecialchars($guardian_suffixname ?? ''); ?>" 
                                    placeholder="Suffix">
                            </div>
                        </div>
                        <div class="form-row four-cols">
                            <div class="form-group">
                                <label for="guardian_mobnumber">Mobile Number </label>
                                <input type="text" id="guardian_mobnumber" name="guardian_mobnumber" value="<?php echo htmlspecialchars($guardian_mobnumber ?? ''); ?>" 
                                    required placeholder="09XXXXXXXXX">
                            </div>
                            <div class="form-group">
                                <label for="guardian_emailaddress">Email </label>
                                <input type="email" id="guardian_emailaddress" name="guardian_emailaddress"  value="<?php echo htmlspecialchars($guardian_emailaddress ?? ''); ?>" 
                                    required placeholder="Email Address">
                            </div>
                            <div class="form-group">
                                <label for="guardian_occupation">Occupation</label>
                                <input type="text" id="guardian_occupation" name="guardian_occupation" value="<?php echo htmlspecialchars($guardian_occupation ?? ''); ?>" 
                                    placeholder="Occupation">
                            </div>
                            <div class="form-group">
                                <label for="guardian_relationship">Relationship</label>
                                <input type="text" id="guardian_relationship" name="guardian_relationship" value="<?php echo htmlspecialchars($guardian_relationship ?? ''); ?>" 
                                    placeholder="Relationship">
                            </div>
                        </div>
                    </div>
                </fieldset> 
                <button type="submit" class="btn">Proceed to Next Step</button>
            </form>
        </div>
    </div>
</main>    
</body>
</head>
    <script src="assets/js/validate-parents.js"></script>    
    <script>
        window.onload = function() {
            const yearLevelGroup = document.getElementById("yearLevelGroup");
            const lrnField = document.getElementById("lrn");
        };

        document.addEventListener("DOMContentLoaded", function() {
            const yearLevel = document.getElementById("yearlevel");
            const courseGroup = document.getElementById("courseGroup");
            const courseSelect = document.getElementById("course");

            function toggleCourseField() {
                if (yearLevel.value === "Grade 11" || yearLevel.value === "Grade 12") {
                    courseGroup.style.display = "block";
                    courseSelect.required = true;
                } else {
                    courseGroup.style.display = "none";
                    courseSelect.required = false;
                    courseSelect.value = ""; // clear if hidden
                }
            }

            yearLevel.addEventListener("change", toggleCourseField);
            toggleCourseField(); // run once on load
        });

        document.getElementById("sameAddress").addEventListener("change", function() {
            if (this.checked) {
                document.getElementById("p_streetno").value = document.getElementById("streetno").value;
                document.getElementById("p_street").value   = document.getElementById("street").value;
                document.getElementById("p_subd").value     = document.getElementById("subd").value;
                document.getElementById("p_brgy").value     = document.getElementById("brgy").value;
                document.getElementById("p_city").value     = document.getElementById("city").value;
                document.getElementById("p_province").value = document.getElementById("province").value;
                document.getElementById("p_zipcode").value  = document.getElementById("zipcode").value;
            } else {
                // Clear permanent address if unchecked
                document.getElementById("p_streetno").value = "";
                document.getElementById("p_street").value   = "";
                document.getElementById("p_subd").value     = "";
                document.getElementById("p_brgy").value     = "";
                document.getElementById("p_city").value     = "";
                document.getElementById("p_province").value = "";
                document.getElementById("p_zipcode").value  = "";
            }
        });
    </script>
</html>

<?php
   include '../includes/footer.php';
?>