# TODO — Development Roadmap

## Phase 1: Core Infrastructure (Completed)

### Authentication & Authorization
- [x] Login/Register page (`/login`, `/register`)
- [x] Password reset flow
- [x] Email verification (MustVerifyEmail)
- [x] Pending approval workflow (Pembina approves)
- [x] Role-based middleware (Admin, Pembina, Pengurus, Anggota, Alumni, Juru Uang)
- [ ] Email service integration (SMTP/Brevo/Resend) — pending .env SMTP keys

### Database & Models
- [x] 40+ Models (User, Member, Angkatan, Attendance, Event, Finance, etc.)
- [x] 30+ Migrations
- [x] Supabase PostgreSQL connection
- [x] DemoUsersSeeder, SkuPenegakPointSeeder, etc.

### Frontend Foundation
- [x] AppLayout.vue (header, sidebar, mobile drawer, bottom nav)
- [x] PWA service worker
- [x] Cookie overflow protection
- [x] Toast notifications
- [x] Inertia.js page resolution
- [x] Tailwind CSS dark theme (#263D26, #335233, #6F9435, #EDD330)

### CI/CD
- [x] Vercel deployment pipeline
- [x] `.vercelignore` (excludes vendor/, .kilo/, .pmtiles, etc.)

---

## Phase 2: Member & Attendance (In Progress)

### Member Management
- [x] Member CRUD (Admin)
- [x] Pending approval list (Pembina)
- [x] Position assignment (bulk)
- [x] Angkatan/batch management
- [x] Profile update (Anggota)
- [x] Member import/export (CSV)

### Attendance / Presensi
- [x] Attendance session creation with QR code (Pengurus)
- [x] QR code scanning page (Anggota)
- [x] Attendance history viewer
- [x] Materi PDF upload/download
- [ ] Dynamic QR code (auto-refresh 5-10s) — future enhancement
- [ ] Geolocation verification for QR scan — future enhancement

---

## Phase 3: Activity & Engagement (Completed)

### Events / Kegiatan
- [x] Event CRUD
- [x] Event join/leave
- [x] Participant management

### Meetings / Permusyawaratan
- [x] Meeting CRUD with agenda
- [x] Voting system
- [x] Meeting minutes
- [x] Attendee tracking

### SKU / TKU
- [x] SKU submission
- [x] SKU approval/rejection (Pengurus)
- [x] Point management (add/edit)
- [x] TKK point submissions

---

## Phase 4: Documentation & Content (Completed)

### Certificates / Sertifikat
- [x] Certificate CRUD
- [x] PDF download

### Field Guides / Buku Saku
- [x] Guide CRUD
- [x] Online reading view
- [ ] Offline cache for guides — PWA enhancement

### Articles / Blog
- [x] Article CRUD
- [x] Public viewing

### Gallery
- [x] Gallery CRUD (upload/delete)
- [ ] Cloud storage integration for photos — pending

---

## Phase 5: Administration (Completed)

### Finance / Keuangan
- [x] Finance category management (CRUD)
- [x] Finance period management (open/close)
- [x] Transaction posting and reversal
- [x] Member iuran viewing
- [x] Finance PDF report
- [x] Donation system (Alumni)

### Inventory / Inventaris
- [x] Inventory CRUD
- [x] Loan system (pinjam/kembali)
- [x] Stock adjustment
- [x] Movement history tracking

### Teams / Gugus Depan
- [x] Team CRUD
- [x] Team member management
- [x] Task management
- [x] Task status tracking

### Reminders / Pengumuman
- [x] Reminder CRUD
- [x] Mark as sent
- [x] Announcement CRUD

---

## Phase 6: System & Governance (Completed)

### Letters / Persuratan
- [x] Letter CRUD
- [x] Template management (CRUD)
- [x] Letter preview
- [x] Print and download (PDF via dompdf)
- [ ] Cloud storage for letter attachments — pending

### Notifications
- [x] In-app notification feed
- [x] Broadcast notifications (Admin)
- [x] Mark as read / delete

### Reports / Laporan
- [x] Finance PDF report
- [x] Members CSV export
- [x] Attendance PDF report
- [x] SKU PDF report

### Health & Safety
- [x] Health record CRUD
- [x] Safety check CRUD

### Audit & Permissions
- [x] Audit log viewing (Admin)
- [x] User permission management (Admin)

### System Tools
- [x] Database backup (Admin)
- [x] Webhook management (CRUD + trigger)
- [x] System points management

### Candidates
- [x] Candidate CRUD

---

## Phase 7: Maps & PWA

### Maps / Peta Kontur
- [x] MapLibre GL JS integration
- [x] Sulawesi contour map (PMTiles)
- [x] Search functionality
- [ ] Offline map tile caching — PWA enhancement

---

## Phase 8: Technical Debt & Enhancements (Backlog)

- [ ] **Cloud storage integration**: Migrate all file uploads (gallery, materi, sertifikat, letters) from local to S3/Supabase/Cloudinary
- [ ] **Dynamic QR code**: Auto-refresh QR for attendance to prevent screenshot sharing
- [ ] **Geolocation verification**: GPS radius check for attendance scanning
- [ ] **Real-time notifications**: Migrate from polling to WebSocket/Pusher for critical notifications
- [ ] **Email service**: Configure real SMTP (Brevo/Resend) in production .env
- [ ] **Caching strategy**: Implement Redis caching for frequently-accessed data (dashboard stats, announcements)
- [ ] **Dark/Light theme toggle**: Currently dark-only theme
- [ ] **Search across modules**: Global search functionality
- [ ] **Mobile responsiveness audit**: Full audit on iOS/Android PWA
- [ ] **Performance monitoring**: Add Sentry or similar for error tracking
- [ ] **API rate limiting per-user**: Current rate limiting is per-IP
- [ ] **Background job monitoring**: Failed job alerting
- [ ] **Database indexing audit**: Add indexes on high-frequency query columns
- [ ] **Unit test coverage**: Increase from current baseline to 80%+
- [ ] **Form Request validation**: Migrate all controller validation to FormRequest classes
- [ ] **API documentation**: Add OpenAPI/Swagger spec for backend endpoints
- [ ] **Accessibility audit**: WCAG 2.1 compliance check
