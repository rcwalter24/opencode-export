<?php
declare(strict_types=1);

function db_connect(array $cfg): PDO {
    $path = $cfg['DB_PATH'];
    $dir  = dirname($path);

    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("PRAGMA journal_mode=WAL");
    $pdo->exec("PRAGMA foreign_keys=ON");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS share (
            slug        TEXT PRIMARY KEY,
            session_id  TEXT UNIQUE NOT NULL,
            title       TEXT NOT NULL,
            short_url   TEXT,
            payload     TEXT NOT NULL,
            created_at  INTEGER NOT NULL,
            updated_at  INTEGER NOT NULL
        );
        CREATE INDEX IF NOT EXISTS idx_share_session ON share(session_id);
    ");

    return $pdo;
}

function slug_generate(PDO $pdo): string {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    do {
        $bytes = random_bytes(6);
        $slug  = '';
        $n     = strlen($bytes);
        for ($i = 0; $i < $n; $i++) {
            $slug .= $chars[ord($bytes[$i]) % 62];
        }
        $exists = $pdo->prepare("SELECT 1 FROM share WHERE slug = ?");
        $exists->execute([$slug]);
    } while ($exists->fetchColumn());
    return $slug;
}
