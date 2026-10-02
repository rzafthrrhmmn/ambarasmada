<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Latihan Rutin</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Scan QR Presensi</h1>
      <p class="mt-1 text-sm text-[#8fa06a]">{{ session.nama }} | {{ formatDate(session.tanggal) }}</p>
    </div>

    <div v-if="scanResult" class="mb-6 rounded-2xl border border-[#A7B92B]/40 bg-[#263D26] p-5">
      <p class="text-sm font-semibold text-[#A7B92B]">QR berhasil discan!</p>
      <p class="mt-1 text-xs text-[#8fa06a]">Token: {{ scanResult }}</p>
    </div>

    <div
      v-if="checkInSaved"
      class="mb-6 rounded-2xl border border-[#6F9435] bg-[#263D26] p-5"
      role="status"
    >
      <p class="text-sm font-semibold text-[#A7B92B]">Presensi tercatat</p>
      <p class="mt-1 text-xs text-[#8fa06a]">
        Kehadiran Anda sudah tersimpan di sistem. Anda dapat kembali ke daftar sesi.
      </p>
    </div>

    <div
      v-if="checkInError"
      class="mb-6 rounded-2xl border border-[#ef4419]/60 bg-[#263D26] p-5"
      role="alert"
    >
      <p class="text-sm font-semibold text-[#ef4419]">Presensi BELUM tercatat</p>
      <p class="mt-1 text-xs leading-5 text-[#d4dc9a]">{{ checkInError }}</p>
      <p class="mt-2 text-xs text-[#8fa06a]">
        Perbaiki penyebabnya lalu tekan "Catat Kehadiran" lagi. Kode QR tidak perlu
        dipindai ulang.
      </p>
    </div>

    <div class="mb-6">
      <div class="relative mx-auto max-w-sm">
        <!--
          Wadah scanner ini sengaja dibiarkan tanpa elemen anak.
          Html5Qrcode.start() memanggil clearElement() yang mengosongkan
          innerHTML elemen ini. Kalau ada elemen milik Vue di dalamnya, Vue
          masih memegang simpul itu sehingga patching gagal ("Cannot read
          properties of null"), cameraLoading tidak pernah kembali false dan
          QR tidak pernah terbaca. Semua indikator dipindahkan ke sibling
          di luar wadah ini.
        -->
        <div
          id="scannerRef"
          ref="scannerRef"
          class="min-h-[7rem] w-full rounded-xl border-2 border-dashed border-[#6F9435] bg-[#263D26]"
        ></div>

        <div v-if="cameraLoading" class="absolute inset-0 flex items-center justify-center rounded-xl bg-[#263D26]/80">
          <SkeletonLoader variant="card" :lines="1" class="h-6 w-40" />
        </div>
        <p
          v-if="!scanning && !cameraError"
          class="absolute inset-0 flex items-center justify-center px-4 text-center text-xs text-[#8fa06a]"
        >
          Klik untuk aktifkan kamera
        </p>
      </div>
      <p v-if="cameraError" class="mt-2 text-center text-xs leading-5 text-[#ef4419]">{{ cameraError }}</p>
      <p v-if="scanning" class="mt-2 text-center text-xs text-[#8fa06a]">Arahkan kamera ke QR Code sesi</p>
      <div class="mt-3 flex justify-center gap-2">
        <button v-if="!scanning" @click="startScan" :disabled="cameraLoading" type="button" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">
          <span v-if="cameraLoading">Memulai kamera...</span>
          <span v-else>Aktifkan Kamera</span>
        </button>
        <button v-if="scanning" @click="stopScan" type="button" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Matikan Kamera</button>
      </div>
    </div>

    <div class="mb-6 rounded-2xl border border-[#6F9435] bg-[#335233] p-5">
      <h2 class="text-sm font-semibold text-[#f0ead8]">Izin perangkat yang dibutuhkan</h2>
      <ul class="mt-3 grid gap-2 sm:grid-cols-2">
        <li
          v-for="izin in permissionList"
          :key="izin.key"
          class="flex items-start gap-2 rounded-lg border border-[#6F9435]/60 bg-[#263D26] px-3 py-2"
        >
          <span class="mt-0.5 text-xs" :class="izinClass(izin.state)">{{ izinIcon(izin.state) }}</span>
          <span class="text-xs">
            <span class="block font-medium text-[#d4dc9a]">{{ izin.label }}</span>
            <span class="block text-[#8fa06a]">{{ izinNote(izin.state) }}</span>
            <span v-if="izin.state !== 'not-needed'" class="mt-0.5 block text-[#8fa06a]/80">{{ izin.needed }}</span>
          </span>
        </li>
      </ul>
      <p v-if="!windowIsSecure" class="mt-3 text-xs text-[#ef4419]">
        Situs ini belum dibuka lewat HTTPS. Kamera dan lokasi hanya dapat diakses pada koneksi aman.
      </p>
    </div>

    <div class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5">
      <h2 class="mb-3 font-semibold text-[#f0ead8]">Cetak Kehadiran</h2>
      <form @submit.prevent="submitCheckIn" class="grid gap-3">
        <input v-model="form.qr_token" type="hidden" />
        <label class="block">
          <span class="text-xs font-medium">Nama sesi presensi</span>
          <input v-model="form.nama" type="text" readonly class="mt-1 w-full rounded-lg border border-[#6F9435]/30 bg-[#263D26] px-3 py-2 text-sm text-[#8fa06a]" />
        </label>
        <label class="block">
          <span class="text-xs font-medium">Keterangan</span>
          <select v-model="form.keterangan" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]">
            <option>Hadir</option>
            <option>Izin</option>
            <option>Sakit</option>
            <option>Alpa</option>
          </select>
        </label>
        <input v-model="form.member_id" type="hidden" />
        <button v-if="canScan" type="submit" :disabled="isSubmitting || !form.qr_token" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">
          <span v-if="isSubmitting">Menyimpan...</span>
          <span v-else>Catat Kehadiran</span>
        </button>
        <p v-if="!form.qr_token" class="text-xs text-[#8fa06a]">
          Tombol aktif setelah QR Code sesi berhasil dipindai.
        </p>
      </form>

      <div v-if="cameraError" class="mt-4 border-t border-[#6F9435]/60 pt-4">
        <p class="text-xs leading-5 text-[#8fa06a]">
          Kamera tidak dapat dipakai. Untuk sementara Anda bisa mencatat kehadiran tanpa
          memindai QR Code, tetapi verifier jarak lokasi tetap diperiksa server bila sesi ini
          punya titik lokasi.
        </p>
        <button type="button" @click="useManualToken" class="mt-2 w-full rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">
          Catat tanpa memindai QR
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import { Html5Qrcode } from 'html5-qrcode';
import { enqueue } from '@/OfflineQueue.js';
import { useNetworkStatus } from '@/Composables/useNetworkStatus.js';
import { useAccess } from '@/Composables/useAccess.js';
import {
  describeMediaError,
  hasCameraSupport,
  permissionState,
  preflightCamera,
  requestCoordinates,
} from '@/Composables/useDevicePermissions.js';

const props = defineProps({
  session: Object,
  member: Object,
});
const { can } = useAccess();
const toast = useToast();
const scanning = ref(false);
const cameraLoading = ref(false);
const cameraError = ref('');
const scanResult = ref('');
const checkInError = ref('');
const checkInSaved = ref(false);
const scannerRef = ref(null);
const html5QrCode = ref(null);

// Satu pengiriman pada satu waktu. Pemindai QR.decode bisa memanggil callback
// beberapa kali untuk frame beruntun sebelum stream berhenti, dan tanpa penjaga
// itu setiap frame memicu POST beserta navigasinya sendiri.
const isSubmitting = ref(false);

// Halaman bisa ditutup selagi startScan masih menunggu izin kamera. Tanpa
// penanda ini, lanjutan async-nya tetap berjalan pada halaman yang sudah
// ditinggalkan dan memunculkan pesan kamera palsu setelah pengguna pindah halaman.
let disposed = false;

const { isOnline } = useNetworkStatus();

// Sesi dengan titik lokasi mewajibkan anggota berada di dalam radiusnya, jadi
// koordinat perangkat wajib dikumpulkan sebelum presensi dikirim.
//
// Syaratnya harus sama persis dengan $hasGeofence di AttendanceController, yang
// memakai latitude + longitude + radius. Kalau klien hanya mengecek latitude +
// radius, sesi tanpa longitude tetap memicu dialog izin lokasi dan menunggu GPS
// sampai timeout, padahal server tidak memverifikasi jarak sama sekali.
const sessionHasGeofence = computed(() => {
  const s = props.session;
  return s?.latitude != null && s?.longitude != null && s?.radius != null;
});

// Status izin dipantau supaya pengguna melihat perangkat yang masih ditolak
// browser sebelum menekan tombol kamera.
const cameraPermission = ref('unknown');
const geolocationPermission = ref('unknown');
const windowIsSecure = ref(true);

const permissionList = computed(() => [
  {
    key: 'camera',
    label: 'Kamera',
    state: cameraPermission.value,
    needed: 'Memindai QR Code sesi',
  },
  {
    key: 'geolocation',
    label: 'Lokasi',
    state: sessionHasGeofence.value ? geolocationPermission.value : 'not-needed',
    needed: sessionHasGeofence.value ? 'Verifikasi jarak presensi' : 'Tidak dipakai pada sesi ini',
  },
]);

const PERMISSION_NOTES = {
  granted: 'Sudah diizinkan.',
  prompt: 'Akan diminta saat fitur digunakan.',
  denied: 'Ditolak. Aktifkan di pengaturan situs pada browser.',
  // Beberapa browser (Firefox, Safari) tidak melaporkan status izin
  // geolokasi lewat Permissions API. Itu bukan berarti izin ditolak, jadi
  // pesannya harus mengarahkan ke langkah berikutnya, bukan membuat buntu.
  unknown: 'Browser tidak melaporkan status izin. Izin tetap diminta saat fitur dipakai.',
  'not-needed': 'Tidak diperlukan pada sesi ini.',
};

function izinNote(state) {
  if (state === 'denied') return PERMISSION_NOTES.denied;
  if (state === 'granted') return PERMISSION_NOTES.granted;
  if (state === 'prompt') return PERMISSION_NOTES.prompt;
  if (state === 'not-needed') return PERMISSION_NOTES['not-needed'];
  return PERMISSION_NOTES.unknown;
}

function izinIcon(state) {
  if (state === 'granted') return '✓';
  if (state === 'denied') return '✕';
  if (state === 'prompt') return '•';
  if (state === 'not-needed') return '–';
  return '?';
}

function izinClass(state) {
  if (state === 'granted') return 'text-[#A7B92A]';
  if (state === 'denied') return 'text-[#ef4419]';
  if (state === 'not-needed') return 'text-[#8fa06a]';
  return 'text-[#EDD330]';
}

async function refreshPermissionStates(known = {}) {
  windowIsSecure.value = window.isSecureContext;

  if (!hasCameraSupport()) {
    cameraPermission.value = 'denied';
  } else {
    cameraPermission.value = known.camera ?? (await permissionState('camera'));
  }

  geolocationPermission.value =
    known.geolocation ??
    (typeof navigator !== 'undefined' && navigator.geolocation
      ? await permissionState('geolocation')
      : 'denied');
}

const permissionWatchers = [];

function watchPermission(name, target) {
  if (typeof navigator === 'undefined' || !navigator.permissions?.query) return;

  try {
    const result = navigator.permissions.query({ name });
    if (typeof result?.then !== 'function') return;

    result
      .then((status) => {
        target.value = status.state;

        const onChange = () => {
          target.value = status.state;
        };

        status.addEventListener?.('change', onChange);
        permissionWatchers.push(status, onChange);
      })
      .catch(() => {});
  } catch {
    // Prompt permission tidak didukung untuk tipe ini; biarkan 'unknown'.
  }
}

// Halaman ini sudah dibatasi role:Anggota di rute dan guardMember di
// AttendanceController, sehingga nilai ini praktis selalu benar. Pintunya
// tetap ditulis supaya tombol yang benar-benar mengirim presensi tidak lagi
// bergantung pada anggapan "semua yang bisa membuka halaman pasti boleh
// mencatat".
const canScan = computed(() => can('attendance.self.scan'));

const form = useForm({
  qr_token: '',
  // Nilai ini sengaja tetap dikirim. Bentuk payload ini adalah kontrak antrean
  // offline, dan AttendanceController menerimanya sebagai nullable integer lalu
  // mengabaikannya: yang menentukan adalah profil anggota milik pengguna yang
  // sedang login. Jadi field ini kini kosmetik, bukan pembuka celah.
  member_id: props.member?.id ?? '',
  keterangan: 'Hadir',
  nama: props.session?.nama ?? '',
  latitude: null,
  longitude: null,
});

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}

/**
 * Ambil token sesi dari apa pun yang keluar dari pemindai.
 *
 * QR Code yang ditampilkan di halaman sesi memuat URL lengkap
 * (`https://situs/attendance/scan/TOKEN`), bukan token polos, jadi hasil
 * decode tidak bisa langsung dipakai sebagai qr_token. Kalau tidak
 * dinormalkan, server mencari qr_token berisi URL dan membalas 404.
 */
function normaliseToken(decoded) {
  const raw = String(decoded ?? '').trim();
  if (!raw) return '';

  let candidate = raw;

  if (/^https?:\/\//i.test(raw) || raw.startsWith('/')) {
    try {
      // Pathname tidak memuat query string maupun fragment.
      const url = new URL(raw, window.location.origin);
      const segments = url.pathname.split('/').filter(Boolean);
      candidate = segments.at(-1) ?? '';
    } catch {
      candidate = raw;
    }
  }

  return candidate.trim().toUpperCase();
}

async function startScan() {
  cameraLoading.value = true;
  cameraError.value = '';

  // Token TIDAK diisi di sini. Mengisinya sebelum kamera berhasil membuat tombol
  // "Catat Kehadiran" aktif begitu kamera gagal dinyalakan, jadi anggota bisa
  // mencatat kehadiran tanpa pernah memindai QR sama sekali. Token baru diisi
  // setelah kode benar-benar terbaca, atau lewat fallback manual yang eksplisit.

  // Minta izin kamera lebih dulu agar kegagalanPermissions-Policy (camera=())
  // bisa dibedakan dari penolakan pengguna, dan pesan yang muncul bisa
  // menyebutkan tindakan yang benar.
  const preflight = await preflightCamera();

  if (disposed) return;

  if (!preflight.ok) {
    cameraLoading.value = false;
    scanning.value = false;
    cameraError.value = preflight.message;
    toast.error(preflight.message);

    // Kegagalan nyata lebih informatif daripada status yang tidak bisa dibaca.
    const blocked = ['NotAllowedError', 'SecurityError', 'insecure', 'unsupported'];
    refreshPermissionStates({
      camera: blocked.includes(preflight.code) ? 'denied' : undefined,
    });
    return;
  }

  scanning.value = true;
  const scanner = new Html5Qrcode('scannerRef');
  html5QrCode.value = scanner;

  try {
    await scanner.start(
      { facingMode: 'environment' },
      {
        width: 640,
        height: 480,
      },
      (decoded) => {
        // Callback decode bisa berulang untuk beberapa frame beruntun sebelum
        // stream benar-benar berhenti. Penjaga isSubmitting memastikan hanya
        // satu pengiriman yang terjadi.
        if (isSubmitting.value) return;

        // QR memuat URL halaman scan, bukan token polos.
        const token = normaliseToken(decoded);

        if (!token) {
          cameraError.value = 'Kode QR tidak terbaca. Pastikan seluruh kode ada di dalam bingkai, lalu pindai ulang.';
          toast.error(cameraError.value);
          return;
        }

        scanResult.value = token;
        form.qr_token = token;
        checkInError.value = '';
        checkInSaved.value = false;
        stopScan();
        autoSubmit();
      },
      () => {}
    );

    // Halaman ditutup selagi start() masih menunggu; scanner yang baru dibuat
    // tidak boleh lanjut menyalakan kamera pada elemen yang sudah dilepas.
    if (disposed) {
      html5QrCode.value = null;
      await scanner.stop?.().catch(() => {});
      await scanner.clear?.().catch(() => {});
      return;
    }

    cameraLoading.value = false;
    refreshPermissionStates();
  } catch (err) {
    if (disposed) return;
    cameraLoading.value = false;
    scanning.value = false;
    cameraError.value = describeMediaError(err);
    toast.error(cameraError.value);
    html5QrCode.value = null;
  }
}

/**
 * Fallback untuk perangkat yang kamera-/browser-nya tidak bisa memindai.
 * Sengaja harus ditekan anggota sendiri supaya "tanpa QR" tidak pernah terjadi
 * diam-diam hanya karena kamera gagal dinyalakan. Verifikasi jarak lokasi tetap
 * dijalankan server untuk sesi bergeofence.
 */
function useManualToken() {
  form.qr_token = props.session?.qr_token ?? '';
  scanResult.value = ' dicatat manual (QR tidak dipindai)';
  toast.warning('Presensi dicatat tanpa pemindaian QR. Minta konfirmasi pembina.');
}

function stopScan() {
  const scanner = html5QrCode.value;
  html5QrCode.value = null;

  // Status direset sebelum kerja asynchronous apa pun supaya tombol dan
  // skeleton loader langsung kembali normal.
  scanning.value = false;
  cameraLoading.value = false;

  if (!scanner) return;

  // clear() melempar "Cannot clear while scan is ongoing" kalau scanner masih
  // berjalan, dan callback decode justru dipanggil saat itu masih berjalan.
  // stop() lebih dulu, baru clear() untuk mengosongkan wadah.
  Promise.resolve()
    .then(() => scanner.stop?.())
    .catch(() => {})
    .then(() => scanner.clear?.())
    .catch(() => {});
}

/**
 * Simpan presensi. Saat sinyal hilang, payload diantrekan di perangkat dan
 * dikirim otomatis setelah koneksi kembali. Endpoint checkIn mengulang baris
 * yang sama, jadi pengiriman ulang tidak menghasilkan presensi ganda.
 */
async function queueOrSubmit({ stayOnPage }) {
  // Satu presensi pada satu waktu. Tanpa ini, dua callback decode atau tombol
  // yang ditekan dua kali bisa mengirim permintaan bersamaan.
  if (isSubmitting.value) return;

  isSubmitting.value = true;
  checkInError.value = '';
  checkInSaved.value = false;

  try {
    await runCheckIn({ stayOnPage });
  } finally {
    isSubmitting.value = false;
  }
}

async function runCheckIn({ stayOnPage }) {
  // Koordinat hanya diminta bila sesi punya geofence, sehingga sesi biasa tidak
  // memicu dialog izin lokasi tanpa alasan.
  const position = sessionHasGeofence.value ? await requestCoordinates() : { ok: false };

  if (sessionHasGeofence.value && !position.ok) {
    // QR sudah terbaca, jadi token tetap disimpan agar anggota bisa mencoba
    // lagi tanpa memindai ulang setelah lokasi berhasil dibaca.
    checkInError.value = `${position.reason} Presensi belum tercatat.`;
    toast.error(position.reason);

    // Timeout bukan penolakan izin, jadi hanya 'denied' yang mengubah status.
    if (position.denied) geolocationPermission.value = 'denied';
    else refreshPermissionStates();
    return;
  }

// Koordinat harus ditulis ke form, bukan hanya ke payload antrean. form.post()
// mengirim seluruh isi form, jadi kalau hanya payload yang diisi, permintaan
// online tetap mengirim latitude dan longitude null dan server menolak dengan
// "Lokasi perangkat tidak terkirim".
form.latitude = position.ok ? position.latitude : null;
form.longitude = position.ok ? position.longitude : null;

const payload = {
  qr_token: form.qr_token,
  member_id: form.member_id,
  keterangan: form.keterangan,
  latitude: form.latitude,
  longitude: form.longitude,
};

  if (!isOnline.value) {
    enqueue(payload, { url: '/attendance/check-in' });
    toast.warning('Tidak ada sinyal. Presensi disimpan di perangkat dan dikirim otomatis nanti.');
    resetForNextScan();
    return;
  }

  await form.post('/attendance/check-in', {
    onSuccess: () => {
      checkInSaved.value = true;
      checkInError.value = '';
      toast.success('Presensi berhasil disimpan!');

      if (stayOnPage) {
        resetForNextScan();
      } else {
        router.visit(route('attendance.index'));
      }
    },
    onError: (errors) => {
      // Jaringan bisa hilang di tengah pengiriman meski navigator.onLine.
      const message = pickErrorMessage(errors);

      if (!hasFieldErrors(errors)) {
        enqueue(payload, { url: '/attendance/check-in' });
        toast.warning('Gagal terkirim, disimpan di perangkat. ' + message);
        resetForNextScan();
        return;
      }

      // Penyebab sebenarnya (geofence, sesi, validasi) harus terlihat. Sebelumnya
      // hanya 'Coba lagi.' yang tampil, sehingga anggota mengira QR-nya gagal discan
      // padahal server menolak karena jarak atau lokasi tidak terkirim.
      checkInError.value = message;
      toast.error(message);
    },
  });
}

/**
 * Ambil pesan paling informatif dari error Inertia.
 *
 * Server menaruh sebab sebenarnya di errors.location (geofence) atau
 * errors.message. Pesan yang paling spesifik didahulukan supaya anggota tahu
 * harus mendekatkan diri, mengaktifkan lokasi, atau memeriksa kembali kode QR.
 */
function pickErrorMessage(errors) {
  if (!errors || typeof errors !== 'object') return 'Presensi gagal disimpan.';

  const location = errors.location;
  const message = errors.message;

  if (typeof location === 'string' && location) return location;
  if (Array.isArray(location) && location.length) return location[0];
  if (typeof message === 'string' && message) return message;
  if (Array.isArray(message) && message.length) return message[0];

  const firstField = Object.entries(errors).find(
    ([key, value]) => key !== 'message' && (typeof value === 'string' || Array.isArray(value))
  );

  if (firstField) {
    const [, value] = firstField;
    return Array.isArray(value) ? value[0] : value;
  }

  return 'Presensi gagal disimpan.';
}

function hasFieldErrors(errors) {
  if (!errors || typeof errors !== 'object') return false;
  return Object.keys(errors).some((key) => key !== 'message');
}

function resetForNextScan() {
  form.qr_token = '';
  form.keterangan = 'Hadir';
  scanResult.value = '';
}
function autoSubmit() {
  // Hanya token QR yang diperiksa. member_id tidak lagi menentukan apa pun di
  // server: AttendanceController::checkIn memakai profil anggota milik pengguna
  // yang login dan mengabaikan nilai yang dikirim klien. Meminta member_id di
  // sini membuat pemindaian otomatis diam-diam gagal untuk akun yang belum
  // punya baris member, padahal server sudah punya pesan kesalahan yang jelas
  // untuk keadaan itu.
  if (!form.qr_token) {
    toast.warning('Token QR tidak ditemukan.');
    return;
  }
  queueOrSubmit({ stayOnPage: false });
}

function submitCheckIn() {
  if (!form.qr_token) {
    toast.warning('Pindai QR Code sesi terlebih dahulu.');
    return;
  }
  queueOrSubmit({ stayOnPage: true });
}

onMounted(() => {
  refreshPermissionStates();
  watchPermission('camera', cameraPermission);
  watchPermission('geolocation', geolocationPermission);
});

onBeforeUnmount(() => {
  disposed = true;
  stopScan();

  // Listener izin dilepas agar halaman yang sudah ditutup tidak tetap
  // menerima perubahan status Permissions-Policy.
  permissionWatchers.forEach((item) => item?.removeEventListener?.('change', item));
  permissionWatchers.length = 0;
});
</script>





