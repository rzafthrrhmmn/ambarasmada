<template>
  <Modal title="Unduh Peta Offline" @close="emit('close')">
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
              :checked="downloadMode === 'full'"
              @change="emit('update:download-mode', 'full')"
              class="sr-only peer"
            />
            <div
              :class="[
                'relative rounded-lg border-2 p-4 text-center transition-all',
                downloadMode === 'full'
                  ? 'border-daun-400 bg-daun-400/10 ring-2 ring-daun-400/20'
                  : 'border-[#6F9435] bg-[#263D26]',
              ]"
            >
              <div class="mx-auto mb-1 w-fit text-[#EDD330]">
                <NavIcon name="map" class="h-6 w-6" />
              </div>
              <div class="mt-1 text-sm font-semibold text-[#f0ead8]">Seluruh Peta</div>
              <div class="mt-1 text-xs text-[#8fa06a]">Sulawesi Selatan lengkap</div>
            </div>
          </label>
          <label class="relative cursor-pointer">
            <input
              type="radio"
              name="downloadMode"
              value="region"
              :checked="downloadMode === 'region'"
              @change="emit('update:download-mode', 'region')"
              class="sr-only peer"
            />
            <div
              :class="[
                'relative rounded-lg border-2 p-4 text-center transition-all',
                downloadMode === 'region'
                  ? 'border-daun-400 bg-daun-400/10 ring-2 ring-daun-400/20'
                  : 'border-[#6F9435] bg-[#263D26]',
              ]"
            >
              <div class="mx-auto mb-1 w-fit text-[#EDD330]">
                <NavIcon name="locate" class="h-6 w-6" />
              </div>
              <div class="mt-1 text-sm font-semibold text-[#f0ead8]">Satu Daerah</div>
              <div class="mt-1 text-xs text-[#8fa06a]">Pilih kabupaten/kota, lalu kecamatan</div>
            </div>
          </label>
        </div>
      </div>

      <!-- Region Selection (when region mode) -->
      <div v-if="downloadMode === 'region'" class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
        <label for="unduh-kabupaten" class="block text-xs font-medium text-[#d4dc9a] mb-2">Pilih Kabupaten/Kota</label>
        <select
          id="unduh-kabupaten"
          :value="selectedOfflineRegion"
          @change="onOfflineRegionChange"
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

        <!-- Unduhan bisa dibatasi sampai satu kecamatan. Tanpa ini paket tile
             selalu memuat seluruh kabupaten padahal yang dibutuhkan peta satu
             kecamatan. -->
        <label for="unduh-kecamatan" class="mt-3 block text-xs font-medium text-[#d4dc9a] mb-2">
          Pilih Kecamatan <span class="text-[#8fa06a]">(opsional)</span>
        </label>
        <select
          id="unduh-kecamatan"
          :value="selectedOfflineKecamatan"
          @change="onOfflineKecamatanChange"
          :disabled="!selectedOfflineRegion"
          class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2.5 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330] disabled:cursor-not-allowed disabled:opacity-50"
        >
          <option value="">
            {{ selectedOfflineRegion ? '-- Semua Kecamatan --' : '-- Pilih Kabupaten dulu --' }}
          </option>
          <option
            v-for="kec in offlineKecamatanOptions"
            :key="kec.id_kec"
            :value="kec.id_kec"
          >
            {{ kec.nama_kec }}
          </option>
        </select>

        <div v-if="selectedOfflineWilayah" class="mt-2 text-xs text-[#8fa06a]">
          Area: {{ selectedOfflineWilayah.label }}
          <span class="text-[#EDD330]">({{ selectedOfflineWilayah.kode }})</span>
          <span
            v-if="selectedOfflineKecData && !selectedOfflineKecData.bbox"
            class="mt-1 block text-[#EDD330]"
          >
            Kecamatan ini belum punya batas sendiri, paket tile mengikuti seluruh kabupaten.
          </span>
        </div>
      </div>

      <!-- Mini Map Preview (when region selected) -->
      <div v-if="showMiniMap" class="rounded-lg border border-[#6F9435]/30 bg-[#263D26] p-3">
        <div class="flex items-center justify-between mb-2">
          <label class="text-xs font-medium text-[#d4dc9a]">Preview Wilayah</label>
          <span v-if="selectedOfflineWilayah" class="text-xs text-[#EDD330]">{{ selectedOfflineWilayah.name }}</span>
        </div>
        <div
          ref="miniMapContainer"
          class="peta-shell h-[200px] w-full rounded-lg overflow-hidden border border-[#6F9435]/30 relative"
        >
          <div v-if="miniMapLoading" class="absolute inset-0 flex items-center justify-center bg-[#263D26]/90">
            <p class="text-sm text-[#8fa06a]">Memuat preview...</p>
          </div>
          <div v-if="miniMapError" class="absolute inset-0 flex items-center justify-center bg-[#263D26]/90 text-center p-4">
            <p class="text-sm text-[#f87171]">{{ miniMapError }}</p>
            <button @click="emit('init-minimap')" class="mt-2 text-xs text-daun-400 hover:underline">Coba lagi</button>
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
              :value="offlineZoomMin"
              @input="onOfflineZoomMinChange"
              type="range"
              :min="zoomBounds.min"
              :max="zoomBounds.max"
              class="w-full"
            />
            <p class="text-xs text-[#8fa06a] text-right">Zoom: {{ offlineZoomMin }}</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-[#d4dc9a] mb-1">Zoom Max</label>
            <input
              :value="offlineZoomMax"
              @input="onOfflineZoomMaxChange"
              type="range"
              :min="zoomBounds.min"
              :max="zoomBounds.max"
              class="w-full"
            />
            <p class="text-xs text-[#8fa06a] text-right">Zoom: {{ offlineZoomMax }}</p>
          </div>
        </div>
        <div class="mt-3 rounded-lg bg-[#263D26] p-3 text-xs text-[#8fa06a]">
          <p>Estimasi tile: {{ estimatedTiles.toLocaleString() }}</p>
          <p>Estimasi ukuran: ~{{ estimatedSize }}</p>
          <p v-if="archiveZoom" class="mt-1">
            Arsip peta memuat zoom {{ archiveZoom.min }}&ndash;{{ archiveZoom.max }}.
          </p>
        </div>
      </div>

      <!-- Tampilan Peta: berlaku untuk peta utama, preview di atas, dan PNG yang
           diunduh. Ketiganya harus ikut berubah karena PNG diambil dari kanvas utama. -->
      <div class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
        <label class="block text-xs font-medium text-[#d4dc9a] mb-3">Tampilan Peta</label>
        <div class="space-y-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              :checked="showContour"
              @change="emit('toggle-contour', $event.target.checked)"
              class="rounded border-daun-500 text-daun-400 focus:ring-daun-400"
            />
            <span class="text-sm text-[#f0ead8]">Garis Kontur</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              :checked="showHillshade"
              @change="emit('toggle-hillshade', $event.target.checked)"
              class="rounded border-daun-500 text-daun-400 focus:ring-daun-400"
            />
            <span class="text-sm text-[#f0ead8]">Hillshade</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              :checked="showDistrictLabels"
              @change="emit('toggle-district-labels', $event.target.checked)"
              class="rounded border-daun-500 text-daun-400 focus:ring-daun-400"
            />
            <span class="text-sm text-[#f0ead8]">Label Kabupaten/Kota</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              :checked="showContourLabels"
              @change="emit('toggle-contour-labels', $event.target.checked)"
              class="rounded border-daun-500 text-daun-400 focus:ring-daun-400"
            />
            <span class="text-sm text-[#f0ead8]">Label Kontur</span>
          </label>
        </div>
        <p class="mt-2 text-xs text-[#8fa06a]">Berlaku untuk peta utama, preview, dan PNG yang diunduh.</p>
      </div>

      <!-- Format Lembar PNG -->
      <div class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
        <label for="unduh-orientasi" class="block text-xs font-medium text-[#d4dc9a] mb-2">Format Lembar PNG</label>
        <select
          id="unduh-orientasi"
          :value="printOrientation"
          @change="onPrintOrientationChange"
          class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-2.5 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        >
          <option v-for="option in sheetOrientationList" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <p class="mt-2 text-xs text-[#8fa06a]">Berlaku untuk tombol Cetak dan untuk PNG yang diunduh.</p>
        <!-- Pratinjau format. Kotak digambar dengan rasio sisi lembar yang benar-benar dipilih. -->
        <div class="mt-3 flex items-start gap-4">
          <div
            class="flex shrink-0 flex-col overflow-hidden rounded border-2 border-daun-400/70 bg-krem-100"
            :style="{
              width: printFormatPreview.height <= printFormatPreview.width ? '160px' : '110px',
              aspectRatio: `${printFormatPreview.width} / ${printFormatPreview.height}`,
            }"
            aria-hidden="true"
          >
            <div class="bg-daun-400/50" style="height: 14%"></div>
            <div class="flex-1 bg-[#c9d6b0]"></div>
            <div class="bg-krem-300/40" style="height: 7%"></div>
          </div>
          <div class="text-xs text-[#d4dc9a]">
            <p class="font-semibold text-emas-400">{{ printFormatPreview.label }}</p>
            <p class="mt-1">Lembar: {{ printFormatPreview.width }} &times; {{ printFormatPreview.height }}</p>
            <p>Berkas PNG: {{ printFormatPreview.outputWidth }} &times; {{ printFormatPreview.outputHeight }} piksel</p>
            <p class="mt-1 text-krem-300">
              Area peta dipatok supaya judul dan kaki halaman tetap muat.
            </p>
          </div>
        </div>
      </div>

      <!-- Map Layout Options -->
      <div class="rounded-lg border border-[#6F9435]/30 bg-[#335233] p-4">
        <label class="block text-xs font-medium text-[#d4dc9a] mb-3">Komponen Peta Offline</label>
        <div class="space-y-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              :checked="includeScaleBar"
              @change="emit('update:include-scale-bar', $event.target.checked)"
              class="rounded border-daun-500 text-daun-400 focus:ring-daun-400"
            />
            <span class="text-sm text-[#f0ead8]">Bilah Skala</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              :checked="includeNorthArrow"
              @change="emit('update:include-north-arrow', $event.target.checked)"
              class="rounded border-daun-500 text-daun-400 focus:ring-daun-400"
            />
            <span class="text-sm text-[#f0ead8]">Kompas Utara</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              :checked="includeLegend"
              @change="emit('update:include-legend', $event.target.checked)"
              class="rounded border-daun-500 text-daun-400 focus:ring-daun-400"
            />
            <span class="text-sm text-[#f0ead8]">Legenda</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              :checked="includeHistogram"
              @change="emit('update:include-histogram', $event.target.checked)"
              class="rounded border-daun-500 text-daun-400 focus:ring-daun-400"
            />
            <span class="text-sm text-[#f0ead8]">Histogram</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              :checked="includeGrid"
              @change="emit('update:include-grid', $event.target.checked)"
              class="rounded border-daun-500 text-daun-400 focus:ring-daun-400"
            />
            <span class="text-sm text-[#f0ead8]">Grid Koordinat</span>
          </label>
        </div>
      </div>

      <div v-if="downloadStatus" class="rounded-lg border border-[#6F9435]/30 bg-[#263D26] p-3 text-xs text-[#d4dc9a]">
        {{ downloadStatus }}
      </div>
      <button
        type="button"
        :disabled="downloading || !hasPmtiles || (downloadMode === 'region' && !selectedOfflineRegion)"
        @click="emit('download-offline')"
        class="w-full rounded-lg bg-gradient-to-r from-daun-400 to-daun-500 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ downloading ? 'Mengunduh...' : 'Mulai Unduh' }}
      </button>
    </div>
  </Modal>
</template>

<script setup lang="ts">
import { PropType, ref } from 'vue';
import NavIcon from '@/Components/NavIcon.vue';
import Modal from '@/Components/Modal.vue';

interface OfflineWilayah {
  name: string;
  label: string;
  kode: string;
  bbox?: number[] | null;
  namaKab?: string;
  namaKec?: string | null;
}

interface SheetOrientation {
  value: string;
  label: string;
}

interface PrintFormatPreview {
  label: string;
  width: number;
  height: number;
  outputWidth: number;
  outputHeight: number;
}

const props = defineProps({
  downloadMode: {
    type: String,
    default: 'full',
  },
  selectedOfflineRegion: {
    type: String,
    default: '',
  },
  selectedOfflineKecamatan: {
    type: String,
    default: '',
  },
  kabupatens: {
    type: Array as PropType<Array<{ id_kab: string; nama_kab: string }>>,
    default: () => [],
  },
  offlineKecamatanOptions: {
    type: Array as PropType<Array<{ id_kec: string; nama_kec: string }>>,
    default: () => [],
  },
  selectedOfflineWilayah: {
    type: Object as PropType<OfflineWilayah | null>,
    default: null,
  },
  selectedOfflineKecData: {
    type: Object as PropType<OfflineWilayah | null>,
    default: null,
  },
  offlineZoomMin: {
    type: Number,
    default: 8,
  },
  offlineZoomMax: {
    type: Number,
    default: 14,
  },
  zoomBounds: {
    type: Object as PropType<{ min: number; max: number }>,
    default: () => ({ min: 6, max: 14 }),
  },
  estimatedTiles: {
    type: Number,
    default: 0,
  },
  estimatedSize: {
    type: String,
    default: '0 KB',
  },
  archiveZoom: {
    type: Object as PropType<{ min: number; max: number } | null>,
    default: null,
  },
  showMiniMap: {
    type: Boolean,
    default: false,
  },
  miniMapLoading: {
    type: Boolean,
    default: false,
  },
  miniMapError: {
    type: String,
    default: '',
  },
  showContour: {
    type: Boolean,
    default: true,
  },
  showHillshade: {
    type: Boolean,
    default: false,
  },
  showDistrictLabels: {
    type: Boolean,
    default: false,
  },
  showContourLabels: {
    type: Boolean,
    default: false,
  },
  printOrientation: {
    type: String,
    default: 'landscape',
  },
  includeScaleBar: {
    type: Boolean,
    default: true,
  },
  includeNorthArrow: {
    type: Boolean,
    default: true,
  },
  includeLegend: {
    type: Boolean,
    default: true,
  },
  includeHistogram: {
    type: Boolean,
    default: true,
  },
  includeGrid: {
    type: Boolean,
    default: true,
  },
  hasPmtiles: {
    type: Boolean,
    default: false,
  },
  downloading: {
    type: Boolean,
    default: false,
  },
  downloadStatus: {
    type: String,
    default: '',
  },
  sheetOrientationList: {
    type: Array as PropType<SheetOrientation[]>,
    default: () => [],
  },
  printFormatPreview: {
    type: Object as PropType<PrintFormatPreview>,
    default: null,
  },
});

const emit = defineEmits([
  'close',
  'update:download-mode',
  'update:selected-offline-region',
  'update:selected-offline-kecamatan',
  'update:offline-zoom-min',
  'update:offline-zoom-max',
  'update:include-scale-bar',
  'update:include-north-arrow',
  'update:include-legend',
  'update:include-histogram',
  'update:include-grid',
  'update:print-orientation',
  'toggle-contour',
  'toggle-hillshade',
  'toggle-district-labels',
  'toggle-contour-labels',
  'download-offline',
  'init-minimap',
]);

const miniMapContainer = ref(null);

function onOfflineRegionChange(event: Event) {
  const target = event.target as HTMLSelectElement;
  emit('update:selected-offline-region', target.value);
}

function onOfflineKecamatanChange(event: Event) {
  const target = event.target as HTMLSelectElement;
  emit('update:selected-offline-kecamatan', target.value);
}

function onOfflineZoomMinChange(event: Event) {
  const target = event.target as HTMLInputElement;
  emit('update:offline-zoom-min', Number(target.value));
}

function onOfflineZoomMaxChange(event: Event) {
  const target = event.target as HTMLInputElement;
  emit('update:offline-zoom-max', Number(target.value));
}

function onPrintOrientationChange(event: Event) {
  const target = event.target as HTMLSelectElement;
  emit('update:print-orientation', target.value);
}
</script>
