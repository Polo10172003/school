<?php
// payment_form.php
session_start();

// Get the LRN from GET parameter (passed after registration)
$lrn = $_GET['lrn'] ?? '';

if (!$lrn) {
    die("Student LRN is required to make a payment.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Submit Payment</title>
</head>
<body>
    <h1>Submit Your Payment</h1>
    <form action="submit_payment.php" method="POST" enctype="multipart/form-data">
        <!-- Hidden LRN field -->
        <input type="hidden" name="lrn" value="<?php echo htmlspecialchars($lrn); ?>" />


        <div>
            <label for="reference_number">Reference Number:</label><br />
            <input type="text" id="reference_number" name="reference_number" required />
        </div>
           <div><label for="amount">Amount Paid:</label>
            <input type="number" step="0.01" name="amount" required>
        </div>

        <br />
        <div>
            <label for="payment_screenshot">Upload Payment Screenshot:</label><br />
            <input type="file" id="payment_screenshot" name="payment_screenshot" accept="image/*" required />
        </div>
        <br />
        <button type="submit">Submit Payment</button>
    </form>
</body>
</html>
