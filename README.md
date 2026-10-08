# DrunkDrive (Laravel API)

Ride / driver backend on this repository’s **Laravel + Sanctum + Eloquent** stack.
DrunkDrive is a clean domain under `/api/v1/drunkdrive` (E-libra book APIs remain under `/api/v1`).

Full route reference: [`docs/drunkdrive.md`](docs/drunkdrive.md).

## Stack

- PHP 8.3, Laravel 13, Sanctum (Bearer JWT-like tokens)
- MySQL (Docker Compose) or SQLite for local/dev (`.env.example` defaults to SQLite)
- Role middleware: `user`, `driver`, `admin`, `super_admin` (product name **superadmin** is aliased)

## Quick start

```bash
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite   # if using sqlite
php artisan migrate --seed
php artisan storage:link         # licence scan URLs
php artisan serve                # http://127.0.0.1:8000
```

Docker (MySQL + app):

```bash
docker compose up -d --build
# app uses DB_HOST=db; see docker-compose.yaml
```

Run DrunkDrive-focused tests:

```bash
php artisan test --filter=DrunkDrive
```

### Seed logins (password: `password`)

| Email | Role |
|---|---|
| `superadmin@drunkdrive.test` | super_admin |
| `admin@drunkdrive.test` | admin |
| `user@drunkdrive.test` | user |

## Fare model

Anchors from product: **1 km → 2000 R**, **5 km → 5000 R**.

“Km^2” is interpreted as distance-based pricing; a pure quadratic through the origin cannot satisfy both anchors, so fare is the **linear fit**:

```text
fare_R(d) = round(1250 + 750 × d)     # d in km
# same as: round(2000 + 750 × max(0, d − 1))
```

- `GET /api/v1/drunkdrive/fare/formula`
- `POST /api/v1/drunkdrive/fare/quote` with `distance_km` or pickup/dropoff coordinates

## API summary

| Area | Paths |
|---|---|
| Auth | `POST .../auth/register`, `.../register-driver`, `.../login`, `GET .../auth/me` |
| Licence | driver register + `POST/PUT .../driver/licence` (scan upload and/or manual; OCR stub) |
| Stores | `GET/POST/PUT/DELETE .../stores` (write: admin/superadmin) |
| Nearby | `GET .../drivers/nearby?latitude=&longitude=` |
| Fare | `GET .../fare/formula`, `POST .../fare/quote` |
| Trips | `POST .../trips`, `GET .../trips/history`, `PATCH .../trips/{id}/status` |

Use header: `Authorization: Bearer {token}` and `Accept: application/json`.

## Licence scan

Drivers may upload a licence photo/PDF (`licence_scan`) and/or enter fields manually.
OCR is optional/stubbed (`licence_ocr_stub`); files land in `storage/app/public/drunkdrive/licences`.

---

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


