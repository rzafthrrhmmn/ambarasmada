<template>
  <div class="sidebar-shell min-h-0 flex-1 flex-col">
    <!-- Profile card -->
    <div class="sidebar-profile px-5 pb-4">
      <div class="flex items-center gap-3">
        <span class="sidebar-avatar h-10 w-10 rounded-xl border border-daun-400/30 bg-hutan-900 overflow-hidden flex items-center justify-center shadow-lg shadow-daun-500/10">
          <img :src="user?.photo_url || ambalan?.logo_url || '/images/Logo_Ambalan.png'" alt="Avatar" class="h-full w-full object-contain p-1" />
        </span>
        <div class="min-w-0">
          <p class="block truncate text-sm font-bold text-krem-100">{{ user?.name || 'Pengguna' }}</p>
          <p class="block truncate text-[10px] font-medium text-lumut-400 uppercase tracking-wider">{{ user?.role || '-' }}</p>
          <p v-if="user?.nta" class="block truncate text-[10px] font-medium text-daun-400">NTA {{ user.nta }}</p>
        </div>
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

    <div class="mt-auto space-y-2 pt-5 pb-3 px-3">
      <a
          v-if="footer?.href && collapsed"
          :href="footer.href"
          class="sidebar-footer-cta sidebar-footer-cta--round w-9 h-9 rounded-xl border border-daun-400/30 bg-hutan-700/60 flex items-center justify-center text-lumut-400 hover:bg-daun-500/20 hover:border-daun-400/50 hover:text-emas-400 transition-all duration-200"
          :title="footer.title"
          :aria-label="footer.cta"
          @click="emit('navigate')"
      >
        <NavIcon :name="footer.icon" class="h-5 w-5" />
      </a>

      <div v-else-if="footer" class="sidebar-footer-card rounded-2xl border border-daun-400/20 bg-hutan-700/40 p-3 backdrop-blur-sm shadow-inner">
        <p class="block text-xs font-bold text-emas-400 tracking-tight">{{ footer.title }}</p>
        <p class="block mt-1 text-[10px] text-lumut-400 leading-tight">{{ footer.description }}</p>
        <a v-if="footer.href" :href="footer.href" class="sidebar-footer-cta mt-2 block w-full rounded-xl border border-daun-400/30 bg-hutan-800 px-3 py-1.5 text-center text-xs font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400" @click="emit('navigate')">
          {{ footer.cta }}
        </a>
      </div>

      <button
          v-if="showToggle"
          type="button"
          class="w-full rounded-xl border border-daun-400/30 bg-hutan-700/60 px-3 py-2 text-left text-xs font-semibold text-krem-100 transition-all duration-200 hover:bg-daun-500/20 hover:border-daun-400/50 flex items-center justify-between"
          :aria-label="collapsed ? 'Perlebar sidebar' : 'Ciutkan sidebar'"
          :aria-expanded="!collapsed"
          @click="emit('toggle-collapse')"
      >
        <span class="flex items-center gap-2">
          <NavIcon :name="collapsed ? 'expand' : 'collapse'" class="h-4 w-4" />
          <span v-if="!collapsed" class="hidden sm:block">Ciutkan</span>
        </span>
        <NavIcon name="chevronRight" class="h-3.5 w-3.5 text-lumut-400 transition-transform duration-200" :class="{ 'rotate-90': collapsed }" />
      </button>

      <ThemeToggle class="mt-2" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import NavIcon from '@/Components/NavIcon.vue';
import SidebarNav from '@/Components/SidebarNav.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

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

const ambalan = computed(() => {
  // Try to get ambalan from a global or page prop if available
  return null;
});

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
