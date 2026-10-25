<template>
  <header class="mb-10 sm:mb-16" data-parallax-hero>
    <section v-if="sliderSlides.length > 0" class="mb-10">
      <PhotoSlider :slides="sliderSlides" :interval="6000" />
    </section>

    <div class="flex flex-col items-center text-center">
      <ScrollReveal animation="zoom-in" :delay="100">
        <div class="group relative mb-4 flex h-28 w-28 items-center justify-center rounded-3xl border-2 border-daun-500/50 bg-hutan-800/80 shadow-2xl shadow-daun-400/20 transition-all duration-500 hover:rotate-[5deg] hover:scale-105 sm:h-44 sm:w-44">
          <img
            :src="logoUrl"
            alt="Logo Ambalan UPT SMAN 2 Maros"
            class="guest-logo guest-logo-lg"
            @error="e => { e.target.onerror = null; e.target.src = '/images/Logo_Ambalan.png'; }"
          />
          <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 opacity-0 blur transition-opacity duration-500 group-hover:opacity-60"></div>
        </div>
      </ScrollReveal>

      <ScrollReveal animation="fade-up" :delay="200">
        <h1 class="text-3xl font-black text-transparent sm:text-5xl lg:text-6xl xl:text-7xl mb-3">
          <span class="guest-text-gradient">Satya dan Darma</span>
          <br class="sm:hidden" />
          <span class="relative inline-block guest-text-gradient-gold"> dalam satu genggaman.</span>
        </h1>
      </ScrollReveal>

      <ScrollReveal animation="fade-up" :delay="300">
        <p class="mx-auto mt-3 max-w-2xl text-base leading-relaxed font-medium text-krem-300/80 sm:text-xl">
          Ekosistem digital untuk anggota, pembina, pengurus, dan alumni. Pantau SKU, presensi, kas, inventaris, dan kabar ambalan dengan lebih tertib.
        </p>
      </ScrollReveal>

      <ScrollReveal animation="fade-up" :delay="400">
        <div class="mt-6 flex flex-col sm:flex-row flex-wrap justify-center gap-3">
          <Link
            href="/register"
            class="guest-focus-ring group relative isolate overflow-hidden rounded-2xl bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 px-6 py-3.5 text-sm font-black text-hutan-800 shadow-xl shadow-emas-400/30 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-emas-400/40 active:scale-95 before:absolute before:inset-0 before:bg-white before:opacity-0 before:transition-opacity before:duration-300 hover:before:opacity-20 w-full sm:w-auto"
          >
            <span class="relative z-10 flex items-center gap-2">
              Bergabung Sekarang
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4 transition group-hover:translate-x-1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </span>
          </Link>
          <Link
            href="/login"
            class="guest-focus-ring rounded-2xl border-2 border-daun-500 bg-hutan-800/40 px-6 py-3.5 text-sm font-bold text-krem-300 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-emas-400 hover:bg-daun-500/20 hover:text-emas-400 hover:shadow-lg hover:shadow-daun-500/20 active:scale-95 w-full sm:w-auto"
          >
            Masuk
          </Link>
        </div>
      </ScrollReveal>
    </div>
  </header>
</template>

<script setup>
import PhotoSlider from '@/Components/PhotoSlider.vue';
import ScrollReveal from '@/Components/ScrollReveal.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  sliderSlides: { type: Array, default: () => [] },
  logoUrl: { type: String, default: '/images/Logo_Ambalan.png' },
  ambalanNama: { type: String, default: 'Ambalan UPT SMAN 2 Maros' },
  isMobile: { type: Boolean, default: false },
});
</script>
