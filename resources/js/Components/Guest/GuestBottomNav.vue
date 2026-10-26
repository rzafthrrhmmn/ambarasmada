<template>
  <nav
    v-if="isMobile"
    class="guest-bottom-nav fixed bottom-0 left-0 right-0 z-50 bg-hutan-900/96 backdrop-blur-md border-t border-daun-500/30 shadow-2xl shadow-black/20"
    aria-label="Navigasi bawah"
  >
    <div class="mx-auto flex max-w-md items-stretch justify-between px-2 sm:px-4">
      <a
        v-for="(item, index) in navItems"
        :key="item.id"
        :href="item.href"
        class="guest-bottom-nav-item flex flex-col items-center justify-center gap-1 px-3 py-2.5 text-xs font-medium transition-all duration-200 relative"
        :class="{
          'text-emas-400': activeSection === item.id,
          'text-lumut-400': activeSection !== item.id,
        }"
        @click.prevent="onNavClick(item.id, $event)"
        :aria-current="activeSection === item.id ? 'page' : undefined"
      >
        <span class="bottom-nav-indicator absolute -top-1 left-1/2 -translate-x-1/2 w-2 h-2 rounded-full bg-emas-400 opacity-0 transition-opacity duration-200" :class="{ 'opacity-100': activeSection === item.id }" aria-hidden="true" />
        <AppIcon :name="item.icon" class="h-5 w-5 transition-transform duration-200" :class="{ 'scale-110': activeSection === item.id, 'text-emas-400': activeSection === item.id, 'text-lumut-400': activeSection !== item.id }" />
        <span class="leading-none" :class="{ 'font-black': activeSection === item.id, 'font-medium': activeSection !== item.id }">{{ item.label }}</span>
      </a>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  isMobile: { type: Boolean, default: false },
  activeSection: { type: String, default: '' },
});

const emit = defineEmits(['navigate']);

const navItems = computed(() => [
  { id: 'fitur', label: 'Fitur', icon: 'tools', href: '#fitur' },
  { id: 'dokumentasi', label: 'Galeri', icon: 'galleries', href: '#dokumentasi' },
  { id: 'testimoni', label: 'Testimoni', icon: 'star', href: '#testimoni' },
  { id: 'pengumatan', label: 'Pengumuman', icon: 'bell', href: '#pengumatan' },
  { id: 'masuk', label: 'Masuk', icon: 'login', href: '/login' },
]);

function onNavClick(sectionId, event) {
  if (sectionId === 'masuk') {
    window.location.href = '/login';
    return;
  }
  emit('navigate', sectionId);
  const el = document.getElementById(sectionId);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}
</script>

<style scoped>
.guest-focus-ring {
  outline: none;
}
.guest-focus-ring:focus-visible {
  outline: 2px solid var(--color-emas-400, #EDD330);
  outline-offset: 2px;
}
</style>
