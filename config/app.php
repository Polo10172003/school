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

    $basePath = '/';
    if ($documentRoot !== '' && strpos($appRoot, $documentRoot) === 0) {
        $relative = trim(str_replace('\\', '/', substr($appRoot, strlen($documentRoot))), '/');
        $basePath = $relative === '' ? '/' : '/' . $relative . '/';
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

