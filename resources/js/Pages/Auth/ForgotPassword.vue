<template>
  <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-[#263D26] via-[#2d4a2d] to-[#263D26] px-4 py-8 md:py-0">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
      <div class="animate-float absolute left-10 top-20 h-72 w-72 rounded-full bg-[#A7B92A]/10 blur-3xl animate-pulse-glow"></div>
      <div class="animate-float absolute right-10 top-1/3 h-60 w-60 rounded-full bg-[#EDD330]/8 blur-3xl animate-pulse-glow" style="animation-delay: 2s;"></div>
      <div class="animate-float absolute bottom-20 left-1/3 h-56 w-56 rounded-full bg-[#6F9435]/10 blur-3xl animate-pulse-glow" style="animation-delay: 4s;"></div>
    </div>

    <div class="relative w-full max-w-lg overflow-hidden rounded-3xl border-2 border-[#A7B92A]/30 bg-[#335233]/80 shadow-2xl shadow-black/40 backdrop-blur-sm">
      <div class="col-span-3 flex flex-col justify-center p-6 md:p-10">
        <div class="w-full max-w-md mx-auto">
          <div class="mb-8 flex flex-col items-center text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-[#6F9435] bg-[#263D26] overflow-hidden shadow-lg shadow-[#A7B92A]/20">
              <img v-if="$page.props.ambalan?.logo_url" :src="$page.props.ambalan.logo_url" alt="Logo" class="h-full w-full object-contain" />
              <img v-else :src="'/images/Logo_Ambalan.png'" alt="Logo" class="h-full w-full object-contain" />
            </div>
            <p class="mt-3 text-sm font-bold tracking-wide text-[#EDD330]">{{ $page.props.ambalan?.nama || "Ambalan UPT SMAN 2 Maros" }}</p>
          </div>

          <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg border border-[#6F9435] bg-[#6F9435]/20 p-3 text-sm font-medium text-[#EDD330]">
            {{ $page.props.flash.success }}
          </div>
          <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg border border-[#ef4419]/40 bg-[#ef4419]/10 p-3 text-sm font-medium text-[#ef4419]">
            {{ $page.props.flash.error }}
          </div>

          <div class="mb-7 text-center">
            <p class="text-sm font-bold tracking-widest text-[#A7B92A] uppercase">Lupa Password</p>
            <h1 class="mt-2 text-3xl font-black text-[#f0ead8]" style="text-shadow: 2px 2px 0 rgba(0,0,0,0.3);">Atur Ulang Sandi</h1>
            <p class="mt-2 text-sm font-medium text-[#8fa06a]">
              Masukkan email terdaftar. Kami akan mengirim tautan untuk membuat password baru.
            </p>
          </div>

          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block">
                <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Email</span>
                <input v-model="form.email" type="email" required autocomplete="email" placeholder="contoh@email.com" class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-3 text-sm text-[#f0ead8] outline-none transition placeholder:text-[#8fa06a]/50 focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
              </label>
              <span v-if="$page.props.errors.email" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.email }}</span>
            </div>

            <button type="submit" :disabled="form.processing" :class="['group relative w-full overflow-hidden rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#EDD330] px-4 py-3 font-extrabold text-[#263D26] shadow-lg shadow-[#EDD330]/30 transition hover:shadow-xl hover:shadow-[#EDD330]/40 hover:-translate-y-0.5 disabled:cursor-wait border-2 border-[#EDD330]', form.processing ? 'animate-pulse shadow-[#EDD330]/50' : '']">
              <span class="relative z-10 flex items-center justify-center gap-2">
                <svg v-if="form.processing" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-5 w-5 animate-spin text-[#263D26]">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity="0.25"></circle>
                  <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-if="form.processing">
                  Mengirim
                  <span class="inline-flex gap-0.5">
                    <span class="animate-bounce inline-block h-1.5 w-1.5 rounded-full bg-[#263D26]" style="animation-delay: 0ms;"></span>
                    <span class="animate-bounce inline-block h-1.5 w-1.5 rounded-full bg-[#263D26]" style="animation-delay: 150ms;"></span>
                    <span class="animate-bounce inline-block h-1.5 w-1.5 rounded-full bg-[#263D26]" style="animation-delay: 300ms;"></span>
                  </span>
                </span>
                <span v-else>Kirim Tautan Reset</span>
                <svg v-if="!form.processing" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4 transition group-hover:translate-x-1"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
              </span>
            </button>
          </form>

          <div class="mt-5 border-t-2 border-[#6F9435]/20 pt-5 text-center">
            <Link href="/login" class="group inline-flex items-center gap-2 rounded-lg border-2 border-[#6F9435] px-6 py-2.5 text-sm font-bold text-[#d4dc9a] transition hover:-translate-y-0.5 hover:bg-[#6F9435]/20 hover:text-[#EDD330] hover:shadow-lg hover:shadow-[#6F9435]/20">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4 transition group-hover:-translate-x-1"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
              Kembali ke Halaman Masuk
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';

const form = useForm({ email: '' });

function submit() {
  form.post('/forgot-password', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
  });
}
</script>