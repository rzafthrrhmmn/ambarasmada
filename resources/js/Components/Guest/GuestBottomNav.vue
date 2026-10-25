<template>
  <nav
    v-if="isMobile"
    class="guest-bottom-nav fixed bottom-0 inset-x-0 z-50 bg-hutan-900/95 backdrop-blur-md border-t border-daun-500/20"
    aria-label="Navigasi bawah"
  >
    <a
      v-for="(item, index) in navItems"
      :key="item.id"
      :href="item.href"
      class="guest-bottom-nav-item flex flex-col items-center justify-center gap-0.5 px-3 py-2 text-xs font-medium transition-colors duration-200"
      :class="{
        'text-emas-400': activeSection === item.id,
        'text-lumut-400': activeSection !== item.id,
      }"
      @click.prevent="onNavClick(item.id, $event)"
    >
      <AppIcon :name="item.icon" class="h-5 w-5" :class="{ 'text-emas-400': activeSection === item.id, 'text-lumut-400': activeSection !== item.id }" />
      <span :class="{ 'font-black': activeSection === item.id, 'font-medium': activeSection !== item.id }">{{ item.label }}</span>
      <div class="guest-bottom-nav-indicator" :class="{ 'bg-emas-400': activeSection === item.id }" aria-hidden="true"></div>
    </a>
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
</style>
