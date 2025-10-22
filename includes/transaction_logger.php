<?php

require_once __DIR__ . '/session.php';

/**
 * Resolve the best available actor context based on the current session.
 *
 * @param mysqli     $conn
 * @param array|null $overrides Optional overrides such as ['context' => 'cashier']
 *
 * @return array{context:string, user_id: ?int, username:string, fullname:string, role:string}
 */
function transaction_log_resolve_actor(mysqli $conn, ?array $overrides = null): array
{
    $contextOverride = $overrides['context'] ?? null;

    $candidates = [
        'admin' => [
            'username' => $_SESSION['admin_username'] ?? null,
            'fullname' => $_SESSION['admin_fullname'] ?? null,
            'role'     => $_SESSION['admin_role'] ?? 'admin',
        ],
        'cashier' => [
            'username' => $_SESSION['cashier_username'] ?? null,
            'fullname' => $_SESSION['cashier_fullname'] ?? null,
            'role'     => $_SESSION['cashier_role'] ?? 'cashier',
        ],
        'registrar' => [
            'username' => $_SESSION['registrar_username'] ?? null,
            'fullname' => $_SESSION['registrar_fullname'] ?? null,
            'role'     => $_SESSION['registrar_role'] ?? 'registrar',
        ],
    ];

    $candidateOrder = $contextOverride && isset($candidates[$contextOverride])
        ? [$contextOverride]
        : array_keys($candidates);

    foreach ($candidateOrder as $context) {
        $candidate = $candidates[$context];
        $username  = $candidate['username'] ?? null;
        if ($username === null || $username === '') {
            continue;
        }

        $userData = transaction_log_lookup_user($conn, $username);
        $userId   = $userData['id'] ?? null;
        $fullname = $candidate['fullname'] ?? ($userData['fullname'] ?? $username);

        return [
            'context'  => $context,
            'user_id'  => $userId !== null ? (int) $userId : null,
            'username' => $username,
            'fullname' => $fullname,
            'role'     => $context,
        ];
    }

    $fallbackContext = $contextOverride ?? 'system';

    return [
        'context'  => $fallbackContext,
        'user_id'  => null,
        'username' => $fallbackContext,
        'fullname' => ucwords(str_replace('_', ' ', $fallbackContext)),
        'role'     => $fallbackContext,
    ];
}

/**
 * Lookup user details once per request to attach IDs/roles to log entries.
 *
 * @param mysqli $conn
 * @param string $username
 *
 * @return array{id:int, fullname:?string, role:?string}|null
 */
function transaction_log_lookup_user(mysqli $conn, string $username): ?array
{
    static $cache = [];

    if (isset($cache[$username])) {
        return $cache[$username];
    }

    $stmt = $conn->prepare('SELECT id, fullname, role FROM users WHERE username = ? LIMIT 1');
    if (!$stmt) {
        $cache[$username] = null;
        return null;
    }
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        $cache[$username] = null;
        return null;
    }

    $normalized = [
        'id'       => isset($row['id']) ? (int) $row['id'] : null,
        'fullname' => $row['fullname'] ?? null,
        'role'     => $row['role'] ?? null,
    ];
    $cache[$username] = $normalized;
    return $normalized;
}

/**
 * Build a best-effort JSON string for metadata payloads.
 *
 * @param mixed $value
 */
function transaction_log_encode_metadata($value): ?string
{
    if ($value === null) {
        return null;
    }

    if (is_string($value)) {
        return $value === '' ? null : $value;
    }

    try {
        $encoded = json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
        );
    } catch (Throwable $e) {
        $encoded = json_encode((string) print_r($value, true));
    }

    if ($encoded === false) {
        return null;
    }

    return $encoded;
}

/**
 * Determine the best available client IP for logging purposes.
 */
function transaction_log_detect_ip(): ?string
{
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $raw = (string) $_SERVER[$key];
        $segments = array_map('trim', explode(',', $raw));
        foreach ($segments as $segment) {
            if ($segment !== '') {
                return substr($segment, 0, 45);
            }
        }
    }

    return null;
}

/**
 * Insert a structured transaction log entry.
 *
 * Expected keys inside $entry:
 *  - action (string, required)
 *  - description (string)
 *  - category (string)
 *  - target_type (string)
 *  - target_id (scalar)
 *  - metadata (mixed)
 *  - ip_address (string)
 *  - actor (array{username?:string, fullname?:string, role?:string, user_id?:int})
 *  - context (string) one of admin|cashier|registrar (used when actor missing)
 *
 * @param mysqli $conn
 * @param array  $entry
 */
function transaction_log_record(mysqli $conn, array $entry): void
{
    $action = trim((string) ($entry['action'] ?? ''));
    if ($action === '') {
        return;
    }

    $contextOverride = null;
    if (isset($entry['context']) && is_string($entry['context'])) {
        $contextOverride = strtolower($entry['context']);
    }

    $actorData = $entry['actor'] ?? [];
    if (!is_array($actorData)) {
        $actorData = [];
    }

    if (empty($actorData['username'])) {
        $actor = transaction_log_resolve_actor($conn, ['context' => $contextOverride]);
    } else {
        $resolvedContext = strtolower((string) ($contextOverride ?? $actorData['role'] ?? 'staff'));
        $actor = [
            'context'  => $resolvedContext,
            'user_id'  => $actorData['user_id'] ?? null,
            'username' => (string) $actorData['username'],
            'fullname' => $actorData['fullname'] ?? (string) $actorData['username'],
            'role'     => $resolvedContext,
        ];

        if ($actor['user_id'] === null && $actor['username'] !== '') {
            $lookup = transaction_log_lookup_user($conn, $actor['username']);
            if ($lookup) {
                $actor['user_id'] = $lookup['id'] ?? null;
                if (empty($actorData['fullname']) && !empty($lookup['fullname'])) {
                    $actor['fullname'] = $lookup['fullname'];
                }
                if (empty($actorData['role']) && !empty($lookup['role'])) {
                    $actor['role'] = $lookup['role'];
                }
            }
        }
    }

    $description = trim((string) ($entry['description'] ?? ''));
    $category    = isset($entry['category']) ? trim((string) $entry['category']) : null;
    $targetType  = isset($entry['target_type']) ? trim((string) $entry['target_type']) : null;
    $targetIdRaw = $entry['target_id'] ?? null;
    $metadata    = transaction_log_encode_metadata($entry['metadata'] ?? null);
    $ipAddress   = isset($entry['ip_address']) ? (string) $entry['ip_address'] : transaction_log_detect_ip();

    $targetId = null;
    if (is_scalar($targetIdRaw)) {
        $targetId = trim((string) $targetIdRaw);
        if ($targetId === '') {
            $targetId = null;
        } elseif (strlen($targetId) > 100) {
            $targetId = substr($targetId, 0, 100);
        }
    }

    $actorUsername = substr($actor['username'] ?? 'unknown', 0, 100);
    $actorFullname = substr($actor['fullname'] ?? $actorUsername, 0, 150);
    $actorRoleCanonical = strtolower(trim((string) ($actor['context'] ?? $actor['role'] ?? 'staff')));
    if ($actorRoleCanonical === '') {
        $actorRoleCanonical = 'staff';
    }
    $actorRole     = substr($actorRoleCanonical, 0, 50);
    $category      = $category !== null && $category !== '' ? substr($category, 0, 50) : null;
    $targetType    = $targetType !== null && $targetType !== '' ? substr($targetType, 0, 50) : null;
    $description   = $description !== '' ? substr($description, 0, 1000) : null;
    $ipAddress     = $ipAddress !== null ? substr($ipAddress, 0, 45) : null;

    $sql = 'INSERT INTO transaction_logs
        (actor_user_id, actor_username, actor_fullname, actor_role, category, action, target_type, target_id, description, metadata, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('[transaction-log] prepare failed: ' . $conn->error);
        return;
    }

    $actorUserId = $actor['user_id'] ?? null;

    $stmt->bind_param(
        'issssssssss',
        $actorUserId,
        $actorUsername,
        $actorFullname,
        $actorRole,
        $category,
        $action,
        $targetType,
        $targetId,
        $description,
        $metadata,
        $ipAddress
    );

    if (!$stmt->execute()) {
        error_log('[transaction-log] insert failed: ' . $stmt->error);
    }
    $stmt->close();
}
