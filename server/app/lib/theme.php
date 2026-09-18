<?php
declare(strict_types=1);

/**
 * Shared colour palette + manual light/dark toggle.
 *
 * The palette is emitted three times from one definition: light tokens on
 * :root, dark tokens under the OS preference (unless the user forced light),
 * and dark tokens again when the user forced dark. The choice is stored in
 * localStorage ("oc-theme" = light | dark, absent = follow the system).
 */

const THEME_LIGHT = [
    'bg' => '#ffffff', 'bg-alt' => '#f6f8fa', 'bg-card' => '#ffffff', 'border' => '#d0d7de',
    'text' => '#1f2328', 'text-muted' => '#656d76', 'accent' => '#0969da', 'error' => '#c03030',
    'user-bg' => '#f0f6ff', 'user-border' => '#b6d4f5', 'ai-bg' => '#f6f8fa', 'ai-border' => '#d0d7de',
    'tool-bg' => '#fff8e1', 'tool-border' => '#f0c040', 'tool-err-bg' => '#fff0f0', 'tool-err-border' => '#e06060',
    'reason-bg' => '#f0f0ff', 'reason-border' => '#c0c0e0', 'compact-bg' => '#fffbe6', 'compact-border' => '#e6d000',
    'code-bg' => '#f6f8fa', 'shadow' => '0 1px 3px rgba(0,0,0,.08)',
    'ok-bg' => '#d4f0d4', 'ok-fg' => '#1a6a1a', 'ok-border' => '#80cc80',
    'err-bg' => '#f0d4d4', 'err-fg' => '#6a1a1a', 'err-border' => '#cc8080',
];

const THEME_DARK = [
    'bg' => '#0d1117', 'bg-alt' => '#161b22', 'bg-card' => '#161b22', 'border' => '#30363d',
    'text' => '#e6edf3', 'text-muted' => '#8b949e', 'accent' => '#58a6ff', 'error' => '#f08080',
    'user-bg' => '#1a2332', 'user-border' => '#2a4370', 'ai-bg' => '#161b22', 'ai-border' => '#30363d',
    'tool-bg' => '#1e1a10', 'tool-border' => '#8a6a00', 'tool-err-bg' => '#1e1010', 'tool-err-border' => '#a04040',
    'reason-bg' => '#12122a', 'reason-border' => '#3a3a70', 'compact-bg' => '#1a1800', 'compact-border' => '#807000',
    'code-bg' => '#1e2430', 'shadow' => '0 1px 3px rgba(0,0,0,.4)',
    'ok-bg' => '#0d2e0d', 'ok-fg' => '#80cc80', 'ok-border' => '#2e6e2e',
    'err-bg' => '#2e0d0d', 'err-fg' => '#cc8080', 'err-border' => '#6e2e2e',
];

function theme_tokens(array $t): string {
    $out = '';
    foreach ($t as $k => $v) {
        $out .= "--$k:$v;";
    }
    return $out;
}

/** <style> + boot script for <head>. The script runs before first paint to avoid a flash. */
function theme_head(string $nonce): string {
    $light = theme_tokens(THEME_LIGHT);
    $dark  = theme_tokens(THEME_DARK);
    return <<<HTML
<style nonce="$nonce">
:root { color-scheme: light dark; $light }
@media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { $dark } }
:root[data-theme="dark"]  { color-scheme: dark; $dark }
:root[data-theme="light"] { color-scheme: light; }
.theme-toggle {
  position: fixed; top: .75rem; right: .75rem; z-index: 50;
  width: 2.2rem; height: 2.2rem; border-radius: 999px; font-size: 1.05rem; line-height: 1;
  background: var(--bg-card, var(--bg)); color: var(--text); border: 1px solid var(--border);
  box-shadow: var(--shadow); cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.theme-toggle:hover { background: var(--bg-alt); }
@media print { .theme-toggle { display: none; } }
</style>
<script nonce="$nonce">
(function () {
  var KEY = 'oc-theme';
  function stored() { try { var v = localStorage.getItem(KEY); return v === 'light' || v === 'dark' ? v : ''; } catch (e) { return ''; } }
  function apply(t) {
    var r = document.documentElement;
    if (t) r.setAttribute('data-theme', t); else r.removeAttribute('data-theme');
    var l = document.getElementById('hljs-light'), d = document.getElementById('hljs-dark');
    if (l && d) {
      l.media = t === 'light' ? 'all' : t === 'dark' ? 'not all' : '(prefers-color-scheme: light)';
      d.media = t === 'dark'  ? 'all' : t === 'light' ? 'not all' : '(prefers-color-scheme: dark)';
    }
    var b = document.querySelector('.theme-toggle');
    if (b) {
      var label = t === 'light' ? 'Light theme (click for dark)' : t === 'dark' ? 'Dark theme (click for system)' : 'System theme (click for light)';
      b.textContent = t === 'light' ? '☀️' : t === 'dark' ? '🌙' : '🌓';
      b.title = label; b.setAttribute('aria-label', label);
    }
  }
  apply(stored());
  document.addEventListener('DOMContentLoaded', function () {
    apply(stored());
    var b = document.querySelector('.theme-toggle');
    if (!b) return;
    b.addEventListener('click', function () {
      var next = { '': 'light', 'light': 'dark', 'dark': '' }[stored()];
      try { if (next) localStorage.setItem(KEY, next); else localStorage.removeItem(KEY); } catch (e) {}
      apply(next);
    });
  });
})();
</script>
HTML;
}

function theme_toggle(): string {
    return '<button type="button" class="theme-toggle" title="Theme" aria-label="Theme">&#x1F313;</button>';
}
