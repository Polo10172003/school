<?php
session_start();
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['student_number'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

include __DIR__ . '/../db_connection.php';

$student_number = $_SESSION['student_number'];

$stmt = $conn->prepare('SELECT year FROM students_registration WHERE student_number = ?');
$stmt->bind_param('s', $student_number);
$stmt->execute();
$stmt->bind_result($gradeLevel);
$stmt->fetch();
$stmt->close();

if (!$gradeLevel) {
    echo json_encode(['success' => true, 'items' => []]);
    exit();
}

function map_grade_code($label)
{
    $key = strtolower(str_replace([' ', '-'], '', $label));
    switch ($key) {
        case 'preschool':
            return 'preschool';
        case 'kinder1':
        case 'k1':
            return 'k1';
        case 'kinder2':
        case 'k2':
            return 'k2';
        case 'grade1':
        case 'g1':
            return 'g1';
        case 'grade2':
        case 'g2':
            return 'g2';
        case 'grade3':
        case 'g3':
            return 'g3';
        case 'grade4':
        case 'g4':
            return 'g4';
        case 'grade5':
        case 'g5':
            return 'g5';
        case 'grade6':
        case 'g6':
            return 'g6';
        case 'grade7':
        case 'g7':
            return 'g7';
        case 'grade8':
        case 'g8':
            return 'g8';
        case 'grade9':
        case 'g9':
            return 'g9';
        case 'grade10':
        case 'g10':
            return 'g10';
        case 'grade11':
        case 'g11':
            return 'g11';
        case 'grade12':
        case 'g12':
            return 'g12';
        default:
            return '';
    }
}

$gradeCode = map_grade_code($gradeLevel);

$conn->query("CREATE TABLE IF NOT EXISTS student_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    target_scope TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$result = $conn->query('SELECT subject, body, target_scope, created_at FROM student_announcements ORDER BY created_at DESC LIMIT 50');

$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $scope = $row['target_scope'];
        $audiences = array_filter(array_map('trim', explode(',', $scope)));
        $should_include = in_array('everyone', $audiences, true);
        if (!$should_include && $gradeCode !== '') {
            $should_include = in_array($gradeCode, $audiences, true);
        }
        if ($should_include) {
            $items[] = [
                'subject' => htmlspecialchars($row['subject']),
                'body' => nl2br(htmlspecialchars($row['body'])),
                'sent_at' => date('M d, Y g:i A', strtotime($row['created_at'])),
            ];
        }
    }
}

echo json_encode(['success' => true, 'items' => $items]);
