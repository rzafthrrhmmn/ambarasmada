<template>
  <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end section-animate animate-fade-up">
    <div class="min-w-0">
      <p v-if="eyebrow" class="text-xs font-bold uppercase tracking-widest text-daun-400 mb-1">{{ eyebrow }}</p>
      <h1 class="text-2xl font-extrabold text-krem-100 sm:text-3xl tracking-tight">{{ title }}</h1>
      <p v-if="subtitle" class="mt-1 text-sm text-krem-300 leading-relaxed">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.actions" class="flex flex-wrap items-center justify-start gap-2 sm:justify-end">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  eyebrow: { type: String, default: '' },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
});
</script>
