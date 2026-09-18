<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Shared Sessions</title>
<?= theme_head($nonce) ?>
<style nonce="<?= $nonce ?>">
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
       font-size: 15px; line-height: 1.6; background: var(--bg); color: var(--text); }
a { color: var(--accent); text-decoration: none; }
a:hover { text-decoration: underline; }
.page { max-width: 800px; margin: 0 auto; padding: 3.25rem 1rem 4rem; }
h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: 1.5rem;
     padding-bottom: .75rem; border-bottom: 1px solid var(--border); }
h1 span { font-weight: 400; font-size: 1rem; color: var(--text-muted); margin-left: .5rem; }
.topbar { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; }
.logout-btn { background: none; border: 1px solid var(--border); border-radius: 5px; color: var(--text-muted);
              cursor: pointer; font-size: .8rem; padding: .2rem .6rem; }
.logout-btn:hover { color: var(--text); background: var(--bg-alt); }
.lock { font-size: .8rem; margin-left: .3rem; }
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
.del-btn:hover { color: var(--error); }
.pw-btn { background: none; border: none; color: var(--text-muted); cursor: pointer;
          font-size: .85rem; padding: 0; margin-right: .6rem; }
.pw-btn:hover { color: var(--accent); }
/* the global reset zeroes margins; dialogs rely on margin:auto to be centred */
dialog { margin: auto; inset: 0; background: var(--bg-card, var(--bg)); color: var(--text); border: 1px solid var(--border);
         border-radius: 10px; padding: 1.5rem 1.5rem 1.25rem; width: min(92vw, 380px); box-shadow: var(--shadow); }
dialog::backdrop { background: rgba(0,0,0,.45); }
dialog h2 { font-size: 1rem; margin: 0 0 .25rem; }
dialog .hint { font-size: .82rem; color: var(--text-muted); margin: 0 0 1rem; word-break: break-word; }
dialog label { display: block; font-size: .8rem; font-weight: 600; color: var(--text-muted); margin-bottom: .35rem; }
dialog input[type=password] { width: 100%; font-size: 1rem; padding: .5rem .65rem; border: 1px solid var(--border);
         border-radius: 6px; background: var(--bg); color: var(--text); }
dialog .actions { display: flex; gap: .5rem; margin-top: 1rem; flex-wrap: wrap; }
dialog button { padding: .45rem .9rem; font-size: .85rem; border-radius: 6px; cursor: pointer;
         border: 1px solid var(--border); background: var(--bg-alt); color: var(--text); }
dialog button.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
dialog button.danger  { color: var(--error); margin-left: auto; }
dialog .error { color: var(--error); font-size: .82rem; margin: .6rem 0 0; }
@media (max-width: 600px) {
  th.hide-mobile, td.hide-mobile { display: none; }
  .title-cell { max-width: 160px; }
}
</style>
</head>
<body>
<?= theme_toggle() ?>
<div class="page">
  <div class="topbar">
    <h1>Shared Sessions <span><?= (int)$total ?> total</span></h1>
    <form method="post" action="/logout"><button class="logout-btn" type="submit">Sign out</button></form>
  </div>

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
      <tr id="row-<?= h($r['slug']) ?>">
        <td class="title-cell"><?= h($r['title']) ?><span class="lock" title="Password protected"<?= empty($r['password_hash']) ? ' hidden' : '' ?>>&#x1F512;</span></td>
        <td class="slug-cell hide-mobile"><?= h($r['slug']) ?></td>
        <td class="date-cell hide-mobile"><?= format_ts($r['updated_at']) ?></td>
        <td class="links-cell">
          <a href="<?= h($base . '/s/' . $r['slug']) ?>"
             target="_blank" rel="noopener noreferrer">open</a>
          <button class="pw-btn" data-slug="<?= h($r['slug']) ?>" data-title="<?= h($r['title']) ?>"
                  data-protected="<?= empty($r['password_hash']) ? '0' : '1' ?>" title="Password">&#x1F511;</button>
          <button class="del-btn" data-slug="<?= h($r['slug']) ?>" title="Delete">&#x1F5D1;</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($pages > 1):
    $pageUrl = fn(int $p) => '/?page=' . $p;
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

<dialog id="pw-dialog">
  <form method="dialog" id="pw-form" autocomplete="off">
    <h2 id="pw-heading">Password</h2>
    <p class="hint" id="pw-hint"></p>
    <label for="pw-input">New password (4–72 characters)</label>
    <input type="password" id="pw-input" autocomplete="new-password" minlength="4" maxlength="72">
    <p class="error" id="pw-error" hidden></p>
    <div class="actions">
      <button type="submit" class="primary" value="set">Save</button>
      <button type="button" value="cancel" id="pw-cancel">Cancel</button>
      <button type="button" class="danger" value="clear" id="pw-clear">Remove password</button>
    </div>
  </form>
</dialog>

<script nonce="<?= $nonce ?>">
(function () {
  var dlg = document.getElementById('pw-dialog'), form = document.getElementById('pw-form');
  var input = document.getElementById('pw-input'), err = document.getElementById('pw-error');
  var clearBtn = document.getElementById('pw-clear'), current = null;

  function api(slug, password) {
    return fetch('/api/share/' + encodeURIComponent(slug) + '/password', {
      method: 'PUT', credentials: 'same-origin',
      headers: { 'X-Requested-With': 'opencode-share', 'Content-Type': 'application/json' },
      body: JSON.stringify({ password: password })
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, body: j }; }); });
  }
  function showError(msg) { err.textContent = msg; err.hidden = false; }
  function applyState(btn, isProtected) {
    btn.dataset.protected = isProtected ? '1' : '0';
    var lock = btn.closest('tr').querySelector('.lock');
    if (lock) lock.hidden = !isProtected;
  }

  document.querySelectorAll('.pw-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      current = btn;
      var prot = btn.dataset.protected === '1';
      document.getElementById('pw-heading').textContent = prot ? 'Change password' : 'Set password';
      document.getElementById('pw-hint').textContent = btn.dataset.title + ' (' + btn.dataset.slug + ')';
      clearBtn.hidden = !prot;
      input.value = ''; err.hidden = true;
      dlg.showModal(); input.focus();
    });
  });
  document.getElementById('pw-cancel').addEventListener('click', function () { dlg.close(); });
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (input.value.length < 4) { showError('Password must be at least 4 characters.'); return; }
    api(current.dataset.slug, input.value).then(function (r) {
      if (!r.ok) { showError(r.status === 401 ? 'Session expired — sign in again.' : (r.body.error || 'HTTP ' + r.status)); return; }
      applyState(current, r.body.protected); dlg.close();
    }).catch(function () { showError('Network error'); });
  });
  clearBtn.addEventListener('click', function () {
    if (!confirm('Remove the password? The page becomes public.')) return;
    api(current.dataset.slug, null).then(function (r) {
      if (!r.ok) { showError(r.body.error || 'HTTP ' + r.status); return; }
      applyState(current, false); dlg.close();
    }).catch(function () { showError('Network error'); });
  });
})();

document.querySelectorAll('.del-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var slug = btn.getAttribute('data-slug');
    if (!confirm('Delete "' + slug + '"?')) return;
    btn.disabled = true;
    fetch('/api/share/' + encodeURIComponent(slug), {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'opencode-share' }
    }).then(function (r) {
      if (r.status === 204 || r.status === 200) {
        var row = document.getElementById('row-' + slug);
        if (row) row.remove();
      } else if (r.status === 401) {
        alert('Session expired — please sign in again.');
        location.reload();
      } else {
        alert('Delete failed (HTTP ' + r.status + ')');
        btn.disabled = false;
      }
    }).catch(function () { alert('Network error'); btn.disabled = false; });
  });
});
</script>
</body>
</html>
