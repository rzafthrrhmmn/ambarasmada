<template>
  <div class="rounded-xl border border-[#6F9435] bg-[#263D26] p-3">
    <div class="mb-3 flex flex-col gap-2">
      <div class="flex gap-2">
        <input
          v-model="query"
          type="search"
          placeholder="Cari nama lokasi, mis. Balai Ambalan"
          class="flex-1 rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] placeholder:text-[#8fa06a]/60 focus:border-[#EDD330] focus:outline-none"
          @keydown.enter.prevent="search"
        />
        <button
          type="button"
          :disabled="searching"
          class="shrink-0 rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/20 hover:text-[#EDD330] disabled:opacity-50"
          @click="search"
        >
          {{ searching ? 'Mencari...' : 'Cari' }}
        </button>
      </div>

      <ul v-if="results.length" class="max-h-40 overflow-y-auto rounded-lg border border-[#6F9435] bg-[#335233]">
        <li v-for="(item, index) in results" :key="item.place_id">
          <button
            type="button"
            class="w-full px-3 py-2 text-left text-xs text-[#d4dc9a] transition hover:bg-[#6F9435]/20 hover:text-[#EDD330]"
            @click="selectResult(item)"
          >
            {{ item.display_name }}
          </button>
        </li>
      </ul>
      <p v-if="searchError" class="text-xs text-[#ef4419]">{{ searchError }}</p>
    </div>

    <div ref="mapContainer" class="h-72 w-full rounded-lg border border-[#6F9435]" />

    <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
      <button
        type="button"
        class="shrink-0 rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/20 hover:text-[#EDD330]"
        @click="useMyLocation"
      >
        Gunakan lokasi saya
      </button>

      <label class="flex-1">
        <span class="mb-1 block text-xs font-medium text-[#8fa06a]">
          Radius ({{ radius }} meter dari titik QR)
        </span>
        <input
          v-model.number="radiusProxy"
          type="range"
          min="10"
          max="500"
          step="10"
          class="w-full accent-[#A7B92A]"
        />
      </label>

      <button
        v-if="hasPoint"
        type="button"
        class="shrink-0 rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#ef4419]/20 hover:text-[#ef4419]"
        @click="clearPoint"
      >
        Hapus titik
      </button>
    </div>

    <p class="mt-2 text-xs text-[#8fa06a]">
      <template v-if="hasPoint">
        Titik QR: {{ lat }}, {{ lng }}
      </template>
      <template v-else>
        Belum ada titik lokasi. Klik peta atau cari nama lokasi untuk menetapkannya.
      </template>
    </p>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
// maplibre-gl v6 tidak mengekspor default, jadi harus named import.
// Impor lewat wrapper supaya WORKER_URL worker-nya sudah diatur.
import {
  Map as MaplibreMap,
  Marker,
  NavigationControl,
  GeolocateControl,
} from '../maplibre';
import 'maplibre-gl/dist/maplibre-gl.css';

/**
 * Pemilih lokasi untuk sesi presensi.
 *
 * Dua cara menetapkan titik QR: klik langsung di peta, atau cari nama lokasi
 * lewat Nominatim (OpenStreetMap). Pendekatan yang sama sudah dipakai di
 * Peta/MapDenganPencarian.vue, jadi tidak ada integrasi geocoder baru.
 *
 * Peta dimuat malas (v-if di pemanggil + onMounted) karena maplibre-gl cukup
 * berat dan halaman daftar sesi tidak selalu butuh peta.
 */

const props = defineProps({
  modelValue: Object,
  defaultRadius: { type: Number, default: 100 },
});

const emit = defineEmits(['update:modelValue']);

const DEFAULT_CENTER = [-5.1487, 119.4324];
const DEFAULT_ZOOM = 13;

const mapContainer = ref(null);
const query = ref('');
const results = ref([]);
const searching = ref(false);
const searchError = ref('');

let map = null;
let marker = null;
let lastSearchAt = 0;

const lat = computed(() => props.modelValue?.latitude ?? null);
const lng = computed(() => props.modelValue?.longitude ?? null);
const hasPoint = computed(() => lat.value !== null && lng.value !== null);
const radiusProxy = computed({
  get: () => props.modelValue?.radius ?? props.defaultRadius,
  set: (value) => {
    emit('update:modelValue', {
      ...(props.modelValue ?? {}),
      radius: Number(value),
    });
  },
});
const radius = computed(() => radiusProxy.value);

onMounted(() => {
  map = new MaplibreMap({
    container: mapContainer.value,
    style: {
      version: 8,
      sources: {
        osm: {
          type: 'raster',
          tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
          tileSize: 256,
          attribution: '&copy; OpenStreetMap',
        },
      },
      layers: [{ id: 'osm', type: 'raster', source: 'osm' }],
    },
    center: hasPoint.value ? [lng.value, lat.value] : DEFAULT_CENTER,
    zoom: DEFAULT_ZOOM,
  });

  map.addControl(new NavigationControl({ showCompass: false }), 'top-right');
  map.addControl(
    new GeolocateControl({
      positionOptions: { enableHighAccuracy: true },
      trackUserLocation: false,
    }),
    'top-right'
  );

  map.on('load', syncFromModel);

  map.on('click', (event) => {
    pick({ latitude: round8(event.lngLat.lat), longitude: round8(event.lngLat.lng) });
  });
});

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
});

watch(
  () => [props.modelValue?.latitude, props.modelValue?.longitude],
  () => syncFromModel()
);

watch(radius, () => drawRadius());

function round8(value) {
  return Number(Number(value).toFixed(8));
}

function pick(point) {
  emit('update:modelValue', {
    ...(props.modelValue ?? {}),
    latitude: point.latitude,
    longitude: point.longitude,
    radius: Number(radius.value),
  });
}

function clearPoint() {
  emit('update:modelValue', {
    ...(props.modelValue ?? {}),
    latitude: null,
    longitude: null,
  });
}

function syncFromModel() {
  if (!map || !map.isStyleLoaded()) return;

  const latNow = lat.value;
  const lngNow = lng.value;

  if (latNow === null || lngNow === null) {
    if (marker) {
      marker.remove();
      marker = null;
    }
    drawRadius();
    return;
  }

  const point = [lngNow, latNow];

  if (marker) {
    marker.setLngLat(point);
  } else {
    marker = new Marker({ color: '#EDD330' }).setLngLat(point).addTo(map);
  }

  drawRadius();
  map.flyTo({ center: point, zoom: Math.max(map.getZoom(), DEFAULT_ZOOM), duration: 600 });
}

function drawRadius() {
  if (!map || !map.isStyleLoaded()) return;

  const latNow = lat.value;
  const lngNow = lng.value;
  const active = latNow !== null && lngNow !== null;

  // maplibre-gl v5+ menambahå›¾å±‚ lewat addSource + addLayer dengan objek
  // biasa; tidak ada kelas Layer yang bisa diinstansiasi.
  if (map.getLayer('presensi-radius')) {
    map.removeLayer('presensi-radius');
  }

  if (map.getSource('presensi-radius-src')) {
    map.removeSource('presensi-radius-src');
  }

  if (!active) return;

  const metersPerDegree = 111320;
  const dLat = Number(radius.value) / metersPerDegree;
  const dLng = Number(radius.value) / (metersPerDegree * Math.cos((latNow * Math.PI) / 180) || 1);

  map.addSource('presensi-radius-src', {
    type: 'geojson',
    data: {
      type: 'Feature',
      properties: {},
      geometry: {
        type: 'Polygon',
        coordinates: [
          [
            [lngNow - dLng, latNow - dLat],
            [lngNow + dLng, latNow - dLat],
            [lngNow + dLng, latNow + dLat],
            [lngNow - dLng, latNow + dLat],
            [lngNow - dLng, latNow - dLat],
          ],
        ],
      },
    },
  });

  map.addLayer({
    id: 'presensi-radius',
    type: 'circle',
    source: 'presensi-radius-src',
    paint: {
      'circle-color': '#A7B92A',
      'circle-opacity': 0.15,
      'circle-stroke-color': '#A7B92A',
      'circle-stroke-width': 2,
    },
  });
}

function useMyLocation() {
  if (!navigator.geolocation) return;

  navigator.geolocation.getCurrentPosition(
    (position) => {
      pick({
        latitude: round8(position.coords.latitude),
        longitude: round8(position.coords.longitude),
      });
    },
    () => {
      searchError.value = 'Lokasi perangkat tidak dapat diakses.';
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

async function search() {
  const term = query.value.trim();
  if (term.length < 3) return;

  // Nominatim membatasi 1 permintaan per detik.
  const since = Date.now() - lastSearchAt;
  if (since < 1000) await new Promise((resolve) => setTimeout(resolve, 1000 - since));
  lastSearchAt = Date.now();

  searching.value = true;
  searchError.value = '';

  try {
    const url =
      `https://nominatim.openstreetmap.org/search?format=json&limit=5` +
      `&countrycodes=id&accept-language=id&q=${encodeURIComponent(term)}`;

    const response = await fetch(url);
    if (!response.ok) throw new Error('Pencarian lokasi gagal.');

    const data = await response.json();
    results.value = Array.isArray(data) ? data : [];

    if (results.value.length === 0) {
      searchError.value = 'Lokasi tidak ditemukan.';
    }
  } catch {
    results.value = [];

    // Browser hanya melaporkan "Failed to fetch" untuk kegagalan jaringan maupun
    // penolakan CSP, jadi pesan teknisnya tidak berguna bagi pengguna.
    searchError.value =
      navigator.onLine === false
        ? 'Anda sedang offline. Sambungkan internet untuk mencari lokasi.'
        : 'Pencarian lokasi gagal. Periksa koneksi internet Anda.';
  } finally {
    searching.value = false;
  }
}

function selectResult(item) {
  results.value = [];

  const label = item.display_name.split(',')[0].trim();
  query.value = label;

  pick({
    latitude: round8(Number(item.lat)),
    longitude: round8(Number(item.lon)),
    label,
  });

  map?.flyTo({ center: [Number(item.lon), Number(item.lat)], zoom: 16, duration: 800 });
}

defineExpose({ pick });
</script>
