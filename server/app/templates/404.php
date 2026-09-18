<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>404 — Session Not Found</title>
<?= theme_head($nonce) ?>
<style nonce="<?= $nonce ?>">
body { margin: 0; background: var(--bg); color: var(--text);
       font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
       display: flex; align-items: center; justify-content: center; min-height: 100vh; }
.box { text-align: center; padding: 2rem; }
h1   { font-size: 5rem; font-weight: 800; margin: 0; color: var(--text-muted); line-height: 1; }
p    { font-size: 1.1rem; color: var(--text-muted); margin: 1rem 0; }
a    { color: var(--accent); }
</style>
</head>
<body>
<?= theme_toggle() ?>
<div class="box">
  <h1>404</h1>
  <p>This session does not exist or has been deleted.</p>
</div>
</body>
</html>
