<?php
session_start();
include 'db_connection.php'; // Adjust this to your actual connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Only fetch registrar users
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'registrar'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $registrar = $result->fetch_assoc();

    if ($registrar && md5($password) == $registrar['password']) { // use password_verify() if hashed differently
        $_SESSION['registrar_username'] = $registrar['username'];
        header("Location: registrar_dashboard.php");
        exit;
    } else {
        $error = "Invalid registrar credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registrar Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .container {
            width: 60%;
            margin: auto;
            padding: 20px;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .btn-submit {
            background-color: #008000;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        .btn-submit:hover {
            background-color: #006400;
        }
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Registrar Login</h1>
    <form action="registrar_login.php" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus />
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required />
        </div>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        <button type="submit" class="btn-submit">Login</button>
    </form>
</div>

</body>
</html>
