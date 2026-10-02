/**
 * Model navigasi aplikasi.
 *
 * Seluruh sidebar (desktop, drawer mobile, rail ringkas, bottom nav) hanya
 * membaca file ini. Menambah, memindahkan, atau menyembunyikan menu cukup
 * dilakukan di sini, bukan di template.
 *
 * Bentuk entri:
 *   key      identifier unik, dipakai sebagai `:key` dan untuk pengujian.
 *   href     tujuan rute Inertia.
 *   label    teks tombol.
 *   icon     nama ikon pada `Navigation/icons.js`.
 *   hint     keterangan pendek, dipakai sebagai tooltip saat rail ringkas.
 *   roles    daftar peran yang boleh melihat entri.
 *   badge    sumber angka badge, mis. `{ source: 'pendingCount' }`. Angka dibaca
 *            dari props Inertia yang sudah dihitung server dari model terkait.
 *   requires(predikat)  syarat tambahan di luar peran, mis. khusus juru uang.
 *   exact    true bila hanya cocok pada path persis, bukan turunannya.
 *   keywords kata kunci tambahan untuk pencarian menu di sidebar.
 */

export const ROLES = {
    ADMIN: 'Admin',
    PEMBINA: 'Pembina',
    PENGURUS: 'Pengurus',
    ANGGOTA: 'Anggota',
    ALUMNI: 'Alumni',
};

/** Peran pengguna internal (bukan alumni). */
export const INTERNAL_ROLES = [ROLES.ADMIN, ROLES.PEMBINA, ROLES.PENGURUS];
export const MEMBER_ROLES = [...INTERNAL_ROLES, ROLES.ANGGOTA];
export const ALUMNI_ROLES = [ROLES.ALUMNI];

const canApproveMember = (user) => user?.role === ROLES.ADMIN || user?.role === ROLES.PEMBINA;
const isFinanceOfficer = (user) => user?.role !== ROLES.PENGURUS || Boolean(user?.is_juru_uang);

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
                roles: MEMBER_ROLES,
                keywords: ['beranda', 'ringkasan', 'home'],
            },
            {
                key: 'peta-kontur',
                href: '/peta',
                hint: 'Peta kontur titik kegiatan',
                icon: 'map',
                roles: MEMBER_ROLES,
                keywords: ['peta', 'contour', 'lokasi', 'titik'],
            },
            {
                key: 'notifikasi',
                href: '/notifications',
                label: 'Notifikasi',
                hint: 'Pemberitahuan terbaru',
                icon: 'bell',
                roles: MEMBER_ROLES,
                badge: { source: 'unreadNotificationCount' },
                keywords: ['notifikasi', 'bell', 'pemberitahuan'],
            },
            {
                key: 'kegiatan',
                href: '/events',
                label: 'Kegiatan',
                hint: 'Agenda dan jadwal kegiatan',
                icon: 'events',
                roles: MEMBER_ROLES,
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
                roles: INTERNAL_ROLES,
                keywords: ['anggota', 'member', 'daftar', 'direktori'],
            },
            {
                key: 'menunggu-persetujuan',
                href: '/members/pending',
                label: 'Menunggu Persetujuan',
                hint: 'Verifikasi anggota baru',
                icon: 'pending',
                roles: [ROLES.ADMIN, ROLES.PEMBINA],
                requires: canApproveMember,
                badge: { source: 'pendingCount' },
                keywords: ['verifikasi', 'approve', 'pending', 'calon'],
            },
            {
                key: 'kehadiran',
                href: '/attendance',
                label: 'Kehadiran',
                hint: 'Rekap absensi kegiatan',
                icon: 'attendance',
                roles: MEMBER_ROLES,
                keywords: ['kehadiran', 'absensi', 'presensi', 'hadir'],
            },
            {
                key: 'sku-tku',
                href: '/sku',
                label: 'SKU / TKU',
                hint: 'Satuan Kompetensi dan Tunai',
                icon: 'sku',
                roles: MEMBER_ROLES,
                keywords: ['sku', 'tku', 'kompetsi', 'lipatan'],
            },
            {
                key: 'gugus-depan',
                href: '/teams',
                label: 'Gugus Depan',
                hint: 'Unit dan gugus depan',
                icon: 'teams',
                roles: INTERNAL_ROLES,
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
                roles: [ROLES.ADMIN, ROLES.PEMBINA],
                keywords: ['penilaian', 'nilai', 'asesmen'],
            },
            {
                key: 'sertifikat',
                href: '/certificates',
                label: 'Sertifikat',
                hint: 'Sertifikat kepramukaan',
                icon: 'certificates',
                roles: [ROLES.ADMIN, ROLES.PEMBINA],
                keywords: ['sertifikat', 'certificate', 'sert'],
            },
            {
                key: 'laporan',
                href: '/reports',
                label: 'Laporan',
                hint: 'Rekap dan cetakan',
                icon: 'reports',
                roles: INTERNAL_ROLES,
                keywords: ['laporan', 'report', 'rekap', 'cetak'],
            },
            {
                key: 'rapat',
                href: '/meetings',
                label: 'Rapat',
                hint: 'Notulen rapat kepengurusan',
                icon: 'meetings',
                roles: INTERNAL_ROLES,
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
                roles: MEMBER_ROLES,
                requires: isFinanceOfficer,
                keywords: ['keuangan', 'kas', 'iuran', 'uang', 'finance'],
            },
            {
                key: 'inventaris',
                href: '/inventory',
                label: 'Inventaris',
                hint: 'Barang dan aset ambalan',
                icon: 'inventory',
                roles: INTERNAL_ROLES,
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
                roles: MEMBER_ROLES,
                keywords: ['pengumuman', 'announcement', 'info'],
            },
            {
                key: 'buku-saku',
                href: '/field-guides',
                label: 'Buku Saku',
                hint: 'Panduan lapangan',
                icon: 'guides',
                roles: MEMBER_ROLES,
                keywords: ['buku saku', 'panduan', 'field guide', 'literatur'],
            },
            {
                key: 'blog',
                href: '/articles',
                label: 'Blog',
                hint: 'Artikel dan catatan',
                icon: 'articles',
                roles: MEMBER_ROLES,
                keywords: ['blog', 'artikel', 'berita', 'catatan'],
            },
            {
                key: 'galeri',
                href: '/galleries',
                label: 'Galeri',
                hint: 'Dokumentasi kegiatan',
                icon: 'galleries',
                roles: MEMBER_ROLES,
                keywords: ['galeri', 'foto', 'dokumentasi', 'gallery'],
            },
            {
                key: 'materi',
                href: '/materials',
                label: 'Materi',
                hint: 'Materi dan berkas',
                icon: 'materials',
                roles: MEMBER_ROLES,
                keywords: ['materi', 'berkas', 'file', 'unduh', 'modul'],
            },
            {
                key: 'persuratan',
                href: '/letters',
                label: 'Persuratan',
                hint: 'Surat dan disposisi',
                icon: 'letters',
                roles: [ROLES.PEMBINA, ROLES.PENGURUS],
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
                roles: INTERNAL_ROLES,
                keywords: ['poin', 'points', 'skor', 'reward'],
            },
            {
                key: 'log-aktivitas',
                href: '/audit-logs',
                label: 'Log Aktivitas',
                hint: 'Jejak perubahan data',
                icon: 'audit',
                roles: [ROLES.ADMIN],
                keywords: ['log', 'audit', 'aktivitas', 'riwayat'],
            },
            {
                key: 'izin-pengguna',
                href: '/permissions',
                label: 'Izin Pengguna',
                hint: 'Peran dan hak akses',
                icon: 'permissions',
                roles: [ROLES.ADMIN],
                keywords: ['izin', 'permission', 'role', 'hak akses', 'peran'],
            },
            {
                key: 'tools-sistem',
                href: '/system/tools/backups',
                label: 'Tools Sistem',
                hint: 'Cadangan dan pemeliharaan',
                icon: 'tools',
                roles: [ROLES.ADMIN],
                keywords: ['tools', 'backup', 'cadangan', 'sistem', 'perawatan'],
            },
            {
                key: 'pengaturan',
                href: '/ambalan',
                label: 'Pengaturan',
                hint: 'Identitas ambalan',
                icon: 'settings',
                roles: [ROLES.ADMIN, ROLES.PEMBINA],
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
                roles: MEMBER_ROLES,
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
                roles: ALUMNI_ROLES,
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
    { key: 'beranda', href: '/dashboard', label: 'Beranda', icon: 'dashboard', exact: true, roles: MEMBER_ROLES },
    { key: 'kehadiran-nav', href: '/attendance', label: 'Kehadiran', icon: 'attendance', roles: MEMBER_ROLES },
    { key: 'sku-nav', href: '/sku', label: 'SKU', icon: 'sku', roles: MEMBER_ROLES },
    { key: 'peta-nav', href: '/peta', label: 'Peta', icon: 'map', roles: MEMBER_ROLES },
    { key: 'profil-nav', href: '/profile', label: 'Profil', icon: 'profile', roles: MEMBER_ROLES },
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
