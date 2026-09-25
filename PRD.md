# Product Requirements Document (PRD)

## Sistem PWA Ambalan (Sistem Ekosistem Digital Kepramukaan Ambalan UPT SMAN 2 Maros)

**Version:** 1.0
**Date:** 2026-09-25
**Author:** Development Team
**Status:** Active

---

## 1. Problem Statement

Organisasi kepramukaan di sekolah menghadapi tantangan dalam mengelola data anggota, kegiatan, presensi, keuangan, inventaris, dan dokumentasi secara terpusat dan efisien. Sistem yang ada biasanya tersebar di berbagai platform atau berupa catatan manual, sehingga:

- **Duplikasi data** terjadi karena tidak adanya sistem terintegrasi.
- **Presensi** dan **kehadiran kegiatan** sulit dilacak secara real-time.
- **Laporan keuangan** dan **inventaris** tidak transparan dan sulit diakses.
- **Anggota dan alumni** kesulitan mengakses informasi penting secara mandiri.
- **Dokumen resmi** (surat, laporan, sertifikat) tidak terstandardisasi dan sulit dicetak.

Solusi yang dibutuhkan adalah sebuah sistem digital berbasis web yang berfungsi sebagai Progressive Web App (PWA), sehingga dapat diakses baik melalui browser desktop maupun diinstal sebagai aplikasi mobile, dengan semua modul kegiatan kepramukaan terintegrasi dalam satu ekosistem.

## 2. Goals

| No | Goal | Metric | Target |
|----|------|--------|--------|
| 1 | Centralize all scouting organization data in one integrated system | Number of disintegrated systems reduced | From 5+ fragmented systems to 1 |
| 2 | Enable real-time attendance tracking via QR code | Attendance capture time | < 30 seconds per scan |
| 3 | Provide transparent financial management | Finance entries tracked digitally | 100% digital (0 cash-only records) |
| 4 | Streamline member management and approval workflow | Pending approval resolution time | < 24 hours |
| 5 | Enable offline access for read-only content and mobile installation | PWA offline capability | 90%+ static content readable offline (profil, buku saku, materi yang pernah diakses) |
| 6 | Standardize document generation (letters, reports, certificates) | Document templates created | 15+ standardized templates |
| 7 | Improve data-driven decision making through reporting | Reports generated automatically | 5+ report types available |

## 3. Target Users

### Primary Users

| Role | Description | Key Needs |
|------|-------------|-----------|
| **Admin** | Full system administrator with unrestricted access | Configure system settings, manage all data, generate reports, manage users and permissions |
| **Pembina** | Scout advisor/mentor (teacher in charge) | Approve member registrations, manage activities, oversee all operations, access all data |
| **Pengurus** | Scout managers/officials (e.g., Bendahara/Pengurus tertentu) | Manage day-to-day operations: events, meetings, inventory, announcements |
| **Anggota** | Active scout members | View personal profile, check attendance, participate in events, submit SKU/TKU, check finances |
| **Alumni** | Former members | Access alumni portal, update career info, make donations, view donation history |
| **Juru Uang** (sub-role of Pengurus) | Finance handler | Manage financial categories, periods, approve transactions, generate financial reports |

### Secondary Users

| User Type | Description | Key Needs |
|-----------|-------------|-----------|
| **Tamu/Guest** | Unauthenticated visitors to the landing page | View public information, access login/register |
| **Calon Anggota** | Prospective members who haven't been approved | Register, wait for approval |

## 4. User Stories

### Authentication & Authorization
- **US-001:** Sebagai Tamu, saya ingin melihat halaman beranda publik sehingga saya dapat memahami apa itu Ambalan.
- **US-002:** Sebagai Pengguna, saya ingin mendaftar akun sehingga saya dapat mengakses sistem secara pribadi.
- **US-002B:** Sebagai Pengguna baru, saya ingin menerima email verifikasi setelah mendaftar sehingga saya dapat mengonfirmasi kepemilikan alamat email saya.
- **US-003:** Sebagai Anggota, saya ingin masuk dengan email dan password sehingga saya dapat mengakses dashboard pribadi.
- **US-004:** Sebagai Pengguna, saya ingin reset password melalui email sehingga saya dapat memulihkan akses akun yang hilang.
- **US-005:** Sebagai Calon Anggota, saya ingin melihat status pendaftaran (pending approval) sehingga saya tahu apakah sudah disetujui.

### Dashboard
- **US-006:** Sebagai Anggota, saya ingin melihat ringkasan aktivitas terbaru di dashboard sehingga saya dapat melihat apa yang perlu diketahui.
- **US-007:** Sebagai Pengurus, saya ingin melihat statistik kehadiran dan kegiatan di dashboard sehingga saya dapat memantau status kegiatan.

### Member Management
- **US-008:** Sebagai Pembina, saya ingin melihat daftar anggota yang menunggu persetujuan sehingga saya dapat menyetujui atau menolak pendaftaran.
- **US-009:** Sebagai Admin, saya ingin mengelola data anggota (CRUD) sehingga data tetap akurat.
- **US-010:** Sebagai Pengurus, saya ingin mengelola posisi/jabatan anggota sehingga struktur organisasi jelas.
- **US-011:** Sebagai Pengurus, saya ingin mengelola angkatan/tahun (batch) anggota sehingga data kelompok tertentan terorganisir.
- **US-012:** Sebagai Anggota, saya ingin memperbarui profil pribadi sehingga informasi kontak tetap up-to-date.

### Attendance / Presensi
- **US-013:** Sebagai Pengurus, saya ingin membuat sesi presensi dengan QR code dinamis (auto-refresh tiap 5-10 detik) sehingga anggota dapat melakukan scan kehadiran dengan aman.
- **US-014:** Sebagai Anggota, saya ingin memindai QR code untuk presensi sehingga kehadiran saya tercatat otomatis.
- **US-015:** Sebagai Pengurus, saya ingin mengunggah materi kegiatan sehingga anggota dapat mengunduhnya.
- **US-016:** Sebagai Pengguna, saya ingin melihat riwayat kehadiran saya sehingga saya dapat memantau pola kehadiran.

### Events / Kegiatan
- **US-017:** Sebagai Pengurus, saya ingin membuat dan mengelola kegiatan sehingga informasi disebarkan ke seluruh anggota.
- **US-018:** Sebagai Anggota, saya ingin bergabung ke dalam kegiatan sehingga kehadiran saya tercatat.
- **US-019:** Sebagai Pengurus, saya ingin mengelola peserta kegiatan sehingga data kehadiran akurat.

### Meetings / Permusyawaratan
- **US-020:** Sebagai Pengurus, saya ingin mengadakan pertemuan/permusyawaratan dengan agenda terstruktur sehingga keputusan dapat diambil secara formal.
- **US-021:** Sebagai Anggota, saya ingin memberikan suara dalam voting pertemuan sehingga partisipasi saya tercatat.
- **US-022:** Sebagai Pengurus, saya ingin mencatat notul rapat sehingga keputusan terdokumentasi.

### SKU / TKU (Surat Keterangan Usaha / Tanda Kehadiran Usaha)
- **US-023:** Sebagai Anggota, saya ingin mengajukan SKU/TKU sehingga kegiatan saya tercatat secara resmi.
- **US-024:** Sebagai Pengurus, saya ingin menyetujui/menolak pengajuan SKU sehingga proses verifikasi berjalan.
- **US-025:** Sebagai Pengguna, saya ingin melihat poin SKU/TKU saya sehingga saya dapat memantau kemajuan.

### Assessments / Penilaian
- **US-026:** Sebagai Pembina, saya ingin membuat penilaian untuk anggota sehingga evaluasi kegiatan dapat dilakukan.
- **US-027:** Sebagai Pengguna, saya ingin melihat hasil penilaian saya sehingga saya tahu perkembangan diri.

### Certificates / Sertifikat
- **US-028:** Sebagai Pengurus, saya ingin membuat dan mengunduh sertifikat untuk anggota sehingga pencapaian terdokumentasi.
- **US-029:** Sebagai Anggota, saya ingin mengunduh sertifikat milik saya sehingga dapat disimpan sebagai dokumen.

### Field Guides / Buku Saku
- **US-030:** Sebagai Anggota, saya ingin mengakses buku saku panduan kepramukaan sehingga informasi penting tersedia offline.

### Articles / Blog
- **US-031:** Sebagai Pengunjung, saya ingin membaca artikel/kegiatan terbaru sehingga terinformasi tentang berita Ambalan.
- **US-032:** Sebagai Pengurus, saya ingin membuat dan mengelola artikel sehingga konten tetap terbarui.

### Gallery
- **US-033:** Sebagai Anggota, saya ingin melihat galeri foto kegiatan sehingga ingatan kegiatan tersimpan.
- **US-034:** Sebagai Pengurus, saya ingin mengunggah foto ke galeri sehingga dokumentasi kegiatan lengkap.

### Teams / Gugus Depan
- **US-035:** Sebagai Pengurus, saya ingin mengelola gugus departemen sehingga struktur organisasi jelas.
- **US-036:** Sebagai Anggota, saya ingin melihat tugas yang diberikan pada gugus saya sehingga saya tahu apa yang harus dikerjakan.

### Finance / Keuangan
- **US-037:** Sebagai Anggota, saya ingin melihat rincian iuran/keuangan pribadi sehingga transparan.
- **US-038:** Sebagai Juru Uang, saya ingin mengelola kategori dan periode keuangan sehingga laporan akurat.
- **US-039:** Sebagai Pengurus, saya ingin memposting dan membatalkan transaksi keuangan sehingga pencatatan akurat.

### Inventory / Inventaris
- **US-040:** Sebagai Pengurus, saya ingin mencatat inventaris baru sehingga stok terlacak.
- **US-041:** Sebagai Pengguna, saya ingin meminjam inventaris sehingga keperluan kegiatan terpenuhi.
- **US-042:** Sebagai Pengurus, saya ingin melihat riwayat pergerakan inventaris sehingga stok akurat.

### Announcements / Pengumuman
- **US-043:** Sebagai Anggota, saya ingin melihat pengumuman terbaru sehingga tidak melewatkan informasi penting.
- **US-044:** Sebagai Pengurus, saya ingin membuat pengumuman baru sehingga informasi cepat tersebar.

### Letters / Persuratan
- **US-045:** Sebagai Pengurus, saya ingin membuat surat dengan template resmi sehingga dokumen profesional.
- **US-046:** Sebagai Pengguna, saya ingin mencetak dan mengunduh surat sehingga dapat disimpan.
- **US-047:** Sebagai Pengurus, saya ingin mengelola template surat sehingga format konsisten.

### Notifications / Notifikasi
- **US-048:** Sebagai Pengguna, saya ingin menerima notifikasi tentang aktivitas penting sehingga tidak melewatkan update.
- **US-049:** Sebagai Admin, saya ingin mengirim notifikasi broadcast ke pengguna tertentu sehingga komunikasi efisien.

### Reports / Laporan
- **US-050:** Sebagai Pengurus, saya ingin mengekspor laporan keuangan dalam PDF sehingga dokumentasi resmi tersedia.
- **US-051:** Sebagai Admin, saya ingin mengekspor daftar anggota dalam CSV sehingga dapat diolah di spreadsheet.
- **US-052:** Sebagai Pengurus, saya ingin mengekspor laporan presensi dalam PDF sehingga arsip tersedia.

### Health & Safety
- **US-053:** Sebagai Pengurus, saya ingin mencatat rekam medis kesehatan anggota sehingga informasi darurat tersedia.
- **US-054:** Sebagai Pengurus, saya ingin mencatat pengecekan keselamatan kegiatan sehingga risiko teridentifikasi.

### Alumni Portal
- **US-055:** Sebagai Alumni, saya ingin memperbarui informasi karier/alumni sehingga jejak karier tercatat.
- **US-056:** Sebagai Alumni, saya ingin berdonasi sehingga dapat mendukung Ambalan.
- **US-057:** Sebagai Alumni, saya ingin melihat riwayat donasi saya sehingga transparan.

### PWA & System Tools
- **US-058:** Sebagai Pengguna, saya ingin menginstal aplikasi PWA di perangkat mobile sehingga dapat diakses tanpa perlu browser.
- **US-059:** Sebagai Admin, saya ingin mencadangkan data (backup) sehingga data aman.
- **US-060:** Sebagai Admin, saya ingin mengelola webhook eksternal sehingga integrasi sistem aman.

### Audit & Permissions
- **US-061:** Sebagai Admin, saya ingin melihat log aktivitas pengguna sehingga dapat memantau aktivitas mencurigakan.
- **US-062:** Sebagai Admin, saya ingin mengelola izin pengguna sehingga akses dikontrol.

## 5. Functional Requirements

### 5.1 Authentication & Authorization
- **FR-01:** Sistem harus mendukung registrasi pengguna baru dengan email dan password, serta mengirimkan link/token verifikasi email secara otomatis ke email pengguna.
- **FR-01B:** Pengguna yang baru mendaftar tidak dapat mengakses fitur internal aplikasi sebelum melakukan konfirmasi via link verifikasi yang dikirimkan ke email mereka (Email Verification flow).
- **FR-02:** Sistem harus mendukung login dengan email dan password.
- **FR-03:** Sistem harus mendukung reset password dengan mengirimkan email yang memuat link token reset password resmi ke email pengguna.
- **FR-04:** Sistem harus memiliki middleware autentikasi pada semua route yang memerlukan login.
- **FR-05:** Sistem harus memiliki workflow persetujuan: pengguna baru harus disetujui oleh Pembina sebelum dapat mengakses fitur penuh.
- **FR-06:** Sistem harus mendukung role-based access control: Admin, Pembina, Pengurus, Anggota, Alumni.
- **FR-07:** Sistem harus mendukung sub-role Juru Uang untuk akses modul keuangan.
- **FR-08:** Sistem harus menerapkan rate limiting pada semua endpoint (misal: 10-20 request per menit).

### 5.2 Member Management
- **FR-09:** Admin/Pembina/Pengurus dapat melihat daftar semua anggota dengan paginasi.
- **FR-10:** Admin dapat membuat, mengedit, dan menghapus anggota.
- **FR-11:** Pembina dapat melihat dan mengelola anggota yang pending (menunggu persetujuan).
- **FR-12:** Pengurus dapat mengelola posisi/jabatan anggota (bulk assign/delete).
- **FR-13:** Pengurus dapat mengelola angkatan/tahun (batch) anggota.
- **FR-14:** Anggota dapat memperbarui profil pribadi (nama, kontak, dll).

### 5.3 Attendance / Presensi
- **FR-15:** Pengurus dapat membuat sesi presensi dengan QR code unik yang bersifat dinamis (refresh otomatis setiap 5-10 detik) atau dilengkapi verifikasi lokasi geografis (GPS radius check).
- **FR-16:** Anggota dapat memindai QR code untuk mencatat kehadiran.
- **FR-17:** Pengurus dapat mengunggah file materi (.pdf) untuk setiap sesi presensi.
- **FR-18:** Sistem harus mencatat waktu presensi secara otomatis.
- **FR-19:** Pengguna dapat melihat riwayat kehadiran pribadi.

### 5.4 Events / Kegiatan
- **FR-20:** Pengurus dapat membuat, mengedit, dan menghapus kegiatan.
- **FR-21:** Anggota dapat bergabung dan keluar dari kegiatan.
- **FR-22:** Pengurus dapat mengelola peserta kegiatan.
- **FR-23:** Sistem harus menampilkan daftar kegiatan dengan status partisipasi.

### 5.5 Meetings / Permusyawaratan
- **FR-24:** Pengurus dapat mengadakan pertemuan dengan agenda terstruktur.
- **FR-25:** Anggota dapat memberikan suara dalam voting pertemuan.
- **FR-26:** Pengurus dapat mencatat notul rapat.
- **FR-27:** Sistem harus mencatat kehadiran peserta pertemuan.

### 5.6 SKU / TKU
- **FR-28:** Anggota/Pengguna dapat mengajukan SKU/TKU baru.
- **FR-29:** Pengurus dapat menyetujui/menolak/membatalkan pengajuan SKU/TKU.
- **FR-30:** Anggota dapat menambahkan, mengedit, dan melihat poin SKU/TKU.
- **FR-31:** Sistem mendukung TKK (Tanda Kehadiran Kegiatan) points.

### 5.7 Assessments / Penilaian
- **FR-32:** Pembina dapat membuat penilaian dengan detail kriteria.
- **FR-33:** Sistem menyimpan nilai penilaian per anggota.
- **FR-34:** Pengguna dapat melihat hasil penilaian pribadi.

### 5.8 Certificates / Sertifikat
- **FR-35:** Pengurus dapat membuat dan mengelola sertifikat.
- **FR-36:** Pengguna dapat mengunduh sertifikat dalam format PDF.

### 5.9 Field Guides / Buku Saku
- **FR-37:** Pengguna dapat membaca panduan kepramukaan secara online.
- **FR-38:** Sistem mendukung unggah dan pengelolaan panduan.

### 5.10 Articles / Blog
- **FR-39:** Pengguna dapat membaca artikel publik.
- **FR-40:** Pengurus dapat CRUD artikel.

### 5.11 Gallery
- **FR-41:** Pengguna dapat melihat galeri foto kegiatan.
- **FR-42:** Pengurus dapat mengunggah dan menghapus foto.

### 5.12 Teams / Gugus Depan
- **FR-43:** Pengurus dapat CRUD gugus departemen.
- **FR-44:** Pengurus dapat menambahkan/menghapus anggota gugus.
- **FR-45:** Pengurus dapat membuat dan mengelola tugas gugus.
- **FR-46:** Anggota dapat melihat tugas yang diberikan.

### 5.13 Finance / Keuangan
- **FR-47:** Semua pengguna terautentikasi dapat melihat ringkasan keuangan.
- **FR-48:** Anggota dapat melihat iuran/keuangan pribadi.
- **FR-49:** Juru Uang dapat mengelola kategori keuangan (CRUD).
- **FR-50:** Juru Uang dapat mengelola periode keuangan (buka/tutup periode).
- **FR-51:** Pengurus dapat mem-posting dan membalik transaksi keuangan.
- **FR-52:** Sistem mendukung pembuatan transaksi keuangan oleh anggota.

### 5.14 Inventory / Inventaris
- **FR-53:** Pengurus dapat CRUD item inventaris.
- **FR-54:** Pengguna dapat meminjam inventaris.
- **FR-55:** Pengguna dapat mengembalikan inventaris yang dipinjam.
- **FR-56:** Pengurus dapat melakukan penyesuaian stok (adjustment).
- **FR-57:** Pengguna dapat melihat riwayat pergerakan inventaris.

### 5.15 Announcements / Pengumuman
- **FR-58:** Semua pengguna terautentikasi dapat melihat pengumuman.
- **FR-59:** Pengurus dapat CRUD pengumuman.

### 5.16 Letters / Persuratan
- **FR-60:** Pengurus dapat CRUD surat.
- **FR-61:** Pengurus dapat mengelola template surat (CRUD).
- **FR-62:** Pengguna dapat mencetak dan mengunduh surat dalam PDF.
- **FR-63:** Sistem mendukung preview surat sebelum pencetakan.

### 5.17 Notifications / Notifikasi
- **FR-64:** Pengguna dapat melihat daftar notifikasi dalam sistem in-app notification feed (berbasis basis data/polling, bukan WebSocket real-time).
- **FR-65:** Pengguna dapat menandai notifikasi sebagai dibaca.
- **FR-66:** Admin dapat mengirim notifikasi broadcast ke pengguna tertentu melalui in-app notification feed.
- **FR-67:** Pengguna dapat menghapus notifikasi.

### 5.18 Maps / Peta Kontur
- **FR-68:** Pengguna dapat melihat peta kontur Sulawesi dengan marker lokasi.
- **FR-69:** Pengguna dapat mencari lokasi di peta.

### 5.19 Reports / Laporan
- **FR-70:** Pengguna dapat mengekspor laporan keuangan dalam PDF.
- **FR-71:** Admin dapat mengekspor daftar anggota dalam CSV.
- **FR-72:** Pengguna dapat mengekspor laporan presensi dalam PDF.
- **FR-73:** Pengguna dapat mengekspor laporan SKU dalam PDF.

### 5.20 Health & Safety
- **FR-74:** Pengurus dapat mencatat rekam medis kesehatan anggota.
- **FR-75:** Pengurus dapat mencatat pengecekan keselamatan kegiatan.

### 5.21 Alumni Portal
- **FR-76:** Alumni dapat memperbarui profil (status, instansi, pekerjaan, domisili, media sosial).
- **FR-77:** Alumni dapat membuat donasi dengan nominal dan keterangan.
- **FR-78:** Alumni dapat membatalkan donasi yang masih pending.
- **FR-79:** Alumni dapat melihat direktori alumni lain.
- **FR-80:** Alumni dapat melihat riwayat donasi.

### 5.22 PWA Support
- **FR-81:** Sistem harus mendaftarkan perangkat PWA untuk notifikasi push.
- **FR-82:** Pengguna dapat menginstal aplikasi sebagai PWA di perangkat mobile/desktop.

### 5.23 System Tools
- **FR-83:** Admin dapat membuat backup database.
- **FR-84:** Admin dapat mengelola webhook (CRUD + trigger).
- **FR-85:** Admin dapat mengelola poin sistem.
- **FR-86:** Admin dapat mengelola izin pengguna (sync permissions).

### 5.24 Audit & Monitoring
- **FR-87:** Admin dapat melihat log aktivitas pengguna.
- **FR-88:** Sistem harus mencatat semua aktivitas pengguna yang signifikan.

## 6. Non-Functional Requirements

### 6.1 Performance
- **NFR-01:** Waktu respons halaman utama (dashboard) < 2 detik pada koneksi 3G.
- **NFR-02:** Waktu respons API endpoint < 500ms untuk 95% permintaan.
- **NFR-03:** Vite build size untuk aset JavaScript utama < 500KB terkompresi.

### 6.2 Availability & Reliability
- **NFR-04:** Uptime sistem > 99% (ketersediaan 24/7 kecuali maintenance terjadwal).
- **NFR-05:** Backup database otomatis dilakukan minimal 1x per hari.
- **NFR-06:** Rate limiting mencegah serangan brute force (5 login attempts/menit, 3 registrasi/menit).

### 6.3 Security
- **NFR-07:** Semua komunikasi menggunakan HTTPS (SSL/TLS).
- **NFR-08:** Password harus di-hash menggunakan bcrypt.
- **NFR-09:** Sistem menggunakan CSRF protection pada semua form.
- **NFR-10:** API endpoints dilindungi dengan rate limiting.
- **NFR-10B:** Pengiriman email verifikasi dan reset password harus dijalankan secara asynchronous (menggunakan Laravel Queue / Job Background Process) agar tidak menghambat response time permintaan HTTP pendaftaran (NFR-02).
- **NFR-11:** User input divalidasi di server side untuk mencegah XSS dan SQL injection.
- **NFR-12:** Session timeout setelah 60 menit tidak aktif.
- **NFR-13:** Role-based access control (RBAC) mencegah akses tidak sah ke fitur.

### 6.4 Scalability
- **NFR-14:** Sistem mendukung hingga 1000 pengguna aktif bersamaan.
- **NFR-15:** Database menggunakan index yang optimal untuk query pencarian.
- **NFR-16:** Pagination diterapkan pada semua daftar data (> 20 item halaman).

### 6.5 Usability
- **NFR-17:** Antarmuka responsif dan mobile-first.
- **NFR-18:** PWA dapat diinstal tanpa perlu app store.
- **NFR-19:** Offline support terbatas pada pembacaan data statis yang sudah di-cache (panduan/buku saku, profil anggota, sertifikat yang pernah diakses, materi yang sudah pernah dibuka). Transaksi online (presensi, posting keuangan, pembuatan data) memerlukan koneksi internet.
- **NFR-20:** Tema gelap dengan aksen hijau (#6F9435) sebagai identitas visual.

### 6.6 Compatibility
- **NFR-21:** Frontend kompatibel dengan Chrome, Firefox, Safari, Edge (versi terbaru).
- **NFR-22:** Backend membutuhkan PHP >= 8.2.
- **NFR-23:** Database: SQLite (testing), MySQL 8+ (production).
- **NFR-24:** PWA kompatibel dengan iOS Safari dan Android Chrome.

### 6.7 Data Management
- **NFR-25:** Semua data sensitif (password, token) tidak pernah disimpan dalam bentuk plaintext.
- **NFR-25B:** Kredensial SMTP / API Key penyedia layanan email harus disimpan aman sebagai Environment Variables di Vercel/Server (MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION).
- **NFR-26:** Export file (PDF, CSV) hanya mengandung data yang diizinkan berdasarkan role.
- **NFR-27:** File upload dibatasi tipe dan ukuran (misal: PDF < 10MB, gambar < 5MB, format: jpg/png/gif/pdf).
- **NFR-27B:** Semua file upload (foto galeri, materi, sertifikat, surat) harus disimpan di cloud storage eksternal (AWS S3/Supabase/Cloudinary), bukan di filesystem Vercel yang ephemeral.

### 6.8 Deployment
- **NFR-28:** Deploy menggunakan Vercel dengan PHP runtime (vercel-php@0.9.0).
- **NFR-29:** Build process harus sukses dalam < 60 detik.
- **NFR-30:** `.vercelignore` mengecualikan vendor/, .kilo/, dan file .pmtiles untuk stay di bawah 100MB.

## 7. Scope

### In Scope
1. **Pengembangan modul inti:** Authentication, Dashboard, Member Management, Attendance, Events, Meetings, SKU/TKU, Assessments, Certificates, Field Guides, Articles, Gallery, Teams, Finance, Inventory, Announcements, Letters, Notifications, Maps, Reports, Health & Safety, Alumni Portal, System Tools, Audit Logs, Permissions.
   2. **Frontend:** Vue 3 + Inertia.js SPA dengan routing client-side, responsif/mobile-first.
   3. **Backend:** Laravel 12 API + Inertia rendering.
   4. **Email Service Integration:** SMTP / transactional email provider (Mailtrap, Resend, Brevo, atau Amazon SES) untuk verifikasi email pendaftaran dan reset password, dengan Laravel Queue untuk pengiriman asynchronous.
   5. **PWA:** Installable web app dengan service worker, offline cache, push notification support.
   6. **Media File Storage:** Cloud storage integration (mis. AWS S3, Supabase Storage, atau Cloudinary) untuk menyimpan file upload (foto galeri, PDF materi, sertifikat, surat) karena Vercel Functions memiliki penyimpanan ephemeral yang tidak persisten.
   7. **Deployment:** Vercel production hosting.
   8. **Testing:** PHPUnit feature/functional tests, PHP lint, Vite build verification.
   9. **Linting & Code Quality:** PHP Pint formatting, PHP lint.

### Out of Scope
   1. **Mobile app native** (Android/iOS) - hanya PWA yang disediakan.
   2. **Real-time chat/messaging** - notifikasi menggunakan sistem in-app notification, bukan WebSocket real-time.
   3. **Multi-organisasi/multi-tenant** - sistem dirancang untuk satu organisasi (Ambalan UPT SMAN 2 Maros).
   4. **AI/ML features** - tidak termasuk analitik prediktif atau rekomendasi otomatis.
   5. **External API integrasi** (misal: integrasi dengan sistem pihak ketiga) - hanya webhook management.
   6. **Admin panel terpisah** - semua manajemen dilakukan melalui UI Inertia yang sama.
   7. **Migrasi data dari sistem lama** - tidak termasuk dalam fase pengembangan ini.
