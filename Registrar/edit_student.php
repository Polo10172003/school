<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize input

    // Fetch the data
    $query = "SELECT * FROM students_registration WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $student = mysqli_fetch_assoc($result);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize POST data
    $id = intval($_POST['id']);
    $student_no = $_POST['Lrn']; 
    $last_name = $_POST['lastname'];  
    $first_name = $_POST['firstname'];  
    $middle_name = $_POST['middlename'];    
    $year = $_POST['year'];  
    $course = $_POST['course'];  
    $sex = $_POST['sex'];  
    $birth_date = $_POST['dob'];  
    $religion = $_POST['religion'];  
    $address = $_POST['specaddress'];  
    $school_type = $_POST['schooltype'];  
    $last_school = $_POST['sname'];  
    $father_name = $_POST['father'];  
    $mother_name = $_POST['mother'];  
    $guardian_name = $_POST['gname'];  
    $barangay = $_POST['brgy']; 
    $city = $_POST['city']; 
    $email_address = $_POST['emailaddress'];  
    $contact_no = $_POST['contactno'];  
    

    // Update query
    $update = "UPDATE students_registration SET 
        lrn='$student_no',
        lastname='$last_name',
        firstname='$first_name',
        middlename='$middle_name',
        year='$year',
        course='$course',
        sex='$sex',
        dob='$birth_date',
        specaddress='$address',
        brgy='$barangay',
        city='$city',
        mother='$mother_name',
        father='$father_name',
        gname='$guardian_name',
        religion='$religion',
        emailaddress='$email_address',
        contactno='$contact_no',
        schooltype='$school_type',
        sname='$last_school'
        WHERE id = $id";

    if (mysqli_query($conn, $update)) {
        // Send email notification that student info was updated
        $mail = new PHPMailer(true);

        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'deadpoolvictorio@gmail.com'; // your gmail
            $mail->Password   = 'ldcmeapjfuonxypu'; // your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
            $mail->addAddress($email_address, "$first_name $last_name");

            $mail->isHTML(true);
            $mail->Subject = 'Student Information Updated - Escuela De Sto. Rosario';
            $mail->Body = "
                <p>Dear $first_name $last_name,</p>
                <p>Your student information has been <strong>successfully updated</strong> by the school administration.</p>
                <p>If you did not request this update or if you have any concerns, please contact us immediately.</p>
                <br>
                <p>Thank you,<br>Escuela De Sto. Rosario</p>
            ";

            $mail->send();

            echo "<script>alert('Student record updated successfully and notification email sent!'); window.location.href='admin_dashboard.php';</script>";
            exit();
        } catch (Exception $e) {
            echo "<script>alert('Student record updated but email could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.location.href='admin_dashboard.php';</script>";
            exit();
        }

    } else {
        echo "Error: " . $update . "<br>" . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student Registration</title>
    <style>
        /* Your existing styles here */
        body {
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          background-color: #f0f4f3;
          margin: 0;
          padding: 0;
          color: #333;
        }
        header {
          background-color: #004d00;
          color: white;
          text-align: center;
          padding: 20px 0;
        }
        .container {
          width: 90%;
          max-width: 1000px;
          margin: 30px auto;
          background: white;
          padding: 30px;
          border-radius: 12px;
          box-shadow: 0 0 15px rgba(0, 0, 0, 0.08);
        }
        h2 {
          color: #007f3f;
          border-bottom: 2px solid #007f3f;
          padding-bottom: 10px;
        }
        input[type="text"], input[type="date"], select, input[type="email"], input[type="tel"] {
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 10px;
            width: 100%;
            margin-top: 5px;
        }
        input[type="submit"], button {
            background-color: #007f3f;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        input[type="submit"]:hover, button:hover {
            background-color: #004d00;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Student Registration</h2>
    <form method="post" action="">
        <input type="hidden" name="id" value="<?= htmlspecialchars($student['id']) ?>">

        <h3>Enrollment Info</h3>
        <label>LRN</label>
        <input type="text" name="Lrn" value="<?= htmlspecialchars($student['lrn']) ?>" placeholder="Enter Student No" required>

        <label>Grade Level:</label>
            <select name="year" required>
                <?php
                $grades = [
                    'k1' => 'Kinder 1',
                    'k2' => 'Kinder 2',
                    'g1' => 'Grade 1',
                    'g2' => 'Grade 2',
                    'g3' => 'Grade 3',
                    'g4' => 'Grade 4',
                    'g5' => 'Grade 5',
                    'g6' => 'Grade 6',
                    'g7' => 'Grade 7',
                    'g8' => 'Grade 8',
                    'g9' => 'Grade 9',
                    'g10' => 'Grade 10',
                    'g11' => 'Grade 11',
                    'g12' => 'Grade 12',
                ];
                foreach ($grades as $code => $label) {
                    $selected = $student['year'] == $code ? 'selected' : '';
                    echo "<option value='$code' $selected>$label</option>";
                }
                ?>
            </select>


        <label>Course:</label>
        <select name="course" required>
            <?php
            $courses = ['N/A', 'ABM', 'HUMSS', 'GAS', 'TVL - ICT', 'TVL - HE'];
            foreach ($courses as $c) {
                $selected = $student['course'] == $c ? 'selected' : '';
                echo "<option value='$c' $selected>$c</option>";
            }
            ?>
        </select>

        <h3>Personal Information</h3>
        <label>Last Name:</label>
        <input type="text" name="lastname" value="<?= htmlspecialchars($student['lastname']) ?>" placeholder="Enter Last Name" required>

        <label>First Name:</label>
        <input type="text" name="firstname" value="<?= htmlspecialchars($student['firstname']) ?>" placeholder="Enter First Name" required>

        <label>Middle Name:</label>
        <input type="text" name="middlename" value="<?= htmlspecialchars($student['middlename']) ?>" placeholder="Enter Middle Name">

        <label>House No./Street/Compound/Village/Phase:</label>
        <input type="text" name="specaddress" value="<?= htmlspecialchars($student['specaddress']) ?>" placeholder="Enter House Address" required>

        <label>Barangay:</label>
        <input type="text" name="brgy" value="<?= htmlspecialchars($student['brgy']) ?>" placeholder="Enter Barangay" required>

        <label>City/Municipality:</label>
        <input type="text" name="city" value="<?= htmlspecialchars($student['city']) ?>" placeholder="Enter City/Municipality" required>

        <label>Father's Name:</label>
        <input type="text" name="father" value="<?= htmlspecialchars($student['father']) ?>" placeholder="Enter Father's Name" required>

        <label>Mother's Name:</label>
        <input type="text" name="mother" value="<?= htmlspecialchars($student['mother']) ?>" placeholder="Enter Mother's Name" required>

        <label>Guardian's Name (Optional):</label>
        <input type="text" name="gname" value="<?= htmlspecialchars($student['gname']) ?>" placeholder="Enter Guardian's Name (Optional)">

        <label>Sex:</label>
        <select name="sex" required>
            <option value="Male" <?= $student['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?= $student['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
        </select>

        <label>Date of Birth:</label>
        <input type="date" name="dob" value="<?= htmlspecialchars($student['dob']) ?>" required>

        <label>Religion:</label>
        <select name="religion" required>
            <option value="Roman Catholic" <?= $student['religion'] == 'Roman Catholic' ? 'selected' : ''; ?>>Roman Catholic</option>
            <option value="Christian" <?= $student['religion'] == 'Christian' ? 'selected' : ''; ?>>Christian</option>
            <option value="Iglesia Ni Cristo" <?= $student['religion'] == 'Iglesia Ni Cristo' ? 'selected' : ''; ?>>Iglesia Ni Cristo</option>
            <option value="Islam" <?= $student['religion'] == 'Islam' ? 'selected' : ''; ?>>Islam</option>
            <option value="Others" <?= $student['religion'] == 'Others' ? 'selected' : ''; ?>>Others</option>
        </select>

        <label>Email Address:</label>
        <input type="email" name="emailaddress" value="<?= htmlspecialchars($student['emailaddress']) ?>" placeholder="Enter Email Address" required>

        <label>Contact No.:</label>
        <input type="tel" name="contactno" value="<?= htmlspecialchars($student['contactno']) ?>" placeholder="Enter Contact No." required>

        <h3>Educational Attainment</h3>
        <label>Type of School:</label>
        <select name="schooltype" required>
            <option value="Public" <?= $student['schooltype'] == 'Public' ? 'selected' : ''; ?>>Public</option>
            <option value="Private" <?= $student['schooltype'] == 'Private' ? 'selected' : ''; ?>>Private</option>
        </select>

        <label>School Name:</label>
        <input type="text" name="sname" value="<?= htmlspecialchars($student['sname']) ?>" placeholder="Enter Last School Attended" required>

        <br><br>
        <input type="submit" value="Update Student Information">
    </form>
</div>

</body>
</html>
