# Pure Laravel on resumesmaker.com (no Node.js)

Your Postman response `{"status":true,"result":"xxx.pdf"}` only is **Node.js**, not this Laravel project.

The marketing **404 page** on `/resume/deploy-fingerprint` means the browser hit the **Angular site**, not Laravel `public/index.php`.

---

## Goal

One app: **Laravel only**. Document root = Laravel **`public/`** folder. Node stopped and removed from routing.

---

## Step 1 — Hostinger: document root (required)

hPanel → **Domains** → **resumesmaker.com** → **Document root**

Set to the **`public` folder inside your Laravel upload**, for example:

```text
/home/u69059908/domains/resumesmaker.com/public_html/server/public
```

Not `public_html` alone. Not `public_html/server` (missing `/public`). Not Node app root.

Save and wait 1–2 minutes.

---

## Step 2 — Stop Node completely

hPanel → **Advanced** → **Node.js** (or SSH):

- Delete or **Stop** the application that runs `index.js` / port 3000.
- Do not proxy `/resume/create` to Node.

---

## Step 3 — Upload Laravel (`laravel81`)

Upload the full project to e.g. `public_html/server/` so you have:

```text
public_html/server/app/
public_html/server/routes/
public_html/server/resources/resume-builder/
public_html/server/public/index.php
public_html/server/public/laravel-resume-check.php   ← upload this
public_html/server/vendor/   (or run composer on SSH)
public_html/server/.env
```

On SSH:

```bash
cd ~/domains/resumesmaker.com/public_html/server
/opt/alt/php81/usr/bin/php /usr/local/bin/composer2 install --no-dev --optimize-autoloader
/opt/alt/php81/usr/bin/php artisan key:generate   # if .env is new
/opt/alt/php81/usr/bin/php artisan optimize:clear
/opt/alt/php81/usr/bin/php artisan resume:verify-deploy
```

---

## Step 4 — Verify Laravel is live (browser)

Open **one** of these (after Step 1 document root = `server/public`):

| URL | Expected |
|-----|----------|
| `https://resumesmaker.com/laravel-resume-check.php` | JSON with `"resume_engine":"laravel"` |
| `https://resumesmaker.com/resume/deploy-fingerprint` | Same JSON |
| `https://resumesmaker.com/test-api` | JSON `"resume_engine":"laravel"` |

If you still see the **Resumes Maker 404 marketing page**, document root is **still wrong** (Angular/Node parent folder).

---

## Step 5 — Postman (PDF)

```http
POST https://resumesmaker.com/resume/create
Content-Type: application/json
```

Use your JSON **without** `"debug_normalize": true` when generating PDF.

**Correct Laravel response** (many fields), including:

```json
"resume_engine": "laravel",
"mapper_version": "laravel-resume-mapper-v9",
"post_render_version": "resume12-post-render-v3"
```

**Wrong (Node still running or wrong host):**

```json
{"status": true, "result": "1733....pdf"}
```

---

## Step 6 — Optional: same domain, Laravel + Angular frontend

Laravel already serves Angular from `public/frontend/index.html` for non-API routes.

With document root = `server/public`:

- `POST /resume/create` → Laravel controller  
- `/`, `/templates`, etc. → SPA fallback in `routes/web.php`

No Node required.

---

## If document root cannot be changed

Put this at the **top** of `public_html/.htaccess` (adjust `server` path):

```apache
RewriteEngine On
RewriteRule ^(resume/create|resume/deploy-fingerprint|resume/mapper-check|test-api|laravel-resume-check\.php)$ server/public/$1 [L,QSA]
RewriteRule ^([0-9]+\.pdf)$ server/public/$1 [L]
RewriteRule ^LAST_RENDER\.html$ server/public/LAST_RENDER.html [L]
```

Better long-term: still use Step 1 (document root = `server/public`).

---

## Summary

| Problem | Fix |
|---------|-----|
| Empty Skills/Education on PDF | Laravel mapper (already in code) |
| Upload Laravel but no change | Site still on **Node** or wrong **document root** |
| `/resume/deploy-fingerprint` 404 | Not Laravel — fix **document root** |
| Proof URL | `https://resumesmaker.com/laravel-resume-check.php` |
