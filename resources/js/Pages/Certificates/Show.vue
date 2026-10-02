<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Sertifikat</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ certificate.judul }}</h1>
      <p class="mt-1 text-sm text-[#8fa06a]">Detail sertifikat</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
      <div class="lg:col-span-2 space-y-4">
        <div class="rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
          <div class="mb-4 flex items-center justify-between">
            <span class="rounded-full px-3 py-1 text-xs font-bold" :class="{
              'bg-[#A7B92B]/20 text-[#A7B92B]': certificate.status === 'Diterbitkan',
              'bg-[#EDD330]/20 text-[#EDD330]': certificate.status === 'Draft',
              'bg-[#ef4419]/20 text-[#ef4419]': certificate.status === 'Dibatalkan',
            }">{{ certificate.status }}</span>
            <p class="text-xs text-[#8fa06a]">{{ certificate.nomor_sertifikat }}</p>
          </div>
          <dl class="grid gap-2 sm:grid-cols-2 text-sm">
            <div>
              <dt class="text-[#8fa06a]">Anggota</dt>
              <dd class="font-medium text-[#f0ead8]">{{ certificate.member?.nama_lengkap }}</dd>
            </div>
            <div>
              <dt class="text-[#8fa06a]">Jenis</dt>
              <dd class="font-medium text-[#f0ead8]">{{ certificate.jenis }}</dd>
            </div>
            <div>
              <dt class="text-[#8fa06a]">Tanggal Diterbitkan</dt>
              <dd class="font-medium text-[#f0ead8]">{{ formatDate(certificate.tanggal_diterbitkan) }}</dd>
            </div>
            <div>
              <dt class="text-[#8fa06a]">Diterbitkan Oleh</dt>
              <dd class="font-medium text-[#f0ead8]">{{ certificate.issuedBy?.name ?? '-' }}</dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="text-[#8fa06a]">Deskripsi</dt>
              <dd class="font-medium text-[#f0ead8] whitespace-pre-wrap">{{ certificate.deskripsi ?? '-' }}</dd>
            </div>
          </dl>
        </div>

        <div v-if="certificate.file_path" class="rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
          <h3 class="mb-3 text-sm font-bold text-[#f0ead8]">File Lampiran</h3>
          <a :href="`/certificates/${certificate.id}/download`" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a] hover:bg-[#6F9435]/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            Download File Lampiran
          </a>
        </div>
      </div>

      <div class="space-y-4">
        <div class="rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
          <h3 class="mb-3 text-sm font-bold text-[#f0ead8]">Aksi</h3>
          <div class="flex flex-col gap-2">
            <button @click="generatePdf" :disabled="generatingPdf" class="w-full rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
              {{ generatingPdf ? 'Menghasilkan PDF...' : 'Generate & Download PDF' }}
            </button>
            <button @click="previewPdf" :disabled="generatingPdf" class="w-full rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a] hover:bg-[#6F9435]/20 disabled:opacity-50">
              Preview PDF
            </button>
            <a v-if="canManage" href="/certificates" class="w-full text-center rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a] hover:bg-[#6F9435]/20">Kembali ke Daftar</a>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Components/AppLayout.vue';
import { useAccess } from '@/Composables/useAccess.js';

defineProps({ certificate: Object });
const { can } = useAccess();
// Dulu selalu true sehingga tombol terbit muncul untuk semua orang, lalu
// ditolak 403 saat dikirim.
const canManage = computed(() => can('certificates.manage'));
const generatingPdf = ref(false);

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}

function generatePdf() {
  generatingPdf.value = true;
  window.open(`/certificates/${certificate.id}/pdf`, '_blank');
  generatingPdf.value = false;
}

function previewPdf() {
  window.open(`/certificates/${certificate.id}/preview-pdf`, '_blank');
}
</script>