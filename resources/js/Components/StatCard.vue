<template>
  <div
    class="group relative overflow-hidden rounded-2xl border-2 border-daun-400/40 bg-gradient-to-br from-hutan-600 to-hutan-700 p-5 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-emas-400/70 hover:shadow-xl hover:shadow-emas-400/20"
    :class="variantClass"
  >
    <span
      aria-hidden="true"
      class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-emas-400/5 blur-2xl transition-opacity duration-300 group-hover:opacity-100"
    />

    <div class="relative flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-bold uppercase tracking-wide text-lumut-400">{{ label }}</p>
        <p class="mt-2 text-3xl font-extrabold text-emas-400">{{ value }}</p>
        <p v-if="subtext" class="mt-1 text-[11px] font-medium text-krem-300">{{ subtext }}</p>
      </div>
      <span
        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border-2 border-daun-500/40 bg-hutan-800 text-emas-400 transition-all duration-300 group-hover:border-emas-400/50 group-hover:text-emas-400 group-hover:scale-110"
      >
        <AppIcon v-if="icon" :name="icon" class="h-6 w-6" />
      </span>
    </div>

    <div v-if="trend" class="mt-3 flex items-center gap-1.5">
      <span class="flex h-2 w-2 rounded-full" :class="trendDotClass" />
      <span class="text-[10px] font-bold" :class="trendTextClass">
        {{ trendDirection }} {{ trend }}
      </span>
    </div>
  </div>
</template>

<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import { computed } from 'vue';

defineProps({
  label: String,
  value: [String, Number],
  icon: String,
  variant: { type: String, default: 'default' },
  subtext: { type: String, default: '' },
  trend: { type: [String, Number], default: null },
  trendDirection: { type: String, default: '↑' },
});

const variantClass = computed(() => {
  switch (props.variant) {
    case 'success':
      return 'border-daun-400/60 from-hutan-600 to-daun-400/20';
    case 'warning':
      return 'border-emas-400/60 from-emas-400/10 to-hutan-700';
    case 'danger':
      return 'border-ef4419/60 from-ef4419/10 to-hutan-700';
    case 'info':
      return 'border-daun-500/60 from-hutan-600 to-daun-500/20';
    default:
      return '';
  }
});

const trendDotClass = computed(() => {
  if (!props.trend) return 'bg-krem-300';
  const num = Number(props.trend);
  if (num > 0) return 'bg-daun-400';
  if (num < 0) return 'bg-ef4419';
  return 'bg-krem-300';
});

const trendTextClass = computed(() => {
  if (!props.trend) return 'text-krem-300';
  const num = Number(props.trend);
  if (num > 0) return 'text-daun-400';
  if (num < 0) return 'text-ef4419';
  return 'text-krem-300';
});
</script>
