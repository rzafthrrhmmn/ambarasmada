<template>
  <section v-if="!isLoading" id="pengumatan" class="guest-section scroll-mt-24">
    <div class="mb-8 sm:mb-12 text-center">
      <ScrollReveal animation="fade-up">
        <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
          <span class="h-1.5 w-1.5 rounded-full bg-emas-400 animate-pulse"></span>
          Aktivitas Terbaru
        </div>
      </ScrollReveal>
      <ScrollReveal animation="fade-up" :delay="100">
        <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl">
          <span class="guest-text-gradient">Pengumuman</span>
          <span class="guest-text-gradient-gold"> Terbaru</span>
        </h2>
        <p class="mx-auto mt-4 max-w-lg text-sm font-medium text-krem-300/80">
          Ikuti perkembangan terbaru dari ambalan.
        </p>
      </ScrollReveal>
    </div>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <ScrollReveal v-for="(item, idx) in announcements.slice(0, 6)" :key="item.id" :delay="idx * 80" :stagger="true" :style="`--stagger-index: ${idx}`" animation="fade-up">
        <article class="guest-announcement-card group">
          <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-daun-400/5 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
          <div class="relative mb-4 flex items-center justify-between">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-daun-400/15 px-3 py-1 text-xs font-bold text-emas-400">
              <AppIcon :name="categoryIcon(item.kategori)" class="h-3.5 w-3.5" />
              {{ item.kategori || 'Pengumuman' }}
            </span>
            <time class="text-xs font-bold text-lumut-400" :datetime="item.published_at">
              {{ formatDate(item.published_at) }}
            </time>
          </div>
          <div class="relative flex-1">
            <h3 class="text-lg font-extrabold text-krem-100 transition-colors duration-300 group-hover:text-emas-400">{{ item.judul }}</h3>
            <p class="mt-2 text-sm leading-relaxed font-medium text-krem-300/70 line-clamp-3">{{ item.isi }}</p>
            <span class="mt-4 inline-flex items-center gap-1 text-xs font-bold text-daun-400 transition-all duration-300 group-hover:text-emas-400 group-hover:translate-x-1">
              Baca selengkapnya
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </span>
          </div>
        </article>
      </ScrollReveal>
      <div v-if="!announcements.length" class="empty-state md:col-span-2 lg:col-span-3">
        <div class="empty-state-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
        <p class="empty-state-text">Belum ada pengumuman.</p>
      </div>
    </div>
  </section>
  <div v-else id="pengumatan-skeleton" class="guest-section scroll-mt-24">
    <div class="mb-8 sm:mb-12 text-center">
      <SkeletonLoader class="h-4 w-24 mb-2" />
      <SkeletonLoader class="mt-5 h-8 w-40 mx-auto" />
      <SkeletonLoader class="mt-4 h-4 w-64 mx-auto" />
    </div>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="i in 3" :key="i" class="guest-announcement-card">
        <div class="relative mb-4">
          <SkeletonLoader class="h-20 w-full rounded-xl bg-hutan-700/50" />
        </div>
        <div class="relative flex-1">
          <SkeletonLoader class="h-6 w-48 mt-2" />
          <SkeletonLoader class="mt-2 h-4 w-64 line-clamp-3" />
          <SkeletonLoader class="mt-4 h-4 w-24" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import ScrollReveal from '@/Components/ScrollReveal.vue';
import AppIcon from '@/Components/AppIcon.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';

const props = defineProps({
  announcements: { type: Array, default: () => [] },
  isLoading: { type: Boolean, default: false },
  formatDate: { type: Function, required: true },
  categoryIcon: { type: Function, required: true },
});
</script>
