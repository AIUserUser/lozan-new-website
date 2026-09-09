# Lozan (Laravel)

Bilingual boutique storefront for [lozan-kw.com](https://lozan-kw.com). Arabic by default, English at `/en`.

## Local (no PHP required)

Edit this repo and push to GitHub. The Oracle server pulls and runs PHP/MySQL.

```bash
git add -A
git commit -m "…"
git push origin main
```

On the server:

```bash
cd /var/www/lozan
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
sudo systemctl reload php-fpm httpd
```

## First-time server setup

- PHP 8.3, Composer, MySQL, php-fpm, Apache
- `.env` copied from `.env.example` (never commit `.env`)
- `php artisan key:generate`
- `php artisan migrate --force && php artisan db:seed --force`
- `php artisan lozan:import-supabase` (needs `SUPABASE_URL` + `SUPABASE_ANON_KEY`)
- Point Telegram webhook at `https://lozan-kw.com/telegram/webhook`

Admin login uses `ADMIN_EMAIL` / `ADMIN_PASSWORD` from `.env`.
