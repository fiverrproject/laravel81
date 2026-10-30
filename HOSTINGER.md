# Hostinger — resumesmaker.com (pure Laravel, no Node)

## Is there a solution? **Yes.**

| Part | Status |
|------|--------|
| Resume JSON → PDF mapping (Skills, Education, Language, etc.) | **Done in this repo** — works on local Laravel |
| Your live site | **Not fixed by uploading `server/app` again** while **Node.js port 3000** still answers the domain |

Live proof (test anytime): open **`https://resumesmaker.com/test-api`**

- **Wrong (what you have now):** `{"success":true,"message":"Port 3000 API is working"}` → **Node** (Laravel upload is ignored)
- **Correct (Laravel):** `"message":"Laravel resume API is working"` and `"resume_engine":"laravel"`

**Also check Postman response headers** (not only JSON body):

| Header | Laravel (correct) | Node (wrong) |
|--------|-------------------|--------------|
| `X-Resume-Engine` | `laravel` | missing |
| `X-Mapper-Version` | `laravel-resume-mapper-v9` | missing |

**Postman body for `POST /resume/create`:**

| Response | Meaning |
|----------|---------|
| Only `"status"` + `"result"` (2 keys) | **Node** — Skills/Language will stay empty |
| Includes `"resume_engine":"laravel"` and `"debug_first_skill"` | **Laravel** — PDF should match local |

Until `/test-api` shows Laravel, PDF will stay broken no matter how many PHP files you upload.

---

## Your server layout (from your File Manager)

```text
public_html/
├── index.php          ← must load Laravel (see deploy/hostinger/index.php)
├── .htaccess          ← must route to server/public (see deploy/hostinger/.htaccess)
└── server/            ← full Laravel project
    └── public/
        ├── index.php  ← MUST EXIST (from laravel81/public/index.php)
        └── .htaccess
```

**Common mistake:** `server/public/index.php` missing on server → Laravel never runs.

---

## Fix checklist (do in this order only)

### 1. Stop Node.js (required)

hPanel → Websites → **resumesmaker.com** → **Node.js** → **Stop** or **Delete** the app (port **3000**).

### 2. Three files at `public_html` root

Copy from this repo on your PC to Hostinger:

| PC | Server |
|----|--------|
| `deploy/hostinger/index.php` | `public_html/index.php` |
| `deploy/hostinger/.htaccess` | `public_html/.htaccess` |

### 3. Laravel front controller

| PC | Server |
|----|--------|
| `public/index.php` | `public_html/server/public/index.php` |

### 4. Upload / refresh full Laravel `server/` folder

Upload changed app code (at minimum after git pull from your PC):

- `app/Services/` (mapper, Handlebars, ResumeSectionHtmlBuilder, Resume12PostRenderFix)
- `app/Http/Controllers/ResumeBuilderController.php`
- `resources/resume-builder/resume12.html`
- `routes/web.php`, `routes/api.php`

### 5. SSH

```bash
cd ~/domains/resumesmaker.com/public_html/server
/opt/alt/php81/usr/bin/php artisan optimize:clear
/opt/alt/php81/usr/bin/php artisan resume:verify-deploy
```

### 6. Verify (browser)

1. `https://resumesmaker.com/test-api` → **Laravel** message (not Port 3000)
2. Postman `POST https://resumesmaker.com/resume/create` → JSON must include **`resume_engine`** and **`mapper_version`** (not only `status` + `result`)

**Optional (cleaner):** hPanel document root = `public_html/server/public` (then Step 2 can be skipped later; **Step 1 still required**).

---

## No `package.json` / `node_modules` in this Laravel upload

This repo is **PHP only**. There is no Node resume server and no npm install inside `server/`.

PDF uses **chrome-php/chrome** (needs **Chrome or Chromium** on the server).

After deploy, SSH:

```bash
cd ~/domains/resumesmaker.com/public_html/server
/opt/alt/php81/usr/bin/php artisan resume:detect-chrome
```

- If it prints `CHROME_PATH=...`, put that line in **`server/.env`**, then:
  ```bash
  /opt/alt/php81/usr/bin/php artisan config:clear
  ```
- If detect **fails**, Hostinger shared hosting may not include Chrome — use **VPS**, install `chromium-browser`, or ask Hostinger support for the Chromium binary path.

Do **not** set `CHROME_PATH=/usr/bin/google-chrome` unless that file exists (`ls -la` on SSH).

---

## PHP 8.1, Composer, .env, permissions

Keep **PHP 8.1** in hPanel. SSH often uses PHP 7.3 — always:

```bash
/opt/alt/php81/usr/bin/php /usr/local/bin/composer2 install --no-dev --optimize-autoloader
/opt/alt/php81/usr/bin/php artisan key:generate   # if new .env
chmod -R 775 storage bootstrap/cache
```

`.env`: `APP_URL=https://resumesmaker.com`, `CHROME_PATH=` (auto) or path from `resume:detect-chrome`.

Templates: upload `resources/resume-builder/` → `server/resources/resume-builder/`.

---

## Postman

- URL: `POST https://resumesmaker.com/resume/create`
- Remove `"debug_normalize": true` when you want a PDF (debug mode returns JSON only, no PDF).

---

## Summary

- **No** — uploading more random new files will not fix it.
- **Yes** — stop Node + `public_html/index.php` + `server/public/index.php` + existing Laravel code in `server/`.

Do not run Node for resume API on this domain anymore.
