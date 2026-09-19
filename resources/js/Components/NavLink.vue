<template>
  <a
    :href="href"
    @click="handleClick"
    :class="[
      'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold transition-all duration-200 border-l-4',
      isActive
        ? 'bg-[#6F9435]/40 text-[#EDD330] border-l-4 border-[#EDD330]'
        : 'text-[#d4dc9a] hover:bg-[#335233] hover:text-[#EDD330] border-l-4 border-transparent',
    ]">
    <i class="flex h-5 w-5 items-center justify-center" v-html="icon" />
    <span>{{ label }}</span>
  </a>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  href: { type: String, required: true },
  label: { type: String, required: true },
  icon: { type: String, default: '' },
  active: { type: Boolean, default: false },
});

const page = usePage();

const isActive = computed(() => {
  if (props.active) return true;
  const currentPath = page.url.split('?')[0];
  return currentPath === props.href || currentPath.startsWith(props.href + '/');
});

function handleClick(event) {
  if (page.props.flash?.mobileNavClose) {
    // Allow parent to close mobile nav
  }
}
</script>
