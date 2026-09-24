<template>
  <div
    class="relative w-full overflow-hidden rounded-2xl border-2 border-[#6F9435]/40 bg-[#263D26] shadow-2xl shadow-black/30"
    @mouseenter="pause"
    @mouseleave="resume"
  >
    <div class="relative h-[260px] sm:h-[340px] md:h-[420px] lg:h-[480px] overflow-hidden">
      <TransitionGroup name="slider">
        <div
          v-for="(slide, index) in slides"
          v-show="currentIndex === index"
          :key="index"
          class="absolute inset-0"
        >
          <img
            :src="slide.src"
            :alt="slide.alt || ''"
            class="h-full w-full object-cover"
            :draggable="false"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-[#263D26]/95 via-[#263D26]/30 to-transparent" />
          <div class="absolute bottom-0 left-0 right-0 p-5 sm:p-7 md:p-10">
            <p v-if="slide.label" class="mb-2 text-xs font-bold tracking-widest text-[#A7B92A] uppercase">
              {{ slide.label }}
            </p>
            <h2 class="text-xl font-extrabold text-[#f0ead8] sm:text-2xl md:text-3xl" style="text-shadow: 2px 2px 0 rgba(0,0,0,0.4);">
              {{ slide.title }}
            </h2>
            <p v-if="slide.description" class="mt-2 max-w-lg text-sm font-medium text-[#d4dc9a]/80 sm:text-base">
              {{ slide.description }}
            </p>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <button
      v-if="slides.length > 1"
      @click="prev"
      class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-[#263D26]/80 p-2 text-[#f0ead8] transition hover:bg-[#6F9435]/40 hover:text-[#EDD330] focus:outline-none focus:ring-2 focus:ring-[#A7B92A]/50"
      aria-label="Sebelumnya"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
      </svg>
    </button>
    <button
      v-if="slides.length > 1"
      @click="next"
      class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-[#263D26]/80 p-2 text-[#f0ead8] transition hover:bg-[#6F9435]/40 hover:text-[#EDD330] focus:outline-none focus:ring-2 focus:ring-[#A7B92A]/50"
      aria-label="Berikutnya"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
      </svg>
    </button>

    <div v-if="slides.length > 1" class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2">
      <button
        v-for="(slide, index) in slides"
        :key="index"
        @click="goTo(index)"
        class="h-2 rounded-full transition-all focus:outline-none focus:ring-2 focus:ring-[#A7B92A]/50"
        :class="currentIndex === index ? 'w-6 bg-[#EDD330]' : 'w-2 bg-[#6F9435]/50 hover:bg-[#A7B92A]'"
        :aria-label="'Slide ' + (index + 1)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  slides: {
    type: Array,
    required: true,
    default: () => [],
  },
  interval: {
    type: Number,
    default: 5000,
  },
});

const currentIndex = ref(0);
let timer = null;

const advance = () => {
  currentIndex.value = (currentIndex.value + 1) % props.slides.length;
};

const next = () => {
  currentIndex.value = (currentIndex.value + 1) % props.slides.length;
  resetTimer();
};

const prev = () => {
  currentIndex.value = (currentIndex.value - 1 + props.slides.length) % props.slides.length;
  resetTimer();
};

const goTo = (index) => {
  currentIndex.value = index;
  resetTimer();
};

const pause = () => clearInterval(timer);
const resume = () => {
  clearInterval(timer);
  timer = setInterval(advance, props.interval);
};

const resetTimer = () => {
  clearInterval(timer);
  timer = setInterval(advance, props.interval);
};

watch(
  () => props.slides.length,
  (len) => {
    if (len > 0) {
      currentIndex.value = Math.min(currentIndex.value, len - 1);
      resetTimer();
    }
  }
);

onMounted(() => {
  if (props.slides.length > 1) {
    timer = setInterval(advance, props.interval);
  }
});

onUnmounted(() => {
  clearInterval(timer);
});
</script>

<style scoped>
.slider-enter-active,
.slider-leave-active {
  transition: opacity 0.6s ease, transform 0.6s ease;
}

.slider-enter-from {
  opacity: 0;
  transform: translateX(30px);
}

.slider-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}
</style>