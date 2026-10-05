<template>
  <AppIcon :name="name" :stroke="stroke" />
</template>

<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import { ICON_STROKE_WIDTH } from '@/Navigation/icons.js';

/**
 * Alias navigasi untuk `AppIcon.vue`.
 *
 * Sidebar, header, dan panel peta memakai nama `NavIcon` supaya jelas ikon itu
 * menutup bagian navigasi, sedangkan halaman konten memakai `AppIcon` untuk
 * ikon di dalam tombol dan label datanya. Keduanya merender bentuk yang sama
 * dari satu komponen, jadi tidak ada dua implementasi SVG yang bisa melebar.
 */
defineProps({
    name: {
        type: String,
        required: true,
    },
    stroke: {
        type: Number,
        default: ICON_STROKE_WIDTH,
    },
});
</script>