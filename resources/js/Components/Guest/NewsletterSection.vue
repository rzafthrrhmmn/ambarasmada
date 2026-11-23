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
              <AppIcon name="spinner" class="h-4 w-4 animate-spin" aria-hidden="true" />
              Mengirim…
            </span>
            <span v-else>Berlangganan</span>
          </button>
        </form>

        <div v-if="newsletterSubscribed" class="mt-3 flex items-center justify-center gap-2 text-xs font-bold text-daun-400" role="status" aria-live="polite">
          <AppIcon name="checkCircle" class="h-3 w-3" aria-hidden="true" />
          <span>Terima kasih, Anda berhasil berlangganan.</span>
        </div>
        <div v-else-if="newsletterError" class="mt-3 flex items-center justify-center gap-2 text-xs font-bold text-[#ef4419]" role="alert" aria-live="assertive" id="newsletter-error">
          <AppIcon name="xCircle" class="h-3 w-3" aria-hidden="true" />
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
