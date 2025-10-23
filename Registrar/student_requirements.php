<?php
declare(strict_types=1);

define('SESSION_GUARD_JSON', true);

require_once __DIR__ . '/../includes/session.php';

if (empty($_SESSION['registrar_username'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

include __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/../includes/student_requirements.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit();
}

$conn->set_charset('utf8mb4');
@$conn->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_uca1400_ai_ci'");
@$conn->query("SET collation_connection = 'utf8mb4_uca1400_ai_ci'");

student_requirements_ensure_schema($conn);

/**
 * Fetch minimal student profile.
 */
function registrar_requirements_fetch_student(mysqli $conn, int $studentId): ?array
{
    $stmt = $conn->prepare('SELECT id, firstname, lastname, student_number, year FROM students_registration WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return null;
    }

    $firstname = trim((string) ($row['firstname'] ?? ''));
    $lastname = trim((string) ($row['lastname'] ?? ''));
    $fullName = trim($firstname . ' ' . $lastname);

    return [
        'id'             => (int) ($row['id'] ?? 0),
        'firstname'      => $firstname,
        'lastname'       => $lastname,
        'full_name'      => $fullName !== '' ? $fullName : 'Student',
        'student_number' => $row['student_number'] ?? '',
        'grade_level'    => $row['year'] ?? '',
    ];
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    $studentId = (int) ($_GET['student_id'] ?? 0);
    if ($studentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing or invalid student id.']);
        $conn->close();
        exit();
    }

    $student = registrar_requirements_fetch_student($conn, $studentId);
    if (!$student) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Student not found.']);
        $conn->close();
        exit();
    }

    $record = student_requirements_fetch_single($conn, $studentId);
    $payload = student_requirements_build_payload($student['grade_level'], $record);

    $conn->close();
    echo json_encode([
        'success'      => true,
        'student'      => $student,
        'requirements' => $payload,
    ]);
    exit();
}

if ($method === 'POST') {
    $rawBody = file_get_contents('php://input') ?: '';
    $data = json_decode($rawBody, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid payload.']);
        $conn->close();
        exit();
    }

    $studentId = (int) ($data['student_id'] ?? 0);
    if ($studentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing or invalid student id.']);
        $conn->close();
        exit();
    }

    $student = registrar_requirements_fetch_student($conn, $studentId);
    if (!$student) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Student not found.']);
        $conn->close();
        exit();
    }

    $gradeLevel = (string) ($data['grade_level'] ?? $student['grade_level'] ?? '');
    $scopeInput = strtolower(trim((string) ($data['scope'] ?? '')));
    $valuesInput = is_array($data['values'] ?? null) ? $data['values'] : [];

    $existingRecord = student_requirements_fetch_single($conn, $studentId);
    $normalizedExisting = student_requirements_normalize_record($existingRecord);

    if ($scopeInput === '' || $scopeInput === 'auto') {
        $scope = student_requirements_resolve_scope($gradeLevel, $normalizedExisting);
    } elseif (!in_array($scopeInput, ['early_child', 'k_to_12'], true)) {
        $scope = student_requirements_resolve_scope($gradeLevel, $normalizedExisting);
    } else {
        $scope = $scopeInput;
    }

    $keyMap = student_requirements_key_map();
    $mergedValues = [];
    foreach (array_keys($keyMap) as $key) {
        if (array_key_exists($key, $valuesInput)) {
            $mergedValues[$key] = !empty($valuesInput[$key]);
        } elseif (!empty($normalizedExisting['values'][$key])) {
            $mergedValues[$key] = true;
        } else {
            $mergedValues[$key] = false;
        }
    }

    $updatedBy = $_SESSION['registrar_username'] ?? null;
    $saved = student_requirements_save($conn, $studentId, $mergedValues, $scope, $updatedBy);
    if (!$saved) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Unable to save requirements.']);
        $conn->close();
        exit();
    }

    $freshRecord = student_requirements_fetch_single($conn, $studentId);
    $payload = student_requirements_build_payload($gradeLevel, $freshRecord);

    $conn->close();
    echo json_encode([
        'success'      => true,
        'student'      => $student,
        'requirements' => $payload,
    ]);
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
$conn->close();
