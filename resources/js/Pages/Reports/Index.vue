<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Laporan</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Laporan Ambalan</h1>
      <p class="mt-1 text-sm text-[#8fa06a]">Unduh laporan keuangan, kehadiran, dan data anggota.</p>
    </div>

    <!-- Isi halaman mengikuti reports.view. Tautan unduh tidak memakai
         capability yang sama karena ReportController menjaganya dengan daftar
         peran yang berbeda-beda, sehingga tiap kartu memakai kapabilitas
         export yang paling sempit sesuai penjaga server. -->
    <template v-if="canViewReports">
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <a
          v-for="card in visibleCards"
          :key="card.href"
          :href="card.href"
          class="group relative flex flex-col overflow-hidden rounded-2xl border-2 border-[#6F9435]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-lg transition duration-200 hover:-translate-y-1 hover:border-[#EDD330]/70 hover:shadow-xl hover:shadow-[#EDD330]/15"
        >
          <span
            aria-hidden="true"
            class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-[#EDD330]/10 blur-2xl transition duration-300 group-hover:scale-125"
          />

          <span
            class="relative flex h-12 w-12 items-center justify-center rounded-xl border-2 border-[#6F9435]/40 bg-[#263D26] text-[#A7B92B] transition duration-200 group-hover:border-[#EDD330]/60 group-hover:text-[#EDD330]"
          >
            <AppIcon :name="card.icon" class="h-6 w-6" />
          </span>

          <h3 class="relative mt-4 text-sm font-bold text-[#f0ead8]">{{ card.title }}</h3>
          <p class="relative mt-1 text-[10px] leading-4 text-[#8fa06a]">{{ card.description }}</p>

          <span
            class="relative mt-4 inline-flex items-center gap-1.5 text-[10px] font-bold text-[#A7B92B] transition group-hover:text-[#EDD330]"
          >
            <AppIcon name="download" class="h-3.5 w-3.5" />
            {{ card.action }}
            <AppIcon
              name="arrowRight"
              class="h-3.5 w-3.5 -translate-x-1 opacity-0 transition duration-200 group-hover:translate-x-0 group-hover:opacity-100"
            />
          </span>
        </a>
      </div>

      <p v-if="!visibleCards.length" class="mt-4 empty-state">
        Tidak ada laporan yang bisa diunduh untuk peran Anda saat ini.
      </p>
    </template>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useAccess } from '@/Composables/useAccess.js';

/**
 * Halaman laporan hanya berisi tautan unduh, jadi satu-satunya tempat
 * kapabilitas bisa berlaku adalah isi halaman itu sendiri. Karena tidak ada
 * data sensitif yang perlu dipisah di server, capability dipakai sebagai
 * pagar tampilan, bukan penyaring data.
 */
const { can } = useAccess();

const canViewReports = computed(() => can('reports.view'));
const canExportFinance = computed(() => can('reports.export_finance'));
const canExportMembers = computed(() => can('reports.export_members'));
const canExportAttendance = computed(() => can('reports.export_attendance'));
const canExportSku = computed(() => can('reports.export_sku'));

/*
 * Kartu disusun sebagai data supaya daftar cepat, nama kapabilitas, dan ikon
 * tidak lagi ditulis ulang di empat blok template yang saling mirip.
 */
const cards = [
    {
        key: 'finance',
        href: '/reports/finance/pdf',
        icon: 'reports',
        title: 'Laporan Keuangan',
        description: 'PDF - Laporan kas dan tabungan',
        action: 'Download PDF',
        allowed: canExportFinance,
    },
    {
        key: 'members',
        href: '/reports/members/csv',
        icon: 'teams',
        title: 'Data Anggota',
        description: 'CSV - Daftar seluruh anggota',
        action: 'Download CSV',
        allowed: canExportMembers,
    },
    {
        key: 'attendance',
        href: '/reports/attendance/pdf',
        icon: 'clipboard',
        title: 'Rekap Kehadiran',
        description: 'PDF - Rekap presensi latihan',
        action: 'Download PDF',
        allowed: canExportAttendance,
    },
    // Rekap SKU lebih sempit dari kartu lain: server hanya mengizinkan Admin
    // dan Pembina, jadi Pengurus yang boleh membuka halaman ini tetap tidak
    // melihat tautan ini.
    {
        key: 'sku',
        href: '/reports/sku/pdf',
        icon: 'star',
        title: 'Rekap SKU',
        description: 'PDF - Poin SKU seluruh anggota',
        action: 'Download PDF',
        allowed: canExportSku,
    },
];

const visibleCards = computed(() => cards.filter((card) => card.allowed.value));
</script>