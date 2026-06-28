<?php
declare(strict_types=1);

define('BASE', dirname(__DIR__) . '/app');

// Load config
$cfgFile = BASE . '/config.php';
if (!file_exists($cfgFile)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'config.php not found — copy config.example.php']);
    exit;
}
$cfg = require $cfgFile;

require BASE . '/lib/db.php';
require BASE . '/lib/auth.php';
require BASE . '/lib/yourls.php';
require BASE . '/lib/render.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = '/' . ltrim($uri, '/');

// ---- POST /api/upload ----
if ($method === 'POST' && $uri === '/api/upload') {
    auth_check($cfg);

    $maxBytes = 32 * 1024 * 1024;
    $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if (strlen($raw) > $maxBytes) {
        http_response_code(413);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'payload too large (max 32 MB)']);
        exit;
    }
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'invalid JSON']);
        exit;
    }

    $sessionId = trim($data['session_id'] ?? '');
    $title     = trim($data['title']      ?? '');

    if ($sessionId === '') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'session_id is required']);
        exit;
    }
    if ($title === '') {
        $title = 'Untitled Session';
    }

    $pdo = db_connect($cfg);
    $now = time();

    // Check for existing record (idempotency on session_id)
    $stmt = $pdo->prepare("SELECT slug, short_url FROM share WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $slug     = $existing['slug'];
        $shortUrl = $existing['short_url'] ?: null;

        // Update content
        $upd = $pdo->prepare("UPDATE share SET title=?, payload=?, updated_at=? WHERE session_id=?");
        $upd->execute([$title, $raw, $now, $sessionId]);
    } else {
        $slug     = slug_generate($pdo);
        $shortUrl = null;

        $ins = $pdo->prepare("INSERT INTO share (slug,session_id,title,short_url,payload,created_at,updated_at) VALUES (?,?,?,?,?,?,?)");
        $ins->execute([$slug, $sessionId, $title, null, $raw, $now, $now]);
    }

    $longUrl = rtrim($cfg['PUBLIC_BASE_URL'], '/') . '/s/' . $slug;

    // YOURLS: create or renew (skipped if not configured)
    if ($shortUrl !== null) {
        yourls_renew_expiry($cfg, $shortUrl);
    } else {
        $shortUrl = yourls_shorten($cfg, $longUrl);
        if ($shortUrl !== null) {
            $upd2 = $pdo->prepare("UPDATE share SET short_url=? WHERE slug=?");
            $upd2->execute([$shortUrl, $slug]);
        }
    }

    $resp = [
        'url'        => $longUrl,
        'slug'       => $slug,
        'session_id' => $sessionId,
    ];
    if ($shortUrl !== null) {
        $resp['short_url'] = $shortUrl;
    }

    header('Content-Type: application/json');
    echo json_encode($resp);
    exit;
}

// ---- DELETE /api/share/:slug ----
if ($method === 'DELETE' && preg_match('#^/api/share/([A-Za-z0-9]{1,32})$#', $uri, $m)) {
    auth_check($cfg);

    $pdo  = db_connect($cfg);
    $slug = $m[1];

    $stmt = $pdo->prepare("SELECT short_url FROM share WHERE slug = ?");
    $stmt->execute([$slug]);
    $row  = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'not found']);
        exit;
    }

    if (!empty($row['short_url'])) {
        yourls_expire_now($cfg, $row['short_url']);
    }

    $del = $pdo->prepare("DELETE FROM share WHERE slug = ?");
    $del->execute([$slug]);

    http_response_code(204);
    exit;
}

// ---- GET /s/:slug/raw  (raw JSON payload for machine consumption) ----
if ($method === 'GET' && preg_match('#^/s/([A-Za-z0-9]{1,32})/raw$#', $uri, $m)) {
    $pdo  = db_connect($cfg);
    $slug = $m[1];

    $stmt = $pdo->prepare("SELECT payload FROM share WHERE slug = ?");
    $stmt->execute([$slug]);
    $row  = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'not found']);
        exit;
    }

    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    echo $row['payload'];
    exit;
}

// ---- GET /s/:slug ----
if ($method === 'GET' && preg_match('#^/s/([A-Za-z0-9]{1,32})$#', $uri, $m)) {
    $pdo  = db_connect($cfg);
    $slug = $m[1];

    $stmt = $pdo->prepare("SELECT * FROM share WHERE slug = ?");
    $stmt->execute([$slug]);
    $row  = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        header('Content-Type: text/html; charset=UTF-8');
        include BASE . '/templates/404.php';
        exit;
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    header('Content-Type: text/html; charset=UTF-8');
    echo render_session($row, $page);
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

    header('Content-Type: application/json');
    echo json_encode(['deleted' => count($rows)]);
    exit;
}

// ---- GET /api/sessions  (Bearer auth, returns JSON list) ----
if ($method === 'GET' && $uri === '/api/sessions') {
    auth_check($cfg);

    $pdo  = db_connect($cfg);
    $rows = $pdo->query(
        "SELECT slug, session_id, title, short_url, created_at, updated_at
         FROM share ORDER BY updated_at DESC"
    )->fetchAll();

    $base = rtrim($cfg['PUBLIC_BASE_URL'], '/');
    $list = array_map(fn($r) => [
        'slug'       => $r['slug'],
        'session_id' => $r['session_id'],
        'title'      => $r['title'],
        'url'        => $base . '/s/' . $r['slug'],
        'short_url'  => $r['short_url'] ?: null,
        'created_at' => (int)$r['created_at'],
        'updated_at' => (int)$r['updated_at'],
    ], $rows);

    header('Content-Type: application/json');
    echo json_encode($list);
    exit;
}

// ---- GET /  (list page — token via ?token= or Bearer) ----
if ($method === 'GET' && $uri === '/') {
    $token = $_GET['token'] ?? '';
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (str_starts_with($header, 'Bearer ')) {
        $token = substr($header, 7);
    }
    if (!hash_equals($cfg['SHARE_TOKEN'], $token)) {
        http_response_code(401);
        header('Content-Type: text/html; charset=UTF-8');
        include BASE . '/templates/401.php';
        exit;
    }

    $pdo      = db_connect($cfg);
    $per_page = 25;
    $total    = (int)$pdo->query("SELECT COUNT(*) FROM share")->fetchColumn();
    $pages    = max(1, (int)ceil($total / $per_page));
    $page     = max(1, min($pages, (int)($_GET['page'] ?? 1)));
    $offset   = ($page - 1) * $per_page;

    $stmt = $pdo->prepare(
        "SELECT slug, session_id, title, short_url, created_at, updated_at
         FROM share ORDER BY updated_at DESC LIMIT ? OFFSET ?"
    );
    $stmt->execute([$per_page, $offset]);
    $rows = $stmt->fetchAll();

    $base = rtrim($cfg['PUBLIC_BASE_URL'], '/');
    header('Content-Type: text/html; charset=UTF-8');
    include BASE . '/templates/list.php';
    exit;
}

// ---- Fallback ----
http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
include BASE . '/templates/404.php';
