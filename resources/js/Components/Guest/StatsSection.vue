<template>
  <section class="guest-section" data-animate="stats" id="stats" aria-label="Statistik ambalan">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <ScrollReveal
        v-for="(stat, idx) in props.statsItems"
        :key="`stat-${stat.key}`"
        :delay="idx * 100"
        :stagger="true"
        :style="`--stagger-index: ${idx}`"
        animation="fade-up"
      >
        <div
          class="guest-stat-card group"
          :aria-label="`${stat.label}: ${stat.value}`"
          role="region"
        >
          <span aria-hidden="true" class="guest-stat-card-glow" />
          <div class="relative flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-xs font-bold uppercase tracking-wide text-lumut-400">{{ stat.label }}</p>
              <p class="mt-2 text-3xl font-extrabold text-emas-400 sm:text-4xl" aria-live="polite">{{ animatedStats[stat.key] }}</p>
            </div>
            <span
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border-2 border-daun-500/40 bg-hutan-800 text-emas-400 transition duration-200 group-hover:border-emas-400/50 group-hover:text-emas-400 focus-visible:ring-2 focus-visible:ring-emas-400 focus-visible:outline-none"
            >
              <AppIcon :name="stat.icon" class="h-6 w-6" aria-hidden="true" />
            </span>
          </div>
        </div>
      </ScrollReveal>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import ScrollReveal from '@/Components/ScrollReveal.vue';

const props = defineProps({
  statsItems: { type: Array, default: () => [] },
  enableRepeatAnimation: { type: Boolean, default: false },
});

const animatedStats = ref({});
let animationFrameId = null;
let repeatTimeoutId = null;

function easeOutExpo(t) {
  return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
}

function animateCounter(target, key, duration = 1800) {
  if (typeof target !== 'number') {
    animatedStats.value[key] = target;
    return;
  }

  const startTime = performance.now();
  const numTarget = target;

  const step = (timestamp) => {
    const elapsed = timestamp - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const eased = easeOutExpo(progress);
    animatedStats.value[key] = Math.floor(numTarget * eased);

    if (progress < 1) {
      animationFrameId = requestAnimationFrame(step);
    } else if (props.enableRepeatAnimation && repeatTimeoutId === null) {
      repeatTimeoutId = setTimeout(() => {
        repeatTimeoutId = null;
        animateCounter(target, key, duration);
      }, 3000);
    }
  };

  animationFrameId = requestAnimationFrame(step);
}

function startAnimations() {
  props.statsItems.forEach((stat) => {
    const target = typeof stat.value === 'number' ? stat.value : 0;
    animateCounter(target, stat.key);
  });
}

onMounted(() => {
  startAnimations();
});

onUnmounted(() => {
  if (animationFrameId) cancelAnimationFrame(animationFrameId);
  if (repeatTimeoutId) clearTimeout(repeatTimeoutId);
});
</script>

<style scoped>
.guest-focus-ring {
  outline: none;
}
</style>
