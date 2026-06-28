<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>401 — Unauthorized</title>
<style>
:root { --bg:#fff; --text:#1f2328; --muted:#656d76; }
@media (prefers-color-scheme: dark) { :root { --bg:#0d1117; --text:#e6edf3; --muted:#8b949e; } }
body { margin:0; background:var(--bg); color:var(--text);
       font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
       display:flex; align-items:center; justify-content:center; min-height:100vh; }
.box { text-align:center; padding:2rem; }
h1 { font-size:4rem; font-weight:800; margin:0; color:var(--muted); }
p  { color:var(--muted); margin:.75rem 0; }
code { font-size:.9rem; background:#eee; padding:.1em .4em; border-radius:3px; }
@media (prefers-color-scheme: dark) { code { background:#222; } }
</style>
</head>
<body>
<div class="box">
  <h1>401</h1>
  <p>Access this page with your token:</p>
  <p><code>https://your-domain.example.com/?token=YOUR_TOKEN</code></p>
</div>
</body>
</html>
