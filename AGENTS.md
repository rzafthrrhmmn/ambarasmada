# AGENTS.md — Project Guidelines

## Project Overview

**Project:** Sistem PWA Ambalan (Sistem Ekosistem Digital Kepramukaan Ambalan UPT SMAN 2 Maros)
**App Name:** AMBARA-SISTEM DIGITAL
**Production URL:** https://ambarasmada.vercel.app
**Framework:** Laravel 12 (PHP 8.2+) + Vue 3 + Inertia.js
**Build Tool:** Vite
**CSS Framework:** Tailwind CSS
**Database (prod):** PostgreSQL on Supabase
**Deployment:** Vercel (vercel-php@0.9.0 runtime)
**Testing:** PHPUnit (SQLite in-memory)

## Prerequisites

```sh
php -v      # >= 8.2
composer -V
node -v     # >= 20.x
npm -v
```

## Setup

```sh
composer install
npm install --ignore-scripts
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## Environment Variables

Key `.env` variables (see `.env.example`):

| Variable | Description |
|----------|-------------|
| `APP_NAME` | "AMBARA-SISTEM DIGITAL" |
| `APP_URL` | Production URL (https://ambarasmada.vercel.app) |
| `DB_CONNECTION` | pgsql (production), sqlite (testing) |
| `DB_HOST` | Supabase pooler host |
| `QUEUE_CONNECTION` | database (async jobs via Laravel Queue) |
| `MAIL_MAILER` | log (dev), smtp/brevo/resend (prod) |
| `FILESYSTEM_DISK` | local (dev), s3/cloud (prod) |

## Coding Standards

### Laravel (Backend)
- **PSR-12** compliant — enforced via `vendor/bin/pint --test`
- Controller methods return `Inertia::render()` for page views or JSON for API
- Use Eloquent ORM with explicit `fillable` / `hidden` attributes
- Apply `throttle:rate,minutes` middleware on all mutating routes
- Use Form Request validation classes for complex validation
- **No inline CSS** — use Tailwind classes via Blade/Inertia props

### Vue 3 (Frontend)
- Single File Components (`.vue`) with `<template>`, `<script setup>`, `<script>`
- Use Composition API with `ref`/`computed`/`onMounted`
- Inertia `Link` component for navigation, `router.visit()` for programmatic
- Props are typed: `defineProps({ propName: Object as PropType<Type> })`
- Tailwind CSS utility classes with project color scheme:
  - Background: `#263D26`, `#335233`
  - Primary: `#6F9435` (green), `#A7B92B` (light green)
  - Accent: `#EDD330` (yellow/gold)
  - Text: `#f0ead8` (light), `#8fa06a` (muted)

### Database
- Migrations in `database/migrations/` — follow pattern: `YYYY_MM_DD_HHMMSS_create_table_name_table.php`
- Use Supabase Postgres for production data
- Foreign key constraints where applicable

## Testing

```sh
# Run all tests
composer test

# Run specific test file
php artisan test --filter=EmailVerificationTest

# Run with coverage
php artisan test --coverage
```

### Test Files
- `tests/Unit/` — Unit tests
- `tests/Feature/` — Feature/integration tests
- `tests/Feature/SecurityAuditTest.php` — Role-based access control tests
- `tests/Feature/BlackboxWhiteboxTest.php` — Functional blackbox tests
- `tests/Feature/WhiteboxNewFeaturesTest.php` — Internal logic tests
- `tests/Feature/RouteErrorCheckTest.php` — Route access control tests
- `tests/Feature/EmailVerificationTest.php` — Auth flow verification tests

## Lint & Quality Checks

```sh
# PHP lint (single file)
php -l app/Http/Controllers/AuthController.php

# PHP Pint formatting (fix)
vendor/bin/pint

# PHP Pint check (CI)
vendor/bin/pint --test

# Frontend build (production)
npm run build

# Frontend dev (HMR)
npm run dev
```

## Git Workflow

```sh
git checkout -b feature/feature-name
git add .
git commit -m "feat: description of change"
git push origin feature/feature-name
npx vercel --prod --force --yes  # deploy to production
```

### Commit Message Convention
- `feat:` — new feature
- `fix:` — bug fix
- `refactor:` — refactoring
- `chore:` — tooling/config
- `docs:` — documentation
- `test:` — testing

**Example:** `git commit -m "feat: add dynamic QR code for attendance sessions"`

## Deployment

```sh
# Deploy to production on Vercel
npx vercel --prod --force --yes
```

### Vercel Configuration
- Runtime: `vercel-php@0.9.0`
- Build: `composer install && npm run build && php artisan optimize:clear`
- `.vercelignore` excludes: `node_modules/`, `vendor/`, `.kilo/`, `.env*`, `tests`, `*.pmtiles`, `file dokumen/`

## Authorization Model

| Middleware | Description |
|------------|-------------|
| `auth` | Must be logged in |
| `approved` | User `status` must be `approved` (not `pending`) |
| `role:Admin\|Pembina\|...` | Role must match one of specified roles |
| `juru_uang` | Sub-role check for finance access |

### Role Hierarchy
1. **Admin** — full access to all modules
2. **Pembina** — approve members, all read access
3. **Pengurus** — manage events, attendance, inventory, announcements
4. **Juru Uang** (sub-role Pengurus) — finance management access
5. **Anggota** — participation, own data, SKU submissions
6. **Alumni** — alumni portal (donations, profile, directory)

## Common Commands

```sh
# Generate model + migration
php artisan make:model Member -m

# Generate controller
php artisan make:controller MemberController --resource

# Generate Form Request
php artisan make:request StoreMemberRequest

# Generate Inertia page
php artisan inertia:maker Member/Index

# Clear caches
php artisan optimize:clear

# Run migrations
php artisan migrate

# Rollback
php artisan migrate:rollback
```

## Key File Structure

```
app/
  Http/Controllers/     # 37 controllers (one per domain module)
  Http/Middleware/      # Auth, Role, Approval, Security headers, Cookie guard
  Http/Requests/        # Form Request validation classes
  Models/               # 30+ Eloquent models
  Providers/            # AppServiceProvider
resources/
  js/
    Components/         # Reusable Vue components (AppLayout, FlashMessage, Modal, etc.)
    Pages/             # Inertia page components (25+ directories)
    views/             # Blade templates (app.blade.php, report PDFs, letter PDF)
  css/app.css
  views/app.blade.php   # Root HTML template
routes/
  web.php              # All routes (328 lines, 100+ routes)
database/
  migrations/          # Schema migrations
  seeders/             # Demo data seeders
tests/
  Feature/             # Integration tests
  Unit/                # Unit tests
public/
  sw.js               # PWA service worker
  manifest.webmanifest
  offline.html
```
