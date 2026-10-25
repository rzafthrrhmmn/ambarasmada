<template>
  <div class="fixed inset-0 z-50 flex items-end justify-center bg-[#1f3320]/85 p-3 sm:items-center sm:p-5">
    <div class="w-full max-w-2xl rounded-2xl border-2 border-daun-400/50 bg-hutan-600 shadow-2xl shadow-[#1f3320]/70 sm:p-6">
      <div class="mb-4 flex items-start justify-between gap-4">
        <div>
          <p class="text-xs font-bold uppercase tracking-wide text-emas-400">Formulir</p>
          <h2 class="mt-1 text-xl font-extrabold text-krem-100">
            <slot name="title">{{ title }}</slot>
          </h2>
        </div>
        <button
          type="button"
          class="rounded-lg p-2 text-krem-300 transition hover:bg-daun-500/30 hover:text-emas-400"
          aria-label="Tutup"
          @click="$emit('close')"
        >
          <AppIcon name="close" :stroke="2" class="h-5 w-5" />
        </button>
      </div>
      <div class="max-h-[75vh] overflow-y-auto"><slot /></div>
    </div>
  </div>
</template>

<script setup>
import AppIcon from '@/Components/AppIcon.vue';

defineProps({ title: { type: String, default: 'Formulir' } });
defineEmits(['close']);
</script>