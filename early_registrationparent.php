<?php 
session_start();

// Prefill variables from session if available
$mother = $_SESSION['registration']['mother'] ?? '';
$father = $_SESSION['registration']['father'] ?? '';
$gname = $_SESSION['registration']['gname'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save submitted data into session (merge with existing)
    $_SESSION['registration'] = array_merge($_SESSION['registration'] ?? [], $_POST);

    // Redirect to next step
    header('Location: early_registrationschool.php');
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
        }
        .btn-next:hover {
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
    <h1>Parent's Information</h1>
    <form action="early_registrationparent.php" method="POST" enctype="multipart/form-data">

       
        
            <div class="form-group">
                <label for="mother">Mother's Name</label>
                <input type="text" id="mother" name="mother" required>
            </div>
            <div class="form-group">
                <label for="father">Father's Name</label>
                <input type="text" id="father" name="father" required>
            </div>
            <div class="form-group">
                <label for="gname">Guardian's Name (Optional)</label>
                <input type="text" id="gname" name="gname" placeholder="Enter Guardian's Name (Optional)">
            </div>



        <button type="submit" class="btn-next">Next</button>

    </form>
</div>



</body>
</html>
