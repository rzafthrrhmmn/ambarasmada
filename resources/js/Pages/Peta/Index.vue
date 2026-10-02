<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Peta Kontur</p>
        <h1 class="mt-1 text-2xl font-extrabold text-[#f0ead8]">Peta Kontur Sulawesi</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Visualisasi garis kontur elevasi Pulau Sulawesi dengan dukungan offline.</p>
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
      </div>
    </div>

    <div class="relative overflow-hidden rounded-2xl border-2 border-[#A7B92A]/40 bg-[#263D26] shadow-lg">
      <div ref="mapContainer" class="h-[70vh] w-full min-h-[400px]"></div>

      <!-- Coordinate display on mouse hover -->
      <div v-if="showCoordinates && mouseCoords" class="absolute bottom-4 left-4 z-20 rounded-lg bg-[#1a1a1a]/90 border border-[#6F9435]/30 px-3 py-1.5 text-xs font-mono text-[#EDD330] pointer-events-none">
        Lon: {{ mouseCoords.lng.toFixed(6) }}° | Lat: {{ mouseCoords.lat.toFixed(6) }}°
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

      <!-- Print button -->
      <button
        @click="printMap"
        class="absolute top-4 right-52 z-20 rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm font-bold text-[#EDD330] transition hover:bg-[#6F9435]/30"
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
import { getActiveServiceWorker } from '@/ServiceWorker.js';

const props = defineProps({
  mapConfig: Object,
});

const mapContainer = ref(null);
const map = ref(null);
const loading = ref(true);
const mapError = ref(null);
const showContour = ref(true);
const showHillshade = ref(false);
const showOfflineModal = ref(false);
const downloading = ref(false);
const downloadStatus = ref('');
const mapStatus = ref('');
const offlineZoomMin = ref(8);
const offlineZoomMax = ref(15);

// Map controls state
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

// Offline detection
const isOffline = ref(!navigator.onLine);

const hasPmtiles = computed(() => props.mapConfig?.hasPmtiles ?? false);
const boundingBox = computed(() => props.mapConfig?.boundingBox ?? { west: 118.5, east: 125.5, south: -6.0, north: 2.0 });

const geojsonUrl = computed(() => {
  const url = props.mapConfig?.geojsonUrl ?? '/storage/maps/batas_kabupaten_sulsel.geojson';
  if (url.startsWith('http')) {
    return url.replace(/^https?:\/\/[^\/]+/, '');
  }
  return url;
});

// Arsip PMTiles di-host di Supabase Storage, jadi URL-nya harus tetap absolut.
// Pustaka pmtiles membaca byte arsip langsung dari host tersebut lewat HTTP
// Range; membuang host membuat permintaan Range mendarat di origin sendiri.
const pmtilesSourceUrl = computed(() => `pmtiles://${props.mapConfig?.pmtilesUrl ?? ''}`);

function toggleLayer() {
  if (!map.value) return;
  showContour.value = !showContour.value;
  const layerId = 'garis-kontur';
  if (map.value.getLayer(layerId)) {
    map.value.setLayoutProperty(layerId, 'visibility', showContour.value ? 'visible' : 'none');
  }
}

function toggleHillshade() {
  showHillshade.value = !showHillshade.value;
  if (!map.value) return;
  const layerId = 'hillshade-layer';
  if (map.value.getLayer(layerId)) {
    map.value.setLayoutProperty(layerId, 'visibility', showHillshade.value ? 'visible' : 'none');
  }
}

async function initMap() {
  if (!mapContainer.value || !hasPmtiles.value) return;

  mapError.value = null;
  loading.value = true;

  try {
    const { Map, addProtocol, ScaleControl, NavigationControl, GeolocateControl, GLYPHS_URL } = await import('../../maplibre');
    const { Protocol } = await import('pmtiles');

    const protocol = new Protocol();
    addProtocol('pmtiles', protocol.tile);

    map.value = new Map({
      container: mapContainer.value,
      style: {
        version: 8,
        // Layer symbol memakai text-field, jadi style wajib punya glyphs.
        // Tanpa itu MapLibre gagal menyusun shader teks: error-nya uncaught
        // dan render loop berhenti sehingga kanvas tetap abu-abu.
        glyphs: GLYPHS_URL,
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
            // Host jamak (tiles.opentopomap.org) menyajikan sertifikat TLS yang
            // tidak cocok dengan nama hostnya, sehingga browser menolak
            // koneksi dan layer hillshade tidak pernah punya data. Terrarium
            // di S3 menyajikan DEM yang sama dengan sertifikat yang sah.
            tiles: ['https://s3.amazonaws.com/elevation-tiles-prod/terrarium/{z}/{x}/{y}.png'],
            encoding: 'terrarium',
            tileSize: 256,
            maxzoom: 14,
          },
        },
        layers: [
          {
            id: 'osm-base',
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
          {
            id: 'garis-kontur',
            type: 'line',
            source: 'kontur-sulawesi',
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

    // Add scale bar
    const scale = new ScaleControl({ maxWidth: 200, unit: 'metric' });
    map.value.addControl(scale, 'bottom-left');

    // Add north arrow
    const nav = new NavigationControl({ showCompass: true, showZoom: false });
    map.value.addControl(nav, 'top-right');

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

    // Offline/online detection
    window.addEventListener('online', () => { isOffline.value = false; });
    window.addEventListener('offline', () => { isOffline.value = true; });

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

function changeBasemap() {
  if (!map.value) return;
  const layers = ['osm-base', 'satellite-layer', 'terrain-layer', 'dark-layer'];
  layers.forEach(layer => {
    if (map.value.getLayer(layer)) {
      map.value.setLayoutProperty(layer, 'visibility', layer === `${basemap.value}-layer` || (layer === 'osm-base' && basemap.value === 'osm') ? 'visible' : 'none');
    }
  });
  mapStatus.value = `Basemap: ${basemap.value}`;
}

function locateUser() {
  if (!map.value || !hasGeolocation.value) return;
  locating.value = true;
  navigator.geolocation.getCurrentPosition(
    async (pos) => {
      const { longitude, latitude } = pos.coords;
      map.value.flyTo({ center: [longitude, latitude], zoom: 14, duration: 2000 });
      const { Popup } = await import('../../maplibre');
      new Popup()
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

function printMap() {
  if (!map.value) return;
  const printWindow = window.open('', '_blank');
  const center = map.value.getCenter();
  const zoom = map.value.getZoom();
  const bearing = map.value.getBearing();
  const scale = Math.round(156543.03392 * Math.cos(center.lat * Math.PI / 180) / Math.pow(2, zoom));
  const date = new Date().toLocaleString('id-ID');
  const html = `<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Peta Kontur Sulawesi - Cetak</title>
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
  <h1>Peta Kontur Sulawesi</h1>
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
&lt;script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"&gt;&lt;/script&gt;
<link href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" rel="stylesheet" />
&lt;script src="https://unpkg.com/pmtiles/dist/pmtiles.js"&gt;&lt;/script&gt;
&lt;script&gt;
  // Build pmtiles dari unpkg hanya tersedia tanpa nomor versi, dan itu IIFE
  // yang mengekspos global 'pmtiles' huruf kecil. new PMTiles.Protocol() akan
  // ReferenceError karena PMTiles tidak terdefinisi.
  const protocol = new pmtiles.Protocol();
  maplibregl.addProtocol('pmtiles', protocol.tile);
  const map = new maplibregl.Map({
    container: 'print-map',
    style: {
      version: 8,
      sources: {
        'kontur': { type: 'vector', url: '${pmtilesSourceUrl.value}' },
        'batas': { type: 'geojson', data: '${url}' },
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
</body></html>`;
  printWindow.document.write(html);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
}

async function downloadOffline() {
  if (!hasPmtiles.value) return;

  downloading.value = true;
  downloadStatus.value = 'Memulai pengunduhan tile untuk area Sulawesi...';

  if (!('serviceWorker' in navigator)) {
    downloadStatus.value = 'Service Worker tidak didukung browser ini.';
    downloading.value = false;
    return;
  }

  try {
    const worker = await getActiveServiceWorker();

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

    worker.postMessage(
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

