<template>
  <div
    v-if="isOnline === false"
    class="fixed top-0 inset-x-0 z-50 bg-emas-400 text-hutan-800 text-center text-xs font-bold py-1.5 px-4 shadow-lg transition-opacity duration-300"
    role="status"
    aria-label="Status koneksi jaringan"
  >
    <span class="flex items-center justify-center gap-1">
      <span
        class="w-1.5 h-1.5 rounded-full bg-hutan-800 animate-pulse"
        aria-hidden="true"
      ></span>
      Anda sedang offline — menampilkan data yang tersimpan di perangkat
    </span>
    <button
      v-if="canDismiss"
      @click="emit('dismiss')"
      class="ml-4 text-xs font-bold underline underline-offset-4 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-hutan-800"
      aria-label="Tutup peringatan offline"
    >
      Tutup
    </button>
  </div>
</template>

<script setup>
defineProps({
  isOnline: { type: Boolean, default: true },
  canDismiss: { type: Boolean, default: false },
});

const emit = defineEmits(['dismiss']);
</script>
