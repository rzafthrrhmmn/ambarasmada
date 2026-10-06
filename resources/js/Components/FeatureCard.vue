<template>
  <article
    class="feature-card"
    ref="card"
    @mousemove="handleMouseMove"
    @mouseleave="handleMouseLeave"
    @mouseenter="handleMouseEnter"
    :style="cardStyle"
  >
    <div class="card-glow" :style="glowStyle" aria-hidden="true"></div>
    <div class="card-border" aria-hidden="true"></div>
    
    <div class="card-content">
      <div class="icon-wrapper" :style="iconWrapperStyle">
        <component :is="iconComponent" class="feature-icon" v-if="iconComponent" />
        <AppIcon v-else :name="iconName" class="feature-icon" />
      </div>
      
      <h3 class="feature-title">{{ title }}</h3>
      <p class="feature-description">{{ description }}</p>
      
      <div class="feature-tags" v-if="tags && tags.length">
        <span v-for="tag in tags" :key="tag" class="feature-tag">{{ tag }}</span>
      </div>
      
      <div class="feature-arrow" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
        </svg>
      </div>
    </div>
  </article>
</template>

<script setup>
import { ref, computed, shallowRef } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, required: true },
  iconName: { type: String, default: '' },
  iconComponent: { type: Object, default: null },
  tags: { type: Array, default: () => [] },
  variant: { type: String, default: 'default' },
  tiltIntensity: { type: Number, default: 8 },
});

const card = ref(null);
const rotateX = ref(0);
const rotateY = ref(0);
const isHovering = ref(false);

const cardStyle = computed(() => ({
  transform: `perspective(1000px) rotateX(${rotateX.value}deg) rotateY(${rotateY.value}deg)`,
  transition: isHovering.value ? 'transform 0.1s ease-out' : 'transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)',
}));

const glowStyle = computed(() => ({
  transform: `translate(${rotateY.value * 5}px, ${-rotateX.value * 5}px)`,
  opacity: isHovering.value ? 1 : 0,
}));

const iconWrapperStyle = computed(() => ({
  transform: `translateZ(40px) rotateX(${-rotateX.value * 0.5}deg) rotateY(${-rotateY.value * 0.5}deg)`,
}));

function handleMouseMove(e) {
  if (!card.value) return;
  
  const rect = card.value.getBoundingClientRect();
  const centerX = rect.left + rect.width / 2;
  const centerY = rect.top + rect.height / 2;
  
  const deltaX = (e.clientX - centerX) / (rect.width / 2);
  const deltaY = (e.clientY - centerY) / (rect.height / 2);
  
  rotateY.value = deltaX * props.tiltIntensity;
  rotateX.value = -deltaY * props.tiltIntensity;
}

function handleMouseLeave() {
  rotateX.value = 0;
  rotateY.value = 0;
  isHovering.value = false;
}

function handleMouseEnter() {
  isHovering.value = true;
}
</script>

<style scoped>
.feature-card {
  position: relative;
  background: linear-gradient(145deg, var(--color-hutan-700) 0%, var(--color-hutan-800) 100%);
  border: 2px solid var(--color-daun-500);
  border-radius: 1.5rem;
  padding: 2rem;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transform-style: preserve-3d;
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  cursor: pointer;
}

.feature-card:hover {
  border-color: var(--color-daun-400);
  transform: perspective(1000px) rotateX(0) rotateY(0) translateY(-8px);
  box-shadow: 
    0 30px 60px -30px rgba(0, 0, 0, 0.7),
    0 0 0 1px var(--color-daun-400),
    0 0 60px -10px var(--color-daun-400);
}

.card-glow {
  position: absolute;
  inset: -50%;
  background: radial-gradient(ellipse at center, var(--color-daun-400) 0%, var(--color-daun-500) 30%, transparent 70%);
  opacity: 0;
  transition: opacity 0.4s ease, transform 0.1s ease-out;
  pointer-events: none;
  border-radius: 50%;
  filter: blur(40px);
}

.feature-card:hover .card-glow {
  opacity: 0.15;
}

.card-border {
  position: absolute;
  inset: 0;
  border-radius: 1.5rem;
  background: linear-gradient(135deg, var(--color-daun-400) 0%, var(--color-emas-400) 50%, var(--color-daun-400) 100%);
  opacity: 0;
  transition: opacity 0.4s ease;
  z-index: -1;
}

.feature-card:hover .card-border {
  opacity: 0.1;
}

.card-content {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  height: 100%;
  transform: translateZ(20px);
}

.icon-wrapper {
  position: relative;
  width: 72px;
  height: 72px;
  border-radius: 1.25rem;
  background: linear-gradient(135deg, var(--color-hutan-800) 0%, var(--color-hutan-600) 100%);
  border: 2px solid var(--color-daun-500);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  transform-style: preserve-3d;
}

.feature-card:hover .icon-wrapper {
  border-color: var(--color-daun-400);
  box-shadow: 0 0 30px -5px var(--color-daun-400);
}

.feature-icon {
  width: 32px;
  height: 32px;
  color: var(--color-emas-400);
  transition: all 0.3s ease;
}

.feature-card:hover .feature-icon {
  color: var(--color-daun-400);
  transform: scale(1.1);
}

.feature-title {
  font-size: 1.25rem;
  font-weight: 900;
  color: var(--color-krem-100);
  margin-bottom: 0.75rem;
  transition: color 0.3s ease;
}

.feature-card:hover .feature-title {
  color: var(--color-emas-400);
}

.feature-description {
  font-size: 0.9375rem;
  line-height: 1.7;
  color: var(--color-krem-300);
  opacity: 0.85;
  flex: 1;
}

.feature-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-daun-500);
}

.feature-tag {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.25rem 0.625rem;
  background: var(--color-hutan-800);
  color: var(--color-daun-400);
  border: 1px solid var(--color-daun-500);
  border-radius: 9999px;
  transition: all 0.3s ease;
}

.feature-card:hover .feature-tag {
  background: var(--color-daun-500);
  color: var(--color-hutan-800);
  border-color: var(--color-daun-400);
}

.feature-arrow {
  position: absolute;
  bottom: 2rem;
  right: 2rem;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--color-hutan-800);
  border: 2px solid var(--color-daun-500);
  color: var(--color-emas-400);
  opacity: 0;
  transform: translateX(-20px) translateZ(40px);
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.feature-card:hover .feature-arrow {
  opacity: 1;
  transform: translateX(0) translateZ(40px);
  border-color: var(--color-daun-400);
  color: var(--color-daun-400);
}

.feature-card:focus-visible {
  outline: 3px solid var(--color-emas-400);
  outline-offset: -3px;
}

@media (prefers-reduced-motion: reduce) {
  .feature-card,
  .icon-wrapper,
  .feature-title,
  .feature-tag,
  .feature-arrow,
  .card-glow {
    transition: none !important;
  }
  
  .feature-card:hover {
    transform: translateY(-8px);
  }
}
</style>