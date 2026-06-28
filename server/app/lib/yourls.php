<?php
declare(strict_types=1);

function yourls_call(array $cfg, array $params): ?array {
    $base  = $cfg['YOURLS_API_URL']         ?? '';
    $token = $cfg['YOURLS_SIGNATURE_TOKEN'] ?? '';

    if (!$base || !$token) {
        return null;
    }

    $timestamp = time();
    $signature = md5($timestamp . $token);

    $params['timestamp'] = $timestamp;
    $params['signature'] = $signature;
    $params['format']    = 'json';

    $ch = curl_init($base);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    if ($resp === false) {
        return null;
    }
    $decoded = json_decode($resp, true);
    return is_array($decoded) ? $decoded : null;
}

function yourls_shorten(array $cfg, string $longUrl): ?string {
    $r = yourls_call($cfg, [
        'action'  => 'shorturl',
        'url'     => $longUrl,
        'expiry'  => 'clock',
        'age'     => (int)($cfg['YOURLS_EXPIRY_DAYS'] ?? 7),
        'ageMod'  => 'day',
    ]);
    return $r['shorturl'] ?? null;
}

function yourls_renew_expiry(array $cfg, string $shortUrl): bool {
    $r = yourls_call($cfg, [
        'action'   => 'expiry',
        'shorturl' => $shortUrl,
        'expiry'   => 'clock',
        'age'      => (int)($cfg['YOURLS_EXPIRY_DAYS'] ?? 7),
        'ageMod'   => 'day',
    ]);
    return $r !== null;
}

function yourls_expire_now(array $cfg, string $shortUrl): void {
    yourls_call($cfg, [
        'action'   => 'expiry',
        'shorturl' => $shortUrl,
        'expiry'   => 'clock',
        'age'      => 1,
        'ageMod'   => 'min',
    ]);
}
