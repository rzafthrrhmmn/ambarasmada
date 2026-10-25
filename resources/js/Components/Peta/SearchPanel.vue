<template>
  <div class="mb-4 rounded-xl border-2 border-daun-400/40 bg-hutan-600 p-4">
    <label for="peta-kabupaten" class="block text-xs font-medium text-[#d4dc9a] mb-2">Cari Kabupaten</label>
    <div class="flex flex-col gap-2 sm:flex-row">
      <select
        id="peta-kabupaten"
        :value="selectedKabupaten"
        @change="onKabupatenChange"
        class="flex-1 rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2.5 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
      >
        <option value="">-- Pilih Kabupaten --</option>
        <option
          v-for="kab in kabupatens"
          :key="kab.id_kab"
          :value="kab.id_kab"
        >
          {{ kab.nama_kab }}
        </option>
      </select>

      <!-- Dropdown kecamatan mengikuti kabupaten yang dipilih. Daftar seluruh
           Sulawesi hampir 313 nama dan hampir semuanya di luar kabupaten
           aktif, jadi yang ditampilkan selalu daftar milik kabupaten
           terpilih saja. -->
      <select
        id="peta-kecamatan"
        :value="selectedKecamatan"
        @change="onKecamatanChange"
        :disabled="!selectedKabupaten"
        class="flex-1 rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2.5 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330] disabled:cursor-not-allowed disabled:opacity-50"
      >
        <option value="">
          {{ selectedKabupaten ? '-- Pilih Kecamatan --' : '-- Pilih Kabupaten dulu --' }}
        </option>
        <option
          v-for="kec in kecamatanOptions"
          :key="kec.id_kec"
          :value="kec.id_kec"
        >
          {{ kec.nama_kec }}
        </option>
      </select>

      <div class="flex shrink-0 gap-2">
        <button
          v-if="selectedKabupaten"
          @click="emit('print-wilayah')"
          :disabled="isExporting"
          class="rounded-lg border-2 border-daun-400 bg-daun-400/10 text-daun-400 transition hover:bg-daun-400/20 disabled:opacity-50 disabled:cursor-not-allowed"
          :title="`Cetak PNG wilayah yang dipilih: ${selectedKabData?.nama_kab ?? ''}`"
        >
          {{ isExporting ? 'Menyiapkan...' : 'Cetak PNG' }}
        </button>
        <button
          v-if="selectedKabupaten"
          @click="emit('clear-selection')"
          class="rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30"
        >
          Hapus
        </button>
      </div>
    </div>

    <div v-if="selectedKabData" class="mt-2 flex flex-wrap gap-3 text-xs text-[#8fa06a]">
      <span>Kabupaten: <strong class="text-[#EDD330]">{{ selectedKabData.nama_kab }}</strong></span>
      <span v-if="selectedKecData">
        Kecamatan:
        <strong class="text-[#EDD330]">{{ selectedKecData.nama_kec }}</strong>
        <span class="text-[#8fa06a]">({{ selectedKecData.id_kec }})</span>
      </span>
      <span>ID: {{ selectedKabData.id_kab }}</span>
      <span v-if="selectedWilayahBBox">BBox: {{ selectedWilayahBBox.join(', ') }}</span>
      <span v-if="selectedKabupaten && !selectedKecamatan">
        {{ kecamatanOptions.length }} kecamatan di {{ selectedKabData.nama_kab }}
      </span>
    </div>

    <!-- Pencarian global tempat atau koordinat (lat, lng) lewat usePetaSearch().
         State pencarian (searchQuery, searchFocused, searchResults) dikelola
         di composable, tidak lagi terserang di halaman induk. -->
    <div class="mt-4 flex flex-col sm:flex-row sm:items-end gap-2">
      <div class="relative flex-1">
        <input
          v-model="searchQuery"
          @keyup.enter="handleSearchPlace"
          @focus="searchFocused = true"
          @blur="searchFocused = false"
          placeholder="Cari tempat atau koordinat (lat, lng)..."
          class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330] pr-10"
        />
        <button
          v-if="searchQuery"
          type="button"
          @click="clearSearch"
          class="absolute right-2 top-1/2 -translate-y-1/2 text-[#8fa06a] transition hover:text-[#EDD330]"
          title="Bersihkan pencarian"
        >
          <NavIcon name="close" class="h-4 w-4" />
        </button>
      </div>
      <button
        @click="handleSearchPlace"
        :disabled="!searchQuery.trim()"
        class="rounded-lg border-2 border-daun-400 bg-daun-400/10 px-3 py-2 text-sm font-bold text-daun-400 transition hover:bg-daun-400/20 disabled:opacity-50"
      >
        Cari
      </button>
    </div>

    <!-- Hasil pencarian dropdown -->
    <div
      v-if="searchFocused && searchResults.length > 0"
      class="max-h-60 w-full overflow-y-auto rounded-lg border border-[#6F9435] bg-[#263D26] shadow-lg mt-1"
    >
      <div
        v-for="result in searchResults"
        :key="result.place_id ?? result.lat + ',' + result.lon"
        @click="onSelectSearchResult(result)"
        class="cursor-pointer border-b border-[#6F9435]/20 px-4 py-2 last:border-0 hover:bg-[#335233]"
      >
        <div class="font-medium text-[#f0ead8]">{{ result.display_name || result.label }}</div>
        <div class="text-xs text-[#8fa06a]">{{ result.lat }}, {{ result.lon }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { PropType } from 'vue';
import NavIcon from '@/Components/NavIcon.vue';
import { usePetaSearch } from '@/Composables/usePetaSearch.js';

interface Kabupaten {
  id_kab: string;
  nama_kab: string;
  kecamatans?: Kecamatan[];
}

interface Kecamatan {
  id_kec: string;
  nama_kec: string;
}

interface SearchResult {
  place_id?: number | string;
  display_name?: string;
  label?: string;
  lat: number;
  lon: number;
}

interface SearchPlaceResult {
  type: string;
  lat?: number;
  lng?: number;
  results?: SearchResult[];
}

const props = defineProps({
  kabupatens: {
    type: Array as PropType<Kabupaten[]>,
    default: () => [],
  },
  selectedKabupaten: {
    type: String,
    default: '',
  },
  selectedKecamatan: {
    type: String,
    default: '',
  },
  selectedKabData: {
    type: Object as PropType<Kabupaten | null>,
    default: null,
  },
  selectedKecData: {
    type: Object as PropType<Kecamatan | null>,
    default: null,
  },
  selectedWilayahBBox: {
    type: Array as PropType<number[]>,
    default: null,
  },
  kecamatanOptions: {
    type: Array as PropType<Kecamatan[]>,
    default: () => [],
  },
  mapStatus: {
    type: String,
    default: '',
  },
  isExporting: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  'update:selected-kabupaten',
  'update:selected-kecamatan',
  'print-wilayah',
  'clear-selection',
  'search-select',
]);

const {
  searchQuery,
  searchFocused,
  searchResults,
  searchPlace,
  selectSearchResult,
  clearSearch,
} = usePetaSearch();

function onKabupatenChange(event: Event) {
  const target = event.target as HTMLSelectElement;
  emit('update:selected-kabupaten', target.value);
  emit('update:selected-kecamatan', '');
}

function onKecamatanChange(event: Event) {
  const target = event.target as HTMLSelectElement;
  emit('update:selected-kecamatan', target.value);
}

async function handleSearchPlace() {
  const result = (await searchPlace()) as SearchPlaceResult;
  if (result?.type === 'coordinates') {
    emit('search-select', {
      lat: result.lat,
      lon: result.lng,
      label: `${result.lat!.toFixed(6)}, ${result.lng!.toFixed(6)}`,
    });
    clearSearch();
    return;
  }
}

function onSelectSearchResult(result: SearchResult) {
  const chosen = selectSearchResult(result);
  clearSearch();
  emit('search-select', {
    lat: chosen.lat,
    lon: chosen.lon,
    label: chosen.label,
  });
}
</script>
