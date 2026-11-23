<template>
  <div class="min-h-screen selection:bg-daun-500/30 selection:text-krem-100">
    <!-- Background glow layer -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
      <div
        class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-daun-500/20 blur-[120px] mix-blend-overlay filter"
        style="filter: blur(120px)"
      ></div>
      <div
        class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-emas-400/10 blur-[120px]"
      ></div>
    </div>

    <!-- Header -->
    <header class="app-header relative z-40">
      <div class="app-header-inner px-4 sm:px-6 lg:px-8 xl:px-10 py-3 sm:py-3.5 flex items-center justify-between">
        <!-- Mobile menu toggle -->
        <button
            type="button"
            class="app-header-button lg:hidden flex items-center justify-center w-9 h-9 rounded-xl border border-daun-400/30 bg-hutan-700/60 backdrop-blur-sm text-krem-100 hover:bg-daun-500/30 hover:border-daun-400/50 transition-all duration-200"
            :class="{ 'bg-daun-500/20 border-daun-400/60': mobileMenuOpen }"
            aria-label="Buka menu navigasi"
            :aria-expanded="mobileMenuOpen"
            @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <NavIcon :name="mobileMenuOpen ? 'close' : 'menu'" :stroke="2" class="h-5 w-5" />
        </button>

        <!-- Brand -->
        <a href="/dashboard" class="app-header-brand flex items-center gap-3 min-w-0">
          <div class="app-header-logo flex-shrink-0 relative h-10 w-10 sm:h-12 sm:w-12 rounded-2xl border border-daun-400/30 bg-hutan-900 overflow-hidden shadow-lg shadow-daun-500/10">
            <img :src="ambalan?.logo_url || '/images/Logo_Ambalan.png'" alt="Logo Ambalan" class="h-full w-full object-contain p-1" />
            <div class="absolute -right-1 -bottom-1 h-3 w-3 rounded-full bg-emas-400 border-2 border-hutan-800 animate-pulse" aria-hidden="true" />
          </div>

          <div class="min-w-0">
            <span class="block truncate font-bold text-krem-100 text-sm tracking-tight">{{ ambalan?.nama || 'Pramuka SMAN 2 Maros' }}</span>
            <span class="block truncate text-[10px] font-medium text-lumut-400 uppercase tracking-wider">{{ context.current }}</span>
          </div>
        </a>

        <!-- Header center: breadcrumb + search -->
        <div class="hidden lg:flex items-center gap-6 flex-1 max-w-2xl">
          <nav class="flex items-center gap-2 text-xs" aria-label="Lokasi halaman">
            <template v-if="context.parent">
              <span class="truncate text-lumut-400">{{ context.parent }}</span>
              <NavIcon name="chevronRight" class="h-3 w-3 text-krem-300/50" />
            </template>
            <span class="truncate font-semibold text-krem-100">{{ context.current }}</span>
          </nav>
        </div>

        <!-- Header actions -->
        <div class="app-header-actions flex items-center gap-2">
          <a href="/notifications" class="flex items-center justify-center w-9 h-9 rounded-xl border border-daun-400/30 bg-hutan-700/60 backdrop-blur-sm text-krem-100 hover:bg-daun-500/30 hover:border-daun-400/50 relative transition-all duration-200" :aria-label="notifLabel" :title="notifLabel">
            <NavIcon name="bell" class="h-5 w-5" />
            <span v-if="unreadNotifications > 0" class="absolute right-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-[#ef4419] text-[10px] font-bold text-white border border-hutan-800 ring-2 ring-hutan-800" aria-hidden="true">
              {{ unreadNotifications > 99 ? '99+' : unreadNotifications }}
            </span>
          </a>

          <AppHeaderUserMenu
              :user="user"
              :unread-notifications="unreadNotifications"
              :pwa-install-available="pwaInstallAvailable"
              @logout="logout"
              @install="installPwa"
          />
        </div>
      </div>
    </header>

    <!-- Body: sidebar + main -->
    <div class="app-body relative">
      <!-- Mobile drawer overlay -->
      <transition
          enter-active-class="transition ease-out duration-300"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition ease-in duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
      >
        <div
            v-if="mobileMenuOpen"
            class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm lg:hidden"
            @click="closeMobileMenu"
        ></div>
      </transition>

      <!-- Mobile drawer -->
      <transition
          enter-active-class="transition ease-out duration-300 transform"
          enter-from-class="-translate-x-full"
          enter-to-class="translate-x-0"
          leave-active-class="transition ease-in duration-200 transform"
          leave-from-class="translate-x-0"
          leave-to-class="-translate-x-full"
      >
        <aside
            v-if="mobileMenuOpen"
            class="fixed inset-y-0 left-0 z-50 flex w-full max-w-[85vw] flex-col border-r border-daun-400/20 bg-hutan-800/90 backdrop-blur-lg shadow-2xl lg:hidden"
        >
          <div class="flex items-center justify-between gap-3 border-b border-daun-400/20 px-5 py-4">
            <div class="flex min-w-0 items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-daun-500 bg-hutan-900">
                <img :src="ambalan?.logo_url || '/images/Logo_Ambalan.png'" alt="Logo Ambalan" class="h-full w-full object-contain p-1" />
              </div>
              <div class="min-w-0">
                <span class="block truncate text-sm font-bold text-emas-400">{{ ambalan?.nama || 'Pramuka SMAN 2 Maros' }}</span>
                <span class="block text-[10px] font-medium text-lumut-400">Ekosistem Digital</span>
              </div>
            </div>
            <button
                type="button"
                class="w-9 h-9 rounded-xl border border-daun-500 bg-hutan-900 text-lumut-400 flex items-center justify-center transition hover:bg-daun-500/30 hover:text-emas-400"
                aria-label="Tutup menu"
                @click="closeMobileMenu"
            >
              <NavIcon name="close" :stroke="2" class="h-5 w-5" />
            </button>
          </div>

          <div class="flex min-h-0 flex-1 flex-col px-5 py-4 overflow-y-auto">
            <AppSidebar
                v-model:query="query"
                :user="user"
                :sections="sections"
                :footer="footer"
                :no-results="noResults"
                :result-count="resultCount"
                :show-toggle="false"
                @navigate="closeMobileMenu"
            />
          </div>
        </aside>
      </transition>

      <!-- Desktop sidebar -->
      <aside class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:left-0 lg:z-30 lg:w-[260px] border-r border-daun-400/20 bg-hutan-800/40 backdrop-blur-lg lg:overflow-y-auto">
        <div class="flex flex-col h-full px-5 py-5">
          <AppSidebar
              v-model:query="query"
              :user="user"
              :sections="sections"
              :footer="footer"
              :no-results="noResults"
              :result-count="resultCount"
              :show-toggle="true"
          />
        </div>
      </aside>

      <!-- Main content -->
      <main class="min-w-0 px-4 sm:px-6 lg:pl-[280px] xl:pl-[300px] lg:px-8 xl:px-10 pt-[100px] sm:pt-[112px] lg:pt-[116px] xl:pt-[120px] pb-24 section-animate animate-fade-up">
        <OfflineBanner />
        <FlashMessage />
        <slot />
      </main>
    </div>

    <!-- Mobile bottom nav -->
    <nav
        v-show="!mobileMenuOpen"
        class="fixed inset-x-0 bottom-0 z-50 border-t border-daun-500/20 bg-hutan-900/90 backdrop-blur-xl shadow-2xl shadow-black/30 lg:hidden"
        aria-label="Navigasi bawah"
    >
      <div class="mx-auto flex max-w-md items-stretch justify-between px-3 sm:px-6 py-3">
        <a
            v-for="item in bottomItems"
            :key="item.key"
            :href="item.href"
            class="bottom-nav-item flex flex-col items-center justify-center gap-1.5 px-2 py-2 text-[10px] font-medium transition-all duration-200 relative"
            :class="{
              'text-emas-400': item.active,
              'text-lumut-400': !item.active,
            }"
            :aria-current="item.active ? 'page' : undefined"
        >
          <div class="relative">
            <NavIcon :name="item.icon" class="h-5 w-5 transition-all duration-200" :class="{ 'scale-110': item.active, 'text-emas-400': item.active, 'text-lumut-400': !item.active }" />
            <span class="bottom-nav-indicator absolute -top-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-emas-400 opacity-0 transition-opacity duration-200" :class="{ 'opacity-100': item.active }" aria-hidden="true" />
          </div>
          <span class="leading-none" :class="{ 'font-black': item.active, 'font-medium': !item.active }">{{ item.label }}</span>
        </a>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppHeaderUserMenu from '@/Components/AppHeaderUserMenu.vue';
import AppSidebar from '@/Components/AppSidebar.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import NavIcon from '@/Components/NavIcon.vue';
import OfflineBanner from '@/Components/OfflineBanner.vue';
import { useNavigation } from '@/Composables/useNavigation.js';
import { clear as clearQueue } from '@/OfflineQueue.js';
import { purgeUserScopedCaches } from '@/ServiceWorker.js';

const page = usePage();
const ambalan = computed(() => page.props.ambalan || null);
const unreadNotifications = computed(() => Number(page.props.unreadNotificationCount ?? 0) || 0);
const notifLabel = computed(() =>
    unreadNotifications.value > 0 ? `Notifikasi, ${unreadNotifications.value} belum dibaca` : 'Notifikasi',
);
const mobileMenuOpen = ref(false);
const pwaInstallAvailable = ref(!!window.pwaInstallAvailable);

const {
    user,
    query,
    sections,
    bottomItems,
    footer,
    context,
    noResults,
    resultCount,
} = useNavigation(page);

function closeMobileMenu() {
    mobileMenuOpen.value = false;
}

function installPwa() {
    if (typeof window.installPwa !== 'function') {
        return;
    }

    window.installPwa().then(() => {
        pwaInstallAvailable.value = false;
    });
}

function handlePwaInstallReady() {
    pwaInstallAvailable.value = true;
}

function handlePwaInstallUnavailable() {
    pwaInstallAvailable.value = false;
}

function handleEscape(event) {
    if (event.key === 'Escape') {
        closeMobileMenu();
    }
}

function handleResize() {
    if (window.innerWidth >= 1024) {
        closeMobileMenu();
    }
}

watch(mobileMenuOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

onMounted(() => {
    window.addEventListener('pwa-install-ready', handlePwaInstallReady);
    window.addEventListener('pwa-install-unavailable', handlePwaInstallUnavailable);
    window.addEventListener('keydown', handleEscape);
    window.addEventListener('resize', handleResize);
});

onBeforeUnmount(() => {
    window.removeEventListener('pwa-install-ready', handlePwaInstallReady);
    window.removeEventListener('pwa-install-unavailable', handlePwaInstallUnavailable);
    window.removeEventListener('keydown', handleEscape);
    window.removeEventListener('resize', handleResize);
    document.body.style.overflow = '';
});

async function logout() {
    clearQueue();
    await purgeUserScopedCaches();

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    if (token) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = token;
        form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
}
</script>
