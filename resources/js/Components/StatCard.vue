<template>
  <div
    class="group relative overflow-hidden rounded-2xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-lg transition duration-200 hover:-translate-y-0.5 hover:border-[#EDD330]/70 hover:shadow-xl hover:shadow-[#EDD330]/20"
  >
    <span
      aria-hidden="true"
      class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-[#EDD330]/5 blur-2xl transition-opacity duration-300 group-hover:opacity-100"
    />

    <div class="relative flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-bold uppercase tracking-wide text-[#8fa06a]">{{ label }}</p>
        <p class="mt-2 text-3xl font-extrabold text-[#EDD330]">{{ value }}</p>
      </div>
      <span
        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border-2 border-[#6F9435]/40 bg-[#263D26] text-[#EDD330] transition duration-200 group-hover:border-[#EDD330]/50 group-hover:text-[#EDD330]"
      >
        <AppIcon v-if="icon" :name="icon" class="h-6 w-6" />
      </span>
    </div>
  </div>
</template>

<script setup>
import AppIcon from '@/Components/AppIcon.vue';

defineProps({ label: String, value: [String, Number], icon: String });
</script>