<?php
declare(strict_types=1);

header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'error'   => 'Portal activation is handled automatically after payment. Manual activation is disabled.',
]);
exit();
