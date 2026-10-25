<template>
  <div ref="container" class="relative min-h-screen overflow-hidden bg-hutan-800 font-sans guest-scroll">
    <!-- Particle Background -->
    <ParticleBackground
      :particle-count="isMobile ? 40 : 80"
      :colors="['#A7B92B', '#EDD330', '#6F9435', '#f0ead8']"
      :interactive="true"
      :connection-distance="140"
      :mouse-influence="180"
      class="absolute inset-0"
    />

    <!-- Decorative Background Blobs -->
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
      <div class="absolute -top-40 -left-40 h-[500px] w-[500px] rounded-full bg-gradient-to-br from-daun-400/15 via-daun-400/5 to-transparent blur-[100px] will-change-transform" data-parallax-bg></div>
      <div class="absolute -right-40 top-20 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-emas-400/15 via-emas-400/5 to-transparent blur-[100px] will-change-transform"></div>
      <div class="absolute -bottom-40 left-1/3 h-[350px] w-[350px] rounded-full bg-gradient-to-br from-daun-500/20 to-transparent blur-[100px] will-change-transform"></div>
      <div class="guest-noise-overlay absolute inset-0 opacity-30" aria-hidden="true"></div>
    </div>

    <!-- Skip Link -->
    <a href="#main-content" class="guest-focus-ring skip-link">Lewati ke konten utama</a>

    <!-- Guest Header (Navigation + Mobile Menu) -->
    <GuestHeader
      :ambalan-nama="ambalanNama"
      :logo-url="logoUrl"
      :is-mobile-menu-open="uiStore.mobileMenuOpen"
      @toggle-mobile-menu="toggleMobileMenu"
      @close-mobile-menu="closeMobileMenu"
      @logo-error="handleLogoError"
    />

    <!-- Offline Banner -->
    <OfflineBanner :is-online="isOnline" :can-dismiss="true" @dismiss="dismissOfflineBanner" />

    <main id="main-content" class="relative mx-auto w-full px-4 py-8 sm:px-6 sm:py-12 lg:px-[50px] lg:py-20">
      <!-- Hero Section -->
      <HeroSection
        :slider-slides="guestStore.sliderSlides"
        :ambalan-nama="ambalanNama"
        :logo-url="logoUrl"
        @logo-click="handleLogoClick"
        @logo-error="handleLogoError"
      />

      <!-- Stats Section -->
      <StatsSection :stats-items="statsItems" :enable-repeat-animation="false" />

      <!-- How It Works Section -->
      <HowItWorksSection :steps="howItWorks" />

      <!-- Gallery Section -->
      <GallerySection :gallery="guestStore.gallery" @gallery-item-click="handleGalleryItemClick" />

      <!-- Features Section -->
      <FeaturesSection :features="features" @feature-click="handleFeatureClick" />

      <!-- Testimonials Section -->
      <TestimonialsSection :testimonials="testimonials" />

      <!-- Announcements Section -->
      <AnnouncementsSection :announcements="guestStore.announcements" :display-limit="6" />

      <!-- CTA Section -->
      <CTASection />

      <!-- Newsletter Section -->
      <NewsletterSection :initial-email="newsletterEmail" @subscribe="handleNewsletterSubscribe" />

      <!-- Guest Footer -->
      <GuestFooter :ambalan-nama="ambalanNama" :logo-url="logoUrl" @logo-error="handleLogoError" />
    </main>

    <!-- Mobile Bottom Nav -->
    <GuestBottomNav
      :is-mobile="isMobile"
      :active-section="activeSection"
      @navigate="onBottomNavNavigate"
    />

    <!-- Dismissed Offline Banner State -->
    <template v-if="offlineBannerDismissed">
      <!-- Banner is hidden, nothing to render -->
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useGuestStore } from '@/Stores/guest';
import { useUIStore } from '@/Stores/ui';

// Section Components
import GuestHeader from '@/Components/Guest/GuestHeader.vue';
import OfflineBanner from '@/Components/Guest/OfflineBanner.vue';
import HeroSection from '@/Components/Guest/HeroSection.vue';
import StatsSection from '@/Components/Guest/StatsSection.vue';
import HowItWorksSection from '@/Components/Guest/HowItWorksSection.vue';
import GallerySection from '@/Components/Guest/GallerySection.vue';
import FeaturesSection from '@/Components/Guest/FeaturesSection.vue';
import TestimonialsSection from '@/Components/Guest/TestimonialsSection.vue';
import AnnouncementsSection from '@/Components/Guest/AnnouncementsSection.vue';
import CTASection from '@/Components/Guest/CTASection.vue';
import NewsletterSection from '@/Components/Guest/NewsletterSection.vue';
import GuestFooter from '@/Components/Guest/GuestFooter.vue';
import GuestBottomNav from '@/Components/Guest/GuestBottomNav.vue';

// Shared Components
import ParticleBackground from '@/Components/ParticleBackground.vue';

const guestStore = useGuestStore();
const uiStore = useUIStore();
const page = usePage();

const props = defineProps({
  announcements: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({ members: 0, alumni: 0 }) },
  sliderSlides: { type: Array, default: () => [] },
  gallery: { type: Array, default: () => [] },
});

// Refs
const container = ref(null);
const isMobile = ref(false);
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const offlineBannerDismissed = ref(false);
const activeSection = ref('');
const newsletterEmail = ref('');
let animationFrameId = null;
let scrollHandler = null;
let revealObserver = null;
let lastFocusedElement = null;
let onlineHandler = null;
let offlineHandler = null;

// Computed
const ambalanNama = computed(() => {
  return page.props?.ambalan?.nama || 'Ambalan UPT SMAN 2 Maros';
});

const logoUrl = computed(() => {
  return page.props?.ambalan?.logo_url || '/images/Logo_Ambalan.png';
});

const statsItems = computed(() => [
  {
    label: 'Anggota Aktif',
    key: 'members',
    value: guestStore.stats.value?.members ?? props.stats.members,
    icon: 'members',
  },
  {
    label: 'Alumni Tercatat',
    key: 'alumni',
    value: guestStore.stats.value?.alumni ?? props.stats.alumni,
    icon: 'alumni',
  },
  {
    label: 'Layanan Digital',
    key: 'services',
    value: '5+',
    icon: 'tools',
  },
  {
    label: 'Akses Ponsel',
    key: 'access',
    value: 'PWA',
    icon: 'download',
  },
]);

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

// Helper Functions
function easeOutExpo(t) {
  return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
}

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

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
}

function handleLogoError() {
  // Logo error handled in child components, just log
  console.warn('[Guest/Home] Logo image failed to load, using fallback.');
}

function handleLogoClick() {
  // Logo is already on home page, click is decorative
  console.log('[Guest/Home] Logo clicked.');
}

function handleGalleryItemClick(idx) {
  console.log(`[Guest/Home] Gallery item ${idx} clicked.`);
}

function handleFeatureClick(feature) {
  console.log(`[Guest/Home] Feature clicked: ${feature.title}`);
}

function handleNewsletterSubscribe(email) {
  console.log(`[Guest/Home] Newsletter subscribed: ${email}`);
}

function handleGalleryItemClickHandler(idx) {
  // Placeholder for future analytics
}

function toggleMobileMenu() {
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

function dismissOfflineBanner() {
  offlineBannerDismissed.value = true;
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

function updateActiveSection() {
  if (!container.value) return;
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  const scrollY = window.scrollY;
  const viewMiddle = scrollY + window.innerHeight * 0.4;
  let current = '';

  const sections = ['fitur', 'dokumentasi', 'testimoni', 'pengumatan'];
  sections.forEach((id) => {
    const el = document.getElementById(id);
    if (el && el.offsetTop <= viewMiddle) current = id;
  });
  activeSection.value = current;
}

function handleScroll() {
  if (!container.value) return;
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  const scrollY = window.scrollY;

  // Parallax background
  const bg = container.value.querySelector('[data-parallax-bg]');
  if (bg) bg.style.transform = `translateY(${scrollY * 0.3}px)`;

  // Parallax hero
  const hero = container.value.querySelector('[data-parallax-hero]');
  if (hero) hero.style.transform = `translateY(${scrollY * 0.1}px)`;

  updateActiveSection();
}

function setupIntersectionObserver() {
  const observerOptions = { threshold: 0.1 };
  revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          if (entry.target.dataset.animate === 'stats') {
            // Stats animation handled by StatsSection component
          }
          revealObserver.unobserve(entry.target);
        }
      });
    },
    observerOptions
  );

  if (container.value) {
    container.value.querySelectorAll('[data-animate]').forEach((el) => revealObserver.observe(el));
  }
}

// Lifecycle
onMounted(() => {
  setMetaTags();
  handleResize();
  window.addEventListener('resize', handleResize);
  onlineHandler = () => { isOnline.value = true; };
  offlineHandler = () => { isOnline.value = false; };
  window.addEventListener('online', onlineHandler);
  window.addEventListener('offline', offlineHandler);

  // Initialize guest store from props
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

  // Fetch fresh data from API
  void guestStore.fetchGuestData().catch((err) => {
    console.error('[Guest/Home] Gagal memuat data tamu dari API:', err?.message ?? err);
  });

  setupIntersectionObserver();

  scrollHandler = handleScroll;
  window.addEventListener('scroll', scrollHandler, { passive: true });
  window.addEventListener('keydown', trapFocus);

  requestAnimationFrame(() => {
    handleScroll();
  });
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

// Watch gallery length changes for scroll state
watch(
  () => guestStore.gallery.length,
  () => nextTick(() => {
    // Gallery scroll state handled by GallerySection component
  })
);
</script>

<style scoped>
.skip-link {
  position: absolute;
  top: -40px;
  left: 0;
  z-index: 100;
  padding: 8px 16px;
  background: var(--color-emas-400, #EDD330);
  color: var(--color-hutan-800, #263D26);
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

.guest-focus-ring {
  outline: none;
}
.guest-focus-ring:focus-visible {
  outline: 2px solid #EDD330;
  outline-offset: 2px;
}
</style>
