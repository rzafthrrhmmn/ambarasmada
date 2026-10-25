<template>
  <section class="guest-section" aria-label="Ajakan bergabung">
    <ScrollReveal animation="zoom-in">
      <div
        class="relative isolate overflow-hidden rounded-3xl border-2 border-emas-400/40 bg-gradient-to-r from-daun-400 via-daun-500 to-hutan-800 p-8 text-center shadow-2xl sm:p-12"
      >
        <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-emas-400/20 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -bottom-24 -right-24 h-80 w-80 rounded-full bg-daun-400/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative">
          <h2 class="text-3xl font-black text-white sm:text-4xl lg:text-5xl">
            Siap Membangun Ambalan Lebih Digital?
          </h2>
          <p class="mx-auto mt-4 max-w-lg text-base font-medium text-white/80">
            Gabung sekarang dan jadilah bagian dari ekosistem informasi kepramukaan yang modern.
          </p>
          <div class="mt-8 flex flex-wrap justify-center gap-4">
            <a
              href="/register"
              class="guest-focus-ring rounded-2xl bg-hutan-800 px-9 py-4 text-sm font-black text-emas-400 shadow-xl shadow-black/30 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-black/40 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
            >
              Bergabung Sekarang
            </a>
            <a
              href="/login"
              class="guest-focus-ring rounded-2xl border-2 border-hutan-800 bg-white/10 px-9 py-4 text-sm font-black text-hutan-800 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-transparent hover:bg-white/20 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
            >
              Masuk Akun
            </a>
          </div>
        </div>
      </div>
    </ScrollReveal>
  </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import ScrollReveal from '@/Components/ScrollReveal.vue';
</script>
