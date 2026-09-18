<?php
declare(strict_types=1);

/**
 * Cross-cutting security helpers: hardened error handling, response headers,
 * signed cookies, client IP detection and a small SQLite-backed rate limiter.
 */

// ---- Error handling -------------------------------------------------------

function security_install_error_handler(): void {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    set_exception_handler(function (Throwable $e): void {
        error_log(sprintf('[opencode-share] %s: %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode(['error' => 'internal server error']);
        exit;
    });
}

// ---- Response headers -------------------------------------------------------

function security_nonce(): string {
    static $nonce = null;
    if ($nonce === null) {
        $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }
    return $nonce;
}

function security_headers(): void {
    $nonce = security_nonce();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header(
        "Content-Security-Policy: default-src 'none'; "
        . "script-src 'nonce-{$nonce}' https://cdnjs.cloudflare.com; "
        . "style-src 'nonce-{$nonce}' https://cdnjs.cloudflare.com; "
        . "img-src 'self' data: https:; "
        . "connect-src 'self'; "
        . "form-action 'self'; "
        . "base-uri 'none'; "
        . "frame-ancestors 'none'"
    );
}

function security_is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (($_SERVER['SERVER_PORT'] ?? null) == 443) {
        return true;
    }
    return strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function security_cookie_secure(array $cfg): bool {
    $forced = $cfg['COOKIE_SECURE'] ?? null;
    if (is_bool($forced)) {
        return $forced;
    }
    return security_is_https();
}

// ---- Signed cookies -------------------------------------------------------------

/** Derive a per-purpose signing key from the shared secret (the secret itself is never used raw). */
function security_signing_key(array $cfg, string $purpose): string {
    return hash_hmac('sha256', 'opencode-share:' . $purpose, $cfg['SHARE_TOKEN'], true);
}

/**
 * Build a tamper-proof cookie value: "<expiry>.<hmac>". The `$binding` string is
 * folded into the signature so the cookie is only valid for that context (slug,
 * password hash, ...) and is automatically invalidated when the context changes.
 */
function security_sign(array $cfg, string $purpose, string $binding, int $ttl): string {
    $exp = time() + $ttl;
    $mac = hash_hmac('sha256', $binding . '|' . $exp, security_signing_key($cfg, $purpose));
    return $exp . '.' . $mac;
}

function security_verify(array $cfg, string $purpose, string $binding, ?string $value): bool {
    if ($value === null || $value === '' || !str_contains($value, '.')) {
        return false;
    }
    [$exp, $mac] = explode('.', $value, 2);
    if (!ctype_digit($exp) || (int)$exp < time()) {
        return false;
    }
    $expected = hash_hmac('sha256', $binding . '|' . $exp, security_signing_key($cfg, $purpose));
    return hash_equals($expected, $mac);
}

function security_set_cookie(array $cfg, string $name, string $value, int $ttl, string $path, string $sameSite = 'Lax'): void {
    setcookie($name, $value, [
        'expires'  => $ttl > 0 ? time() + $ttl : 1,
        'path'     => $path,
        'secure'   => security_cookie_secure($cfg),
        'httponly' => true,
        'samesite' => $sameSite,
    ]);
}

function security_clear_cookie(array $cfg, string $name, string $path, string $sameSite = 'Lax'): void {
    security_set_cookie($cfg, $name, '', 0, $path, $sameSite);
}

// ---- Client IP ------------------------------------------------------------------

/** Cloudflare's published edge ranges (https://www.cloudflare.com/ips/). */
const CLOUDFLARE_RANGES = [
    '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
    '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
    '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
    '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
    '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
    '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
];

/** True when $ip lies inside $cidr (plain IPs are accepted as /32 or /128). */
function security_ip_in_cidr(string $ip, string $cidr): bool {
    [$net, $bits] = array_pad(explode('/', $cidr, 2), 2, null);
    $ipBin  = @inet_pton($ip);
    $netBin = @inet_pton($net);
    if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) {
        return false;
    }
    $max  = strlen($ipBin) * 8;
    $bits = $bits === null ? $max : (int)$bits;
    if ($bits < 0 || $bits > $max) {
        return false;
    }
    $bytes = intdiv($bits, 8);
    $rem   = $bits % 8;
    if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) {
        return false;
    }
    if ($rem === 0) {
        return true;
    }
    $mask = (0xFF << (8 - $rem)) & 0xFF;
    return (ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask);
}

function security_is_trusted_proxy(array $cfg, string $ip): bool {
    $list = $cfg['TRUSTED_PROXIES'] ?? [];
    if (!is_array($list)) {
        return false;
    }
    foreach ($list as $entry) {
        if ($entry === 'cloudflare') {
            foreach (CLOUDFLARE_RANGES as $r) {
                if (security_ip_in_cidr($ip, $r)) {
                    return true;
                }
            }
        } elseif (is_string($entry) && security_ip_in_cidr($ip, $entry)) {
            return true;
        }
    }
    return false;
}

/**
 * Best-effort client IP for rate limiting.
 *
 * REMOTE_ADDR is used as-is unless it belongs to a trusted proxy. In that case
 * CLIENT_IP_HEADER (e.g. CF-Connecting-IP) wins when set, otherwise
 * X-Forwarded-For is walked from the right, skipping trusted hops, so a client
 * cannot spoof its address by prepending values to the header.
 */
function security_client_ip(array $cfg): string {
    $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    if (!security_is_trusted_proxy($cfg, $remote)) {
        return $remote;
    }

    $header = (string)($cfg['CLIENT_IP_HEADER'] ?? '');
    if ($header !== '') {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        $val = trim((string)($_SERVER[$key] ?? ''));
        if ($val !== '' && filter_var($val, FILTER_VALIDATE_IP)) {
            return $val;
        }
    }

    $hops = array_map('trim', explode(',', (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')));
    for ($i = count($hops) - 1; $i >= 0; $i--) {
        if ($hops[$i] === '' || !filter_var($hops[$i], FILTER_VALIDATE_IP)) {
            continue;
        }
        if (!security_is_trusted_proxy($cfg, $hops[$i])) {
            return $hops[$i];
        }
    }
    return $remote;
}

// ---- Rate limiting --------------------------------------------------------------

/** Returns true when the caller is currently blocked for `$key`. */
function rate_limit_blocked(PDO $pdo, string $key, int $max, int $window): bool {
    $stmt = $pdo->prepare("SELECT attempts, reset_at FROM rate_limit WHERE key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }
    if ((int)$row['reset_at'] <= time()) {
        return false;
    }
    return (int)$row['attempts'] >= $max;
}

function rate_limit_hit(PDO $pdo, string $key, int $window): void {
    $now = time();
    // Opportunistic cleanup so the table never grows unbounded.
    if (random_int(1, 50) === 1) {
        $pdo->prepare("DELETE FROM rate_limit WHERE reset_at <= ?")->execute([$now]);
    }
    $pdo->prepare("
        INSERT INTO rate_limit (key, attempts, reset_at) VALUES (?, 1, ?)
        ON CONFLICT(key) DO UPDATE SET
            attempts = CASE WHEN reset_at <= excluded.reset_at - ? THEN 1 ELSE attempts + 1 END,
            reset_at = CASE WHEN reset_at <= excluded.reset_at - ? THEN excluded.reset_at ELSE reset_at END
    ")->execute([$key, $now + $window, $window, $window]);
}

function rate_limit_clear(PDO $pdo, string $key): void {
    $pdo->prepare("DELETE FROM rate_limit WHERE key = ?")->execute([$key]);
}
