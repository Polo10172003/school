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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['registration'] = array_merge($_SESSION['registration'] ?? [], $_POST);

    header("Location: early_registrationparent.php");
    exit();
}

if (isset($_POST['sameAddress'])) {
    $_POST['p_streetno'] = $_POST['streetno'];
    $_POST['p_street']   = $_POST['street'];
    $_POST['p_subd']     = $_POST['subd'];
    $_POST['p_brgy']     = $_POST['brgy'];
    $_POST['p_city']     = $_POST['city'];
    $_POST['p_province'] = $_POST['province'];
    $_POST['p_zipcode']  = $_POST['zipcode'];
}

include 'includes/header.php';
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Early Registration Form</title>
    <style>
        .main-content {
            background: #f4f4f4;
            padding-top: 30px;
            padding-bottom: 30px;
        }

        .form-container {
            width: 70%;
            margin: auto;
            padding: 20px;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }   
        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 10px;
        }
        .form-group label {
            font-weight: bold;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .btn {
            background:  #145A32;
            color: white;
            padding: 6px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            display: block;
            margin-top: 20px;
            border: none;
        }
        .btn:hover {
            background-color: #0f723aff;
            color: #ffffff !important;
            transform: translateY(-1px);
        }
        .required {
            color: red;
        }
    </style>
</head>
<body>



    <!-- Main Content -->
    <div class="main-content">
        <div class="form-container mt-5" >
            <div class="form-header">
                <h2>Early Registration Form</h2>
                <p>We extend our welcome to primary and secondary students, junior high school completers, and prospective senior high school students who wish to join our academic community.</p>
                <p>To ensure an efficient admissions process, applicants are advised to complete the early registration form.</p>
            </div>
            <div class="form-content">
                <form action="early_registration.php" method="POST">

                    <!-- Admission Information -->
                    <div class="form-admission">
                        <h3 class="section-title">Admission Information</h3>
                            <div class="form-row">
                                <!-- LRN -->
                                <div class="form-group">
                                <label for="lrn">LRN <span class="required">*</span></label>
                                <input type="text" id="lrn" name="lrn" value="<?php echo htmlspecialchars($lrn); ?>" placeholder="Enter LRN">
                                </div>

                                <!-- Year Level -->
                                <div class="form-group">
                                    <label for="yearlevel">Year Level <span class="required">*</span></label>
                                    <select id="yearlevel" name="yearlevel" required>
                                        <option value="">Please select year level</option>
                                        <?php
                                        $yearLevels = [
                                        "Kinder 1", "Kinder 2", "Grade 1", "Grade 2", "Grade 3", "Grade 4",
                                        "Grade 5", "Grade 6", "Grade 7", "Grade 8", "Grade 9", "Grade 10",
                                        "Grade 11", "Grade 12"
                                        ];
                                        foreach ($yearLevels as $level) {
                                        $selected = ($yearlevel === $level) ? 'selected' : '';
                                        echo "<option value=\"$level\" $selected>$level</option>";
                                        }
                                        ?>
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
                                <label for="suffixname">Suffix Name <span class="required">*</span></label>
                                <input type="text" id="suffixname" name="suffixname" required value="<?php echo htmlspecialchars($suffixname); ?>"
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
                    <div class="form-currentaddress">
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
                                <label for="subd">Subdivision / Village / Bldg.: <span class="required">*</span></label>
                                <input type="text" id="subd" name="subd" required value="<?php echo htmlspecialchars($subd); ?>">
                            </div>
                            <div class="form-group">
                                <label for="brgy">Barangay: <span class="required">*</span></label>
                                <input type="text" id="brgy" name="brgy" required value="<?php echo htmlspecialchars($brgy); ?>">
                            </div>
                        </div>
                        <div class="form-row three-cols">
                            <div class="form-group">
                                <label for="city">City / Municipality: <span class="required">*</span></label>
                                <input type="text" id="city" name="city" required value="<?php echo htmlspecialchars($city); ?>">
                            </div>
                            <div class="form-group">
                                <label for="province">Province: <span class="required">*</span></label>
                                <input type="text" id="province" name="province" required value="<?php echo htmlspecialchars($province); ?>">  
                            </div>
                            <div class="form-group">
                                <label for="zipcode">Zip Code: <span class="required">*</span></label>
                                <input type="text" id="zipcode" name="zipcode" required value="<?php echo htmlspecialchars($zipcode); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Permanent Address -->
                    <div class="form-permanentaddress">
                        <h3 class="section-title">Permanent Address</h3>
                        <label>
                            <input type="checkbox" id="sameAddress"> Same as Current Address
                        </label>

                        <div class="form-row four-cols">
                            <div class="form-group">
                                <label for="p_streetno">Street # / Unit #:</label>
                                <input type="text" id="p_streetno" name="p_streetno">
                            </div>
                            <div class="form-group">
                                <label for="p_street">Street:</label>
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
                                <label for="p_city">City / Municipality:</label>
                                <input type="text" id="p_city" name="p_city">
                            </div>
                            <div class="form-group">
                                <label for="p_province">Province:</label>
                                <input type="text" id="p_province" name="p_province">
                            </div>
                            <div class="form-group">
                                <label for="p_zipcode">Zip Code:</label>
                                <input type="text" id="p_zipcode" name="p_zipcode">
                            </div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                     <div class="form-contactdetails">
                        <h3 class="section-title">Contact Details</h3>

                        <div class="form-row three-cols">
                            <div class="form-group">
                                <label for="telnumber">Telephone No.</label>
                                <input type="email" id="telnumber" name="telnumber" required value="<?php echo htmlspecialchars($telnumber); ?>">
                            </div>
                            <div class="form-group">
                                <label for="mobnumber">Mobile Number <span class="required">*</span></label>
                                <input type="email" id="mobnumber" name="mobnumber" required value="<?php echo htmlspecialchars($mobnumber); ?>"
                                placeholder="09XXXXXXXXX">
                            </div>
                            <div class="form-group">
                                <label for="emailaddress">Email Address <span class="required">*</span></label>
                                <input type="email" id="emailaddress" name="emailaddress" required value="<?php echo htmlspecialchars($emailaddress); ?>"
                                placeholder="example@domain.com">
                            </div>
                     </div>

                    <!-- Parents Information -->
                    <div class="form-section">
                        <h3 class="section-title">Parents/Guardian Information</h3>
                        
                        <div class="form-row three-cols">
                            <div class="form-group">
                                <label for="father">Father's Name <span class="required">*</span></label>
                                <input type="text" id="father" name="father" required value="<?php echo htmlspecialchars($father); ?>">
                            </div>
                            <div class="form-group">
                                <label for="mother">Mother's Name <span class="required">*</span></label>
                                <input type="text" id="mother" name="mother" required value="<?php echo htmlspecialchars($mother); ?>">
                            </div>
                            <div class="form-group">
                                <label for="gname">Guardian's Name</label>
                                <input type="text" id="gname" name="gname" placeholder="Optional" value="<?php echo htmlspecialchars($gname); ?>">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn">Proceed to Next Step</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            const yearLevelGroup = document.getElementById("yearLevelGroup");
            const lrnField = document.getElementById("lrn");

            if (lrnField.hasAttribute("readonly")) {
                yearLevelGroup.style.display = "block";
            }
        };

        function checkLRN() {
            const lrn = document.getElementById("lrn").value.trim();
            const yearLevelGroup = document.getElementById("yearLevelGroup");

            if (lrn === "" || lrn.toLowerCase() === "new") {
                yearLevelGroup.style.display = "block";
            } else {
                yearLevelGroup.style.display = "none";
            }
        }

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
</body>
</html>

<?php
   include 'includes/footer.php';
?>