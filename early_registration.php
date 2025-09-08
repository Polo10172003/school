<?php 
session_start();

// Capture LRN if passed from lrn.php
if (isset($_GET['lrn'])) {
    $_SESSION['registration']['lrn'] = $_GET['lrn'];
}

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
            id="lrn" name="lrn" 
           value="<?php echo isset($_SESSION['registration']['lrn']) ? htmlspecialchars($_SESSION['registration']['lrn']) : ''; ?>" 
           <?php echo isset($_SESSION['registration']['lrn']) ? 'readonly' : 'required'; ?>>
</div>


            <!-- Year Level Dropdown (shows only if new LRN) -->
            <div class="form-group" id="yearLevelGroup">
                <label for="yearlevel">Year Level</label>
                <select id="yearlevel" name="yearlevel">
                    <option value="">Select Year Level</option>
                    <option value="Kinder 1">Kinder 1</option>
                    <option value="Kinder 2">Kinder 2</option>
                    <option value="Grade 1">Grade 1</option>
                    <option value="Grade 2">Grade 2</option>
                    <option value="Grade 3">Grade 3</option>
                    <option value="Grade 4">Grade 4</option>
                    <option value="Grade 5">Grade 5</option>
                    <option value="Grade 6">Grade 6</option>
                    <option value="Grade 7">Grade 7</option>
                    <option value="Grade 8">Grade 8</option>
                    <option value="Grade 9">Grade 9</option>
                    <option value="Grade 10">Grade 10</option>
                    <option value="Grade 11">Grade 11</option>
                    <option value="Grade 12">Grade 12</option>
                </select>
            </div>

            <div class="form-group">
                <label for="lastname">Last Name</label>
                <input type="text" id="lastname" name="lastname" required>
            </div>
            <div class="form-group">
                <label for="firstname">First Name</label>
                <input type="text" id="firstname" name="firstname" required>
            </div>
            <div class="form-group">
                <label for="middlename">Middle Name</label>
                <input type="text" id="middlename" name="middlename" required>
            </div>

            <div class="form-group">
                <label for="specaddress">House No./Street/Compound/Village/Phase</label>
                <input type="text" id="specaddress" name="specaddress" required>
            </div>
            <div class="form-group">
                <label for="brgy">Barangay</label>
                <input type="text" id="brgy" name="brgy" required>
            </div>
            <div class="form-group">
                <label for="city">City/Municipality</label>
                <input type="text" id="city" name="city" required>
            </div>

            <div class="form-group">
                <label for="sex">Sex</label>
                <select id="sex" name="sex">
                    <option value="">Select Sex</option>
                    <option value="Female">Female</option>
                    <option value="Male">Male</option>
                </select>
            </div>
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" required>
            </div>

            <div class="form-group">
                <label for="religion">Religion</label>
                <select id="religion" name="religion">
                    <option value="">Select Religion</option>
                    <option value="romCat">Roman Catholic</option>
                    <option value="aglipay">Aglipay</option>
                    <option value="baptist">Baptist</option>
                    <option value="bornagain">Born Again Christian</option>
                    <option value="buddhism">Buddhism</option>
                    <option value="christianfellowship">Christian Fellowship</option>
                    <option value="coc">Church of Christ</option>
                    <option value="datingdaan">Dating Daan</option>
                    <option value="Iglesia">Iglesia</option>
                    <option value="islammuslim">Islam (Muslim)</option>
                    <option value="jw">Jehovah's Witness</option>
                    <option value="mormons">Mormons</option>
                    <option value="svd">Seven Day Adventist</option>
                </select>
            </div>

            <div class="form-group">
                <label for="emailaddress">Email Address</label>
                <input type="email" id="emailaddress" name="emailaddress" required>
            </div>
            <div class="form-group">
                <label for="contactno">Contact No.</label>
                <input type="text" id="contactno" name="contactno" required>
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
</script>

</body>
</html>
