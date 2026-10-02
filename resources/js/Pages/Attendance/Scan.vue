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
      <div ref="scannerRef" id="scannerRef" class="relative mx-auto max-w-sm rounded-xl border-2 border-dashed border-[#6F9435] bg-[#263D26] p-4">
        <div v-if="cameraLoading" class="absolute inset-0 flex items-center justify-center rounded-xl bg-[#263D26]/80">
          <SkeletonLoader variant="card" :lines="1" class="h-6 w-40" />
        </div>
        <p v-if="!scanning && !cameraError" class="text-center text-xs text-[#8fa06a]">Klik untuk aktifkan kamera</p>
        <p v-if="cameraError" class="text-center text-xs text-[#ef4419]">{{ cameraError }}</p>
        <video v-if="scanning" ref="videoRef" class="mx-auto max-h-[300px] w-full rounded-lg"></video>
        <canvas v-if="scanning" ref="canvasRef" class="hidden"></canvas>
      </div>
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
        <button type="submit" :disabled="form.processing || !form.qr_token" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">
          <span v-if="form.processing">Menyimpan...</span>
          <span v-else>Batalkan Kehadiran</span>
        </button>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import { Html5Qrcode } from 'html5-qrcode';
import { enqueue } from '@/OfflineQueue.js';
import { useNetworkStatus } from '@/Composables/useNetworkStatus.js';
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
const page = usePage();
const toast = useToast();
const scanning = ref(false);
const cameraLoading = ref(false);
const cameraError = ref('');
const scanResult = ref('');
const checkInError = ref('');
const checkInSaved = ref(false);
const videoRef = ref(null);
const canvasRef = ref(null);
const scannerRef = ref(null);
const html5QrCode = ref(null);

const { isOnline, flushQueue } = useNetworkStatus();

// Status izin dipantau supaya pengguna melihat Features perangkat yang masih
// ditolak browser sebelum menekan tombol kamera.
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
    state: geolocationPermission.value,
    needed: sessionHasGeofence.value ? 'Verifikasi jarak presensi' : 'Tidak dipakai pada sesi ini',
  },
]);

const PERMISSION_NOTES = {
  granted: 'Sudah diizinkan.',
  prompt: 'Akan diminta saat fitur digunakan.',
  denied: 'Ditolak. Aktifkan di pengaturan situs pada browser.',
  unknown: 'Status tidak dapat dibaca browser ini.',
};

function izinNote(state) {
  if (state === 'denied') return PERMISSION_NOTES.denied;
  if (state === 'granted') return PERMISSION_NOTES.granted;
  if (state === 'prompt') return PERMISSION_NOTES.prompt;
  return PERMISSION_NOTES.unknown;
}

function izinIcon(state) {
  if (state === 'granted') return '✓';
  if (state === 'denied') return '✕';
  if (state === 'prompt') return '•';
  return '?';
}

function izinClass(state) {
  if (state === 'granted') return 'text-[#A7B92A]';
  if (state === 'denied') return 'text-[#ef4419]';
  return 'text-[#EDD330]';
}

async function refreshPermissionStates() {
  windowIsSecure.value = window.isSecureContext;

  if (!hasCameraSupport()) {
    cameraPermission.value = 'denied';
  } else {
    cameraPermission.value = await permissionState('camera');
  }

  geolocationPermission.value =
    typeof navigator !== 'undefined' && navigator.geolocation
      ? await permissionState('geolocation')
      : 'denied';
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

const form = useForm({
  qr_token: '',
  member_id: props.member?.id ?? '',
  keterangan: 'Hadir',
  nama: props.session?.nama ?? '',
  latitude: null,
  longitude: null,
});

// Sesi dengan titik lokasi mewajibkan anggota berada di dalam radiusnya,
// jadi koordinat perangkat wajib dikumpulkan sebelum presensi dikirim.
const sessionHasGeofence = computed(
  () =>
    props.session?.latitude !== null &&
    props.session?.latitude !== undefined &&
    props.session?.radius !== null &&
    props.session?.radius !== undefined
);


function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}

async function startScan() {
  cameraLoading.value = true;
  cameraError.value = '';
  form.qr_token = props.session.qr_token;

  // Minta izin kamera lebih dulu agar kegagalanPermissions-Policy (camera=())
  // bisa dibedakan dari penolakan pengguna, dan pesan yang muncul bisa
  // menyebutkan tindakan yang benar.
  const preflight = await preflightCamera();

  if (!preflight.ok) {
    cameraLoading.value = false;
    scanning.value = false;
    cameraError.value = preflight.message;
    toast.error(preflight.message);
    refreshPermissionStates();
    return;
  }

  scanning.value = true;
  html5QrCode.value = new Html5Qrcode('scannerRef');

  try {
    await html5QrCode.value.start(
      { facingMode: 'environment' },
      {
        width: 640,
        height: 480,
      },
      (decoded) => {
        scanResult.value = decoded;
        form.qr_token = decoded;
        checkInError.value = '';
        checkInSaved.value = false;
        stopScan();
        autoSubmit();
      },
      (error) => {
        if (error && error.message && !error.message.includes('NotFoundException')) {
          // Silent continuous scanning error - not displayed to user
        }
      }
    );
    cameraLoading.value = false;
    refreshPermissionStates();
  } catch (err) {
    cameraLoading.value = false;
    scanning.value = false;
    cameraError.value = describeMediaError(err);
    toast.error(cameraError.value);
    html5QrCode.value = null;
  }
}

function stopScan() {
  if (html5QrCode.value) {
    html5QrCode.value.clear().catch(() => {});
    html5QrCode.value = null;
  }
  scanning.value = false;
  cameraLoading.value = false;
}

/**
 * Simpan presensi. Saat sinyal hilang, payload diantrekan di perangkat dan
 * dikirim otomatis setelah koneksi kembali. Endpoint checkIn mengulang baris
 * yang sama, jadi pengiriman ulang tidak menghasilkan presensi ganda.
 */
async function queueOrSubmit({ stayOnPage }) {
  checkInError.value = '';
  checkInSaved.value = false;

  // Koordinat hanya diminta bila sesi punya geofence, sehingga sesi biasa tidak
  // memicu dialog izin lokasi tanpa alasan.
  const position = sessionHasGeofence.value ? await requestCoordinates() : { ok: false };

  if (sessionHasGeofence.value && !position.ok) {
    // QR sudah terbaca, jadi token tetap disimpan agar anggota bisa mencoba
    // lagi tanpa memindai ulang setelah lokasi berhasil dibaca.
    checkInError.value = `${position.reason} Presensi belum tercatat.`;
    toast.error(position.reason);
    refreshPermissionStates();
    return;
  }

  const payload = {
    qr_token: form.qr_token,
    member_id: form.member_id,
    keterangan: form.keterangan,
    latitude: position.ok ? position.latitude : null,
    longitude: position.ok ? position.longitude : null,
  };

  if (!isOnline.value) {
    enqueue(payload, { url: '/attendance/check-in' });
    toast.warning('Tidak ada sinyal. Presensi disimpan di perangkat dan dikirim otomatis nanti.');
    resetForNextScan();
    return;
  }

  form.post('/attendance/check-in', {
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
  if (!form.qr_token || !form.member_id) {
    toast.warning('Token QR atau ID anggota tidak ditemukan.');
    return;
  }
  queueOrSubmit({ stayOnPage: false });
}

function submitCheckIn() {
  if (!form.qr_token) {
    toast.warning('Scan QR terlebih dahulu atau masukkan token secara manual.');
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
  stopScan();

  // Listener izin dilepas agar halaman yang sudah ditutup tidak tetap
  // menerima perubahan status Permissions-Policy.
  permissionWatchers.forEach((item) => item?.removeEventListener?.('change', item));
  permissionWatchers.length = 0;
});
</script>





