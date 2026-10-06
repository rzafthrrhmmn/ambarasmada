<template>
  <div class="gallery-section">
    <div class="gallery-header" v-if="showHeader">
      <div>
        <span class="section-eyebrow">{{ eyebrow }}</span>
        <h2 class="section-title">
          <span class="text-gradient-primary">{{ titlePart1 }}</span>
          <span class="text-gradient-accent">{{ titlePart2 }}</span>
        </h2>
        <p class="section-description" v-if="description">{{ description }}</p>
      </div>
      <Link v-if="viewAllHref" :href="viewAllHref" class="view-all-link">
        Lihat semua
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
        </svg>
      </Link>
    </div>

    <div class="gallery-grid" ref="galleryGrid" role="list" aria-label="Galeri dokumentasi">
      <GalleryItem
        v-for="(item, index) in items"
        :key="item.id || index"
        :item="item"
        :index="index"
        @open-lightbox="openLightbox"
      />
    </div>

    <LightboxModal
      v-model:open="lightboxOpen"
      :items="items"
      :current-index="lightboxIndex"
      @close="closeLightbox"
      @navigate="navigateLightbox"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import GalleryItem from './GalleryItem.vue';
import LightboxModal from './LightboxModal.vue';

const props = defineProps({
  items: { type: Array, required: true },
  eyebrow: { type: String, default: 'Dokumentasi Kegiatan' },
  titlePart1: { type: String, default: 'Dokumentasi' },
  titlePart2: { type: String, default: 'Kegiatan' },
  description: { type: String, default: '' },
  viewAllHref: { type: String, default: '/galleries' },
  showHeader: { type: Boolean, default: true },
});

const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

function openLightbox(index) {
  lightboxIndex.value = index;
  lightboxOpen.value = true;
  document.body.style.overflow = 'hidden';
}

function closeLightbox() {
  lightboxOpen.value = false;
  document.body.style.overflow = '';
}

function navigateLightbox(direction) {
  const newIndex = direction === 'next' 
    ? (lightboxIndex.value + 1) % props.items.length
    : (lightboxIndex.value - 1 + props.items.length) % props.items.length;
  lightboxIndex.value = newIndex;
}
</script>

<style scoped>
.gallery-section {
  position: relative;
}

.gallery-header {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

@media (min-width: 768px) {
  .gallery-header {
    flex-direction: row;
    align-items: flex-end;
    justify-content: space-between;
  }
}

.section-eyebrow {
  @apply text-xs font-bold uppercase tracking-widest text-daun-400;
}

.section-title {
  @apply mt-1 text-3xl font-black sm:text-4xl lg:text-5xl;
}

.text-gradient-primary {
  background: linear-gradient(to right, var(--color-krem-100), var(--color-krem-300));
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

.text-gradient-accent {
  background: linear-gradient(to right, var(--color-daun-400), var(--color-emas-400), var(--color-daun-400));
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

.section-description {
  @apply mt-2 text-sm font-medium text-lumut-400 max-w-lg;
}

.view-all-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--color-daun-400);
  opacity: 0.7;
  transition: all 0.3s ease;
}

.view-all-link:hover {
  opacity: 1;
  color: var(--color-emas-400);
  transform: translateX(4px);
}

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 1.5rem;
}

@media (min-width: 640px) {
  .gallery-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .gallery-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 1280px) {
  .gallery-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}
</style>