<template>
  <section class="guest-section" data-animate="stats" id="stats" v-if="!isLoading">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <ScrollReveal v-for="(stat, idx) in statsItems" :key="stat.label" :delay="idx * 100" :stagger="true" :style="`--stagger-index: ${idx}`">
        <div class="guest-stat-card group">
          <span aria-hidden="true" class="guest-stat-card-glow" />
          <div class="relative flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-xs font-bold uppercase tracking-wide text-lumut-400">{{ stat.label }}</p>
              <p class="mt-2 text-3xl font-extrabold text-emas-400 sm:text-4xl">{{ animatedStats[stat.key] }}</p>
            </div>
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border-2 border-daun-500/40 bg-hutan-800 text-emas-400 transition duration-200 group-hover:border-emas-400/50 group-hover:text-emas-400">
              <AppIcon :name="stat.icon" class="h-6 w-6" />
            </span>
          </div>
        </div>
      </ScrollReveal>
    </div>
  </section>
  <div v-else class="guest-section" id="stats" data-animate="stats">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="i in 4" :key="i" class="guest-stat-card">
        <SkeletonLoader class="h-6 w-24" />
        <SkeletonLoader class="mt-2 h-6 w-32" />
      </div>
    </div>
  </div>
</template>

<script setup>
import ScrollReveal from '@/Components/ScrollReveal.vue';
import AppIcon from '@/Components/AppIcon.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';

const props = defineProps({
  statsItems: { type: Array, default: () => [] },
  animatedStats: { type: Object, default: () => ({}) },
  isLoading: { type: Boolean, default: false },
});
</script>
