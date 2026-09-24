<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Peta Kontur</p>
        <h1 class="mt-1 text-2xl font-extrabold text-[#f0ead8]">Peta Kontur Sulawesi Selatan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Cari kabupaten, lihat batas administratif, dan jelajahi kontur topografi.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-if="hasPmtiles"
          @click="showOfflineModal = true"
          class="inline-flex items-center rounded-lg border-2 border-[#A7B92A] bg-[#A7B92A]/10 px-4 py-2 text-sm font-bold text-[#A7B92A] transition hover:bg-[#A7B92A]/20"
        >
          Unduh Peta Offline
        </button>
        <button
          @click="toggleLayer"
          class="inline-flex items-center rounded-lg border-2 border-[#6F9435] px-4 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30"
        >
          {{ showContour ? 'Sembunyikan Kontur' : 'Tampilkan Kontur' }}
        </button>
        <button
          @click="toggleHillshade"
          class="inline-flex items-center rounded-lg border-2 border-[#6F9435] px-4 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30"
        >
          {{ showHillshade ? 'Sembunyikan Hillshade' : 'Tampilkan Hillshade' }}
        </button>
        <button
          @click="toggleDistrictLabels"
          class="inline-flex items-center rounded-lg border-2 border-[#6F9435] px-4 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30"
        >
          {{ showDistrictLabels ? 'Sembunyikan Label' : 'Tampilkan Label' }}
        </button>
        <button
          @click="toggleContourLabels"
          class="inline-flex items-center rounded-lg border-2 border-[#6F9435] px-4 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30"
        >
          {{ showContourLabels ? 'Sembunyikan Label Kontur' : 'Tampilkan Label Kontur' }}
        </button>
      </div>
    </div>

    <div class="mb-4 rounded-xl border-2 border-[#A7B92A]/40 bg-[#335233] p-4">
      <label class="block text-xs font-medium text-[#d4dc9a] mb-2">Cari Kabupaten</label>
      <div class="flex gap-2">
        <select
          v-model="selectedKabupaten"
          @change="onKabupatenSelect"
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
        <button
          v-if="selectedKabupaten"
          @click="clearSelection"
          class="rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30"
        >
          Hapus
        </button>
      </div>
      <div v-if="selectedKabData" class="mt-2 flex flex-wrap gap-3 text-xs text-[#8fa06a]">
        <span>Kabupaten: <strong class="text-[#EDD330]">{{ selectedKabData.nama_kab }}</strong></span>
        <span>ID: {{ selectedKabData.id_kab }}</span>
        <span v-if="selectedKabData.bbox">BBox: {{ selectedKabData.bbox.join(', ') }}</span>
      </div>
    </div>

    <div class="relative overflow-hidden rounded-2xl border-2 border-[#A7B92A]/40 bg-[#263D26] shadow-lg">
      <div ref="mapContainer" class="h-[70vh] w-full min-h-[400px]"></div>
      
      <!-- Coordinate display on mouse hover -->
      <div v-if="showCoordinates && mouseCoords" class="absolute bottom-4 left-4 z-20 rounded-lg bg-[#1a1a1a]/90 border border-[#6F9435]/30 px-3 py-1.5 text-xs font-mono text-[#EDD330] pointer-events-none">
        Lon: {{ mouseCoords.lng.toFixed(6) }}° | Lat: {{ mouseCoords.lat.toFixed(6) }}°
      </div>

      <!-- Scale bar -->
      <div v-if="showScaleBar" class="absolute bottom-4 left-4 z-20" ref="scaleBarContainer"></div>

      <!-- North arrow -->
      <div v-if="showNorthArrow" class="absolute top-4 right-4 z-20" ref="northArrowContainer"></div>

      <!-- Elevation legend -->
      <div v-if="showElevationLegend" class="absolute bottom-4 right-4 z-20" ref="legendContainer"></div>

      <!-- Measurement tool panel -->
      <div v-if="measurementMode !== 'none'" class="absolute top-4 left-4 z-20 rounded-lg bg-[#1a1a1a]/90 border border-[#6F9435]/30 p-3 text-xs text-[#EDD330] min-w-[200px]">
        <div class="flex items-center justify-between mb-2">
          <span class="font-bold">{{ measurementMode === 'distance' ? '📏 Ukur Jarak' : '📐 Ukur Luas' }}</span>
          <button @click="cancelMeasurement" class="text-[#f87171] hover:underline">Batal</button>
        </div>
        <div v-if="measurementPoints.length > 0" class="space-y-1">
          <div>Titik: {{ measurementPoints.length }}</div>
          <div v-if="measurementMode === 'distance' && measurementDistance > 0">
            Jarak: {{ formatDistance(measurementDistance) }}
          </div>
          <div v-if="measurementMode === 'area' && measurementArea > 0">
            Luas: {{ formatArea(measurementArea) }}
          </div>
        </div>
        <button @click="finishMeasurement" class="mt-2 w-full rounded bg-[#A7B92A] px-3 py-1.5 text-xs font-bold text-white">Selesai</button>
      </div>

      <!-- Search panel -->
      <div class="absolute top-4 left-4 z-20 flex gap-2" style="max-width: 300px;">
        <div class="relative flex-1">
          <input
            v-model="searchQuery"
            @keyup.enter="searchPlace"
            @focus="searchFocused = true"
            @blur="searchFocused = false"
            placeholder="Cari tempat atau koordinat (lat, lng)..."
            class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330] pr-10"
          />
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-[#8fa06a] hover:text-[#EDD330]"
          >
            ✕
          </button>
        </div>
        <button
          @click="searchPlace"
          :disabled="!searchQuery.trim()"
          class="rounded-lg border-2 border-[#A7B92A] bg-[#A7B92A]/10 px-3 py-2 text-sm font-bold text-[#A7B92A] transition hover:bg-[#A7B92A]/20 disabled:opacity-50"
        >
          Cari
        </button>
      </div>

      <!-- Search results dropdown -->
      <div v-if="searchFocused && searchResults.length > 0" class="absolute top-12 left-4 z-30 w-[300px] rounded-lg border border-[#6F9435] bg-[#263D26] shadow-lg max-h-60 overflow-y-auto">
        <div v-for="result in searchResults" :key="result.place_id || result.lat + ',' + result.lon" @click="selectSearchResult(result)" class="px-4 py-2 hover:bg-[#335233] cursor-pointer border-b border-[#6F9435]/20 last:border-0">
          <div class="font-medium text-[#f0ead8]">{{ result.display_name || result.label }}</div>
          <div class="text-xs text-[#8fa06a]">{{ result.lat }}, {{ result.lon }}</div>
        </div>
      </div>

      <!-- GPS locate button -->
      <button
        v-if="hasGeolocation"
        @click="locateUser"
        :disabled="locating"
        class="absolute bottom-4 right-4 z-20 rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30 disabled:opacity-50"
        title="Lokasi saya"
      >
        {{ locating ? '⟳' : '📍' }}
      </button>

      <!-- Basemap selector -->
      <div class="absolute top-4 right-4 z-20" style="width: 180px;">
        <select
          v-model="basemap"
          @change="changeBasemap"
          class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        >
          <option value="osm">🗺️ OpenStreetMap</option>
          <option value="satellite">🛰️ Satelit</option>
          <option value="terrain">🏔️ Terrain</option>
          <option value="dark">🌙 Dark</option>
        </select>
      </div>

      <!-- Bookmark/save view button -->
      <button
        @click="saveBookmark"
        class="absolute top-4 right-52 z-20 rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30"
        title="Simpan tampilan"
      >
        🔖
      </button>

      <!-- Print button -->
      <button
        @click="printMap"
        class="absolute top-4 right-96 z-20 rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30"
        title="Cetak peta"
      >
        🖨️
      </button>

      <!-- Offline indicator -->
      <div v-if="isOffline" class="absolute top-4 left-4 z-20 rounded-lg bg-[#f59e0b]/90 px-3 py-1.5 text-xs font-bold text-white animate-pulse">
        📴 Mode Offline
      </div>

      <div v-if="!hasPmtiles" class="absolute inset-0 flex items-center justify-center bg-[#263D26]/90">
        <div class="text-center p-6">
          <p class="text-2xl font-bold text-[#EDD330]">Peta Belum Tersedia</p>
          <p class="mt-2 text-sm text-[#8fa06a]">File PMTiles belum diunggah ke server.</p>
        </div>
      </div>
      <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-[#263D26]/70">
        <p class="text-lg font-bold text-[#EDD330]">Memuat peta...</p>
      </div>
      <div v-if="mapError" class="absolute inset-0 flex items-center justify-center bg-[#263D26]/90">
        <div class="text-center p-6">
          <p class="text-2xl font-bold text-[#f87171]">Gagal Memuat Peta</p>
          <p class="mt-2 text-sm text-[#8fa06a]">{{ mapError }}</p>
          <button
            @click="initMap"
            class="mt-4 inline-flex items-center rounded-lg border-2 border-[#A7B92A] bg-[#A7B92A]/10 px-4 py-2 text-sm font-bold text-[#A7B92A] transition hover:bg-[#A7B92A]/20"
          >
            Coba Lagi
          </button>
        </div>
      </div>
    </div>

    <div v-if="mapStatus" class="mt-3 rounded-lg border border-[#6F9435]/30 bg-[#335233] p-3 text-xs text-[#d4dc9a]">
      {{ mapStatus }}
    </div>
  </AppLayout>

  <Modal v-if="showOfflineModal" title="Unduh Peta Offline" @close="showOfflineModal = false">
    <div class="space-y-4">
      <!-- Download Mode Selection -->
      <div class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
        <label class="block text-xs font-medium text-[#d4dc9a] mb-3">Mode Unduh</label>
        <div class="grid gap-2 sm:grid-cols-2">
          <label class="relative cursor-pointer">
            <input
              type="radio"
              name="downloadMode"
              value="full"
              v-model="downloadMode"
              class="sr-only peer"
            />
            <div class="relative rounded-lg border-2 border-[#6F9435] bg-[#263D26] p-4 text-center transition-all peer-checked:border-[#A7B92A] peer-checked:bg-[#A7B92A]/10 peer-checked:ring-2 peer-checked:ring-[#A7B92A]/20">
              <div class="text-lg font-bold text-[#EDD330]">🗺️</div>
              <div class="mt-1 text-sm font-semibold text-[#f0ead8]">Seluruh Peta</div>
              <div class="mt-1 text-xs text-[#8fa06a]">Sulawesi Selatan lengkap</div>
            </div>
          </label>
          <label class="relative cursor-pointer">
            <input
              type="radio"
              name="downloadMode"
              value="region"
              v-model="downloadMode"
              class="sr-only peer"
            />
            <div class="relative rounded-lg border-2 border-[#6F9435] bg-[#263D26] p-4 text-center transition-all peer-checked:border-[#A7B92A] peer-checked:bg-[#A7B92A]/10 peer-checked:ring-2 peer-checked:ring-[#A7B92A]/20">
              <div class="text-lg font-bold text-[#EDD330]">📍</div>
              <div class="mt-1 text-sm font-semibold text-[#f0ead8]">Satu Daerah</div>
              <div class="mt-1 text-xs text-[#8fa06a]">Pilih kabupaten/kota</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Region Selection (when region mode) -->
      <div v-if="downloadMode === 'region'" class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
        <label class="block text-xs font-medium text-[#d4dc9a] mb-2">Pilih Kabupaten/Kota</label>
        <select
          v-model="selectedOfflineRegion"
          class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2.5 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        >
          <option value="">-- Pilih Daerah --</option>
          <option
            v-for="kab in kabupatens"
            :key="kab.id_kab"
            :value="kab.id_kab"
          >
            {{ kab.nama_kab }}
          </option>
        </select>
        <div v-if="selectedOfflineRegion" class="mt-2 text-xs text-[#8fa06a]">
          Area: {{ selectedOfflineRegionData?.nama_kab }} ({{ selectedOfflineRegionData?.id_kab }})
        </div>
      </div>

      <!-- Mini Map Preview (when region selected) -->
      <div v-if="showMiniMap" class="rounded-lg border border-[#6F9435]/30 bg-[#263D26] p-3">
        <div class="flex items-center justify-between mb-2">
          <label class="text-xs font-medium text-[#d4dc9a]">Preview Wilayah</label>
          <span v-if="selectedOfflineRegionData" class="text-xs text-[#EDD330]">{{ selectedOfflineRegionData.nama_kab }}</span>
        </div>
        <div ref="miniMapContainer" class="h-[200px] w-full rounded-lg overflow-hidden border border-[#6F9435]/30 relative">
          <div v-if="miniMapLoading" class="absolute inset-0 flex items-center justify-center bg-[#263D26]/90">
            <p class="text-sm text-[#8fa06a]">Memuat preview...</p>
          </div>
          <div v-if="miniMapError" class="absolute inset-0 flex items-center justify-center bg-[#263D26]/90 text-center p-4">
            <p class="text-sm text-[#f87171]">{{ miniMapError }}</p>
            <button @click="initMiniMap" class="mt-2 text-xs text-[#A7B92A] hover:underline">Coba lagi</button>
          </div>
        </div>
      </div>

      <!-- Zoom Settings -->
      <div class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
        <label class="block text-xs font-medium text-[#d4dc9a] mb-3">Level Zoom</label>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-xs font-medium text-[#d4dc9a] mb-1">Zoom Min</label>
            <input
              v-model.number="offlineZoomMin"
              type="range"
              min="6"
              max="14"
              class="w-full"
            />
            <p class="text-xs text-[#8fa06a] text-right">Zoom: {{ offlineZoomMin }}</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-[#d4dc9a] mb-1">Zoom Max</label>
            <input
              v-model.number="offlineZoomMax"
              type="range"
              min="6"
              max="14"
              class="w-full"
            />
            <p class="text-xs text-[#8fa06a] text-right">Zoom: {{ offlineZoomMax }}</p>
          </div>
        </div>
        <div class="mt-3 rounded-lg bg-[#263D26] p-3 text-xs text-[#8fa06a]">
          <p>Estimasi tile: {{ estimatedTiles.toLocaleString() }}</p>
          <p>Estimasi ukuran: ~{{ estimatedSize }}</p>
        </div>
      </div>

      <!-- Map Layout Options -->
      <div class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
        <label class="block text-xs font-medium text-[#d4dc9a] mb-3">Komponen Peta Offline</label>
        <div class="space-y-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="includeScaleBar"
              class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
            />
            <span class="text-sm text-[#f0ead8]">Scale Bar (Skala)</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="includeNorthArrow"
              class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
            />
            <span class="text-sm text-[#f0ead8]">Kompas (North Arrow)</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="includeLegend"
              class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
            />
            <span class="text-sm text-[#f0ead8]">Legenda Kontur</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="includeHistogram"
              class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
            />
            <span class="text-sm text-[#f0ead8]">Histogram Elevasi</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              v-model="includeGrid"
              class="rounded border-[#6F9435] text-[#A7B92A] focus:ring-[#A7B92A]"
            />
            <span class="text-sm text-[#f0ead8]">Grid Koordinat</span>
          </label>
        </div>
      </div>

      <div v-if="downloadStatus" class="rounded-lg border border-[#6F9435]/30 bg-[#263D26] p-3 text-xs text-[#d4dc9a]">
        {{ downloadStatus }}
      </div>
      <button
        @click="downloadOffline"
        :disabled="downloading || (downloadMode === 'region' && !selectedOfflineRegion)"
        class="w-full rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ downloading ? 'Mengunduh...' : 'Mulai Unduh' }}
      </button>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
  mapConfig: Object,
  kabupatens: Array,
});

const page = usePage();
const mapContainer = ref(null);
const miniMapContainer = ref(null);
const scaleBarContainer = ref(null);
const northArrowContainer = ref(null);
const legendContainer = ref(null);
const map = ref(null);
const miniMap = ref(null);
const loading = ref(true);
const mapError = ref(null);
const showContour = ref(true);
const showHillshade = ref(false);
const showDistrictLabels = ref(false);
const showContourLabels = ref(false);
const showOfflineModal = ref(false);
const downloading = ref(false);
const downloadStatus = ref('');
const mapStatus = ref('');
const offlineZoomMin = ref(8);
const offlineZoomMax = ref(14);
const selectedKabupaten = ref('');
const miniMapLoading = ref(false);
const miniMapError = ref('');

// Map controls state
const showScaleBar = ref(true);
const showNorthArrow = ref(true);
const showElevationLegend = ref(true);
const showCoordinates = ref(true);
const mouseCoords = ref(null);
const hasGeolocation = ref(false);
const locating = ref(false);

// Basemap
const basemap = ref('osm');
const basemapSources = {
  osm: { type: 'raster', tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'], tileSize: 256, attribution: '© OpenStreetMap' },
  satellite: { type: 'raster', tiles: ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'], tileSize: 256, attribution: '© Esri' },
  terrain: { type: 'raster', tiles: ['https://tile.opentopomap.org/{z}/{x}/{y}.png'], tileSize: 256, attribution: '© OpenTopoMap' },
  dark: { type: 'raster', tiles: ['https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png'], tileSize: 256, attribution: '© Stadia Maps' },
};

// Search
const searchQuery = ref('');
const searchFocused = ref(false);
const searchResults = ref([]);

// Measurement
const measurementMode = ref('none'); // 'none', 'distance', 'area'
const measurementPoints = ref([]);
const measurementDistance = ref(0);
const measurementArea = ref(0);
const measurementSource = ref(null);
const measurementLineLayer = ref(null);
const measurementFillLayer = ref(null);

// Bookmarks
const bookmarks = ref([]);

// Offline detection
const isOffline = ref(!navigator.onLine);
const boundingBox = computed(() => props.mapConfig?.boundingBox ?? { west: 118.9, east: 121.6, south: -5.8, north: -1.8 });
const kabupatens = computed(() => props.kabupatens ?? []);

const geojsonUrl = computed(() => {
  const url = props.mapConfig?.geojsonUrl ?? '/storage/maps/batas_kabupaten_sulsel.geojson';
  if (url.startsWith('http')) {
    return url.replace(/^https?:\/\/[^\/]+/, '');
  }
  return url;
});

const selectedKabData = computed(() => {
  return kabupatens.value.find((k) => k.id_kab === selectedKabupaten.value) ?? null;
});

const selectedOfflineRegionData = computed(() => {
  return kabupatens.value.find((k) => k.id_kab === selectedOfflineRegion.value) ?? null;
});

const currentBBox = computed(() => {
  if (downloadMode.value === 'region' && selectedOfflineRegionData.value?.bbox) {
    const [west, south, east, north] = selectedOfflineRegionData.value.bbox;
    return { west, east, south, north };
  }
  return boundingBox.value;
});

const estimatedTiles = computed(() => {
  let total = 0;
  const bbox = currentBBox.value;
  for (let z = offlineZoomMin.value; z <= offlineZoomMax.value; z++) {
    const xMin = Math.floor(((bbox.west + 180) / 360) * Math.pow(2, z));
    const xMax = Math.ceil(((bbox.east + 180) / 360) * Math.pow(2, z)) - 1;
    const latRadN = (bbox.north * Math.PI) / 180;
    const latRadS = (bbox.south * Math.PI) / 180;
    const yMin = Math.ceil((1 - Math.log(Math.tan(latRadN) + 1 / Math.cos(latRadN)) / Math.PI) / 2 * Math.pow(2, z));
    const yMax = Math.floor((1 - Math.log(Math.tan(latRadS) + 1 / Math.cos(latRadS)) / Math.PI) / 2 * Math.pow(2, z));
    total += Math.max(0, (xMax - xMin + 1) * (yMax - yMin + 1));
  }
  return total;
});

const estimatedSize = computed(() => {
  const tiles = estimatedTiles.value;
  const avgTileSizeKb = 15;
  const mb = (tiles * avgTileSizeKb) / 1024;
  if (mb < 1) return `${Math.round(tiles * avgTileSizeKb)} KB`;
  if (mb < 1024) return `${mb.toFixed(1)} MB`;
  return `${(mb / 1024).toFixed(2)} GB`;
});

const pmtilesSourceUrl = computed(() => {
  const url = props.mapConfig?.pmtilesUrl ?? '';
  if (url.startsWith('http')) {
    const path = url.replace(/^https?:\/\/[^\/]+/, '');
    return `pmtiles://${path}`;
  }
  return `pmtiles://${url}`;
});

// Utility functions
function formatDistance(meters) {
  if (meters >= 1000) return `${(meters / 1000).toFixed(2)} km`;
  return `${Math.round(meters)} m`;
}

function formatArea(sqMeters) {
  if (sqMeters >= 1000000) return `${(sqMeters / 1000000).toFixed(2)} km²`;
  if (sqMeters >= 10000) return `${(sqMeters / 10000).toFixed(2)} ha`;
  return `${Math.round(sqMeters)} m²`;
}

function calculateDistance(coord1, coord2) {
  const R = 6371000; // Earth radius in meters
  const lat1 = coord1[1] * Math.PI / 180;
  const lat2 = coord2[1] * Math.PI / 180;
  const dLat = (coord2[1] - coord1[1]) * Math.PI / 180;
  const dLon = (coord2[0] - coord1[0]) * Math.PI / 180;
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon/2) * Math.sin(dLon/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}

function calculateArea(coords) {
  // Shoelace formula for polygon area on sphere (approximate)
  let area = 0;
  for (let i = 0; i < coords.length - 1; i++) {
    area += (coords[i+1][0] - coords[i][0]) * (2 + Math.sin(coords[i][1] * Math.PI/180) + Math.sin(coords[i+1][1] * Math.PI/180));
  }
  return Math.abs(area * 6371000 * 6371000 * Math.PI / 180 / 2);
}

function toggleLayer() {
  if (!map.value) return;
  showContour.value = !showContour.value;
  const layerId = 'garis-kontur';
  if (map.value.getLayer(layerId)) {
    map.value.setLayoutProperty(layerId, 'visibility', showContour.value ? 'visible' : 'none');
  } else {
    // Layer not ready yet, wait for map load
    if (map.value.loaded()) {
      // Map already loaded but layer not found - log error
      console.warn(`Layer ${layerId} not found on loaded map`);
    } else {
      map.value.once('load', () => {
        if (map.value?.getLayer(layerId)) {
          map.value.setLayoutProperty(layerId, 'visibility', showContour.value ? 'visible' : 'none');
        }
      });
    }
  }
}

function highlightKabupaten(idKab) {
  if (!map.value) return;
  const layerId = 'kabupaten-highlight';
  if (map.value.getLayer(layerId)) {
    if (idKab) {
      map.value.setFilter(layerId, ['==', ['get', 'id_kab'], idKab]);
    } else {
      map.value.setFilter(layerId, ['==', ['get', 'id_kab'], '']);
    }
  } else {
    // Layer not ready yet, wait for map load
    map.value.once('load', () => {
      if (map.value.getLayer(layerId)) {
        if (idKab) {
          map.value.setFilter(layerId, ['==', ['get', 'id_kab'], idKab]);
        } else {
          map.value.setFilter(layerId, ['==', ['get', 'id_kab'], '']);
        }
      }
    });
  }
}

function onKabupatenSelect() {
  const kab = selectedKabData.value;
  if (!map.value || !kab || !kab.bbox) return;

  const [west, south, east, north] = kab.bbox;
  map.value.fitBounds(
    [[west, south], [east, north]],
    { padding: 40, duration: 2000 }
  );
  highlightKabupaten(kab.id_kab);
  mapStatus.value = `Menampilkan ${kab.nama_kab} (BBox: ${kab.bbox.join(', ')})`;
}

function clearSelection() {
  selectedKabupaten.value = '';
  highlightKabupaten('');
  if (map.value) {
    map.value.fitBounds(
      [[boundingBox.value.west, boundingBox.value.south], [boundingBox.value.east, boundingBox.value.north]],
      { padding: 40, duration: 2000 }
    );
  }
  mapStatus.value = 'Peta dikembalikan ke tampilan Sulawesi Selatan.';
}

let miniMapWatch = null;

function initMiniMap() {
  if (!miniMapContainer.value || !selectedOfflineRegionData.value || !hasPmtiles.value) return;
  if (miniMap.value) {
    miniMap.value.remove();
    miniMap.value = null;
  }

  const kab = selectedOfflineRegionData.value;
  const [west, south, east, north] = kab.bbox;

  miniMapLoading.value = true;
  miniMapError.value = '';

  import('maplibre-gl').then(({ Map, addProtocol, config }) => {
    config.WORKER_COUNT = 0;
    import('pmtiles').then(({ Protocol }) => {
      const protocol = new Protocol();
      addProtocol('pmtiles', protocol.tile);

      try {
        miniMap.value = new Map({
          container: miniMapContainer.value,
          style: {
            version: 8,
            sources: {
              'kontur': { type: 'vector', url: pmtilesSourceUrl.value },
              'batas': { type: 'geojson', data: geojsonUrl.value },
            },
            layers: [
              { id: 'mini-kontur', type: 'line', source: 'kontur', 'source-layer': 'kontur',
                layout: { 'line-join': 'round', 'line-cap': 'round' },
                paint: { 'line-color': '#8c510a', 'line-width': 0.8 } },
              { id: 'mini-batas', type: 'line', source: 'batas',
                paint: { 'line-color': '#2563eb', 'line-width': 1, 'line-dasharray': [1, 1] } },
            ],
          },
          center: [(west + east) / 2, (south + north) / 2],
          zoom: 8,
          maxZoom: 14,
        });

        miniMap.value.on('load', () => {
          miniMapLoading.value = false;
          // Fit to the region's bbox for better preview
          if (miniMap.value && kab.bbox) {
            miniMap.value.fitBounds(
              [[kab.bbox[0], kab.bbox[1]], [kab.bbox[2], kab.bbox[3]]],
              { padding: 20, duration: 1000 }
            );
          }
        });

        miniMap.value.on('error', (e) => {
          miniMapLoading.value = false;
          miniMapError.value = `Gagal memuat preview: ${e.error?.message || 'Kesalahan peta'}`;
          console.error('Mini map error:', e);
        });
      } catch (error) {
        miniMapLoading.value = false;
        miniMapError.value = `Gagal inisialisasi preview: ${error.message}`;
        console.error('Mini map init error:', error);
      }
    });
  });
}

async function initMap() {
  if (!mapContainer.value || !hasPmtiles.value) return;

  try {
    const { Map, addProtocol, config, Popup, ScaleControl, NavigationControl, GeolocateControl } = await import('maplibre-gl');
    const { Protocol } = await import('pmtiles');

    // Disable workers to avoid worker loading issues on Vercel
    config.WORKER_COUNT = 0;

    const protocol = new Protocol();
    addProtocol('pmtiles', protocol.tile);

    // Create map with multiple basemap sources
    map.value = new Map({
      container: mapContainer.value,
      style: {
        version: 8,
        sources: {
          'kontur-sulsel': {
            type: 'vector',
            url: pmtilesSourceUrl.value,
          },
          'batas-kabupaten': {
            type: 'geojson',
            data: geojsonUrl.value,
          },
          'osm-tiles': {
            type: 'raster',
            tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
            tileSize: 256,
            attribution: '© OpenStreetMap',
          },
          'satellite-tiles': {
            type: 'raster',
            tiles: ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'],
            tileSize: 256,
            attribution: '© Esri',
          },
          'terrain-tiles': {
            type: 'raster',
            tiles: ['https://tile.opentopomap.org/{z}/{x}/{y}.png'],
            tileSize: 256,
            attribution: '© OpenTopoMap',
          },
          'dark-tiles': {
            type: 'raster',
            tiles: ['https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png'],
            tileSize: 256,
            attribution: '© Stadia Maps',
          },
          'hillshade-tiles': {
            type: 'raster-dem',
            tiles: ['https://tiles.opentopomap.org/{z}/{x}/{y}.png'],
            tileSize: 256,
            maxzoom: 14,
          },
        },
        layers: [
          // Base layers (only one visible at a time)
          {
            id: 'osm-layer',
            type: 'raster',
            source: 'osm-tiles',
            layout: { visibility: 'visible' },
          },
          {
            id: 'satellite-layer',
            type: 'raster',
            source: 'satellite-tiles',
            layout: { visibility: 'none' },
          },
          {
            id: 'terrain-layer',
            type: 'raster',
            source: 'terrain-tiles',
            layout: { visibility: 'none' },
          },
          {
            id: 'dark-layer',
            type: 'raster',
            source: 'dark-tiles',
            layout: { visibility: 'none' },
          },
          // Hillshade layer (optional)
          {
            id: 'hillshade-layer',
            type: 'hillshade',
            source: 'hillshade-tiles',
            layout: { visibility: showHillshade.value ? 'visible' : 'none' },
            paint: {
              'hillshade-illumination-direction': 315,
              'hillshade-illumination-anchor': 'map',
              'hillshade-exaggeration': 0.5,
              'hillshade-shadow-color': 'rgba(0, 0, 0, 0.5)',
              'hillshade-highlight-color': 'rgba(255, 255, 255, 0.5)',
              'hillshade-accent-color': 'rgba(140, 81, 10, 0.3)',
            },
          },
          // Contour lines
          {
            id: 'garis-kontur',
            type: 'line',
            source: 'kontur-sulsel',
            'source-layer': 'kontur',
            layout: {
              'line-join': 'round',
              'line-cap': 'round',
              visibility: showContour.value ? 'visible' : 'none',
            },
            paint: {
              'line-color': '#8c510a',
              'line-width': [
                'case',
                ['==', ['%', ['get', 'ELEV'], 50], 0],
                1.8,
                0.8,
              ],
              'line-opacity': 0.8,
            },
          },
          // Contour labels (index contours every 50m)
          {
            id: 'kontur-labels',
            type: 'symbol',
            source: 'kontur-sulsel',
            'source-layer': 'kontur',
            filter: ['==', ['%', ['get', 'ELEV'], 50], 0],
            layout: {
              visibility: showContourLabels.value ? 'visible' : 'none',
              'symbol-placement': 'line',
              'text-field': ['concat', ['get', 'ELEV'], ' m'],
              'text-font': ['Open Sans Regular'],
              'text-size': 10,
              'text-fill': '#8c510a',
              'text-halo-color': '#fff',
              'text-halo-width': 1.5,
              'text-halo-blur': 1,
            },
            paint: {},
          },
          // District borders
          {
            id: 'kabupaten-border',
            type: 'line',
            source: 'batas-kabupaten',
            paint: {
              'line-color': '#2563eb',
              'line-width': 1.5,
              'line-dasharray': [2, 2],
            },
          },
          // District highlight
          {
            id: 'kabupaten-highlight',
            type: 'fill',
            source: 'batas-kabupaten',
            filter: ['==', ['get', 'id_kab'], ''],
            paint: {
              'fill-color': '#3b82f6',
              'fill-opacity': 0.15,
            },
          },
          // District name labels
          {
            id: 'kabupaten-labels',
            type: 'symbol',
            source: 'batas-kabupaten',
            filter: ['==', ['get', 'id_kab'], ['get', 'id_kab']],
            layout: {
              visibility: showDistrictLabels.value ? 'visible' : 'none',
              'text-field': ['get', 'nama_kab'],
              'text-font': ['Open Sans Bold', 'Open Sans Regular'],
              'text-size': [
                'interpolate',
                ['linear'],
                ['zoom'],
                8, 10,
                12, 14,
              ],
              'text-fill': '#1f2937',
              'text-halo-color': '#fff',
              'text-halo-width': 2,
              'text-halo-blur': 1,
              'text-anchor': 'center',
              'text-allow-overlap': true,
            },
          },
        ],
      },
      center: [120.2, -3.3],
      zoom: 10,
      maxZoom: 14,
    });

    // Add scale bar
    if (showScaleBar.value) {
      const scale = new ScaleControl({ maxWidth: 200, unit: 'metric' });
      map.value.addControl(scale, 'bottom-left');
    }

    // Add north arrow (navigation control with compass)
    if (showNorthArrow.value) {
      const nav = new NavigationControl({ showCompass: true, showZoom: false });
      map.value.addControl(nav, 'top-right');
    }

    // Add geolocate control
    if ('geolocation' in navigator) {
      hasGeolocation.value = true;
      const geolocate = new GeolocateControl({
        positionOptions: { enableHighAccuracy: true },
        trackUserLocation: true,
        showAccuracyCircle: true,
      });
      map.value.addControl(geolocate, 'bottom-right');
    }

    // Mouse move - coordinate display
    map.value.on('mousemove', (e) => {
      if (showCoordinates.value) {
        mouseCoords.value = { lng: e.lngLat.lng, lat: e.lngLat.lat };
      }
    });

    map.value.on('mouseleave', () => {
      mouseCoords.value = null;
    });

    // Click - feature info (district popup)
    map.value.on('click', 'batas-kabupaten', (e) => {
      const feature = e.features[0];
      if (feature && feature.properties) {
        const props = feature.properties;
        const content = `
          <div class="p-2 min-w-[200px]">
            <h3 class="font-bold text-[#1f2937] mb-1">${props.nama_kab || 'Kabupaten'}</h3>
            <div class="text-sm text-[#6b7280]">
              <div>ID: ${props.id_kab || 'N/A'}</div>
              <div>Provinsi: ${props.nama_prov || 'Sulawesi Selatan'}</div>
            </div>
            <div class="mt-2 flex gap-2">
              <button onclick="window.dispatchEvent(new CustomEvent('map-zoom-to', {detail: ${JSON.stringify([e.lngLat.lng, e.lngLat.lat])})}))" class="text-xs bg-[#A7B92A] text-white px-2 py-1 rounded">Zoom</button>
              <button onclick="window.dispatchEvent(new CustomEvent('map-bookmark', {detail: ${JSON.stringify({name: props.nama_kab, coords: [e.lngLat.lng, e.lngLat.lat]})})}))" class="text-xs bg-[#6F9435] text-white px-2 py-1 rounded">Bookmark</button>
            </div>
          </div>
        `;
        new Popup({ closeButton: true, maxWidth: '300px' })
          .setLngLat(e.lngLat)
          .setHTML(content)
          .addTo(map.value);
      }
    });

    // Change cursor on hover
    map.value.on('mouseenter', 'batas-kabupaten', () => {
      map.value.getCanvas().style.cursor = 'pointer';
    });
    map.value.on('mouseleave', 'batas-kabupaten', () => {
      map.value.getCanvas().style.cursor = '';
    });

    // Offline/online detection
    window.addEventListener('online', () => { isOffline.value = false; });
    window.addEventListener('offline', () => { isOffline.value = true; });

    // Listen for bookmark events from popup
    window.addEventListener('map-bookmark', (e) => {
      const { name, coords } = e.detail;
      saveBookmark(name, coords);
    });

    window.addEventListener('map-zoom-to', (e) => {
      const [lng, lat] = e.detail;
      map.value.flyTo({ center: [lng, lat], zoom: 12, duration: 2000 });
    });

    map.value.on('load', () => {
      loading.value = false;
      mapError.value = null;
      mapStatus.value = 'Peta kontur Sulawesi Selatan dimuat. GeoJSON batas kabupaten aktif.';

      // Initialize elevation legend
      initElevationLegend();

      // Restore bookmarks from localStorage
      loadBookmarks();
    });

    map.value.on('error', (e) => {
      loading.value = false;
      const errorMsg = e.error?.message || 'Kesalahan peta';
      mapStatus.value = `Peringatan: ${errorMsg}`;
      if (errorMsg.includes('source') || errorMsg.includes('tile') || errorMsg.includes('network') || errorMsg.includes('404') || errorMsg.includes('500')) {
        mapError.value = `Gagal memuat data peta: ${errorMsg}. Periksa koneksi dan coba lagi.`;
      }
    });
  } catch (error) {
    loading.value = false;
    mapError.value = `Gagal memuat peta: ${error.message}`;
    console.error('Map initialization error:', error);
  }
}

// Elevation legend
function initElevationLegend() {
  if (!legendContainer.value || !showElevationLegend.value) return;
  const legend = document.createElement('div');
  legend.className = 'rounded-lg bg-[#1a1a1a]/90 border border-[#6F9435]/30 p-3 text-xs text-[#d4dc9a] min-w-[150px]';
  legend.innerHTML = `
    <div class="font-bold text-[#EDD330] mb-2">Legenda Elevasi</div>
    <div class="space-y-1">
      <div class="flex items-center gap-2"><span class="w-6 h-1.5 rounded" style="background: #8c510a;"></span> Kontur 10m</div>
      <div class="flex items-center gap-2"><span class="w-6 h-1.5 rounded" style="background: #a0522d;"></span> Kontur 50m (Index)</div>
      <div class="flex items-center gap-2"><span class="w-6 h-1.5 rounded" style="background: #cd853f;"></span> Kontur 100m</div>
      <div class="flex items-center gap-2"><span class="w-6 h-1.5 rounded" style="background: #8b4513;"></span> Kontur 500m+</div>
      <div class="flex items-center gap-2 mt-2 pt-2 border-t border-[#6F9435]/30"><span class="w-6 h-1.5 rounded" style="background: #2563eb; border: 1px dashed #2563eb;"></span> Batas Kabupaten</div>
    </div>
  `;
  legendContainer.value.appendChild(legend);
}

// Toggle hillshade
function toggleHillshade() {
  showHillshade.value = !showHillshade.value;
  if (!map.value) return;
  const layerId = 'hillshade-layer';
  if (map.value.getLayer(layerId)) {
    map.value.setLayoutProperty(layerId, 'visibility', showHillshade.value ? 'visible' : 'none');
  }
}

// Toggle district labels
function toggleDistrictLabels() {
  showDistrictLabels.value = !showDistrictLabels.value;
  if (!map.value) return;
  const layerId = 'kabupaten-labels';
  if (map.value.getLayer(layerId)) {
    map.value.setLayoutProperty(layerId, 'visibility', showDistrictLabels.value ? 'visible' : 'none');
  }
}

// Toggle contour labels
function toggleContourLabels() {
  showContourLabels.value = !showContourLabels.value;
  if (!map.value) return;
  const layerId = 'kontur-labels';
  if (map.value.getLayer(layerId)) {
    map.value.setLayoutProperty(layerId, 'visibility', showContourLabels.value ? 'visible' : 'none');
  }
}

// Change basemap
function changeBasemap() {
  if (!map.value) return;
  const layers = ['osm-layer', 'satellite-layer', 'terrain-layer', 'dark-layer'];
  layers.forEach(layer => {
    if (map.value.getLayer(layer)) {
      map.value.setLayoutProperty(layer, 'visibility', layer === `${basemap.value}-layer` ? 'visible' : 'none');
    }
  });
  mapStatus.value = `Basemap: ${basemap.value}`;
}

// Locate user
function locateUser() {
  if (!map.value || !hasGeolocation.value) return;
  locating.value = true;
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      const { longitude, latitude } = pos.coords;
      map.value.flyTo({ center: [longitude, latitude], zoom: 14, duration: 2000 });
      new (await import('maplibre-gl')).Popup()
        .setLngLat([longitude, latitude])
        .setHTML('<div class="p-2 text-[#1f2937]">Lokasi Anda</div>')
        .addTo(map.value);
      locating.value = false;
    },
    (err) => {
      locating.value = false;
      mapStatus.value = `Gagal mendapatkan lokasi: ${err.message}`;
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

// Search by place name (Nominatim)
async function searchPlace() {
  if (!searchQuery.value.trim()) return;
  searchFocused.value = false;

  // Check if query is coordinates
  const coordMatch = searchQuery.value.match(/^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/);
  if (coordMatch) {
    const lat = parseFloat(coordMatch[1]);
    const lng = parseFloat(coordMatch[2]);
    if (map.value) {
      map.value.flyTo({ center: [lng, lat], zoom: 14, duration: 2000 });
      new (await import('maplibre-gl')).Popup()
        .setLngLat([lng, lat])
        .setHTML(`<div class="p-2 text-[#1f2937]">Koordinat: ${lat.toFixed(6)}, ${lng.toFixed(6)}</div>`)
        .addTo(map.value);
      searchQuery.value = '';
      return;
    }
  }

  // Search via Nominatim
  try {
    searchResults.value = [];
    const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=5&countrycodes=id&accept-language=id`);
    const data = await response.json();
    searchResults.value = data.map(item => ({
      place_id: item.place_id,
      display_name: item.display_name,
      lat: parseFloat(item.lat),
      lon: parseFloat(item.lon),
    }));
    if (searchResults.value.length === 0) {
      mapStatus.value = 'Tidak ditemukan hasil untuk pencarian tersebut.';
    }
  } catch (error) {
    console.error('Search error:', error);
    mapStatus.value = 'Gagal mencari lokasi. Periksa koneksi internet.';
  }
}

function selectSearchResult(result) {
  if (!map.value) return;
  map.value.flyTo({ center: [result.lon, result.lat], zoom: 14, duration: 2000 });
  new (await import('maplibre-gl')).Popup()
    .setLngLat([result.lon, result.lat])
    .setHTML(`<div class="p-2 text-[#1f2937]">${result.display_name}</div>`)
    .addTo(map.value);
  searchQuery.value = '';
  searchResults.value = [];
  searchFocused.value = false;
}

// Measurement tools
function startMeasurement(mode) {
  if (!map.value) return;
  measurementMode.value = mode;
  measurementPoints.value = [];
  measurementDistance.value = 0;
  measurementArea.value = 0;

  // Create measurement source if not exists
  if (!map.value.getSource('measurement')) {
    map.value.addSource('measurement', { type: 'geojson', data: { type: 'FeatureCollection', features: [] } });
  }
  measurementSource.value = map.value.getSource('measurement');

  // Add click handler
  map.value.on('click', onMeasureClick);
  map.value.getCanvas().style.cursor = 'crosshair';
  mapStatus.value = mode === 'distance' ? 'Klik untuk menambah titik ukur jarak' : 'Klik untuk menambah titik ukur luas';
}

function onMeasureClick(e) {
  if (measurementMode.value === 'none') return;
  measurementPoints.value.push([e.lngLat.lng, e.lngLat.lat]);
  updateMeasurement();
}

function updateMeasurement() {
  if (!map.value || !measurementSource.value) return;
  const coords = measurementPoints.value;
  if (coords.length === 0) return;

  // Update line
  const lineFeature = {
    type: 'Feature',
    geometry: { type: 'LineString', coordinates: coords },
    properties: {},
  };
  measurementSource.value.setData({
    type: 'FeatureCollection',
    features: [lineFeature],
  });

  if (measurementMode.value === 'distance' && coords.length >= 2) {
    let total = 0;
    for (let i = 1; i < coords.length; i++) {
      total += calculateDistance(coords[i-1], coords[i]);
    }
    measurementDistance.value = total;
  } else if (measurementMode.value === 'area' && coords.length >= 3) {
    // Close polygon
    const closedCoords = [...coords, coords[0]];
    measurementArea.value = calculateArea(closedCoords);
  }
}

function cancelMeasurement() {
  if (!map.value) return;
  measurementMode.value = 'none';
  measurementPoints.value = [];
  measurementDistance.value = 0;
  measurementArea.value = 0;
  if (measurementSource.value) {
    measurementSource.value.setData({ type: 'FeatureCollection', features: [] });
  }
  map.value.off('click', onMeasureClick);
  map.value.getCanvas().style.cursor = '';
  mapStatus.value = 'Pengukuran dibatalkan';
}

function finishMeasurement() {
  if (!map.value) return;
  const mode = measurementMode.value;
  const dist = measurementDistance.value;
  const area = measurementArea.value;
  measurementMode.value = 'none';
  measurementPoints.value = [];
  measurementDistance.value = 0;
  measurementArea.value = 0;
  if (measurementSource.value) {
    measurementSource.value.setData({ type: 'FeatureCollection', features: [] });
  }
  map.value.off('click', onMeasureClick);
  map.value.getCanvas().style.cursor = '';
  mapStatus.value = mode === 'distance' 
    ? `Jarak: ${formatDistance(dist)}` 
    : `Luas: ${formatArea(area)}`;
}

// Bookmark functions
function saveBookmark(name = null, coords = null) {
  if (!map.value) return;
  const center = coords || [map.value.getCenter().lng, map.value.getCenter().lat];
  const zoom = map.value.getZoom();
  const bearing = map.value.getBearing();
  const pitch = map.value.getPitch();
  const basemapCurrent = basemap.value;

  const bookmarkName = name || `Bookmark ${bookmarks.value.length + 1} - ${new Date().toLocaleString('id-ID')}`;
  const bookmark = { name: bookmarkName, center, zoom, bearing, pitch, basemap: basemapCurrent, timestamp: Date.now() };
  bookmarks.value.unshift(bookmark);
  if (bookmarks.value.length > 20) bookmarks.value.pop();
  localStorage.setItem('mapBookmarks', JSON.stringify(bookmarks.value));
  mapStatus.value = `Tampilan disimpan: ${bookmarkName}`;
}

function loadBookmarks() {
  try {
    const stored = localStorage.getItem('mapBookmarks');
    if (stored) bookmarks.value = JSON.parse(stored);
  } catch {}
}

function goToBookmark(bm) {
  if (!map.value) return;
  basemap.value = bm.basemap;
  changeBasemap();
  map.value.flyTo({ center: bm.center, zoom: bm.zoom, bearing: bm.bearing, pitch: bm.pitch, duration: 2000 });
  mapStatus.value = `Berpindah ke: ${bm.name}`;
}

function deleteBookmark(index) {
  bookmarks.value.splice(index, 1);
  localStorage.setItem('mapBookmarks', JSON.stringify(bookmarks.value));
}

// Print layout
function printMap() {
  if (!map.value) return;
  // Create a print-friendly version
  const printWindow = window.open('', '_blank');
  const center = map.value.getCenter();
  const zoom = map.value.getZoom();
  const bearing = map.value.getBearing();
  const html = generatePrintHTML(center, zoom, bearing);
  printWindow.document.write(html);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
}

function generatePrintHTML(center, zoom, bearing) {
  const title = 'Peta Kontur Sulawesi Selatan';
  const date = new Date().toLocaleString('id-ID');
  const scale = Math.round(156543.03392 * Math.cos(center.lat * Math.PI / 180) / Math.pow(2, zoom));
  return `<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>${title} - Cetak</title>
<style>
  @page { margin: 1cm; size: A4 landscape; }
  body { margin: 0; font-family: Arial, sans-serif; }
  .header { text-align: center; margin-bottom: 10px; }
  .header h1 { margin: 0; font-size: 24px; color: #1f2937; }
  .header p { margin: 5px 0; font-size: 12px; color: #6b7280; }
  .map-container { position: relative; width: 100%; height: 70vh; border: 2px solid #6F9435; }
  #print-map { width: 100%; height: 100%; }
  .footer { display: flex; justify-content: space-between; margin-top: 10px; font-size: 11px; color: #6b7280; }
  .legend { display: flex; gap: 20px; flex-wrap: wrap; }
  .legend-item { display: flex; align-items: center; gap: 5px; }
  .legend-color { width: 20px; height: 3px; border-radius: 2px; }
</style></head><body>
<div class="header">
  <h1>${title}</h1>
  <p>Dicetak pada: ${date} | Koordinat tengah: ${center.lng.toFixed(6)}, ${center.lat.toFixed(6)} | Zoom: ${zoom.toFixed(1)} | Skala ~1:${scale.toLocaleString()}</p>
</div>
<div class="map-container" id="print-map"></div>
<div class="footer">
  <div>Sumber: OpenStreetMap, PMTiles Kontur Sulsel</div>
  <div class="legend">
    <div class="legend-item"><span class="legend-color" style="background:#8c510a"></span> Kontur</div>
    <div class="legend-item"><span class="legend-color" style="background:#2563eb; border:1px dashed #2563eb"></span> Batas Kabupaten</div>
  </div>
</div>
<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
<link href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" rel="stylesheet" />
<script src="https://unpkg.com/pmtiles@3.1.4/dist/pmtiles.js"></script>
<script>
  const protocol = new PMTiles.Protocol();
  maplibregl.addProtocol('pmtiles', protocol.tile);
  const map = new maplibregl.Map({
    container: 'print-map',
    style: {
      version: 8,
      sources: {
        'kontur': { type: 'vector', url: 'pmtiles:///storage/maps/sulsel_kontur.pmtiles' },
        'batas': { type: 'geojson', data: '/storage/maps/batas_kabupaten_sulsel.geojson' },
        'osm': { type: 'raster', tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'], tileSize: 256 }
      },
      layers: [
        { id: 'osm', type: 'raster', source: 'osm' },
        { id: 'kontur', type: 'line', source: 'kontur', 'source-layer': 'kontur',
          layout: { 'line-join': 'round', 'line-cap': 'round' },
          paint: { 'line-color': '#8c510a', 'line-width': ['case', ['==', ['%', ['get', 'ELEV'], 50], 0], 1.8, 0.8] }
        },
        { id: 'batas', type: 'line', source: 'batas', paint: { 'line-color': '#2563eb', 'line-width': 1.5, 'line-dasharray': [2, 2] } }
      ]
    },
    center: [${center.lng}, ${center.lat}],
    zoom: ${zoom},
    bearing: ${bearing},
    pitch: ${map.value?.getPitch() || 0},
  });
  map.once('load', () => { window.print(); });
</script>
</body></html>`;
}

async function downloadOffline() {
  if (!hasPmtiles.value) return;

  downloading.value = true;
  const areaName = downloadMode.value === 'region' && selectedOfflineRegionData.value
    ? selectedOfflineRegionData.value.nama_kab
    : 'Sulawesi Selatan';
  downloadStatus.value = `Memulai pengunduhan tile untuk ${areaName}...`;

  if (!('serviceWorker' in navigator)) {
    downloadStatus.value = 'Service Worker tidak didukung.';
    downloading.value = false;
    return;
  }

  try {
    const registration = await navigator.serviceWorker.ready;

    const bbox = currentBBox.value;
    const minZoom = offlineZoomMin.value;
    const maxZoom = offlineZoomMax.value;
    // Use relative path for Service Worker to avoid CORS issues
    const pmtilesUrl = props.mapConfig.pmtilesUrl.replace(/^https?:\/\/[^\/]+/, '');
    const geojsonUrlRelative = geojsonUrl.value.replace(/^https?:\/\/[^\/]+/, '');

    const layoutOptions = {
      scaleBar: includeScaleBar.value,
      northArrow: includeNorthArrow.value,
      legend: includeLegend.value,
      histogram: includeHistogram.value,
      grid: includeGrid.value,
    };

    const channel = new MessageChannel();
    channel.port1.onmessage = (event) => {
      const data = event.data;
      if (data.type === 'DOWNLOAD_PROGRESS') {
        downloadStatus.value = `${data.status} (${data.downloaded}/${data.total} tile)`;
      }
      if (data.type === 'DOWNLOAD_COMPLETE') {
        downloadStatus.value = `Selesai! ${data.downloaded} tile berhasil diunduh untuk offline.`;
        downloading.value = false;
      }
    };

    registration.active?.postMessage(
      {
        type: 'DOWNLOAD_OFFLINE_TILES',
        bbox: { west: bbox.west, east: bbox.east, south: bbox.south, north: bbox.north },
        zoomMin: minZoom,
        zoomMax: maxZoom,
        pmtilesUrl,
        geojsonUrl: geojsonUrlRelative,
        layoutOptions,
        areaName,
      },
      [channel.port2]
    );
  } catch (error) {
    downloadStatus.value = `Gagal mengunduh: ${error.message}`;
    downloading.value = false;
  }
}

onMounted(() => {
  if (!hasPmtiles.value) {
    loading.value = false;
    return;
  }
  initMap();
});

watch(showMiniMap, async (visible) => {
  if (visible) {
    await nextTick();
    initMiniMap();
  } else {
    if (miniMap.value) {
      miniMap.value.remove();
      miniMap.value = null;
    }
  }
});

watch(selectedOfflineRegion, () => {
  if (showMiniMap.value) {
    nextTick().then(() => initMiniMap());
  }
});

onBeforeUnmount(() => {
  if (map.value) {
    map.value.remove();
    map.value = null;
  }
  if (miniMap.value) {
    miniMap.value.remove();
    miniMap.value = null;
  }
});
</script>
