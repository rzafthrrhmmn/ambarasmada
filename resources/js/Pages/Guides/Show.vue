<template>
  <AppLayout>
    <div class="mx-auto max-w-3xl">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-[#EDD330]">Panduan Kegiatan</p>
          <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ guide.judul }}</h1>
          <p class="mt-1 text-xs text-[#8fa06a]">Kegiatan: {{ guide.kecamatan }}</p>
        </div>
        <button @click="router.back()" class="rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-semibold text-[#d4dc9a]">Kembali</button>
      </div>

      <div class="rounded-2xl border border-[#6F9435] bg-[#335233] p-6 shadow-sm border-[#6F9435]">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-[#8fa06a]">Susunan Upacara</h2>
        <div class="mt-3 whitespace-pre-line text-sm leading-7 text-[#d4dc9a]">{{ guide.teks_susunan_upacara || 'Belum ada teks susunan upacara.' }}</div>
      </div>

      <div v-if="guide.checklist_perlengkapan && guide.checklist_perlengkapan.length" class="mt-6 rounded-2xl border border-[#6F9435] bg-[#335233] p-6 shadow-sm border-[#6F9435]">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-[#8fa06a]">Checklist Perlengkapan</h2>
        <ul class="mt-3 space-y-2">
          <li v-for="(item, idx) in guide.checklist_perlengkapan" :key="idx" class="flex items-start gap-2 text-sm text-[#d4dc9a]">
            <input type="checkbox" class="mt-1 h-4 w-4 rounded border-[#6F9435]" />
            <span>{{ item }}</span>
          </li>
        </ul>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

defineProps({ guide: Object });
</script>