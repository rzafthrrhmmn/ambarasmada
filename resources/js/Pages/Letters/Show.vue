<template>
  <AppLayout>
    <div class="rounded-2xl border border-[#6F9435] bg-[#335233] p-6 shadow-sm">
      <div class="mb-4 flex flex-wrap items-start justify-between gap-3 border-b border-[#6F9435]/40 pb-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-[#EDD330]">Persuratan</p>
          <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ letter.perihal }}</h1>
          <p class="mt-1 text-xs text-[#8fa06a]">
            {{ letter.jenis_surat }} &bull; dibuat {{ formatDate(letter.created_at) }}
            <span v-if="letter.createdBy">&bull; {{ letter.createdBy.name }}</span>
          </p>
        </div>
        <Link href="/letters" class="rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-semibold text-[#d4dc9a]">
          Kembali
        </Link>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <p class="text-xs text-[#8fa06a]">Nomor surat</p>
          <p class="mt-1 text-sm font-medium text-[#f0ead8]">{{ letter.nomor_surat || '-' }}</p>
        </div>
        <div>
          <p class="text-xs text-[#8fa06a]">Tanggal surat</p>
          <p class="mt-1 text-sm font-medium text-[#f0ead8]">{{ formatDate(letter.tgl_surat) }}</p>
        </div>
        <div>
          <p class="text-xs text-[#8fa06a]">Tujuan / Pengirim</p>
          <p class="mt-1 text-sm font-medium text-[#f0ead8]">{{ letter.tujuan_pengirim || '-' }}</p>
        </div>
        <div>
          <p class="text-xs text-[#8fa06a]">Ambalan</p>
          <p class="mt-1 text-sm font-medium text-[#f0ead8]">{{ letter.ambalan?.nama || '-' }}</p>
        </div>
      </div>

      <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
          <p class="text-xs text-[#8fa06a]">Waktu kegiatan</p>
          <p class="mt-1 text-sm font-medium text-[#f0ead8]">{{ letter.waktu_kegiatan || '-' }}</p>
        </div>
        <div>
          <p class="text-xs text-[#8fa06a]">Lokasi kegiatan</p>
          <p class="mt-1 text-sm font-medium text-[#f0ead8]">{{ letter.lokasi_kegiatan || '-' }}</p>
        </div>
      </div>

      <div class="mt-4">
        <p class="text-xs text-[#8fa06a]">Isi surat</p>
        <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-[#d4dc9a]">{{ letter.isi_surat || '-' }}</p>
      </div>

      <div v-if="letter.template" class="mt-5 rounded-xl border border-[#6F9435]/60 bg-[#263D26] p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-[#EDD330]">Template: {{ letter.template.name }}</p>
        <p v-if="letter.template.description" class="mt-1 text-xs text-[#8fa06a]">{{ letter.template.description }}</p>

        <div v-if="placeholders.length" class="mt-3">
          <p class="text-xs font-semibold text-[#d4dc9a]">Nilai penanda yang akan ditulis ke dokumen</p>
          <div class="mt-2 grid gap-1 sm:grid-cols-2">
            <div
              v-for="name in placeholders"
              :key="name"
              class="flex items-baseline gap-2 text-xs"
            >
              <code class="shrink-0 text-[10px] text-[#8fa06a]">${name}</code>
              <span :class="unfilled.includes(name) ? 'text-[#ef4419]' : 'text-[#f0ead8]'">
                {{ resolved[name] ?? '-' }}
                <span v-if="unfilled.includes(name)" class="text-[10px]">(belum diisi)</span>
              </span>
            </div>
          </div>
        </div>
        <p v-else class="mt-2 text-xs text-[#8fa06a]">
          Template ini tidak memiliki penanda, sehingga surat memakai tata letak bawaan.
        </p>
      </div>

      <div class="mt-6 flex flex-wrap gap-2 border-t border-[#6F9435]/40 pt-4">
        <a
          :href="`/letters/${letter.id}/generate?format=docx`"
          data-test="btn-docx"
          :disabled="!canGenerate"
          class="inline-flex items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
        >
          Ekspor DOCX
        </a>
        <a
          :href="`/letters/${letter.id}/generate?format=pdf`"
          data-test="btn-pdf"
          :disabled="!canGenerate"
          class="inline-flex items-center rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#A7B92A] disabled:opacity-50"
        >
          Ekspor PDF
        </a>
        <Link
          :href="`/letters/${letter.id}/print`"
          class="inline-flex items-center rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]"
        >
          Lihat / cetak
        </Link>
        <a
          v-if="letter.file_path"
          :href="`/letters/${letter.id}/download`"
          class="inline-flex items-center rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]"
        >
          Unduh lampiran
        </a>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

defineProps({
  letter: { type: Object, required: true },
  placeholders: { type: Array, default: () => [] },
  resolved: { type: Object, default: () => ({}) },
  unfilled: { type: Array, default: () => [] },
  canGenerate: { type: Boolean, default: false },
});

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}
</script>
