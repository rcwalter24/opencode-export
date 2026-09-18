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

  <!-- Meta card -->
  <div class="meta-card">
    <div class="meta-title"><?= h($payload['title'] ?? 'Untitled Session') ?><?php if (!empty($row['password_hash'])): ?><span class="lock-badge" title="This session is password protected">&#x1F512; Protected</span><?php endif; ?></div>
    <div class="meta-grid">
      <div><strong>Session ID</strong><br><?= h($payload['session_id'] ?? '-') ?></div>
      <div><strong>Created</strong><br><?= isset($payload['created_at']) ? format_ts($payload['created_at']) : '-' ?></div>
      <?php if (!empty($payload['model']) && is_string($payload['model'])): ?>
      <div><strong>Model</strong><br><?= h($payload['model']) ?></div>
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
    $slugUrl = fn(int $p) => '/s/' . rawurlencode($row['slug']) . ($p > 1 ? '?page=' . $p : '');
  ?>
  <div class="messages">
    <?php foreach ($payload['messages'] as $msg):
      $role      = is_string($msg['role'] ?? null) ? $msg['role'] : 'unknown';
      $roleClass = in_array($role, ['user', 'assistant'], true) ? 'role-' . $role : 'role-unknown';
      $model     = $msg['model'] ?? null;
      $modelStr  = '';
      if (is_array($model)) {
          $modelStr = (string)($model['providerID'] ?? '') . '/' . (string)($model['modelID'] ?? '');
      } elseif (is_string($model)) {
          $modelStr = $model;
      }
      $msgTime   = isset($msg['time_created']) ? format_ts($msg['time_created']) : '';
      $msgTokens = is_array($msg['tokens'] ?? null) ? $msg['tokens'] : null;
      $msgCost   = $msg['cost']   ?? null;
    ?>
    <div class="message <?= $roleClass ?>">
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
      </div>
      <?php foreach (is_array($msg['parts'] ?? null) ? $msg['parts'] : [] as $part):
        if (is_array($part)) echo render_part($part, $pd);
      endforeach; ?>
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
