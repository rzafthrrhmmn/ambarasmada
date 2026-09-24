<template>
  <nav v-if="links.length > 3" class="flex flex-wrap items-center justify-between gap-2 text-sm">
    <template v-for="(link, index) in links" :key="index">
      <Link v-if="link.url" :href="link.url" v-html="link.label" class="rounded-lg border-2 px-3 py-1.5 transition" :class="link.active ? 'border-[#EDD330] bg-gradient-to-r from-[#A7B92A] to-[#EDD330] text-[#263D26]' : 'border-[#6F9435] text-[#d4dc9a] hover:bg-[#6F9435]/30'" />
      <span v-else v-html="link.label" class="rounded-lg border-2 border-transparent px-3 py-1.5 text-[#8fa06a]" />
    </template>
  </nav>
</template>
<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ links: { type: Array, default: () => [] } });
</script>