<template>
  <section :id="sectionId || 'cara-kerja'" class="guest-section scroll-mt-24" aria-label="Cara kerja ambalan digital">
    <div class="mb-8 sm:mb-12 text-center">
      <ScrollReveal animation="fade-up">
        <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
          <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse" aria-hidden="true"></span>
          Cara Kerja
        </div>
      </ScrollReveal>
      <ScrollReveal animation="fade-up" :delay="100">
        <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl lg:text-5xl">
          <span class="guest-text-gradient">Mulai dalam</span>
          <span class="guest-text-gradient-gold"> 3 Langkah</span>
        </h2>
      </ScrollReveal>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <ScrollReveal
        v-for="(step, idx) in steps"
        :key="`step-${idx}`"
        :delay="idx * 150"
        :stagger="true"
        :style="`--stagger-index: ${idx}`"
        animation="fade-up"
      >
        <div
          class="relative flex h-full flex-col items-center rounded-3xl border-2 border-daun-500/30 bg-hutan-600/60 p-6 text-center shadow-lg transition-all duration-300 hover:-translate-y-2 hover:border-daun-400/60 hover:bg-hutan-600/80 focus-visible:ring-2 focus-visible:ring-emas-400 focus-visible:outline-none"
        >
          <div
            class="absolute -top-4 left-1/2 -translate-x-1/2 flex h-8 w-8 items-center justify-center rounded-full border-2 border-daun-400 bg-hutan-800 text-sm font-black text-emas-400"
            aria-hidden="true"
          >
            {{ idx + 1 }}
          </div>
          <div class="mt-4 mb-4 flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-daun-500/30 bg-hutan-800 transition-transform hover:rotate-6">
            <AppIcon :name="step.iconName" class="h-8 w-8 text-emas-400" aria-hidden="true" />
          </div>
          <h3 class="text-xl font-extrabold text-krem-100">{{ step.title }}</h3>
          <p class="mt-3 text-sm leading-relaxed font-medium text-krem-300/80">{{ step.description }}</p>
        </div>
      </ScrollReveal>
    </div>
  </section>
</template>

<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import ScrollReveal from '@/Components/ScrollReveal.vue';

const props = defineProps({
  steps: { type: Array, default: () => [] },
  sectionId: { type: String, default: '' },
});

</script>

<style scoped>
.guest-focus-ring {
  outline: none;
}
</style>
