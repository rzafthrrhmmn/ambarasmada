/**
 * Model kapabilitas RBAC untuk frontend.
 *
 * Sebelumnya setiap halaman menulis cek perannya sendiri dengan lima ejaan
 * berbeda, sementara model navigasi menyimpan daftar peran terpisah yang tidak
 * pernah diimpor halaman mana pun. Akibatnya tampilan antar peran bisa
 * berbeda dari yang diizinkan server, dan beberapa tombol muncul untuk semua
 * orang lalu gagal 403 ketika dikirim.
 *
 * Berkas ini adalah satu-satunya daftar kapabilitas. Sidebar
 * (Navigation/model.js) dan setiap halaman (useAccess) membacanya dari sini,
 * sehingga "menu ini terlihat" dan "aksi ini boleh" tidak bisa lagi berbeda.
 *
 * Padanan sisi server ada di app/Support/Roles.php. Keduanya memakai daftar
 * peran dan kelompok yang sama; kalau satu ditambah, yang lain harus
 * menyusul, karena menu yang tidak muncul untuk orang yang memang berhak
 * sama rusaknya dengan tombol yang muncul untuk orang yang tidak berhak.
 *
 * Bentuk aturan:
 *   [role, ...]            mengizinkan role tersebut.
 *   { roles, also(user) }  selain role, atau predicate tambahan seperti
 *                          posisi juru uang yang dipegang seorang pengurus.
 *
 * Catatan penting: model ini hanya mengatur tampilan. Penegakan yang
 * sebenarnya tetap ada di middleware role: dan Roles::guard* pada
 * controller. Jangan memakai can() untuk menyembunyikan data yang hak aksesnya
 * sudah lewat; untuk itu lakukan pemisahan data di server.
 */

export const ROLES = {
  ADMIN: 'Admin',
  PEMBINA: 'Pembina',
  PENGURUS: 'Pengurus',
  ANGGOTA: 'Anggota',
  ALUMNI: 'Alumni',
};

/** Kelompok peran yang sering dipakai bersama. */
export const ROLE_GROUPS = {
  /** Admin, Pembina, Pengurus: boleh mengelola modul operasional. */
  management: [ROLES.ADMIN, ROLES.PEMBINA, ROLES.PENGURUS],
  /** Admin dan Pembina: boleh mengambil keputusan, mis. verifikasi anggota. */
  approver: [ROLES.ADMIN, ROLES.PEMBINA],
  /** Semua peran dengan akun aktif di aplikasi. */
  active: [ROLES.ADMIN, ROLES.PEMBINA, ROLES.PENGURUS, ROLES.ANGGOTA],
  alumni: [ROLES.ALUMNI],
};

const MANAGEMENT = ROLE_GROUPS.management;
const APPROVER = ROLE_GROUPS.approver;
const ACTIVE = ROLE_GROUPS.active;

const isJuruUang = (user) => Boolean(user?.is_juru_uang);

/**
 * Pengurus biasa tidak melihat kas; yang memegang posisi juru uang boleh.
 *
 * Syaratnya dua-duanya, bukan hanya is_juru_uang. Middleware EnsureJuruUang
 * juga menolak pengguna di luar Admin/Pembina/Pengurus yang kebetulan punya
 * penanda juru uang, jadi kapabilitas harus menolak dengan cara yang sama
 * supaya tombol tidak muncul untuk orang yang pasti mendapat 403.
 */
const isFinanceOfficer = (user) => user?.role === ROLES.PENGURUS && isJuruUang(user);

export const CAPABILITIES = {
  /* Akses umum */
  'app.access': ACTIVE,
  'profile.access': ACTIVE,
  'content.access': ACTIVE,
  'alumni.access': ROLE_GROUPS.alumni,

  /* Dasbor */
  'dashboard.access': ACTIVE,
  /** Ringkasan angka untuk pengambil keputusan, bukan sekadar daftar tugas. */
  'dashboard.view_metrics': APPROVER,

  /* Kehadiran (presensi) */
  'attendance.access': ACTIVE,
  'attendance.self.view': ACTIVE,
  'attendance.self.scan': [ROLES.ANGGOTA],
  'attendance.record.view_all': MANAGEMENT,
  'attendance.record.manage': MANAGEMENT,
  'attendance.session.create': MANAGEMENT,
  'attendance.session.edit': MANAGEMENT,
  'attendance.session.delete': MANAGEMENT,
  'attendance.session.bulk_delete': MANAGEMENT,
  'attendance.session.view_qr': MANAGEMENT,
  'attendance.materi.download': ACTIVE,

  /* Keanggotaan */
  'members.access': MANAGEMENT,
  'members.manage': MANAGEMENT,
  'members.verify': APPROVER,
  'members.role.bulk_update': MANAGEMENT,
  'members.position.manage': APPROVER,
  /** Membuat dan mengubah angkatan, bukan data anggota per satuan. */
  'members.angkatan.manage': MANAGEMENT,
  /** Antrean verifikasi pendaftaran, dibatasi Admin dan Pembina. */
  'members.pending.review': APPROVER,
  /** Mengubah kelas, tingkatan, dan status aktif anggota di halaman profil. */
  'members.profile.edit_academic': APPROVER,

  /* Kegiatan, rapat, dan pengingat */
  'events.access': ACTIVE,
  'events.manage': MANAGEMENT,
  'events.join': ACTIVE,
  /** Mengubah status kehadiran peserta, bukan membuat kegiatan. */
  'events.participant.manage': APPROVER,

  'meetings.access': ACTIVE,
  'meetings.manage': MANAGEMENT,
  'meetings.agenda.manage': MANAGEMENT,
  'meetings.minute.manage': MANAGEMENT,
  'meetings.vote.cast': ACTIVE,
  /** Mengubah hasil suara dan kehadiran peserta rapat. */
  'meetings.vote.manage': APPROVER,

  'reminders.access': ACTIVE,
  'reminders.manage': APPROVER,

  /* Penilaian dan operasional */
  'assessments.access': APPROVER,
  'assessments.manage': APPROVER,
  'reports.view': MANAGEMENT,
  'reports.export_members': MANAGEMENT,
  'reports.export_finance': MANAGEMENT,
  'reports.export_attendance': MANAGEMENT,
  'reports.export_sku': APPROVER,
  'points.manage': MANAGEMENT,

  /* Inventaris */
  'inventory.access': MANAGEMENT,
  'inventory.manage': MANAGEMENT,
  'inventory.movements.view': MANAGEMENT,

  /* SKU / TKU */
  'sku.access': ACTIVE,
  /** Mengajukan dan membatalkan pengajuan milik sendiri. */
  'sku.submit': [ROLES.ANGGOTA],
  'sku.review': APPROVER,
  'sku.point.manage': APPROVER,

  /* Surat dan dokumen */
  'letters.access': MANAGEMENT,
  'letters.manage': MANAGEMENT,
  'letters.templates.manage': MANAGEMENT,

  /* Materi, panduan, dan media */
  'materials.access': ACTIVE,
  'materials.download': ACTIVE,
  'materials.manage': MANAGEMENT,
  'guides.access': ACTIVE,
  'guides.manage': MANAGEMENT,
  'medias.access': ACTIVE,
  'medias.manage': MANAGEMENT,
  'field_guides.access': ACTIVE,
  'field_guides.manage': APPROVER,

  /* Pengumuman dan notifikasi */
  'announcements.access': ACTIVE,
  'announcements.manage': MANAGEMENT,
  'notifications.access': ACTIVE,
  'notifications.broadcast': APPROVER,

  /* Tim, peta, dan kesehatan */
  'teams.access': MANAGEMENT,
  'teams.manage': MANAGEMENT,
  'teams.member.manage': APPROVER,
  'teams.task.manage': APPROVER,
  'map.access': ACTIVE,
  'health_safety.access': MANAGEMENT,
  'health_safety.manage': MANAGEMENT,

  /* Konten */
  'content.articles.access': ACTIVE,
  'content.articles.create': ACTIVE,
  'content.articles.manage': APPROVER,
  'content.gallery.upload': ACTIVE,
  'content.gallery.manage': MANAGEMENT,
  'certificates.access': MANAGEMENT,
  'certificates.manage': APPROVER,

  /* Keuangan */
  'finance.view': {
    roles: [ROLES.ADMIN, ROLES.PEMBINA, ROLES.ANGGOTA],
    also: isFinanceOfficer,
  },
  'finance.manage': {
    roles: APPROVER,
    also: isFinanceOfficer,
  },
  'finance.categories.manage': {
    roles: APPROVER,
    also: isFinanceOfficer,
  },
  'finance.periods.manage': {
    roles: APPROVER,
    also: isFinanceOfficer,
  },
  /**
   * Membayar iuran sendiri. Jalur ini terpisah dari pengelolaan kas: seorang
   * anggota tidak boleh mengelola transaksi, tetapi tetap perlu mencatat
   * pembayarannya sendiri lewat FinanceController::store.
   */
  'finance.self.pay': [ROLES.ANGGOTA],

  /* Sistem */
  'audit.view': [ROLES.ADMIN],
  'permissions.manage': [ROLES.ADMIN],
  'tools.manage': [ROLES.ADMIN],
  'settings.manage': APPROVER,
  'candidates.manage': APPROVER,
  'trainings.access': APPROVER,
  'trainings.manage': APPROVER,
  'ambalan.manage': APPROVER,
};

/**
 * Ubah akun pengguna menjadi himpunan kapabilitas.
 *
 * Nilai Set membuat pengecekan di template tetap murah, dan mencegah
 * kapabilitas yang tidak terdaftar ikut terhitung diam-diam.
 */
export function resolveCapabilities(user) {
  const granted = new Set();

  if (!user?.role) {
    return granted;
  }

  for (const [capability, rule] of Object.entries(CAPABILITIES)) {
    const roles = Array.isArray(rule) ? rule : rule.roles;
    const also = Array.isArray(rule) ? null : rule.also;

    if (roles.includes(user.role) || (typeof also === 'function' && also(user))) {
      granted.add(capability);
    }
  }

  return granted;
}

export function hasCapability(user, capability) {
  return resolveCapabilities(user).has(capability);
}