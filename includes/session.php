<?php
require_once __DIR__ . '/../config/app.php';

// Centralized session bootstrap to enforce secure defaults.
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');

    $isSecure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );

    $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookieParams);
    } else {
        session_set_cookie_params(
            $cookieParams['lifetime'],
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
        ini_set('session.cookie_samesite', $cookieParams['samesite']);
    }

    if (session_name() !== 'ESRSESSID') {
        session_name('ESRSESSID');
    }

    session_cache_limiter('');
    session_start();

    if (!isset($_SESSION['__session_initialized'])) {
        session_regenerate_id(true);
        $_SESSION['__session_initialized'] = time();
    } elseif (
        isset($_SESSION['__session_initialized']) &&
        (time() - (int) $_SESSION['__session_initialized']) > 1800
    ) {
        session_regenerate_id(true);
        $_SESSION['__session_initialized'] = time();
    }
}
