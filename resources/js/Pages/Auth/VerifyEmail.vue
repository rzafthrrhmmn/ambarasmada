<template>
  <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-[#263D26] via-[#2d4a2d] to-[#263D26] px-4 py-8 md:py-0">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
      <div class="animate-float absolute left-10 top-20 h-72 w-72 rounded-full bg-[#A7B92A]/10 blur-3xl animate-pulse-glow"></div>
      <div class="animate-float absolute right-10 top-1/3 h-60 w-60 rounded-full bg-[#EDD330]/8 blur-3xl animate-pulse-glow" style="animation-delay: 2s;"></div>
      <div class="animate-float absolute bottom-20 left-1/3 h-56 w-56 rounded-full bg-[#6F9435]/10 blur-3xl animate-pulse-glow" style="animation-delay: 4s;"></div>
    </div>

    <div class="relative grid w-full max-w-5xl overflow-hidden rounded-3xl border-2 border-[#A7B92A]/30 bg-[#335233]/80 shadow-2xl shadow-black/40 backdrop-blur-sm md:grid-cols-5 min-h-[600px]">
      <div class="relative col-span-2 hidden flex-col justify-center gap-6 bg-gradient-to-br from-[#263D26] to-[#2d4a2d] p-10 md:flex">
        <div class="animate-float">
          <div class="flex items-center justify-center gap-3">
            <div class="flex h-20 w-20 items-center justify-center rounded-2xl border-2 border-[#6F9435] bg-[#263D26] overflow-hidden shadow-lg shadow-[#A7B92A]/20">
              <img v-if="$page.props.ambalan?.logo_url" :src="$page.props.ambalan.logo_url" alt="Logo" class="h-full w-full object-contain" />
              <img v-else :src="'/images/Logo_Ambalan.png'" alt="Logo" class="h-full w-full object-contain" />
            </div>
          </div>
          <p class="mt-5 text-center text-sm font-bold tracking-wide text-[#EDD330]">{{ $page.props.ambalan?.nama || "Ambalan UPT SMAN 2 Maros" }}</p>
        </div>
        <div>
          <h2 class="text-3xl font-black text-[#f0ead8]" style="text-shadow: 2px 2px 0 rgba(0,0,0,0.3);">Verifikasi Email</h2>
          <p class="mt-2 text-base font-medium leading-relaxed text-[#d4dc9a]/80">Silakan verifikasi alamat email Anda untuk mengakses sistem.</p>
        </div>
        <div class="space-y-3">
          <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#A7B92A]/20">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4 text-[#EDD330]"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
            </div>
            <span class="text-sm font-medium text-[#d4dc9a]">Aktivasi Akun</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#A7B92A]/20">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4 text-[#EDD330]"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
            </div>
            <span class="text-sm font-medium text-[#d4dc9a]">Keamanan Terjamin</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#A7B92A]/20">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4 text-[#EDD330]"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
            </div>
            <span class="text-sm font-medium text-[#d4dc9a]">Akses Cepat</span>
          </div>
        </div>
        <div class="mt-4 rounded-xl border border-[#A7B92A]/20 bg-[#263D26]/50 p-4 text-center">
          <p class="text-xs font-bold text-[#8fa06a]">Akses mudah di ponsel</p>
          <p class="mt-1 text-2xl font-black text-[#EDD330]">PWA</p>
        </div>
      </div>

      <div class="col-span-3 flex flex-col justify-center p-6 md:p-10">
        <div class="w-full max-w-md mx-auto">
          <div class="mb-8 md:hidden">
            <div class="flex items-center justify-center gap-3">
              <div class="flex h-14 w-14 items-center justify-center rounded-xl border-2 border-[#6F9435] bg-[#263D26] overflow-hidden">
                <img v-if="$page.props.ambalan?.logo_url" :src="$page.props.ambalan.logo_url" alt="Logo" class="h-full w-full object-contain" />
                <img v-else :src="'/images/Logo_Ambalan.png'" alt="Logo" class="h-full w-full object-contain" />
              </div>
              <p class="text-sm font-bold text-[#EDD330]">{{ $page.props.ambalan?.nama || "Ambalan UPT SMAN 2 Maros" }}</p>
            </div>
          </div>

          <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg border border-[#6F9435] bg-[#6F9435]/20 p-3 text-sm font-medium text-[#EDD330]">
            {{ $page.props.flash.success }}
          </div>
          <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg border border-[#ef4419] bg-[#ef4419]/20 p-3 text-sm font-medium text-[#fca5a5]">
            {{ $page.props.flash.error }}
          </div>

<div class="mb-7 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#A7B92A]/20">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-8 w-8 text-[#EDD330]"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 011.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
            </div>
            <p class="text-3xl font-black text-[#f0ead8]" style="text-shadow: 2px 2px 0 rgba(0,0,0,0.3);">Verifikasi Email</p>
            <p class="mt-2 text-sm font-medium text-[#8fa06a]">
              Kami telah mengirim tautan aktivasi ke
              <span v-if="email" class="font-bold text-[#EDD330]">{{ email }}</span>
              <span v-else>alamat email Anda</span>.
              Silakan cek kotak masuk (dan folder spam).
            </p>
          </div>

          <div class="mb-5 space-y-2 rounded-xl border-2 border-[#A7B92A]/30 bg-[#263D26]/50 p-4 text-sm">
            <p class="font-bold text-[#EDD330]">Cara mengaktifkan akun:</p>
            <ol class="list-inside list-decimal space-y-1 text-[#d4dc9a]">
              <li>Buka email yang kami kirim dan salin tautannya.</li>
              <li>Pastikan Anda sudah masuk ke akun <span v-if="email" class="font-bold text-[#EDD330]">{{ email }}</span><span v-else>Anda</span> di browser ini.</li>
              <li v-if="nta">Nomor tanda anggota Anda: <span class="font-mono font-bold text-[#EDD330]">{{ nta }}</span></li>
              <li>Buka tautan tersebut &mdash; akun aktif seketika.</li>
            </ol>
            <p v-if="linkExpiryMinutes" class="pt-1 text-xs text-[#8fa06a]">
              Tautan berlaku {{ linkExpiryMinutes }} menit. Belum masuk? Tekan tombol di bawah untuk meminta tautan baru.
            </p>
          </div>

          <form @submit.prevent="resendVerification" class="space-y-4">
            <button type="submit" :disabled="form.processing || cooldown > 0" :class="['group relative w-full overflow-hidden rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#EDD330] px-4 py-3 font-extrabold text-[#263D26] shadow-lg shadow-[#EDD330]/30 transition hover:shadow-xl hover:shadow-[#EDD330]/40 hover:-translate-y-0.5 disabled:cursor-wait disabled:opacity-60 border-2 border-[#EDD330]', (form.processing || cooldown > 0) ? 'animate-pulse shadow-[#EDD330]/50' : '']">
              <span class="relative z-10 flex items-center justify-center gap-2">
                <svg v-if="form.processing" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-5 w-5 animate-spin text-[#263D26]">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity="0.25"></circle>
                  <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-if="form.processing">
                  Mengirim...
                  <span class="inline-flex gap-0.5">
                    <span class="animate-bounce inline-block h-1.5 w-1.5 rounded-full bg-[#263D26]" style="animation-delay: 0ms;"></span>
                    <span class="animate-bounce inline-block h-1.5 w-1.5 rounded-full bg-[#263D26]" style="animation-delay: 150ms;"></span>
                    <span class="animate-bounce inline-block h-1.5 w-1.5 rounded-full bg-[#263D26]" style="animation-delay: 300ms;"></span>
                  </span>
                </span>
                <span v-else-if="cooldown > 0">Tunggu {{ cooldown }} detik...</span>
                <span v-else>Kirim Ulang Verifikasi</span>
                <svg v-if="!form.processing && cooldown === 0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4 transition group-hover:translate-x-1"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
              </span>
            </button>
          </form>

          <div class="mt-5 border-t-2 border-[#6F9435]/20 pt-5 text-center">
            <button type="button" @click="logout" class="group inline-flex items-center gap-2 rounded-lg border-2 border-[#6F9435] px-6 py-2.5 text-sm font-bold text-[#d4dc9a] transition hover:-translate-y-0.5 hover:bg-[#6F9435]/20 hover:text-[#EDD330] hover:shadow-lg hover:shadow-[#6F9435]/20">
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

function logout() {
  router.post('/logout');
}

onMounted(() => startCooldown());
onBeforeUnmount(() => {
  if (timer) clearInterval(timer);
});
</script>