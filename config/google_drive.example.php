<?php

declare(strict_types=1);

/**
 * Copy this file to config/google_drive.php and fill in the values.
 * Never commit real credentials.
 */
return [
    'mode' => 'oauth', // or 'service_account'

    // Shared Google Drive folder ID where advisers upload the Excel workbooks.
    'folder_id' => '1Ly2x7syMlocajyHf2YhF6Demy96bRGLU',

    // OAuth client credentials + refresh token.
    'oauth' => [
        'client_id' => 'CLIENT_ID_HERE',
        'client_secret' => 'CLIENT_SECRET_HERE',
        'refresh_token' => 'REFRESH_TOKEN_HERE', // See docs for generating one.
    ],

    // Service account JSON key path. Only used when mode === 'service_account'.
    'service_account' => [
        'json_key_path' => __DIR__ . '/google-service-account.json',
    ],
];

