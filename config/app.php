<?php
/**
 * Application base path helpers so the project works whether it lives in the
 * document root (public_html) or inside a subdirectory (e.g., /Enrollment).
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__ . '/..') ?: __DIR__ . '/..');
}

if (!defined('APP_BASE_PATH')) {
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $appRoot      = APP_ROOT;

    $documentRoot = str_replace('\\', '/', rtrim((string) $documentRoot, '/'));
    $appRoot = str_replace('\\', '/', rtrim((string) $appRoot, '/'));

    $basePath = '/';
    if ($documentRoot !== '' && strpos($appRoot . '/', $documentRoot . '/') === 0) {
        $relative = trim(substr($appRoot, strlen($documentRoot)), '/');
        // Treat deployments dropped directly in public_html as root-level installs.
        if ($relative === '' || $relative === 'public_html') {
            $basePath = '/';
        } else {
            $basePath = '/' . $relative . '/';
        }
    } else {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
        $scriptName = str_replace('\\', '/', (string) $scriptName);
        if ($scriptName !== '') {
            $guessed = rtrim(dirname($scriptName), '/');
            $basePath = ($guessed === '' || $guessed === '.') ? '/' : $guessed . '/';
        }
    }

    define('APP_BASE_PATH', $basePath);
}

if (!defined('APP_BASE_URL')) {
    $host   = $_SERVER['HTTP_HOST'] ?? '';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';

    if ($host !== '') {
        $normalizedPath = rtrim(APP_BASE_PATH, '/');
        $urlPath = $normalizedPath === '' ? '' : $normalizedPath;
        define('APP_BASE_URL', $scheme . $host . $urlPath . '/');
    } else {
        // Fallback when running from CLI
        define('APP_BASE_URL', APP_BASE_PATH);
    }
}
