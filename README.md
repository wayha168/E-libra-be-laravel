<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# E-libra backend

## After `git pull` (styles + `/docs/api`)

`public/build` (Vite CSS/JS) is **not** in git. Scramble API docs are also **cached**.  
A plain `git pull` will not refresh either — run one of:

```bash
composer refresh
```

Or:

```powershell
.\scripts\refresh-after-pull.ps1
```

```bash
bash scripts/refresh-after-pull.sh
```

Then hard-refresh the browser (`Ctrl+F5`). API docs: `/docs/api`.

### Docker note

`docker-compose.yaml` bind-mounts the project (`./:/var/www/html`), so the image’s built assets are overridden by the host. Always run `composer refresh` (or the script) on the host after pull. Rebuild the image only if you deploy without that volume mount.

MySQL is **not** published on host `:3306` (avoids conflict with system MySQL). The app uses `DB_HOST=db`. To connect from the host, map `3307:3306` in compose.

## Live notifications (Telegram vs dashboard)

Telegram alerts are plain HTTP and work without WebSockets.

In-app toast / bell / badge need **Laravel Reverb**:

```bash
# local (also included in `composer dev`)
php artisan reverb:start
```

Docker Compose includes a `reverb` service on port **8080**.

Production (`https://elibra.skinme.store`): run Reverb on the server and proxy WebSockets through nginx, e.g.:

```nginx
location /app {
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_pass http://127.0.0.1:8080;
}
```

Then set:

```env
BROADCAST_CONNECTION=reverb
REVERB_HOST=elibra.skinme.store
REVERB_PORT=443
REVERB_SCHEME=https
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

Rebuild assets (`npm run build`) after changing `VITE_*`. Keep the dashboard open as **admin/author** to see the bell; polling still refreshes every ~5s if WebSocket is down.

### Production console errors (common)

**1. `GET /storage/uploads/... 404`**  
URL path is correct; the **file is missing on the server** and/or `public/storage` symlink is missing.

```bash
cd /var/www/E-libra-be-laravel
bash scripts/fix-production-storage-reverb.sh
# or:
php artisan storage:link --force
ls -la public/storage
ls storage/app/public/uploads/profile
```

Uploads done on your PC are **not** in git. Copy them or re-upload on production:

```bash
# from your PC (example)
scp -r storage/app/public/uploads root@YOUR_SERVER:/var/www/E-libra-be-laravel/storage/app/public/
```

**2. `WebSocket connection to wss://elibra.skinme.store/app/... failed`**  
Browser is correct (WSS on `/app`). Reverb is not running or nginx is not proxying.

```bash
# install + start Reverb service
cp deploy/reverb.service /etc/systemd/system/elibra-reverb.service
# edit WorkingDirectory if your path differs
systemctl daemon-reload
systemctl enable --now elibra-reverb
systemctl status elibra-reverb
ss -tlnp | grep 8080
```

Add `location /app { ... }` from `deploy/nginx-elibra.conf.example` into the site config, then reload nginx.

Production `.env` (then rebuild assets):

```env
BROADCAST_CONNECTION=reverb
REVERB_HOST=elibra.skinme.store
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=elibra.skinme.store
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
VITE_REVERB_ENABLED=true
```

```bash
npm run build
php artisan config:clear
systemctl reload nginx
```


