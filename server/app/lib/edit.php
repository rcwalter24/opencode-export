<?php
declare(strict_types=1);

/**
 * Server-side editing of a stored session (redaction): find & replace across
 * every string in the payload, deleting messages / parts, editing a text part,
 * duplicating a share. Every mutation first stores a snapshot in share_history
 * so it can be undone.
 */

const HISTORY_KEEP = 5;

/** Keys whose values are identifiers, never content — left untouched by replace. */
const REPLACE_SKIP_KEYS = ['id', 'session_id', 'message_id', 'parentID', 'type', 'role', 'time_created'];

function edit_decode(array $row): array {
    $data = json_decode($row['payload'], true);
    if (!is_array($data)) {
        throw new RuntimeException('stored payload is not valid JSON');
    }
    if (!is_array($data['messages'] ?? null)) {
        $data['messages'] = [];
    }
    return $data;
}

function edit_snapshot(PDO $pdo, array $row, string $note): void {
    $pdo->prepare("INSERT INTO share_history (slug, title, payload, note, created_at) VALUES (?,?,?,?,?)")
        ->execute([$row['slug'], $row['title'], $row['payload'], $note, time()]);
    // keep only the most recent snapshots per share
    $pdo->prepare("
        DELETE FROM share_history WHERE slug = ? AND id NOT IN (
            SELECT id FROM share_history WHERE slug = ? ORDER BY id DESC LIMIT ?
        )")->execute([$row['slug'], $row['slug'], HISTORY_KEEP]);
}

function edit_history_count(PDO $pdo, string $slug): int {
    $st = $pdo->prepare("SELECT COUNT(*) FROM share_history WHERE slug = ?");
    $st->execute([$slug]);
    return (int)$st->fetchColumn();
}

/** Persist an edited payload (title is kept in sync with the payload's title). */
function edit_save(PDO $pdo, array $row, array $data): void {
    $title = is_string($data['title'] ?? null) && trim($data['title']) !== '' ? mb_substr(trim($data['title']), 0, 300) : $row['title'];
    $data['title'] = $title;
    $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        throw new RuntimeException('payload could not be re-encoded');
    }
    $pdo->prepare("UPDATE share SET title = ?, payload = ?, updated_at = ? WHERE slug = ?")
        ->execute([$title, $payload, time(), $row['slug']]);
}

/** Undo the most recent edit. Returns false when there is nothing to restore. */
function edit_restore_latest(PDO $pdo, array $row): bool {
    $st = $pdo->prepare("SELECT * FROM share_history WHERE slug = ? ORDER BY id DESC LIMIT 1");
    $st->execute([$row['slug']]);
    $snap = $st->fetch();
    if (!$snap) {
        return false;
    }
    $pdo->prepare("UPDATE share SET title = ?, payload = ?, updated_at = ? WHERE slug = ?")
        ->execute([$snap['title'], $snap['payload'], time(), $row['slug']]);
    $pdo->prepare("DELETE FROM share_history WHERE id = ?")->execute([$snap['id']]);
    return true;
}

// ---- find & replace -------------------------------------------------------------

/**
 * Walk every string value in $node and replace $find with $replace.
 * $stats collects match counts and a few context samples for previews.
 */
function edit_replace_walk(mixed &$node, string $find, string $replace, bool $ci, bool $dry, array &$stats, array $path = []): void {
    if (is_array($node)) {
        foreach ($node as $k => &$v) {
            if (is_string($k) && in_array($k, REPLACE_SKIP_KEYS, true)) {
                continue;
            }
            edit_replace_walk($v, $find, $replace, $ci, $dry, $stats, [...$path, $k]);
        }
        unset($v);
        return;
    }
    if (!is_string($node) || $node === '') {
        return;
    }
    $count = $ci ? mb_substr_count(mb_strtolower($node), mb_strtolower($find)) : substr_count($node, $find);
    if ($count === 0) {
        return;
    }
    $stats['matches'] += $count;
    // messages[<i>] ... -> remember which message this hit belongs to
    if (($path[0] ?? null) === 'messages' && isset($path[1])) {
        $stats['messages'][(int)$path[1]] = true;
    }
    if (count($stats['samples']) < 8) {
        $pos = $ci ? mb_stripos($node, $find) : mb_strpos($node, $find);
        $start = max(0, $pos - 40);
        $ctx = mb_substr($node, $start, mb_strlen($find) + 80);
        $stats['samples'][] = [
            'path'    => implode('.', array_map('strval', $path)),
            'context' => ($start > 0 ? '…' : '') . $ctx . (mb_strlen($node) > $start + mb_strlen($ctx) ? '…' : ''),
        ];
    }
    if (!$dry) {
        $node = $ci ? str_ireplace($find, $replace, $node) : str_replace($find, $replace, $node);
    }
}

/** Returns stats; when !$dry the payload in $data is modified in place. */
function edit_replace(array &$data, string $find, string $replace, bool $ci, bool $dry): array {
    $stats = ['matches' => 0, 'messages' => [], 'samples' => []];
    edit_replace_walk($data, $find, $replace, $ci, $dry, $stats);
    $stats['messages'] = count($stats['messages']);
    return $stats;
}

// ---- structural edits -------------------------------------------------------------

function edit_delete_message(array &$data, int $i): bool {
    if (!isset($data['messages'][$i])) {
        return false;
    }
    array_splice($data['messages'], $i, 1);
    return true;
}

function edit_delete_part(array &$data, int $i, int $j): bool {
    if (!isset($data['messages'][$i]['parts'][$j]) || !is_array($data['messages'][$i]['parts'])) {
        return false;
    }
    array_splice($data['messages'][$i]['parts'], $j, 1);
    return true;
}

/** Replace the text of a text/reasoning part, or a tool part's input/output/error. */
function edit_set_part_text(array &$data, int $i, int $j, string $field, string $text): ?string {
    $part = &$data['messages'][$i]['parts'][$j];
    if (!is_array($part)) {
        return 'part not found';
    }
    $type = $part['type'] ?? '';
    if (($type === 'text' || $type === 'reasoning') && $field === 'text') {
        $part['text'] = $text;
        return null;
    }
    if ($type === 'tool' && in_array($field, ['output', 'error'], true)) {
        if (!is_array($part['state'] ?? null)) {
            $part['state'] = [];
        }
        $part['state'][$field] = $text;
        return null;
    }
    if ($type === 'tool' && $field === 'input') {
        if (!is_array($part['state'] ?? null)) {
            $part['state'] = [];
        }
        // keep structured input structured when the edited text is still valid JSON
        $decoded = json_decode($text, true);
        $part['state']['input'] = (json_last_error() === JSON_ERROR_NONE && !is_string($decoded)) ? $decoded : $text;
        return null;
    }
    return "field '$field' cannot be edited on a '$type' part";
}

// ---- duplicate ----------------------------------------------------------------------

function edit_duplicate(PDO $pdo, array $cfg, array $row): array {
    $data  = edit_decode($row);
    $slug  = slug_generate($pdo);
    $title = mb_substr($row['title'], 0, 285) . ' (copy)';
    $sid   = mb_substr($row['session_id'], 0, 150) . '-copy-' . $slug;
    $data['title']      = $title;
    $data['session_id'] = $sid;
    $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $now = time();
    $pdo->prepare("INSERT INTO share (slug,session_id,title,short_url,payload,password_hash,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$slug, $sid, $title, null, $payload, $row['password_hash'], $now, $now]);
    $longUrl  = rtrim((string)$cfg['PUBLIC_BASE_URL'], '/') . '/s/' . $slug;
    $shortUrl = yourls_shorten($cfg, $longUrl);
    if ($shortUrl !== null) {
        $pdo->prepare("UPDATE share SET short_url=? WHERE slug=?")->execute([$shortUrl, $slug]);
    }
    return ['slug' => $slug, 'url' => $longUrl, 'short_url' => $shortUrl, 'title' => $title, 'protected' => $row['password_hash'] !== null];
}
