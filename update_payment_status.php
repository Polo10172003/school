    <?php
    require 'vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    include 'db_connection.php';
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    header('Content-Type: application/json');

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
        $status = $_POST["status"] ?? '';
        $or_number = isset($_POST["or_number"]) ? trim($_POST["or_number"]) : null;
        if ($or_number === '') { $or_number = null; }

        if ($id <= 0) {
            echo json_encode(["success" => false, "error" => "Missing or invalid payment id."]);
            $conn->close();
            exit();
        }
        if (!in_array($status, ['paid', 'declined'], true)) {
            echo json_encode(["success" => false, "error" => "Invalid status."]);
            $conn->close();
            exit();
        }

        // 🔹 Fetch payment_type first so we know whether it's Cash or Online
        $type_stmt = $conn->prepare("SELECT payment_type FROM student_payments WHERE id = ?");
        $type_stmt->bind_param("i", $id);
        $type_stmt->execute();
        $type_stmt->bind_result($payment_type);
        $type_stmt->fetch();
        $type_stmt->close();

        if (!$payment_type) {
            echo json_encode(["success" => false, "error" => "Payment not found."]);
            $conn->close();
            exit();
        }

        // 🔹 Build UPDATE
        if ($status === 'paid') {
            $payment_date = date("Y-m-d");

            if (strtolower($payment_type) === 'cash') {
                $sql = "UPDATE student_payments
                        SET payment_status = ?,
                            payment_date   = ?,
                            or_number      = COALESCE(?, or_number)
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $status, $payment_date, $or_number, $id);
            } else {
                $sql = "UPDATE student_payments
                        SET payment_status = ?,
                            payment_date   = ?,
                            reference_number = COALESCE(?, reference_number)
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $status, $payment_date, $or_number, $id);
            }
        } else {
            $sql = "UPDATE student_payments
                    SET payment_status = ?,
                        payment_date   = NULL
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $status, $id);
        }

        if (!$stmt->execute()) {
            echo json_encode(["success" => false, "error" => "Error updating payment."]);
            $stmt->close();
            $conn->close();
            exit();
        }
        $stmt->close();

        // 🔹 Get student info for email + enrollment update
        $student_stmt = $conn->prepare("
            SELECT sr.id, sr.emailaddress, sr.firstname, sr.lastname, sp.payment_type, sp.amount
            FROM student_payments sp
            JOIN students_registration sr ON sr.id = sp.student_id
            WHERE sp.id = ?
        ");
        $student_stmt->bind_param("i", $id);
        $student_stmt->execute();
        $student_stmt->bind_result($student_id, $email, $firstname, $lastname, $payment_type, $amount);
        $student_stmt->fetch();
        $student_stmt->close();

        if (!$student_id) {
            echo json_encode(["success" => false, "error" => "Payment found, but student not found."]);
            $conn->close();
            exit();
        }

        // 🔹 Update enrollment if paid
        if ($status === 'paid') {
            $enroll_stmt = $conn->prepare("UPDATE students_registration SET enrollment_status = 'enrolled' WHERE id = ?");
            $enroll_stmt->bind_param("i", $student_id);
            $enroll_stmt->execute();
            $enroll_stmt->close();
        }

        // 🔹 Send Email
        if (!empty($email)) {
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
                    $extraLine = "";
                    if (strtolower($payment_type) === 'cash' && !empty($or_number)) {
                        $extraLine = "<li><strong>Official Receipt #:</strong> " . htmlspecialchars($or_number) . "</li>";
                    }
                    $mail->Body = "
                        <h2>Payment Receipt Confirmation</h2>
                        <p>Dear <strong>$firstname $lastname</strong>,</p>
                        <p>We have received your payment:</p>
                        <ul>
                            <li><strong>Payment Type:</strong> $payment_type</li>
                            <li><strong>Amount:</strong> ₱" . number_format((float)$amount, 2) . "</li>
                            $extraLine
                            <li><strong>Status:</strong> PAID</li>
                        </ul>
                        <p>Your enrollment is now marked as <strong>ENROLLED</strong>.</p>
                    ";
                } else {
                    $mail->Subject = "Payment Declined - Escuela De Sto. Rosario";
                    $mail->Body = "
                        <h2>Payment Declined</h2>
                        <p>Dear <strong>$firstname $lastname</strong>,</p>
                        <p>We regret to inform you that your payment has been <strong>DECLINED</strong>.</p>
                        <p>Please visit the school cashier for clarification.</p>
                    ";
                }
                $mail->send();
            } catch (Exception $e) {
                error_log("Email failed: {$mail->ErrorInfo}");
            }
        }

        echo json_encode(["success" => true, "status" => $status]);
        $conn->close();
        exit();
    }

    $conn->close();
    echo json_encode(["success" => false, "error" => "Invalid request method."]);
