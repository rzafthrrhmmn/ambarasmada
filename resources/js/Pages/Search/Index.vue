<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-emas-400">Pencarian Global</p>
        <h1 class="mt-1 text-2xl font-bold text-krem-100">Hasil Pencarian</h1>
        <p v-if="query" class="mt-1 text-sm text-krem-300">Menampilkan hasil untuk "<strong>{{ query }}</strong>"</p>
      </div>
      <a href="/attendance" class="inline-flex items-center gap-1.5 rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-semibold text-emas-400 transition hover:bg-daun-500/20 hover:text-emas-400">
        <span>Kembali</span>
      </a>
    </div>

    <div class="rounded-2xl border border-[#6F9435] bg-hutan-600 p-5 shadow-sm">
      <div class="mb-4 relative">
        <NavIcon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-krem-300/70" />
        <input
          v-model="searchQuery"
          type="search"
          :value="query"
          @input="debouncedSearch"
          placeholder="Cari anggota, acara, surat, atau sesi latihan..."
          class="w-full rounded-lg border border-[#6F9435] bg-hutan-800 px-3 py-2 pl-9 text-sm text-krem-100 focus:border-daun-500 focus:outline-none"
          aria-label="Cari di seluruh modul"
        />
      </div>

      <div v-if="searching" class="flex items-center justify-center py-8">
        <SkeletonLoader variant="card" :lines="3" class="h-4 w-48" />
        <SkeletonLoader variant="card" :lines="3" class="h-4 w-48 mt-2" />
        <SkeletonLoader variant="card" :lines="3" class="h-4 w-48 mt-2" />
      </div>

      <div v-else-if="resultCount === 0 && query !== ''" class="py-10 text-center">
        <AppIcon name="search" class="mx-auto empty-state-icon h-8 w-8 text-krem-300/50" />
        <p class="mt-3 text-sm text-krem-300">Tidak ditemukan hasil untuk "<strong>{{ query }}</strong>".</p>
        <p class="mt-1 text-xs text-lumut-400">Coba kata kunci lain atau periksa ejaan.</p>
      </div>

      <div v-else-if="query === ''" class="py-10 text-center">
        <AppIcon name="search" class="mx-auto empty-state-icon h-8 w-8 text-krem-300/50" />
        <p class="mt-3 text-sm text-krem-300">Ketik kata kunci di atas untuk mencari di seluruh modul.</p>
        <p class="mt-1 text-xs text-lumut-400">Cari anggota, acara, surat, dan sesi latihan secara bersamaan.</p>
      </div>

      <div v-else class="space-y-3">
        <div v-for="group in resultGroups" :key="group.type" class="mb-5">
          <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-krem-300">{{ group.label }}</h3>
          <div class="space-y-2">
            <a
              v-for="item in group.items"
              :key="item.url + '-' + item.title"
              :href="item.url"
              class="block rounded-xl border border-[#6F9435]/40 bg-hutan-700/50 px-4 py-3 hover:border-daun-500/60 hover:bg-daun-500/20 transition"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-sm font-medium text-krem-100">{{ item.title }}</p>
                  <p class="mt-0.5 text-[11px] text-lumut-400">{{ item.subtitle }}</p>
                </div>
                <NavIcon :name="item.icon" class="shrink-0 h-4 w-4 text-daun-500/70" />
              </div>
            </a>
          </div>
        </div>
      </div>

      <div v-if="resultCount > 0" class="mt-6 pt-6 border-t border-[#6F9435]/30 text-center">
        <p class="text-xs text-lumut-400">{{ resultCount }} hasil ditemukan</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import NavIcon from '@/Components/NavIcon.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';

const props = defineProps({
  query: { type: String, default: '' },
  results: { type: Array, default: () => [] },
  resultCount: { type: Number, default: 0 },
});

const toast = useToast();
const searchQuery = ref(props.query);
let debounceTimer = null;

const resultGroups = computed(() => {
  const groups = {};
  (props.results || []).forEach((item) => {
    if (!groups[item.type]) {
      groups[item.type] = { label: '', items: [] };
    }
    groups[item.type].items.push(item);
  });

  const labels = {
    member: 'Anggota',
    event: 'Acara',
    letter: 'Surat',
    session: 'Sesi Latihan',
  };

  return Object.entries(groups).map(([type, data]) => ({
    type,
    label: labels[type] || type,
    items: data.items,
  }));
});

const searching = ref(false);

function debouncedSearch() {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    const q = searchQuery.value.trim();
    if (q === '') {
      router.visit(route('search.index'), { replace: true });
      return;
    }
    router.visit(route('search.index', { q }), { replace: true });
  }, 300);
}

watch(
  () => searchQuery.value,
  () => {
    debouncedSearch();
  },
);

onMounted(() => {
  searchQuery.value = props.query;
});
</script>
