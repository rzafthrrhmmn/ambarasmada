<template>
  <section id="fitur" class="guest-section scroll-mt-24">
    <div class="mb-8 sm:mb-12 text-center">
      <ScrollReveal animation="fade-up">
        <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
          <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse"></span>
          Layanan Unggulan
        </div>
      </ScrollReveal>
      <ScrollReveal animation="fade-up" :delay="100">
        <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl lg:text-5xl">
          <span class="guest-text-gradient">Sistem Informasi</span>
          <span class="guest-text-gradient-gold"> Terintegrasi</span>
        </h2>
        <p class="mx-auto mt-4 max-w-lg text-sm font-medium text-krem-300/80 sm:text-base">
          Lima layanan digital yang saling terhubung untuk mendukung operasional ambalan secara efisien.
        </p>
      </ScrollReveal>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <ScrollReveal v-for="(feature, idx) in features" :key="idx" :delay="idx * 100" :stagger="true" :style="`--stagger-index: ${idx}`" animation="fade-up">
        <div class="guest-feature-card group">
          <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-daun-400/5 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
          <div class="relative flex items-start gap-5">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-hutan-800 to-hutan-600 border-2 border-daun-500/30 text-emas-400 shadow-lg shadow-daun-500/10 transition-all duration-300 group-hover:rotate-[5deg] group-hover:border-emas-400/50 group-hover:shadow-xl group-hover:shadow-daun-500/20">
              <AppIcon :name="feature.iconName" class="h-7 w-7 transition-transform duration-300 group-hover:scale-110" />
            </div>
            <div>
              <h3 class="text-xl font-extrabold text-krem-100 transition-colors duration-300 group-hover:text-emas-400">{{ feature.title }}</h3>
              <p class="mt-2 text-sm leading-relaxed font-medium text-krem-300/80">{{ feature.description }}</p>
            </div>
          </div>
          <div class="absolute bottom-0 right-0 h-24 w-24 rounded-full bg-daun-400/5 blur-2xl transition-all duration-500 group-hover:bg-daun-400/10 group-hover:scale-150"></div>
        </div>
      </ScrollReveal>
    </div>
  </section>
</template>

<script setup>
import ScrollReveal from '@/Components/ScrollReveal.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  features: { type: Array, default: () => [] },
});
</script>
