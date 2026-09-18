<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Password required</title>
<?= theme_head($nonce) ?>
<style nonce="<?= $nonce ?>">
* { box-sizing: border-box; }
body { margin:0; background:var(--bg); color:var(--text);
       font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
       display:flex; align-items:center; justify-content:center; min-height:100vh; padding:1rem; }
.box { width:100%; max-width:380px; background:var(--bg-card); border:1px solid var(--border);
       border-radius:10px; padding:2rem 1.75rem; box-shadow:var(--shadow); }
.icon { font-size:2.2rem; text-align:center; margin-bottom:.5rem; }
h1 { font-size:1.15rem; margin:0 0 .35rem; text-align:center; }
p  { color:var(--text-muted); margin:0 0 1.25rem; font-size:.9rem; text-align:center; }
label { display:block; font-size:.8rem; font-weight:600; color:var(--text-muted); margin-bottom:.35rem; }
input[type=password] { width:100%; font-size:1rem; padding:.55rem .7rem; border:1px solid var(--border);
       border-radius:6px; background:var(--bg); color:var(--text); }
input[type=password]:focus { outline:2px solid var(--accent); outline-offset:1px; border-color:var(--accent); }
button { width:100%; margin-top:1rem; padding:.6rem; font-size:.95rem; font-weight:600; border:none;
       border-radius:6px; background:var(--accent); color:#fff; cursor:pointer; }
button:hover { filter:brightness(1.08); }
.error { color:var(--error); font-size:.85rem; margin:.6rem 0 0; text-align:center; }
</style>
</head>
<body>
<?= theme_toggle() ?>
<div class="box">
  <div class="icon">&#x1F512;</div>
  <h1>This session is password protected</h1>
  <p>Enter the password you received from the person who shared it.</p>
  <form method="post" action="/s/<?= h($row['slug']) ?>/unlock" autocomplete="off">
    <input type="hidden" name="page" value="<?= (int)$page ?>">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required autofocus autocomplete="current-password" maxlength="72">
    <button type="submit">Unlock</button>
    <?php if (!empty($error)): ?>
    <p class="error"><?= h($error) ?></p>
    <?php endif; ?>
  </form>
</div>
</body>
</html>
