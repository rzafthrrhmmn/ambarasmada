<template>
  <AppLayout>
    <header class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Kehadiran Saya</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">
        <template v-if="linked">Presensi {{ member.nama_lengkap }}</template>
        <template v-else>Presensi</template>
      </h1>
      <p class="mt-1 text-sm text-[#8fa06a]">
        <template v-if="linked">
          Catat kehadiran dengan memindai QR Code yang ditampilkan pemandu sesi, lalu lihat rekap kehadiranmu di sini.
        </template>
        <template v-else>
          Akun ini belum terhubung dengan data anggota, jadi riwayat kehadiran pribadi belum dapat ditampilkan.
        </template>
      </p>
    </header>

    <div v-if="!linked" class="empty-state">
      <AppIcon name="attendance" class="empty-state-icon h-6 w-6" />
      <p class="text-sm font-semibold text-[#f0ead8]">Belum ada data kehadiran untuk akun ini</p>
      <p class="mx-auto mt-1 max-w-md text-xs text-[#8fa06a]">
        Hubungi pembina untuk menautkan akunmu dengan data anggota. Setelah terhubung, riwayat presensi dan
        pemindaian QR Code akan muncul di halaman ini.
      </p>
    </div>

    <template v-else>
    <section class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5" aria-label="Rekap kehadiran saya">
      <div
        v-for="card in summaryCards"
        :key="card.label"
        class="rounded-xl border border-[#6F9435] bg-[#335233] px-4 py-3 shadow-sm"
        :class="card.emphasis ? 'border-[#EDD330]/60' : ''"
      >
        <p class="text-xs font-medium text-[#8fa06a]">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-bold" :class="card.textClass">{{ card.value }}</p>
      </div>
    </section>

    <div class="grid gap-5 lg:grid-cols-3">
      <section class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm lg:col-span-2">
        <div class="mb-4">
          <h2 class="font-semibold text-[#f0ead8]">Sesi latihan</h2>
          <p class="mt-0.5 text-xs text-[#8fa06a]">Status yang tampil adalah catatan kehadiran milikmu sendiri.</p>
        </div>

        <div class="space-y-3">
          <SkeletonLoader v-if="!sessions || !sessions.data" variant="list" :lines="5" />

<div
            v-if="!sessions.data.length"
            class="rounded-xl border border-dashed border-[#6F9435]/50 bg-[#263D26]/60 p-6 text-center"
          >
            <p class="text-sm text-[#8fa06a]">Belum ada sesi latihan yang tercatat.</p>
          </div>

          <div
            v-for="session in sessions.data"
            :key="session.id"
            class="rounded-xl border border-[#6F9435] bg-[#335233] p-4"
            :class="statusBorderClass(session)"
          >
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
              <div class="min-w-0">
                <Link :href="`/attendance/${session.id}`" class="font-semibold text-[#EDD330] hover:underline">
                  {{ session.nama }}
                </Link>
                <p class="mt-1 text-xs text-[#8fa06a]">
                  {{ formatDate(session.tanggal) }} <span aria-hidden="true">•</span> {{ session.lokasi || 'Lokasi belum diisi' }}
                </p>
                <p v-if="session.materi_nama" class="mt-1 text-xs text-[#8fa06a]">Materi: {{ session.materi_nama }}</p>
                <p v-if="hasGeofence(session)" class="mt-1 text-xs text-[#8fa06a]">
                  Geofence aktif <span aria-hidden="true">•</span> radius {{ session.radius }} m
                </p>
              </div>

              <span
                class="shrink-0 self-start rounded-full px-2.5 py-1 text-xs font-semibold"
                :class="statusChipClass(session)"
              >
                {{ statusLabel(session) }}
              </span>
            </div>

            <p v-if="session.my_attendance?.catatan" class="mt-2 text-xs text-[#8fa06a]">
              Catatan: {{ session.my_attendance.catatan }}
            </p>
            <p v-if="session.my_attendance?.checked_at" class="mt-1 text-[10px] text-[#6F9435]">
              Dicatat {{ formatDateTime(session.my_attendance.checked_at) }}
            </p>

            <div class="mt-3 flex flex-wrap gap-2">
              <Link
                :href="`/attendance/${session.id}`"
                class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]"
              >
                Detail sesi
              </Link>

              <Link
                v-if="session.can_scan"
                :href="scanUrl(session)"
                class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]"
              >
                Scan QR Code
              </Link>

              <span
                v-else-if="session.my_attendance?.keterangan"
                class="rounded-lg border border-[#6F9435]/30 px-3 py-1.5 text-xs font-semibold text-[#8fa06a]"
              >
                Kehadiran sudah tercatat
              </span>
            </div>
          </div>
        </div>

        <Pagination :links="sessions.links" class="mt-4 border-t border-[#6F9435] p-3" />
      </section>

      <aside class="space-y-4">
        <div class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
          <h2 class="font-semibold text-[#f0ead8]">Cara mencatat kehadiran</h2>
          <ol class="mt-3 space-y-2 text-sm leading-6 text-[#8fa06a]">
            <li>1. Minta QR Code sesi kepada pemandu latihan.</li>
            <li>2. Tekan <strong class="text-[#d4dc9a]">Scan QR Code</strong> pada sesi yang sedang berlangsung.</li>
            <li>3. Izinkan kamera dan lokasi bila sesi memakai geofence.</li>
          </ol>
        </div>

        <div class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
          <h2 class="font-semibold text-[#f0ead8]">Keterangan yang dipakai</h2>
          <ul class="mt-3 space-y-2 text-sm text-[#8fa06a]">
            <li v-for="item in keteranganLegend" :key="item.label" class="flex items-center gap-2">
              <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="item.dot" aria-hidden="true"></span>
              <span><strong class="text-[#d4dc9a]">{{ item.label }}</strong> - {{ item.hint }}</span>
            </li>
          </ul>
          <p class="mt-3 text-xs text-[#8fa06a]">
            Bila kamu berhalangan, minta izin kepada pemandu sesi agar keterangannya diubah dari Alpa menjadi Izin.
          </p>
        </div>
      </aside>
    </div>
    </template>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import Pagination from '@/Components/Pagination.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import { useAccess } from '@/Composables/useAccess.js';

/**
 * Tampilan anggota untuk modul kehadiran.
 *
 * Berbeda dari Attendance/Index.vue: tidak ada filter pengelola, tidak ada
 * checkbox pilihan massal, tidak ada QR Code sesi milik orang lain, dan tidak
 * ada tombol ubah/hapus. Yang tampil hanya sesi latihan, status kehadiran
 * milik sendiri, dan rekap pribadi.
 *
 * Data yang sampai ke halaman ini sudah dibatasi di AttendanceController:
 * tanpa direktori anggota, tanpa presensi anggota lain, dan tanpa koordinat
 * persis titik geofence.
 */
const props = defineProps({
  sessions: Object,
  member: { type: Object, default: null },
  /**
   * False untuk akun yang tidak punya baris di tabel members. Dipakai supaya
   * halaman bisa menjelaskan keadaannya, bukan menampilkan rekap nol seolah
   * itu data yang sebenarnya.
   */
  linked: { type: Boolean, default: true },
  summary: {
    type: Object,
    default: () => ({ total: 0, hadir: 0, izin: 0, sakit: 0, alpa: 0, percentage: 0 }),
  },
});

const { can } = useAccess();
const canScan = computed(() => can('attendance.self.scan'));

/**
 * Sesi hanya menyediakan tautan pemindai bila server mengirim qr_token-nya.
 * `session.can_scan` sudah memperhitungkan tanggal sesi, QR dinamis, dan
 * apakah kehadiran sudah tercatat, sehingga halaman ini tidak perlu menebak.
 */
function isScannable(session) {
  return canScan.value && session.can_scan === true;
}

const summaryCards = computed(() => [
  { label: 'Total tercatat', value: props.summary.total, textClass: 'text-[#f0ead8]' },
  { label: 'Hadir', value: props.summary.hadir, textClass: 'text-[#d4f0a0]', emphasis: true },
  { label: 'Izin', value: props.summary.izin, textClass: 'text-[#EDD330]' },
  { label: 'Sakit', value: props.summary.sakit, textClass: 'text-[#f0c987]' },
  { label: 'Alpa', value: props.summary.alpa, textClass: 'text-[#ef4419]' },
]);

const keteranganLegend = [
  { label: 'Hadir', hint: 'tercatat hadir pada sesi', dot: 'bg-[#A7B92B]' },
  { label: 'Izin', hint: 'tidak hadir dengan izin', dot: 'bg-[#EDD330]' },
  { label: 'Sakit', hint: 'tidak hadir karena sakit', dot: 'bg-[#f0c987]' },
  { label: 'Alpa', hint: 'tidak hadir tanpa keterangan', dot: 'bg-[#ef4419]' },
];

function hasGeofence(session) {
  return session.radius !== null && session.radius !== undefined;
}

function statusLabel(session) {
  return session.my_attendance?.keterangan || 'Belum tercatat';
}

function statusChipClass(session) {
  switch (session.my_attendance?.keterangan) {
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
}

function statusBorderClass(session) {
  return session.my_attendance?.keterangan === 'Hadir' ? 'border-[#A7B92B]/60 bg-[#2d4a2d]' : '';
}

function scanUrl(session) {
  if (!session.qr_token) {
    return null;
  }

  return route('attendance.scan', session.qr_token);
}

function formatDate(value) {
  return value
    ? new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
    : '-';
}

function formatDateTime(value) {
  return value ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-';
}
</script>