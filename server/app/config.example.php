<?php
return [
    // A strong random secret — used to authenticate upload/delete requests.
    // Generate one with: openssl rand -hex 32
    'SHARE_TOKEN'            => 'change-me-to-a-strong-random-token',

    // The public base URL of your server (no trailing slash).
    'PUBLIC_BASE_URL'        => 'https://share.example.com',

    // Path to the SQLite database file.
    'DB_PATH'                => __DIR__ . '/data/share.db',

    // ---- YOURLS short-link integration (optional) ----
    // Leave YOURLS_API_URL and YOURLS_SIGNATURE_TOKEN empty to disable short links.
    'YOURLS_API_URL'         => '',
    'YOURLS_SIGNATURE_TOKEN' => '',
    'YOURLS_EXPIRY_DAYS'     => 7,
];
