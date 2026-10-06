<template>
  <div class="card-inner">
    <div class="card-glow" aria-hidden="true"></div>
    
    <div class="card-header">
      <span class="category-badge" :style="badgeStyle">
        <AppIcon :name="categoryIcon" class="h-3.5 w-3.5" aria-hidden="true" />
        {{ item.kategori || 'Pengumuman' }}
      </span>
      <time class="card-date" :datetime="item.published_at" v-if="item.published_at">
        {{ formatDate(item.published_at) }}
      </time>
    </div>
    
    <h3 class="card-title">{{ item.judul || item.title }}</h3>
    
    <p class="card-excerpt" v-if="item.isi || item.description">
      {{ item.isi || item.description }}
    </p>
    
    <div class="card-footer">
      <span class="read-more" v-if="!item.url">
        Baca selengkapnya
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
        </svg>
      </span>
      <span class="external-link" v-else aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
        </svg>
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  item: { type: Object, required: true },
});

const emit = defineEmits(['click']);

const categoryIcon = computed(() => {
  const map = {
    'Kegiatan': 'events',
    'Latihan': 'assessments',
    'Outbound': 'map',
    'Jambore': 'teams',
    'Peringatan': 'announcements',
    'Umum': 'announcements',
    'Pengumuman': 'announcements',
  };
  return map[props.item.kategori] || 'announcements';
});

const badgeStyle = computed(() => {
  const colors = {
    'Kegiatan': 'var(--color-daun-400)',
    'Latihan': 'var(--color-emas-400)',
    'Outbound': '#8B5CF6',
    'Jambore': '#EC4899',
    'Peringatan': '#EF4444',
    'Umum': 'var(--color-lumut-400)',
    'Pengumuman': 'var(--color-lumut-400)',
  };
  const color = colors[props.item.kategori] || 'var(--color-lumut-400)';
  return {
    '--badge-color': color,
  };
});

function formatDate(value) {
  return value
    ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    : '';
}
</script>

<style scoped>
.card-inner {
  position: relative;
  height: 100%;
  display: flex;
  flex-direction: column;
  background: linear-gradient(145deg, var(--color-hutan-700) 0%, var(--color-hutan-800) 100%);
  border: 2px solid var(--color-daun-500);
  border-radius: 1.25rem;
  padding: 1.5rem;
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  overflow: hidden;
}

.announcement-card:hover .card-inner,
.announcement-card:focus-visible .card-inner {
  border-color: var(--color-daun-400);
  transform: translateY(-6px);
  box-shadow: 
    0 25px 50px -25px rgba(0, 0, 0, 0.6),
    0 0 0 1px var(--color-daun-400);
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

.announcement-card:hover .card-glow {
  opacity: 0.08;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.category-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.375rem 0.75rem;
  background: var(--color-hutan-800);
  color: var(--badge-color);
  border: 1px solid var(--badge-color);
  border-radius: 9999px;
  backdrop-filter: blur(8px);
  transition: all 0.3s ease;
}

.announcement-card:hover .category-badge {
  background: var(--badge-color);
  color: var(--color-hutan-800);
}

.card-date {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-lumut-400);
  white-space: nowrap;
  flex-shrink: 0;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 800;
  color: var(--color-krem-100);
  line-height: 1.4;
  margin-bottom: 0.75rem;
  transition: color 0.3s ease;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.announcement-card:hover .card-title {
  color: var(--color-emas-400);
}

.card-excerpt {
  font-size: 0.875rem;
  line-height: 1.6;
  color: var(--color-krem-300);
  opacity: 0.8;
  flex: 1;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-daun-500);
}

.read-more {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--color-daun-400);
  opacity: 0.8;
  transition: all 0.3s ease;
}

.announcement-card:hover .read-more {
  opacity: 1;
  color: var(--color-emas-400);
  transform: translateX(4px);
}

.external-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--color-hutan-800);
  border: 1px solid var(--color-daun-500);
  color: var(--color-lumut-400);
  transition: all 0.3s ease;
}

.announcement-card:hover .external-link {
  background: var(--color-daun-500);
  border-color: var(--color-daun-400);
  color: var(--color-hutan-800);
}

.announcement-card:focus-visible {
  outline: 3px solid var(--color-emas-400);
  outline-offset: -3px;
}

@media (prefers-reduced-motion: reduce) {
  .card-inner,
  .category-badge,
  .card-title,
  .read-more,
  .external-link,
  .card-glow {
    transition: none !important;
  }
  
  .announcement-card:hover .card-inner {
    transform: translateY(-6px);
  }
}
</style>