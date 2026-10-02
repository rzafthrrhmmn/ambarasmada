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
      <a v-if="canExportFinance" href="/reports/finance/pdf" class="group rounded-2xl border-2 border-[#A7B92A]/40 bg-[#335233] p-5 transition hover:border-[#A7B92B]">
        <p class="text-2xl">📊</p>
        <h3 class="mt-2 text-sm font-bold text-[#f0ead8]">Laporan Keuangan</h3>
        <p class="mt-1 text-[10px] text-[#8fa06a]">PDF - Laporan kas dan tabungan</p>
        <p class="mt-2 text-[10px] font-bold text-[#A7B92B] group-hover:underline">Download PDF →</p>
      </a>
      <a v-if="canExportMembers" href="/reports/members/csv" class="group rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5 transition hover:border-[#A7B92B]">
        <p class="text-2xl">👥</p>
        <h3 class="mt-2 text-sm font-bold text-[#f0ead8]">Data Anggota</h3>
        <p class="mt-1 text-[10px] text-[#8fa06a]">CSV - Daftar seluruh anggota</p>
        <p class="mt-2 text-[10px] font-bold text-[#A7B92B] group-hover:underline">Download CSV →</p>
      </a>
      <a v-if="canExportAttendance" href="/reports/attendance/pdf" class="group rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5 transition hover:border-[#A7B92B]">
        <p class="text-2xl">📋</p>
        <h3 class="mt-2 text-sm font-bold text-[#f0ead8]">Rekap Kehadiran</h3>
        <p class="mt-1 text-[10px] text-[#8fa06a]">PDF - Rekap presensi latihan</p>
        <p class="mt-2 text-[10px] font-bold text-[#A7B92B] group-hover:underline">Download PDF →</p>
      </a>
      <!-- Rekap SKU lebih sempit dari kartu lain: server hanya mengizinkan
           Admin dan Pembina, jadi Pengurus yang boleh membuka halaman ini
           tetap tidak melihat tautan ini. -->
      <a v-if="canExportSku" href="/reports/sku/pdf" class="group rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5 transition hover:border-[#A7B92B]">
        <p class="text-2xl">⭐</p>
        <h3 class="mt-2 text-sm font-bold text-[#f0ead8]">Rekap SKU</h3>
        <p class="mt-1 text-[10px] text-[#8fa06a]">PDF - Poin SKU seluruh anggota</p>
        <p class="mt-2 text-[10px] font-bold text-[#A7B92B] group-hover:underline">Download PDF →</p>
      </a>
    </div>
    </template>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Components/AppLayout.vue';
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
</script>
