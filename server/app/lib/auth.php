<?php
declare(strict_types=1);

const ADMIN_COOKIE     = 'oc_admin';
const ADMIN_COOKIE_TTL = 12 * 3600;

const LOGIN_MAX_ATTEMPTS = 10;
const LOGIN_WINDOW       = 15 * 60;

function json_error(int $code, string $msg, array $extraHeaders = []): never {
    http_response_code($code);
    header('Content-Type: application/json');
    foreach ($extraHeaders as $h) {
        header($h);
    }
    echo json_encode(['error' => $msg]);
    exit;
}

/**
 * Some SAPIs (Apache + CGI/FPM) drop the Authorization header unless configured;
 * try the usual fallbacks before giving up.
 */
function auth_header(): string {
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $k) {
        if (!empty($_SERVER[$k])) {
            return (string)$_SERVER[$k];
        }
    }
    if (function_exists('apache_request_headers')) {
        $h = apache_request_headers();
        foreach ($h as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                return (string)$value;
            }
        }
    }
    return '';
}

function auth_bearer_token(): ?string {
    $header = auth_header();
    if (str_starts_with($header, 'Bearer ')) {
        return trim(substr($header, 7));
    }
    return null;
}

/** Password from HTTP Basic auth (username ignored), used by the /raw endpoint. */
function auth_basic_password(): ?string {
    $header = auth_header();
    if (!str_starts_with($header, 'Basic ')) {
        return null;
    }
    $decoded = base64_decode(trim(substr($header, 6)), true);
    if ($decoded === false || !str_contains($decoded, ':')) {
        return null;
    }
    return explode(':', $decoded, 2)[1];
}

function auth_token_valid(array $cfg, ?string $token): bool {
    return $token !== null && $token !== '' && hash_equals($cfg['SHARE_TOKEN'], $token);
}

/** API guard: Bearer token only. */
function auth_check(array $cfg): void {
    if (!auth_token_valid($cfg, auth_bearer_token())) {
        json_error(401, 'unauthorized');
    }
}

/**
 * API guard for requests issued by the admin web UI: accept either the Bearer
 * token, or a valid admin cookie plus a custom header. The custom header cannot
 * be sent cross-origin without a CORS preflight (which we never answer), so it
 * doubles as CSRF protection on top of the SameSite=Strict cookie.
 */
function auth_check_api_or_admin(array $cfg): void {
    if (auth_token_valid($cfg, auth_bearer_token())) {
        return;
    }
    $xrw = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    if ($xrw === 'opencode-share' && auth_admin_cookie_valid($cfg)) {
        return;
    }
    json_error(401, 'unauthorized');
}

// ---- Admin session (cookie) ----

function auth_admin_cookie_valid(array $cfg): bool {
    return security_verify($cfg, 'admin', 'admin', $_COOKIE[ADMIN_COOKIE] ?? null);
}

function auth_admin_login(array $cfg): void {
    $value = security_sign($cfg, 'admin', 'admin', ADMIN_COOKIE_TTL);
    security_set_cookie($cfg, ADMIN_COOKIE, $value, ADMIN_COOKIE_TTL, '/', 'Strict');
}

function auth_admin_logout(array $cfg): void {
    security_clear_cookie($cfg, ADMIN_COOKIE, '/', 'Strict');
}

// ---- Share password protection ----

function share_cookie_name(string $slug): string {
    return 'oc_share_' . $slug;
}

function share_cookie_path(string $slug): string {
    return '/s/' . $slug;
}

/** Viewer has already unlocked this share in this browser (cookie bound to slug + current hash). */
function share_cookie_valid(array $cfg, array $row): bool {
    $name = share_cookie_name($row['slug']);
    return security_verify($cfg, 'share', $row['slug'] . '|' . $row['password_hash'], $_COOKIE[$name] ?? null);
}

function share_cookie_set(array $cfg, array $row): void {
    $ttl   = (int)($cfg['PASSWORD_COOKIE_TTL'] ?? 12 * 3600);
    $value = security_sign($cfg, 'share', $row['slug'] . '|' . $row['password_hash'], $ttl);
    security_set_cookie($cfg, share_cookie_name($row['slug']), $value, $ttl, share_cookie_path($row['slug']), 'Lax');
}

/**
 * Verify a submitted password against the stored hash with brute-force throttling.
 * Returns 'ok', 'wrong' or 'blocked'.
 */
function share_password_attempt(array $cfg, PDO $pdo, array $row, string $password): string {
    $key = 'pw:' . $row['slug'] . ':' . security_client_ip($cfg);
    $max = (int)($cfg['PASSWORD_MAX_ATTEMPTS'] ?? 10);
    $win = (int)($cfg['PASSWORD_ATTEMPT_WINDOW'] ?? 15 * 60);

    if (rate_limit_blocked($pdo, $key, $max, $win)) {
        return 'blocked';
    }
    if (password_verify($password, (string)$row['password_hash'])) {
        rate_limit_clear($pdo, $key);
        return 'ok';
    }
    rate_limit_hit($pdo, $key, $win);
    return 'wrong';
}

/**
 * Validate a password chosen by the uploader. Returns an error message or null.
 * bcrypt silently truncates input at 72 bytes, so longer passwords are rejected
 * rather than giving a false sense of security.
 */
function share_password_validate(mixed $password): ?string {
    if (!is_string($password)) {
        return 'password must be a string';
    }
    $len = strlen($password);
    if ($len < 4) {
        return 'password must be at least 4 characters';
    }
    if ($len > 72) {
        return 'password must be at most 72 bytes';
    }
    if (preg_match('/[\x00-\x1F\x7F]/', $password)) {
        return 'password must not contain control characters';
    }
    return null;
}

function share_password_hash(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT);
}
