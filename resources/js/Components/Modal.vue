<template>
  <div class="fixed inset-0 z-50 flex items-end justify-center bg-[#1f3320]/85 p-3 sm:items-center sm:p-5">
    <div class="w-full max-w-2xl rounded-2xl border-2 border-[#A7B92A]/50 bg-[#335233] shadow-2xl shadow-[#1f3320]/70 sm:p-6"><div class="mb-4 flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wide text-[#EDD330]">Formulir</p><h2 class="mt-1 text-xl font-extrabold text-[#f0ead8]"><slot name="title">{{ title }}</slot></h2></div><button type="button" @click="$emit('close')" class="rounded-lg p-2 text-[#8fa06a] hover:bg-[#6F9435]/30 hover:text-[#EDD330]" aria-label="Tutup"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button></div><div class="max-h-[75vh] overflow-y-auto"><slot /></div></div>
  </div>
</template>
<script setup>
defineProps({ title: { type: String, default: 'Formulir' } });
defineEmits(['close']);
</script>