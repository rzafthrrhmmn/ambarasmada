<template>
  <div class="rounded-2xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-lg transition hover:shadow-xl hover:shadow-[#EDD330]/20 hover:border-[#EDD330]">
    <div class="flex items-start justify-between gap-3">
      <div>
        <p class="text-xs font-bold uppercase tracking-wide text-[#8fa06a]">{{ label }}</p>
        <p class="mt-2 text-3xl font-extrabold text-[#EDD330]">{{ value }}</p>
      </div>
      <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#263D26] text-[#EDD330]" v-html="icon"></span>
    </div>
  </div>
</template>

<script setup>
defineProps({ label: String, value: [String, Number], icon: String });
</script>