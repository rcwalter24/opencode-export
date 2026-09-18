<?php
declare(strict_types=1);

function db_connect(array $cfg): PDO {
    $path = $cfg['DB_PATH'];
    $dir  = dirname($path);

    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        throw new RuntimeException("cannot create data directory: $dir");
    }
    $fresh = !file_exists($path);

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // SQLite honours the umask, which on shared hosts often means world-readable.
    if ($fresh) {
        @chmod($path, 0640);
    }

    $pdo->exec("PRAGMA journal_mode=WAL");
    $pdo->exec("PRAGMA busy_timeout=5000");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS share (
            slug          TEXT PRIMARY KEY,
            session_id    TEXT UNIQUE NOT NULL,
            title         TEXT NOT NULL,
            short_url     TEXT,
            payload       TEXT NOT NULL,
            password_hash TEXT,
            created_at    INTEGER NOT NULL,
            updated_at    INTEGER NOT NULL
        );
        CREATE INDEX IF NOT EXISTS idx_share_session ON share(session_id);
        CREATE TABLE IF NOT EXISTS share_history (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            slug       TEXT NOT NULL,
            title      TEXT NOT NULL,
            payload    TEXT NOT NULL,
            note       TEXT,
            created_at INTEGER NOT NULL
        );
        CREATE INDEX IF NOT EXISTS idx_history_slug ON share_history(slug);
        CREATE TABLE IF NOT EXISTS rate_limit (
            key      TEXT PRIMARY KEY,
            attempts INTEGER NOT NULL,
            reset_at INTEGER NOT NULL
        );
    ");

    db_migrate($pdo);

    return $pdo;
}

/** Add columns introduced after the initial release to databases created earlier. */
function db_migrate(PDO $pdo): void {
    $cols = array_column($pdo->query("PRAGMA table_info(share)")->fetchAll(), 'name');
    if (!in_array('password_hash', $cols, true)) {
        $pdo->exec("ALTER TABLE share ADD COLUMN password_hash TEXT");
    }
}

function slug_generate(PDO $pdo): string {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $exists = $pdo->prepare("SELECT 1 FROM share WHERE slug = ?");
    do {
        $slug = '';
        for ($i = 0; $i < 8; $i++) {
            $slug .= $chars[random_int(0, 61)];
        }
        $exists->execute([$slug]);
    } while ($exists->fetchColumn());
    return $slug;
}
