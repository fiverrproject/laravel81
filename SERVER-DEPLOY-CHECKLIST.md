# Server blank Skills / Education — fix checklist

Local Laravel works. Server PDF shows **Experience description only** and empty Skills, Education, Language, etc.

That pattern means **Postman JSON is reaching the template without field mapping** (same as old **Node.js** `index.js`).

Laravel code is correct locally. The server is either **not running that code** or **missing files**.

---

## Step 1 — Postman (mandatory, 10 seconds)

`POST https://YOUR-DOMAIN/api/resume/create`  
Header: `Content-Type: application/json`  
Body: your JSON (add `"debug_normalize": true` to skip PDF).

### Wrong (Node or old API) — PDF will stay broken

```json
{
  "status": true,
  "result": "1793097165622.pdf"
}
```

Only 2 keys = **not Laravel**. Uploading Laravel files does nothing until this changes.

### Correct (Laravel)

```json
{
  "status": true,
  "resume_engine": "laravel",
  "mapper_version": "laravel-resume-mapper-v7",
  "debug_first_skill": "PHP / Laravel",
  "debug_education_course": "Bachelor of Science in Computer Science",
  "mapped": { ... }
}
```

If you do not see `resume_engine`, stop Node and fix routing (Step 3).

---

## Step 2 — Upload these files (PC → `public_html/server/`)

| # | Upload from PC (`laravel81/`) | To server |
|---|------------------------------|-----------|
| 1 | `app/Services/ResumeTemplateDataMapper.php` | same path |
| 2 | `app/Services/ResumeTemplatesInstaller.php` | same path |
| 3 | `app/Services/HandlebarsService.php` | same path |
| 4 | `app/Services/ResumeDataNormalizer.php` | same path |
| 5 | `app/Http/Controllers/ResumeBuilderController.php` | same path |
| 6 | `app/Providers/AppServiceProvider.php` | same path |
| 7 | `app/Console/Commands/VerifyResumeDeploy.php` | same path |
| 8 | `app/Console/Commands/SyncResumeTemplates.php` | same path |
| 9 | `routes/api.php` | same path |
| 10 | `routes/web.php` | same path |
| 11 | **Whole folder** `resources/resume-builder/` | same path |

Missing **#1** → site error `Class ResumeTemplateDataMapper not found`.  
Missing **#11** → wrong or no templates.

After upload, SSH:

```bash
cd ~/domains/resumemaker.com/public_html/server
/opt/alt/php81/usr/bin/php artisan optimize:clear
/opt/alt/php81/usr/bin/php artisan resume:verify-deploy
/opt/alt/php81/usr/bin/php artisan resume:sync-templates
```

All lines must **PASS**.

---

## Step 3 — Stop Node + point domain to Laravel

On Hostinger you had:

- `public_html/server/` = Laravel
- `public_html/index.php` = must load Laravel, not Node

**Stop** anything running:

- `node index.js`
- PM2 process on port **3000**
- Old `public_html` Node app

**Document root** must be:

`public_html/server/public`

**Or** `public_html/index.php` contains only:

```php
<?php
require __DIR__.'/server/public/index.php';
```

---

## Step 4 — After one API call, check File Manager

Open: `public_html/server/public/LAST_MAP.json`

Must contain:

- `"resume_engine": "laravel"`
- `"mapper_version": "laravel-resume-mapper-v7"`
- `"Skills": [ { "skillName": "PHP / Laravel", ... } ]`

If **LAST_MAP.json does not exist** → request never hit this Laravel `public/` folder.

---

## Step 5 — URL must match Laravel routes

Use exactly:

| Method | URL |
|--------|-----|
| POST | `https://resumemaker.com/api/resume/create` |
| GET | `https://resumemaker.com/api/resume/mapper-check` |

Not `resumesmaker.com` unless that domain also points to the same Laravel `server/public`.

---

## You do NOT need to edit 45 HTML templates

Laravel maps `name` → `skillName`, `degree` → `course`, etc. in PHP.

HTML changes are optional; **routing + files above** are required.
