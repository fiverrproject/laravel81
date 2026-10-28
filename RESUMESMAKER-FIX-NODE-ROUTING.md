# resumesmaker.com — route traffic to Laravel (not Node)

**Use the full guide:** [PURE-LARAVEL-HOSTINGER.md](./PURE-LARAVEL-HOSTINGER.md)

Quick proof after document root = `server/public`:

- `https://resumesmaker.com/laravel-resume-check.php` → must show `"resume_engine":"laravel"`
- Postman `/resume/create` → must **not** be only `{"status":true,"result":"....pdf"}`

