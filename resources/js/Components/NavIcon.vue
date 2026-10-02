<template>
  <svg
    :viewBox="`0 0 ${icon.viewBox} ${icon.viewBox}`"
    fill="none"
    stroke="currentColor"
    :stroke-width="stroke"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
  >
    <template v-for="(shape, index) in icon.shapes" :key="index">
      <path v-if="shape.type === 'path'" :d="shape.d" />
      <rect
        v-else-if="shape.type === 'rect'"
        :x="shape.x"
        :y="shape.y"
        :width="shape.width"
        :height="shape.height"
        :rx="shape.rx"
      />
      <circle v-else-if="shape.type === 'circle'" :cx="shape.cx" :cy="shape.cy" :r="shape.r" />
    </template>
  </svg>
</template>

<script setup>
import { computed, watchEffect } from 'vue';
import { ICON_STROKE_WIDTH, resolveIcon } from '@/Navigation/icons.js';

/**
 * Satu-satunya tempat ikon dirender sebagai SVG asli.
 *
 * `name` merujuk entri pada `Navigation/icons.js`, sehingga menambah ikon cukup
 * dengan menambah data di sana. Ukuran dikendalikan kelas dari induk
 * (`h-5 w-5`, `h-4 w-4`, ...) karena `svg` sengaja tidak diberi atribut width
 * atau height.
 */
const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    stroke: {
        type: Number,
        default: ICON_STROKE_WIDTH,
    },
});

const icon = computed(() => resolveIcon(props.name));

if (import.meta.env?.DEV) {
    // Nama ikon berasal dari model navigasi, bukan dari input pengguna, jadi
    // peringatan ini hanya membantu saat model sedang dikembangkan.
    watchEffect(() => {
        if (icon.value.fallback) {
            console.warn(`[nav] Ikon "${props.name}" tidak dikenal, memakai ikon cadangan.`);
        }
    });
}
</script>