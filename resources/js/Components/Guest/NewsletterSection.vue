<template>
  <section id="newsletter" class="guest-section scroll-mt-24" aria-label="Langganan buletin">
    <ScrollReveal animation="fade-up">
      <div class="mx-auto max-w-2xl rounded-3xl border-2 border-daun-500/30 bg-hutan-600/60 p-8 text-center shadow-lg backdrop-blur-sm sm:p-10">
        <h2 class="text-2xl font-black text-krem-100 sm:text-3xl">Dapatkan Kabar Terbaru</h2>
        <p class="mt-2 text-sm font-medium text-krem-300/80">
          Berlangganan buletin kami untuk kabar kegiatan, pengumuman, dan update sistem.
        </p>

        <form @submit.prevent="handleSubmit" class="mt-6 flex flex-col gap-3 sm:flex-row" novalidate>
          <input
            v-model="newsletterEmail"
            type="email"
            required
            placeholder="email@contoh.com"
            :aria-invalid="newsletterError ? 'true' : 'false'"
            :aria-describedby="newsletterError ? 'newsletter-error' : undefined"
            class="guest-focus-ring flex-1 rounded-xl border-2 border-daun-500/40 bg-hutan-800 px-4 py-3 text-sm font-semibold text-krem-100 outline-none transition focus:border-emas-400 focus:shadow-lg focus:shadow-emas-400/10"
          />
          <button
            type="submit"
            :disabled="newsletterLoading"
            :aria-busy="newsletterLoading"
            class="btn-primary rounded-xl px-6 py-3 text-sm font-black transition-colors duration-200 disabled:opacity-60 disabled:cursor-not-allowed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
          >
            <span v-if="newsletterLoading" class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-4 w-4 animate-spin">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity="0.25"></circle>
                <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Mengirim…
            </span>
            <span v-else>Berlangganan</span>
          </button>
        </form>

        <div v-if="newsletterSubscribed" class="mt-3 flex items-center justify-center gap-2 text-xs font-bold text-daun-400" role="status" aria-live="polite">
          <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <span>Terima kasih, Anda berhasil berlangganan.</span>
        </div>
        <div v-else-if="newsletterError" class="mt-3 flex items-center justify-center gap-2 text-xs font-bold text-[#ef4419]" role="alert" aria-live="assertive" id="newsletter-error">
          <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span>{{ newsletterError }}</span>
        </div>
      </div>
    </ScrollReveal>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import ScrollReveal from '@/Components/ScrollReveal.vue';

const props = defineProps({
  initialEmail: { type: String, default: '' },
});

const emit = defineEmits(['subscribe']);

const newsletterEmail = ref(props.initialEmail || '');
const newsletterSubscribed = ref(false);
const newsletterLoading = ref(false);
const newsletterError = ref('');

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

async function handleSubmit() {
  newsletterError.value = '';

  if (!newsletterEmail.value.trim()) {
    newsletterError.value = 'Email tidak boleh kosong.';
    return;
  }

  if (!EMAIL_REGEX.test(newsletterEmail.value)) {
    newsletterError.value = 'Format email tidak valid.';
    return;
  }

  newsletterLoading.value = true;

  try {
    const res = await fetch('/v1/guest/newsletter', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ email: newsletterEmail.value.trim() }),
    });

    const payload = await res.json().catch(() => ({}));

    if (!res.ok) {
      throw new Error(payload.message || `Permintaan gagal (HTTP ${res.status})`);
    }

    newsletterSubscribed.value = true;
    newsletterEmail.value = '';
    emit('subscribe', newsletterEmail.value);
  } catch (err) {
    newsletterError.value = err instanceof Error ? err.message : 'Tidak dapat mengirimkan langganan.';
  } finally {
    newsletterLoading.value = false;
  }
}
</script>

<style scoped>
.guest-focus-ring {
  outline: none;
}
</style>
