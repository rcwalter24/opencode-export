# opencode-export

Export and share [OpenCode](https://opencode.ai) sessions — because the official export is incomplete and sharing requires a paid plan.

This project has two parts:

- **`opencode-export`** — a Bash client that reads your local OpenCode SQLite database and uploads sessions to your own server.
- **`server/`** — a self-hosted PHP application that stores and renders shared sessions as clean, readable HTML pages.

## Screenshots

### Uploading a session from the CLI
![CLI upload command](screenshots/list.png)

### Shared session view
![Shared session view](screenshots/session.png)

## Features

- Full session export — including tool calls, reasoning blocks, compaction points, and token/cost metadata
- Clean HTML rendering with syntax highlighting; dark mode follows the system, with a manual light/dark toggle on every page (remembered per browser)
- Paginated session view for long conversations
- **Raw JSON endpoint** — append `/raw` to any share URL (e.g. `https://share.example.com/s/AbCdEf/raw`) to get machine-readable output, ideal for feeding sessions to AI assistants
- **Password-protected shares** — `upload -p` asks for a password; viewers unlock the page in the browser, and the raw endpoint accepts HTTP Basic auth (`curl -u :PASSWORD`) so AI tools can still read it
- Bearer-token authentication for upload/delete operations; brute-force throttling on password and login attempts
- Hardened HTML output: strict Content-Security-Policy, Subresource Integrity on CDN assets, `noindex`, no inline handlers
- Optional [YOURLS](https://yourls.org) integration for short links with automatic expiry
- `opencode-export` CLI: list, show (Markdown), upload, list shared, delete, purge

---

## Requirements

### Server
- PHP 8.1+ with `pdo_sqlite` extension
- Nginx or Apache
- Write permission on `server/app/data/`

### Client (`opencode-export`)
- macOS or Linux
- `sqlite3`, `jq`, `curl`

---

## Server Setup

### 1. Clone and configure

```bash
git clone https://github.com/rcwalter24/opencode-export.git
cd opencode-export/server/app
cp config.example.php config.php
```

Edit `config.php`:

```php
return [
    'SHARE_TOKEN'     => 'your-strong-random-token',   // openssl rand -hex 32 — at least 32 chars
    'PUBLIC_BASE_URL' => 'https://share.example.com',
    'DB_PATH'         => __DIR__ . '/data/share.db',

    // Password-protected shares (defaults shown)
    'PASSWORD_COOKIE_TTL'     => 12 * 3600, // how long an unlocked page stays unlocked
    'PASSWORD_MAX_ATTEMPTS'   => 10,        // failed attempts per IP + share ...
    'PASSWORD_ATTEMPT_WINDOW' => 15 * 60,   // ... within this many seconds

    // Behind a reverse proxy / CDN? Rate limiting must see the real client IP.
    'TRUSTED_PROXIES'  => [],   // IPs, CIDRs, or 'cloudflare' (its published ranges)
    'CLIENT_IP_HEADER' => '',   // e.g. 'CF-Connecting-IP' behind Cloudflare

    // YOURLS (optional — leave empty to disable short links)
    'YOURLS_API_URL'         => '',
    'YOURLS_SIGNATURE_TOKEN' => '',
    'YOURLS_EXPIRY_DAYS'     => 7,
];
```

Behind Cloudflare use `'TRUSTED_PROXIES' => ['cloudflare'], 'CLIENT_IP_HEADER' => 'CF-Connecting-IP'`; otherwise list your proxy's address (e.g. `['127.0.0.1']`) and the last untrusted hop of `X-Forwarded-For` is used. Without this every visitor shares one rate-limit bucket.

The server refuses to start while `SHARE_TOKEN` is the placeholder or shorter than 32 characters. The token authenticates the API **and** signs the admin/unlock cookies, so rotating it logs everyone out.

### 2. Set permissions

```bash
chmod 750 server/app/data
chmod 640 server/app/config.php
```

Only `server/public/` may be exposed by the web server; `config.php` and the database live outside it.

### 3. Nginx configuration

Point the document root to `server/public/` and route all requests through `index.php`:

```nginx
server {
    listen 443 ssl;
    server_name share.example.com;

    root /path/to/opencode-export/server/public;
    index index.php;

    client_max_body_size 40m;   # uploads are capped at 32 MB by the app

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 4. Apache configuration

```apache
<VirtualHost *:443>
    DocumentRoot /path/to/opencode-export/server/public
    DirectoryIndex index.php

    <Directory /path/to/opencode-export/server/public>
        AllowOverride All
        Options -Indexes
        Require all granted
    </Directory>

    # Apache strips the Authorization header from PHP (CGI/FPM) unless told otherwise
    CGIPassAuth On

    # Rewrite everything through index.php
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [QSA,L]
</VirtualHost>
```

In `php.ini` make sure `post_max_size` is at least `40M` so 32 MB uploads are not truncated.

---

## Client Setup

### 1. Install `opencode-export`

```bash
# Copy to a directory in your PATH
cp opencode-export ~/.local/bin/opencode-export
chmod +x ~/.local/bin/opencode-export
```

### 2. Configure

Create `~/.config/opencode-export/config`:

```bash
SHARE_URL=https://share.example.com
SHARE_TOKEN=your-strong-random-token
```

The token must match `SHARE_TOKEN` in the server's `config.php`. Keep the file private — the CLI warns if it is readable by other users:

```bash
chmod 600 ~/.config/opencode-export/config
```

---

## Usage

### List recent local sessions

```bash
opencode-export list
opencode-export list -s "keyword"   # filter by title
opencode-export list -n 50          # show more results
```

### Preview a session as Markdown

```bash
opencode-export show <session_id>
```

### Upload a session

```bash
opencode-export upload <session_id>
# Prints the share URL to stdout
```

### Upload with a password

```bash
opencode-export upload <session_id> -p          # prompts (twice) for a password
echo "$PW" | opencode-export upload <session_id> --password-stdin   # for scripts
```

Viewers opening the link get a password prompt; once unlocked, the page stays unlocked in that browser for `PASSWORD_COOKIE_TTL` (12 h by default). The raw JSON endpoint takes the password via HTTP Basic auth, so AI assistants and scripts still work:

```bash
curl -u :PASSWORD https://share.example.com/s/AbCdEf/raw
```

Re-uploading the same session **without** a password flag keeps the existing protection; `-p` replaces the password and `--clear-password` makes the page public again. You can also change it later without re-uploading — from the admin page (🔑 button on each row) or with:

```bash
opencode-export password <slug|url>            # prompts for a new password
opencode-export password <slug|url> --clear    # make it public again
``` Changing or clearing the password invalidates every browser that had unlocked it. Passwords are stored as bcrypt hashes and never written into the payload. Wrong guesses are throttled per IP (10 per 15 minutes by default).

### Manage shared sessions

```bash
opencode-export shares              # list all shared sessions
opencode-export shares -s "keyword" # filter by title
opencode-export delete <slug|url>   # delete one session
opencode-export purge               # delete ALL shared sessions
```

### Override the database path

```bash
OPENCODE_DB=/custom/path/opencode.db opencode-export list
```

### Redact a shared session (admin page)

Sessions often contain things you did not mean to publish — API keys in tool output, internal hostnames, a customer's name. Sign in to the admin page and click **edit** on a share (or open `…/s/<slug>?edit=1`) to get an editing toolbar on the rendered page:

- **Find & replace** — exact text (optionally ignoring case) across every message, tool input/output and reasoning block. *Preview* shows the match count and context snippets before anything changes.
- **✕** on a message or on a single part removes it; **✎** on a text, reasoning or tool part opens it in a textarea.
- **Duplicate** creates a copy under a new link (same password setting) so you can redact the copy and keep the original private.
- **Undo** reverts the last edit; the five most recent states are kept per share.

Edits change the stored payload, so the page, the raw JSON endpoint and any existing short link all serve the redacted version. Nothing is sent to any third-party service.

### Read a session with an AI assistant

Every shared session has a raw JSON endpoint. Just append `/raw` to the share URL:

```
https://share.example.com/s/AbCdEf/raw
```

For public shares this endpoint needs no token and returns the full session payload as JSON — paste the URL into your AI assistant or `curl` it directly. For password-protected shares pass the password with HTTP Basic auth (`curl -u :PASSWORD …/raw`); the owner's Bearer token works too.

### Admin page

Open `https://share.example.com/` and sign in with `SHARE_TOKEN`. The session is a signed, HttpOnly cookie valid for 12 hours; the token itself is never placed in a URL. Old `/?token=…` bookmarks still work — they are exchanged for the cookie and redirected, but prefer the login form so the token stays out of browser history and server logs.

---

## YOURLS Integration (Optional)

If you run a [YOURLS](https://yourls.org) instance, fill in the `YOURLS_API_URL` and `YOURLS_SIGNATURE_TOKEN` fields in `config.php`. The server will automatically generate a short link for each uploaded session and expire it after `YOURLS_EXPIRY_DAYS` days.

Leave both fields empty to disable this feature entirely — sessions are still accessible via their full URL.

---

## API Reference

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/api/upload` | Bearer | Upload a session (JSON body). Optional `"password"` field: string sets/replaces, `null` clears, absent keeps. Response includes `protected` |
| `GET` | `/api/sessions` | Bearer | List all shared sessions (each with a `protected` flag) |
| `DELETE` | `/api/sessions` | Bearer | Delete all shared sessions |
| `DELETE` | `/api/share/:slug` | Bearer or admin cookie | Delete one session by slug |
| `PUT` | `/api/share/:slug/password` | Bearer or admin cookie | Body `{"password": "..."}` sets/replaces, `{"password": null}` removes |
| `POST` | `/api/share/:slug/replace` | Bearer or admin cookie | `{"find","replace","ignore_case","preview"}` → `{matches, messages, samples}`; without `preview` the change is applied |
| `POST` | `/api/share/:slug/delete-message` | Bearer or admin cookie | `{"message": i}` |
| `POST` | `/api/share/:slug/delete-part` | Bearer or admin cookie | `{"message": i, "part": j}` |
| `POST` | `/api/share/:slug/get-part`, `/set-part` | Bearer or admin cookie | Read / write one part: `{"message", "part", "field": text\|input\|output\|error, "text"}` |
| `POST` | `/api/share/:slug/duplicate` | Bearer or admin cookie | Copy the share under a new slug |
| `POST` | `/api/share/:slug/undo` | Bearer or admin cookie | Restore the state before the last edit |
| `GET` | `/s/:slug` | Public / password | View session as HTML (shows an unlock form when protected) |
| `POST` | `/s/:slug/unlock` | — | Submit the password; sets an unlock cookie scoped to `/s/:slug` |
| `GET` | `/s/:slug/raw` | Public / Basic / Bearer | Raw JSON payload. Protected shares accept `Authorization: Basic` (any user, the share password) or the Bearer token |
| `GET` | `/` | Admin cookie | Admin list page (login form when signed out) |
| `POST` | `/login`, `/logout` | — | Admin sign in (form field `token`) / sign out |

Error responses are JSON `{"error": "..."}`. Uploads are validated (`session_id` string, `messages[]` objects, `parts[]` objects) and rejected with `400` otherwise; `413` for payloads over the size limit; `429` with `Retry-After` when password or login attempts are throttled.

## Security notes

- `SHARE_TOKEN` guards uploads, deletions and the admin page. Generate it with `openssl rand -hex 32` and keep `config.php` unreadable by other users.
- Share URLs are unlisted (8 random alphanumeric characters) but not secret; use `-p` for anything sensitive.
- The stored payload is exactly what the client sent minus the `password` field. Sessions can contain file contents, environment details and tool output — review with `opencode-export show <id>` before uploading.
- HTML is rendered with Parsedown in safe mode plus escaping of every other field; the page ships with a nonce-based CSP, SRI on the highlight.js CDN assets, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer` and `noindex`.
- Unlock and admin cookies are HMAC-signed with a key derived from `SHARE_TOKEN`, `HttpOnly`, `SameSite` and `Secure` on HTTPS. There is no server-side session store.

---

## Donate

If this project saved you money on a paid plan, consider buying me a coffee.

**USDT (TRC20 / Tron)**
```
TNF3Cg6TfAiGTGsLq5Nx79KATiUkHmDqjD
```

---

## License

MIT
