<?php 
session_start();


// Prefill variables from session if available
$mother = $_SESSION['registration']['mother'] ?? '';
$father = $_SESSION['registration']['father'] ?? '';
$gname = $_SESSION['registration']['gname'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['registration'] = array_merge($_SESSION['registration'] ?? [], $_POST);
    header('Location: early_registrationschool.php');
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
            <div class="registration-container" style="padding-top:90px;">
                <h1>Parent's Information</h1>
                <form action="" method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label for="mother">Mother's Name</label>
                        <input type="text" id="mother" name="mother" required value="<?php echo htmlspecialchars($mother); ?>">
                    </div>
                    <div class="form-group">
                        <label for="father">Father's Name</label>
                        <input type="text" id="father" name="father" required value="<?php echo htmlspecialchars($father); ?>">
                    </div>
                    <div class="form-group">
                        <label for="gname">Guardian's Name (Optional)</label>
                        <input type="text" id="gname" name="gname" placeholder="Enter Guardian's Name (Optional)" value="<?php echo htmlspecialchars($gname); ?>">
                    </div>
                    <button type="submit" class="btn">Next</button>
                </form>
            </div>
        </main>
    </body>
</html>

<?php 
    include '../includes/footer.php';
?>

