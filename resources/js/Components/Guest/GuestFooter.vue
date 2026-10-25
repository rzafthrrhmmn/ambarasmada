<template>
  <footer class="guest-section border-t border-daun-500/20 pt-12 pb-8">
    <div class="flex flex-col items-center justify-center gap-6">
      <div class="flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl overflow-hidden border-2 border-daun-500/40 bg-hutan-800 text-emas-400">
          <img :src="logoUrl" alt="Logo Ambalan" class="h-8 w-8 object-contain" width="64" height="64" loading="eager" @error="onLogoError" />
        </div>
        <span class="text-base font-bold text-krem-300">{{ ambalanNama }}</span>
      </div>

      <nav class="flex flex-wrap items-center justify-center gap-6 text-xs font-medium text-lumut-400">
          <a href="/privacy" class="transition hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400 rounded">Kebijakan Privasi</a>
        <span class="h-3 w-px bg-daun-500/30" aria-hidden="true"></span>
        <a :href="mailtoLink" class="transition hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400 rounded">Hubungi Admin</a>
      </nav>

      <p class="text-xs font-medium text-lumut-400">
        &copy; {{ currentYear }} {{ ambalanNama }} &middot; Dibangun dengan kebanggaan.
      </p>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  ambalanNama: { type: String, default: 'Ambalan UPT SMAN 2 Maros' },
  logoUrl: { type: String, default: '/images/Logo_Ambalan.png' },
});

const emit = defineEmits(['logo-error']);

const currentYear = computed(() => new Date().getFullYear());
const mailtoLink = computed(() => 'mailto:admin@sman2maros.sch.id');

function onLogoError(event) {
  event.target.onerror = null;
  event.target.src = '/images/Logo_Ambalan.png';
  emit('logo-error');
}
</script>

<style scoped>
.guest-focus-ring {
  outline: none;
}
</style>
