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
    $normalized = strtolower(trim((string) $label));
    $normalized = str_replace(['-', '_'], ' ', $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized);

    $map = [
        'preschool'      => ['preschool'],
        'preprime1'      => ['pp1'],
        'pre prime 1'    => ['pp1'],
        'pre-prime 1'    => ['pp1'],
        'preprime2'      => ['pp2'],
        'pre prime 2'    => ['pp2'],
        'pre-prime 2'    => ['pp2'],
        'preprime12'     => ['pp12'],
        'pre prime 12'   => ['pp12'],
        'pre-prime 12'   => ['pp12'],
        'preprime 1 & 2' => ['pp12'],
        'kinder 1'       => ['kg', 'k1'],
        'kinder1'        => ['kg', 'k1'],
        'k1'             => ['kg', 'k1'],
        'kinder 2'       => ['kg', 'k2'],
        'kinder2'        => ['kg', 'k2'],
        'k2'             => ['kg', 'k2'],
        'kindergarten'   => ['kg', 'k1', 'k2'],
        'kinder'         => ['kg', 'k1', 'k2'],
        'kg'             => ['kg', 'k1', 'k2'],
        'grade 1'        => ['g1'],
        'grade1'         => ['g1'],
        'g1'             => ['g1'],
        'grade 2'        => ['g2'],
        'grade2'         => ['g2'],
        'g2'             => ['g2'],
        'grade 3'        => ['g3'],
        'grade3'         => ['g3'],
        'g3'             => ['g3'],
        'grade 4'        => ['g4'],
        'grade4'         => ['g4'],
        'g4'             => ['g4'],
        'grade 5'        => ['g5'],
        'grade5'         => ['g5'],
        'g5'             => ['g5'],
        'grade 6'        => ['g6'],
        'grade6'         => ['g6'],
        'g6'             => ['g6'],
        'grade 7'        => ['g7'],
        'grade7'         => ['g7'],
        'g7'             => ['g7'],
        'grade 8'        => ['g8'],
        'grade8'         => ['g8'],
        'g8'             => ['g8'],
        'grade 9'        => ['g9'],
        'grade9'         => ['g9'],
        'g9'             => ['g9'],
        'grade 10'       => ['g10'],
        'grade10'        => ['g10'],
        'g10'            => ['g10'],
        'grade 11'       => ['g11'],
        'grade11'        => ['g11'],
        'g11'            => ['g11'],
        'grade 12'       => ['g12'],
        'grade12'        => ['g12'],
        'g12'            => ['g12'],
    ];

    if (isset($map[$normalized])) {
        return $map[$normalized];
    }

    $compacted = str_replace(' ', '', $normalized);
    return $map[$compacted] ?? [];
}

$gradeCodes = map_grade_code($gradeLevel);

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
        if (!$should_include && !empty($gradeCodes)) {
            if (count(array_intersect($gradeCodes, $audiences)) > 0) {
                $should_include = true;
            }
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
