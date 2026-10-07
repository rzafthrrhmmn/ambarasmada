<template>
  <Transition name="lightbox">
    <Teleport to="body">
      <div
        v-if="open"
        class="lightbox-overlay"
        @click.self="close"
        @keydown.escape="close"
        @keydown.left="prev"
        @keydown.right="next"
        role="dialog"
        aria-modal="true"
        aria-label="Galeri foto"
      >
        <button
          class="lightbox-close"
          @click="close"
          aria-label="Tutup galeri"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-7 w-7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
          </svg>
        </button>

        <button
          v-if="items.length > 1"
          class="lightbox-nav lightbox-prev"
          @click="prev"
          aria-label="Foto sebelumnya"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-8 w-8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
        </button>

        <div class="lightbox-content" ref="content">
          <div
            class="lightbox-track"
            ref="track"
            :style="trackStyle"
          >
            <div
              v-for="(item, index) in items"
              :key="item.id || index"
              class="lightbox-slide"
            >
               <img
                 v-if="item.src"
                 :src="item.src"
                 :alt="item.title || 'Foto dokumentasi'"
                 class="lightbox-image"
                 @load="onImageLoad(index)"
                 @error="e => { e.target.onerror = null; e.target.src = '/images/Logo_Ambalan.png'; }"
               />
              <div v-else class="lightbox-placeholder">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-16 w-16 text-lumut-400">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.25-5.25a2.25 2.25 0 013 0l3.75 3.75M9.75 12.75l.75.75m0 0l.75.75m-.75-.75v-6.75m-.75 6.75h6" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <button
          v-if="items.length > 1"
          class="lightbox-nav lightbox-next"
          @click="next"
          aria-label="Foto berikutnya"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-8 w-8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
          </svg>
        </button>

        <div class="lightbox-info" v-if="currentItem">
          <div class="info-header">
            <span class="info-category" v-if="currentItem.kategori">{{ currentItem.kategori }}</span>
            <span class="info-counter">{{ currentIndex + 1 }} / {{ items.length }}</span>
          </div>
          <h3 class="info-title">{{ currentItem.title || 'Tanpa judul' }}</h3>
          <p v-if="currentItem.description" class="info-description">{{ currentItem.description }}</p>
          <time v-if="currentItem.tanggal" class="info-date" :datetime="currentItem.tanggal">
            {{ formatDate(currentItem.tanggal) }}
          </time>
        </div>

        <div class="lightbox-dots" v-if="items.length > 1" role="tablist" aria-label="Navigasi foto">
          <button
            v-for="(_, index) in items"
            :key="index"
            @click="goTo(index)"
            class="lightbox-dot"
            :class="{ 'is-active': index === currentIndex }"
            :aria-label="'Foto ' + (index + 1)"
            :aria-selected="index === currentIndex"
            role="tab"
          />
        </div>
      </div>
    </Teleport>
  </Transition>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  items: { type: Array, required: true },
  currentIndex: { type: Number, default: 0 },
});

const emit = defineEmits(['update:modelValue', 'close', 'navigate']);

const open = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
});

const content = ref(null);
const track = ref(null);
let touchStartX = 0;
let isDragging = false;
let contentEl = null;

const currentItem = computed(() => props.items[props.currentIndex] || null);

const trackStyle = computed(() => ({
  transform: `translateX(-${props.currentIndex * 100}%)`,
  transition: isDragging ? 'none' : 'transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)',
}));

function close() {
  emit('close');
}

function prev() {
  if (props.items.length <= 1) return;
  const newIndex = (props.currentIndex - 1 + props.items.length) % props.items.length;
  emit('navigate', 'prev');
}

function next() {
  if (props.items.length <= 1) return;
  const newIndex = (props.currentIndex + 1) % props.items.length;
  emit('navigate', 'next');
}

function goTo(index) {
  if (index === props.currentIndex) return;
  emit('navigate', index);
}

function onImageLoad(index) {
  // Image loaded
}

function handleTouchStart(e) {
  touchStartX = e.touches[0].clientX;
  isDragging = true;
}

function handleTouchMove(e) {
  if (!isDragging) return;
  const diff = touchStartX - e.touches[0].clientX;
  if (Math.abs(diff) > 50) {
    if (diff > 0) next();
    else prev();
    isDragging = false;
  }
}

function handleTouchEnd() {
  isDragging = false;
}

function handleKeydown(e) {
  if (e.key === 'ArrowLeft') prev();
  else if (e.key === 'ArrowRight') next();
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
  contentEl = content.value;
  if (contentEl) {
    contentEl.addEventListener('touchstart', handleTouchStart, { passive: true });
    contentEl.addEventListener('touchmove', handleTouchMove, { passive: true });
    contentEl.addEventListener('touchend', handleTouchEnd);
  }
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
  if (contentEl) {
    contentEl.removeEventListener('touchstart', handleTouchStart);
    contentEl.removeEventListener('touchmove', handleTouchMove);
    contentEl.removeEventListener('touchend', handleTouchEnd);
  }
});

function formatDate(value) {
  return value
    ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    : '';
}
</script>

<style scoped>
.lightbox-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(0, 0, 0, 0.95);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.lightbox-close {
  position: fixed;
  top: 1.5rem;
  right: 1.5rem;
  z-index: 1010;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--color-hutan-800);
  border: 2px solid var(--color-daun-500);
  color: var(--color-krem-100);
  cursor: pointer;
  transition: all 0.3s ease;
  opacity: 0.8;
}

.lightbox-close:hover {
  background: var(--color-daun-500);
  border-color: var(--color-daun-400);
  color: var(--color-hutan-800);
  opacity: 1;
  transform: scale(1.1) rotate(90deg);
}

.lightbox-nav {
  position: fixed;
  top: 50%;
  transform: translateY(-50%);
  z-index: 1010;
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--color-hutan-800);
  border: 2px solid var(--color-daun-500);
  color: var(--color-krem-100);
  cursor: pointer;
  transition: all 0.3s ease;
  opacity: 0.8;
}

.lightbox-nav:hover {
  background: var(--color-daun-500);
  border-color: var(--color-daun-400);
  color: var(--color-hutan-800);
  opacity: 1;
  transform: translateY(-50%) scale(1.1);
}

.lightbox-prev { left: 1.5rem; }
.lightbox-next { right: 1.5rem; }

@media (max-width: 768px) {
  .lightbox-nav {
    width: 44px;
    height: 44px;
  }
  .lightbox-prev { left: 0.5rem; }
  .lightbox-next { right: 0.5rem; }
  .lightbox-close {
    top: 0.5rem;
    right: 0.5rem;
    width: 40px;
    height: 40px;
  }
}

.lightbox-content {
  position: relative;
  width: 100%;
  max-width: 1200px;
  height: 80vh;
  max-height: 700px;
  border-radius: 1rem;
  overflow: hidden;
  background: var(--color-hutan-900);
}

.lightbox-track {
  display: flex;
  height: 100%;
  width: 100%;
  will-change: transform;
}

.lightbox-slide {
  flex: 0 0 100%;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lightbox-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.lightbox-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-lumut-400);
}

.lightbox-info {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 2rem;
  background: linear-gradient(to top, rgba(22, 36, 26, 0.98) 0%, transparent 100%);
  color: var(--color-krem-100);
}

.info-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.info-category {
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--color-emas-400);
  padding: 0.25rem 0.75rem;
  background: var(--color-hutan-800);
  border: 1px solid var(--color-daun-500);
  border-radius: 9999px;
}

.info-counter {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-lumut-400);
}

.info-title {
  font-size: 1.5rem;
  font-weight: 900;
  margin-bottom: 0.5rem;
}

.info-description {
  font-size: 1rem;
  color: var(--color-krem-300);
  opacity: 0.9;
  line-height: 1.6;
  margin-bottom: 0.5rem;
}

.info-date {
  font-size: 0.875rem;
  color: var(--color-lumut-400);
  font-weight: 500;
}

.lightbox-dots {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 0.5rem;
  z-index: 1010;
}

.lightbox-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--color-daun-500);
  opacity: 0.4;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
}

.lightbox-dot:hover {
  opacity: 0.7;
  transform: scale(1.2);
}

.lightbox-dot.is-active {
  background: var(--color-emas-400);
  opacity: 1;
  width: 28px;
  border-radius: 9999px;
}

.lightbox-enter-active,
.lightbox-leave-active {
  transition: opacity 0.3s ease;
}

.lightbox-enter-from,
.lightbox-leave-to {
  opacity: 0;
}

.lightbox-enter-active .lightbox-content,
.lightbox-leave-active .lightbox-content {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.lightbox-enter-from .lightbox-content {
  transform: scale(0.95);
  opacity: 0;
}

.lightbox-leave-to .lightbox-content {
  transform: scale(0.95);
  opacity: 0;
}
</style>