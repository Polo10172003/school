<<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['registration'] = array_merge($_SESSION['registration'] ?? [], $_POST);

    // Redirect to final submission page after saving all data
    header('Location: submit_registration.php');
    exit();
}

$data = $_SESSION['registration'] ?? [];

$schooltype = $data['schooltype'] ?? '';
$sname = $data['sname'] ?? '';
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
        .btn-submit {
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
        }
        .btn-submit:hover {
            background: #004d00;
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
    <form action="early_registrationschool.php" method="POST" enctype="multipart/form-data">

       

            <fieldset id="educationalAttainmentFieldset">
                <legend>Educational Attainment</legend>
                <div class="form-group">
                    <label for="schooltype">Type of School</label>
                    <select id="schooltype" name="schooltype">
                        <option value="">Select Type</option>
                    <option value="public">Public</option>
                    <option value="private">Private</option>
                </select>
            </div>
            <div class="form-group">
                <label for="sname">School Name</label>
                <input type="text" id="sname" name="sname" required>
            </div>
        </fieldset>

        <button type="submit" class="btn-submit">Submit</button>

    </form>
</div>



</body>
</html>
