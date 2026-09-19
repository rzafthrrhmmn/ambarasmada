<template>
  <AppLayout>
    <div class="mb-6 flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-[#EDD330]">Materi</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ material.nama }}</h1>
        <p v-if="material.deskripsi" class="mt-1 text-xs text-[#8fa06a]">Kategori: {{ material.deskripsi }}</p>
      </div>
      <button @click="router.back()" class="rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-semibold text-[#d4dc9a]">Kembali</button>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <div class="rounded-2xl border border-[#6F9435] bg-[#335233] p-6 shadow-sm border-[#6F9435]">
          <div class="prose prose-sm prose-invert max-w-none text-[#d4dc9a]" v-html="renderedContent"></div>
        </div>
      </div>
      <div class="space-y-4">
        <div v-if="material.file_path" class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435]">
          <p class="text-xs font-semibold uppercase tracking-wide text-[#8fa06a]">Berkas</p>
          <a :href="`/materials/${material.id}/download`" target="_blank" class="mt-2 inline-flex items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Unduh berkas</a>
        </div>
        <div class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435]">
          <p class="text-xs font-semibold uppercase tracking-wide text-[#8fa06a]">Info</p>
          <p class="mt-2 text-xs text-[#8fa06a]">Dibuat: {{ formatDate(material.created_at) }}</p>
          <p class="text-xs text-[#8fa06a]">Akses: {{ material.is_restricted ? 'Terbatas' : 'Publik' }}</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

const props = defineProps({ material: Object });

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
}

const renderedContent = computed(() => {
  return (props.material?.konten || '').replace(/\n/g, '<br>');
});
</script>