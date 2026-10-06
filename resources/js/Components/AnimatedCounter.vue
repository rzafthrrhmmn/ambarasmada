<template>
  <div class="animated-counter" :class="{ 'is-visible': isVisible }">
    <div class="counter-value" ref="counterEl" aria-live="polite" aria-atomic="true">
      <span v-if="showPrefix">{{ prefix }}</span>
      {{ formattedValue }}
      <span v-if="showSuffix">{{ suffix }}</span>
    </div>
    <div class="counter-label">{{ label }}</div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
  value: { type: Number, required: true },
  label: { type: String, required: true },
  duration: { type: Number, default: 2000 },
  delay: { type: Number, default: 0 },
  prefix: { type: String, default: '' },
  suffix: { type: String, default: '' },
  decimals: { type: Number, default: 0 },
  separator: { type: String, default: ',' },
  easing: { type: String, default: 'easeOutExpo' },
});

const animatedValue = ref(0);
const isVisible = ref(false);
const counterEl = ref(null);
let hasAnimated = false;
let observer = null;

const formattedValue = computed(() => {
  const val = animatedValue.value.toFixed(props.decimals);
  const parts = val.split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, props.separator);
  return parts.join('.');
});

const showPrefix = computed(() => props.prefix.length > 0);
const showSuffix = computed(() => props.suffix.length > 0);

const easingFunctions = {
  easeOutExpo: (t) => t === 1 ? 1 : 1 - Math.pow(2, -10 * t),
  easeOutCubic: (t) => 1 - Math.pow(1 - t, 3),
  easeOutQuart: (t) => 1 - Math.pow(1 - t, 4),
  easeOutQuint: (t) => 1 - Math.pow(1 - t, 5),
  easeOutSine: (t) => Math.sin((t * Math.PI) / 2),
  easeOutCirc: (t) => Math.sqrt(1 - Math.pow(t - 1, 2)),
  easeOutBack: (t) => {
    const c1 = 1.70158;
    const c3 = c1 + 1;
    return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2);
  },
};

function animate() {
  if (hasAnimated) return;
  
  const easing = easingFunctions[props.easing] || easingFunctions.easeOutExpo;
  const startTime = performance.now() + props.delay;
  const targetValue = props.value;
  
  function step(timestamp) {
    if (timestamp < startTime) {
      requestAnimationFrame(step);
      return;
    }
    
    const elapsed = timestamp - startTime;
    const progress = Math.min(elapsed / props.duration, 1);
    const easedProgress = easing(progress);
    
    animatedValue.value = targetValue * easedProgress;
    
    if (progress < 1) {
      requestAnimationFrame(step);
    } else {
      animatedValue.value = targetValue;
      hasAnimated = true;
    }
  }
  
  requestAnimationFrame(step);
}

function handleIntersection(entries) {
  entries.forEach(entry => {
    if (entry.isIntersecting && !hasAnimated) {
      isVisible.value = true;
      setTimeout(animate, props.delay);
      observer.unobserve(entry.target);
    }
  });
}

onMounted(() => {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
  if (prefersReducedMotion) {
    animatedValue.value = props.value;
    isVisible.value = true;
    hasAnimated = true;
    return;
  }
  
  observer = new IntersectionObserver(handleIntersection, {
    threshold: 0.3,
    rootMargin: '0px 0px -50px 0px',
  });
  
  if (counterEl.value) {
    observer.observe(counterEl.value);
  }
});

watch(() => props.value, (newValue) => {
  if (hasAnimated && isVisible.value) {
    animatedValue.value = newValue;
  }
});
</script>

<style scoped>
.animated-counter {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94),
              transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.animated-counter.is-visible {
  opacity: 1;
  transform: translateY(0);
}

.counter-value {
  font-size: clamp(2.5rem, 6vw, 4rem);
  font-weight: 900;
  line-height: 1.1;
  background: linear-gradient(135deg, var(--color-krem-100) 0%, var(--color-daun-400) 50%, var(--color-emas-400) 100%);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  display: inline-flex;
  align-items: baseline;
  gap: 0.25rem;
}

.counter-value span {
  font-weight: 800;
}

.counter-label {
  margin-top: 0.5rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-krem-300);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
</style>