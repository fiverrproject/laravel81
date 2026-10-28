# Hostinger Deploy (PHP 8.1)

This folder (`laravel81`) is Laravel **10** and works with **PHP 8.1**.

Do **not** upload the old `laravel/` folder (that is Laravel 12 / PHP 8.2).

## 1) hPanel PHP version

Keep **PHP 8.1** selected (as you already did).

## 2) Upload code

Upload the contents of `laravel81/` to your server folder, e.g.:

`~/domains/resumemaker.com/public_html/server`

Document root / domain should point to the `public` folder (or use a subdomain that maps to `public`).

## 3) Important: SSH uses different PHP

hPanel can be PHP 8.1, but SSH `php` may still be **7.3**.  
Always run Composer with **PHP 8.1**:

```bash
cd ~/domains/resumemaker.com/public_html/server

# Find PHP 8.1 binary (try these)
php81 -v
# or
/opt/alt/php81/usr/bin/php -v
# or
ls /opt/alt | grep php
```

Then install:

```bash
# Option A
php81 /usr/local/bin/composer2 install --no-dev --optimize-autoloader

# Option B
/opt/alt/php81/usr/bin/php /usr/local/bin/composer2 install --no-dev --optimize-autoloader
```

If `ext-sodium` warning appears:

```bash
php81 /usr/local/bin/composer2 install --no-dev --ignore-platform-req=ext-sodium
```

## 4) .env

```bash
cp .env.example .env
php81 artisan key:generate
```

Set at least:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

OPENAI_API_KEY=your_key
CHROME_PATH=/usr/bin/google-chrome
```

## 5) Permissions

```bash
chmod -R 775 storage bootstrap/cache
chmod -R 775 public/festival public/statusmaker public/invitation_img public/invitation_pdf public/resume-web
```

## 6) Upload resume templates (required)

Templates live in `resources/resume-builder/` (all `resume*.html` files).

After deploy, on SSH:

```bash
cd ~/domains/resumemaker.com/public_html/server
/opt/alt/php81/usr/bin/php artisan resume:sync-templates
```

Or upload the whole folder from your PC:

`laravel81/resources/resume-builder/` → `public_html/server/resources/resume-builder/`

## 7) Stop old Node API (fixes blank Skills / Education on server)

If **local Laravel works** but **server PDF shows empty Skills, Education, Language, Interests, Projects** while Experience `description` still shows, the server is almost certainly still running the **old Node** app. Node passes raw JSON (`name`, `degree`, `language`) but templates need mapped keys (`skillName`, `course`, `hoby`).

**Check Postman response after** `POST /api/resume/create`:

| Response | Meaning |
|----------|---------|
| Only `{"status":true,"result":"123.pdf"}` | **Node** — stop it |
| Includes `"resume_engine":"laravel"` and `"mapper_version":"laravel-resume-mapper-v7"` | **Laravel** — mapping is active |

Actions:

1. In Hostinger, **stop Node/PM2** (anything running `index.js` on port 3000).
2. Point the domain to **`public_html/server/public`** (or use root `index.php` that requires `server/public/index.php`).
3. Upload these Laravel files (minimum):
   - `app/Services/ResumeTemplateDataMapper.php`
   - `app/Services/HandlebarsService.php`
   - `app/Http/Controllers/ResumeBuilderController.php`
   - `app/Providers/AppServiceProvider.php`
4. Clear cache: `/opt/alt/php81/usr/bin/php artisan optimize:clear`

Quick test:

```bash
curl -sS https://your-domain.com/api/resume/mapper-check
curl -sS -X POST https://your-domain.com/api/test-api
```

## 8) Test resume create

```bash
curl -X POST https://your-domain.com/api/resume/create \
  -H "Content-Type: application/json" \
  -d '{"format":"resume25.html","debug_normalize":true,"Personal Details":{"Name":"Test","Email":"a@b.com","Phone":"1","Address":"x","jobTitle":"Dev"},"Skills":[{"name":"PHP","expertise":"90"}],"Education":[{"degree":"BSc","institution":"Uni","startYear":"2013","endYear":"2017"}],"Objective":{"Objective":"Hi"}}'
```

You should see `mapped.Skills[0].skillName` = `PHP`.

## Note about PDF (Chrome)

PDF/image APIs need Chrome/Chromium + Node (Puppeteer).  
Shared hosting often blocks this. If PDF fails after Composer succeeds, you need Chrome on the server (VPS) or keep PDF generation on a VPS/Node server.
