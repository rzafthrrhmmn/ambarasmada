<template>
  <AppLayout>
    <nav class="mb-5" aria-label="Remah roti">
      <Link href="/attendance" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#EDD330] transition hover:gap-2.5 hover:underline">
        Kembali ke kehadiran saya
      </Link>
    </nav>

    <header class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
      <div class="flex flex-wrap items-center gap-2">
        <span class="rounded-full bg-[#6F9435]/40 px-2.5 py-1 text-xs font-semibold text-[#d4f0a0]">Detail Sesi</span>
        <span v-if="session.qr_dynamic" class="rounded-full bg-[#EDD330]/20 px-2.5 py-1 text-xs font-semibold text-[#EDD330]">QR dinamis</span>
      </div>

      <h1 class="mt-3 break-words text-2xl font-bold text-[#f0ead8] sm:text-3xl">{{ session.nama }}</h1>

      <div class="mt-3 flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#263D26] px-2.5 py-1 text-xs text-[#d4dc9a]">
          {{ formatDate(session.tanggal) }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#263D26] px-2.5 py-1 text-xs text-[#d4dc9a]">
          {{ session.lokasi || 'Lokasi belum diisi' }}
        </span>
        <span v-if="hasGeofence" class="inline-flex items-center gap-1.5 rounded-lg bg-[#263D26] px-2.5 py-1 text-xs text-[#d4dc9a]">
          Geofence {{ session.radius }} m
        </span>
      </div>
    </header>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
      <section class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm lg:col-span-2">
        <h2 class="font-semibold text-[#f0ead8]">Kehadiran saya pada sesi ini</h2>

        <div v-if="myAttendance" class="mt-4 rounded-xl border p-4" :class="statusBorderClass">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusChipClass">
              {{ myAttendance.keterangan }}
            </span>
            <span class="text-xs text-[#8fa06a]">Dicatat {{ formatDateTime(myAttendance.checked_at) }}</span>
          </div>

          <p v-if="myAttendance.catatan" class="mt-3 text-sm text-[#d4dc9a]">Catatan: {{ myAttendance.catatan }}</p>

          <p v-else class="mt-3 text-xs text-[#8fa06a]">
            Catatan otomatis dari pemindaian. Bila keterangan ini keliru, minta pemandu sesi memperbaikinya.
          </p>
        </div>

        <div v-else class="mt-4 rounded-xl border border-dashed border-[#6F9435] bg-[#263D26] p-6 text-center">
          <p class="text-sm font-semibold text-[#f0ead8]">Belum tercatat pada sesi ini</p>
          <p class="mx-auto mt-1 max-w-sm text-xs text-[#8fa06a]">
            Pindai QR Code yang ditampilkan pemandu agar kehadiranmu tercatat.
          </p>
          <Link
            v-if="canScan && scanUrl"
            :href="scanUrl"
            class="mt-4 inline-block rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-xs font-semibold text-white"
          >
            Scan QR Code
          </Link>
        </div>
      </section>

      <aside class="space-y-4">
        <section class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
          <h2 class="font-semibold text-[#f0ead8]">Informasi sesi</h2>

          <dl class="mt-3 space-y-2 text-xs">
            <div v-if="session.qr_token" class="flex items-start justify-between gap-3">
              <dt class="shrink-0 text-[#8fa06a]">Kode QR</dt>
              <dd class="break-all text-right font-mono font-semibold text-[#EDD330]">{{ session.qr_token }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3">
              <dt class="shrink-0 text-[#8fa06a]">Geofence</dt>
              <dd class="text-right text-[#d4dc9a]">
                <template v-if="hasGeofence">Aktif, radius {{ session.radius }} m</template>
                <template v-else>Nonaktif</template>
              </dd>
            </div>
            <div class="flex items-start justify-between gap-3">
              <dt class="shrink-0 text-[#8fa06a]">Ambalan</dt>
              <dd class="text-right text-[#d4dc9a]">{{ session.ambalan?.nama || '-' }}</dd>
            </div>
          </dl>

          <Link
            v-if="canScan && scanUrl"
            :href="scanUrl"
            class="mt-4 block rounded-lg border border-[#6F9435] px-3 py-2 text-center text-xs font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/30"
          >
            Buka halaman pindai QR
          </Link>
        </section>

        <section v-if="session.materi_nama" class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
          <h2 class="font-semibold text-[#f0ead8]">Materi latihan</h2>
          <p class="mt-2 truncate text-sm font-medium text-[#d4dc9a]">{{ session.materi_nama }}</p>
          <p class="mt-0.5 text-xs text-[#8fa06a]">{{ formatFileSize(session.materi_size) }}</p>
          <Link
            :href="materiUrl"
            class="mt-4 block rounded-lg border border-[#6F9435] px-3 py-2 text-center text-xs font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/30"
          >
            Unduh materi
          </Link>
        </section>
      </aside>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import { useAccess } from '@/Composables/useAccess.js';

/**
 * Detail sesi untuk anggota.
 *
 * Hanya memuat data sesi dan catatan kehadiran sendiri. Baris presensi anggota
 * lain, direktori anggota, serta koordinat persis geofence tidak dikirim ke
 * sini oleh AttendanceController, jadi komponen ini tidak pernah punya akses
 * atas data tersebut.
 */
const props = defineProps({
  session: { type: Object, required: true },
  myAttendance: { type: Object, default: null },
});

const { can } = useAccess();
const canScan = computed(() => can('attendance.self.scan') && props.session.can_scan === true);

const hasGeofence = computed(() => props.session.radius !== null && props.session.radius !== undefined);
// Server hanya mengirim qr_token untuk sesi hari ini yang belum tercatat dan
// tidak memakai QR dinamis. Token yang tidak ada karena itu disengaja, bukan
// bug, jadi tautan pemindai ikut disembunyikan.
const scanUrl = computed(() =>
  props.session.qr_token ? route('attendance.scan', props.session.qr_token) : null,
);
const materiUrl = computed(() => route('attendance.materi.download', props.session.id));

const statusChipClass = computed(() => {
  switch (props.myAttendance?.keterangan) {
    case 'Hadir':
      return 'bg-[#A7B92B]/25 text-[#d4f0a0]';
    case 'Izin':
      return 'bg-[#EDD330]/20 text-[#EDD330]';
    case 'Sakit':
      return 'bg-[#f0c987]/20 text-[#f0c987]';
    case 'Alpa':
      return 'bg-[#ef4419]/25 text-[#ffb4a3]';
    default:
      return 'bg-[#263D26] text-[#8fa06a]';
  }
});

const statusBorderClass = computed(() =>
  props.myAttendance?.keterangan === 'Hadir' ? 'border-[#A7B92B]/60 bg-[#2d4a2d]' : 'border-[#6F9435] bg-[#263D26]',
);

function formatDate(value) {
  return value
    ? new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
    : '-';
}

function formatDateTime(value) {
  return value ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-';
}

function formatFileSize(bytes) {
  const size = Number(bytes);

  if (!Number.isFinite(size) || size <= 0) {
    return '-';
  }

  const units = ['B', 'KB', 'MB', 'GB'];
  const index = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1);

  return `${(size / 1024 ** index).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
}
</script>