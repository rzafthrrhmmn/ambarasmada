<template>
  <div
    ref="el"
    class="scroll-reveal"
    :class="{
      'is-visible': isVisible,
      'stagger-children': stagger,
    }"
    :style="containerStyle"
  >
    <slot />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';

const props = defineProps({
  threshold: { type: Number, default: 0 },
  rootMargin: { type: String, default: '0px 0px 0px 0px' },
  delay: { type: Number, default: 0 },
  stagger: { type: Boolean, default: false },
  staggerDelay: { type: Number, default: 100 },
  once: { type: Boolean, default: true },
  animation: { type: String, default: 'fade-up' },
  disabled: { type: Boolean, default: false },
});

const el = ref(null);
const isVisible = ref(false);
let observer = null;
let fallbackTimer = null;

const animationClasses = {
  'fade-up': 'animate-fade-up',
  'fade-down': 'animate-fade-down',
  'fade-left': 'animate-fade-left',
  'fade-right': 'animate-fade-right',
  'zoom-in': 'animate-zoom-in',
  'zoom-out': 'animate-zoom-out',
  'flip-up': 'animate-flip-up',
  'slide-up': 'animate-slide-up',
};

const containerStyle = computed(() => ({
  '--stagger-delay': `${props.staggerDelay}ms`,
}));

function handleIntersection(entries) {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      if (props.delay > 0) {
        setTimeout(() => {
          isVisible.value = true;
        }, props.delay);
      } else {
        isVisible.value = true;
      }
      
      if (props.once && observer) {
        observer.unobserve(entry.target);
      }
    } else if (!props.once) {
      isVisible.value = false;
    }
  });
}

function initObserver() {
  if (props.disabled) {
    isVisible.value = true;
    return;
  }
  
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
  if (prefersReducedMotion) {
    isVisible.value = true;
    return;
  }
  
  observer = new IntersectionObserver(handleIntersection, {
    threshold: props.threshold,
    rootMargin: props.rootMargin,
  });
  
  if (el.value) {
    observer.observe(el.value);
  }
}

onMounted(() => {
  nextTick(() => {
    try {
      initObserver();
    } catch (e) {
      isVisible.value = true;
    }
  });

  fallbackTimer = setTimeout(() => {
    if (!isVisible.value) {
      isVisible.value = true;
    }
  }, 50);
});

onUnmounted(() => {
  if (observer) {
    observer.disconnect();
  }
  if (fallbackTimer) {
    clearTimeout(fallbackTimer);
  }
});

watch(() => props.disabled, (disabled) => {
  if (disabled) {
    isVisible.value = true;
    if (observer) {
      try { observer.disconnect(); } catch (e) {}
    }
  } else {
    try { initObserver(); } catch (e) { isVisible.value = true; }
  }
});
</script>

<style scoped>
.scroll-reveal {
  opacity: 1;
}

.scroll-reveal.is-visible {
  animation: var(--reveal-animation, none) 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
}

.scroll-reveal.stagger-children > * {
  opacity: 1;
}

.scroll-reveal.stagger-children.is-visible > * {
  animation: var(--reveal-animation, fadeUp) 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
  animation-delay: calc(var(--stagger-index, 0) * var(--stagger-delay));
}

@keyframes fadeUp {
  from { transform: translateY(30px); }
  to { transform: translateY(0); }
}

@keyframes fadeDown {
  from { transform: translateY(-30px); }
  to { transform: translateY(0); }
}

@keyframes fadeLeft {
  from { transform: translateX(30px); }
  to { transform: translateX(0); }
}

@keyframes fadeRight {
  from { transform: translateX(-30px); }
  to { transform: translateX(0); }
}

@keyframes zoomIn {
  from { transform: scale(0.9); }
  to { transform: scale(1); }
}

@keyframes zoomOut {
  from { transform: scale(1.1); }
  to { transform: scale(1); }
}

@keyframes flipUp {
  from { transform: rotateX(-90deg); transform-origin: bottom; }
  to { transform: rotateX(0); transform-origin: bottom; }
}

@keyframes slideUp {
  from { transform: translateY(50px); }
  to { transform: translateY(0); }
}

.animate-fade-up { --reveal-animation: fadeUp; }
.animate-fade-down { --reveal-animation: fadeDown; }
.animate-fade-left { --reveal-animation: fadeLeft; }
.animate-fade-right { --reveal-animation: fadeRight; }
.animate-zoom-in { --reveal-animation: zoomIn; }
.animate-zoom-out { --reveal-animation: zoomOut; }
.animate-flip-up { --reveal-animation: flipUp; }
.animate-slide-up { --reveal-animation: slideUp; }

@media (prefers-reduced-motion: reduce) {
  .scroll-reveal {
    opacity: 1 !important;
  }
  
  .scroll-reveal.stagger-children > * {
    opacity: 1 !important;
    animation: none !important;
  }
}
</style>