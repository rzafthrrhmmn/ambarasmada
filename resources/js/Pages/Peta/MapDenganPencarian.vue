<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Peta Kontur</p>
        <h1 class="mt-1 text-2xl font-extrabold text-[#f0ead8]">Peta Kontur Sulawesi Selatan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Cari kabupaten, lihat batas administratif, dan jelajahi kontur topografi.</p>
      </div>
      <div class="flex gap-2">
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
const map = ref(null);
const miniMap = ref(null);
const loading = ref(true);
const mapError = ref(null);
const showContour = ref(true);
const showOfflineModal = ref(false);
const downloading = ref(false);
const downloadStatus = ref('');
const mapStatus = ref('');
const offlineZoomMin = ref(8);
const offlineZoomMax = ref(14);
const selectedKabupaten = ref('');
const miniMapLoading = ref(false);
const miniMapError = ref('');

// Offline download state
const downloadMode = ref('full');
const selectedOfflineRegion = ref('');
const showMiniMap = computed(() => downloadMode.value === 'region' && !!selectedOfflineRegion.value);
const includeScaleBar = ref(true);
const includeNorthArrow = ref(true);
const includeLegend = ref(true);
const includeHistogram = ref(true);
const includeGrid = ref(false);

const hasPmtiles = computed(() => props.mapConfig?.hasPmtiles ?? false);
const boundingBox = computed(() => props.mapConfig?.boundingBox ?? { west: 118.9, east: 121.6, south: -5.8, north: -1.8 });
const kabupatens = computed(() => props.kabupatens ?? []);

const geojsonUrl = computed(() => {
  return props.mapConfig?.geojsonUrl ?? '/storage/maps/batas_kabupaten_sulsel.geojson';
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

  import('maplibre-gl').then(({ default: maplibregl }) => {
    import('pmtiles').then(({ Protocol }) => {
      const protocol = new Protocol();
      maplibregl.addProtocol('pmtiles', protocol.tile);

      try {
        miniMap.value = new maplibregl.Map({
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
    const maplibregl = (await import('maplibre-gl')).default;
    const { Protocol } = await import('pmtiles');

    const protocol = new Protocol();
    maplibregl.addProtocol('pmtiles', protocol.tile);

    map.value = new maplibregl.Map({
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
          },
        },
        layers: [
          {
            id: 'osm-layer',
            type: 'raster',
            source: 'osm-tiles',
          },
          {
            id: 'garis-kontur',
            type: 'line',
            source: 'kontur-sulsel',
            'source-layer': 'kontur',
            layout: {
              'line-join': 'round',
              'line-cap': 'round',
            },
            paint: {
              'line-color': '#8c510a',
              'line-width': [
                'case',
                ['==', ['%', ['get', 'ELEV'], 50], 0],
                1.8,
                0.8,
              ],
            },
          },
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
        ],
      },
      center: [120.2, -3.3],
      zoom: 10,
      maxZoom: 14,
    });

    map.value.on('load', () => {
      loading.value = false;
      mapError.value = null;
      mapStatus.value = 'Peta kontur Sulawesi Selatan dimuat. GeoJSON batas kabupaten aktif.';
    });

    map.value.on('error', (e) => {
      loading.value = false;
      const errorMsg = e.error?.message || 'Kesalahan peta';
      mapStatus.value = `Peringatan: ${errorMsg}`;
      // Set mapError for critical errors (like source loading failures)
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
