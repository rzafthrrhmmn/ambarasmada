<template>
  <header class="sticky top-4 z-40 mx-auto w-full max-w-[1400px] guest-container">
    <div class="glass-panel flex items-center justify-between px-4 py-2.5 sm:px-6 sm:py-3">
      <Link
        href="/"
        class="flex items-center gap-3 group"
        aria-label="Beranda {{ ambalanNama }}"
      >
        <div
          class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-daun-500/60 bg-hutan-800 transition-all duration-300 group-hover:border-emas-400/60"
        >
          <img
            :src="logoUrl"
            alt="Logo Ambalan UPT SMAN 2 Maros"
            class="guest-logo guest-logo-sm"
            width="48"
            height="48"
            loading="eager"
            @error="onLogoError"
          />
        </div>
        <div class="hidden sm:block">
          <p class="text-sm font-black text-krem-100">{{ ambalanNama }}</p>
          <p class="text-[11px] font-bold text-lumut-400">Ekosistem Digital Kepramukaan</p>
        </div>
      </Link>

      <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
        <a
          href="#fitur"
          class="guest-focus-ring rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
          scroll-margin-top="80px"
        >
          Fitur
        </a>
        <a
          href="#dokumentasi"
          class="guest-focus-ring rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
          scroll-margin-top="80px"
        >
          Dokumentasi
        </a>
        <a
          href="#testimoni"
          class="guest-focus-ring rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
          scroll-margin-top="80px"
        >
          Testimoni
        </a>
        <a
          href="#pengumatan"
          class="guest-focus-ring rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
          scroll-margin-top="80px"
        >
          Pengumuman
        </a>
        <span class="mx-2 h-4 w-px bg-daun-500/30" aria-hidden="true"></span>
        <Link
          href="/login"
          class="guest-focus-ring rounded-xl px-4 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
        >
          Masuk
        </Link>
        <Link
          href="/register"
          class="guest-focus-ring rounded-xl bg-gradient-to-r from-daun-400 to-emas-400 px-4 py-2 text-sm font-black text-hutan-800 shadow-lg shadow-emas-400/20 transition hover:brightness-110 hover:shadow-xl hover:shadow-emas-400/30 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
        >
          Daftar
        </Link>
      </nav>

      <button
        id="mobile-menu-button"
        class="guest-focus-ring flex h-10 w-10 items-center justify-center rounded-xl border-2 border-daun-500/40 text-krem-300 transition hover:border-emas-400 hover:text-emas-400 lg:hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
        @click="emit('toggle-mobile-menu')"
        :aria-expanded="isMobileMenuOpen"
        aria-controls="mobile-menu-overlay"
        aria-label="Buka menu navigasi"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
      </button>
    </div>
  </header>

  <!-- Mobile Menu Overlay -->
  <Teleport to="body">
    <Transition name="fade" appear>
      <div
        v-if="isMobileMenuOpen"
        id="mobile-menu-overlay"
        class="fixed inset-0 z-50 flex items-center justify-center bg-hutan-900/95 backdrop-blur-xl"
        @click.self="emit('close-mobile-menu')"
        role="dialog"
        aria-modal="true"
        aria-label="Menu navigasi"
        aria-labelledby="mobile-menu-title"
      >
        <div class="mx-4 w-full max-w-sm rounded-3xl border-2 border-daun-500/40 bg-hutan-800 p-6 shadow-2xl">
          <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <img :src="logoUrl" alt="Logo Ambalan" class="h-10 w-10 object-contain p-1" @error="onLogoError" width="40" height="40" />
              <span id="mobile-menu-title" class="sr-only">Navigasi menu utama</span>
              <span class="text-lg font-black text-krem-100">Menu</span>
            </div>
            <button
              id="mobile-menu-close"
              @click="emit('close-mobile-menu')"
              class="guest-focus-ring flex h-10 w-10 items-center justify-center rounded-full border-2 border-daun-500 text-krem-300 transition hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
              aria-label="Tutup menu navigasi"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="flex flex-col gap-3">
            <a
              href="#fitur"
              @click="emit('close-mobile-menu')"
              class="guest-focus-ring rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
              scroll-margin-top="80px"
            >
              Fitur
            </a>
            <a
              href="#dokumentasi"
              @click="emit('close-mobile-menu')"
              class="guest-focus-ring rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
              scroll-margin-top="80px"
            >
              Dokumentasi
            </a>
            <a
              href="#testimoni"
              @click="emit('close-mobile-menu')"
              class="guest-focus-ring rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
              scroll-margin-top="80px"
            >
              Testimoni
            </a>
            <a
              href="#pengumatan"
              @click="emit('close-mobile-menu')"
              class="guest-focus-ring rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
              scroll-margin-top="80px"
            >
              Pengumuman
            </a>
            <Link
              href="/login"
              @click="emit('close-mobile-menu')"
              class="guest-focus-ring rounded-2xl border-2 border-daun-500 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
            >
              Masuk
            </Link>
            <Link
              href="/register"
              @click="emit('close-mobile-menu')"
              class="guest-focus-ring rounded-2xl bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 px-4 py-3 text-center font-black text-hutan-800 shadow-lg transition hover:brightness-110 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
            >
              Bergabung Sekarang
            </Link>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  ambalanNama: { type: String, default: 'Ambalan UPT SMAN 2 Maros' },
  logoUrl: { type: String, default: '/images/Logo_Ambalan.png' },
  isMobileMenuOpen: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle-mobile-menu', 'close-mobile-menu', 'logo-error']);

function onLogoError(event) {
  event.target.onerror = null;
  event.target.src = '/images/Logo_Ambalan.png';
  emit('logo-error');
}
</script>

<style scoped>
.guest-focus-ring {
  outline: none;
}
</style>
