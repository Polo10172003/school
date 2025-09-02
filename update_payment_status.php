<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db_connection.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
header('Content-Type: application/json'); // always send JSON


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST["id"]);
    $status = $_POST["status"];

    if (in_array($status, ['paid', 'declined'])) {
        if ($status === 'paid') {
            // Paid → set current date
            $payment_date = date("Y-m-d");

            $stmt = $conn->prepare("UPDATE student_payments SET payment_status=?, payment_date=? WHERE id=?");
            $stmt->bind_param("ssi", $status, $payment_date, $id);
        } else {
            // Declined → set payment_date NULL
            $stmt = $conn->prepare("UPDATE student_payments SET payment_status=?, payment_date=NULL WHERE id=?");
            $stmt->bind_param("si", $status, $id);
        }

        if ($stmt->execute()) {
            // Fetch student info for email
            $student_stmt = $conn->prepare("
                SELECT sr.emailaddress, sr.firstname, sr.lastname, sp.payment_type, sp.amount 
                FROM student_payments sp
                JOIN students_registration sr ON sr.id = sp.student_id
                WHERE sp.id = ?
            ");
            $student_stmt->bind_param("i", $id);
            $student_stmt->execute();
            $student_stmt->bind_result($email, $firstname, $lastname, $payment_type, $amount);
            $student_stmt->fetch();
            $student_stmt->close();

            if ($email) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'deadpoolvictorio@gmail.com'; 
                    $mail->Password = 'ldcmeapjfuonxypu'; 
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
                    $mail->addAddress($email, "$firstname $lastname");
                    $mail->isHTML(true);

                    if ($status === 'paid') {
                        $mail->Subject = "Payment Confirmation - Escuela De Sto. Rosario";
                        $mail->Body = "
                            <h2>Payment Receipt Confirmation</h2>
                            <p>Dear <strong>$firstname $lastname</strong>,</p>
                            <p>We have received your payment:</p>
                            <ul>
                                <li><strong>Payment Type:</strong> $payment_type</li>
                                <li><strong>Amount:</strong> ₱" . number_format($amount, 2) . "</li>
                                <li><strong>Status:</strong> PAID</li>
                            </ul>
                            <p>Your enrollment is now marked as <strong>ENROLLED</strong>.</p>
                            <hr>
                            <p><strong>IMPORTANT:</strong> Please wait for your student portal to be activated with your email address: <strong>$email</strong>.</p>
                            <br>
                            <p>Best regards,<br>Escuela De Sto. Rosario</p>
                        ";
                    } else {
                        $mail->Subject = "Payment Declined - Escuela De Sto. Rosario";
                        $mail->Body = "
                            <h2>Payment Declined</h2>
                            <p>Dear <strong>$firstname $lastname</strong>,</p>
                            <p>We regret to inform you that your payment has been <strong>DECLINED</strong>.</p>
                            <ul>
                                <li><strong>Payment Type:</strong> $payment_type</li>
                                <li><strong>Amount:</strong> ₱" . number_format($amount, 2) . "</li>
                                <li><strong>Status:</strong> DECLINED</li>
                            </ul>
                            <p>This may be due to an <strong>improper or incorrect reference number</strong>.</p>
                            <p>Please visit the school cashier to appeal or provide clarification.</p>
                            <br>
                            <p>Thank you,<br>Escuela De Sto. Rosario</p>
                        ";
                    }

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Email failed: {$mail->ErrorInfo}");
                }
            }

            echo json_encode(["success" => true, "status" => $status]);
        } else {
            echo json_encode(["success" => false, "error" => "Error updating payment."]);
        }

        $stmt->close();
    } else {
        echo json_encode(["success" => false, "error" => "Invalid status."]);
    }
}

$conn->close();