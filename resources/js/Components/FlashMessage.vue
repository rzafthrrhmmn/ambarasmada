<template>
  <transition name="slide-down">
    <div v-if="showFlash" class="fixed top-4 left-1/2 z-50 flex -translate-x-1/2 w-full max-w-md px-4">
      <div
        :class="[
          'w-full rounded-lg border-2 px-4 py-3 shadow-lg text-sm',
          type === 'success'
            ? 'bg-[#263D26] text-[#EDD330] border-[#EDD330]/50'
            : 'bg-[#263D26] text-[#ef4419] border-[#ef4419]/50',
        ]">
        <div class="flex items-start gap-3">
          <div class="mt-0.5 flex-shrink-0">
            <svg v-if="type === 'success'" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 0 1 0 1.414l-8.5 8.5a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414l.707-.707a1 1 0 0 1 1.414 0L8 12.586l7.596-7.596a1 1 0 0 1 1.111.007Z"
                clip-rule="evenodd" />
            </svg>
            <svg v-else class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414 0L4.293 11.414a1 1 0 1 1 0-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z"
                clip-rule="evenodd" />
            </svg>
          </div>
          <div class="flex-1 leading-tight">
            <span class="font-bold">{{ flashMessage }}</span>
          </div>
          <button @click="showFlash = false" class="flex-shrink-0 rounded p-1 hover:bg-[#EDD330]/10">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const showFlash = ref(false);
const flashMessage = ref('');
const type = ref('success');

const props = defineProps({
  persistent: { type: Boolean, default: false },
});

const HIDE_DELAY = 8000;
const ERROR_KEYS = ['error', 'danger'];

let timer = null;

/**
 * Pilih pesan pertama yang benar-benar punya isi.
 *
 * Key flash yang kosong (null atau '') dulu tetap dianggap sebagai pesan, jadi
 * setiap kali halaman dimuat, termasuk setelah refresh, muncul kotak notifikasi
 * tanpa teks. Kotak tanpa teks tidak berguna, jadi key kosong dilewati dan pesan
 * pertama yang ada teksnya yang ditampilkan.
 */
function pickMessage(flash) {
  if (!flash || typeof flash !== 'object') {
    return null;
  }

  const keys = ERROR_KEYS.some((key) => key in flash)
    ? [...ERROR_KEYS, 'warning', 'info', 'success']
    : ['success', 'info', 'warning', ...ERROR_KEYS];

  for (const key of keys) {
    const value = typeof flash[key] === 'string' ? flash[key].trim() : flash[key];

    if (value !== '' && value !== null && value !== undefined && value !== false) {
      return {
        message: String(value).trim(),
        type: ERROR_KEYS.includes(key) ? 'error' : 'success',
      };
    }
  }

  return null;
}

function startTimer() {
  if (timer) {
    clearTimeout(timer);
  }

  if (props.persistent) {
    return;
  }

  timer = setTimeout(() => {
    showFlash.value = false;
  }, HIDE_DELAY);
}

watch(
  () => page.props.flash,
  (flash) => {
    const picked = pickMessage(flash);

    if (!picked) {
      return;
    }

    flashMessage.value = picked.message;
    type.value = picked.type;
    showFlash.value = true;
    startTimer();
  },
  { immediate: true },
);

watch(
  () => page.props.errors,
  (errors) => {
    if (errors && Object.keys(errors).length > 0) {
      flashMessage.value = 'Harap perbaiki kesalahan pada formulir.';
      type.value = 'error';
      showFlash.value = true;
      startTimer();
    }
  },
  { immediate: true },
);

onBeforeUnmount(() => {
  if (timer) {
    clearTimeout(timer);
  }
});
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>