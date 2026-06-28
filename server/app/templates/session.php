<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($payload['title'] ?? 'OpenCode Session', ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css" media="(prefers-color-scheme: light)">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css" media="(prefers-color-scheme: dark)">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js" defer></script>
<script>document.addEventListener('DOMContentLoaded', () => hljs.highlightAll());</script>
<style>
/* ---- CSS custom properties ---- */
:root {
  --bg:          #ffffff;
  --bg-alt:      #f6f8fa;
  --bg-card:     #ffffff;
  --border:      #d0d7de;
  --text:        #1f2328;
  --text-muted:  #656d76;
  --accent:      #0969da;
  --user-bg:     #f0f6ff;
  --user-border: #b6d4f5;
  --ai-bg:       #f6f8fa;
  --ai-border:   #d0d7de;
  --tool-bg:     #fff8e1;
  --tool-border: #f0c040;
  --tool-err-bg:    #fff0f0;
  --tool-err-border:#e06060;
  --reason-bg:   #f0f0ff;
  --reason-border:#c0c0e0;
  --compact-bg:  #fffbe6;
  --compact-border:#e6d000;
  --code-bg:     #f6f8fa;
  --shadow:      0 1px 3px rgba(0,0,0,.08);
}
@media (prefers-color-scheme: dark) {
  :root {
    --bg:          #0d1117;
    --bg-alt:      #161b22;
    --bg-card:     #161b22;
    --border:      #30363d;
    --text:        #e6edf3;
    --text-muted:  #8b949e;
    --accent:      #58a6ff;
    --user-bg:     #1a2332;
    --user-border: #2a4370;
    --ai-bg:       #161b22;
    --ai-border:   #30363d;
    --tool-bg:     #1e1a10;
    --tool-border: #8a6a00;
    --tool-err-bg:    #1e1010;
    --tool-err-border:#a04040;
    --reason-bg:   #12122a;
    --reason-border:#3a3a70;
    --compact-bg:  #1a1800;
    --compact-border:#807000;
    --code-bg:     #1e2430;
    --shadow:      0 1px 3px rgba(0,0,0,.4);
  }
}

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
  padding: 1.5rem 1rem 4rem;
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
.status-ok    .tool-status { background: #d4f0d4; color: #1a6a1a; border-color: #80cc80; }
.status-error .tool-status { background: #f0d4d4; color: #6a1a1a; border-color: #cc8080; }
@media (prefers-color-scheme: dark) {
  .status-ok    .tool-status { background: #0d2e0d; color: #80cc80; border-color: #2e6e2e; }
  .status-error .tool-status { background: #2e0d0d; color: #cc8080; border-color: #6e2e2e; }
}
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
  color: #c00;
  border-radius: 0 4px 4px 0;
}
@media (prefers-color-scheme: dark) {
  .tool-error { color: #f08080; }
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
<div class="page-wrapper">

  <!-- Meta card -->
  <div class="meta-card">
    <div class="meta-title"><?= htmlspecialchars($payload['title'] ?? 'Untitled Session', ENT_QUOTES, 'UTF-8') ?></div>
    <div class="meta-grid">
      <div><strong>Session ID</strong><br><?= htmlspecialchars($payload['session_id'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
      <div><strong>Created</strong><br><?= isset($payload['created_at']) ? format_ts((int)$payload['created_at']) : '-' ?></div>
      <?php if (!empty($payload['model'])): ?>
      <div><strong>Model</strong><br><?= htmlspecialchars($payload['model'], ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <?php if (!empty($payload['agent'])): ?>
      <div><strong>Agent</strong><br><?= htmlspecialchars($payload['agent'], ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <?php $tok = $payload['tokens'] ?? null; if ($tok): ?>
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
         href="<?= htmlspecialchars(rtrim($cfg['PUBLIC_BASE_URL'], '/') . '/s/' . $row['slug'] . '/raw', ENT_QUOTES, 'UTF-8') ?>"
         target="_blank" rel="noopener noreferrer">{} Raw JSON</a>
      <span class="raw-hint">for AI / API access</span>
    </div>

    <?php if (!empty($row['short_url'])): ?>
    <div class="short-url-row">
      <span class="short-url-badge">Short link &bull; 7 days</span>
      <a class="short-url-link" href="<?= htmlspecialchars($row['short_url'], ENT_QUOTES, 'UTF-8') ?>"
         target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($row['short_url'], ENT_QUOTES, 'UTF-8') ?></a>
      <button class="copy-btn" onclick="copyShort(this)" data-url="<?= htmlspecialchars($row['short_url'], ENT_QUOTES, 'UTF-8') ?>">Copy</button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Messages -->
  <?php
    $slugUrl = fn(int $p) => '/s/' . rawurlencode($row['slug']) . ($p > 1 ? '?page=' . $p : '');
  ?>
  <div class="messages">
    <?php foreach ($payload['messages'] ?? [] as $msg):
      $role      = $msg['role'] ?? 'unknown';
      $roleClass = in_array($role, ['user', 'assistant'], true) ? 'role-' . $role : 'role-unknown';
      $model     = $msg['model'] ?? null;
      $modelStr  = '';
      if (is_array($model)) {
          $modelStr = ($model['providerID'] ?? '') . '/' . ($model['modelID'] ?? '');
      } elseif (is_string($model)) {
          $modelStr = $model;
      }
      $msgTime   = isset($msg['time_created']) ? format_ts((int)$msg['time_created']) : '';
      $msgTokens = $msg['tokens'] ?? null;
      $msgCost   = $msg['cost']   ?? null;
    ?>
    <div class="message <?= $roleClass ?>">
      <div class="msg-header">
        <span class="role-badge"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></span>
        <?php if ($modelStr): ?>
        <span class="msg-model"><?= htmlspecialchars($modelStr, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <?php if ($msgTokens): ?>
        <span class="msg-tokens">in&nbsp;<?= (int)($msgTokens['input'] ?? 0) ?>&nbsp;/&nbsp;out&nbsp;<?= (int)($msgTokens['output'] ?? 0) ?></span>
        <?php endif; ?>
        <?php if ($msgCost !== null && $msgCost > 0): ?>
        <span class="msg-cost"><?= format_cost($msgCost) ?></span>
        <?php endif; ?>
        <?php if ($msgTime): ?><span class="msg-time"><?= htmlspecialchars($msgTime, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
      </div>
      <?php foreach ($msg['parts'] ?? [] as $part):
        echo render_part($part, $pd);
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
      <a href="<?= htmlspecialchars($slugUrl($page - 1), ENT_QUOTES, 'UTF-8') ?>">&lsaquo;</a>
    <?php else: ?>
      <span class="disabled">&lsaquo;</span>
    <?php endif; ?>

    <?php foreach ($pageRange($page, $pages) as $p): ?>
      <?php if ($p === '…'): ?>
        <span class="ellipsis">&hellip;</span>
      <?php elseif ($p === $page): ?>
        <span class="current"><?= $p ?></span>
      <?php else: ?>
        <a href="<?= htmlspecialchars($slugUrl($p), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($page < $pages): ?>
      <a href="<?= htmlspecialchars($slugUrl($page + 1), ENT_QUOTES, 'UTF-8') ?>">&rsaquo;</a>
    <?php else: ?>
      <span class="disabled">&rsaquo;</span>
    <?php endif; ?>
  </nav>
  <?php endif; ?>

</div>

<script>
function copyShort(btn) {
  var url = btn.getAttribute('data-url');
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url).then(function() {
      btn.textContent = 'Copied!';
      setTimeout(function() { btn.textContent = 'Copy'; }, 2000);
    });
  } else {
    var ta = document.createElement('textarea');
    ta.value = url;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    btn.textContent = 'Copied!';
    setTimeout(function() { btn.textContent = 'Copy'; }, 2000);
  }
}
</script>
</body>
</html>
