<template>
  <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-hutan-800 via-hutan-700 to-hutan-800 px-4 py-8 md:py-0 section-animate">
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

          <form action="/login" method="POST" class="space-y-4" @submit="onSubmit">
            <input type="hidden" name="_token" :value="$page.props.csrf_token || document.querySelector('meta[name=csrf-token]')?.content" />

            <div>
              <label class="block">
                <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Username atau email</span>
                <input
                  name="identity"
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
                  name="password"
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
              <input name="remember" type="checkbox" value="1" class="rounded border-2 border-daun-500 bg-hutan-800 text-emas-400" />
              <span class="cursor-pointer">Ingat saya</span>
            </label>

            <button
              type="submit"
              class="group relative w-full overflow-hidden rounded-lg bg-gradient-to-r from-daun-400 to-emas-400 px-4 py-3 font-extrabold text-hutan-800 shadow-lg shadow-emas-400/30 transition hover:shadow-xl hover:shadow-emas-400/40 hover:-translate-y-0.5 border-2 border-emas-400"
            >
              <span class="relative z-10 flex items-center justify-center gap-2">Masuk</span>
            </button>
          </form>

          <div class="mt-4 text-center">
            <p class="text-sm text-[#8fa06a]">
              Belum punya akun?
              <a href="/register" class="font-bold text-[#EDD330] hover:underline">Daftar Akun</a>
            </p>
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
function onSubmit(e) {
  const formEl = e.target;
  if (!formEl) return;
  let tokenInput = formEl.querySelector('input[name="_token"]');
  if (!tokenInput || !tokenInput.value) {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = '_token';
      input.value = meta.content;
      formEl.appendChild(input);
    }
  }
}
</script>