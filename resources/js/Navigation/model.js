import { ROLES } from '@/Access/capabilities.js';

/**
 * Model navigasi aplikasi.
 *
 * Seluruh sidebar (desktop, drawer mobile, rail ringkas, bottom nav) hanya
 * membaca file ini. Menambah, memindahkan, atau menyembunyikan menu cukup
 * dilakukan di sini, bukan di template.
 *
 * Bentuk entri:
 *   key         identifier unik, dipakai sebagai :key dan untuk pengujian.
 *   href        tujuan rute Inertia.
 *   label       teks tombol.
 *   icon        nama ikon pada Navigation/icons.js.
 *   hint        keterangan pendek, dipakai sebagai tooltip saat rail ringkas.
 *   capabilities kapabilitas yang boleh melihat entri. Diambil dari
 *               Access/capabilities.js, bukan daftar peran terpisah, sehingga
 *               menu sidebar dan isi halaman memakai satu aturan yang sama.
 *   exact       true bila hanya cocok pada path persis, bukan turunannya.
 *   keywords    kata kunci tambahan untuk pencarian menu di sidebar.
 */

export { ROLES };

export const navigationModel = [
    {
        key: 'utama',
        label: 'Utama',
        items: [
            {
                key: 'dashboard',
                href: '/dashboard',
                label: 'Dashboard',
                hint: 'Ringkasan aktivitas ambalan',
                icon: 'dashboard',
                exact: true,
                capabilities: ['app.access'],
                keywords: ['beranda', 'ringkasan', 'home'],
            },
            {
                key: 'peta-kontur',
                href: '/peta',
                label: 'Peta Kontur',
                hint: 'Peta kontur titik kegiatan',
                icon: 'map',
                capabilities: ['app.access'],
                keywords: ['peta', 'contour', 'lokasi', 'titik'],
            },
            {
                key: 'notifikasi',
                href: '/notifications',
                label: 'Notifikasi',
                hint: 'Pemberitahuan terbaru',
                icon: 'bell',
                capabilities: ['app.access'],
                badge: { source: 'unreadNotificationCount' },
                keywords: ['notifikasi', 'bell', 'pemberitahuan'],
            },
            {
                key: 'kegiatan',
                href: '/events',
                label: 'Kegiatan',
                hint: 'Agenda dan jadwal kegiatan',
                icon: 'events',
                capabilities: ['events.access'],
                keywords: ['kegiatan', 'event', 'agenda', 'jadwal'],
            },
        ],
    },
    {
        key: 'keanggotaan',
        label: 'Keanggotaan',
        items: [
            {
                key: 'anggota',
                href: '/members',
                label: 'Anggota',
                hint: 'Direktorium anggota ambalan',
                icon: 'members',
                capabilities: ['members.access'],
                keywords: ['anggota', 'member', 'daftar', 'direktori'],
            },
            {
                key: 'menunggu-persetujuan',
                href: '/members/pending',
                label: 'Menunggu Persetujuan',
                hint: 'Verifikasi anggota baru',
                icon: 'pending',
                capabilities: ['members.verify'],
                badge: { source: 'pendingCount' },
                keywords: ['verifikasi', 'approve', 'pending', 'calon'],
            },
            {
                key: 'kehadiran',
                href: '/attendance',
                label: 'Kehadiran',
                hint: 'Rekap absensi kegiatan',
                icon: 'attendance',
                capabilities: ['attendance.access'],
                keywords: ['kehadiran', 'absensi', 'presensi', 'hadir'],
            },
            {
                key: 'sku-tku',
                href: '/sku',
                label: 'SKU / TKU',
                hint: 'Satuan Kompetensi dan Tunai',
                icon: 'sku',
                capabilities: ['app.access'],
                keywords: ['sku', 'tku', 'kompetsi', 'lipatan'],
            },
            {
                key: 'gugus-depan',
                href: '/teams',
                label: 'Gugus Depan',
                hint: 'Unit dan gugus depan',
                icon: 'teams',
                capabilities: ['members.access'],
                keywords: ['gugus', 'unit', 'tim', 'departemen'],
            },
        ],
    },
    {
        key: 'penilaian',
        label: 'Penilaian',
        items: [
            {
                key: 'penilaian',
                href: '/assessments',
                label: 'Penilaian',
                hint: 'Nilai dan capaian',
                icon: 'assessments',
                capabilities: ['assessments.manage'],
                keywords: ['penilaian', 'nilai', 'asesmen'],
            },
            {
                key: 'sertifikat',
                href: '/certificates',
                label: 'Sertifikat',
                hint: 'Sertifikat kepramukaan',
                icon: 'certificates',
                capabilities: ['certificates.manage'],
                keywords: ['sertifikat', 'certificate', 'sert'],
            },
            {
                key: 'laporan',
                href: '/reports',
                label: 'Laporan',
                hint: 'Rekap dan cetakan',
                icon: 'reports',
                capabilities: ['reports.view'],
                keywords: ['laporan', 'report', 'rekap', 'cetak'],
            },
            {
                key: 'rapat',
                href: '/meetings',
                label: 'Rapat',
                hint: 'Notulen rapat kepengurusan',
                icon: 'meetings',
                capabilities: ['meetings.manage'],
                keywords: ['rapat', 'meeting', 'notulen', 'minset'],
            },
        ],
    },
    {
        key: 'keuangan',
        label: 'Keuangan',
        items: [
            {
                key: 'keuangan-iuran',
                href: '/finance',
                label: 'Keuangan & Iuran',
                hint: 'Kas ambalan dan iuran',
                icon: 'wallet',
                capabilities: ['finance.view'],
                keywords: ['keuangan', 'kas', 'iuran', 'uang', 'finance'],
            },
            {
                key: 'inventaris',
                href: '/inventory',
                label: 'Inventaris',
                hint: 'Barang dan aset ambalan',
                icon: 'inventory',
                capabilities: ['inventory.manage'],
                keywords: ['inventaris', 'barang', 'aset', 'stok'],
            },
        ],
    },
    {
        key: 'konten',
        label: 'Konten',
        items: [
            {
                key: 'pengumuman',
                href: '/announcements',
                label: 'Pengumuman',
                hint: 'Info resmi ambalan',
                icon: 'announcements',
                capabilities: ['content.access'],
                keywords: ['pengumuman', 'announcement', 'info'],
            },
            {
                key: 'buku-saku',
                href: '/field-guides',
                label: 'Buku Saku',
                hint: 'Panduan lapangan',
                icon: 'guides',
                capabilities: ['content.access'],
                keywords: ['buku saku', 'panduan', 'field guide', 'literatur'],
            },
            {
                key: 'blog',
                href: '/articles',
                label: 'Blog',
                hint: 'Artikel dan catatan',
                icon: 'articles',
                capabilities: ['content.access'],
                keywords: ['blog', 'artikel', 'berita', 'catatan'],
            },
            {
                key: 'galeri',
                href: '/galleries',
                label: 'Galeri',
                hint: 'Dokumentasi kegiatan',
                icon: 'galleries',
                capabilities: ['content.access'],
                keywords: ['galeri', 'foto', 'dokumentasi', 'gallery'],
            },
            {
                key: 'materi',
                href: '/materials',
                label: 'Materi',
                hint: 'Materi dan berkas',
                icon: 'materials',
                capabilities: ['content.access'],
                keywords: ['materi', 'berkas', 'file', 'unduh', 'modul'],
            },
            {
                key: 'persuratan',
                href: '/letters',
                label: 'Persuratan',
                hint: 'Surat dan disposisi',
                icon: 'letters',
                capabilities: ['letters.manage'],
                keywords: ['surat', 'persuratan', 'disposisi', 'letter'],
            },
        ],
    },
    {
        key: 'sistem',
        label: 'Sistem',
        items: [
            {
                key: 'poin-sistem',
                href: '/system/points',
                label: 'Poin Sistem',
                hint: 'Poin dan penghargaan',
                icon: 'points',
                capabilities: ['points.manage'],
                keywords: ['poin', 'points', 'skor', 'reward'],
            },
            {
                key: 'log-aktivitas',
                href: '/audit-logs',
                label: 'Log Aktivitas',
                hint: 'Jejak perubahan data',
                icon: 'audit',
                capabilities: ['audit.view'],
                keywords: ['log', 'audit', 'aktivitas', 'riwayat'],
            },
            {
                key: 'izin-pengguna',
                href: '/permissions',
                label: 'Izin Pengguna',
                hint: 'Peran dan hak akses',
                icon: 'permissions',
                capabilities: ['permissions.manage'],
                keywords: ['izin', 'permission', 'role', 'hak akses', 'peran'],
            },
            {
                key: 'tools-sistem',
                href: '/system/tools/backups',
                label: 'Tools Sistem',
                hint: 'Cadangan dan pemeliharaan',
                icon: 'tools',
                capabilities: ['tools.manage'],
                keywords: ['tools', 'backup', 'cadangan', 'sistem', 'perawatan'],
            },
            {
                key: 'pengaturan',
                href: '/ambalan',
                label: 'Pengaturan',
                hint: 'Identitas ambalan',
                icon: 'settings',
                capabilities: ['settings.manage'],
                keywords: ['pengaturan', 'settings', 'ambalan', 'konfigurasi', 'profil ambalan'],
            },
        ],
    },
    {
        key: 'akun',
        label: 'Akun',
        items: [
            {
                key: 'profil',
                href: '/profile',
                label: 'Profil',
                hint: 'Data dan riwayat pribadi',
                icon: 'profile',
                capabilities: ['profile.access'],
                keywords: ['profil', 'profile', 'akun', 'saya', 'password'],
            },
        ],
    },
    {
        key: 'alumni',
        label: 'Alumni',
        items: [
            {
                key: 'portal-alumni',
                href: '/alumni/dashboard',
                label: 'Portal Alumni',
                hint: 'Jaringan dan donasi alumni',
                icon: 'alumni',
                capabilities: ['alumni.access'],
                keywords: ['alumni', 'purna', 'lulusan', 'donasi', 'direktori alumni'],
            },
        ],
    },
];

/**
 * Bottom nav mobile dibatasi lima entri agar target sentuhnya tetap nyaman;
 * sisanya tetap terjangkau lewat drawer sidebar.
 */
export const bottomNavModel = [
    { key: 'beranda', href: '/dashboard', label: 'Beranda', icon: 'dashboard', exact: true, capabilities: ['app.access'] },
    { key: 'kehadiran-nav', href: '/attendance', label: 'Kehadiran', icon: 'attendance', capabilities: ['attendance.access'] },
    { key: 'sku-nav', href: '/sku', label: 'SKU', icon: 'sku', capabilities: ['app.access'] },
    { key: 'peta-nav', href: '/peta', label: 'Peta', icon: 'map', capabilities: ['app.access'] },
    { key: 'profil-nav', href: '/profile', label: 'Profil', icon: 'profile', capabilities: ['profile.access'] },
];

/**
 * Kartu ajakan di kaki sidebar ikut berubah mengikuti data nyata pengguna,
 * bukan teks statis. Urutan prioritas: hal yang butuh tindakan lebih dulu.
 */
export function resolveSidebarFooter({ counts = {} } = {}) {
    const pending = Number(counts.pendingCount) || 0;
    const unread = Number(counts.unreadNotificationCount) || 0;

    if (pending > 0) {
        return {
            key: 'pending',
            tone: 'warning',
            icon: 'pending',
            title: 'Anggota Menunggu Verifikasi',
            description: `${pending} pengajuan perlu ditinjau sebelum masuk ke direktori.`,
            href: '/members/pending',
            cta: 'Tinjau Sekarang',
        };
    }

    if (unread > 0) {
        return {
            key: 'unread',
            tone: 'info',
            icon: 'bell',
            title: 'Notifikasi Belum Dibaca',
            description: `${unread} pemberitahuan menunggu untuk dibaca.`,
            href: '/notifications',
            cta: 'Lihat Notifikasi',
        };
    }

    return {
        key: 'default',
        tone: 'default',
        icon: 'points',
        title: 'Siap berlatih?',
        description: 'Kelola kegiatan ambalan dengan data yang tertib dan transparan.',
        href: '/events',
        cta: 'Buka Kegiatan',
    };
}