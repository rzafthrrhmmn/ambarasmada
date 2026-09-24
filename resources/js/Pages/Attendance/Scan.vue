<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Latihan Rutin</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Scan QR Presensi</h1>
      <p class="mt-1 text-sm text-[#8fa06a]">{{ session.nama }} | {{ formatDate(session.tanggal) }}</p>
    </div>

    <div v-if="scanResult" class="mb-6 rounded-2xl border border-[#A7B92B]/40 bg-[#263D26] p-5">
      <p class="text-sm font-semibold text-[#A7B92B]">Scan berhasil!</p>
      <p class="mt-1 text-xs text-[#8fa06a]">Token: {{ scanResult }}</p>
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
import { ref, onMounted, onBeforeUnmount, inject } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';

import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import { Html5Qrcode } from 'html5-qrcode';

const props = defineProps({
  session: Object,
  member: Object,
});
const page = usePage();
$toast = inject('toast');
const scanning = ref(false);
const cameraLoading = ref(false);
const cameraError = ref('');
const scanResult = ref('');
const videoRef = ref(null);
const canvasRef = ref(null);
const scannerRef = ref(null);
const html5QrCode = ref(null);

const form = useForm({
  qr_token: '',
  member_id: props.member?.id ?? '',
  keterangan: 'Hadir',
  nama: props.session?.nama ?? '',
});

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}

async function startScan() {
  cameraLoading.value = true;
  cameraError.value = '';
  scanning.value = true;
  form.qr_token = props.session.qr_token;

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
        $toast.success('QR berhasil discan!');
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
  } catch (err) {
    cameraLoading.value = false;
    scanning.value = false;
    if (err.name === 'NotAllowedError' || err.name === 'NotAllowedError' || (err.message && err.message.includes('permission'))) {
      cameraError.value = 'Akses kamera ditolak. Silakan izinkan akses kamera di pengaturan browser.';
      $toast.error('Izin kamera ditolak. Aktifkan izin di pengaturan browser Anda.');
    } else if (err.name === 'NotFoundError') {
      cameraError.value = 'Tidak ditemukan kamera di perangkat ini. Gunakan upload foto sebagai alternatif.';
      $toast.error('Kamera tidak ditemukan pada perangkat ini.');
    } else if (err.name === 'NotReadableError' || err.name === 'BusyError') {
      cameraError.value = 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi yang menggunakan kamera.';
      $toast.warn('Kamera sedang digunakan aplikasi lain.');
    } else {
      cameraError.value = 'Gagal mengakses kamera: ' + (err.message || err);
      $toast.error('Gagal mengakses kamera. Coba muat ulang halaman.');
    }
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

function autoSubmit() {
  if (!form.qr_token || !form.member_id) {
    $toast.warning('Token QR atau ID anggota tidak ditemukan.');
    return;
  }
  form.post('/attendance/check-in', {
    onSuccess: () => {
      $toast.success('Presensi berhasil disimpan!');
      router.visit(route('attendance.index'));
    },
    onError: (errors) => {
      $toast.error('Gagal menyimpan presensi. ' + (errors?.message || 'Coba lagi.'));
    },
  });
}

function submitCheckIn() {
  if (!form.qr_token) {
    $toast.warning('Scan QR terlebih dahulu atau masukkan token secara manual.');
    return;
  }
  form.post('/attendance/check-in', {
    onSuccess: () => {
      $toast.success('Presensi berhasil disimpan!');
      router.visit(route('attendance.index'));
    },
    onError: (errors) => {
      $toast.error('Gagal menyimpan presensi. ' + (errors?.message || 'Coba lagi.'));
    },
  });
}

onBeforeUnmount(() => {
  stopScan();
});
</script>





