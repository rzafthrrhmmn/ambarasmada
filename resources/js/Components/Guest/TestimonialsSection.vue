<template>
  <section :id="sectionId || 'testimoni'" class="guest-section scroll-mt-24" aria-label="Testimoni pengguna">
    <div class="mb-8 sm:mb-12 text-center">
      <ScrollReveal animation="fade-up">
        <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
          <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse" aria-hidden="true"></span>
          Testimoni
        </div>
      </ScrollReveal>
      <ScrollReveal animation="fade-up" :delay="100">
        <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl lg:text-5xl">
          <span class="guest-text-gradient">Apa Kata</span>
          <span class="guest-text-gradient-gold"> Mereka</span>
        </h2>
      </ScrollReveal>
    </div>

    <ScrollReveal animation="fade-up" :delay="150">
      <TestimonialsCarousel :testimonials="testimonials" :auto-play="true" :interval="5000" />
    </ScrollReveal>
  </section>
</template>

<script setup>
import TestimonialsCarousel from '@/Components/TestimonialsCarousel.vue';
import ScrollReveal from '@/Components/ScrollReveal.vue';

const props = defineProps({
  testimonials: { type: Array, default: () => [] },
  sectionId: { type: String, default: '' },
});
</script>
