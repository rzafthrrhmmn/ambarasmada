<template>
  <AppLayout>
    <div class="mx-auto max-w-3xl rounded-2xl border border-[#6F9435] bg-[#335233] p-8 shadow-sm border-[#6F9435]">
      <div class="mb-6 text-center">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#EDD330]">Ambalan UPT SMAN 2 Maros</p>
        <h1 class="mt-2 text-2xl font-bold text-[#f0ead8]">{{ letter.perihal }}</h1>
      </div>

      <div class="grid gap-3 text-sm sm:grid-cols-2">
        <div><span class="text-[#8fa06a]">Nomor Surat:</span> <strong>{{ letter.nomor_surat || '-' }}</strong></div>
        <div><span class="text-[#8fa06a]">Jenis:</span> <strong>{{ letter.jenis_surat }}</strong></div>
        <div><span class="text-[#8fa06a]">Tujuan:</span> <strong>{{ letter.tujuan_pengirim }}</strong></div>
        <div><span class="text-[#8fa06a]">Tanggal:</span> <strong>{{ formatDate(letter.tgl_surat) }}</strong></div>
      </div>

      <div class="mt-8 border-t border-[#6F9435] pt-6 border-[#6F9435]">
        <p v-if="letter.file_path" class="text-sm text-[#d4dc9a]">Berkas surat tersedia. Silakan unduh untuk membaca isi lengkap.</p>
        <p v-else class="text-sm text-[#8fa06a]">Tidak ada berkas yang diunggah. Silakan tambahkan berkas PDF atau gambar scan surat.</p>
      </div>

      <div class="mt-8 flex justify-end gap-3">
        <a v-if="letter.file_path" :href="`/letters/${letter.id}/download`" target="_blank" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Unduh Berkas</a>
        <button @click="window.print()" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Cetak Halaman</button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Components/AppLayout.vue';

defineProps({ letter: Object });

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}
</script>