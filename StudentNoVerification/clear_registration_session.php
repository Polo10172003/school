<?php
declare(strict_types=1);

define('SESSION_GUARD_SKIP', true);
require_once __DIR__ . '/../includes/session.php';

$isReturning = isset($_SESSION['returning_source_id']) || isset($_SESSION['returning_inactive_source_id']);

if (!$isReturning) {
    unset(
        $_SESSION['registration'],
        $_SESSION['registration_returning_tag'],
        $_SESSION['registration_previous_school_year']
    );
}

http_response_code(204);
exit();
