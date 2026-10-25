<template>
  <section v-if="gallery.length > 0" id="dokumentasi" class="guest-section scroll-mt-24" aria-label="Galeri dokumentasi kegiatan">
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
        <Link
          href="/galleries"
          class="text-sm font-bold text-daun-400 opacity-70 transition-opacity hover:opacity-100 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400 rounded"
        >
          Lihat semua &rarr;
        </Link>
      </ScrollReveal>
    </div>

    <ScrollReveal animation="fade-up" :delay="150">
      <div class="relative">
        <div
          ref="galleryContainer"
          class="guest-gallery-track overflow-x-auto scroll-smooth hide-scrollbar"
          @scroll="onGalleryScroll"
          @touchstart="onTouchStart"
          @touchmove="onTouchMove"
          @touchend="onTouchEnd"
        >
          <button
            v-for="(item, idx) in gallery"
            :key="`gallery-item-${item.id}`"
            class="guest-gallery-item group block"
            style="width: min(300px, 90vw);"
            @click="openLightbox(idx)"
            :aria-label="`Lihat ${item.title || 'foto'}`"
            tabindex="0"
            focus-visible:outline-none
            focus-visible:ring-2
            focus-visible:ring-emas-400
            role="button"
          >
            <div class="relative h-52 overflow-hidden">
              <img
                v-if="item.src"
                :src="item.src"
                :alt="item.title || 'Foto dokumentasi'"
                class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                loading="lazy"
                :width="300"
                :height="200"
                decoding="async"
                @error="onImageError"
              />
              <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-hutan-800 to-hutan-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-lumut-400" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.25-5.25a2.25 2.25 0 013 0l3.75 3.75M9.75 12.75l.75.75m0 0l.75.75m-.75-.75v-6.75m-.75 6.75h6" />
                </svg>
              </div>
              <div class="absolute inset-0 bg-gradient-to-t from-hutan-800/95 via-hutan-800/20 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" aria-hidden="true"></div>
              <div class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100" aria-hidden="true">
                <div class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-emas-400 bg-hutan-800/90 text-emas-400 backdrop-blur-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
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

        <!-- Gallery navigation buttons -->
        <button
          v-if="gallery.length > 1"
          @click="scrollGallery('left')"
          :disabled="galleryScrollLeft <= 0"
          class="guest-gallery-nav left-2 absolute left-2 top-1/2 -translate-y-1/2 z-10"
          aria-label="Gulir galeri ke kiri"
          focus-visible:outline-none
          focus-visible:ring-2
          focus-visible:ring-emas-400
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
        </button>
        <button
          v-if="gallery.length > 1"
          @click="scrollGallery('right')"
          :disabled="galleryScrollRight <= 0"
          class="guest-gallery-nav right-2 absolute right-2 top-1/2 -translate-y-1/2 z-10"
          aria-label="Gulir galeri ke kanan"
          focus-visible:outline-none
          focus-visible:ring-2
          focus-visible:ring-emas-400
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
          </svg>
        </button>
      </div>
    </ScrollReveal>
  </section>

  <!-- Lightbox Modal -->
  <LightboxModal
    v-model:open="lightboxOpen"
    :items="gallery"
    :current-index="lightboxIndex"
    @close="lightboxOpen = false"
    @navigate="onLightboxNavigate"
  />
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import LightboxModal from '@/Components/LightboxModal.vue';
import ScrollReveal from '@/Components/ScrollReveal.vue';

const props = defineProps({
  gallery: { type: Array, default: () => [] },
});

const emit = defineEmits(['gallery-item-click']);

const galleryContainer = ref(null);
const galleryScrollLeft = ref(0);
const galleryScrollRight = ref(0);
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);
let touchStartX = 0;

function onGalleryScroll() {
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
  setTimeout(() => {
    onGalleryScroll();
  }, 400);
}

function openLightbox(idx) {
  lightboxIndex.value = idx;
  lightboxOpen.value = true;
  emit('gallery-item-click', idx);
}

function onLightboxNavigate(payload) {
  const len = gallery.length;
  if (len === 0) return;

  if (typeof payload === 'number') {
    lightboxIndex.value = Math.max(0, Math.min(payload, len - 1));
  } else if (payload === 'prev') {
    lightboxIndex.value = (lightboxIndex.value - 1 + len) % len;
  } else if (payload === 'next') {
    lightboxIndex.value = (lightboxIndex.value + 1) % len;
  }
}

function onTouchStart(e) {
  touchStartX = e.touches[0].clientX;
}

function onTouchMove(e) {
  if (!galleryContainer.value) return;
  const diff = touchStartX - e.touches[0].clientX;
  if (Math.abs(diff) > 50) {
    if (diff > 0) {
      scrollGallery('right');
    } else {
      scrollGallery('left');
    }
    touchStartX = e.touches[0].clientX;
  }
}

function onTouchEnd() {
  touchStartX = 0;
}

function onImageError(event) {
  event.target.onerror = null;
  event.target.src = '/images/Logo_Ambalan.png';
}
</script>

<style scoped>
.guest-focus-ring {
  outline: none;
}
</style>
