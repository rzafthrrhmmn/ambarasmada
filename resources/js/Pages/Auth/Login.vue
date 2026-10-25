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
        <h2 class="text-3xl font-black text-krem-100 drop-shadow-md text-center">Masuk ke Ambalan</h2>
        <p class="mt-2 text-base font-medium leading-relaxed text-[#d4dc9a]/80 text-center">
          Ekosistem digital untuk anggota, pembina, pengurus, dan alumni kepramukaan.
        </p>
      </div>

      <div class="col-span-3 flex flex-col justify-center p-6 md:p-10">
        <div class="w-full max-w-md mx-auto">
          <div class="mb-8 md:hidden flex items-center justify-center gap-3">
            <div class="flex h-14 w-14 items-center justify-center rounded-xl border-2 border-[#6F9435] bg-[#263D26] overflow-hidden">
              <img v-if="$page.props.ambalan?.logo_url" :src="$page.props.ambalan.logo_url" alt="Logo" class="h-full w-full object-contain" />
              <img v-else :src="'/images/Logo_Ambalan.png'" alt="Logo" class="h-full w-full object-contain" />
            </div>
            <p class="text-sm font-bold text-[#EDD330]">{{ $page.props.ambalan?.nama || "Ambalan UPT SMAN 2 Maros" }}</p>
          </div>

          <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg border border-[#6F9435] bg-[#6F9435]/20 p-3 text-sm font-medium text-[#f0ead8]">
            {{ $page.props.flash.success }}
          </div>
          <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg border border-[#ef4419]/40 bg-[#ef4419]/20 p-3 text-sm font-medium text-[#f0ead8]">
            {{ $page.props.flash.error }}
          </div>

          <div class="mb-7">
            <p class="text-sm font-bold tracking-widest text-daun-400 uppercase">Welcome Back</p>
            <h1 class="mt-2 text-3xl font-black text-krem-100 drop-shadow-md">Masuk</h1>
            <p class="mt-2 text-sm font-medium text-krem-300">
              Masuk untuk mengelola kegiatan kepramukaan.
            </p>
          </div>

          <form @submit.prevent="submitLogin" class="space-y-4">
            <div>
              <label class="block">
                <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Username atau email</span>
                <input
                  v-model="form.identity"
                  type="text"
                  required
                  autocomplete="username"
                  placeholder="Masukkan username atau email"
                  class="w-full rounded-lg border-2 border-daun-500 bg-hutan-800 px-4 py-3 text-sm text-krem-100 outline-none transition placeholder:text-krem-300/50 focus:border-emas-400 focus:ring-2 focus:ring-emas-400/50"
                />
              </label>
              <span v-if="$page.props.errors.identity" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.identity }}</span>
            </div>
            <div>
              <label class="block">
                <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Password</span>
                <input
                  v-model="form.password"
                  type="password"
                  required
                  autocomplete="current-password"
                  placeholder="Masukkan password"
                  class="w-full rounded-lg border-2 border-daun-500 bg-hutan-800 px-4 py-3 text-sm text-krem-100 outline-none transition placeholder:text-krem-300/50 focus:border-emas-400 focus:ring-2 focus:ring-emas-400/50"
                />
              </label>
              <div class="mt-1 flex items-center justify-between gap-2">
                <span v-if="$page.props.errors.password" class="text-xs font-bold text-[#ef4419]">{{ $page.props.errors.password }}</span>
                <a href="/forgot-password" class="text-xs font-bold text-emas-400 hover:underline">Lupa Sandi?</a>
              </div>
            </div>

            <label class="flex items-center gap-2 text-sm font-medium text-[#d4dc9a]">
              <input v-model="form.remember" type="checkbox" value="1" class="rounded border-2 border-daun-500 bg-hutan-800 text-emas-400" />
              <span class="cursor-pointer">Ingat saya</span>
            </label>

            <button
              type="submit"
              :disabled="form.processing"
              :class="['group relative w-full overflow-hidden rounded-lg bg-gradient-to-r from-daun-400 to-emas-400 px-4 py-3 font-extrabold text-hutan-800 shadow-lg shadow-emas-400/30 transition hover:shadow-xl hover:shadow-emas-400/40 hover:-translate-y-0.5 disabled:cursor-wait disabled:opacity-60 border-2 border-emas-400', form.processing ? 'animate-pulse' : '']"
            >
              <span class="relative z-10 flex items-center justify-center gap-2">
                <span v-if="form.processing" class="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-5 w-5 animate-spin text-hutan-800">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity="0.25"></circle>
                    <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Masuk...
                </span>
                <span v-else>Masuk</span>
              </span>
            </button>
          </form>

          <div class="mt-4 text-center">
            <p class="text-sm text-[#8fa06a]">
              Belum punya akun?
              <a href="/register" class="font-bold text-[#EDD330] hover:underline">Daftar Akun</a>
            </p>
          </div>

          <div class="mt-3 rounded-xl border-2 border-[#6F9435]/30 bg-[#263D26]/40 p-4">
            <button
              type="button"
              @click="showResend = !showResend"
              class="flex w-full items-center justify-center gap-2 text-left text-sm font-bold text-emas-400"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
              </svg>
              {{ showResend ? 'Tutup' : 'Tidak menerima email aktivasi?' }}
            </button>
          </div>

          <div v-if="showResend" class="mt-3 rounded-xl border-2 border-[#6F9435]/30 bg-[#263D26]/40 p-4">
            <div class="mt-3">
              <p class="mb-3 text-xs leading-relaxed text-[#8fa06a]">
                Masukkan email yang Anda gunakan saat mendaftar. Kami akan mengirim ulang tautan aktivasi.
              </p>
              <form @submit.prevent="submitResend" class="space-y-3">
                <div>
                  <label class="block">
                    <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Email terdaftar</span>
                    <input
                      v-model="resendForm.email"
                      type="email"
                      required
                      autocomplete="email"
                      placeholder="contoh@email.com"
                      class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-3 text-sm text-[#f0ead8] outline-none transition placeholder:text-[#8fa06a]/50 focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50"
                    />
                  </label>
                  <span v-if="resendErrors.email" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ resendErrors.email }}</span>
                </div>
                <button
                  type="submit"
                  :disabled="resendForm.processing"
                  class="w-full rounded-lg border-2 border-daun-500 bg-hutan-600 px-4 py-2.5 text-sm font-extrabold text-emas-400 transition hover:-translate-y-0.5 hover:bg-daun-500/20 disabled:cursor-wait disabled:opacity-60"
                >
                  <span v-if="resendForm.processing" class="inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-4 w-4 animate-spin">
                      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity="0.25"></circle>
                      <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Mengirim...
                  </span>
                  <span v-else>Kirim Ulang Email Aktivasi</span>
                </button>
              </form>
            </div>
          </div>

          <div class="mt-5 border-t-2 border-[#6F9435]/20 pt-5 text-center">
            <a href="/" class="group inline-flex items-center gap-2 rounded-lg border-2 border-daun-500 px-6 py-2.5 text-sm font-bold text-[#d4dc9a] transition hover:-translate-y-0.5 hover:bg-daun-500/20 hover:text-emas-400 hover:shadow-lg hover:shadow-daun-500/20">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4 transition group-hover:-translate-x-1"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
              Kembali ke Halaman Guest
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const form = useForm({ identity: '', password: '', remember: false });
const resendForm = useForm({ email: '' });
const resendErrors = ref({});
const showResend = ref(false);

function submitLogin() {
  form.post('/login', {
    onSuccess: () => {
      // Router handles redirect from backend
    },
    onError: (errors) => {
      // Errors handled by Inertia ($page.props.errors)
    },
  });
}

function submitResend() {
  resendForm.post('/email/resend-activation', {
    onSuccess: () => {
      resendForm.reset();
      resendErrors.value = {};
    },
    onError: (errors) => {
      resendErrors.value = errors;
    },
  });
}
</script>