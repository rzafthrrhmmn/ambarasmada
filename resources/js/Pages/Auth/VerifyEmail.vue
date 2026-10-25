<template>
  <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-hutan-800 via-hutan-700 to-hutan-800 px-4 py-8 md:py-0">
    <div class="relative grid w-full max-w-5xl overflow-hidden rounded-3xl border-2 border-daun-400/30 bg-hutan-600/80 shadow-2xl shadow-black/40 backdrop-blur-sm md:grid-cols-5 min-h-[600px]">
      <div class="relative col-span-2 hidden flex-col justify-center gap-6 bg-gradient-to-br from-hutan-800 to-hutan-700 p-10 md:flex">
        <div class="flex items-center justify-center gap-3">
          <div class="flex h-20 w-20 items-center justify-center rounded-2xl border-2 border-daun-500 bg-hutan-800 overflow-hidden shadow-lg shadow-daun-400/20">
            <img v-if="$page.props.ambalan?.logo_url" :src="$page.props.ambalan.logo_url" alt="Logo" class="h-full w-full object-contain" />
            <img v-else :src="'/images/Logo_Ambalan.png'" alt="Logo" class="h-full w-full object-contain" />
          </div>
        </div>
        <h2 class="text-3xl font-black text-krem-100 drop-shadow-md text-center">Verifikasi Email</h2>
        <p class="mt-2 text-base font-medium leading-relaxed text-[#d4dc9a]/80 text-center">
          Silakan verifikasi alamat email Anda untuk mengakses sistem.
        </p>
      </div>

      <div class="col-span-3 flex flex-col justify-center p-6 md:p-10">
        <div class="w-full max-w-md mx-auto">
          <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg border border-[#6F9435] bg-[#6F9435]/20 p-3 text-sm font-medium text-[#EDD330]">
            {{ $page.props.flash.success }}
          </div>
          <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg border border-[#ef4419]/40 bg-[#ef4419]/20 p-3 text-sm font-medium text-[#fca5a5]">
            {{ $page.props.flash.error }}
          </div>

          <div class="mb-7 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-daun-400/20">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-8 w-8 text-emas-400"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 011.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
            </div>
            <p class="text-3xl font-black text-krem-100 drop-shadow-md">Verifikasi Email</p>
            <p class="mt-2 text-sm font-medium text-[#8fa06a]">
              Kami telah mengirim tautan aktivasi ke
              <span v-if="email" class="font-bold text-emas-400">{{ email }}</span>
              <span v-else>alamat email Anda</span>.
              Silakan cek kotak masuk (dan folder spam).
            </p>
          </div>

          <div class="mb-5 space-y-2 rounded-xl bg-daun-400/30 bg-hutan-800/50 p-4 text-sm">
            <p class="font-bold text-[#EDD330]">Cara mengaktifkan akun:</p>
            <ol class="list-inside list-decimal space-y-1 text-[#d4dc9a]">
              <li>Buka email yang kami kirim dan salin tautannya.</li>
              <li>Pastikan Anda sudah masuk di browser ini.</li>
              <li v-if="nta">Nomor anggota Anda: <span class="font-mono font-bold text-[#EDD330]">{{ nta }}</span></li>
              <li>Buka tautan tersebut — akun aktif seketika.</li>
            </ol>
            <p v-if="linkExpiryMinutes" class="pt-1 text-xs text-[#8fa06a]">
              Tautan berlaku {{ linkExpiryMinutes }} menit.
            </p>
          </div>

          <form @submit.prevent="resendVerification" class="space-y-4">
            <button
              type="submit"
              :disabled="form.processing || cooldown > 0"
              :class="['group relative w-full overflow-hidden rounded-lg bg-gradient-to-r from-daun-400 to-emas-400 px-4 py-3 font-extrabold text-hutan-800 shadow-lg shadow-emas-400/30 transition hover:shadow-xl hover:shadow-emas-400/40 hover:-translate-y-0.5 disabled:cursor-wait disabled:opacity-60 border-2 border-emas-400', (form.processing || cooldown > 0) ? 'animate-pulse' : '']"
            >
              <span class="relative z-10 flex items-center justify-center gap-2">
                <span v-if="form.processing">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-5 w-5 animate-spin text-hutan-800">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity="0.25"></circle>
                    <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Mengirim...
                </span>
                <span v-else-if="cooldown > 0">Tunggu {{ cooldown }} detik...</span>
                <span v-else>Kirim Ulang Verifikasi</span>
              </span>
            </button>
          </form>

          <div class="mt-5 border-t-2 border-[#6F9435]/20 pt-5 text-center">
            <button
              type="button"
              @click="logout"
              class="group inline-flex items-center gap-2 rounded-lg border-2 border-daun-500 px-6 py-2.5 text-sm font-bold text-[#d4dc9a] transition hover:-translate-y-0.5 hover:bg-daun-500/20 hover:text-emas-400 hover:shadow-lg hover:shadow-daun-500/20"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
              Ganti Akun
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
  email: { type: String, default: null },
  nta: { type: String, default: null },
  linkExpiryMinutes: { type: Number, default: null },
});

const COOLDOWN_SECONDS = 30;
const form = useForm({});
const cooldown = ref(0);
let timer = null;

function startCooldown() {
  cooldown.value = COOLDOWN_SECONDS;
  if (timer) clearInterval(timer);
  timer = setInterval(() => {
    cooldown.value -= 1;
    if (cooldown.value <= 0 && timer) {
      clearInterval(timer);
      timer = null;
    }
  }, 1000);
}

function resendVerification() {
  if (cooldown.value > 0) return;

  form.post('/email/verification-notification', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      startCooldown();
    },
    onError: () => {
      form.reset();
    },
  });
}

async function logout() {
  try {
    await router.post('/logout');
  } catch {
    window.location.href = '/';
  }
}

onMounted(() => startCooldown());
onBeforeUnmount(() => {
  if (timer) clearInterval(timer);
});
</script>