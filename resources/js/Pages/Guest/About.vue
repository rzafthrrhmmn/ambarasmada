<template>
  <div class="min-h-screen bg-hutan-800 font-sans">
    <GuestHeader
      :ambalan-nama="ambalanNama"
      :logo-url="logoUrl"
      :is-mobile-menu-open="uiStore.mobileMenuOpen"
      @toggle-mobile-menu="toggleMobileMenu"
      @close-mobile-menu="closeMobileMenu"
    />

    <main class="relative mx-auto w-full px-4 py-8 sm:px-6 sm:py-12 lg:px-[50px] lg:py-20 pt-24 sm:pt-28 lg:pt-32 pb-20 sm:pb-0">
      <!-- Hero Section -->
      <section class="mb-12">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-daun-500/20 border border-daun-500/30 mb-4">
          <AppIcon name="groups" class="h-4 w-4 text-emas-400" />
          <span class="text-xs font-bold uppercase tracking-wider text-daun-100">Tentang Ambalan</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold text-krem-100 mb-4">
          Sejarah dan Visi {{ ambalanNama }}
        </h1>
        <p class="text-lg text-krem-300 max-w-2xl">
          Mengenal lebih dalam perjalanan, filosofi, visi, dan misi Ambalan
          {{ ambalanNama }} sebagai wadah pengembangan kepemudaan dan kepramukaan.
        </p>
      </section>

      <!-- Sejarah Section -->
      <section id="sejarah" class="mb-12">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-krem-100">Sejarah Berdirinya</h2>
          <AppIcon name="calendar" class="h-6 w-6 text-daun-400" />
        </div>
        <div class="card p-6">
          <p class="text-krem-300 leading-relaxed">
            {{ ambalan?.sejarah || 'Sejarah ambalan sedang disusun dan akan segera diterbitkan.' }}
          </p>
        </div>
      </section>

      <!-- Visi & Misi Section -->
      <section id="visi-misi" class="mb-12 grid gap-8 lg:grid-cols-2">
        <div class="card p-6">
          <div class="flex items-center gap-2 mb-4">
            <AppIcon name="rocket" class="h-5 w-5 text-emas-400" />
            <h3 class="text-xl font-bold text-krem-100">Visi</h3>
          </div>
          <p class="text-krem-300 leading-relaxed">
            {{ ambalan?.visi || 'Visi ambalan sedang disusun dan akan segera diterbitkan.' }}
          </p>
        </div>
        <div class="card p-6">
          <div class="flex items-center gap-2 mb-4">
            <AppIcon name="checklist" class="h-5 w-5 text-daun-400" />
            <h3 class="text-xl font-bold text-krem-100">Misi</h3>
          </div>
          <p class="text-krem-300 leading-relaxed">
            {{ ambalan?.misi || 'Misi ambalan sedang disusun dan akan segera diterbitkan.' }}
          </p>
        </div>
      </section>

      <!-- Struktur Pengurus Section -->
      <section id="struktur" class="mb-12">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-krem-100">Struktur Pengurus</h2>
          <AppIcon name="members" class="h-6 w-6 text-daun-400" />
        </div>
        <div class="card overflow-hidden">
          <div class="p-6 border-b border-[#6F9435]/30">
            <p class="text-krem-300 text-sm">
              Data struktur pengurus periode berjalan akan ditampilkan di sini.
              Struktur pengurus diolah melalui halaman Manajemen Dewan Ambalan.
            </p>
          </div>
        </div>
      </section>

      <!-- CTA Section -->
      <section class="text-center pt-12 pb-8">
        <div class="inline-block px-6 py-3 rounded-full bg-daun-500 border border-daun-400/50 text-krem-100 font-semibold hover:bg-daun-400 transition-colors cursor-pointer" onclick="location.href='/daftar'">
          Daftar Menjadi Anggota
        </div>
      </section>
    </main>

    <GuestFooter :ambalan-nama="ambalanNama" :logo-url="logoUrl" />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useUIStore } from '@/Stores/ui';

import GuestHeader from '@/Components/Guest/GuestHeader.vue';
import GuestFooter from '@/Components/Guest/GuestFooter.vue';
import AppIcon from '@/Components/AppIcon.vue';

const uiStore = useUIStore();
const page = usePage();

const ambalanNama = computed(() => page.props?.ambalan?.nama || 'Ambalan UPT SMAN 2 Maros');
const logoUrl = computed(() => page.props?.ambalan?.logo_url || '/images/Logo_Ambalan.png');

function toggleMobileMenu() {
  uiStore.setMobileMenuOpen(true);
  document.body.style.overflow = 'hidden';
}

function closeMobileMenu() {
  uiStore.setMobileMenuOpen(false);
  document.body.style.overflow = '';
}
</script>
