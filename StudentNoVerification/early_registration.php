<?php 

session_start();


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
    $_SESSION['registration'] = array_merge($_SESSION['registration'] ?? [], $_POST);
    header("Location: early_registrationparent.php");
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
    <style>
        .registration-container {
            width: 60%;
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
       
    </style>
<body>
<main>
<div class="container" style="padding-top:90px;">
    <h1>Early Registration Form</h1>
    <form action="" method="POST" enctype="multipart/form-data">

        <!-- Personal Information Section -->
        <fieldset>
            <legend>Personal Information</legend>
            <hr>
            <div class="form-group">
                <label for="lrn">LRN</label>
                <input type="text" id="lrn" name="lrn">
            </div>

            <!-- Year Level Dropdown (shows only if new LRN) -->
            <div class="form-group" id="yearLevelGroup">
                <label for="yearlevel">Year Level</label>
                <select id="yearlevel" name="yearlevel"required>
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

        <button type="submit" class="btn">Next</button>

    </form>
</div>
</main>
</body> 
</head>
<script>
window.onload = function() {
    const yearLevelGroup = document.getElementById("yearLevelGroup");
    const lrnField = document.getElementById("lrn");

   
};


</script>
</html>
<?php 
    include '../includes/footer.php';
?>
