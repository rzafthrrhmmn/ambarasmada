# Project Skills

This document defines the specialized **Skills** that can be loaded when working on the Sistem PWA Ambalan project. Each skill provides targeted instructions, workflows, and context for specific types of development tasks.

## Available Skills

### 1. Laravel Backend Development

**When to use:** Tasks involving server-side logic, database operations, API endpoints, middleware, or Laravel-specific features.

**Scope:**
- Controllers (`app/Http/Controllers/*.php`)
- Models (`app/Models/*.php`)
- Middleware (`app/Http/Middleware/*.php`)
- Database migrations (`database/migrations/`)
- Eloquent relationships and queries
- Route definitions (`routes/web.php`)
- Form Request validation
- Laravel Queues and Jobs
- File storage and uploads

**Key Files:**
- `routes/web.php` — All application routes
- `app/Http/Middleware/EnsureRole.php` — Role-based access
- `app/Http/Middleware/EnsureApproved.php` — Approval status check
- `app/Models/User.php` — User model with MustVerifyEmail
- `app/Models/Member.php` — Scout member data

**Conventions:**
- Use `Inertia::render()` for page controllers
- Return JSON (`response()->json()`) only for AJAX/API endpoints
- Apply `throttle:rate,minutes` middleware on all mutating routes
- Use Form Request classes for validation
- Rate limiting: 5/min login, 10/min register, 10-20/min data mutations

---

### 2. Vue 3 + Inertia Frontend Development

**When to use:** Tasks involving UI components, page views, client-side interactivity, or state management.

**Scope:**
- Page components (`resources/js/Pages/**/*.vue`)
- Reusable components (`resources/js/Components/*.vue`)
- Tailwind CSS styling
- Inertia.js props passing and router usage
- Form handling with Inertia
- PWA features (service worker, offline)

**Key Files:**
- `resources/js/app.js` — App bootstrap, Inertia setup, Toast, Cookie guard
- `resources/js/Components/AppLayout.vue` — Shared layout (header, sidebar, main)
- `resources/js/Components/FlashMessage.vue` — Flash notification display
- `resources/js/Components/Modal.vue` — Modal component
- `resources/css/app.css` — Tailwind config + custom keyframes

**Conventions:**
- Use `<script setup>` for Composition API
- Use `Inertia::render()` props via `defineProps()`
- Tailwind color scheme: `#263D26` (bg), `#335233` (card bg), `#6F9435` (primary), `#A7B92B` (accent), `#EDD330` (highlight), `#f0ead8` (text), `#8fa06a` (muted)
- Desktop layout: sidebar 15rem + main content, 50px edge padding
- Mobile: bottom nav + drawer menu

---

### 3. Database & Schema Management

**When to use:** Tasks involving migrations, schema changes, database seeders, or data modeling.

**Scope:**
- Migration files (`database/migrations/`)
- Database seeders (`database/seeders/`)
- Eloquent model relationships
- PostgreSQL-specific considerations (Supabase)
- Index optimization

**Key Files:**
- `database/seeders/DemoUsersSeeder.php`
- `database/seeders/SkuPenegakPointSeeder.php`
- `database/seeders/LearningMaterialSeeder.php`
- `database/seeders/PengurusPositionSeeder.php`

**Conventions:**
- Production: PostgreSQL on Supabase
- Testing: SQLite in-memory
- Migrations follow pattern: `YYYY_MM_DD_HHMMSS_create_table_name_table.php`
- Use `$table->foreignId('column')->constrained()` for foreign keys

---

### 4. Testing & Quality Assurance

**When to use:** Writing tests, running test suites, or performing code quality checks.

**Scope:**
- Feature tests (`tests/Feature/`)
- Unit tests (`tests/Unit/`)
- Security audit tests
- Route access control tests

**Test Files:**
- `tests/Feature/SecurityAuditTest.php` — Role-based access control
- `tests/Feature/BlackboxWhiteboxTest.php` — Functional blackbox tests
- `tests/Feature/WhiteboxNewFeaturesTest.php` — Internal logic tests
- `tests/Feature/RouteErrorCheckTest.php` — Route protection tests
- `tests/Feature/EmailVerificationTest.php` — Auth flow tests
- `tests/Feature/BlackboxNewFeaturesTest.php` — Feature coverage tests

**Commands:**
```sh
composer test                          # Run all tests
php artisan test --filter=TestName     # Run specific test
vendor/bin/pint --test                 # Format check
php -l <file>                          # PHP lint
npm run build                          # Vite build verification
```

---

### 5. PWA & Deployment

**When to use:** Tasks related to PWA features, service worker, build optimization, or deployment.

**Scope:**
- Service worker configuration (`public/sw.js`)
- PWA manifest (`public/manifest.webmanifest`)
- Offline fallback (`public/offline.html`)
- Build optimization (Vite config)
- Vercel deployment configuration (`.vercelignore`)
- Vercel function configuration (`vercel.json`)

**Key Files:**
- `public/sw.js` — PWA service worker
- `public/manifest.webmanifest` — PWA install manifest
- `.vercelignore` — Deployment exclusions
- `vite.config.js` — Vite build configuration
- `vercel.json` — Vercel runtime configuration

**Deployment:**
```sh
npm run build                       # Build frontend
npx vercel --prod --force --yes    # Deploy to production
```

---

### 6. Maps & Geolocation

**When to use:** Tasks involving the Peta Kontur module, MapLibre GL JS, or geospatial data.

**Scope:**
- Map rendering with MapLibre GL JS
- GeoJSON layer integration
- PMTiles (offline map tiles)
- Geolocation-based features
- Search functionality

**Key Files:**
- `resources/js/Pages/Peta/Index.vue`
- `resources/js/Pages/Peta/MapDenganPencarian.vue`
- `public/storage/maps/sulsel_kontur.pmtiles`
- `public/storage/maps/batas_kabupaten_sulsel.geojson`

---

### 7. Documents & Reporting

**When to use:** Tasks involving PDF generation, report exports, CSV downloads, or document templates.

**Scope:**
- PDF generation (dompdf via barryvdh/laravel-dompdf)
- CSV exports
- Blade templates for print views
- Letter template management
- Report generation

**Key Files:**
- `app/Http/Controllers/ReportController.php`
- `app/Http/Controllers/LetterController.php`
- `resources/views/reports/*.blade.php`
- `resources/views/letters/pdf.blade.php`

**Libraries:**
- `barryvdh/laravel-dompdf` — PDF generation
- `phpoffice/phpword` — DOCX template processing
