<template>
  <div class="flex min-h-0 flex-1 flex-col">
    <!-- Search -->
    <div v-if="!collapsed" class="sidebar-search px-4 pb-3">
      <div class="relative">
        <NavIcon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-krem-300/60" />

        <input
            ref="searchInput"
            v-model="query"
            type="search"
            class="sidebar-search-input pl-9 pr-3 py-1.5 rounded-xl border border-daun-400/30 bg-hutan-700/40 text-xs text-krem-100 placeholder:text-lumut-400/70 focus:border-daun-400/60 focus:ring-1 focus:ring-daun-500/40 focus:outline-none transition-all duration-200"
            placeholder="Cari menu..."
            aria-label="Cari menu di sidebar"
            autocomplete="off"
            spellcheck="false"
        />

        <button v-if="searching" type="button" class="absolute right-2 top-1/2 -translate-y-1/2" aria-label="Bersihkan pencarian" @click="clear">
          <NavIcon name="close" class="h-3.5 w-3.5 text-krem-300/60 hover:text-krem-100" />
        </button>
        <kbd v-else class="absolute right-2 top-1/2 -translate-y-1/2 nav-kbd text-[9px] font-bold text-lumut-400/60" aria-hidden="true">/</kbd>
      </div>

      <p v-if="!collapsed && searching" class="mt-2 text-[10px] font-semibold text-emas-400">{{ resultCount }} menu cocok</p>
    </div>

    <nav class="sidebar-scroll mt-2 flex-1 overflow-y-auto" aria-label="Navigasi utama">
      <template v-if="!collapsed">
        <section v-for="section in sections" :key="section.key" class="nav-section">
          <h2 class="nav-section-label mb-2 px-4 text-[10px] font-bold text-lumut-400 uppercase tracking-wider">{{ section.label }}</h2>

          <ul class="space-y-0.5">
            <li v-for="item in section.items" :key="item.key">
              <a
                  :href="item.href"
                  class="nav-item group flex items-center gap-3 px-3 py-2 rounded-xl border-transparent transition-all duration-200 cursor-pointer"
                  :class="{
                    'border daun-500/30 bg-daun-500/10': item.active,
                    'border-transparent hover:border-daun-400/20 hover:bg-daun-400/5': !item.active,
                  }"
                  :title="item.hint || item.label"
                  :aria-current="item.active ? 'page' : undefined"
                  @click="emit('navigate', item)"
              >
                <span class="nav-rail flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-transparent transition-colors duration-200" :class="{ 'bg-daun-500/20 text-daun-400 border-daun-400/30': item.active, 'hover:bg-daun-400/10 hover:text-daun-400': !item.active }" aria-hidden="true">
                  <NavIcon :name="item.icon" class="h-4.5 w-4.5" />
                </span>

                <span class="nav-label flex-1 min-w-0 truncate" :class="{ 'font-bold text-krem-100': item.active, 'font-medium text-krem-300/80': !item.active }">{{ item.label }}</span>

                <span
                    v-if="item.badge > 0"
                    class="nav-badge inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-emas-400 text-[10px] font-bold text-hutan-800 shadow-sm shadow-emas-400/20"
                    :aria-label="`${item.badge} belum ditangani`"
                >
                  {{ formatBadge(item.badge) }}
                </span>
              </a>
            </li>
          </ul>
        </section>

        <p v-if="noResults" class="px-4 mt-4 text-[10px] text-lumut-400 text-center">Tidak ada menu yang cocok dengan pencarian ini.</p>
      </template>

      <template v-else>
        <!-- Rail mode: icon-only sidebar -->
        <div class="px-2 py-2 space-y-1">
          <template v-for="section in sections" :key="section.key">
            <div v-for="item in section.items" :key="item.key" class="animate-sidebar-rail group relative">
              <span class="nav-rail-separator block h-[1px] w-full bg-daun-400/10 my-1" aria-hidden="true" />

              <a
                  :href="item.href"
                  class="nav-rail-item flex h-9 w-9 items-center justify-center rounded-lg border border-transparent transition-all duration-200 cursor-pointer mx-auto"
                  :class="{
                    'bg-daun-500/20 border-daun-400/30': item.active,
                    'hover:bg-daun-400/10 hover:border-daun-400/20': !item.active,
                  }"
                  :title="item.hint || item.label"
                  :aria-label="item.label"
                  :aria-current="item.active ? 'page' : undefined"
                  @click="emit('navigate', item)"
              >
                <NavIcon :name="item.icon" class="h-4 w-4 transition-colors duration-200" :class="{ 'text-emas-400': item.active, 'text-lumut-400': !item.active }" />
                <span v-if="item.badge > 0" class="nav-rail-badge absolute right-0 top-0 flex h-3 w-3 items-center justify-center rounded-full bg-[#ef4419] text-[9px] font-bold text-white" aria-hidden="true">
                  {{ formatBadge(item.badge) }}
                </span>
                <span v-else-if="item.active" class="nav-rail-dot absolute right-1 top-1 h-2 w-2 rounded-full bg-emas-400 shadow-lg shadow-emas-400/40" aria-hidden="true" />
              </a>
            </div>
          </template>
        </div>
      </template>
    </nav>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import NavIcon from '@/Components/NavIcon.vue';

const props = defineProps({
    sections: { type: Array, required: true },
    collapsed: { type: Boolean, default: false },
    noResults: { type: Boolean, default: false },
    resultCount: { type: Number, default: 0 },
});

const query = defineModel('query', { type: String, default: '' });
const emit = defineEmits(['navigate']);

const searchInput = ref(null);
const searching = computed(() => query.value.trim().length > 0);

function formatBadge(value) {
    return value > 99 ? '99+' : value;
}

function clear() {
    query.value = '';
}

function handleGlobalKeydown(event) {
    const target = event.target;
    const tag = target?.tagName?.toLowerCase();
    const isTyping = tag === 'input' || tag === 'textarea' || target?.isContentEditable;

    if (event.key === '/' && !isTyping && !props.collapsed) {
        event.preventDefault();
        searchInput.value?.focus();
        return;
    }

    if (event.key === 'Escape' && document.activeElement === searchInput.value) {
        event.preventDefault();
        searching.value ? clear() : searchInput.value?.blur();
    }
}

onMounted(() => window.addEventListener('keydown', handleGlobalKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', handleGlobalKeydown));
</script>
