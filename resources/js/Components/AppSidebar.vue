<template>
  <div class="sidebar-shell">
    <div class="sidebar-profile">
      <span class="sidebar-avatar h-10 w-10 text-sm" aria-hidden="true">{{ initials }}</span>

      <div class="min-w-0">
        <p class="truncate text-sm font-bold text-krem-100">{{ user?.name || 'Pengguna' }}</p>
        <p class="truncate text-[11px] font-medium text-lumut-400">{{ user?.role || '-' }}</p>
        <p v-if="user?.nta" class="truncate text-[10px] font-medium text-daun-500">NTA {{ user.nta }}</p>
      </div>
    </div>

    <SidebarNav
        v-model:query="query"
        :sections="sections"
        :collapsed="collapsed"
        :no-results="noResults"
        :result-count="resultCount"
        @navigate="emit('navigate', $event)"
    />

    <div class="mt-auto space-y-2 pt-1">
      <Link
          v-if="footer?.href && collapsed"
          :href="footer.href"
          class="sidebar-footer-cta sidebar-footer-cta--round"
          :title="footer.title"
          :aria-label="footer.cta"
          @click="emit('navigate')"
      >
        <NavIcon :name="footer.icon" class="h-4 w-4" />
      </Link>

      <div v-else-if="footer" class="sidebar-footer-card" :class="{ 'sidebar-footer-card--warning': footer.tone === 'warning' }">
        <p class="font-bold text-emas-400">{{ footer.title }}</p>
        <p class="mt-1 text-lumut-400">{{ footer.description }}</p>
        <Link v-if="footer.href" :href="footer.href" class="sidebar-footer-cta" @click="emit('navigate')">
          {{ footer.cta }}
        </Link>
      </div>

      <button
          v-if="showToggle"
          type="button"
          class="sidebar-toggle"
          :aria-label="collapsed ? 'Perlebar sidebar' : 'Ciutkan sidebar'"
          :aria-expanded="!collapsed"
          @click="emit('toggle-collapse')"
      >
        <NavIcon :name="collapsed ? 'expand' : 'collapse'" class="h-4 w-4" />
        <span v-if="!collapsed">Ciutkan</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import NavIcon from '@/Components/NavIcon.vue';
import SidebarNav from '@/Components/SidebarNav.vue';

/**
 * Cangkang sidebar yang dipakai bersama oleh sidebar desktop dan drawer mobile.
 * Keduanya hanya berbeda pada pembungkus luar, sehingga kartu profil, pencarian,
 * daftar menu, dan kartu ajakan tidak lagi diduplikasi dua kali.
 */
const props = defineProps({
    user: { type: Object, default: null },
    sections: { type: Array, required: true },
    footer: { type: Object, default: null },
    collapsed: { type: Boolean, default: false },
    showToggle: { type: Boolean, default: true },
    noResults: { type: Boolean, default: false },
    resultCount: { type: Number, default: 0 },
});

const query = defineModel('query', { type: String, default: '' });
const emit = defineEmits(['navigate', 'toggle-collapse']);

const initials = computed(() => {
    const parts = String(props.user?.name || '')
        .split(/\s+/)
        .filter(Boolean);

    if (parts.length === 0) {
        return 'U';
    }

    return parts
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
});
</script>