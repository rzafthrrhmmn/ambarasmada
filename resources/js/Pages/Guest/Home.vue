<template>
  <div ref="container" class="relative min-h-screen overflow-hidden bg-hutan-800 font-sans guest-scroll">
    <ParticleBackground
      :particle-count="isMobile ? 40 : 80"
      :colors="['#A7B92B', '#EDD330', '#6F9435', '#f0ead8']"
      :interactive="true"
      :connection-distance="140"
      :mouse-influence="180"
      class="absolute inset-0"
    />

    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
      <div class="absolute -top-40 -left-40 h-[500px] w-[500px] rounded-full bg-gradient-to-br from-daun-400/15 via-daun-400/5 to-transparent blur-[100px] will-change-transform" data-parallax-bg></div>
      <div class="absolute -right-40 top-20 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-emas-400/15 via-emas-400/5 to-transparent blur-[100px] will-change-transform"></div>
      <div class="absolute -bottom-40 left-1/3 h-[350px] w-[350px] rounded-full bg-gradient-to-br from-daun-500/20 to-transparent blur-[100px] will-change-transform"></div>
      <div class="guest-noise-overlay absolute inset-0 opacity-30"></div>
    </div>

    <a href="#main-content" class="guest-focus-ring skip-link">Lewati ke konten utama</a>

    <nav class="sticky top-4 z-40 mx-auto w-full max-w-[1400px] guest-container">
      <div class="glass-panel flex items-center justify-between px-4 py-2.5 sm:px-6 sm:py-3">
        <Link href="/" class="flex items-center gap-3 group">
          <div class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-daun-500/60 bg-hutan-800 transition-all duration-300 group-hover:border-emas-400/60">
            <img
              :src="logoUrl"
              alt="Logo Ambalan UPT SMAN 2 Maros"
              class="guest-logo guest-logo-sm"
              @error="e => { e.target.onerror = null; e.target.src = '/images/Logo_Ambalan.png'; }"
            />
          </div>
          <div class="hidden sm:block">
            <p class="text-sm font-black text-krem-100">{{ ambalanNama }}</p>
            <p class="text-[11px] font-bold text-lumut-400">Ekosistem Digital Kepramukaan</p>
          </div>
        </Link>

        <div class="hidden items-center gap-1 lg:flex">
          <a href="#fitur" class="guest-focus-ring rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400">Fitur</a>
          <a href="#dokumentasi" class="guest-focus-ring rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400">Dokumentasi</a>
          <a href="#testimoni" class="guest-focus-ring rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400">Testimoni</a>
          <a href="#pengumatan" class="guest-focus-ring rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400">Pengumuman</a>
          <span class="mx-2 h-4 w-px bg-daun-500/30"></span>
          <Link href="/login" class="guest-focus-ring rounded-xl px-4 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400">Masuk</Link>
          <Link href="/register" class="guest-focus-ring guest-btn-shimmer rounded-xl bg-gradient-to-r from-daun-400 to-emas-400 px-4 py-2 text-sm font-black text-hutan-800 shadow-lg shadow-emas-400/20 transition hover:brightness-110 hover:shadow-xl hover:shadow-emas-400/30 active:scale-95">Daftar</Link>
        </div>

        <button
          id="mobile-menu-button"
          class="guest-focus-ring flex h-10 w-10 items-center justify-center rounded-xl border-2 border-daun-500/40 text-krem-300 transition hover:border-emas-400 hover:text-emas-400 lg:hidden"
          @click="openMobileMenu"
          :aria-expanded="uiStore.mobileMenuOpen"
          aria-controls="mobile-menu-overlay"
          aria-label="Buka menu"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
        </button>
      </div>
    </nav>

    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="uiStore.mobileMenuOpen"
          id="mobile-menu-overlay"
          class="fixed inset-0 z-50 flex items-center justify-center bg-hutan-900/90 backdrop-blur-xl"
          @click.self="closeMobileMenu"
          role="dialog"
          aria-modal="true"
          aria-label="Menu navigasi"
          aria-labelledby="mobile-menu-title"
        >
          <div class="mx-4 w-full max-w-sm rounded-3xl border-2 border-daun-500/40 bg-hutan-800 p-6 shadow-2xl">
            <h2 id="mobile-menu-title" class="sr-only">Navigasi menu utama</h2>
            <div class="mb-6 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <img :src="logoUrl" alt="Logo Ambalan" class="h-10 w-10 object-contain p-1" @error="e => { e.target.onerror = null; e.target.src = '/images/Logo_Ambalan.png'; }" />
                <span class="text-lg font-black text-krem-100">Menu</span>
              </div>
              <button
                id="mobile-menu-close"
                @click="closeMobileMenu"
                class="guest-focus-ring flex h-10 w-10 items-center justify-center rounded-full border-2 border-daun-500 text-krem-300 transition hover:text-emas-400"
                aria-label="Tutup menu"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="flex flex-col gap-3">
              <a href="#fitur" @click="closeMobileMenu" class="guest-focus-ring rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Fitur</a>
              <a href="#dokumentasi" @click="closeMobileMenu" class="guest-focus-ring rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Dokumentasi</a>
              <a href="#testimoni" @click="closeMobileMenu" class="guest-focus-ring rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Testimoni</a>
              <a href="#pengumatan" @click="closeMobileMenu" class="guest-focus-ring rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Pengumuman</a>
              <Link href="/login" @click="closeMobileMenu" class="guest-focus-ring rounded-2xl border-2 border-daun-500 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Masuk</Link>
              <Link href="/register" @click="closeMobileMenu" class="guest-focus-ring rounded-2xl bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 px-4 py-3 text-center font-black text-hutan-800 shadow-lg transition hover:brightness-110 active:scale-95">Bergabung Sekarang</Link>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <div v-if="!isOnline" class="fixed top-0 inset-x-0 z-50 bg-emas-400 text-hutan-800 text-center text-xs font-bold py-1.5 px-4 shadow-lg">
      Anda sedang offline — menampilkan data yang tersimpan di perangkat
    </div>

    <main id="main-content" class="relative mx-auto w-full px-4 py-8 sm:px-6 sm:py-12 lg:px-[50px] lg:py-20">
      <HeroSection
        :slider-slides="guestStore.sliderSlides"
        :logo-url="logoUrl"
        :ambalan-nama="ambalanNama"
        :is-mobile="isMobile"
      />

      <StatsSection
        :stats-items="statsItems"
        :animated-stats="animatedStats"
        :is-loading="guestStore.isLoading"
      />

      <section id="cara-kerja" class="guest-section scroll-mt-24">
        <div class="mb-8 sm:mb-12 text-center">
          <ScrollReveal animation="fade-up">
            <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
              <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse"></span>
              Cara Kerja
            </div>
          </ScrollReveal>
          <ScrollReveal animation="fade-up" :delay="100">
            <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl lg:text-5xl">
              <span class="guest-text-gradient">Mulai dalam</span>
              <span class="guest-text-gradient-gold"> 3 Langkah</span>
            </h2>
          </ScrollReveal>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
          <ScrollReveal v-for="(step, idx) in howItWorks" :key="idx" :delay="idx * 150" :stagger="true" :style="`--stagger-index: ${idx}`" animation="fade-up">
            <div class="relative flex h-full flex-col items-center rounded-3xl border-2 border-daun-500/30 bg-hutan-600/60 p-6 text-center shadow-lg transition-all duration-300 hover:-translate-y-2 hover:border-daun-400/60 hover:bg-hutan-600/80">
              <div class="absolute -top-4 left-1/2 -translate-x-1/2 flex h-8 w-8 items-center justify-center rounded-full border-2 border-daun-400 bg-hutan-800 text-sm font-black text-emas-400">
                {{ idx + 1 }}
              </div>
               <div class="mt-4 mb-4 flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-daun-500/30 bg-hutan-800 transition-transform hover:rotate-6">
                <AppIcon :name="step.iconName" class="h-8 w-8 text-emas-400" />
              </div>
              <h3 class="text-xl font-extrabold text-krem-100">{{ step.title }}</h3>
              <p class="mt-3 text-sm leading-relaxed font-medium text-krem-300/80">{{ step.description }}</p>
            </div>
          </ScrollReveal>
        </div>
      </section>

      <GallerySection
        :gallery="guestStore.gallery"
        :is-loading="guestStore.isLoading"
        @open-lightbox="openLightbox"
      />

      <FeaturesSection :features="features" />

      <TestimonialsSection :testimonials="testimonials" />

      <AnnouncementsSection
        :announcements="guestStore.announcements"
        :is-loading="guestStore.isLoading"
        :format-date="formatDate"
        :category-icon="categoryIcon"
      />

      <section class="guest-section">
        <ScrollReveal animation="zoom-in">
          <div class="relative isolate overflow-hidden rounded-3xl border-2 border-emas-400/40 bg-gradient-to-r from-daun-400 via-daun-500 to-hutan-800 p-8 text-center shadow-2xl sm:p-12">
            <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-emas-400/20 blur-3xl"></div>
            <div class="absolute -bottom-24 -right-24 h-80 w-80 rounded-full bg-daun-400/20 blur-3xl"></div>
            <div class="relative">
              <h2 class="text-3xl font-black text-white sm:text-4xl lg:text-5xl">
                Siap Membangun Ambalan Lebih Digital?
              </h2>
              <p class="mx-auto mt-4 max-w-lg text-base font-medium text-white/80">
                Gabung sekarang dan jadilah bagian dari ekosistem informasi kepramukaan yang modern.
              </p>
              <div class="mt-8 flex flex-wrap justify-center gap-4">
                <Link
                  href="/register"
                  class="guest-focus-ring rounded-2xl bg-hutan-800 px-9 py-4 text-sm font-black text-emas-400 shadow-xl shadow-black/30 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-black/40 active:scale-95"
                >
                  Bergabung Sekarang
                </Link>
                <Link
                  href="/login"
                  class="guest-focus-ring rounded-2xl border-2 border-hutan-800 bg-white/10 px-9 py-4 text-sm font-black text-hutan-800 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-transparent hover:bg-white/20 active:scale-95"
                >
                  Masuk Akun
                </Link>
              </div>
            </div>
          </div>
        </ScrollReveal>
      </section>

      <NewsletterSection
        :newsletter-email="newsletterEmail"
        :newsletter-subscribed="newsletterSubscribed"
        :newsletter-loading="newsletterLoading"
        :newsletter-error="newsletterError"
        @update:newsletter-email="newsletterEmail = $event"
        @subscribe="subscribeNewsletter"
      />

      <footer class="guest-section border-t border-daun-500/20">
        <div class="flex flex-col items-center justify-center gap-5">
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl overflow-hidden border-2 border-daun-500/40 bg-hutan-800 text-emas-400">
              <img :src="logoUrl" alt="Logo Ambalan" class="h-8 w-8 object-contain" @error="e => { e.target.onerror = null; e.target.src = '/images/Logo_Ambalan.png'; }" />
            </div>
            <span class="text-base font-bold text-krem-300">{{ ambalanNama }}</span>
          </div>
          <nav class="flex flex-wrap items-center justify-center gap-4 text-xs font-medium text-lumut-400">
            <Link href="/privacy" class="transition hover:text-emas-400">Kebijakan Privasi</Link>
            <span class="h-3 w-px bg-daun-500/30"></span>
            <a href="mailto:admin@sman2maros.sch.id" class="transition hover:text-emas-400">Hubungi Admin</a>
          </nav>
          <p class="text-xs font-medium text-lumut-400">
            &copy; {{ currentYear }} Ekosistem Digital Kepramukaan Ambalan UPT SMAN 2 Maros &middot; Dibangun dengan kebanggaan.
          </p>
        </div>
      </footer>
    </main>

    <nav v-if="isMobile" class="guest-bottom-nav" aria-label="Navigasi bawah">
      <a href="#fitur" class="guest-bottom-nav-item" :class="{ 'is-active': activeSection === 'fitur' }" @click.prevent="scrollToSection('fitur')">
        <AppIcon name="tools" class="h-4 w-4" />
        <div class="guest-bottom-nav-indicator"></div>
        <span>Fitur</span>
      </a>
      <a href="#dokumentasi" class="guest-bottom-nav-item" :class="{ 'is-active': activeSection === 'dokumentasi' }" @click.prevent="scrollToSection('dokumentasi')">
        <AppIcon name="galleries" class="h-4 w-4" />
        <div class="guest-bottom-nav-indicator"></div>
        <span>Galeri</span>
      </a>
      <a href="#testimoni" class="guest-bottom-nav-item" :class="{ 'is-active': activeSection === 'testimoni' }" @click.prevent="scrollToSection('testimoni')">
        <AppIcon name="star" class="h-4 w-4" />
        <div class="guest-bottom-nav-indicator"></div>
        <span>Testimoni</span>
      </a>
      <a href="#pengumatan" class="guest-bottom-nav-item" :class="{ 'is-active': activeSection === 'pengumatan' }" @click.prevent="scrollToSection('pengumatan')">
        <AppIcon name="bell" class="h-4 w-4" />
        <div class="guest-bottom-nav-indicator"></div>
        <span>Pengumuman</span>
      </a>
      <Link href="/login" class="guest-bottom-nav-item">
        <AppIcon name="login" class="h-4 w-4" />
        <div class="guest-bottom-nav-indicator"></div>
        <span>Masuk</span>
      </Link>
    </nav>

    <LightboxModal
      v-model:open="lightboxOpen"
      :items="guestStore.gallery"
      :current-index="lightboxIndex"
      @close="lightboxOpen = false"
      @navigate="onLightboxNavigate"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import ParticleBackground from '@/Components/ParticleBackground.vue';
import ScrollReveal from '@/Components/ScrollReveal.vue';
import LightboxModal from '@/Components/LightboxModal.vue';
import { useGuestStore } from '@/Stores/guest';
import { useUIStore } from '@/Stores/ui';

import HeroSection from '@/Components/Guest/HeroSection.vue';
import StatsSection from '@/Components/Guest/StatsSection.vue';
import GallerySection from '@/Components/Guest/GallerySection.vue';
import FeaturesSection from '@/Components/Guest/FeaturesSection.vue';
import TestimonialsSection from '@/Components/Guest/TestimonialsSection.vue';
import AnnouncementsSection from '@/Components/Guest/AnnouncementsSection.vue';
import NewsletterSection from '@/Components/Guest/NewsletterSection.vue';

const guestStore = useGuestStore();
const uiStore = useUIStore();
const page = usePage();

const props = defineProps({
  announcements: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({ members: 0, alumni: 0 }) },
  sliderSlides: { type: Array, default: () => [] },
  gallery: { type: Array, default: () => [] },
});

const isMobile = ref(false);
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const container = ref(null);
const activeSection = ref('');
const newsletterEmail = ref('');
const newsletterSubscribed = ref(false);
const newsletterLoading = ref(false);
const newsletterError = ref('');
const animatedStats = ref({ members: 0, alumni: 0, services: 0, access: 0 });
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);
let animationFrameId = null;
let scrollHandler = null;
let revealObserver = null;
let lastFocusedElement = null;
let onlineHandler = null;
let offlineHandler = null;

const ambalanNama = computed(() => {
  return page.props?.ambalan?.nama || 'Ambalan UPT SMAN 2 Maros';
});
const logoUrl = computed(() => {
  return page.props?.ambalan?.logo_url || '/images/Logo_Ambalan.png';
});

const howItWorks = [
  { iconName: 'note', title: 'Daftar', description: 'Buat akun dengan email atau NISN. Proses pendaftaran hanya membutuhkan waktu 2 menit.' },
  { iconName: 'checkCircle', title: 'Verifikasi', description: 'Aktivasi akun melalui tautan yang dikirim ke email. Pembina melakukan validasi data.' },
  { iconName: 'rocket', title: 'Akses Fitur', description: 'Setelah disetujui, Anda bisa menggunakan SKU, presensi, kas, inventaris, dan dashboard.' },
];

const features = [
  { iconName: 'sku', title: 'SKU Anggota', description: 'Pengelolaan Surat Keputusan Anggota secara digital, dari penerbitan hingga arsip, lengkap dengan status dan riwayat.' },
  { iconName: 'attendance', title: 'Presensi', description: 'Sistem kehadiran modern dengan pencatatan digital. Pantau kehadiran anggota secara real-time dari mana saja.' },
  { iconName: 'wallet', title: 'Kas Ambalan', description: 'Pengelolaan keuangan ambalan dengan pencatatan pemasukan dan pengeluaran yang transparan dan terstruktur.' },
  { iconName: 'inventory', title: 'Inventaris', description: 'Daftar barang ambalan lengkap dengan kondisi, lokasi, dan riwayat penggunaan dalam satu sistem terpusat.' },
  { iconName: 'dashboard', title: 'Dashboard', description: 'Ringkasan visual aktivitas ambalan dalam satu pandangan. Pantau tren, capaian, dan perkembangan secara cepat.' },
  { iconName: 'profile', title: 'Profil Ambalan', description: 'Informasi lengkap tentang sejarah, visi, misi, dan struktur pengurus ambalan dalam tampilan yang profesional.' },
];

const testimonials = [
  { quote: 'Sistem ini membuat administrasi ambalan jauh lebih mudah. Semua data tersedia dalam satu platform.', name: 'Pembina A', role: 'Pembina Ambalan SMA 2 Maros', rating: 5 },
  { quote: 'Presensi digital dan SKU elektronik sangat membantu. Tidak perlu lagi formulir kertas yang mudah hilang.', name: 'Ketua Ambalan', role: 'Pengurus Ambalan', rating: 5 },
  { quote: 'Sebagai alumni, saya tetap bisa mengikuti perkembangan dan berkontribusi melalui fitur donasi.', name: 'Alumni B', role: 'Anggota Alumni', rating: 5 },
];

const statsItems = computed(() => [
  { label: 'Anggota Aktif', key: 'members', value: guestStore.stats.value?.members ?? props.stats.members, icon: 'members' },
  { label: 'Alumni Tercatat', key: 'alumni', value: guestStore.stats.value?.alumni ?? props.stats.alumni, icon: 'alumni' },
  { label: 'Layanan Digital', key: 'services', value: '5+', icon: 'tools' },
  { label: 'Akses Ponsel', key: 'access', value: 'PWA', icon: 'download' },
]);

const currentYear = computed(() => new Date().getFullYear());

function categoryIcon(kategori) {
  const map = {
    'Kegiatan': 'events',
    'Latihan': 'assessments',
    'Outbound': 'map',
    'Jambore': 'teams',
    'Peringatan': 'announcements',
    'Umum': 'announcements',
  };
  return map[kategori] || 'announcements';
}

function easeOutExpo(t) {
  return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
}

function animateCounter(target, key, duration = 1800) {
  if (typeof target !== 'number') {
    animatedStats.value[key] = target;
    return;
  }

  const startTime = performance.now();
  const numTarget = target;
  const step = (timestamp) => {
    const elapsed = timestamp - startTime;
    const progress = Math.min(elapsed / duration, 1);
    animatedStats.value[key] = Math.floor(numTarget * easeOutExpo(progress));
    if (progress < 1) animationFrameId = requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}

function formatDate(value) {
  return value
    ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    : '-';
}

function openMobileMenu() {
  uiStore.setMobileMenuOpen(true);
  lastFocusedElement = document.activeElement;
  document.body.style.overflow = 'hidden';
  nextTick(() => {
    const closeBtn = document.getElementById('mobile-menu-close');
    if (closeBtn) closeBtn.focus();
  });
}

function closeMobileMenu() {
  uiStore.setMobileMenuOpen(false);
  document.body.style.overflow = '';
  if (lastFocusedElement) lastFocusedElement.focus();
}

function trapFocus(event) {
  if (!uiStore.mobileMenuOpen) return;
  const overlay = document.getElementById('mobile-menu-overlay');
  if (!overlay) return;
  const focusable = overlay.querySelectorAll('a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])');
  if (focusable.length === 0) return;
  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  if (event.key === 'Tab') {
    if (event.shiftKey) {
      if (document.activeElement === first) { event.preventDefault(); last.focus(); }
    } else {
      if (document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
  }
  if (event.key === 'Escape') closeMobileMenu();
}

async function subscribeNewsletter() {
  newsletterError.value = '';
  if (!newsletterEmail.value) return;
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newsletterEmail.value)) {
    newsletterError.value = 'Email tidak valid.';
    return;
  }

  newsletterLoading.value = true;
  try {
    const res = await fetch('/v1/guest/newsletter', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ email: newsletterEmail.value }),
    });

    const payload = await res.json().catch(() => ({}));
    if (!res.ok) {
      throw new Error(payload.message || `Permintaan gagal (HTTP ${res.status})`);
    }

    newsletterSubscribed.value = true;
    newsletterEmail.value = '';
  } catch (err) {
    newsletterError.value = err instanceof Error ? err.message : 'Tidak dapat mengirimkan langganan.';
  } finally {
    newsletterLoading.value = false;
  }
}

function openLightbox(idx) {
  lightboxIndex.value = idx;
  lightboxOpen.value = true;
}

function onLightboxNavigate(payload) {
  const len = guestStore.gallery.length;
  if (len === 0) return;

  if (typeof payload === 'number') {
    lightboxIndex.value = Math.max(0, Math.min(payload, len - 1));
  } else if (payload === 'prev') {
    lightboxIndex.value = (lightboxIndex.value - 1 + len) % len;
  } else if (payload === 'next') {
    lightboxIndex.value = (lightboxIndex.value + 1) % len;
  }
}

function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function handleResize() {
  isMobile.value = window.innerWidth < 1024;
}

function setMetaTags() {
  const baseUrl = window.location.origin.replace(/\/+$/, '');
  const homeUrl = baseUrl + '/';
  const logoImg = baseUrl + '/images/Logo_Ambalan.png';
  const title = 'AMBARA — Sistem Digital Ambalan SMA 2 Maros';
  const description = 'Ekosistem digital kepramukaan: SKU, presensi, kas, inventaris, dan dashboard dalam satu platform untuk anggota SMA 2 Maros.';

  document.title = title;

  const ensureTag = (selector, attr, attrValue) => {
    let tag = document.querySelector(selector);
    if (!tag) {
      tag = document.createElement('meta');
      tag.setAttribute(attr, attrValue);
      document.head.appendChild(tag);
    }
    return tag;
  };

  ensureTag('meta[name="description"]', 'name', 'description').setAttribute('content', description);

  ensureTag('meta[property="og:title"]', 'property', 'og:title').setAttribute('content', title);
  ensureTag('meta[property="og:description"]', 'property', 'og:description').setAttribute('content', description);
  ensureTag('meta[property="og:image"]', 'property', 'og:image').setAttribute('content', logoImg);
  ensureTag('meta[property="og:url"]', 'property', 'og:url').setAttribute('content', homeUrl);

  ensureTag('meta[name="twitter:card"]', 'name', 'twitter:card').setAttribute('content', 'summary_large_image');
  ensureTag('meta[name="twitter:title"]', 'name', 'twitter:title').setAttribute('content', title);
  ensureTag('meta[name="twitter:description"]', 'name', 'twitter:description').setAttribute('content', description);

  const canonicalSelector = 'link[rel="canonical"]';
  let canonical = document.querySelector(canonicalSelector);
  if (!canonical) {
    canonical = document.createElement('link');
    canonical.setAttribute('rel', 'canonical');
    document.head.appendChild(canonical);
  }
  canonical.setAttribute('href', homeUrl);

  let jsonLd = document.getElementById('guest-jsonld');
  if (!jsonLd) {
    jsonLd = document.createElement('script');
    jsonLd.id = 'guest-jsonld';
    jsonLd.type = 'application/ld+json';
    document.head.appendChild(jsonLd);
  }
  jsonLd.textContent = JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: 'Ambalan UPT SMAN 2 Maros',
    url: homeUrl,
    logo: logoImg,
    description: description,
    address: { '@type': 'PostalAddress', addressLocality: 'Maros', addressRegion: 'Sulawesi Selatan', addressCountry: 'ID' },
  });
}

onMounted(() => {
  setMetaTags();
  handleResize();
  window.addEventListener('resize', handleResize);
  onlineHandler = () => { isOnline.value = true; };
  offlineHandler = () => { isOnline.value = false; };
  window.addEventListener('online', onlineHandler);
  window.addEventListener('offline', offlineHandler);

  if (props.announcements?.length) {
    guestStore.setFromProps({ announcements: props.announcements });
  }
  if (props.gallery?.length) {
    guestStore.setFromProps({ gallery: props.gallery });
  }
  if (props.sliderSlides?.length) {
    guestStore.setFromProps({ sliderSlides: props.sliderSlides });
  }
  if (props.stats) {
    guestStore.setFromProps({ stats: props.stats });
  }

  void guestStore.fetchGuestData().catch((err) => {
    console.error('[Guest/Home] Gagal memuat data tamu dari API:', err?.message ?? err);
  });

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
           if (entry.target.dataset.animate === 'stats') {
             const s = guestStore.stats.value || props.stats || { members: 0, alumni: 0 };
             animateCounter(s.members ?? 0, 'members');
             animateCounter(s.alumni ?? 0, 'alumni');
             animateCounter('5+', 'services');
             animateCounter('PWA', 'access');
           }
          revealObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.1 },
  );

  container.value?.querySelectorAll('[data-animate]').forEach((el) => revealObserver.observe(el));

  scrollHandler = () => {
    if (!container.value || prefersReducedMotion) return;
    const scrollY = window.scrollY;
    const bg = container.value.querySelector('[data-parallax-bg]');
    if (bg) bg.style.transform = `translateY(${scrollY * 0.3}px)`;
    const hero = container.value.querySelector('[data-parallax-hero]');
    if (hero) hero.style.transform = `translateY(${scrollY * 0.1}px)`;

    const sections = ['fitur', 'dokumentasi', 'testimoni', 'pengumatan'];
    const viewMiddle = scrollY + window.innerHeight * 0.4;
    let current = '';
    sections.forEach((id) => {
      const el = document.getElementById(id);
      if (el && el.offsetTop <= viewMiddle) current = id;
    });
    activeSection.value = current;
  };
  window.addEventListener('scroll', scrollHandler, { passive: true });

  requestAnimationFrame(() => {
    scrollHandler();
  });

  window.addEventListener('keydown', trapFocus);
});

onUnmounted(() => {
  window.removeEventListener('scroll', scrollHandler);
  window.removeEventListener('resize', handleResize);
  if (onlineHandler) window.removeEventListener('online', onlineHandler);
  if (offlineHandler) window.removeEventListener('offline', offlineHandler);
  if (animationFrameId) cancelAnimationFrame(animationFrameId);
  revealObserver?.disconnect();
  window.removeEventListener('keydown', trapFocus);
  document.body.style.overflow = '';
});
</script>

<style scoped>
.skip-link {
  position: absolute;
  top: -40px;
  left: 0;
  z-index: 100;
  padding: 8px 16px;
  background: var(--color-emas-400);
  color: var(--color-hutan-800);
  font-weight: 900;
  text-decoration: none;
  border-radius: 0 0 0.5rem 0;
}
.skip-link:focus {
  top: 0;
}
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
