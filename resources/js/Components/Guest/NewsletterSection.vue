<template>
  <section id="newsletter" class="guest-section scroll-mt-24">
    <ScrollReveal animation="fade-up">
      <div class="mx-auto max-w-2xl rounded-3xl border-2 border-daun-500/30 bg-hutan-600/60 p-8 text-center shadow-lg backdrop-blur-sm sm:p-10">
        <h2 class="text-2xl font-black text-krem-100 sm:text-3xl">Dapatkan Kabar Terbaru</h2>
        <p class="mt-2 text-sm font-medium text-krem-300/80">
          Berlangganan buletin kami untuk kabar kegiatan, pengumuman, dan update sistem.
        </p>
        <form @submit.prevent="emit('subscribe')" class="mt-6 flex flex-col gap-3 sm:flex-row">
          <input
            :value="newsletterEmail"
            @input="emit('update:newsletterEmail', $event.target.value)"
            type="email"
            required
            placeholder="email@contoh.com"
            class="guest-focus-ring flex-1 rounded-xl border-2 border-daun-500/40 bg-hutan-800 px-4 py-3 text-sm font-semibold text-krem-100 outline-none transition focus:border-emas-400 focus:shadow-lg focus:shadow-emas-400/10"
          />
          <button type="submit" :disabled="newsletterLoading" class="btn-primary rounded-xl px-6 py-3 text-sm font-black disabled:opacity-60 disabled:cursor-not-allowed">
            <span v-if="newsletterLoading">Mengirim…</span>
            <span v-else>Berlangganan</span>
          </button>
        </form>
        <p v-if="newsletterSubscribed" class="mt-3 text-xs font-bold text-daun-400">Terima kasih, Anda berhasil berlangganan.</p>
        <p v-if="newsletterError" class="mt-3 text-xs font-bold text-[#ef4419]">{{ newsletterError }}</p>
      </div>
    </ScrollReveal>
  </section>
</template>

<script setup>
import ScrollReveal from '@/Components/ScrollReveal.vue';

const props = defineProps({
  newsletterEmail: { type: String, default: '' },
  newsletterSubscribed: { type: Boolean, default: false },
  newsletterLoading: { type: Boolean, default: false },
  newsletterError: { type: String, default: '' },
});

const emit = defineEmits(['update:newsletterEmail', 'subscribe', 'newsletter-subscribed', 'newsletter-error']);
</script>
