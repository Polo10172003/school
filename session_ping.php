<?php
declare(strict_types=1);

define('SESSION_GUARD_JSON', true);

require_once __DIR__ . '/includes/session.php';
include __DIR__ . '/db_connection.php';

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'timestamp' => date('c'),
]);

$conn->close();
