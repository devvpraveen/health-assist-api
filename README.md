# Health Assist API

Laravel backend for **Health Assist**: multi-tenant healthcare platform APIs (patients, clinics, appointments, billing, AI Health Guide, Report AI, marketing, WhatsApp).

- **Repo:** [devvpraveen/health-assist-api](https://github.com/devvpraveen/health-assist-api)
- **Monorepo path:** `apps/api` in [health-assist](https://github.com/devvpraveen/health-assist)
- **API base:** `/api/v1`
- **Stack:** Laravel 13, PHP 8.3+, Sanctum, Fortify, SQLite (local) / MySQL (prod)

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
