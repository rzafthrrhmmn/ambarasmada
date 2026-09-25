<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

## Lint & Test Commands

Always run lint and tests before committing:

- **PHP Lint**: `php -l <file>` for individual files; `vendor/bin/pint --test` for full PHP formatting check
- **Build**: `npm run build` (Vite frontend build)
- **Test**: `composer test` (runs php artisan test with phpunit.xml, SQLite in-memory database)
- **Deploy**: `npx vercel --prod --force --yes`

## Project Notes

- Roles: `Admin`, `Pembina`, `Pengurus`, `Anggota`, `Alumni` (plus `Juru Uang` sub-role for finance access)
- Authorization: `EnsureRole` middleware (`role:` alias) and `EnsureApproved` middleware
- Frontend: Vue 3 + Inertia + Vite + Tailwind CSS
- Deployment: Vercel with `vercel-php@0.9.0` runtime; excludes `vendor/`, `.kilo/`, large `.pmtiles` files
- `.vercelignore` must exclude large files to stay under 100MB Hobby plan limit
- Production deploy requires Vercel Functions Storage quota (10 GB limit)
