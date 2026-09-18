<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= h($payload['title'] ?? 'OpenCode Session') ?></title>
<link id="hljs-light" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css"
      integrity="sha384-eFTL69TLRZTkNfYZOLM+G04821K1qZao/4QLJbet1pP4tcF+fdXq/9CdqAbWRl/L" crossorigin="anonymous"
      media="(prefers-color-scheme: light)">
<link id="hljs-dark" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css"
      integrity="sha384-wH75j6z1lH97ZOpMOInqhgKzFkAInZPPSPlZpYKYTOqsaizPvhQZmAtLcPKXpLyH" crossorigin="anonymous"
      media="(prefers-color-scheme: dark)">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"
        integrity="sha384-F/bZzf7p3Joyp5psL90p/p89AZJsndkSoGwRpXcZhleCWhd8SnRuoYo4d0yirjJp" crossorigin="anonymous" defer></script>
<?= theme_head($nonce) ?>
<style nonce="<?= $nonce ?>">
/* ---- Reset & base ---- */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  font-size: 15px;
  line-height: 1.6;
  background: var(--bg);
  color: var(--text);
}
a { color: var(--accent); text-decoration: none; }
a:hover { text-decoration: underline; }
pre { overflow-x: auto; }
code { font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace; font-size: 0.875em; }

/* ---- Layout ---- */
.page-wrapper {
  max-width: 800px;
  margin: 0 auto;
  padding: 3.25rem 1rem 4rem;
}

/* ---- Meta card ---- */
.meta-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 1.25rem 1.5rem;
  margin-bottom: 2rem;
  box-shadow: var(--shadow);
}
.meta-title {
  font-size: 1.3rem;
  font-weight: 700;
  margin-bottom: .75rem;
  word-break: break-word;
}
.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: .4rem .8rem;
  font-size: .85rem;
  color: var(--text-muted);
}
.meta-grid > div { min-width: 0; overflow-wrap: anywhere; }
.meta-grid strong { color: var(--text); }
.short-url-row {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: .75rem;
  flex-wrap: wrap;
}
.short-url-badge {
  background: var(--accent);
  color: #fff;
  padding: .15rem .55rem;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 600;
  white-space: nowrap;
}
.short-url-link { font-size: .9rem; word-break: break-all; }
.copy-btn {
  background: var(--bg-alt);
  border: 1px solid var(--border);
  border-radius: 5px;
  padding: .2rem .6rem;
  font-size: .8rem;
  cursor: pointer;
  color: var(--text);
  transition: background .15s;
}
.copy-btn:hover { background: var(--border); }
.raw-row { margin-top: .75rem; display: flex; align-items: center; gap: .5rem; }
.raw-link { font-size: .82rem; font-family: monospace; color: var(--text-muted);
            border: 1px solid var(--border); border-radius: 4px; padding: .1rem .45rem; }
.raw-link:hover { color: var(--accent); border-color: var(--accent); text-decoration: none; }
.raw-hint { font-size: .78rem; color: var(--text-muted); }
.lock-badge {
  display: inline-block; margin-left: .5rem; vertical-align: middle;
  background: var(--bg-alt); border: 1px solid var(--border); color: var(--text-muted);
  padding: .1rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
}

/* ---- Messages ---- */
.messages { display: flex; flex-direction: column; gap: 1.5rem; }

.message {
  border-radius: 8px;
  padding: 1rem 1.25rem;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}
.message.role-user      { background: var(--user-bg); border-color: var(--user-border); }
.message.role-assistant { background: var(--ai-bg);   border-color: var(--ai-border);   }
.message.role-unknown   { background: var(--bg-alt); }

.msg-header {
  display: flex;
  align-items: baseline;
  gap: .6rem;
  margin-bottom: .75rem;
  flex-wrap: wrap;
}
.role-badge {
  font-weight: 700;
  font-size: .8rem;
  text-transform: uppercase;
  letter-spacing: .04em;
  padding: .15rem .5rem;
  border-radius: 4px;
}
.role-user      .role-badge { background: var(--accent); color: #fff; }
.role-assistant .role-badge { background: #555; color: #fff; }
.role-unknown   .role-badge { background: #888; color: #fff; }
.msg-model  { font-size: .8rem; color: var(--text-muted); }
.msg-time   { font-size: .75rem; color: var(--text-muted); margin-left: auto; }
.msg-tokens { font-size: .75rem; color: var(--text-muted); }
.msg-cost   { font-size: .75rem; color: var(--text-muted); }

/* ---- Part: text ---- */
.part-text { line-height: 1.7; }
.part-text + .part-text { margin-top: .75rem; }
.part-text p       { margin-bottom: .6em; }
.part-text h1,.part-text h2,.part-text h3 { margin: .8em 0 .4em; font-weight: 600; }
.part-text ul,.part-text ol { padding-left: 1.5em; margin-bottom: .6em; }
.part-text li { margin-bottom: .2em; }
.part-text pre { background: var(--code-bg); border: 1px solid var(--border); border-radius: 6px; padding: .75rem 1rem; margin: .5em 0; }
.part-text code { background: var(--code-bg); padding: .1em .3em; border-radius: 3px; }
.part-text pre code { background: transparent; padding: 0; border-radius: 0; }
.part-text blockquote { border-left: 3px solid var(--border); padding-left: 1em; color: var(--text-muted); margin: .5em 0; }
.part-text table { border-collapse: collapse; width: 100%; margin: .5em 0; }
.part-text th,.part-text td { border: 1px solid var(--border); padding: .4em .6em; }
.part-text th { background: var(--bg-alt); }

/* ---- Part: reasoning ---- */
.part-reasoning {
  background: var(--reason-bg);
  border: 1px solid var(--reason-border);
  border-radius: 6px;
  padding: .5rem .75rem;
  margin: .5rem 0;
}
.part-reasoning summary {
  cursor: pointer;
  font-size: .85rem;
  font-weight: 600;
  color: var(--text-muted);
  user-select: none;
  list-style: none;
}
.part-reasoning summary::before { content: "▶ "; font-size: .7em; }
.part-reasoning[open] summary::before { content: "▼ "; font-size: .7em; }
.reasoning-body { margin-top: .5rem; }
.reasoning-body pre { background: transparent; border: none; padding: 0; white-space: pre-wrap; word-break: break-word; font-size: .85rem; color: var(--text-muted); }

/* ---- Part: tool ---- */
.part-tool {
  background: var(--tool-bg);
  border: 1px solid var(--tool-border);
  border-radius: 6px;
  padding: .5rem .75rem;
  margin: .5rem 0;
}
.part-tool.status-error {
  background: var(--tool-err-bg);
  border-color: var(--tool-err-border);
}
.tool-header {
  display: flex;
  align-items: center;
  gap: .5rem;
  margin-bottom: .4rem;
  flex-wrap: wrap;
}
.tool-name   { font-weight: 700; font-size: .9rem; }
.tool-status {
  font-size: .75rem;
  padding: .1rem .4rem;
  border-radius: 3px;
  background: var(--bg-alt);
  color: var(--text-muted);
  border: 1px solid var(--border);
}
.status-ok    .tool-status { background: var(--ok-bg);  color: var(--ok-fg);  border-color: var(--ok-border); }
.status-error .tool-status { background: var(--err-bg); color: var(--err-fg); border-color: var(--err-border); }
.tool-section { margin-top: .4rem; }
.tool-section summary {
  cursor: pointer;
  font-size: .8rem;
  font-weight: 600;
  color: var(--text-muted);
  user-select: none;
}
.tool-input,.tool-output {
  background: var(--code-bg);
  border: 1px solid var(--border);
  border-radius: 4px;
  padding: .5rem .75rem;
  margin-top: .3rem;
  font-size: .82rem;
  max-height: 400px;
  overflow-y: auto;
  white-space: pre-wrap;
  word-break: break-word;
}
.tool-error {
  margin-top: .4rem;
  padding: .4rem .6rem;
  background: var(--tool-err-bg);
  border-left: 3px solid var(--tool-err-border);
  font-size: .85rem;
  color: var(--error);
  border-radius: 0 4px 4px 0;
}

/* ---- Part: file ---- */
.part-file {
  padding: .4rem .6rem;
  background: var(--bg-alt);
  border: 1px solid var(--border);
  border-radius: 4px;
  font-size: .85rem;
  margin: .4rem 0;
}
.file-mime { color: var(--text-muted); }

/* ---- Part: compaction ---- */
.part-compaction {
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .7rem 1rem;
  margin: 1rem 0;
  background: var(--compact-bg);
  border: 1px dashed var(--compact-border);
  border-radius: 6px;
  font-size: .85rem;
  color: var(--text-muted);
}
.compaction-icon { font-size: 1.1rem; flex-shrink: 0; }
.compaction-label { font-style: italic; }

/* ---- Part: unknown ---- */
.part-unknown {
  background: var(--bg-alt);
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: .5rem .75rem;
  margin: .5rem 0;
}
.part-unknown summary {
  cursor: pointer;
  font-size: .85rem;
  font-weight: 600;
  color: var(--text-muted);
  user-select: none;
}
.unknown-json {
  background: var(--code-bg);
  border: 1px solid var(--border);
  border-radius: 4px;
  padding: .5rem .75rem;
  margin-top: .3rem;
  font-size: .8rem;
  white-space: pre-wrap;
  word-break: break-word;
  overflow-x: auto;
}

/* ---- Print ---- */
@media print {
  .meta-card, .message { box-shadow: none; break-inside: avoid; }
  .copy-btn { display: none; }
  .part-tool, .part-reasoning, .part-unknown { break-inside: avoid; }
  details { display: block; }
  details > summary { display: none; }
  .tool-input, .tool-output { max-height: none; overflow: visible; }
}

/* ---- Pagination ---- */
.pagination { display: flex; align-items: center; justify-content: center; gap: .3rem;
              margin-top: 2rem; font-size: .875rem; flex-wrap: wrap; }
.pagination a, .pagination .current, .pagination .disabled {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 2rem; height: 2rem; padding: 0 .55rem;
  border: 1px solid var(--border); border-radius: 6px;
  color: var(--text); text-decoration: none; background: var(--bg); }
.pagination a:hover { background: var(--bg-alt); text-decoration: none; }
.pagination .current { background: var(--accent); color: #fff; border-color: var(--accent); font-weight: 600; }
.pagination .disabled { color: var(--text-muted); pointer-events: none;
  border-color: transparent; background: transparent; }
.pagination .ellipsis { border: none; background: none; color: var(--text-muted);
  min-width: auto; padding: 0 .1rem; pointer-events: none; }
@media print { .pagination { display: none; } }

/* ---- Edit mode ---- */
.ed-bar { position: sticky; top: 0; z-index: 40; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
  margin: -2.25rem -1rem 1.25rem; padding: .55rem 3.5rem .55rem 1rem; background: var(--tool-bg); border-bottom: 1px solid var(--tool-border);
  font-size: .85rem; }
.ed-bar .ed-title { font-weight: 700; margin-right: auto; }
.ed-bar button, .ed-bar a.btn, dialog button { padding: .35rem .7rem; font-size: .82rem; border-radius: 6px; cursor: pointer;
  border: 1px solid var(--border); background: var(--bg-card); color: var(--text); }
.ed-bar button:hover, dialog button:hover { background: var(--bg-alt); }
.ed-bar button.primary, .ed-bar a.btn.primary, dialog button.primary { background: var(--accent); border-color: var(--accent); color: #fff; text-decoration: none; }
.ed-bar button:disabled, dialog button:disabled { opacity: .5; cursor: default; }
.ed-msg { margin-left: auto; }
.ed-msg button, .ed-part button { background: none; border: 1px solid transparent; color: var(--text-muted); cursor: pointer;
  font-size: .78rem; padding: .1rem .4rem; border-radius: 4px; }
.ed-msg button:hover, .ed-part button:hover { color: var(--error); border-color: var(--border); background: var(--bg-card); }
.ed-part button.ed-edit:hover { color: var(--accent); }
.part { position: relative; }
.ed-part { position: absolute; top: .35rem; right: .5rem; display: flex; gap: .2rem; z-index: 2; }
.part + .part > .part-text { margin-top: .75rem; }
.ed-editor { margin: .5rem 0; }
.ed-editor textarea { width: 100%; min-height: 8rem; max-height: 60vh; font-family: "SFMono-Regular", Consolas, Menlo, monospace;
  font-size: .82rem; padding: .5rem; border: 1px solid var(--accent); border-radius: 6px; background: var(--bg); color: var(--text); }
.ed-editor .ed-actions { display: flex; gap: .4rem; margin-top: .4rem; align-items: center; font-size: .8rem; color: var(--text-muted); }
.ed-editor .ed-actions select { font-size: .8rem; padding: .2rem; background: var(--bg); color: var(--text); border: 1px solid var(--border); border-radius: 4px; }
.ed-editor button { padding: .3rem .7rem; font-size: .8rem; border-radius: 5px; cursor: pointer; border: 1px solid var(--border); background: var(--bg-card); color: var(--text); }
.ed-editor button.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
dialog { margin: auto; inset: 0; background: var(--bg-card); color: var(--text); border: 1px solid var(--border);
  border-radius: 10px; padding: 1.4rem 1.5rem 1.2rem; width: min(94vw, 560px); box-shadow: var(--shadow); }
dialog::backdrop { background: rgba(0,0,0,.45); }
dialog h2 { font-size: 1rem; margin: 0 0 .8rem; }
dialog label { display: block; font-size: .78rem; font-weight: 600; color: var(--text-muted); margin: .6rem 0 .25rem; }
dialog input[type=text] { width: 100%; font-size: .95rem; padding: .45rem .6rem; border: 1px solid var(--border); border-radius: 6px;
  background: var(--bg); color: var(--text); font-family: "SFMono-Regular", Consolas, Menlo, monospace; }
dialog .check { display: flex; align-items: center; gap: .4rem; font-size: .82rem; margin-top: .6rem; color: var(--text-muted); }
dialog .actions { display: flex; gap: .5rem; margin-top: 1rem; flex-wrap: wrap; align-items: center; }
dialog .result { margin-top: .8rem; font-size: .82rem; }
dialog .result .summary { font-weight: 600; margin-bottom: .3rem; }
dialog .samples { max-height: 220px; overflow: auto; border: 1px solid var(--border); border-radius: 6px; background: var(--bg-alt); }
dialog .samples div { padding: .3rem .5rem; border-bottom: 1px solid var(--border); font-family: monospace; font-size: .75rem; white-space: pre-wrap; word-break: break-all; }
dialog .samples div:last-child { border-bottom: none; }
dialog .samples span { color: var(--text-muted); }
dialog .error { color: var(--error); font-size: .82rem; margin-top: .5rem; }
dialog .link { font-family: monospace; word-break: break-all; }
@media print { .ed-bar, .ed-msg, .ed-part { display: none; } }

/* ---- Mobile ---- */
@media (max-width: 600px) {
  body { font-size: 14px; }
  .page-wrapper { padding: 1rem .75rem 3rem; }
  .meta-grid { grid-template-columns: 1fr 1fr; }
  .msg-time { margin-left: 0; width: 100%; }
}
</style>
</head>
<body>
<?= theme_toggle() ?>
<div class="page-wrapper">
<?php if ($edit): ?>
  <div class="ed-bar" data-slug="<?= h($row['slug']) ?>">
    <span class="ed-title">&#x270E; Editing</span>
    <button type="button" id="ed-replace">Find &amp; replace</button>
    <button type="button" id="ed-duplicate">Duplicate</button>
    <button type="button" id="ed-undo" <?= $history > 0 ? '' : 'disabled' ?>>Undo (<span id="ed-undo-n"><?= (int)$history ?></span>)</button>
    <a class="btn primary" href="<?= h('/s/' . $row['slug'] . ($page > 1 ? '?page=' . $page : '')) ?>">Done</a>
  </div>
<?php endif; ?>

  <!-- Meta card -->
  <div class="meta-card">
    <div class="meta-title"><?= h($payload['title'] ?? 'Untitled Session') ?><?php if (!empty($row['password_hash'])): ?><span class="lock-badge" title="This session is password protected">&#x1F512; Protected</span><?php endif; ?></div>
    <div class="meta-grid">
      <div><strong>Session ID</strong><br><?= h($payload['session_id'] ?? '-') ?></div>
      <div><strong>Created</strong><br><?= isset($payload['created_at']) ? format_ts($payload['created_at']) : '-' ?></div>
      <?php $modelLabel = format_model($payload['model'] ?? null); if ($modelLabel !== ''): ?>
      <div><strong>Model</strong><br><?= h($modelLabel) ?></div>
      <?php endif; ?>
      <?php if (!empty($payload['agent']) && is_string($payload['agent'])): ?>
      <div><strong>Agent</strong><br><?= h($payload['agent']) ?></div>
      <?php endif; ?>
      <?php $tok = $payload['tokens'] ?? null; if (is_array($tok)): ?>
      <div><strong>Tokens</strong><br>
        in&nbsp;<?= number_format((int)($tok['input'] ?? 0)) ?>
        &nbsp;/&nbsp;out&nbsp;<?= number_format((int)($tok['output'] ?? 0)) ?>
        <?php if (($tok['reasoning'] ?? 0) > 0): ?>&nbsp;/&nbsp;reason&nbsp;<?= number_format((int)$tok['reasoning']) ?><?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if (isset($payload['cost'])): ?>
      <div><strong>Cost</strong><br><?= format_cost($payload['cost']) ?></div>
      <?php endif; ?>
      <div><strong>Uploaded</strong><br><?= format_ts((int)$row['updated_at']) ?></div>
      <div><strong>Messages</strong><br>
        <?php if ($pages > 1): ?>
          <?= $msg_start ?>–<?= $msg_end ?> / <?= $total_msgs ?>
        <?php else: ?>
          <?= $total_msgs ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="raw-row">
      <a class="raw-link"
         href="<?= h($base . '/s/' . $row['slug'] . '/raw') ?>"
         target="_blank" rel="noopener noreferrer">{} Raw JSON</a>
      <?php if (!empty($row['password_hash'])): ?>
      <span class="raw-hint">for AI / API access &mdash; <code>curl -u :PASSWORD &hellip;/raw</code></span>
      <?php else: ?>
      <span class="raw-hint">for AI / API access</span>
      <?php endif; ?>
    </div>

    <?php if (!empty($row['short_url'])): ?>
    <div class="short-url-row">
      <span class="short-url-badge">Short link &bull; <?= (int)($cfg['YOURLS_EXPIRY_DAYS'] ?? 7) ?> days</span>
      <a class="short-url-link" href="<?= h($row['short_url']) ?>"
         target="_blank" rel="noopener noreferrer"><?= h($row['short_url']) ?></a>
      <button class="copy-btn" data-url="<?= h($row['short_url']) ?>">Copy</button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Messages -->
  <?php
    $slugUrl = function (int $p) use ($row, $edit): string {
      $q = [];
      if ($p > 1) $q[] = 'page=' . $p;
      if ($edit)  $q[] = 'edit=1';
      return '/s/' . rawurlencode($row['slug']) . ($q ? '?' . implode('&', $q) : '');
    };
  ?>
  <div class="messages">
    <?php foreach ($payload['messages'] as $k => $msg):
      $mi        = $offset + $k;
      $role      = is_string($msg['role'] ?? null) ? $msg['role'] : 'unknown';
      $roleClass = in_array($role, ['user', 'assistant'], true) ? 'role-' . $role : 'role-unknown';
      $modelStr  = format_model($msg['model'] ?? null);
      $msgTime   = isset($msg['time_created']) ? format_ts($msg['time_created']) : '';
      $msgTokens = is_array($msg['tokens'] ?? null) ? $msg['tokens'] : null;
      $msgCost   = $msg['cost']   ?? null;
    ?>
    <div class="message <?= $roleClass ?>" data-mi="<?= $mi ?>">
      <div class="msg-header">
        <span class="role-badge"><?= h($role) ?></span>
        <?php if ($modelStr): ?>
        <span class="msg-model"><?= h($modelStr) ?></span>
        <?php endif; ?>
        <?php if ($msgTokens): ?>
        <span class="msg-tokens">in&nbsp;<?= (int)($msgTokens['input'] ?? 0) ?>&nbsp;/&nbsp;out&nbsp;<?= (int)($msgTokens['output'] ?? 0) ?></span>
        <?php endif; ?>
        <?php if (is_numeric($msgCost) && $msgCost > 0): ?>
        <span class="msg-cost"><?= format_cost($msgCost) ?></span>
        <?php endif; ?>
        <?php if ($msgTime): ?><span class="msg-time"><?= h($msgTime) ?></span><?php endif; ?>
        <?php if ($edit): ?><span class="ed-msg"><button type="button" class="ed-del-msg" title="Delete this message">&#x2715; message</button></span><?php endif; ?>
      </div>
      <?php foreach (is_array($msg['parts'] ?? null) ? $msg['parts'] : [] as $j => $part):
        if (!is_array($part)) continue;
        $html = render_part($part, $pd);
        if ($html === '') continue;
        $ptype = is_string($part['type'] ?? null) ? $part['type'] : '';
        $fields = match ($ptype) {
          'text', 'reasoning' => ['text'],
          'tool' => array_values(array_filter(['input', 'output', 'error'], fn($f) => isset($part['state'][$f]) && $part['state'][$f] !== '')),
          default => [],
        };
      ?>
      <div class="part" data-pi="<?= (int)$j ?>" data-fields="<?= h(implode(',', $fields)) ?>">
        <?php if ($edit): ?>
        <span class="ed-part">
          <?php if ($fields): ?><button type="button" class="ed-edit" title="Edit text">&#x270E;</button><?php endif; ?>
          <button type="button" class="ed-del-part" title="Delete this part">&#x2715;</button>
        </span>
        <?php endif; ?>
        <?= $html ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($pages > 1):
    $pageRange = function(int $cur, int $total): array {
      if ($total <= 9) return range(1, $total);
      $left = max(2, $cur - 2);  $right = min($total - 1, $cur + 2);
      $out  = [1];
      if ($left  > 2)          $out[] = '…';
      for ($i = $left; $i <= $right; $i++) $out[] = $i;
      if ($right < $total - 1) $out[] = '…';
      $out[] = $total;
      return $out;
    };
  ?>
  <nav class="pagination" aria-label="Page navigation">
    <?php if ($page > 1): ?>
      <a href="<?= h($slugUrl($page - 1)) ?>">&lsaquo;</a>
    <?php else: ?>
      <span class="disabled">&lsaquo;</span>
    <?php endif; ?>

    <?php foreach ($pageRange($page, $pages) as $p): ?>
      <?php if ($p === '…'): ?>
        <span class="ellipsis">&hellip;</span>
      <?php elseif ($p === $page): ?>
        <span class="current"><?= $p ?></span>
      <?php else: ?>
        <a href="<?= h($slugUrl($p)) ?>"><?= $p ?></a>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($page < $pages): ?>
      <a href="<?= h($slugUrl($page + 1)) ?>">&rsaquo;</a>
    <?php else: ?>
      <span class="disabled">&rsaquo;</span>
    <?php endif; ?>
  </nav>
  <?php endif; ?>

</div>

<?php if ($edit): ?>
<dialog id="rp-dialog">
  <form id="rp-form" autocomplete="off">
    <h2>Find &amp; replace across the whole session</h2>
    <label for="rp-find">Find (exact text, min. 2 characters)</label>
    <input type="text" id="rp-find" required minlength="2">
    <label for="rp-replace">Replace with</label>
    <input type="text" id="rp-replace" value="[REDACTED]">
    <label class="check"><input type="checkbox" id="rp-ci"> Ignore case</label>
    <div class="result" id="rp-result" hidden>
      <div class="summary" id="rp-summary"></div>
      <div class="samples" id="rp-samples"></div>
    </div>
    <div class="error" id="rp-error" hidden></div>
    <div class="actions">
      <button type="submit" class="primary" id="rp-preview">Preview</button>
      <button type="button" id="rp-apply" disabled>Replace all</button>
      <button type="button" id="rp-cancel">Close</button>
    </div>
  </form>
</dialog>
<dialog id="dup-dialog">
  <h2>Copy created</h2>
  <p>The copy has a new link and keeps the same password setting. Edit the copy and leave the original untouched:</p>
  <p class="link" id="dup-url"></p>
  <div class="actions">
    <button type="button" class="primary" id="dup-open">Open copy in edit mode</button>
    <button type="button" id="dup-close">Close</button>
  </div>
</dialog>
<script nonce="<?= $nonce ?>">
(function () {
  var slug = document.querySelector('.ed-bar').dataset.slug;
  function api(action, body) {
    return fetch('/api/share/' + encodeURIComponent(slug) + '/' + action, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'X-Requested-With': 'opencode-share', 'Content-Type': 'application/json' },
      body: JSON.stringify(body || {})
    }).then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.error || ('HTTP ' + r.status)); return j; }); });
  }
  function fail(e) { alert(e.message || 'Request failed'); }

  // ---- find & replace
  var rp = document.getElementById('rp-dialog'), rpForm = document.getElementById('rp-form');
  var rpFind = document.getElementById('rp-find'), rpRepl = document.getElementById('rp-replace'), rpCi = document.getElementById('rp-ci');
  var rpResult = document.getElementById('rp-result'), rpSummary = document.getElementById('rp-summary'), rpSamples = document.getElementById('rp-samples');
  var rpApply = document.getElementById('rp-apply'), rpErr = document.getElementById('rp-error');
  function rpBody(preview) { return { find: rpFind.value, replace: rpRepl.value, ignore_case: rpCi.checked, preview: preview }; }
  function rpReset() { rpResult.hidden = true; rpApply.disabled = true; rpErr.hidden = true; }
  [rpFind, rpRepl, rpCi].forEach(function (el) { el.addEventListener('input', rpReset); });
  document.getElementById('ed-replace').addEventListener('click', function () { rpReset(); rp.showModal(); rpFind.focus(); });
  document.getElementById('rp-cancel').addEventListener('click', function () { rp.close(); });
  rpForm.addEventListener('submit', function (e) {
    e.preventDefault();
    api('replace', rpBody(true)).then(function (r) {
      rpSummary.textContent = r.matches === 0 ? 'No matches.' : r.matches + ' match' + (r.matches === 1 ? '' : 'es') + ' in ' + r.messages + ' message' + (r.messages === 1 ? '' : 's');
      rpSamples.innerHTML = '';
      (r.samples || []).forEach(function (s) {
        var d = document.createElement('div'), p = document.createElement('span');
        p.textContent = s.path + ': '; d.appendChild(p); d.appendChild(document.createTextNode(s.context)); rpSamples.appendChild(d);
      });
      rpResult.hidden = false; rpApply.disabled = r.matches === 0;
    }).catch(function (e) { rpErr.textContent = e.message; rpErr.hidden = false; });
  });
  rpApply.addEventListener('click', function () {
    rpApply.disabled = true;
    api('replace', rpBody(false)).then(function () { location.reload(); }).catch(function (e) { rpErr.textContent = e.message; rpErr.hidden = false; });
  });

  // ---- duplicate / undo
  var dup = document.getElementById('dup-dialog'), dupUrl = '';
  document.getElementById('ed-duplicate').addEventListener('click', function () {
    api('duplicate').then(function (r) {
      dupUrl = r.url; document.getElementById('dup-url').textContent = r.url; dup.showModal();
    }).catch(fail);
  });
  document.getElementById('dup-open').addEventListener('click', function () { location.href = dupUrl + '?edit=1'; });
  document.getElementById('dup-close').addEventListener('click', function () { dup.close(); });
  document.getElementById('ed-undo').addEventListener('click', function () {
    if (!confirm('Undo the last edit?')) return;
    api('undo').then(function () { location.reload(); }).catch(fail);
  });

  // ---- per message / per part
  document.querySelectorAll('.ed-del-msg').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var mi = +btn.closest('.message').dataset.mi;
      if (!confirm('Delete message #' + (mi + 1) + '? (can be undone)')) return;
      api('delete-message', { message: mi }).then(function () { location.reload(); }).catch(fail);
    });
  });
  document.querySelectorAll('.ed-del-part').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var part = btn.closest('.part'), mi = +part.closest('.message').dataset.mi, pi = +part.dataset.pi;
      if (!confirm('Delete this part? (can be undone)')) return;
      api('delete-part', { message: mi, part: pi }).then(function () { location.reload(); }).catch(fail);
    });
  });
  document.querySelectorAll('.ed-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var part = btn.closest('.part'), mi = +part.closest('.message').dataset.mi, pi = +part.dataset.pi;
      var fields = part.dataset.fields.split(',').filter(Boolean);
      if (part.querySelector('.ed-editor')) return;
      api('get-part', { message: mi, part: pi }).then(function (p) {
        var box = document.createElement('div'); box.className = 'ed-editor';
        var ta = document.createElement('textarea');
        var sel = document.createElement('select');
        fields.forEach(function (f) { var o = document.createElement('option'); o.value = f; o.textContent = f; sel.appendChild(o); });
        var load = function () { ta.value = p[sel.value] == null ? '' : p[sel.value]; };
        sel.addEventListener('change', load); load();
        var save = document.createElement('button'); save.type = 'button'; save.className = 'primary'; save.textContent = 'Save';
        var cancel = document.createElement('button'); cancel.type = 'button'; cancel.textContent = 'Cancel';
        var act = document.createElement('div'); act.className = 'ed-actions';
        if (fields.length > 1) { act.appendChild(document.createTextNode('Field: ')); act.appendChild(sel); }
        act.appendChild(save); act.appendChild(cancel);
        box.appendChild(ta); box.appendChild(act);
        part.insertBefore(box, part.children[1] || null);
        ta.focus();
        cancel.addEventListener('click', function () { box.remove(); });
        save.addEventListener('click', function () {
          save.disabled = true;
          api('set-part', { message: mi, part: pi, field: sel.value, text: ta.value }).then(function () { location.reload(); }).catch(function (e) { save.disabled = false; fail(e); });
        });
      }).catch(fail);
    });
  });
})();
</script>
<?php endif; ?>
<script nonce="<?= $nonce ?>">
document.addEventListener('DOMContentLoaded', function () {
  if (window.hljs) hljs.highlightAll();
  document.querySelectorAll('.copy-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var url = btn.getAttribute('data-url');
      var done = function () {
        btn.textContent = 'Copied!';
        setTimeout(function () { btn.textContent = 'Copy'; }, 2000);
      };
      if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(done);
      } else {
        var ta = document.createElement('textarea');
        ta.value = url;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        done();
      }
    });
  });
});
</script>
</body>
</html>
