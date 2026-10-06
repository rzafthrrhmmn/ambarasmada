<template>
  <article
    class="announcement-card"
    :href="item.url"
    v-if="item.url"
  >
    <AnnouncementCardContent :item="item" />
  </article>
  <article class="announcement-card" v-else>
    <AnnouncementCardContent :item="item" @click="$emit('click', item)" />
  </article>
</template>

<script setup>
import { computed } from 'vue';
import AnnouncementCardContent from './AnnouncementCardContent.vue';

const props = defineProps({
  item: { type: Object, required: true },
});

const emit = defineEmits(['click']);
</script>

<style scoped>
.announcement-card {
  text-decoration: none;
  color: inherit;
  display: block;
  height: 100%;
}
</style>