<template>
  <div
    v-if="show"
    :class="[
      'mb-3 flex items-center gap-3 rounded-lg border-2 px-4 py-2.5 text-sm font-semibold',
      isOnline ? 'border-[#A7B92A]/40 bg-[#A7B92A]/10 text-[#d4dc9a]' : 'border-[#EDD330]/50 bg-[#EDD330]/10 text-[#EDD330]',
    ]"
    role="status"
    aria-live="polite"
  >
    <span
      :class="[
        'flex h-7 w-7 shrink-0 items-center justify-center rounded-full',
        isOnline ? 'bg-[#A7B92A]/20 text-[#A7B92A]' : 'bg-[#EDD330]/20 text-[#EDD330]',
      ]"
    >
      <template v-if="flushing">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-4 w-4 animate-spin">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity="0.25" />
          <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </template>
      <template v-else-if="isOnline"><AppIcon name="check" :stroke="2.4" class="h-4 w-4" /></template>
      <template v-else><AppIcon name="warning" :stroke="2.2" class="h-4 w-4" /></template>
    </span>

    <span class="min-w-0 flex-1">
      <template v-if="!isOnline">
        Koneksi terputus. Anda tetap dapat-absen, data dikirim otomatis setelah sinyal kembali.
      </template>
      <template v-else-if="queued > 0">
        Mengirim {{ queued }} presensi yang tertunda&hellip;
      </template>
    </span>

    <span
      v-if="queued > 0"
      class="shrink-0 rounded-full bg-[#EDD330] px-2.5 py-0.5 text-xs font-black text-[#263D26]"
    >
      {{ queued }}
    </span>

    <button
      v-if="isOnline && queued > 0"
      type="button"
      :disabled="flushing"
      class="shrink-0 rounded-md border-2 border-[#6F9435] px-3 py-1 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/20 hover:text-[#EDD330] disabled:opacity-50"
      @click="flushQueue"
    >
      Kirim sekarang
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useNetworkStatus } from '@/Composables/useNetworkStatus.js';
import AppIcon from '@/Components/AppIcon.vue';

const { isOnline, queued, flushing, flushQueue } = useNetworkStatus();

// Sembunyikan banner saat online dan tidak ada antrean tertunda.
const show = computed(() => !isOnline.value || queued.value > 0);
</script>
