<template>
  <button
    @click="toggleTheme"
    class="rounded-lg border border-[#6F9435]/40 bg-hutan-800/60 px-2.5 py-1.5 text-xs font-semibold text-krem-300 transition hover:bg-daun-500/30 hover:text-emas-400"
    :aria-label="isDark ? 'Aktifkan tema terang' : 'Aktifkan tema gelap'"
  >
    <span v-if="isDark">🌙</span>
    <span v-else>☀️</span>
    <span class="ml-1.5">{{ isDark ? 'Gelap' : 'Terang' }}</span>
  </button>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const isDark = ref(true);

function loadTheme() {
  const saved = localStorage.getItem('theme');
  const prefersDark = !window.matchMedia || !window.matchMedia('(prefers-color-scheme: light)').matches;
  isDark.value = saved ? saved === 'dark' : prefersDark;
  applyTheme();
}

function applyTheme() {
  if (isDark.value) {
    document.documentElement.classList.remove('light-theme');
    localStorage.setItem('theme', 'dark');
  } else {
    document.documentElement.classList.add('light-theme');
    localStorage.setItem('theme', 'light');
  }
}

function toggleTheme() {
  isDark.value = !isDark.value;
  applyTheme();
}

onMounted(() => {
  loadTheme();
});
</script>
