<template>
  <AppLayout>
    <div class="rounded-2xl border border-[#6F9435] bg-[#335233] p-6 shadow-sm border-[#6F9435]">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-[#EDD330]">Persuratan</p>
          <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ letter.perihal }}</h1>
        </div>
        <button @click="router.back()" class="rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-semibold text-[#d4dc9a]">Kembali</button>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div><p class="text-xs text-[#8fa06a]">Nomor Surat</p><p class="mt-1 text-sm font-medium">{{ letter.nomor_surat || '-' }}</p></div>
        <div><p class="text-xs text-[#8fa06a]">Jenis</p><p class="mt-1 text-sm font-medium">{{ letter.jenis_surat }}</p></div>
        <div><p class="text-xs text-[#8fa06a]">Tujuan / Pengirim</p><p class="mt-1 text-sm font-medium">{{ letter.tujuan_pengirim }}</p></div>
        <div><p class="text-xs text-[#8fa06a]">Tanggal</p><p class="mt-1 text-sm font-medium">{{ formatDate(letter.tgl_surat) }}</p></div>
      </div>

      <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div><p class="text-xs text-[#8fa06a]">Isi Surat</p><p class="mt-1 text-sm text-[#d4dc9a] whitespace-pre-line">{{ letter.isi_surat || '-' }}</p></div>
        <div><p class="text-xs text-[#8fa06a]">Waktu Kegiatan</p><p class="mt-1 text-sm font-medium">{{ letter.waktu_kegiatan || '-' }}</p></div>
        <div><p class="text-xs text-[#8fa06a]">Lokasi Kegiatan</p><p class="mt-1 text-sm font-medium">{{ letter.lokasi_kegiatan || '-' }}</p></div>
        <div v-if="letter.template" class="sm:col-span-3"><p class="text-xs text-[#8fa06a]">Template</p><p class="mt-1 text-sm font-medium">{{ letter.template.name }}</p></div>
      </div>

      <div class="mt-6 flex flex-wrap gap-3">
        <a v-if="letter.file_path" :href="`/letters/${letter.id}/download`" target="_blank" class="inline-flex items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Unduh Berkas</a>
        <button @click="router.visit(`/letters/${letter.id}/print`)" class="inline-flex items-center rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Cetak</button>
        <a :href="`/letters/${letter.id}/generate?format=pdf`" target="_blank" class="inline-flex items-center rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#A7B92A]">Generate PDF</a>
        <a :href="`/letters/${letter.id}/generate?format=docx`" target="_blank" class="inline-flex items-center rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#6F9435]">Generate DOCX</a>
      </div>
    </div>
  </AppLayout>
</template>
<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

defineProps({ letter: Object });

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}
</script>