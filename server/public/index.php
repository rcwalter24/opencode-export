<?php
declare(strict_types=1);

define('BASE', dirname(__DIR__) . '/app');

require BASE . '/lib/security.php';
security_install_error_handler();

// Load config
$cfgFile = BASE . '/config.php';
if (!file_exists($cfgFile)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'config.php not found — copy config.example.php']);
    exit;
}
$cfg = require $cfgFile;

// Refuse to serve with a missing / placeholder / weak secret.
$tok = (string)($cfg['SHARE_TOKEN'] ?? '');
if (strlen($tok) < 32 || str_starts_with($tok, 'change-me')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'SHARE_TOKEN is not configured — set a random secret of at least 32 characters (openssl rand -hex 32)']);
    exit;
}
unset($tok);

require BASE . '/lib/db.php';
require BASE . '/lib/auth.php';
require BASE . '/lib/yourls.php';
require BASE . '/lib/render.php';
require BASE . '/lib/theme.php';

security_headers();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
$uri    = '/' . ltrim($uri, '/');
$base   = rtrim((string)$cfg['PUBLIC_BASE_URL'], '/');
$nonce  = security_nonce();

const SLUG_RE = '[A-Za-z0-9]{1,32}';

function html(int $code, string $template, array $vars = []): never {
    global $cfg, $nonce, $base;
    http_response_code($code);
    header('Content-Type: text/html; charset=UTF-8');
    extract($vars, EXTR_SKIP);
    include BASE . '/templates/' . $template . '.php';
    exit;
}

function json(int $code, mixed $data): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function redirect(string $to): never {
    http_response_code(303);
    header('Location: ' . $to);
    exit;
}

function share_lookup(PDO $pdo, string $slug): ?array {
    $stmt = $pdo->prepare("SELECT * FROM share WHERE slug = ?");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function share_to_api(array $r, string $base): array {
    return [
        'slug'       => $r['slug'],
        'session_id' => $r['session_id'],
        'title'      => $r['title'],
        'url'        => $base . '/s/' . $r['slug'],
        'short_url'  => $r['short_url'] ?: null,
        'protected'  => !empty($r['password_hash']),
        'created_at' => (int)$r['created_at'],
        'updated_at' => (int)$r['updated_at'],
    ];
}

/**
 * Validate the uploaded payload shape strictly enough that the renderer can
 * never hit a TypeError on attacker-controlled (or just buggy) input.
 * Returns an error message or null.
 */
function payload_validate(array $data): ?string {
    if (!isset($data['session_id']) || !is_string($data['session_id']) || trim($data['session_id']) === '') {
        return 'session_id is required';
    }
    if (strlen($data['session_id']) > 200) {
        return 'session_id too long';
    }
    if (isset($data['title']) && !is_string($data['title'])) {
        return 'title must be a string';
    }
    foreach (['agent', 'directory'] as $k) {
        if (isset($data[$k]) && !is_string($data[$k])) {
            return "$k must be a string";
        }
    }
    if (isset($data['model']) && !is_string($data['model']) && !is_array($data['model'])) {
        return 'model must be a string or object';
    }
    if (isset($data['tokens']) && !is_array($data['tokens'])) {
        return 'tokens must be an object';
    }
    if (isset($data['messages'])) {
        if (!is_array($data['messages']) || !array_is_list($data['messages'])) {
            return 'messages must be an array';
        }
        foreach ($data['messages'] as $i => $m) {
            if (!is_array($m)) {
                return "messages[$i] must be an object";
            }
            if (isset($m['role']) && !is_string($m['role'])) {
                return "messages[$i].role must be a string";
            }
            if (isset($m['parts'])) {
                if (!is_array($m['parts']) || !array_is_list($m['parts'])) {
                    return "messages[$i].parts must be an array";
                }
                foreach ($m['parts'] as $j => $p) {
                    if (!is_array($p)) {
                        return "messages[$i].parts[$j] must be an object";
                    }
                    if (isset($p['type']) && !is_string($p['type'])) {
                        return "messages[$i].parts[$j].type must be a string";
                    }
                }
            }
        }
    }
    return null;
}

// ---- POST /api/upload ----
if ($method === 'POST' && $uri === '/api/upload') {
    auth_check($cfg);

    $maxBytes = (int)($cfg['MAX_UPLOAD_BYTES'] ?? 32 * 1024 * 1024);
    $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if ($raw === false || strlen($raw) > $maxBytes) {
        json_error(413, 'payload too large (max ' . intdiv($maxBytes, 1024 * 1024) . ' MB)');
    }
    $data = json_decode($raw, true, 128);
    unset($raw);

    if (!is_array($data)) {
        json_error(400, 'invalid JSON');
    }
    if (($err = payload_validate($data)) !== null) {
        json_error(400, $err);
    }

    $sessionId = trim($data['session_id']);
    $title     = trim($data['title'] ?? '');
    if ($title === '') {
        $title = 'Untitled Session';
    }
    $title = mb_substr($title, 0, 300);
    $data['title'] = $title;

    // Password handling (control field, never persisted inside the payload):
    //   "password": "secret"  -> set / replace
    //   "password": null      -> remove protection
    //   absent                -> keep whatever is currently stored
    $passwordAction = 'keep';
    $passwordHash   = null;
    if (array_key_exists('password', $data)) {
        if ($data['password'] === null || $data['password'] === '') {
            $passwordAction = 'clear';
        } else {
            if (($err = share_password_validate($data['password'])) !== null) {
                json_error(400, $err);
            }
            $passwordAction = 'set';
            $passwordHash   = share_password_hash($data['password']);
        }
        unset($data['password']);
    }

    $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        json_error(400, 'payload is not valid UTF-8');
    }
    unset($data);

    $pdo = db_connect($cfg);
    $now = time();

    // Idempotent on session_id: re-uploading refreshes content and keeps the slug.
    $stmt = $pdo->prepare("SELECT slug, short_url, password_hash FROM share WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $slug     = $existing['slug'];
        $shortUrl = $existing['short_url'] ?: null;
        $hash     = match ($passwordAction) {
            'set'   => $passwordHash,
            'clear' => null,
            default => $existing['password_hash'],
        };
        $pdo->prepare("UPDATE share SET title=?, payload=?, password_hash=?, updated_at=? WHERE session_id=?")
            ->execute([$title, $payload, $hash, $now, $sessionId]);
    } else {
        $slug     = slug_generate($pdo);
        $shortUrl = null;
        $hash     = $passwordAction === 'set' ? $passwordHash : null;
        $pdo->prepare("INSERT INTO share (slug,session_id,title,short_url,payload,password_hash,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$slug, $sessionId, $title, null, $payload, $hash, $now, $now]);
    }

    $longUrl = $base . '/s/' . $slug;

    // YOURLS: create or renew (skipped if not configured)
    if ($shortUrl !== null) {
        yourls_renew_expiry($cfg, $shortUrl);
    } else {
        $shortUrl = yourls_shorten($cfg, $longUrl);
        if ($shortUrl !== null) {
            $pdo->prepare("UPDATE share SET short_url=? WHERE slug=?")->execute([$shortUrl, $slug]);
        }
    }

    $resp = [
        'url'        => $longUrl,
        'slug'       => $slug,
        'session_id' => $sessionId,
        'protected'  => $hash !== null,
    ];
    if ($shortUrl !== null) {
        $resp['short_url'] = $shortUrl;
    }
    json(200, $resp);
}

// ---- DELETE /api/share/:slug ----
if ($method === 'DELETE' && preg_match('#^/api/share/(' . SLUG_RE . ')$#', $uri, $m)) {
    auth_check_api_or_admin($cfg);

    $pdo = db_connect($cfg);
    $row = share_lookup($pdo, $m[1]);
    if (!$row) {
        json_error(404, 'not found');
    }
    if (!empty($row['short_url'])) {
        yourls_expire_now($cfg, $row['short_url']);
    }
    $pdo->prepare("DELETE FROM share WHERE slug = ?")->execute([$row['slug']]);

    http_response_code(204);
    exit;
}

// ---- PUT /api/share/:slug/password  (set / replace / remove the share password) ----
if ($method === 'PUT' && preg_match('#^/api/share/(' . SLUG_RE . ')/password$#', $uri, $m)) {
    auth_check_api_or_admin($cfg);

    $body = json_decode((string)file_get_contents('php://input', false, null, 0, 4096), true);
    if (!is_array($body) || !array_key_exists('password', $body)) {
        json_error(400, 'body must be {"password": "..."} or {"password": null}');
    }
    $password = $body['password'];
    $hash = null;
    if ($password !== null && $password !== '') {
        if (($err = share_password_validate($password)) !== null) {
            json_error(400, $err);
        }
        $hash = share_password_hash($password);
    }

    $pdo = db_connect($cfg);
    $row = share_lookup($pdo, $m[1]);
    if (!$row) {
        json_error(404, 'not found');
    }
    $pdo->prepare("UPDATE share SET password_hash = ? WHERE slug = ?")->execute([$hash, $row['slug']]);
    json(200, ['slug' => $row['slug'], 'protected' => $hash !== null]);
}

// ---- GET /s/:slug/raw  (raw JSON payload for machine consumption) ----
if (($method === 'GET' || $method === 'HEAD') && preg_match('#^/s/(' . SLUG_RE . ')/raw$#', $uri, $m)) {
    $pdo = db_connect($cfg);
    $row = share_lookup($pdo, $m[1]);
    if (!$row) {
        json_error(404, 'not found');
    }

    $protected = !empty($row['password_hash']);
    if ($protected) {
        // Accept: unlocked browser cookie, owner Bearer token, or HTTP Basic (curl -u :password).
        $ok = share_cookie_valid($cfg, $row) || auth_token_valid($cfg, auth_bearer_token());
        if (!$ok) {
            $pw = auth_basic_password();
            if ($pw !== null) {
                $result = share_password_attempt($cfg, $pdo, $row, $pw);
                if ($result === 'blocked') {
                    json_error(429, 'too many attempts, try again later', ['Retry-After: ' . (int)($cfg['PASSWORD_ATTEMPT_WINDOW'] ?? 900)]);
                }
                $ok = $result === 'ok';
            }
        }
        if (!$ok) {
            json_error(401, 'password required', ['WWW-Authenticate: Basic realm="opencode-share", charset="UTF-8"']);
        }
    }

    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: ' . ($protected ? 'private, no-store' : 'public, max-age=60'));
    if (!$protected) {
        // Public shares stay fetchable from browser-based tools; protected ones never leave the origin.
        header('Access-Control-Allow-Origin: *');
    }
    if ($method === 'GET') {
        echo $row['payload'];
    }
    exit;
}

// ---- POST /s/:slug/unlock  (password form submission) ----
if ($method === 'POST' && preg_match('#^/s/(' . SLUG_RE . ')/unlock$#', $uri, $m)) {
    $pdo = db_connect($cfg);
    $row = share_lookup($pdo, $m[1]);
    if (!$row) {
        html(404, '404');
    }
    $target = '/s/' . $row['slug'];
    $page   = (int)($_POST['page'] ?? 1);
    if ($page > 1) {
        $target .= '?page=' . $page;
    }
    if (empty($row['password_hash'])) {
        redirect($target);
    }

    $password = $_POST['password'] ?? '';
    $result   = is_string($password) && $password !== ''
        ? share_password_attempt($cfg, $pdo, $row, $password)
        : 'wrong';

    if ($result === 'ok') {
        share_cookie_set($cfg, $row);
        redirect($target);
    }
    html($result === 'blocked' ? 429 : 401, 'unlock', [
        'row'   => $row,
        'page'  => $page,
        'error' => $result === 'blocked'
            ? 'Too many attempts. Please wait a few minutes and try again.'
            : 'Wrong password.',
    ]);
}

// ---- GET /s/:slug ----
if (($method === 'GET' || $method === 'HEAD') && preg_match('#^/s/(' . SLUG_RE . ')$#', $uri, $m)) {
    $pdo = db_connect($cfg);
    $row = share_lookup($pdo, $m[1]);
    if (!$row) {
        html(404, '404');
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    if (!empty($row['password_hash']) && !share_cookie_valid($cfg, $row) && !auth_token_valid($cfg, auth_bearer_token())) {
        header('Cache-Control: private, no-store');
        html(401, 'unlock', ['row' => $row, 'page' => $page, 'error' => null]);
    }

    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: ' . (!empty($row['password_hash']) ? 'private, no-store' : 'public, max-age=60'));
    echo render_session($cfg, $row, $page);
    exit;
}

// ---- DELETE /api/sessions  (purge all) ----
if ($method === 'DELETE' && $uri === '/api/sessions') {
    auth_check($cfg);

    $pdo  = db_connect($cfg);
    $rows = $pdo->query("SELECT slug, short_url FROM share")->fetchAll();
    foreach ($rows as $r) {
        if (!empty($r['short_url'])) {
            yourls_expire_now($cfg, $r['short_url']);
        }
    }
    $pdo->exec("DELETE FROM share");
    json(200, ['deleted' => count($rows)]);
}

// ---- GET /api/sessions  (Bearer auth, returns JSON list) ----
if ($method === 'GET' && $uri === '/api/sessions') {
    auth_check($cfg);

    $pdo  = db_connect($cfg);
    $rows = $pdo->query(
        "SELECT slug, session_id, title, short_url, password_hash, created_at, updated_at
         FROM share ORDER BY updated_at DESC"
    )->fetchAll();
    json(200, array_map(fn($r) => share_to_api($r, $base), $rows));
}

// ---- POST /login, POST /logout  (admin web UI) ----
if ($method === 'POST' && $uri === '/login') {
    $pdo = db_connect($cfg);
    $key = 'login:' . security_client_ip($cfg);
    if (rate_limit_blocked($pdo, $key, LOGIN_MAX_ATTEMPTS, LOGIN_WINDOW)) {
        html(429, '401', ['error' => 'Too many attempts. Please wait a few minutes and try again.']);
    }
    $token = $_POST['token'] ?? '';
    if (is_string($token) && auth_token_valid($cfg, trim($token))) {
        rate_limit_clear($pdo, $key);
        auth_admin_login($cfg);
        redirect('/');
    }
    rate_limit_hit($pdo, $key, LOGIN_WINDOW);
    html(401, '401', ['error' => 'Invalid token.']);
}
if ($method === 'POST' && $uri === '/logout') {
    auth_admin_logout($cfg);
    redirect('/');
}

// ---- GET /  (admin list page, cookie session) ----
if ($method === 'GET' && $uri === '/') {
    // Legacy ?token= links: exchange for a cookie and strip the secret from the URL.
    if (isset($_GET['token'])) {
        $pdo = db_connect($cfg);
        $key = 'login:' . security_client_ip($cfg);
        if (!rate_limit_blocked($pdo, $key, LOGIN_MAX_ATTEMPTS, LOGIN_WINDOW)
            && is_string($_GET['token']) && auth_token_valid($cfg, $_GET['token'])) {
            auth_admin_login($cfg);
        } else {
            rate_limit_hit($pdo, $key, LOGIN_WINDOW);
        }
        redirect('/');
    }

    header('Cache-Control: private, no-store');
    if (!auth_admin_cookie_valid($cfg)) {
        html(401, '401', ['error' => null]);
    }

    $pdo      = db_connect($cfg);
    $per_page = 25;
    $total    = (int)$pdo->query("SELECT COUNT(*) FROM share")->fetchColumn();
    $pages    = max(1, (int)ceil($total / $per_page));
    $page     = max(1, min($pages, (int)($_GET['page'] ?? 1)));
    $offset   = ($page - 1) * $per_page;

    $stmt = $pdo->prepare(
        "SELECT slug, session_id, title, short_url, password_hash, created_at, updated_at
         FROM share ORDER BY updated_at DESC LIMIT ? OFFSET ?"
    );
    $stmt->execute([$per_page, $offset]);
    $rows = $stmt->fetchAll();

    html(200, 'list', compact('rows', 'total', 'pages', 'page', 'base'));
}

// ---- Fallback ----
if (str_starts_with($uri, '/api/')) {
    json_error(404, 'not found');
}
html(404, '404');
