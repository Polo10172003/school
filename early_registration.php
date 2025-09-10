<?php 
session_start();

// Capture LRN if passed from lrn.php
if (isset($_GET['lrn'])) {
    $_SESSION['registration']['lrn'] = $_GET['lrn'];
}

// Prefill variables from session
$registration = $_SESSION['registration'] ?? [];
$lrn = $registration['lrn'] ?? '';
$firstname = $registration['firstname'] ?? '';
$lastname = $registration['lastname'] ?? '';
$middlename = $registration['middlename'] ?? '';
$yearlevel = $registration['yearlevel'] ?? '';
$sex = $registration['sex'] ?? '';
$dob = $registration['dob'] ?? '';
$religion = $registration['religion'] ?? '';
$emailaddress = $registration['emailaddress'] ?? '';
$contactno = $registration['contactno'] ?? '';
$specaddress = $registration['specaddress'] ?? '';
$brgy = $registration['brgy'] ?? '';
$city = $registration['city'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save submitted data to session
    $_SESSION['registration'] = array_merge($_SESSION['registration'] ?? [], $_POST);

    // Redirect to next step
    header('Location: early_registrationparent.php');
    exit();
}
?>

<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Early Registration Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .top-bar {
            background: #006400;
            color: white;
            text-align: center;
            padding: 10px 0;
            font-size: 14px;
        }
        .menu {
            background: #008000;
            padding: 10px 0;
            text-align: center;
        }
        .menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .menu ul li {
            display: inline;
            margin: 0 15px;
        }
        .menu ul li a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            transition: 0.3s;
        }
        .menu ul li a:hover {
            background: #004d00;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .container {
            width: 60%;
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
        .btn-next {
            background: #008000;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            display: block;
            margin-top: 20px;
            border: none;
        }
        .btn-next:hover {
            background: #004d00;
        }
        /* Hidden by default */
        #yearLevelGroup {
            display: none;
        }
    </style>
</head>
<body>

<div class="top-bar">
    ESCUELA DE STO. ROSARIO - Official Website
</div>

<div class="menu">
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About Us</a></li>
        <li><a href="portal.php">Portal</a></li>
    </ul>
</div>

<div class="container">
    <h1>Early Registration Form</h1>
    <form action="early_registration.php" method="POST" enctype="multipart/form-data">

        <!-- Personal Information Section -->
        <fieldset>
            <legend>Personal Information</legend>
            <hr>
            <div class="form-group">
                <label for="lrn">LRN</label>
                <input type="text" 
                       id="lrn" 
                       name="lrn" 
                       value="<?php echo htmlspecialchars($lrn); ?>" 
                       <?php echo !empty($lrn) ? 'readonly' : 'required'; ?>
                       oninput="checkLRN()">
            </div>

            <!-- Year Level Dropdown (shows only if new LRN) -->
            <div class="form-group" id="yearLevelGroup">
                <label for="yearlevel">Year Level</label>
                <select id="yearlevel" name="yearlevel">
                    <option value="">Select Year Level</option>
                    <option value="Kinder 1" <?php echo ($yearlevel=='Kinder 1')?'selected':''; ?>>Kinder 1</option>
                    <option value="Kinder 2" <?php echo ($yearlevel=='Kinder 2')?'selected':''; ?>>Kinder 2</option>
                    <option value="Grade 1" <?php echo ($yearlevel=='Grade 1')?'selected':''; ?>>Grade 1</option>
                    <option value="Grade 2" <?php echo ($yearlevel=='Grade 2')?'selected':''; ?>>Grade 2</option>
                    <option value="Grade 3" <?php echo ($yearlevel=='Grade 3')?'selected':''; ?>>Grade 3</option>
                    <option value="Grade 4" <?php echo ($yearlevel=='Grade 4')?'selected':''; ?>>Grade 4</option>
                    <option value="Grade 5" <?php echo ($yearlevel=='Grade 5')?'selected':''; ?>>Grade 5</option>
                    <option value="Grade 6" <?php echo ($yearlevel=='Grade 6')?'selected':''; ?>>Grade 6</option>
                    <option value="Grade 7" <?php echo ($yearlevel=='Grade 7')?'selected':''; ?>>Grade 7</option>
                    <option value="Grade 8" <?php echo ($yearlevel=='Grade 8')?'selected':''; ?>>Grade 8</option>
                    <option value="Grade 9" <?php echo ($yearlevel=='Grade 9')?'selected':''; ?>>Grade 9</option>
                    <option value="Grade 10" <?php echo ($yearlevel=='Grade 10')?'selected':''; ?>>Grade 10</option>
                    <option value="Grade 11" <?php echo ($yearlevel=='Grade 11')?'selected':''; ?>>Grade 11</option>
                    <option value="Grade 12" <?php echo ($yearlevel=='Grade 12')?'selected':''; ?>>Grade 12</option>
                </select>
            </div>

            <div class="form-group">
                <label for="lastname">Last Name</label>
                <input type="text" id="lastname" name="lastname" required value="<?php echo htmlspecialchars($lastname); ?>">
            </div>
            <div class="form-group">
                <label for="firstname">First Name</label>
                <input type="text" id="firstname" name="firstname" required value="<?php echo htmlspecialchars($firstname); ?>">
            </div>
            <div class="form-group">
                <label for="middlename">Middle Name</label>
                <input type="text" id="middlename" name="middlename" required value="<?php echo htmlspecialchars($middlename); ?>">
            </div>

            <div class="form-group">
                <label for="specaddress">House No./Street/Compound/Village/Phase</label>
                <input type="text" id="specaddress" name="specaddress" required value="<?php echo htmlspecialchars($specaddress); ?>">
            </div>
            <div class="form-group">
                <label for="brgy">Barangay</label>
                <input type="text" id="brgy" name="brgy" required value="<?php echo htmlspecialchars($brgy); ?>">
            </div>
            <div class="form-group">
                <label for="city">City/Municipality</label>
                <input type="text" id="city" name="city" required value="<?php echo htmlspecialchars($city); ?>">
            </div>

            <div class="form-group">
                <label for="sex">Sex</label>
                <select id="sex" name="sex">
                    <option value="">Select Sex</option>
                    <option value="Female" <?php echo ($sex=='Female')?'selected':''; ?>>Female</option>
                    <option value="Male" <?php echo ($sex=='Male')?'selected':''; ?>>Male</option>
                </select>
            </div>
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" required value="<?php echo htmlspecialchars($dob); ?>">
            </div>

            <div class="form-group">
                <label for="religion">Religion</label>
                <select id="religion" name="religion">
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

            <div class="form-group">
                <label for="emailaddress">Email Address</label>
                <input type="email" id="emailaddress" name="emailaddress" required value="<?php echo htmlspecialchars($emailaddress); ?>">
            </div>
            <div class="form-group">
                <label for="contactno">Contact No.</label>
                <input type="text" id="contactno" name="contactno" required value="<?php echo htmlspecialchars($contactno); ?>">
            </div>
        </fieldset>

        <button type="submit" class="btn-next">Next</button>

    </form>
</div>

<script>
window.onload = function() {
    const yearLevelGroup = document.getElementById("yearLevelGroup");
    const lrnField = document.getElementById("lrn");

    // If LRN is already set (from session/URL), show year level dropdown
    if (lrnField.hasAttribute("readonly")) {
        yearLevelGroup.style.display = "block";
    }
};

function checkLRN() {
    const lrn = document.getElementById("lrn").value.trim();
    const yearLevelGroup = document.getElementById("yearLevelGroup");

    // If user manually types "new" or blank, show dropdown
    if (lrn === "" || lrn.toLowerCase() === "new") {
        yearLevelGroup.style.display = "block";
    } else {
        yearLevelGroup.style.display = "none";
    }
}
window.onload = function() {
    const yearLevelGroup = document.getElementById("yearLevelGroup");
    const lrnField = document.getElementById("lrn");

    // If it's a NEW student (not readonly), show year level dropdown
    if (!lrnField.hasAttribute("readonly")) {
        yearLevelGroup.style.display = "block";
    }
};

</script>

</body>
</html>
