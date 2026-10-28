# Live site check (2026) — resumesmaker.com still on Node.js

We tested your live domain from outside. **Laravel is not handling API requests yet.**

| URL | What we got | Meaning |
|-----|-------------|---------|
| `GET /test-api` | `{"success":true,"message":"Port 3000 API is working"}` | **Node.js** (`resumemaker_admin`), not Laravel |
| `POST /resume/create` | `{"status":true,"result":"....pdf"}` only | **Node** `createResume` — empty Skills/Education |
| `GET /laravel-resume-check.php` | Angular HTML (~15 KB) | Request never runs Laravel PHP |

Laravel `test-api` would return:

```json
"message": "Laravel resume API is working",
"resume_engine": "laravel"
```

Until `/test-api` shows that message, **uploading Laravel files will not fix the PDF.**

---

## What you must do (Hostinger) — in order

### Step 1 — Turn OFF Node.js (required)

hPanel → **Websites** → **resumesmaker.com** → **Node.js** (or **Advanced** → **Node.js Web App**)

- **Delete** or **Stop** the application (the one using **port 3000** / `index.js`).

There must be **no** running Node app on this domain.

### Step 2 — Confirm Node is gone

Open in browser:

**https://resumesmaker.com/test-api**

- **Good:** `"Laravel resume API is working"` and `"resume_engine":"laravel"`
- **Bad:** `"Port 3000 API is working"` → Node still active; repeat Step 1 or contact Hostinger support.

### Step 3 — `public_html` entry files

Upload from PC `laravel81/deploy/hostinger/`:

| PC file | Server path |
|---------|-------------|
| `deploy/hostinger/index.php` | `public_html/index.php` (replace) |
| `deploy/hostinger/.htaccess` | `public_html/.htaccess` (replace) |

Upload from PC `laravel81/public/`:

| PC file | Server path |
|---------|-------------|
| `public/index.php` | `public_html/server/public/index.php` |
| `public/ping.php` | `public_html/server/public/ping.php` |
| `public/laravel-resume-check.php` | `public_html/server/public/laravel-resume-check.php` |

### Step 4 — Test PHP path (before Laravel routes)

Open:

**https://resumesmaker.com/server/public/ping.php**

or (if `.htaccess` works):

**https://resumesmaker.com/ping.php**

You must see plain text: **`LARAVEL_PUBLIC_OK`** and **`index_exists=yes`**.

If you see Angular 404 → `.htaccess` / `index.php` not applied; fix Step 3.

### Step 5 — SSH cache clear

```bash
cd ~/domains/resumesmaker.com/public_html/server
/opt/alt/php81/usr/bin/php artisan optimize:clear
```

### Step 6 — Postman

`POST https://resumesmaker.com/resume/create`  
Remove `"debug_normalize": true` for PDF.

Response must include **`resume_engine`** and **`mapper_version`**.

---

## Optional: document root (cleanest)

hPanel → Document root → **`public_html/server/public`**

Then you can simplify `public_html/index.php` (optional). **Still stop Node first.**

---

## Why “all files replaced” did not help

Files in `server/app/` were updated, but **every HTTP request still goes to Node on port 3000** (or Angular). Laravel code on disk is never executed for `/resume/create`.

**Stopping Node + fixing `public_html/index.php` + `server/public/index.php` is the fix.**
