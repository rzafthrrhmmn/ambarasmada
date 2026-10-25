<template>
  <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-hutan-800 via-hutan-700 to-hutan-800 px-4 py-8 md:py-0">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
      <div class="animate-float absolute left-10 top-20 h-72 w-72 rounded-full bg-daun-400/10 blur-3xl animate-pulse-glow"></div>
      <div class="animate-float absolute right-10 top-1/3 h-60 w-60 rounded-full bg-emas-400/8 blur-3xl animate-pulse-glow"></div>
      <div class="animate-float absolute bottom-20 left-1/3 h-56 w-56 rounded-full bg-daun-500/10 blur-3xl animate-pulse-glow"></div>
    </div>

    <div class="relative grid w-full max-w-5xl overflow-hidden rounded-3xl border-2 border-daun-400/30 bg-hutan-600/80 shadow-2xl shadow-black/40 backdrop-blur-sm md:grid-cols-5 min-h-[600px]">
      <div class="relative col-span-2 hidden flex-col justify-center gap-6 bg-gradient-to-br from-hutan-800 to-hutan-700 p-10 md:flex">
        <div class="animate-float">
          <div class="flex items-center justify-center gap-3">
            <div class="flex h-20 w-20 items-center justify-center rounded-2xl border-2 border-daun-500 bg-hutan-800 overflow-hidden shadow-lg shadow-daun-400/20">
              <img v-if="$page.props.ambalan?.logo_url" :src="$page.props.ambalan.logo_url" alt="Logo" class="h-full w-full object-contain" />
              <img v-else :src="'/images/Logo_Ambalan.png'" alt="Logo" class="h-full w-full object-contain" />
            </div>
          </div>
          <p class="mt-5 text-center text-sm font-bold tracking-wide text-emas-400">{{ $page.props.ambalan?.nama || "Ambalan UPT SMAN 2 Maros" }}</p>
        </div>
        <div>
          <h2 class="text-3xl font-black text-krem-100 drop-shadow-md">Satya dan Darma</h2>
          <p class="mt-2 text-base font-medium leading-relaxed text-[#d4dc9a]/80">Ekosistem digital untuk anggota, pembina, pengurus, dan alumni kepramukaan.</p>
        </div>
        <div class="mt-4 rounded-xl border border-daun-400/20 bg-hutan-800/50 p-4 text-center">
          <p class="text-xs font-bold text-krem-300">Akses mudah di ponsel</p>
          <p class="mt-1 text-2xl font-black text-emas-400">PWA</p>
        </div>
      </div>

      <div class="col-span-3 flex flex-col justify-center p-6 md:p-10">
        <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg border border-[#6F9435] bg-[#6F9435]/20 p-3 text-sm font-medium text-[#f0ead8]">
          {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg border border-[#ef4419]/40 bg-[#ef4419]/20 p-3 text-sm font-medium text-[#f0ead8]">
          {{ $page.props.flash.error }}
        </div>
        <div class="mb-7">
          <p class="text-sm font-bold tracking-widest text-daun-400 uppercase">Pendaftaran Akun</p>
          <h1 class="mt-2 text-3xl font-black text-krem-100 drop-shadow-md">
            Buat Akun Baru
          </h1>
          <p class="mt-2 text-sm font-medium text-krem-300">
            Gabung sekarang dan akses seluruh ekosistem digital.
          </p>
        </div>

        <form @submit.prevent="submitRegister" class="space-y-4">
          <div>
            <label class="block">
              <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Nama Lengkap</span>
              <input v-model="regForm.nama_lengkap" type="text" required placeholder="Masukkan nama lengkap" class="w-full rounded-lg border-2 border-daun-500 bg-hutan-800 px-4 py-3 text-sm text-krem-100 outline-none transition placeholder:text-krem-300/50 focus:border-emas-400 focus:ring-2 focus:ring-emas-400/50" />
            </label>
            <span v-if="$page.props.errors.nama_lengkap" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.nama_lengkap }}</span>
          </div>
          <div>
            <label class="block">
              <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Email (dipakai untuk masuk)</span>
              <input v-model="regForm.email" type="email" required placeholder="contoh@email.com" class="w-full rounded-lg border-2 border-daun-500 bg-hutan-800 px-4 py-3 text-sm text-krem-100 outline-none transition placeholder:text-krem-300/50 focus:border-emas-400 focus:ring-2 focus:ring-emas-400/50" />
            </label>
            <span v-if="$page.props.errors.email" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.email }}</span>
            <p class="mt-1 block text-xs text-[#8fa06a]">
              Email ini sekaligus menjadi username akun Anda, dan akan dipakai untuk masuk serta menerima email aktivasi.
            </p>
          </div>
          <div class="rounded-lg border border-daun-400/25 bg-hutan-800/40 px-4 py-3">
            <span class="block text-sm font-bold text-[#d4dc9a]">NTA (diberikan otomatis)</span>
            <span class="mt-1 block text-xs text-[#8fa06a]">
              Nomor tanda anggota dibuat sistem setelah pendaftaran<span v-if="latestAngkatan && latestAngkatanNomor"> untuk angkatan {{ latestAngkatanNomor }} / Tahun {{ latestAngkatan }}<span v-if="latestAngkatanCurrent"> (angkatan berjalan)</span></span>. Nomornya tampil di halaman menunggu persetujuan.
            </span>
            <span v-if="!latestAngkatan || !latestAngkatanNomor" class="mt-1 block text-xs font-bold text-[#ef4419]">Belum ada angkatan aktif. Hubungi Pembina.</span>
          </div>
          <div>
            <label class="block">
              <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Password</span>
              <input v-model="regForm.password" type="password" required minlength="8" placeholder="Minimal 8 karakter" class="w-full rounded-lg border-2 border-daun-500 bg-hutan-800 px-4 py-3 text-sm text-krem-100 outline-none transition placeholder:text-krem-300/50 focus:border-emas-400 focus:ring-2 focus:ring-emas-400/50" />
            </label>
            <span v-if="$page.props.errors.password" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.password }}</span>
          </div>
          <div>
            <label class="block">
              <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Konfirmasi Password</span>
              <input v-model="regForm.password_confirmation" type="password" required minlength="8" placeholder="Ulangi password" class="w-full rounded-lg border-2 border-daun-500 bg-hutan-800 px-4 py-3 text-sm text-krem-100 outline-none transition placeholder:text-krem-300/50 focus:border-emas-400 focus:ring-2 focus:ring-emas-400/50" />
            </label>
            <span v-if="$page.props.errors.password_confirmation" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.password_confirmation }}</span>
          </div>

          <button type="submit" :disabled="regForm.processing" :class="['group relative w-full overflow-hidden rounded-lg bg-gradient-to-r from-daun-400 to-emas-400 px-4 py-3 font-extrabold text-hutan-800 shadow-lg shadow-emas-400/30 transition hover:shadow-xl hover:shadow-emas-400/40 hover:-translate-y-0.5 disabled:cursor-wait disabled:opacity-60 border-2 border-emas-400', regForm.processing ? 'animate-pulse shadow-emas-400/50' : '']">
            <span class="relative z-10 flex items-center justify-center gap-2">
              <svg v-if="regForm.processing" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-5 w-5 animate-spin text-hutan-800">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity="0.25"></circle>
                <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span v-if="regForm.processing">
                Mendaftar
                <span class="inline-flex gap-0.5">
                  <span class="animate-bounce inline-block h-1.5 w-1.5 rounded-full bg-hutan-800"></span>
                  <span class="animate-bounce inline-block h-1.5 w-1.5 rounded-full bg-hutan-800"></span>
                  <span class="animate-bounce inline-block h-1.5 w-1.5 rounded-full bg-hutan-800"></span>
                </span>
              </span>
              <span v-else>Daftar Akun</span>
            </span>
          </button>
        </form>

        <div class="mt-4 text-center">
          <p class="text-sm text-[#8fa06a]">
            Sudah punya akun?
            <a href="/login" class="font-bold text-[#EDD330] hover:underline">Masuk</a>
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
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  latestAngkatan: { type: String, default: null },
  latestAngkatanNomor: { type: String, default: null },
  latestAngkatanCurrent: { type: Boolean, default: false },
  gudepPrefix: { type: String, default: '31082008' },
});

const regForm = useForm({
  nama_lengkap: '',
  email: '',
  password: '',
  password_confirmation: '',
});

function submitRegister() {
  regForm.post('/register', {
    onSuccess: () => {
      regForm.reset();
    },
    onError: () => {
      regForm.reset();
    },
  });
}
</script>
