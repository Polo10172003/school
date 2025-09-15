<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['registration'] = array_merge($_SESSION['registration'] ?? [], $_POST);

    // Redirect to final submission page after saving all data
    header('Location: StudentNoVerification/submit_registration.php');
    exit();
}

$data = $_SESSION['registration'] ?? [];

$schooltype = $data['schooltype'] ?? '';
$sname = $data['sname'] ?? '';
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
    </style>
</head>
<body>
<main>
<div class="registration-container" style="padding-top:100px;">
    <h1>Educational Attainment</h1>
    <form action="" method="POST" enctype="multipart/form-data">
        <fieldset id="educationalAttainmentFieldset">
            <div class="form-group">
                <label for="schooltype">Type of School</label>
                <select id="schooltype" name="schooltype" required>
                    <option value="">Select Type</option>
                    <option value="public" <?= $schooltype === 'public' ? 'selected' : '' ?>>Public</option>
                    <option value="private" <?= $schooltype === 'private' ? 'selected' : '' ?>>Private</option>
                </select>
            </div>
            <div class="form-group">
                <label for="sname">School Name</label>
                <input type="text" id="sname" name="sname" value="<?= htmlspecialchars($sname) ?>" required>
            </div>
        </fieldset>

        <button type="submit" class="btn">Submit</button>

    </form>
</div>
</main>
</body>
</html>
<?php include '../includes/footer.php'; 
?>