<?php
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../db_connection.php';

date_default_timezone_set('Asia/Manila');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$subject = $argv[1] ?? '';
$body = $argv[2] ?? '';
$scope = $argv[3] ?? '';
$announcementId = isset($argv[4]) ? (int) $argv[4] : 0;
$imagePathArg = $argv[5] ?? '';

if ($subject === '' || $body === '') {
    exit(0);
}

$audiences = array_filter(array_map('trim', explode(',', $scope)));

if (in_array('everyone', $audiences, true)) {
    $stmt = $conn->prepare("SELECT DISTINCT emailaddress, firstname, lastname FROM students_registration WHERE emailaddress IS NOT NULL AND emailaddress != ''");
} else {
    $placeholders = implode(',', array_fill(0, count($audiences), '?'));
    $types = str_repeat('s', count($audiences));
    $stmt = $conn->prepare("SELECT DISTINCT emailaddress, firstname, lastname FROM students_registration WHERE year IN ($placeholders) AND emailaddress IS NOT NULL AND emailaddress != ''");
    $stmt->bind_param($types, ...$audiences);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    exit(0);
}

$imagePath = '';
if ($announcementId > 0) {
    $imageStmt = $conn->prepare('SELECT image_path FROM student_announcements WHERE id = ? LIMIT 1');
    if ($imageStmt) {
        $imageStmt->bind_param('i', $announcementId);
        $imageStmt->execute();
        $imageStmt->bind_result($dbImagePath);
        if ($imageStmt->fetch()) {
            $imagePath = trim((string) $dbImagePath);
        }
        $imageStmt->close();
    }
}

if ($imagePath === '' && $imagePathArg !== '') {
    $imagePath = trim($imagePathArg);
}

$absoluteImagePath = '';
if ($imagePath !== '') {
    $normalized = ltrim($imagePath, '/');
    $candidate = realpath(__DIR__ . '/../' . $normalized);
    if ($candidate && is_file($candidate)) {
        $absoluteImagePath = $candidate;
    } else {
        $absoluteImagePath = '';
    }
}

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'deadpoolvictorio@gmail.com';
    $mail->Password = 'ldcmeapjfuonxypu';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('deadpoolvictorio@gmail.com', 'Escuela De Sto. Rosario');
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mailBody = '<div style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6;">'
        . '<p>' . nl2br(htmlspecialchars($body)) . '</p>';

    $imageCid = null;
    if ($absoluteImagePath !== '') {
        $imageCid = 'announcement_image_' . uniqid();
        $mail->addEmbeddedImage($absoluteImagePath, $imageCid, basename($absoluteImagePath));
        $mailBody .= '<div style="margin-top:16px; text-align:center;">'
            . '<img src="cid:' . $imageCid . '" alt="Announcement Image" style="max-width:100%; border-radius:8px;" />'
            . '</div>';
    }

    $mailBody .= '</div>';
    $mail->Body = $mailBody;

    if ($absoluteImagePath !== '') {
        $mail->AltBody = $body . "\n\n[Image attached]";
        $mail->addAttachment($absoluteImagePath);
    } else {
        $mail->AltBody = $body;
    }

    $counter = 0;
    while ($row = $result->fetch_assoc()) {
        $mail->addAddress($row['emailaddress'], $row['firstname'] . ' ' . $row['lastname']);
        $counter++;
    }

    if ($counter > 0) {
        $mail->send();
    }
} catch (Exception $e) {
    error_log('Announcement mailer failed: ' . $mail->ErrorInfo);
}

$stmt->close();
$conn->close();
