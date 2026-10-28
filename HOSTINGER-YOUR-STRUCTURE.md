# Fix your Hostinger layout (`public_html` + `server`)

Your screenshots show the problem clearly.

## What is wrong

| Location | Problem |
|----------|---------|
| `public_html/server/public/` | **`index.php` is missing** — Laravel cannot start. Without it, every route 404s or falls back to the Angular site. |
| `public_html/index.php` | Document root hits **this** file first (not Laravel `server/public/`). It is probably an **old** entry (Node or a broken copy), not the Laravel front controller. |
| `public_html/.htaccess` | May rewrite to the wrong place (4 days old vs Laravel updated 6 hours ago). |
| Postman `{"status":true,"result":"....pdf"}` only | Still **Node** or old PHP — **not** your Laravel `ResumeBuilderController`. |

Laravel code in `server/app/` is fine. The **web entry point** is wrong or missing.

---

## Fix (choose ONE method)

### Method A — Recommended (pure Laravel)

**1. hPanel → Domains → resumesmaker.com → Document root**

Set to:

```text
public_html/server/public
```

(Full path on Hostinger is often:  
`/home/u690599082/domains/resumesmaker.com/public_html/server/public`)

**2. Upload missing file from your PC**

From local `laravel81/public/` upload to **`public_html/server/public/`**:

- **`index.php`** (required)
- **`laravel-resume-check.php`** (test URL)
- **`routes/web.php`** is under `server/routes/` — upload if you added deploy-fingerprint

**3. Stop Node.js** in hPanel (Advanced → Node.js).

**4. SSH**

```bash
cd ~/domains/resumesmaker.com/public_html/server
/opt/alt/php81/usr/bin/php artisan optimize:clear
/opt/alt/php81/usr/bin/php artisan route:clear
/opt/alt/php81/usr/bin/php artisan resume:verify-deploy
```

**5. Test in browser**

- `https://resumesmaker.com/laravel-resume-check.php`  
- `https://resumesmaker.com/resume/deploy-fingerprint`  

Both must return JSON with `"resume_engine": "laravel"`.

---

### Method B — Keep document root = `public_html` (no hPanel change)

Use the files in **`deploy/hostinger/`** in this repo:

**1.** Replace **`public_html/index.php`** with `deploy/hostinger/public_html-index.php` (rename to `index.php` on server).

**2.** Replace **`public_html/.htaccess`** with `deploy/hostinger/public_html.htaccess`.

**3.** Upload **`index.php`** into **`public_html/server/public/`** (still required).

**4.** Stop Node + run `artisan optimize:clear` as in Method A.

---

## Correct tree (after fix)

```text
public_html/
├── .htaccess              ← routes to Laravel (Method B) OR minimal (Method A)
├── index.php              ← only Method B: loads server/public/index.php
└── server/                ← Laravel project root
    ├── app/
    ├── routes/
    ├── resources/resume-builder/
    ├── vendor/
    ├── .env
    └── public/            ← document root for Method A
        ├── index.php      ← MUST EXIST
        ├── .htaccess
        ├── laravel-resume-check.php
        └── frontend/
```

---

## Permissions (SSH)

```bash
chmod -R 775 ~/domains/resumesmaker.com/public_html/server/storage
chmod -R 775 ~/domains/resumesmaker.com/public_html/server/bootstrap/cache
```

---

## Postman check (after fix)

`POST https://resumesmaker.com/resume/create`

**Good (Laravel):**

```json
"resume_engine": "laravel",
"mapper_version": "laravel-resume-mapper-v9"
```

**Bad (still broken):**

```json
{"status": true, "result": "1733....pdf"}
```

Remove `"debug_normalize": true` when you want a PDF file.

---

## Summary

1. **Upload `server/public/index.php`** — your main mistake today.  
2. **Point the domain to `server/public`** OR use **`deploy/hostinger/`** root files.  
3. **Stop Node.**  
4. Open **`laravel-resume-check.php`** to confirm Laravel is live.

No Node.js code is required for resume PDFs; only this Laravel app.
