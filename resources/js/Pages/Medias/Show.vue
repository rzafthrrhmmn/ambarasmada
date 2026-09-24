<template>
  <AppLayout>
    <div class="mx-auto max-w-3xl">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-[#EDD330]">Media Ambalan</p>
          <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ media.judul }}</h1>
        </div>
        <button @click="router.back()" class="rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-semibold text-[#d4dc9a]">Kembali</button>
      </div>

      <div class="rounded-2xl border border-[#6F9435] bg-[#335233] p-6 shadow-sm border-[#6F9435]">
        <div v-if="media.file_path" class="rounded-xl bg-[#263D26] p-4">
          <audio controls class="w-full">
            <source :src="`/medias/${media.id}/download`" type="audio/mpeg" />
            Browser tidak mendukung elemen audio.
          </audio>
        </div>

        <div v-if="media.lirik" class="mt-6">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-[#8fa06a]">Lirik</h2>
          <div class="mt-3 whitespace-pre-line text-sm leading-7 text-[#d4dc9a]">{{ media.lirik }}</div>
        </div>

        <div v-if="!media.lirik && !media.file_path" class="mt-6 text-center text-sm text-[#8fa06a]">
          Belum ada lirik atau file audio.
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

defineProps({ media: Object });
</script>