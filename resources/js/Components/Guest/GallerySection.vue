<template>
  <section v-if="!isLoading && gallery.length > 0" id="dokumentasi" class="guest-section scroll-mt-24">
    <div class="mb-8 flex items-end justify-between">
      <div>
        <ScrollReveal animation="fade-up">
          <span class="section-eyebrow text-daun-400">Dokumentasi Kegiatan</span>
          <h2 class="text-2xl font-black text-transparent sm:text-3xl">
            <span class="guest-text-gradient">Dokumentasi</span>
            <span class="guest-text-gradient-gold"> Kegiatan</span>
          </h2>
          <p class="mt-1 text-sm font-medium text-lumut-400">Foto-foto dokumentasi dari kegiatan kepramukaan terbaru.</p>
        </ScrollReveal>
      </div>
      <ScrollReveal animation="fade-up" :delay="100">
        <Link href="/galleries" class="text-sm font-bold text-daun-400 opacity-70 transition-opacity hover:opacity-100 hover:text-emas-400">
          Lihat semua &rarr;
        </Link>
      </ScrollReveal>
    </div>
    <ScrollReveal animation="fade-up" :delay="150">
      <div class="relative">
        <div ref="galleryContainer" class="guest-gallery-track">
          <button
            v-for="(item, idx) in gallery"
            :key="idx"
            class="guest-gallery-item group w-[min(300px,80vw)]"
            @click="openLightbox(idx)"
            :aria-label="`Lihat ${item.title || 'foto'}`"
          >
            <div class="relative h-52 overflow-hidden">
              <img
                v-if="item.src"
                :src="item.src"
                :alt="item.title"
                class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                loading="lazy"
                @error="e => { e.target.onerror = null; e.target.src = '/images/Logo_Ambalan.png'; }"
              />
              <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-hutan-800 to-hutan-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-lumut-400">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.25-5.25a2.25 2.25 0 013 0l3.75 3.75M9.75 12.75l.75.75m0 0l.75.75m-.75-.75v-6.75m-.75 6.75h6" />
                </svg>
              </div>
              <div class="absolute inset-0 bg-gradient-to-t from-hutan-800/95 via-hutan-800/20 to-transparent opacity-60 transition-opacity group-hover:opacity-80"></div>
              <div class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                <div class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-emas-400 bg-hutan-800/90 text-emas-400 backdrop-blur-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-6 w-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                  </svg>
                </div>
              </div>
            </div>
            <div class="p-4">
              <span class="mb-1 inline-block rounded-full bg-daun-400/15 px-2.5 py-0.5 text-xs font-bold text-emas-400">{{ item.kategori || 'Dokumentasi' }}</span>
              <h3 class="text-sm font-extrabold text-krem-100 transition-colors group-hover:text-emas-400">{{ item.title || 'Tanpa judul' }}</h3>
              <p v-if="item.description" class="mt-1 line-clamp-2 text-xs leading-relaxed font-medium text-krem-300/70">{{ item.description }}</p>
            </div>
          </button>
        </div>

        <button
          v-if="gallery.length > 1"
          @click="scrollGallery('left')"
          :disabled="galleryScrollLeft <= 0"
          class="guest-gallery-nav left-2"
          aria-label="Gulir kiri"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
        </button>
        <button
          v-if="gallery.length > 1"
          @click="scrollGallery('right')"
          :disabled="galleryScrollRight <= 0"
          class="guest-gallery-nav right-2"
          aria-label="Gulir kanan"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
          </svg>
        </button>
      </div>
    </ScrollReveal>
  </section>
  <div v-else id="dokumentasi-skeleton" class="guest-section scroll-mt-24">
    <div class="mb-8 flex items-end justify-between">
      <div class="flex flex-col gap-2">
        <SkeletonLoader class="h-4 w-32" />
        <SkeletonLoader class="mt-1 h-8 w-48" />
        <SkeletonLoader class="mt-1 h-4 w-64" />
      </div>
    </div>
    <div class="grid gap-4">
      <div v-for="i in 4" :key="i" class="guest-gallery-item">
        <div class="relative h-52 overflow-hidden bg-hutan-700/50 rounded-xl">
          <SkeletonLoader class="h-full w-full" />
        </div>
        <div class="p-4">
          <SkeletonLoader class="h-4 w-24 mb-1" />
          <SkeletonLoader class="h-4 w-40 mt-1" />
          <SkeletonLoader class="h-4 w-56 mt-1 line-clamp-2" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import ScrollReveal from '@/Components/ScrollReveal.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';

const props = defineProps({
  gallery: { type: Array, default: () => [] },
  isLoading: { type: Boolean, default: false },
});

const emit = defineEmits(['open-lightbox', 'navigate-lightbox']);

const galleryContainer = ref(null);
const galleryScrollLeft = ref(0);
const galleryScrollRight = ref(0);

function updateGalleryScrollState() {
  if (!galleryContainer.value) return;
  const { scrollLeft, scrollWidth, clientWidth } = galleryContainer.value;
  galleryScrollLeft.value = scrollLeft;
  galleryScrollRight.value = Math.max(0, scrollWidth - clientWidth - scrollLeft);
}

function scrollGallery(direction) {
  if (!galleryContainer.value) return;
  const { scrollWidth, clientWidth } = galleryContainer.value;
  const scrollAmount = Math.min(300, clientWidth * 0.8);
  galleryContainer.value.scrollBy({
    left: direction === 'right' ? scrollAmount : -scrollAmount,
    behavior: 'smooth',
  });
  setTimeout(updateGalleryScrollState, 400);
}

function openLightbox(idx) {
  emit('open-lightbox', idx);
}

onMounted(() => {
  galleryContainer.value?.addEventListener('scroll', updateGalleryScrollState, { passive: true });
  nextTick(() => {
    updateGalleryScrollState();
  });
});

onUnmounted(() => {
  galleryContainer.value?.removeEventListener('scroll', updateGalleryScrollState);
});

watch(
  () => props.gallery.length,
  () => nextTick(updateGalleryScrollState),
);
</script>
