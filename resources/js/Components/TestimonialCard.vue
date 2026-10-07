<template>
  <div class="testimonial-card group">
    <div class="card-bg">
      <div class="quote-mark" aria-hidden="true">"</div>
      <p class="quote">{{ testimonial.quote }}</p>
    </div>
    
    <div class="author-info">
  <div class="avatar" :style="avatarStyle">
    <span v-if="!testimonial.avatar" class="avatar-initials">{{ computedInitials }}</span>
    <img v-else :src="testimonial.avatar" :alt="testimonial.name" class="avatar-img" />
  </div>
      <div class="author-details">
        <h4 class="author-name">{{ testimonial.name }}</h4>
        <p class="author-role">{{ testimonial.role }}</p>
      </div>
    </div>

      <div class="rating" v-if="testimonial.rating" :aria-label="`Rating ${testimonial.rating} dari 5`">
      <svg v-for="i in 5" :key="i" class="star" :class="{ filled: i <= testimonial.rating }" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
      </svg>
    </div>

    <div class="card-glow" aria-hidden="true"></div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  testimonial: { type: Object, required: true },
  index: { type: Number, default: 0 },
});

const avatarStyle = computed(() => ({
  '--avatar-hue': (props.index * 60) % 360,
}));

const computedInitials = computed(() => {
  if (props.testimonial.initials) return props.testimonial.initials;
  const parts = (props.testimonial.name || '').trim().split(/\s+/);
  if (parts.length === 0 || (parts.length === 1 && !parts[0])) return '?';
  if (parts.length === 1) return parts[0][0].toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
});
</script>

<style scoped>
.testimonial-card {
  position: relative;
  background: linear-gradient(145deg, var(--color-hutan-700) 0%, var(--color-hutan-800) 100%);
  border: 2px solid var(--color-daun-500);
  border-radius: 1.5rem;
  padding: 2rem;
  height: 100%;
  display: flex;
  flex-direction: column;
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  overflow: hidden;
}

.testimonial-card:hover {
  border-color: var(--color-daun-400);
  transform: translateY(-4px);
  box-shadow: 0 20px 40px -20px rgba(0, 0, 0, 0.5), 0 0 0 1px var(--color-daun-400);
}

.card-bg {
  flex: 1;
  display: flex;
  flex-direction: column;
  position: relative;
  z-index: 1;
}

.quote-mark {
  font-size: 4rem;
  font-weight: 900;
  color: var(--color-daun-400);
  opacity: 0.3;
  line-height: 1;
  margin-bottom: 0.5rem;
  font-family: Georgia, serif;
}

.quote {
  font-size: 1rem;
  line-height: 1.7;
  color: var(--color-krem-300);
  font-weight: 400;
  font-style: italic;
}

.author-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-daun-500);
  position: relative;
  z-index: 1;
}

.avatar {
  position: relative;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  background: linear-gradient(135deg, hsl(var(--avatar-hue), 60%, 45%), hsl(var(--avatar-hue), 60%, 35%));
  border: 2px solid var(--color-daun-400);
}

.avatar-initials {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  font-size: 1.25rem;
  font-weight: 900;
  color: var(--color-hutan-800);
  text-transform: uppercase;
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.author-details {
  flex: 1;
  min-width: 0;
}

.author-name {
  font-size: 1rem;
  font-weight: 800;
  color: var(--color-krem-100);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.author-role {
  font-size: 0.8rem;
  color: var(--color-lumut-400);
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rating {
  display: flex;
  gap: 0.25rem;
  margin-top: 1rem;
  color: var(--color-emas-400);
}

.star {
  width: 18px;
  height: 18px;
  opacity: 0.3;
  transition: opacity 0.2s ease;
}

.star.filled {
  opacity: 1;
}

.card-glow {
  position: absolute;
  bottom: -50%;
  right: -50%;
  width: 100%;
  height: 100%;
  background: radial-gradient(ellipse, var(--color-emas-400) 0%, transparent 70%);
  opacity: 0;
  transition: opacity 0.4s ease;
  pointer-events: none;
  border-radius: 50%;
}

.testimonial-card:hover .card-glow {
  opacity: 0.08;
}
</style>