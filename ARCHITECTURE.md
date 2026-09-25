# Architecture

## Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                          User Browser                                │
│                    (Chrome / Safari / Mobile)                        │
└──────────────┬──────────────────────────────┬───────────────────────┘
               │ HTTP(S)                        │
               ▼                                ▼
┌────────────────────────────┐   ┌──────────────────────────────┐
│       Static Assets         │   │     Laravel (vercel-php)      │
│  (Vite: JS, CSS, Fonts)     │   │  PHP 8.2+ / Laravel 12        │
│  CDN via Vercel Edge        │   │  • Routing (web.php)          │
│                             │   │  • Controllers (37 modules)    │
│                             │   │  • Eloquent Models            │
│                             │   │  • Middleware                 │
│                             │   │  • Queue (database driver)    │
│                             │   │  • Mail (SMTP queue)          │
└────────────────────────────┘   └──────────────┬─────────────────┘
                                                │
                                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    PostgreSQL (Supabase)                            │
│  • Users, Members, Angkatan                                        │
│  • Attendance, Events, Meetings, SKU/TKU                           │
│  • Assessments, Certificates                                       │
│  • Finance, Inventory, Letters, Gallery, Teams                    │
│  • Notifications, Reminders, Audit Logs                           │
│  • System Points, Webhooks, Backups, Health & Safety              │
└─────────────────────────────────────────────────────────────────────┘
```

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Vue 3 + Inertia.js 3.x |
| Build | Vite 5, Tailwind CSS 3 |
| Styling | Tailwind utility classes + custom CSS (app.css) |
| Icons | Inline SVG (defined in AppLayout.vue) |
| Routing | Inertia.js client-side + Laravel server-side |
| Auth | Laravel Sanctum-style session auth + `MustVerifyEmail` |
| Database | PostgreSQL (Supabase), SQLite (testing) |
| Queue | Laravel Queue (database driver) |
| Mail | SMTP / Brevo / Resend / Amazon SES |
| File Storage | Local (dev) → Cloud (S3/Supabase/Cloudinary) (prod) |
| Maps | MapLibre GL JS + PMTiles (offline tile support) |
| PWA | Laravel-PWA compatible service worker |
| Deployment | Vercel (vercel-php@0.9.0) |
| Testing | PHPUnit 11.5, Faker |
| Formatting | PHP Pint |
| API Security | Rate limiting, CSRF, RBAC middleware |

## Architecture Style

**Monolithic Laravel + Inertia SPA Hybrid**

- Single Laravel backend serves all HTTP requests
- Inertia.js bridges Laravel controllers to Vue 3 components without API endpoints
- Controllers pass props via `Inertia::render()` — no separate REST API for frontend data
- Route-based authorization middleware enforces access control
- Queue handles background jobs (email, heavy tasks)

## Request Flow

```
Browser Request
  → routes/web.php (middleware stack applies)
    → EnsureAuthenticated? → EnsureApproved? → EnsureRole?
      → Controller method
        → Eloquent Model (query/update)
        → Queue::push() (for emails/background jobs)
        → Inertia::render('PageName', [props])
          → Return HTML/Props via vercel-php
            → Browser renders Vue component
```

### Example: Dashboard Access
```
GET /dashboard
  → auth middleware (must be logged in)
  → approved middleware (status === 'approved')
  → DashboardController::__invoke()
    → Inertia::render('Dashboard', [
        'stats' => [...],
        'events' => [...],
        'attendance' => [...]
    ])
  → Response: HTML + JSON props → Vue renders dashboard
```

## Middleware Chain

| Order | Middleware | File | Purpose |
|-------|-----------|------|---------|
| 1 | `auth` | `app/Http/Middleware/Authenticate.php` | Login required |
| 2 | `approved` | `EnsureApproved.php` | User status must be 'approved' |
| 3 | `role:Admin, ...` | `EnsureRole.php` | Role-based access |
| 4 | `juru_uang` | `EnsureJuruUang.php` | Finance sub-role check |
| 5 | `cookie.guard` | `CookieOverflowGuard.php` | Prevents cookie overflow 431 errors |
| 6 | `security.headers` | `SecurityHeaders.php` | HSTS, XSS protection, etc. |

### Auth & Approval Flow
```
1. User registers → email verification sent → MustVerifyEmail enforced
2. User clicks email verification link
3. User can login but status = 'pending'
4. EnsureApproved middleware redirects to /pending-approval
5. Pembina approves user → status changes to 'approved'
6. User can now access all authorized features
```

## Directory Structure

```
app/
├── Http/
│   ├── Controllers/          # 37 controllers — one per domain module
│   │   ├── AttendanceController.php
│   │   ├── FinanceController.php
│   │   ├── EventController.php
│   │   ├── MeetingController.php
│   │   ├── MemberController.php
│   │   └── ... (30+ more)
│   ├── Middleware/
│   │   ├── EnsureRole.php
│   │   ├── EnsureApproved.php
│   │   ├── EnsureJuruUang.php
│   │   ├── CookieOverflowGuard.php
│   │   ├── SecurityHeaders.php
│   │   └── HandleInertiaRequests.php
│   ├── Requests/             # Form Request validation classes
│   └── Kernel.php
├── Models/                   # 30+ Eloquent models
│   ├── User.php
│   ├── Member.php
│   ├── AttendanceSession.php
│   ├── Event.php
│   ├── Finance.php
│   └── ...
├── Providers/
│   └── AppServiceProvider.php
resources/
├── js/
│   ├── Components/           # Reusable Vue components
│   │   ├── AppLayout.vue     # Main layout (header, sidebar, main)
│   │   ├── FlashMessage.vue
│   │   ├── Modal.vue
│   │   ├── Pagination.vue
│   │   ├── StatCard.vue
│   │   ├── SkeletonLoader.vue
│   │   ├── UpcomingActivities.vue
│   │   └── ... (10+ components)
│   ├── Pages/                # Inertia page components (25+ directories)
│   │   ├── Alumni/Dashboard.vue
│   │   ├── Announcements/Index.vue
│   │   ├── Articles/Index.vue
│   │   ├── Assessments/{Index,Show}.vue
│   │   ├── Attendance/{Index,Show,Scan}.vue
│   │   ├── Auth/{Login,PendingApproval}.vue
│   │   ├── Certificates/Index.vue
│   │   ├── Events/{Index,Show}.vue
│   │   ├── FieldGuides/Index.vue
│   │   ├── Finance/Index.vue
│   │   ├── Galleries/Index.vue
│   │   ├── HealthSafety/{HealthRecords,SafetyChecks}.vue
│   │   ├── Inventory/Index.vue
│   │   ├── Letters/{Index,Show,Print,Templates}.vue
│   │   ├── Materials/{Index,Show}.vue
│   │   ├── Meetings/{Index,Show}.vue
│   │   ├── Members/{Index,AngkatanIndex,PendingUsers}.vue
│   │   ├── Notifications/Index.vue
│   │   ├── Peta/{Index,MapDenganPencarian}.vue
│   │   ├── Profile/Show.vue
│   │   ├── Reports/Index.vue
│   │   ├── Sku/Index.vue
│   │   ├── SystemPoints/Index.vue
│   │   ├── SystemTools/{Backups,Webhooks}.vue
│   │   ├── Teams/Index.vue
│   │   ├── Trainings/Index.vue
│   │   └── Dashboard.vue     # Main dashboard
│   └── views/
│       └── reports/          # Blade templates for PDF views
│           ├── attendance.blade.php
│           ├── finance.blade.php
│           └── sku.blade.php
├── css/
│   └── app.css              # Tailwind + custom keyframes
└── views/
    ├── app.blade.php         # Root HTML template (Inertia mount point)
    └── welcome.blade.php     # Landing page

routes/
├── web.php                   # All application routes (328 lines)
└── console.php              # Artisan commands

database/
├── migrations/              # Schema migrations
├── seeders/                 # Demo data (DemoUsersSeeder, SkuPenegakPointSeeder, etc.)
└── schema/                  # SQL schema reference

public/
├── sw.js                   # PWA service worker
├── manifest.webmanifest
├── index.php              # Vercel PHP entry point
├── offline.html           # Offline fallback page
└── storage/maps/          # Map tiles (.pmtiles, .geojson)

tests/
├── Feature/
│   ├── SecurityAuditTest.php
│   ├── BlackboxWhiteboxTest.php
│   ├── WhiteboxNewFeaturesTest.php
│   ├── RouteErrorCheckTest.php
│   ├── EmailVerificationTest.php
│   └── ExampleTest.php
└── Unit/

config/                      # Laravel + application config
```

## Database Schema (Key Tables)

### Users & Members
- `users` — authentication (email, password, role, status, email_verified_at)
- `members` — scout-specific data (Nama, Pangku, Gatol, etc.)
- `angkatan` — academic years/batches
- `member_positions` — role assignments within gugus

### Core Activity Modules
- `attendance_sessions` — presensi sessions with QR code token
- `attendances` — individual attendance records
- `events` — kegiatan with participant tracking
- `event_participants` — event attendees
- `meetings` — permusyawaratan with agenda/voting
- `meeting_agendas`, `meeting_minutes`, `meeting_votes`, `meeting_attendees`

### SKU/TKU
- `sku_submissions` — pengajuan SKU
- `sku_points` — individual points
- `tkk_points` — TKK point submissions

### Documents & Content
- `certificates` — sertifikat
- `field_guides` — buku saku
- `articles` — blog posts
- `galleries` — photo gallery
- `letters` — surat with templates
- `letter_templates` — letter format templates
- `learning_materials` — materi PDF
- `ambalan_media` — Mars/Rain media files
- `reminders` — pengumuman/pengingat

### Organizations
- `teams` — gugus depan
- `team_members` — anggota gugus
- `team_tasks` — tugas gugus

### Finance
- `finances` — financial transactions
- `finance_categories` — kategori pengeluaran/pemasukan
- `finance_periods` — periode buku besar
- `donations` — alumni donasi

### Inventory
- `inventories` — barang inventaris
- `inventory_loans` — peminjaman
- `inventory_movements` — log pergerakan stok

### System
- `notifications` — in-app notifications
- `audit_logs` — user activity logs
- `user_permissions` — custom permission management
- `system_points` — poin sistem
- `backups` — backup records
- `webhooks` — webhook endpoints
- `health_records` — rekam kesehatan
- `safety_checks` — pengecekan keselamatan
- `candidates` — calon pengurus/bendahara

### Alumni
- `alumni_profiles` — alumni career info

## PWA Architecture

```
public/sw.js
  ├── On install: Cache core assets (CSS, JS, HTML)
  ├── On activate: Remove old cached files
  ├── On fetch: Serve from cache, fallback to network, then offline.html
  └── Push events: Display notifications from PWA devices

resources/js/app.js
  ├── Cookie overflow protection (431 error prevention)
  ├── Inertia router with progress bar (#6F9435 color)
  ├── Toast notifications (vue-toastification)
  └── PWA install prompt handler
```

### Offline Capability (Limited)
- Static assets cached on install
- Previously-viewed pages cached via service worker
- Form submissions deferred when offline (future enhancement)
- Static content (Field Guides, Materials) cacheable for offline reading

## Deployment Architecture

```
Developer ──┐
             ├── git push ──→ GitHub repo
Production ──┤
             └── vercel CLI ──→ Vercel
                              ├── vercel.json (PHP runtime config)
                              ├── Build Phase:
                              │   1. Composer install (PHP deps)
                              │   2. npm run build (Vite/Vue/Tailwind)
                              │   3. php artisan optimize:clear
                              ├── Serverless Function: Laravel app
                              ├── Edge Cache: Static assets
                              └── Database: Supabase PostgreSQL
```

### Vercel Configuration
- Runtime: `vercel-php@0.9.0`
- Memory: 1024MB
- Regions: Washington D.C. (iad1)
- `.vercelignore` excludes:
  - `node_modules/`, `vendor/`, `.kilo/`, `.env*`
  - `tests`, `*.pmtiles`, `file dokumen/`
  - `supabase/`, `coverage/`, `.git/`

## Component Architecture

### Layout Hierarchy
```
AppLayout.vue
├── Header (sticky, 4.25rem height)
│   ├── Logo + App Name
│   ├── Mobile menu toggle (lg:hidden)
│   └── User actions (role badge, install PWA, logout)
├── Desktop Sidebar (lg:block, 15rem width)
│   ├── Navigation links (role-filtered)
│   └── "Siap berlatih?" tip card
├── Mobile Drawer (lg:hidden, slide-in)
│   ├── Navigation links
│   ├── User profile card
│   └── Tip card
├── Main Content Area
│   ├── FlashMessage
│   └── <slot /> (page component)
└── Mobile Bottom Nav (lg:hidden)
    └── Quick nav links (7 items, role-filtered)
```

### Page Component Pattern
All page components follow this structure:
```vue
<template>
  <div class="[page-specific-layout]">
    <!-- Header with title and action buttons -->
    <!-- Content grid -->
    <!-- Modals for create/edit -->
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
// Data, computed props, form handlers
</script>
```

## Error Handling

- **431 Errors:** `CookieOverflowGuard` middleware clears stale cookies
- **403 Errors:** Role not authorized → redirect to dashboard
- **404 Errors:** Route not found → Inertia 404 page
- **500 Errors:** Laravel exception handler → error page
- **Rate Limit:** Laravel throttle middleware → HTTP 429 response
