<template>
  <div class="flex flex-wrap gap-1.5">
    <button
      v-for="layer in layers"
      :key="layer.key"
      type="button"
      @click="emit('toggle-layer', layer.key)"
      :aria-pressed="isLayerActive(layer.key)"
      class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-bold transition"
      :class="
        isLayerActive(layer.key)
          ? 'border-emas-400 bg-emas-400/15 text-emas-400'
          : 'border-daun-500/50 bg-hutan-800/60 text-lumut-400 hover:border-daun-500 hover:text-krem-300'"
    >
      <NavIcon :name="layer.icon" class="h-4 w-4" />
      {{ layer.nama }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { PropType } from 'vue';
import NavIcon from '@/Components/NavIcon.vue';

interface PetaLayer {
  key: string;
  nama: string;
  icon: string;
  aktif?: boolean;
}

const props = defineProps({
  layers: {
    type: Array as PropType<PetaLayer[]>,
    default: () => [],
  },
  activeLayers: {
    type: Object as PropType<Record<string, boolean>>,
    default: () => ({}),
  },
});

const emit = defineEmits(['toggle-layer']);

function isLayerActive(key: string): boolean {
  if (Object.prototype.hasOwnProperty.call(props.activeLayers, key)) {
    return !!props.activeLayers[key];
  }
  const layer = props.layers.find((item) => item.key === key);
  return layer ? !!layer.aktif : false;
}
</script>
