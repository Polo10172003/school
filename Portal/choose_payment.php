<?php
session_start();

if (isset($_GET['student_number'])) {
    $_SESSION['student_number'] = $_GET['student_number'];
}

$student_number = $_SESSION['student_number'] ?? ($_POST['student_number'] ?? '');
$student_id = $_GET['student_id'] ?? ($_POST['student_id'] ?? ($_SESSION['student_id'] ?? ''));
if (!empty($student_id)) {
    $_SESSION['student_id'] = $student_id;
}

$paymentSuccess = $_SESSION['payment_success'] ?? false;
$paymentSuccessMessage = '';
if ($paymentSuccess) {
    $paymentSuccessMessage = $_SESSION['payment_success_message'] ?? '';
    unset($_SESSION['payment_success'], $_SESSION['payment_success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, rgba(20, 90, 50, 0.16) 0%, rgba(255, 255, 255, 0.85) 65%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
        }

        .payment-wrapper {
            max-width: 960px;
            width: 100%;
        }

        .payment-card {
            background-color: #ffffff;
            border-radius: 24px;
            box-shadow: 0 30px 60px rgba(12, 68, 49, 0.15);
            overflow: hidden;
            border: 1px solid rgba(20, 90, 50, 0.08);
        }

        .payment-header {
            padding: 32px 40px 24px;
            background: linear-gradient(120deg, rgba(20, 90, 50, 0.08), rgba(251, 216, 10, 0.15));
        }

        .payment-header h1 {
            margin: 0;
            font-weight: 700;
            color: #145A32;
        }

        .payment-header p {
            margin: 10px 0 0;
            color: #4a5a58;
        }

        .stepper {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #145A32;
        }

        .stepper span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #145A32;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }

        .payment-body {
            padding: 32px 40px 40px;
        }

        .step {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .step.active {
            display: block;
        }

        .method-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            margin: 24px 0 30px;
        }

        .method-card {
            border: 1px solid rgba(20, 90, 50, 0.12);
            border-radius: 18px;
            padding: 24px;
            text-align: left;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
        }

        .method-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 35px rgba(12, 68, 49, 0.14);
        }

        .method-card.active {
            border-color: #145A32;
            box-shadow: 0 18px 35px rgba(12, 68, 49, 0.18);
        }

        .method-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #145A32;
            background: rgba(20, 90, 50, 0.08);
            margin-bottom: 12px;
        }

        .method-card h3 {
            font-size: 1.1rem;
            margin-bottom: 6px;
            font-weight: 700;
            color: #145A32;
        }

        .method-card p {
            margin: 0;
            color: #4a5a58;
            font-size: 0.93rem;
        }

        .info-card {
            border-radius: 18px;
            background: rgba(20, 90, 50, 0.05);
            padding: 22px;
            margin-bottom: 20px;
            display: none;
        }

        .info-card h4 {
            margin-bottom: 12px;
            color: #145A32;
            font-weight: 700;
        }

        .info-card ul {
            margin: 0;
            padding-left: 18px;
            color: #3d4c4b;
        }

        .note-box {
            background: rgba(251, 216, 10, 0.18);
            border-radius: 12px;
            padding: 16px 18px;
            border-left: 4px solid #f1c40f;
            color: #8a6d0a;
            font-weight: 600;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .step-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 28px;
        }

        .btn-primary-soft {
            background: #145A32;
            color: #fff;
            border-radius: 999px;
            padding: 10px 28px;
            border: none;
            font-weight: 600;
        }

        .btn-light-link {
            border: none;
            background: transparent;
            color: #4a5a58;
            font-weight: 600;
            text-decoration: underline;
        }

        form label {
            font-weight: 600;
            margin-bottom: 6px;
            color: #145A32;
        }

        form .form-control,
        form .form-control:focus {
            border-radius: 12px;
            border: 1px solid rgba(20, 90, 50, 0.25);
            padding: 12px 14px;
            box-shadow: none;
        }

        .upload-hint {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 6px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 576px) {
            .payment-header,
            .payment-body {
                padding: 24px;
            }

            .step-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-primary-soft,
            .btn-light-link {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="payment-wrapper">
        <div class="payment-card">
            <div class="payment-header">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div>
                        <div class="stepper">
                            <span>1</span> Select Method &nbsp;&nbsp;→&nbsp;&nbsp; 2 Submit Proof
                        </div>
                        <h1 class="mt-3 mb-1">Online Payment</h1>
                        <p>Pick the payment channel that suits you, then upload your receipt so we can verify it quickly.</p>
                    </div>
                    <div class="text-lg-end text-success fw-semibold">
                        <i class="bi bi-shield-check me-1"></i>Secure &amp; Verified Processing
                    </div>
                </div>
            </div>

            <div class="payment-body">
                <div id="step1" class="step active">
                    <h2 class="h4 fw-bold text-success mb-2">Step 1 • Choose a payment method</h2>
                    <p class="text-muted mb-4">Tap a card below to highlight your preferred channel. You can always switch before continuing.</p>

                    <div class="method-grid">
                        <button type="button" class="method-card" data-method="gcash">
                            <div class="icon"><i class="bi bi-phone"></i></div>
                            <h3>GCash</h3>
                            <p>Instant mobile payments. Make sure to keep a copy of your reference number after you send.</p>
                        </button>
                        <button type="button" class="method-card" data-method="bank">
                            <div class="icon"><i class="bi bi-bank"></i></div>
                            <h3>Bank Transfer</h3>
                            <p>Send through your preferred bank app or over-the-counter branch then upload the deposit slip.</p>
                        </button>
                    </div>

                    <div id="gcashInfo" class="info-card">
                        <h4><i class="bi bi-phone-vibrate me-2"></i>GCash Details</h4>
                        <ul>
                            <li>Account Name: <strong>Escuela de Sto. Rosario</strong></li>
                            <li>GCash Number: <strong>0917-123-4567</strong></li>
                            <li>Send exact amount shown in your portal to avoid delays.</li>
                        </ul>
                    </div>

                    <div id="bankInfo" class="info-card">
                        <h4><i class="bi bi-building me-2"></i>Bank Transfer Details</h4>
                        <ul>
                            <li>Account Name: <strong>Escuela de Sto. Rosario</strong></li>
                            <li>Account Number: <strong>1234-5678-9012</strong></li>
                            <li>Include the student name in the bank's remarks if allowed.</li>
                        </ul>
                    </div>

                    <div class="note-box">
                        <i class="bi bi-info-circle"></i>
                        <span>After paying, screenshot or save your proof of payment and jot down the reference number. You'll need it on the next step.</span>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn-light-link" onclick="goBackOnce()"><i class="bi bi-arrow-left"></i> Go back</button>
                        <button type="button" class="btn-primary-soft" onclick="nextStep()">Continue to upload</button>
                    </div>
                </div>

                <div id="step2" class="step">
                    <h2 class="h4 fw-bold text-success mb-2">Step 2 • Submit payment details</h2>
                    <p class="text-muted mb-4">Share the basic details so our cashier team can match your payment with your account.</p>

                    <form action="submit_payment.php" method="POST" enctype="multipart/form-data" class="row g-4">
                        <input type="hidden" name="payment_type" id="payment_type" value="">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                        <input type="hidden" name="student_number" value="<?php echo htmlspecialchars($student_number); ?>">

                        <div class="col-12">
                            <label for="reference_number" class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_number" id="reference_number" placeholder="Enter the reference number from your receipt" required>
                        </div>

                        <div class="col-md-6">
                            <label for="amount" class="form-label">Amount Paid</label>
                            <input type="number" class="form-control" name="amount" id="amount" placeholder="0.00" step="0.01" min="0" required>
                        </div>

                        <div class="col-md-6">
                            <label for="payment_screenshot" class="form-label">Upload Proof of Payment</label>
                            <input type="file" class="form-control" name="payment_screenshot" id="payment_screenshot" accept="image/*" required>
                            <div class="upload-hint">Accepts clear photos or screenshots in JPG/PNG format.</div>
                        </div>

                        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <button type="button" class="btn-light-link" onclick="goBack()"><i class="bi bi-arrow-left"></i> Back to methods</button>
                            <button type="submit" class="btn btn-success px-4 fw-semibold"><i class="bi bi-cloud-arrow-up me-2"></i>Submit for review</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0 justify-content-center">
                    <h5 class="modal-title text-success fw-bold"><i class="bi bi-check-circle-fill me-2"></i>Payment Submitted</h5>
                </div>
                <div class="modal-body text-center pb-0">
                    <p class="mb-0 text-muted">
                        <?= nl2br(htmlspecialchars($paymentSuccessMessage ?: "Thank you for your payment. Your transaction is now being reviewed by our finance department.\nPlease keep your reference number and wait for confirmation via SMS or Email.")); ?>
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-success px-4" id="paymentSuccessConfirm">Go to student portal</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const paymentSuccess = <?= $paymentSuccess ? 'true' : 'false'; ?>;
        const paymentSuccessMessage = <?= json_encode($paymentSuccessMessage); ?>;
        const methodCards = document.querySelectorAll('.method-card');
        const paymentTypeInput = document.getElementById('payment_type');
        const gcashInfo = document.getElementById('gcashInfo');
        const bankInfo = document.getElementById('bankInfo');

        let selectedMethod = localStorage.getItem('paymentMethod') || 'gcash';

        function reflectSelection(method) {
            methodCards.forEach(card => {
                card.classList.toggle('active', card.dataset.method === method);
            });

            if (paymentTypeInput) {
                paymentTypeInput.value = method;
            }

            if (method === 'gcash') {
                gcashInfo.style.display = 'block';
                bankInfo.style.display = 'none';
            } else {
                gcashInfo.style.display = 'none';
                bankInfo.style.display = 'block';
            }
        }

        methodCards.forEach(card => {
            card.addEventListener('click', function () {
                selectedMethod = this.dataset.method;
                localStorage.setItem('paymentMethod', selectedMethod);
                reflectSelection(selectedMethod);
            });
        });

        reflectSelection(selectedMethod);

        function nextStep() {
            document.getElementById('step1').classList.remove('active');
            document.getElementById('step2').classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function goBack() {
            document.getElementById('step2').classList.remove('active');
            document.getElementById('step1').classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function goBackOnce() {
            window.history.back();
        }

        if (paymentSuccess) {
            const redirectToPortal = () => {
                window.location.href = 'student_portal.php';
            };

            const modalElement = document.getElementById('paymentSuccessModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                const successModal = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                });

                const confirmBtn = document.getElementById('paymentSuccessConfirm');
                if (confirmBtn) {
                    confirmBtn.addEventListener('click', () => successModal.hide());
                }

                modalElement.addEventListener('hidden.bs.modal', redirectToPortal);
                successModal.show();
                setTimeout(redirectToPortal, 6000);
            } else {
                alert(paymentSuccessMessage || 'Thank you for your payment. Your transaction is now being reviewed by our finance department. Please keep your reference number and wait for confirmation via SMS or Email.');
                redirectToPortal();
            }
        }
    </script>
</body>
</html>
