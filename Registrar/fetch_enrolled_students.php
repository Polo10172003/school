<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';

if (empty($_SESSION['registrar_username'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

include __DIR__ . '/../db_connection.php';
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit();
}

$conn->set_charset('utf8mb4');
@$conn->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_uca1400_ai_ci'");
@$conn->query("SET collation_connection = 'utf8mb4_uca1400_ai_ci'");

$gradeFilter = trim((string) ($_GET['grade_filter'] ?? ''));
$students = [];

try {
    if ($gradeFilter !== '') {
        $stmt = $conn->prepare("
            SELECT sr.*, sa.email AS emailaddress
            FROM students_registration sr
            LEFT JOIN student_accounts sa ON sr.emailaddress = sa.email
            WHERE sr.enrollment_status = 'enrolled' AND sr.year = ?
            ORDER BY sr.lastname ASC, sr.firstname ASC
        ");
        if ($stmt) {
            $stmt->bind_param('s', $gradeFilter);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $students[] = $row;
                }
                $result->free();
            }
            $stmt->close();
        }
    } else {
        $sql = "
            SELECT sr.*, sa.email AS emailaddress
            FROM students_registration sr
            LEFT JOIN student_accounts sa ON sr.emailaddress = sa.email
            WHERE sr.enrollment_status = 'enrolled'
            ORDER BY sr.lastname ASC, sr.firstname ASC
        ";
        if ($result = $conn->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            $result->free();
        }
    }
} catch (Throwable $error) {
    error_log('[registrar] fetch_enrolled_students query failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database query failed.']);
    $conn->close();
    exit();
}

$conn->close();
echo json_encode([
    'success' => true,
    'students' => $students,
]);
