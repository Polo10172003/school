<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Load the Google Drive integration configuration array.
 *
 * @return array<string, mixed>
 */
function google_drive_config(): array
{
    $configPath = __DIR__ . '/../config/google_drive.php';

    if (!file_exists($configPath)) {
        throw new RuntimeException(
            'Missing Google Drive config. Copy config/google_drive.example.php to config/google_drive.php and fill in your credentials.'
        );
    }

    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('Invalid Google Drive configuration file.');
    }

    return $config;
}

/**
 * Build an authenticated Google Drive service using either OAuth credentials or a service account.
 */
function google_drive_service(): Google_Service_Drive
{
    $config = google_drive_config();

    if (empty($config['folder_id'])) {
        throw new RuntimeException('Google Drive folder_id is not configured.');
    }

    $client = new Google_Client();
    $client->setApplicationName('Registrar Dropbox Sync');
    $client->setScopes([Google_Service_Drive::DRIVE_READONLY]);

    $mode = $config['mode'] ?? 'oauth';

    if ($mode === 'service_account') {
        $jsonPath = $config['service_account']['json_key_path'] ?? null;
        if (!$jsonPath || !file_exists($jsonPath)) {
            throw new RuntimeException('Service account JSON key not found. Update config/google_drive.php.');
        }

        $client->setAuthConfig($jsonPath);
    } else {
        $oauth = $config['oauth'] ?? [];
        $clientId = $oauth['client_id'] ?? '';
        $clientSecret = $oauth['client_secret'] ?? '';
        $refreshToken = $oauth['refresh_token'] ?? '';

        if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
            throw new RuntimeException('OAuth credentials (client_id, client_secret, refresh_token) must be configured.');
        }

        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setAccessType('offline');
        $client->setPrompt('none');

        $accessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
        if (isset($accessToken['error'])) {
            throw new RuntimeException('Unable to refresh Google Drive access token: ' . $accessToken['error']);
        }

        $client->setAccessToken($accessToken);
        // Ensure the refresh token stays attached for future refreshes.
        if (!$client->getRefreshToken()) {
            $client->setRefreshToken($refreshToken);
        }
    }

    return new Google_Service_Drive($client);
}
