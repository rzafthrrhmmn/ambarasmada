/**
 * Pemb Permissions-Policy dan permintaan izin perangkat.
 *
 * Browser hanya memunculkan dialog izin saat aplikasi benar-benar menyentuh
 * hardware-nya. Fungsi di sini mengecek kesiapan lebih dulu supaya aplikasi
 * bisa menampilkan penyebab kegagalan yang jelas, alih-alih "Gagal mengakses
 * kamera" tanpa penjelasan.
 *
 * All Permission denied yang tetap muncul berasal dari Permissions-Policy
 * header, bukan dari pengguna. Nilai header di app/Http/Middleware/
 * SecurityHeaders.php harus tetap 'camera=(self), geolocation=(self)'.
 */

const MESSAGES = {
  NotAllowedError:
    'Akses ditolak. Izin kamera belum diaktifkan untuk situs ini. Buka pengaturan situs di browser lalu pilih "Izinkan" untuk kamera, lalu muat ulang halaman ini.',
  SecurityError:
    'Akses kamera diblokir oleh kebijakan keamanan situs. Periksa bahwa aplikasi dibuka langsung dari domain resmi, bukan lewat pratinjau di dalam editor.',
  NotFoundError:
    'Tidak ditemukan kamera pada perangkat ini. Gunakan kamera depan, atau tekan "Catat tanpa memindai QR" di bawah untuk mencatat kehadiran tanpa kamera.',
  NotReadableError:
    'Kamera sedang dipakai aplikasi lain. Tutup aplikasi kamera, WhatsApp video, atau tab lain yang memakai kamera, lalu coba lagi.',
  OverconstrainedError:
    'Kamera tidak mendukung resolusi yang diminta. Coba gunakan kamera lain melalui selector perangkat.',
  AbortError:
    'Akses kamera dibatalkan sebelum selesai. Silakan coba lagi.',
};

/**
 * Status izin sebagai string: 'granted', 'denied', 'prompt', atau 'unknown'.
 *
 * navigator.permissions.query() mengembalikan objek PermissionStatus yang punya
 * properti .state, dan bentuknya berbeda antar browser. Nilai .state yang
 * dipakai di sini; bila browser tidak mendukung nama izin tersebut, hasilnya
 * 'unknown' karena exception dilempar secara sinkron.
 */
export async function permissionState(name) {
  if (typeof navigator === 'undefined' || !navigator.permissions?.query) return 'unknown';

  try {
    const status = await navigator.permissions.query({ name });
    return status?.state ?? 'unknown';
  } catch {
    // Firefox melempar TypeError untuk nama izin yang tidak diketahuinya.
    return 'unknown';
  }
}

/**
 * Apakah perangkat mendukung kamera menurut browser.
 */
export function hasCameraSupport() {
  return typeof navigator !== 'undefined' && !!navigator.mediaDevices?.getUserMedia;
}

/**
 * Pastikan izin kamera diberikan sebelum menjalankan kamera.
 *
 * Tidak ada API browser untuk "meminta" izin tanpa membuka stream, jadi
 * getUserMedia dengan Constraints paling ringan dipakai sebagai pemicu dialog
 * perizin. Stream langsung dihentikan supaya indikator kamera tidak menyala.
 */
export async function preflightCamera() {
  if (!hasCameraSupport()) {
    return {
      ok: false,
      code: 'unsupported',
      message:
        'Browser Anda tidak mendukung akses kamera. Gunakan Chrome atau Safari versi terbaru, atau tekan "Catat tanpa memindai QR" di bawah untuk mencatat kehadiran tanpa kamera.',
    };
  }

  if (!window.isSecureContext) {
    return {
      ok: false,
      code: 'insecure',
      message:
        'Kamera hanya bisa diakses lewat koneksi HTTPS. Alamat situs ini belum aman.',
    };
  }

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });

    // Lepas stream supaya tombol kamera di browser tidak tetap aktif.
    stream.getTracks().forEach((track) => track.stop());

    return { ok: true };
  } catch (error) {
    return { ok: false, code: error.name || 'unknown', message: describeMediaError(error) };
  }
}

/**
 * Pesan yang bisa ditindaklanjuti untuk error dari media devices.
 */
export function describeMediaError(error) {
  const name = error?.name;

  if (name === 'NotAllowedError' || name === 'PermissionDeniedError') return MESSAGES.NotAllowedError;
  if (name === 'SecurityError') return MESSAGES.SecurityError;
  if (name === 'NotFoundError' || name === 'DevicesNotFoundError') return MESSAGES.NotFoundError;
  if (name === 'NotReadableError' || name === 'TrackStartError') return MESSAGES.NotReadableError;
  if (name === 'OverconstrainedError') return MESSAGES.OverconstrainedError;
  if (name === 'AbortError') return MESSAGES.AbortError;

  return `Gagal mengakses kamera. ${error?.message || 'Coba muat ulang halaman.'}`;
}

/**
 * Ambil koordinat perangkat sebagai Promise yang selalu selesai.
 *
 * Error 'denied' dibedakan dari kegagalan sementara agar pesan yang tampil
 * menyebutkan tindakan yang harus dilakukan pengguna.
 */
export function requestCoordinates({ timeout = 12000, maximumAge = 30000 } = {}) {
  return new Promise((resolve) => {
    if (typeof navigator === 'undefined' || !navigator.geolocation) {
      resolve({
        ok: false,
        reason: 'Browser Anda tidak mendukung pembacaan lokasi.',
      });
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) =>
        resolve({
          ok: true,
          latitude: Number(position.coords.latitude.toFixed(8)),
          longitude: Number(position.coords.longitude.toFixed(8)),
          accuracy: position.coords.accuracy,
        }),
      (error) =>
        resolve({
          ok: false,
          // Hanya 'denied' yang berarti izin benar-benar ditolak. Timeout dan
          // POSITION_UNAVAILABLE hanya gangguan sesaat, jadi status izin tidak
          // boleh ditandai 'denied' berdasarkan itu.
          denied: error?.code === error?.PERMISSION_DENIED,
          reason: describeGeolocationError(error),
        }),
      { enableHighAccuracy: true, timeout, maximumAge }
    );
  });
}

export function describeGeolocationError(error) {
  if (error?.code === error?.PERMISSION_DENIED) {
    return 'Izin lokasi ditolak. Aktifkan lokasi untuk situs ini di pengaturan browser agar presensi dapat diverifikasi.';
  }

  if (error?.code === error?.TIMEOUT) {
    return 'Lokasi tidak dapat dibaca tepat waktu. Coba pindah ke area dengan sinyal GPS lebih baik.';
  }

  if (error?.code === error?.POSITION_UNAVAILABLE) {
    return 'Lokasi sedang tidak tersedia. Coba lagi beberapa saat lagi.';
  }

  return 'Lokasi tidak dapat dibaca. Coba pindah ke area dengan sinyal GPS.';
}

/**
 * Geolokasi hanya dibutuhkan bila sesi punya geofence.
 */
export async function requestCoordinatesIfGeofenced(session) {
  const hasGeofence =
    session?.latitude !== null &&
    session?.latitude !== undefined &&
    session?.radius !== null &&
    session?.radius !== undefined;

  if (!hasGeofence) return { ok: false, skipped: true, reason: '' };

  return requestCoordinates();
}
