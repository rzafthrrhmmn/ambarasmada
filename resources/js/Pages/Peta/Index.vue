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

    <div class="peta-shell relative overflow-hidden rounded-2xl border-2 border-[#A7B92A]/40 bg-[#263D26] shadow-lg">
      <div ref="mapContainer" class="h-[70vh] w-full min-h-[400px]"></div>

      <!-- Coordinate display on mouse hover.
           Diletakkan di atas bilah skala bawaan MapLibre di pojok kiri bawah.
           Kedua-duanya pernah memakai bottom-4 left-4 sehingga readout
           menutupi angka skala tepat di atasnya. Lebarnya dibatasi supaya
           tidak menabrak tumpukan pojok kanan bawah pada layar sempit. -->
      <div
        v-if="showCoordinates && mouseCoords"
        class="peta-panel pointer-events-none absolute bottom-12 left-3 max-w-[min(260px,calc(100%-13rem))] rounded-lg bg-[#1a1a1a]/90 border border-[#6F9435]/30 px-3 py-1.5 text-xs font-mono text-[#EDD330] shadow-lg backdrop-blur-sm"
      >
        Lon: {{ mouseCoords.lng.toFixed(6) }}° | Lat: {{ mouseCoords.lat.toFixed(6) }}°
      </div>

      <!-- Alat pojok kanan bawah: tombol lokasi saya.
           Legenda elevasi juga memakai pojok ini, jadi keduanya ditumpuk dalam
           satu wadah flex column. Sebelumnya legenda dipatok right-4 dan tombol
           GPS right-14;.right-14 = 56 piksel, sedangkan lebarnya lebih dari
           150 piksel, sehingga tombol GPS berdiri tepat di atas legenda. -->
      <div class="absolute bottom-3 right-3 z-20 flex flex-col items-end gap-2">
        <div
          v-if="hasGeolocation"
          class="rounded-lg border-2 border-[#6F9435]/50 bg-[#263D26]/95 px-3 py-2 text-sm font-bold text-[#EDD330] shadow-lg backdrop-blur-sm transition hover:bg-[#335233]/95 disabled:opacity-50"
        >
          <button
            type="button"
            @click="locateUser"
            :disabled="locating"
            class="inline-flex items-center gap-1.5 disabled:cursor-not-allowed"
            title="Lokasi saya"
          >
            <NavIcon v-if="locating" name="spinner" class="h-4 w-4 animate-spin" />
            <NavIcon v-else name="locate" class="h-4 w-4" />
            <span class="text-xs">{{ locating ? 'Mencari...' : 'Lokasi saya' }}</span>
          </button>
        </div>
      </div>

      <!-- Lapisan atas: lencana offline, panel kiri, dan alat kanan atas.
           Ketiganya hidup di satu wadah flex yang boleh membungkus baris.
           Sebelumnya panel kiri dan alat kanan atas dipatok ke pojok yang
           masing-masing, sehingga pada layar sempit keduanya saling menimpa.
           pr-12 menyisakan ruang untuk NavigationControl di pojok kanan atas. -->
      <div class="peta-panel absolute inset-x-3 top-3 flex flex-wrap items-start justify-between gap-2 pr-12">
        <div
          v-if="isOffline"
          class="inline-flex items-center gap-1.5 rounded-lg bg-[#f59e0b]/90 px-3 py-1.5 text-xs font-bold text-white shadow-lg"
          data-nama="LencanaOffline"
        >
          <NavIcon name="offline" class="h-4 w-4" />
          Mode Offline
        </div>

        <div class="flex flex-wrap items-start justify-end gap-2" data-nama="AlatKananAtas">
          <label class="sr-only" for="peta-basemap">Pilih basemap</label>
          <select
            id="peta-basemap"
            v-model="basemap"
            @change="changeBasemap"
            class="w-[168px] rounded-lg border-2 border-[#6F9435]/60 bg-[#263D26]/95 px-3 py-2 text-sm text-[#f0ead8] shadow-lg backdrop-blur-sm outline-none focus:border-[#EDD330]"
          >
            <!-- Opsi basemap memakai teks saja: elemen <option> hanya bisa memuat teks,
               jadi SVG di dalamnya tidak akan dirender. -->
            <option value="osm">OpenStreetMap</option>
            <option value="satellite">Satelit</option>
            <option value="terrain">Terrain</option>
            <option value="dark">Gelap</option>
          </select>

          <button
            type="button"
            @click="printMap"
            class="inline-flex items-center gap-1.5 rounded-lg border-2 border-[#6F9435]/60 bg-[#263D26]/95 px-3 py-2 text-sm font-bold text-[#EDD330] shadow-lg backdrop-blur-sm transition hover:bg-[#335233]/95"
            title="Cetak peta"
          >
            <NavIcon name="printer" class="h-4 w-4" />
            <span class="text-xs">Cetak</span>
          </button>
        </div>
      </div>

      <div v-if="!hasPmtiles" class="absolute inset-0 z-30 flex items-center justify-center bg-[#263D26]/90 backdrop-blur-sm">
        <div class="text-center p-6">
          <p class="text-2xl font-bold text-[#EDD330]">Peta Belum Tersedia</p>
          <p class="mt-2 text-sm text-[#8fa06a]">File PMTiles belum diunggah ke server.</p>
        </div>
      </div>
      <div v-if="loading" class="absolute inset-0 z-30 flex items-center justify-center bg-[#263D26]/70 backdrop-blur-sm">
        <p class="text-lg font-bold text-[#EDD330]">Memuat peta...</p>
      </div>
      <div v-if="mapError" class="absolute inset-0 z-30 flex items-center justify-center bg-[#263D26]/90 backdrop-blur-sm">
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
        <input v-model.number="offlineZoomMin" type="range" :min="zoomBounds.min" :max="zoomBounds.max" class="mt-1 w-full" />
        <p class="text-xs text-[#8fa06a]">Zoom: {{ offlineZoomMin }}</p>
      </div>
      <div>
        <label class="block text-xs font-medium text-[#d4dc9a]">Zoom Max</label>
        <input v-model.number="offlineZoomMax" type="range" :min="zoomBounds.min" :max="zoomBounds.max" class="mt-1 w-full" />
        <p class="text-xs text-[#8fa06a]">Zoom: {{ offlineZoomMax }}</p>
      </div>
      <div class="rounded-lg bg-[#263D26] p-3 text-xs text-[#8fa06a]">
        <p>Area: Bounding Box Sulawesi</p>
        <p>Barat: {{ boundingBox.west }} | Timur: {{ boundingBox.east }}</p>
        <p>Selatan: {{ boundingBox.south }} | Utara: {{ boundingBox.north }}</p>
        <p v-if="archiveZoom" class="mt-1">
          Arsip peta memuat zoom {{ archiveZoom.min }}&ndash;{{ archiveZoom.max }}.
        </p>
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
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import NavIcon from '@/Components/NavIcon.vue';
import { getActiveServiceWorker, SW_PROTOCOL } from '@/ServiceWorker.js';
import { useMapPngExport, scaleLabel } from '@/Composables/useMapPngExport.js';
import { BASEMAPS, BASEMAP_LABELS, PRINT_BASEMAP, basemapAttribution } from '@/basemaps.js';

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
const offlineZoomMax = ref(12);

const { isExporting, exportError, exportMapPng } = useMapPngExport();

/**
 * Cetak peta yang sedang tampil sebagai PNG.
 *
 * Hasilnya bukan screenshot polos: peta, kompas, dan skala digambar di atas
 * kanvas yang sama dengan uraian komponen peta kontur, supaya berkas tunggal
 * itu bisa langsung dipakai sebagai bahan ajar.
 */
async function printMapPng({ silent = false } = {}) {
  const result = await exportMapPng(map.value, {
    title: 'Peta Kontur Sulawesi',
    sources: sourceCredit.value,
  });

  if (result.ok) {
    // Peta kosong harus diberitahukan, bukan disembunyikan di balik
    // "PNG tersimpan": yang tersimpan hanya bahan ajar, bukan peta.
    if (result.mapMissing) {
      mapStatus.value = `PNG peta tersimpan otomatis TANPA area peta. ${result.mapProblem ?? ''}`.trim();
    } else if (result.blocked) {
      mapStatus.value = 'PNG tersimpan, tetapi area peta tidak ikut karena browser memblokir pembacaan tile.';
    } else {
      mapStatus.value = 'PNG peta tersimpan otomatis.';
    }

    if (!silent && result.mapMissing) {
      console.warn('[peta] Area peta tidak masuk PNG:', result.mapProblem ?? 'kanvas peta kosong');
    }

    if (!silent && result.blocked) {
      console.warn(
        '[peta] Area peta tidak masuk PNG. Tile dari luar biasanya perlu header CORS;'
          + ' kalau ini terjadi, ganti basemap atau pakai tombol Cetak Peta.',
      );
    }
  } else {
    mapStatus.value = exportError.value ?? 'Gagal membuat PNG peta.';
  }

  return result;
}

// Rentang zoom yang benar-benar ada di arsip PMTiles. Arsip produksi hanya
// memuat z8-z12, jadi slider offering sampai z15 membuat estimasi jauh lebih
// besar dari tile yang benar-benar bisa diambil.
const archiveZoom = ref(null);

const zoomBounds = computed(() => ({
  min: archiveZoom.value?.min ?? 8,
  max: archiveZoom.value?.max ?? 15,
}));

async function loadArchiveZoomRange() {
  if (archiveZoom.value || !hasPmtiles.value || !props.mapConfig?.pmtilesUrl) return;

  try {
    const { PMTiles, FetchSource } = await import('pmtiles');
    const header = await new PMTiles(new FetchSource(props.mapConfig.pmtilesUrl)).getHeader();

    if (header?.minZoom != null && header?.maxZoom != null) {
      archiveZoom.value = { min: header.minZoom, max: header.maxZoom };
      offlineZoomMin.value = Math.min(Math.max(offlineZoomMin.value, header.minZoom), header.maxZoom);
      offlineZoomMax.value = Math.min(Math.max(offlineZoomMax.value, header.minZoom), header.maxZoom);
    }
  } catch {
    // Header tidak terbaca: biarkan rentang slider, service worker yang memotong.
  }
}

watch(showOfflineModal, (open) => {
  if (open) loadArchiveZoomRange();
});

// Map controls state
const showCoordinates = ref(true);
const mouseCoords = ref(null);
const hasGeolocation = ref(false);
const locating = ref(false);

// Basemap
const basemap = ref('osm');

/**
 * Kredit sumber untuk PNG hasil ekspor.
 *
 * Ekspor PNG mengambil salinan kanvas WebGL, sedangkan atribusi MapLibre
 * digambar sebagai elemen DOM di atas kanvas itu. Elemen DOM tidak ikut
 * terbaca, jadi PNG harus menulis kreditnya sendiri, dan kredit itu harus
 * mengikuti basemap yang sedang terlihat.
 */
const sourceCredit = computed(() => `Sumber: ${basemapAttribution(basemap.value)}, PMTiles Kontur Sulsel`);

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
    const { Map, addProtocol, ScaleControl, NavigationControl, GLYPHS_URL } = await import('../../maplibre');
    const { Protocol } = await import('pmtiles');

    const protocol = new Protocol();
    addProtocol('pmtiles', protocol.tile);

    map.value = new Map({
      container: mapContainer.value,
      // Wajib agar kanvas peta bisa dibaca sebagai gambar saat diekspor jadi
      // PNG. Tanpa ini browser boleh membuang buffer WebGL tepat setelah frame
      // selesai digambar, sehingga toDataURL() mengembalikan kanvas kosong.
      // Ada sedikit biaya performa, jadi hanya peta yang benar-benar butuh
      // ekspor yang mengaktifkannya.
      preserveDrawingBuffer: true,
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
          // Keempat basemap disalin dari definisi tunggal supaya teks
          // atribusi lisensinya tidak bisa berbeda dari halaman peta utama.
          'osm-tiles': BASEMAPS.osm,
          'satellite-tiles': BASEMAPS.satellite,
          'terrain-tiles': BASEMAPS.terrain,
          'dark-tiles': BASEMAPS.dark,
          'hillshade-tiles': {
            type: 'raster-dem',
            // DEM diambil dari Terrarium di S3, bukan dari host OpenTopoMap.
            // Bentuk jamak tiles.opentopomap.org menyajikan sertifikat TLS yang
            // tidak cocok dengan nama hostnya sehingga browser menolak koneksi
            // dan layer hillshade tidak pernah punya data; bentuk tunggalnya
            // hanya menyajikan gambar raster, bukan DEM. Terrarium di S3
            // menyajikan DEM SRTM yang sama dengan sertifikat yang sah.
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

    // GeolocateControl bawaan MapLibre sengaja tidak ditambahkan. Tombol lokasi
    // sendiri sudah ada di pojok kanan bawah, dan memakai locateUser() yang juga
    // menulis alasannya ke mapStatus. Kalau keduanya dipasang, pojok itu berisi
    // dua tombol lokasi yang saling menutupi.
    if ('geolocation' in navigator) {
      hasGeolocation.value = true;
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

    // Overlay "Memuat peta..." tidak boleh menggantung selamanya, tapi juga tidak
    // boleh hilang sebelum kontennya benar-benar ada. Melepasnya dari
    // `styledata` keliru karena event itu fire paling awal: overlay hilang
    // seketika lalu garis kontur terlihat macet selama tile-nya masih turun.
    // Penanda loading kini dilepas setelah peta selesai (load atau idle), atau
    // lewat batas waktu kalau ada satu sumber yang menggantung.
    const clearLoading = () => {
      loading.value = false;
    };

    map.value.on('idle', clearLoading);

    setTimeout(() => {
      if (loading.value) {
        clearLoading();
        mapStatus.value = 'Peta dimuat sebagian: sebagian layer belum selesai masuk dan masih dimuat di latar belakang.';
      }
    }, 12000);

    map.value.on('load', () => {
      clearLoading();
      mapError.value = null;
      mapStatus.value = 'Peta kontur Sulawesi dimuat. Garis kontur setiap 10 meter elevasi.';
    });

    map.value.on('error', (e) => {
      clearLoading();
      const errorMsg = e.error?.message || 'Kesalahan peta';
      mapStatus.value = `Peringatan: ${errorMsg}`;
      // Error peta harus tetap terlihat di console. Kalau hanya ditulis ke
      // refs, masalah seperti style ditolak MapLibre (peta kosong tanpa satu
      // pun request tile) tidak punya jejak sama sekali saat debugging.
      console.error('Map error:', e.error || errorMsg);
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

/**
 * Escape teks sebelum masuk ke HTML cetakan.
 *
 * Atribusi basemap dan nama wilayah berasal dari data, bukan dari string tetap
 * di dalam template. Tanpa escaping, satu karakter `<` saja sudah cukup untuk
 * menutup tag dan mengubah isi cetakan.
 */
function escapeHtml(text) {
  return String(text ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  })[c]);
}

function printMap() {
  if (!map.value) return;
  const printWindow = window.open('', '_blank');
  const center = map.value.getCenter();
  const zoom = map.value.getZoom();
  const bearing = map.value.getBearing();
  // Skala ditulis 1:N, dengan pembulatan dan format yang sama dipakai readout di
  // layar dan subjudul PNG. Rumus meter per piksel pernah dipakai di sini,
  // sehingga yang muncul "Skala ~1:76": angka 76 itu meter per piksel, bukan
  // pembilang rasio, sehingga yang tertulis bukan skala peta sama sekali.
  const skalaLabel = scaleLabel(center.lat, zoom);
  const date = new Date().toLocaleString('id-ID');
  // URL batas kabupaten wajib dideklarasikan di sini. Versi lama menulis
  // ${url} di dalam template tanpa pernah menyatakannya, sehingga setiap
  // penekanan tombol Cetak melempar ReferenceError sebelum HTML-nya sempat
  // ditulis ke jendela cetak. Atribusi basemap ikut diambil dari satu definisi
  // supaya kredit di kaki cetakan sama dengan yang tampil di layar.
  // Tag script di dalam template di bawah ditulis apa adanya, hanya penutupnya
  // yang di-escape memakai garis miring. Versi lama menulis tag script dalam
  // bentuk entitas HTML, dan browser memperlakukan entitas itu sebagai teks
  // biasa, bukan tag: parser HTML tidak mengurai ulang isi teks menjadi markup.
  // Akibatnya seluruh JavaScript cetakan tampil sebagai teks di atas kertas dan
  // kotak peta terisi kosong.
  const url = geojsonUrl.value;
  const sumber = escapeHtml(`Sumber: ${basemapAttribution('osm')}, PMTiles Kontur Sulsel`);
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
  <p>Dicetak pada: ${date} | Koordinat tengah: ${center.lng.toFixed(6)}, ${center.lat.toFixed(6)} | Zoom: ${zoom.toFixed(1)} | Skala ${skalaLabel}</p>
</div>
<div class="map-container" id="print-map"></div>
<div class="footer">
  <div>${sumber}</div>
  <div class="legend">
    <div class="legend-item"><span class="legend-color" style="background:#8c510a"></span> Kontur</div>
    <div class="legend-item"><span class="legend-color" style="background:#2563eb; border:1px dashed #2563eb"></span> Batas Kabupaten</div>
  </div>
</div>
<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"><\/script>
<link href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" rel="stylesheet" />
<script src="https://unpkg.com/pmtiles/dist/pmtiles.js"><\/script>
<script>
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
        'osm': ${JSON.stringify(PRINT_BASEMAP)}
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
<\/script>
</body></html>`;
  printWindow.document.write(html);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
}

async function downloadOffline() {
  if (!hasPmtiles.value) return;

  downloading.value = true;
  downloadStatus.value = 'Menyiapkan PNG peta...';

  // PNG dicetak lebih dulu, sebelum paket tile diambil. Urutan ini menentukan
  // isi berkasnya: snapshot PNG diambil dari kanvas peta yang sedang tampil, jadi
  // kalau PNG ditunggu sampai tile selesai, yang masuk ke berkas adalah viewport
  // terakhir, bukan area Sulawesi yang diunduh.
  const png = await printMapPng({ silent: true });

  if (!('serviceWorker' in navigator)) {
    downloadStatus.value = 'Service Worker tidak didukung browser ini, jadi paket tile offline tidak bisa dibuat.';
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

    // PMTiles di-host di Supabase Storage, jadi service worker harus memakai
    // URL absolut agar HTTP Range langsung dibaca dari host arsip. GeoJSON
    // masih dilayani dari origin sendiri sehingga boleh dibuat relatif.
    const pmtilesUrl = props.mapConfig.pmtilesUrl;
    const geojsonUrlRelative = geojsonUrl.value.replace(/^https?:\/\/[^\/]+/, '');

    downloadStatus.value = png.ok
      ? 'PNG peta tersimpan. Mengunduh paket tile untuk offline...'
      : 'PNG gagal dibuat. Mengunduh paket tile untuk offline...';

    const channel = new MessageChannel();
    channel.port1.onmessage = (event) => {
      const data = event.data;

      // Service worker yang aktif bisa saja versi lama: browser memakai
      // salinan yang sedang berjalan sampai install dan activate selesai, dan
      // itu bisa memakan waktu setelah deploy. Balasan versi lama tidak
      // membawa zoomMin/zoomMax sehingga unduhan yang gagal tampil sebagai
      // "0 tile (zoom undefined-undefined)". Tolak lebih dulu, lalu suruh
      // pengguna memuat ulang.
      if (data.protocol !== SW_PROTOCOL) {
        downloadStatus.value =
          'Service Worker di perangkat ini masih versi lama. Muat ulang halaman (Ctrl+Shift+R), lalu ulangi unduhan.';
        downloading.value = false;
        return;
      }

      if (data.type === 'DOWNLOAD_PROGRESS') {
        downloadStatus.value = `${data.status} (${data.downloaded}/${data.total} tile)`;
      }
      if (data.type === 'DOWNLOAD_COMPLETE') {
        const skipped = data.skipped ? `, ${data.skipped} tile tidak tersedia di arsip` : '';
        downloadStatus.value =
          `Selesai! ${data.downloaded} tile berhasil diunduh untuk offline (zoom ${data.zoomMin}-${data.zoomMax}${skipped}).`
          + (png.ok ? ' PNG peta tersimpan.' : ' PNG peta gagal dibuat.');
        downloading.value = false;
      }
      if (data.type === 'DOWNLOAD_ERROR') {
        downloadStatus.value = data.error;
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
        protocol: SW_PROTOCOL,
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

