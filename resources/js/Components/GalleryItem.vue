<template>
  <article class="gallery-item group" @click="handleClick" @keydown.enter="handleClick" @keydown.space.prevent="handleClick" tabindex="0" role="listitem" :aria-label="item.title || 'Foto dokumentasi'">
    <div class="image-wrapper">
      <img
        v-if="item.src"
        :src="item.src"
        :alt="item.title || 'Foto dokumentasi kegiatan'"
        class="gallery-image"
        loading="lazy"
      />
      <div v-else class="image-placeholder">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-12 w-12 text-lumut-400">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.25-5.25a2.25 2.25 0 013 0l3.75 3.75M9.75 12.75l.75.75m0 0l.75.75m-.75-.75v-6.75m-.75 6.75h6" />
        </svg>
      </div>

      <div class="image-overlay">
        <div class="overlay-content">
          <span class="zoom-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
          </span>
          <span class="zoom-text">Perbesar</span>
        </div>
      </div>

      <div class="category-badge" v-if="item.kategori">
        {{ item.kategori }}
      </div>
    </div>

    <div class="item-info">
      <h3 class="item-title">{{ item.title || 'Tanpa judul' }}</h3>
      <p v-if="item.description" class="item-description">{{ item.description }}</p>
      <time v-if="item.tanggal" class="item-date" :datetime="item.tanggal">
        {{ formatDate(item.tanggal) }}
      </time>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  item: { type: Object, required: true },
  index: { type: Number, default: 0 },
});

const emit = defineEmits(['open-lightbox']);

function handleClick() {
  emit('open-lightbox', props.index);
}

function formatDate(value) {
  return value
    ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    : '';
}
</script>

<style scoped>
.gallery-item {
  background: linear-gradient(145deg, var(--color-hutan-700) 0%, var(--color-hutan-800) 100%);
  border: 2px solid var(--color-daun-500);
  border-radius: 1.25rem;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  display: flex;
  flex-direction: column;
  height: 100%;
}

.gallery-item:hover {
  border-color: var(--color-daun-400);
  transform: translateY(-6px);
  box-shadow: 0 25px 50px -25px rgba(0, 0, 0, 0.6), 0 0 0 1px var(--color-daun-400);
}

.gallery-item:focus-visible {
  outline: 3px solid var(--color-emas-400);
  outline-offset: -3px;
}

.image-wrapper {
  position: relative;
  aspect-ratio: 4 / 3;
  overflow: hidden;
}

.gallery-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.gallery-item:hover .gallery-image {
  transform: scale(1.08);
}

.image-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, var(--color-hutan-800), var(--color-hutan-600));
}

.image-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(38, 61, 38, 0.95) 0%, rgba(38, 61, 38, 0.3) 50%, transparent 100%);
  opacity: 0;
  transition: opacity 0.4s ease;
  display: flex;
  align-items: flex-end;
  padding: 1.5rem;
}

.gallery-item:hover .image-overlay {
  opacity: 1;
}

.overlay-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--color-emas-400);
  font-weight: 700;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.zoom-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--color-hutan-800);
  border: 2px solid var(--color-daun-400);
  color: var(--color-emas-400);
  transition: all 0.3s ease;
}

.gallery-item:hover .zoom-icon {
  background: var(--color-daun-400);
  color: var(--color-hutan-800);
  transform: scale(1.1);
}

.category-badge {
  position: absolute;
  top: 1rem;
  left: 1rem;
  padding: 0.375rem 0.875rem;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: var(--color-hutan-800);
  color: var(--color-emas-400);
  border: 1px solid var(--color-daun-500);
  border-radius: 9999px;
  backdrop-filter: blur(8px);
}

.item-info {
  padding: 1.25rem;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.item-title {
  font-size: 1.0625rem;
  font-weight: 800;
  color: var(--color-krem-100);
  margin-bottom: 0.5rem;
  transition: color 0.3s ease;
}

.gallery-item:hover .item-title {
  color: var(--color-emas-400);
}

.item-description {
  font-size: 0.8125rem;
  color: var(--color-krem-300);
  opacity: 0.8;
  line-height: 1.5;
  flex: 1;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.item-date {
  font-size: 0.75rem;
  color: var(--color-lumut-400);
  font-weight: 600;
  margin-top: 0.75rem;
}
</style>