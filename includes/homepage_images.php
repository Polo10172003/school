<?php

declare(strict_types=1);

function homepage_images_config_path(): string
{
    return __DIR__ . '/../config/homepage_images.json';
}

function homepage_images_defaults(): array
{
    return [
        'cards' => [
            'programs' => 'EsrBanner.jpg',
            'admissions' => 'Esrlogo.png',
            'campus' => 'EsrBanner.jpg',
        ],
        'events' => [
            'slide1' => 'EsrBanner.jpg',
            'slide2' => 'Esrlogo.png',
            'slide3' => 'EsrBanner.jpg',
        ],
        'achievements' => [
            'slide1' => 'EsrBanner.jpg',
            'slide2' => 'Esrlogo.png',
            'slide3' => 'EsrBanner.jpg',
        ],
        'primary_secondary' => [
            'slide1' => 'EsrBanner.jpg',
            'slide2' => 'Esrlogo.png',
            'slide3' => 'EsrBanner.jpg',
        ],
        'junior_high' => [
            'slide1' => 'EsrBanner.jpg',
            'slide2' => 'Esrlogo.png',
            'slide3' => 'EsrBanner.jpg',
        ],
        'senior_high' => [
            'slide1' => 'EsrBanner.jpg',
            'slide2' => 'Esrlogo.png',
            'slide3' => 'EsrBanner.jpg',
        ],
        'paprisa' => [
            'slide1' => 'EsrBanner.jpg',
            'slide2' => 'Esrlogo.png',
            'slide3' => 'EsrBanner.jpg',
        ],
        'board' => [
            'slide1' => 'Esrlogo.png',
            'slide2' => 'EsrBanner.jpg',
            'slide3' => 'Esrlogo.png',
        ],
        'laudes' => [
            'slide1' => 'EsrBanner.jpg',
            'slide2' => 'Esrlogo.png',
            'slide3' => 'EsrBanner.jpg',
        ],
    ];
}

function homepage_images_load(): array
{
    $defaults = homepage_images_defaults();
    $path = homepage_images_config_path();
    if (!is_file($path)) {
        return $defaults;
    }

    $json = file_get_contents($path);
    if ($json === false) {
        return $defaults;
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return $defaults;
    }

    return array_replace_recursive($defaults, $data);
}

function homepage_image_url(?string $path): string
{
    $path = (string) $path;
    if ($path === '') {
        return '';
    }

    if (preg_match('~^(?:https?:)?//~i', $path)) {
        return $path;
    }

    $normalized = ltrim(str_replace('\\', '/', $path), '/');

    if (!defined('APP_BASE_PATH')) {
        require_once __DIR__ . '/../config/app.php';
    }

    $base = APP_BASE_PATH ?? '/';
    if ($base === '') {
        $base = '/';
    }
    if ($base !== '/' && substr($base, -1) !== '/') {
        $base .= '/';
    }

    return $base === '/' ? '/' . $normalized : $base . $normalized;
}

function homepage_images_save(array $images): bool
{
    $path = homepage_images_config_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }
    }

    $json = json_encode($images, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    return file_put_contents($path, $json) !== false;
}

function homepage_images_set(array &$images, array $pathSegments, string $value): void
{
    $ref =& $images;
    foreach ($pathSegments as $segment) {
        if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
            $ref[$segment] = [];
        }
        $ref =& $ref[$segment];
    }
    $ref = $value;
}

function homepage_images_get(array $images, array $pathSegments, string $default = ''): string
{
    $ref = $images;
    foreach ($pathSegments as $segment) {
        if (!isset($ref[$segment])) {
            return $default;
        }
        $ref = $ref[$segment];
    }
    return is_string($ref) ? $ref : $default;
}
