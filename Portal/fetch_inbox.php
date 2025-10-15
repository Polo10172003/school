<?php
define('SESSION_GUARD_JSON', true);
require_once __DIR__ . '/../includes/session.php';
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

/**
 * Normalize announcement timestamps for display and ISO transport.
 *
 * @param string|null   $value
 * @param DateTimeZone  $targetTimezone
 *
 * @return array{display:string,iso:string}
 */
function portal_format_announcement_timestamp(?string $value, DateTimeZone $targetTimezone): array
{
    $now = new DateTimeImmutable('now', $targetTimezone);
    $fallback = [
        'display' => $now->format('M d, Y g:i A'),
        'iso'     => $now->format(DateTimeInterface::ATOM),
    ];

    if ($value === null) {
        return $fallback;
    }

    $input = trim((string) $value);
    if ($input === '') {
        return $fallback;
    }

    $candidates = [];

    try {
        $candidates[] = (new DateTimeImmutable($input, new DateTimeZone('UTC')))->setTimezone($targetTimezone);
    } catch (Throwable $utcError) {
        // Ignore and try other strategies.
    }

    try {
        $candidates[] = new DateTimeImmutable($input, $targetTimezone);
    } catch (Throwable $localError) {
        // Ignore and fall back later.
    }

    if (empty($candidates)) {
        try {
            $candidates[] = (new DateTimeImmutable($input))->setTimezone($targetTimezone);
        } catch (Throwable $genericError) {
            // Still nothing, return fallback.
        }
    }

    if (empty($candidates)) {
        return $fallback;
    }

    usort($candidates, static function (DateTimeImmutable $a, DateTimeImmutable $b) use ($now): int {
        return abs($a->getTimestamp() - $now->getTimestamp()) <=> abs($b->getTimestamp() - $now->getTimestamp());
    });

    $selected = $candidates[0];

    return [
        'display' => $selected->format('M d, Y g:i A'),
        'iso'     => $selected->format(DateTimeInterface::ATOM),
    ];
}

$gradeCodes = map_grade_code($gradeLevel);

$timezoneName = getenv('APP_TIMEZONE');
if (!is_string($timezoneName) || $timezoneName === '') {
    $timezoneName = date_default_timezone_get() ?: 'Asia/Manila';
}
try {
    $appTimezone = new DateTimeZone($timezoneName);
} catch (Exception $timezoneException) {
    $appTimezone = new DateTimeZone('Asia/Manila');
}

$query = "SELECT sa.id, sa.subject, sa.body, sa.target_scope, sa.image_path, sa.created_at,
                  COALESCE(sas.is_read, 0) AS is_read,
                  COALESCE(sas.is_deleted, 0) AS is_deleted
           FROM student_announcements sa
           LEFT JOIN student_announcement_status sas
             ON sas.announcement_id = sa.id AND sas.student_number = ?
           ORDER BY sa.created_at DESC
           LIMIT 100";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $student_number);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$unreadCount = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ((int) ($row['is_deleted'] ?? 0) === 1) {
            continue;
        }

        $scope = $row['target_scope'] ?? '';
        $audiences = array_filter(array_map('trim', explode(',', $scope)));
        $should_include = in_array('everyone', $audiences, true);
        if (!$should_include && !empty($gradeCodes)) {
            if (count(array_intersect($gradeCodes, $audiences)) > 0) {
                $should_include = true;
            }
        }

        if (!$should_include) {
            continue;
        }

        $isRead = ((int) ($row['is_read'] ?? 0) === 1);
        if (!$isRead) {
            $unreadCount++;
        }

        $body = trim((string) ($row['body'] ?? ''));
        $subject = trim((string) ($row['subject'] ?? 'Announcement'));
        $timestampInfo = portal_format_announcement_timestamp($row['created_at'] ?? '', $appTimezone);

        $imagePath = trim((string) ($row['image_path'] ?? ''));
        $items[] = [
            'id' => (int) $row['id'],
            'subject' => $subject === '' ? 'Announcement' : $subject,
            'body_html' => nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')),
            'body_plain' => $body,
            'sent_at' => $timestampInfo['display'],
            'sent_at_iso' => $timestampInfo['iso'],
            'is_read' => $isRead ? 1 : 0,
            'image_path' => $imagePath,
        ];
    }
}

$stmt->close();

echo json_encode([
    'success' => true,
    'items' => $items,
    'unread_count' => $unreadCount,
]);
