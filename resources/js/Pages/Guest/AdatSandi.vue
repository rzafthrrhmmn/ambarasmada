<template>
  <div class="min-h-screen bg-hutan-800 font-sans">
    <GuestHeader
      :ambalan-nama="ambalanNama"
      :logo-url="logoUrl"
      :is-mobile-menu-open="uiStore.mobileMenuOpen"
      @toggle-mobile-menu="toggleMobileMenu"
      @close-mobile-menu="closeMobileMenu"
    />

    <main class="relative mx-auto w-full px-4 py-8 sm:px-6 sm:py-12 lg:px-[50px] lg:py-20">
      <!-- Hero Section -->
      <section class="mb-12">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-daun-500/20 border border-daun-500/30 mb-4">
          <AppIcon name="shield" class="h-4 w-4 text-emas-400" />
          <span class="text-xs font-bold uppercase tracking-wider text-daun-100">Adat & Sandi</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold text-krem-100 mb-4">
          Adat Istiadat dan Sandi Ambalan
        </h1>
        <p class="text-lg text-krem-300 max-w-2xl">
          Nilai-nilai luhur, sandi kepramukaan, serta aturan dasar tata krama
          yang menjadi landasan setiap anggota Ambalan {{ ambalanNama }}.
        </p>
      </section>

      <!-- Sandi & Amalan Section -->
      <section id="sandi" class="mb-12">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-krem-100">Sandi dan Amalan Kepramukaan</h2>
          <AppIcon name="checklist" class="h-6 w-6 text-emas-400" />
        </div>
        <div class="card p-6">
          <p class="text-krem-300 leading-relaxed">
            {{ ambalan?.sandi_amalan || 'Sandi dan amalan kepramukaan ambalan sedang disusun dan akan segera diterbitkan.' }}
          </p>
        </div>
      </section>

      <!-- Tata Krama Section -->
      <section id="tata-krama" class="mb-12">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-krem-100">Tata Krama Satuan</h2>
          <AppIcon name="users" class="h-6 w-6 text-daun-400" />
        </div>
        <div class="card p-6">
          <p class="text-krem-300 leading-relaxed">
            {{ ambalan?.aturan_tata_krama || 'Aturan tata krama satuan sedang disusun dan akan segera diterbitkan.' }}
          </p>
        </div>
      </section>

      <!-- Nilai Kepramukaan Section -->
      <section id="nilai" class="mb-12 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <template v-for="nilai in nilaiKepramukaan" :key="nilai.nama">
          <div class="card p-5 text-center">
            <AppIcon :name="nilai.icon" class="h-8 w-8 mx-auto mb-3 text-emas-400" />
            <h3 class="text-base font-bold text-krem-100">{{ nilai.nama }}</h3>
            <p class="text-xs text-krem-300 leading-relaxed">{{ nilai.deskripsi }}</p>
          </div>
        </template>
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

const nilaiKepramukaan = [
  { nama: 'Kejujuran', icon: 'checkCircle', deskripsi: 'Berani mengakui kesalahan dan selalu benar dalam setiap kata dan perbuatan.' },
  { nama: 'Kesetiaan', icon: 'shield', deskripsi: 'Setia kepada Tuhan, negara, agama, dan satuan kepramukaan.' },
  { nama: 'Berguna', icon: 'plus', deskripsi: 'Selalu siap membantu sesama tanpa meminta imbalan.' },
  { nama: 'Sahabat', icon: 'users', deskripsi: 'Hormati dan menjalin persahabatan dengan setiap sesama pramuka.' },
  { nama: 'Sopan', icon: 'note', deskripsi: 'Berbicara dan bertindak dengan sopan santun kepada siapa saja.' },
  { nama: 'Senyum', icon: 'star', deskripsi: 'Hadir dengan wajah cerah dan sikap positif dalam setiap situasi.' },
];

function toggleMobileMenu() {
  uiStore.setMobileMenuOpen(true);
  document.body.style.overflow = 'hidden';
}

function closeMobileMenu() {
  uiStore.setMobileMenuOpen(false);
  document.body.style.overflow = '';
}
</script>
