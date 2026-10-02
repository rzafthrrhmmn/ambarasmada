<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-medium text-[#EDD330]">Latihan Rutin</p><h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Presensi Anggota</h1><p class="mt-1 text-sm text-[#8fa06a]">Buat sesi latihan, gunakan QR Code, dan lihat rekap kehadiran.</p></div><button v-if="canManage" @click="openCreate" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Sesi latihan</button></div>
    <div class="grid gap-5 lg:grid-cols-3">
      <section class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435] lg:col-span-2">
        <h2 class="mb-4 font-semibold text-[#f0ead8]">Riwayat sesi</h2>
        <div class="space-y-3">
          <SkeletonLoader v-if="!sessions || !sessions.data" variant="list" :lines="5" />
        <div v-else v-for="session in sessions.data" :key="session.id" class="flex flex-col gap-3 rounded-xl border border-[#6F9435] bg-[#335233] p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <Link :href="`/attendance/${session.id}`" class="font-semibold text-[#EDD330] hover:underline">{{ session.nama }}</Link>
              <p class="mt-1 text-xs text-[#8fa06a]">{{ formatDate(session.tanggal) }} • {{ session.lokasi || '-' }} • {{ session.attendances_count }} presensi</p>
            </div>
            <div class="flex flex-wrap gap-2 items-center">
              <span class="rounded-full bg-[#335233] px-2.5 py-1 text-xs text-[#EDD330]">{{ session.qr_token }}</span>
              <button v-if="canManage" @click="openQr(session)" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">QR Code</button>
              <Link v-if="$page.props.auth?.user?.role === 'Anggota'" :href="route('attendance.scan', session.qr_token)" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">Scan</Link>
            </div>
          </div>
        </div>
        <Pagination :links="sessions.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </section>

      <aside class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435]">
        <h2 class="font-semibold text-[#f0ead8]">QR Code presensi</h2>
        <p class="mt-2 text-sm leading-6 text-[#8fa06a]">Tampilkan QR Code ini kepada anggota untuk memindai kehadiran.</p>
        <div v-if="selectedQr" class="mt-4 rounded-xl bg-[#263D26] p-4 text-center">
          <p class="mb-2 text-xs text-[#8fa06a]">{{ selectedQr.nama }}</p>
          <img :src="selectedQr.imageDataUrl" alt="QR Code presensi" class="mx-auto h-48 w-48" />
          <p class="mt-2 text-xs text-[#8fa06a]">Kode: {{ selectedQr.qr_token }}</p>
          <p class="mt-1 text-[10px] text-[#8fa06a]">Scan via halaman /attendance/scan/{{ selectedQr.qr_token }}</p>
        </div>
        <div v-else class="mt-4 rounded-xl bg-[#263D26] p-4 text-center">
          <p class="text-xs text-[#8fa06a]">Pilih sesi di atas untuk menampilkan QR Code</p>
        </div>
      </aside>
    </div>

    <Modal v-if="showCreate && canManage" title="Buat sesi latihan" @close="closeCreate">
      <form @submit.prevent="submitCreate" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Nama sesi</span><input v-model="form.nama" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <div class="grid gap-3 sm:grid-cols-2">
          <label class="block"><span class="text-xs font-medium">Tanggal</span><input v-model="form.tanggal" type="date" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
          <label class="block"><span class="text-xs font-medium">Lokasi</span><input v-model="form.lokasi" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        </div>
        <label class="block"><span class="text-xs font-medium">Materi latihan (opsional)</span><input @change="onMateriChange" type="file" class="mt-1 text-sm" accept=".pdf,.jpg,.jpeg,.png,.mp4,.webm,.doc,.docx" /></label>
        <p v-if="form.errors.materi" class="text-xs text-[#ef4419]">{{ form.errors.materi }}</p>

        <div class="mt-1">
          <span class="text-xs font-medium">Titik lokasi &amp; radius presensi</span>
          <p class="mb-2 mt-1 text-xs text-[#8fa06a]">Presensi hanya diterima jika anggota berada dalam radius dari titik ini.</p>
          <LocationPicker v-if="showMapPicker" v-model="location" />
          <button v-else type="button" class="w-full rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/20 hover:text-[#EDD330]" @click="showMapPicker = true">
            Tentukan lokasi di peta
          </button>
          <p v-if="form.errors.latitude || form.errors.longitude || form.errors.radius" class="mt-1 text-xs text-[#ef4419]">
            {{ form.errors.latitude || form.errors.longitude || form.errors.radius }}
          </p>
        </div>

        <button class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Buat sesi</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import LocationPicker from '@/Components/LocationPicker.vue';

defineProps({ sessions: Object, members: Array });
const page = usePage();
const canManage = computed(() => {
  const role = page.props.auth?.user?.role;
  return role === 'Admin' || role === 'Pembina' || role === 'Pengurus';
});

const showCreate = ref(false);
const showMapPicker = ref(false);
const location = ref({ latitude: null, longitude: null, radius: 100 });
const selectedQr = ref(null);
const form = useForm({ nama: '', tanggal: new Date().toISOString().slice(0, 10), lokasi: '', materi: null, latitude: null, longitude: null, radius: null });

function submitCreate() {
  form.latitude = location.value.latitude;
  form.longitude = location.value.longitude;
  // Radius hanya bermakna bila ada titik acuan.
  form.radius = location.value.latitude === null ? null : location.value.radius;

  form.post('/attendance', {
    onSuccess: () => {
      showCreate.value = false;
      showMapPicker.value = false;
      location.value = { latitude: null, longitude: null, radius: 100 };
      form.reset();
    },
  });
}

function closeCreate() {
  showCreate.value = false;
  showMapPicker.value = false;
}

function openCreate() {
  form.reset();
  form.tanggal = new Date().toISOString().slice(0, 10);
  location.value = { latitude: null, longitude: null, radius: 100 };
  showMapPicker.value = false;
  showCreate.value = true;
}

function onMateriChange(event) {
  form.materi = event.target.files[0] ?? null;
}

async function openQr(session) {
  const QRCode = (await import('qrcode')).default;
  const scannerUrl = `${window.location.origin}/attendance/scan/${session.qr_token}`;
  selectedQr.value = {
    id: session.id,
    nama: session.nama,
    qr_token: session.qr_token,
    imageDataUrl: await QRCode.toDataURL(scannerUrl, { width: 256, margin: 2, color: { dark: '#1a1a1a', light: '#ffffff' } }),
  };
}

function formatDate(value) {
  return new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
}
</script>


