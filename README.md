# Health Assist API

Laravel backend for **Health Assist**: multi-tenant healthcare platform APIs (patients, clinics, appointments, billing, AI Health Guide, Report AI, marketing, WhatsApp).

- **Repo:** [devvpraveen/health-assist-api](https://github.com/devvpraveen/health-assist-api)
- **Monorepo path:** `apps/api` in [health-assist](https://github.com/devvpraveen/health-assist)
- **API base:** `/api/v1`
- **Stack:** Laravel 13, PHP **8.3+** (Hostinger: set PHP 8.3 or 8.4 in hPanel), Sanctum, Fortify, SQLite (local) / MySQL (prod)
- **Note:** `composer.lock` targets Symfony 7.4 so `composer install` works on PHP 8.3. A lock built on PHP 8.4 alone can pull Symfony 8.1 and fail Hostinger with “lock file does not contain a compatible set of packages.”

AI assists clinicians and patients; it does **not** prescribe or auto-approve clinical content.

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+ (Vite / Inertia staff assets)
- SQLite or MySQL

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # if using SQLite
php artisan migrate --seed
npm install && npm run build
```

Or: `composer run setup` (install, env, migrate, npm build).

## Run

```bash
composer run dev
```

- App / Inertia staff UI: [http://localhost:8000](http://localhost:8000)
- JSON API: [http://localhost:8000/api/v1](http://localhost:8000/api/v1)

## Frontend assets (Vite)

Inertia UI needs `public/build/manifest.json`. Locally:

```bash
npm install
npm run build
```

`public/build` is committed so Hostinger git deploys work without a Node build step. After changing `resources/js` or CSS, rebuild and push again.

### Hostinger (SSH fallback)

If the Vite error appears on the server:

```bash
cd ~/domains/YOUR_DOMAIN/public_html   # or your app root
npm install
npm run build
php artisan optimize:clear
```

Document root must be the Laravel `public/` folder (not the project root).

## Demo accounts

After `migrate --seed` (password for all: `password`):

| Email | Role |
|-------|------|
| `super@healthassist.test` | Platform super admin |
| `admin@healthassist.test` | Clinic / org admin |
| `provider@healthassist.test` | Provider |
| `patient@healthassist.test` | Patient |

Demo tenant slug: `healthassist-demo` (`X-Tenant-Slug` on tenant-scoped public APIs).

## Useful commands

```bash
php artisan test
composer run lint
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=SeoContentSeeder
```

## Key env

| Variable | Notes |
|----------|--------|
| `AI_PROVIDER` | `mock` (default/CI) or `openai_compatible` |
| `BLOCKCHAIN_VERIFICATION_ENABLED` | Keep `false` unless shipping verification |
| `WHATSAPP_DRIVER` | `evolution` + Evolution URL/key for WhatsApp |

## Related apps

| App | Repo | Port |
|-----|------|------|
| Patient / public web | [health-assist-web](https://github.com/devvpraveen/health-assist-web) | 3000 |
| Platform admin | [health-assist-admin](https://github.com/devvpraveen/health-assist-admin) | 3002 |

From the monorepo root you can sync this repo with:

```bash
./push api "your message"
```
