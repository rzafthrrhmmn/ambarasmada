<template>
  <div class="flex min-h-0 flex-1 flex-col">
    <div v-if="!collapsed" class="sidebar-search">
      <NavIcon name="search" class="h-4 w-4" />

      <input
        ref="searchInput"
        v-model="query"
        type="search"
        class="sidebar-search-input"
        placeholder="Cari menu..."
        aria-label="Cari menu di sidebar"
        autocomplete="off"
        spellcheck="false"
      />

      <button v-if="searching" type="button" class="nav-clear" aria-label="Bersihkan pencarian" @click="clear">
        <NavIcon name="close" class="h-4 w-4" />
      </button>
      <kbd v-else class="nav-kbd" aria-hidden="true">/</kbd>
    </div>

    <p v-if="!collapsed && searching" class="nav-count">{{ resultCount }} menu cocok</p>

    <nav class="sidebar-scroll mt-3" aria-label="Navigasi utama">
      <template v-if="!collapsed">
        <section v-for="section in sections" :key="section.key" class="nav-section">
          <h2 class="nav-section-label mb-1.5">{{ section.label }}</h2>

          <ul class="space-y-0.5">
            <li v-for="item in section.items" :key="item.key">
              <Link
                :href="item.href"
                class="nav-item"
                :class="{ 'is-active': item.active }"
                :title="item.hint || item.label"
                :aria-current="item.active ? 'page' : undefined"
                @click="emit('navigate', item)"
              >
                <span class="nav-rail" aria-hidden="true" />

                <span class="nav-icon">
                  <NavIcon :name="item.icon" class="h-5 w-5" />
                </span>

                <span class="nav-label">{{ item.label }}</span>

                <span
                  v-if="item.badge > 0"
                  class="nav-badge animate-nav-badge"
                  :aria-label="`${item.badge} belum ditangani`"
                >
                  {{ formatBadge(item.badge) }}
                </span>
              </Link>
            </li>
          </ul>
        </section>

        <p v-if="noResults" class="nav-empty">Tidak ada menu yang cocok dengan pencarian ini.</p>
      </template>

      <template v-else>
        <div v-for="section in sections" :key="section.key" class="animate-sidebar-rail">
          <span class="nav-rail-separator block" aria-hidden="true" />

          <Link
            v-for="item in section.items"
            :key="item.key"
            :href="item.href"
            class="nav-rail-item"
            :class="{ 'is-active': item.active }"
            :title="item.hint || item.label"
            :aria-label="item.label"
            :aria-current="item.active ? 'page' : undefined"
            @click="emit('navigate', item)"
          >
            <NavIcon :name="item.icon" class="h-5 w-5" />
            <span v-if="item.badge > 0" class="nav-rail-badge" aria-hidden="true">
              {{ formatBadge(item.badge) }}
            </span>
            <span v-else-if="item.active" class="nav-rail-dot" aria-hidden="true" />
          </Link>
        </div>
      </template>
    </nav>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import NavIcon from '@/Components/NavIcon.vue';

/**
 * Daftar menu sidebar. Tidak ada daftar tautan di dalam template: seluruh isi
 * berasal dari `sections` hasil resolver `useNavigation`, termasuk status aktif
 * dan angka badge. Mode ringkas memakai rail ikon dengan tooltip agar label
 * tetap dapat diakses tanpa memakan lebar.
 */
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