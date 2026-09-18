<?php
return [
    // A strong random secret — used to authenticate upload/delete requests and
    // to sign login cookies. Must be at least 32 characters; the server refuses
    // to start with the placeholder value.
    // Generate one with: openssl rand -hex 32
    'SHARE_TOKEN'            => 'change-me-to-a-strong-random-token',

    // The public base URL of your server (no trailing slash).
    'PUBLIC_BASE_URL'        => 'https://share.example.com',

    // Path to the SQLite database file.
    'DB_PATH'                => __DIR__ . '/data/share.db',

    // Maximum accepted upload size in bytes (also raise post_max_size in php.ini
    // and client_max_body_size in nginx if you change this).
    'MAX_UPLOAD_BYTES'       => 32 * 1024 * 1024,

    // ---- Password-protected shares ----
    // How long an unlocked share stays unlocked in a browser (seconds).
    'PASSWORD_COOKIE_TTL'    => 12 * 3600,
    // Brute-force throttle: failed attempts allowed per IP+share inside the window.
    'PASSWORD_MAX_ATTEMPTS'  => 10,
    'PASSWORD_ATTEMPT_WINDOW'=> 15 * 60,

    // ---- Cookies / proxies ----
    // null = auto-detect HTTPS (also honours X-Forwarded-Proto); force with true/false.
    'COOKIE_SECURE'          => null,
    // Proxies in front of PHP, as IPs / CIDRs, or the keyword 'cloudflare' for
    // Cloudflare's edge ranges. Only requests arriving from these addresses may
    // carry a forwarded client IP (used for rate limiting). Leave empty when PHP
    // sees the real client address directly.
    'TRUSTED_PROXIES'        => [],
    // Header holding the real client IP when the request comes from a trusted
    // proxy, e.g. 'CF-Connecting-IP' behind Cloudflare. Empty = walk X-Forwarded-For.
    'CLIENT_IP_HEADER'       => '',

    // ---- YOURLS short-link integration (optional) ----
    // Leave YOURLS_API_URL and YOURLS_SIGNATURE_TOKEN empty to disable short links.
    'YOURLS_API_URL'         => '',
    'YOURLS_SIGNATURE_TOKEN' => '',
    'YOURLS_EXPIRY_DAYS'     => 7,
];
