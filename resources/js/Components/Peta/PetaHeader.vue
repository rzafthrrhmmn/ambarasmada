<template>
  <header class="mb-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-daun-400">Peta Kontur</p>
      <h1 class="mt-1 text-2xl font-extrabold text-[#f0ead8] sm:text-3xl">{{ title }}</h1>
      <p class="mt-2 max-w-2xl text-sm text-[#8fa06a]">{{ subtitle }}</p>

      <!-- Ringkasan tetap yang selalu benar dan tidak bergantung pilihan
           pengguna, jadi pembaca punya konteks sebelum menyentuh peta. -->
      <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-xs">
        <div class="flex items-baseline gap-1.5">
          <dt class="text-[#8fa06a]">Jangkauan</dt>
          <dd class="font-semibold text-[#d4dc9a]">{{ range }}</dd>
        </div>
        <div class="flex items-baseline gap-1.5">
          <dt class="text-[#8fa06a]">Interval kontur</dt>
          <dd class="font-semibold text-[#d4dc9a]">{{ contourInterval }}</dd>
        </div>
        <div class="flex items-baseline gap-1.5">
          <dt class="text-[#8fa06a]">Kontur indeks</dt>
          <dd class="font-semibold text-[#d4dc9a]">{{ indexContour }}</dd>
        </div>
        <div class="flex items-baseline gap-1.5">
          <dt class="text-[#8fa06a]">Status data</dt>
          <dd class="font-semibold" :class="hasPmtiles ? 'text-daun-400' : 'text-[#f87171]'">
            {{ hasPmtiles ? 'Kontur tersedia' : 'Kontur belum tersedia' }}
          </dd>
        </div>
      </dl>
    </div>

    <section class="rounded-xl border-2 border-daun-500/40 bg-hutan-600/70 p-3">
      <div class="mb-2 flex items-center justify-between gap-2">
        <h2 class="text-xs font-semibold uppercase tracking-wider text-daun-400">Lapisan Peta</h2>
        <button
          v-if="hasPmtiles"
          @click="emit('download')"
          class="inline-flex items-center rounded-lg border-2 border-daun-400 bg-daun-400/10 px-3 py-1.5 text-xs font-bold text-daun-400 transition hover:bg-daun-400/20"
        >
          Unduh Offline
        </button>
      </div>
      <div class="flex flex-wrap gap-1.5">
        <LayerSelector
          :layers="layerItems"
          :activeLayers="activeLayers"
          @toggle-layer="onLayerToggle"
        />
      </div>

      <!-- Legenda bukan lapisan data, jadi tombolnya berdiri sendiri.
           Dipisah supaya state-nya tidak ikut ikut v-for di atas. -->
      <button
        type="button"
        @click="emit('toggle-legend')"
        :aria-pressed="legendVisible"
        class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-bold transition"
        :class="legendVisible
          ? 'border-emas-400 bg-emas-400/15 text-emas-400'
          : 'border-daun-500/50 bg-hutan-800/60 text-lumut-400 hover:border-daun-500 hover:text-krem-300'"
      >
        <NavIcon name="legend" class="h-4 w-4" />
        Legenda
      </button>
    </section>
  </header>
</template>

<script setup lang="ts">
import { PropType, computed } from 'vue';
import NavIcon from '@/Components/NavIcon.vue';
import LayerSelector from '@/Components/Peta/LayerSelector.vue';

interface PetaLayerItem {
  key: string;
  nama: string;
  icon: string;
  aktif: boolean;
  toggle?: () => void;
}

const props = defineProps({
  title: {
    type: String,
    default: 'Peta Kontur Sulawesi Selatan',
  },
  subtitle: {
    type: String,
    default: 'Cari kabupaten, lihat batas administratif, dan jelajahi kontur topografi.',
  },
  range: {
    type: String,
    default: '24 kabupaten/kota',
  },
  contourInterval: {
    type: String,
    default: '10 meter',
  },
  indexContour: {
    type: String,
    default: 'Setiap 50 meter',
  },
  hasPmtiles: {
    type: Boolean,
    default: false,
  },
  layerItems: {
    type: Array as PropType<PetaLayerItem[]>,
    default: () => [],
  },
  onToggleLayer: {
    type: Function as PropType<(key: string) => void>,
    default: undefined,
  },
  legendVisible: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['toggle-layer', 'download', 'toggle-legend']);

const activeLayers = computed(() => {
  const map: Record<string, boolean> = {};
  for (const item of props.layerItems) {
    map[item.key] = !!item.aktif;
  }
  return map;
});

function onLayerToggle(key: string) {
  if (props.onToggleLayer) {
    props.onToggleLayer(key);
  }
  emit('toggle-layer', key);
}
</script>
