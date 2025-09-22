

<?php
session_start();

if (isset($_GET['student_number'])) {
    $_SESSION['student_number'] = $_GET['student_number'];  // Save LRN in session
}

$student_number = $_SESSION['student_number'] ?? $_POST['student_number'] ?? '';

$_SESSION[''] = $student_number;
?>


<!DOCTYPE html>
<html>
<head>
    <title>Online Payment</title>
    <style>
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .box {
            border: 1px solid #ccc;
            padding: 20px;
            margin: 0 auto;
            max-width: 500px;
            border-radius: 8px;
        }
        .note {
            background-color: #ffffcc;
            padding: 10px;
            margin-top: 15px;
            font-size: 14px;
            border-left: 4px solid #ffcc00;
        }
    </style>
</head>
<body>

<div class="box">
    <!-- Step 1: Show account info -->
    <div id="step1" class="step active">
        <h2>Online Payment - Step 1</h2>
        <p><strong>Choose a payment method:</strong></p>

        <div id="gcashInfo">
            <h4>GCash Number</h4>
            <p>0917-123-4567</p>
        </div>

        <div id="bankInfo">
            <h4>Bank Account Number</h4>
            <p>1234-5678-9012</p>
        </div>

        <div class="note">
            AFTER PAYING, PLEASE SCREENSHOT THE PROOF OF PAYMENT AND TAKE NOTE OF THE REFERENCE NUMBER.
        </div>

        <br>
        <button onclick="nextStep()">Next</button>
        <button onclick="goBackOnce()">Cancel</button>
    </div>

    <!-- Step 2: Show form -->
    <div id="step2" class="step">
        <h2>Online Payment - Step 2</h2>
        <form action="submit_payment.php" method="POST" enctype="multipart/form-data">
            <label for="reference_number">Reference Number:</label><br>
            <input type="text" name="reference_number" required><br><br>

            <label for="amount">Amount:</label><br>
            <input type="number" name="amount" required><br><br>

            <label for="screenshot">Upload Screenshot:</label><br>
            <input type="file" name="payment_screenshot" accept="image/*" required><br><br>

            <button type="submit">Submit Payment</button>
            <button type="button" onclick="goBack()">Cancel</button>
        </form>
    </div>
</div>

<script>
    // Set this dynamically based on user choice
    let selectedMethod = localStorage.getItem("paymentMethod") || "gcash"; // default

    document.getElementById("payment_type").value = selectedMethod;

    if (selectedMethod === "gcash") {
        document.getElementById("gcashInfo").style.display = "block";
    } else if (selectedMethod === "bank") {
        document.getElementById("bankInfo").style.display = "block";
    }

    function nextStep() {
        document.getElementById("step1").classList.remove("active");
        document.getElementById("step2").classList.add("active");
    }

    function goBack() {
        // Instead of window.history.back(), switch back to step 1
        document.getElementById("step2").classList.remove("active");
        document.getElementById("step1").classList.add("active");
    }
    function goBackOnce(){
        window.history.back();
    }
</script>


</body>
</html>
