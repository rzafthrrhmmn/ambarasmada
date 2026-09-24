<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Peta Kontur</p>
        <h1 class="mt-1 text-2xl font-extrabold text-[#f0ead8]">Peta Kontur Sulawesi</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Visualisasi garis kontur elevasi Pulau Sulawesi dengan dukungan offline.</p>
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
      <div>
        <label class="block text-xs font-medium text-[#d4dc9a]">Zoom Min</label>
        <input v-model.number="offlineZoomMin" type="range" min="8" max="15" class="mt-1 w-full" />
        <p class="text-xs text-[#8fa06a]">Zoom: {{ offlineZoomMin }}</p>
      </div>
      <div>
        <label class="block text-xs font-medium text-[#d4dc9a]">Zoom Max</label>
        <input v-model.number="offlineZoomMax" type="range" min="8" max="15" class="mt-1 w-full" />
        <p class="text-xs text-[#8fa06a]">Zoom: {{ offlineZoomMax }}</p>
      </div>
      <div class="rounded-lg bg-[#263D26] p-3 text-xs text-[#8fa06a]">
        <p>Area: Bounding Box Sulawesi</p>
        <p>Barat: {{ boundingBox.west }} | Timur: {{ boundingBox.east }}</p>
        <p>Selatan: {{ boundingBox.south }} | Utara: {{ boundingBox.north }}</p>
      </div>
      <div v-if="downloadStatus" class="rounded-lg border border-[#6F9435]/30 bg-[#263D26] p-3 text-xs text-[#d4dc9a]">
        {{ downloadStatus }}
      </div>
      <button
        @click="downloadOffline"
        :disabled="downloading"
        class="w-full rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
      >
        {{ downloading ? 'Mengunduh...' : 'Mulai Unduh' }}
      </button>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
  mapConfig: Object,
});

const mapContainer = ref(null);
const map = ref(null);
const loading = ref(true);
const mapError = ref(null);
const showContour = ref(true);
const showOfflineModal = ref(false);
const downloading = ref(false);
const downloadStatus = ref('');
const mapStatus = ref('');
const offlineZoomMin = ref(8);
const offlineZoomMax = ref(15);

const hasPmtiles = computed(() => props.mapConfig?.hasPmtiles ?? false);
const boundingBox = computed(() => props.mapConfig?.boundingBox ?? { west: 118.5, east: 125.5, south: -6.0, north: 2.0 });

const geojsonUrl = computed(() => {
  return props.mapConfig?.geojsonUrl ?? '/storage/maps/batas_kabupaten_sulsel.geojson';
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
  }
}

async function initMap() {
  if (!mapContainer.value || !hasPmtiles.value) return;

  mapError.value = null;
  loading.value = true;

  try {
    const maplibregl = (await import('maplibre-gl')).default;
    const pmtilesModule = (await import('pmtiles')).default;

    const protocol = new pmtilesModule.Protocol();
    maplibregl.addProtocol('pmtiles', protocol.tile);

    map.value = new maplibregl.Map({
      container: mapContainer.value,
      style: {
        version: 8,
        sources: {
          'kontur-sulawesi': {
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
            id: 'osm-base',
            type: 'raster',
            source: 'osm-tiles',
          },
          {
            id: 'garis-kontur',
            type: 'line',
            source: 'kontur-sulawesi',
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
        ],
      },
      center: props.mapConfig.center ?? [119.863, -0.900],
      zoom: props.mapConfig.zoom ?? 10,
      maxZoom: 15,
    });

    map.value.on('load', () => {
      loading.value = false;
      mapError.value = null;
      mapStatus.value = 'Peta kontur Sulawesi dimuat. Garis kontur setiap 10 meter elevasi.';
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

async function downloadOffline() {
  if (!hasPmtiles.value) return;

  downloading.value = true;
  downloadStatus.value = 'Memulai pengunduhan tile untuk area Sulawesi...';

  if (!('serviceWorker' in navigator)) {
    downloadStatus.value = 'Service Worker tidak didukung.';
    downloading.value = false;
    return;
  }

  try {
    const registration = await navigator.serviceWorker.ready;

    const west = boundingBox.value.west;
    const east = boundingBox.value.east;
    const south = boundingBox.value.south;
    const north = boundingBox.value.north;
    const minZoom = offlineZoomMin.value;
    const maxZoom = offlineZoomMax.value;

    const pmtilesUrl = props.mapConfig.pmtilesUrl.replace(/^https?:\/\/[^\/]+/, '');
    const geojsonUrlRelative = geojsonUrl.value.replace(/^https?:\/\/[^\/]+/, '');

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
        bbox: { west, east, south, north },
        zoomMin: minZoom,
        zoomMax: maxZoom,
        pmtilesUrl,
        geojsonUrl: geojsonUrlRelative,
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

onBeforeUnmount(() => {
  if (map.value) {
    map.value.remove();
    map.value = null;
  }
});
</script>

