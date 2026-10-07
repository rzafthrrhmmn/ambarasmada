<template>
  <div class="testimonials-carousel" ref="carousel" @mouseenter="pause" @mouseleave="resume">
    <div class="carousel-track" ref="track" :style="trackStyle">
      <div
        v-for="(testimonial, index) in testimonials"
        :key="index"
        class="carousel-slide"
        :style="slideStyle"
      >
        <TestimonialCard :testimonial="testimonial" :index="index" />
      </div>
    </div>

    <button
      v-if="testimonials.length > 1"
      @click="prev"
      class="carousel-btn carousel-btn-prev"
      :disabled="isAnimating"
      :aria-label="'Testimoni sebelumnya'"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-6 w-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
      </svg>
    </button>

    <button
      v-if="testimonials.length > 1"
      @click="next"
      class="carousel-btn carousel-btn-next"
      :disabled="isAnimating"
      :aria-label="'Testimoni berikutnya'"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-6 w-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
      </svg>
    </button>

    <div v-if="testimonials.length > 1" class="carousel-dots" role="tablist" aria-label="Navigasi testimoni">
      <button
        v-for="(_, index) in testimonials"
        :key="index"
        @click="goTo(index)"
        class="carousel-dot"
        :class="{ 'is-active': currentIndex === index }"
        :aria-label="'Testimoni ' + (index + 1)"
        :aria-selected="currentIndex === index"
        role="tab"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import TestimonialCard from './TestimonialCard.vue';

const props = defineProps({
  testimonials: { type: Array, required: true },
  autoPlay: { type: Boolean, default: true },
  interval: { type: Number, default: 6000 },
  showDots: { type: Boolean, default: true },
  showArrows: { type: Boolean, default: true },
});

const carousel = ref(null);
const track = ref(null);
const currentIndex = ref(0);
const isAnimating = ref(false);
let timer = null;
let touchStartX = 0;
let touchStartIndex = 0;

const slidesPerView = computed(() => {
  const width = carousel.value?.offsetWidth || 1200;
  if (width < 640) return 1;
  if (width < 1024) return 2;
  return 3;
});

const slideWidth = computed(() => {
  return 100 / slidesPerView.value;
});

const trackStyle = computed(() => ({
  transform: `translateX(-${currentIndex.value * slideWidth.value}%)`,
  transition: isAnimating.value ? 'transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94)' : 'none',
}));

const slideStyle = computed(() => ({
  flex: `0 0 ${slideWidth.value}%`,
  maxWidth: `${slideWidth.value}%`,
}));

function goTo(index) {
  if (isAnimating.value || index === currentIndex.value) return;
  
  const maxIndex = Math.max(0, props.testimonials.length - slidesPerView.value);
  const clampedIndex = Math.max(0, Math.min(index, maxIndex));
  
  isAnimating.value = true;
  currentIndex.value = clampedIndex;
  
  setTimeout(() => {
    isAnimating.value = false;
  }, 500);
  
  resetTimer();
}

function next() {
  const maxIndex = Math.max(0, props.testimonials.length - slidesPerView.value);
  goTo(currentIndex.value < maxIndex ? currentIndex.value + 1 : 0);
}

function prev() {
  const maxIndex = Math.max(0, props.testimonials.length - slidesPerView.value);
  goTo(currentIndex.value > 0 ? currentIndex.value - 1 : maxIndex);
}

function pause() {
  if (timer) clearInterval(timer);
  timer = null;
}

function resume() {
  if (!props.autoPlay) return;
  resetTimer();
}

function resetTimer() {
  if (timer) clearInterval(timer);
  if (!props.autoPlay) return;
  
  timer = setInterval(() => {
    if (!isAnimating.value) next();
  }, props.interval);
}

function handleTouchStart(e) {
  touchStartX = e.touches[0].clientX;
  touchStartIndex = currentIndex.value;
  pause();
}

function handleTouchMove(e) {
  if (isAnimating.value) return;
  const diff = touchStartX - e.touches[0].clientX;
  const threshold = 50;
  
  if (Math.abs(diff) > threshold) {
    if (diff > 0) next();
    else prev();
  }
}

function handleTouchEnd() {
  resume();
}

function handleResize() {
  nextTick(() => {
    const maxIndex = Math.max(0, props.testimonials.length - slidesPerView.value);
    if (currentIndex.value > maxIndex) {
      currentIndex.value = maxIndex;
    }
  });
}

function handleKeydown(e) {
  if (e.key === 'ArrowLeft') prev();
  else if (e.key === 'ArrowRight') next();
}

onMounted(() => {
  resetTimer();
  window.addEventListener('resize', handleResize);
  carousel.value?.addEventListener('keydown', handleKeydown);
  
  if (carousel.value) {
    carousel.value.addEventListener('touchstart', handleTouchStart, { passive: true });
    carousel.value.addEventListener('touchmove', handleTouchMove, { passive: true });
    carousel.value.addEventListener('touchend', handleTouchEnd);
  }
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
  window.removeEventListener('resize', handleResize);
  carousel.value?.removeEventListener('keydown', handleKeydown);
  if (carousel.value) {
    carousel.value.removeEventListener('touchstart', handleTouchStart);
    carousel.value.removeEventListener('touchmove', handleTouchMove);
    carousel.value.removeEventListener('touchend', handleTouchEnd);
  }
});

watch(() => props.testimonials, () => {
  nextTick(() => {
    const maxIndex = Math.max(0, props.testimonials.length - slidesPerView.value);
    if (currentIndex.value > maxIndex) {
      currentIndex.value = maxIndex;
    }
  });
});
</script>

<style scoped>
.testimonials-carousel {
  position: relative;
  overflow: hidden;
}

.carousel-track {
  display: flex;
  will-change: transform;
}

.carousel-slide {
  display: flex;
  flex-direction: column;
  padding: 0 1rem;
  box-sizing: border-box;
}

.carousel-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 10;
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

.carousel-btn:hover:not(:disabled) {
  background: var(--color-daun-500);
  border-color: var(--color-daun-400);
  color: var(--color-hutan-800);
  opacity: 1;
  transform: translateY(-50%) scale(1.1);
}

.carousel-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.carousel-btn-prev { left: 0; }
.carousel-btn-next { right: 0; }

@media (max-width: 640px) {
  .carousel-btn {
    width: 36px;
    height: 36px;
  }
  .carousel-btn-prev { left: 4px; }
  .carousel-btn-next { right: 4px; }
}

.carousel-dots {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 1.5rem;
}

.carousel-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--color-daun-500);
  opacity: 0.4;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
}

.carousel-dot:hover {
  opacity: 0.7;
  transform: scale(1.2);
}

.carousel-dot.is-active {
  background: var(--color-emas-400);
  opacity: 1;
  width: 28px;
  border-radius: 9999px;
}
</style>