<?php
declare(strict_types=1);

if (!function_exists('session_guard_ensure_table')) {
    /**
     * Ensure we have a backing table to track active sessions.
     */
    function session_guard_ensure_table(mysqli $conn): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS active_sessions (
    context VARCHAR(32) NOT NULL,
    identifier VARCHAR(190) NOT NULL,
    session_token VARCHAR(64) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (context, identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;
        $conn->query($sql);
        $ensured = true;
    }

    /**
     * Generate a random session token.
     */
    function session_guard_generate_token(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Store the current session token in the database and session.
     */
    function session_guard_store(mysqli $conn, string $context, string $identifier): string
    {
        if ($identifier === '') {
            return '';
        }

        session_guard_ensure_table($conn);
        $token = session_guard_generate_token();

        $stmt = $conn->prepare('REPLACE INTO active_sessions (context, identifier, session_token, updated_at) VALUES (?, ?, ?, NOW())');
        if ($stmt) {
            $stmt->bind_param('sss', $context, $identifier, $token);
            $stmt->execute();
            $stmt->close();
        }

        if (!isset($_SESSION['session_tokens']) || !is_array($_SESSION['session_tokens'])) {
            $_SESSION['session_tokens'] = [];
        }
        $_SESSION['session_tokens'][$context] = $token;
        return $token;
    }

    /**
     * Remove any stored token for a user context.
     */
    function session_guard_clear(mysqli $conn, string $context, string $identifier): void
    {
        if ($identifier !== '') {
            session_guard_ensure_table($conn);
            $stmt = $conn->prepare('DELETE FROM active_sessions WHERE context = ? AND identifier = ?');
            if ($stmt) {
                $stmt->bind_param('ss', $context, $identifier);
                $stmt->execute();
                $stmt->close();
            }
        }

        if (isset($_SESSION['session_tokens'][$context])) {
            unset($_SESSION['session_tokens'][$context]);
        }
    }

    /**
     * Handle logout/response when a session token mismatch occurs.
     *
     * @param array{redirect?:string,response?:string} $options
     */
    function session_guard_handle_failure(array $options = []): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();

        $responseMode = $options['response'] ?? (defined('SESSION_GUARD_JSON') && SESSION_GUARD_JSON ? 'json' : 'redirect');

        if ($responseMode === 'json') {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Session expired. Please sign in again.']);
            exit();
        }

        $redirect = $options['redirect'] ?? (defined('APP_BASE_PATH') ? APP_BASE_PATH : '/');
        if (strpos($redirect, '?') === false) {
            $redirect .= '?session=expired';
        } else {
            $redirect .= '&session=expired';
        }
        header('Location: ' . $redirect);
        exit();
    }

    /**
     * Validate the active session token for a given context.
     *
     * @param array{redirect?:string,response?:string} $options
     */
    function session_guard_validate(mysqli $conn, string $context, ?string $identifier, array $options = []): bool
    {
        if (!$identifier) {
            return true;
        }

        session_guard_ensure_table($conn);

        $sessionTokens = $_SESSION['session_tokens'] ?? [];
        $sessionToken = $sessionTokens[$context] ?? '';
        if ($sessionToken === '') {
            session_guard_handle_failure($options);
            return false;
        }

        $stmt = $conn->prepare('SELECT session_token FROM active_sessions WHERE context = ? AND identifier = ? LIMIT 1');
        if (!$stmt) {
            session_guard_handle_failure($options);
            return false;
        }

        $stmt->bind_param('ss', $context, $identifier);
        $stmt->execute();
        $stmt->bind_result($storedToken);

        if ($stmt->fetch()) {
            $stmt->close();
            if (!hash_equals((string) $storedToken, $sessionToken)) {
                session_guard_handle_failure($options);
                return false;
            }
        } else {
            $stmt->close();
            session_guard_handle_failure($options);
            return false;
        }

        return true;
    }

    /**
     * Automatically validate any known session contexts for the active request.
     */
    function session_guard_base_path(string $suffix): string
    {
        $base = defined('APP_BASE_PATH') ? APP_BASE_PATH : '/';
        if ($base === '') {
            $base = '/';
        }
        if (substr($base, -1) !== '/') {
            $base .= '/';
        }
        return $base . ltrim($suffix, '/');
    }

    function session_guard_auto_check(mysqli $conn): void
    {
        if (defined('SESSION_GUARD_SKIP') && SESSION_GUARD_SKIP) {
            return;
        }

        $contexts = [
            'student'   => ['key' => 'student_number',      'redirect' => session_guard_base_path('Portal/student_login.php')],
            'cashier'   => ['key' => 'cashier_username',    'redirect' => session_guard_base_path('Cashier/cashier_login.php')],
            'registrar' => ['key' => 'registrar_username',  'redirect' => session_guard_base_path('Registrar/registrar_login.php')],
            'admin'     => ['key' => 'admin_username',      'redirect' => session_guard_base_path('admin_login.php')],
            'adviser'   => ['key' => 'adviser_username',    'redirect' => session_guard_base_path('Adviser/adviser_login.php')],
        ];

        foreach ($contexts as $context => $meta) {
            $identifier = $_SESSION[$meta['key']] ?? '';
            if ($identifier !== '') {
                session_guard_validate($conn, $context, $identifier, ['redirect' => $meta['redirect']]);
            }
        }
    }
}
