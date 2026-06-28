<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shared Sessions</title>
<style>
:root {
  --bg:#ffffff; --bg-alt:#f6f8fa; --border:#d0d7de;
  --text:#1f2328; --text-muted:#656d76; --accent:#0969da;
  --shadow:0 1px 3px rgba(0,0,0,.08);
}
@media (prefers-color-scheme: dark) {
  :root {
    --bg:#0d1117; --bg-alt:#161b22; --border:#30363d;
    --text:#e6edf3; --text-muted:#8b949e; --accent:#58a6ff;
    --shadow:0 1px 3px rgba(0,0,0,.4);
  }
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
       font-size: 15px; line-height: 1.6; background: var(--bg); color: var(--text); }
a { color: var(--accent); text-decoration: none; }
a:hover { text-decoration: underline; }
.page { max-width: 800px; margin: 0 auto; padding: 2rem 1rem 4rem; }
h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: 1.5rem;
     padding-bottom: .75rem; border-bottom: 1px solid var(--border); }
h1 span { font-weight: 400; font-size: 1rem; color: var(--text-muted); margin-left: .5rem; }
.pagination { display: flex; align-items: center; justify-content: center; gap: .3rem;
              margin-top: 1.5rem; font-size: .875rem; flex-wrap: wrap; }
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
.empty { color: var(--text-muted); padding: 2rem 0; text-align: center; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: .6rem .75rem; border-bottom: 1px solid var(--border); text-align: left;
         vertical-align: top; }
th { font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em;
     color: var(--text-muted); background: var(--bg-alt); white-space: nowrap; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: var(--bg-alt); }
.title-cell { max-width: 280px; word-break: break-word; }
.slug-cell { font-family: monospace; font-size: .85rem; white-space: nowrap; }
.date-cell { font-size: .82rem; color: var(--text-muted); white-space: nowrap; }
.links-cell { white-space: nowrap; font-size: .85rem; }
.links-cell a + a { margin-left: .75rem; }
.del-btn { background: none; border: none; color: var(--text-muted); cursor: pointer;
           font-size: .85rem; padding: 0; }
.del-btn:hover { color: #c03030; }
@media (max-width: 600px) {
  th.hide-mobile, td.hide-mobile { display: none; }
  .title-cell { max-width: 160px; }
}
</style>
</head>
<body>
<div class="page">
  <h1>Shared Sessions <span><?= $total ?> total</span></h1>

  <?php if (empty($rows)): ?>
    <p class="empty">No sessions uploaded yet.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Title</th>
        <th class="hide-mobile">Slug</th>
        <th class="hide-mobile">Updated</th>
        <th>Links</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr id="row-<?= htmlspecialchars($r['slug'], ENT_QUOTES, 'UTF-8') ?>">
        <td class="title-cell"><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="slug-cell hide-mobile"><?= htmlspecialchars($r['slug'], ENT_QUOTES, 'UTF-8') ?></td>
        <td class="date-cell hide-mobile"><?= format_ts((int)$r['updated_at']) ?></td>
        <td class="links-cell">
          <a href="<?= htmlspecialchars($base . '/s/' . $r['slug'], ENT_QUOTES, 'UTF-8') ?>"
             target="_blank" rel="noopener noreferrer">open</a>
          <button class="del-btn"
                  onclick="delSession('<?= htmlspecialchars($r['slug'], ENT_QUOTES, 'UTF-8') ?>', this)"
                  title="Delete">&#x1F5D1;</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($pages > 1):
    $tok     = urlencode($_GET['token'] ?? '');
    $pageUrl = fn(int $p) => '/?token=' . $tok . '&page=' . $p;
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
  <nav class="pagination" aria-label="Pagination">
    <?php if ($page > 1): ?>
      <a href="<?= $pageUrl($page - 1) ?>">&lsaquo;</a>
    <?php else: ?>
      <span class="disabled">&lsaquo;</span>
    <?php endif; ?>

    <?php foreach ($pageRange($page, $pages) as $p): ?>
      <?php if ($p === '…'): ?>
        <span class="ellipsis">&hellip;</span>
      <?php elseif ($p === $page): ?>
        <span class="current"><?= $p ?></span>
      <?php else: ?>
        <a href="<?= $pageUrl($p) ?>"><?= $p ?></a>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($page < $pages): ?>
      <a href="<?= $pageUrl($page + 1) ?>">&rsaquo;</a>
    <?php else: ?>
      <span class="disabled">&rsaquo;</span>
    <?php endif; ?>
  </nav>
  <?php endif; ?>

  <?php endif; ?>
</div>

<script>
var TOKEN = <?= json_encode($_GET['token'] ?? '') ?>;

function delSession(slug, btn) {
  if (!confirm('Delete "' + slug + '"?')) return;
  btn.disabled = true;
  fetch('/api/share/' + slug, {
    method: 'DELETE',
    headers: { 'Authorization': 'Bearer ' + TOKEN }
  }).then(function(r) {
    if (r.status === 204 || r.status === 200) {
      var row = document.getElementById('row-' + slug);
      if (row) row.remove();
    } else {
      alert('Delete failed (HTTP ' + r.status + ')');
      btn.disabled = false;
    }
  }).catch(function() { alert('Network error'); btn.disabled = false; });
}
</script>
</body>
</html>
