<template>
  <div class="min-h-screen bg-hutan-800 text-krem-100">
    <header class="app-header">
      <div class="app-header-inner">
        <button
            type="button"
            class="app-header-button lg:hidden"
            :class="{ 'is-active': mobileMenuOpen }"
            aria-label="Buka menu navigasi"
            :aria-expanded="mobileMenuOpen"
            @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <NavIcon :name="mobileMenuOpen ? 'close' : 'menu'" :stroke="2" class="h-5 w-5" />
        </button>

        <Link href="/dashboard" class="app-header-brand">
          <span class="app-header-logo h-10 w-10 sm:h-12 sm:w-12">
            <img :src="ambalan?.logo_url || '/images/Logo_Ambalan.png'" alt="Logo Ambalan" class="h-full w-full object-contain" />
          </span>

          <span class="min-w-0">
            <span class="app-header-name">{{ ambalan?.nama || 'Pramuka SMAN 2 Maros' }}</span>
            <span class="app-header-context">{{ context.current }}</span>
            <span class="app-header-tagline">Ekosistem Digital Ambalan</span>
          </span>
        </Link>

        <nav class="app-header-trail" aria-label="Lokasi halaman">
          <template v-if="context.parent">
            <span class="app-header-trail-parent">{{ context.parent }}</span>
            <NavIcon name="chevronRight" class="app-header-trail-sep h-3.5 w-3.5" />
          </template>
          <span class="app-header-trail-current">{{ context.current }}</span>
        </nav>

        <div class="app-header-actions">
          <Link href="/notifications" class="app-header-button" :aria-label="notifLabel">
            <NavIcon name="bell" class="h-5 w-5" />
            <span v-if="unreadNotifications > 0" class="app-header-badge" aria-hidden="true">
              {{ unreadNotifications > 99 ? '99+' : unreadNotifications }}
            </span>
          </Link>

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

    <div
        class="app-body mx-auto grid w-full transition-[grid-template-columns] duration-300 ease-out lg:px-[50px]"
        :class="collapsed ? 'lg:grid-cols-[5.25rem_1fr]' : 'lg:grid-cols-[17.5rem_1fr]'"
    >
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
            class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
            @click="closeMobileMenu"
        ></div>
      </transition>

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
            class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col border-r-2 border-daun-400/30 bg-hutan-800 shadow-nav lg:hidden"
        >
          <div class="flex items-center justify-between gap-3 border-b-2 border-daun-400/20 px-4 py-3">
            <div class="flex min-w-0 items-center gap-2.5">
              <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border-2 border-daun-500 bg-hutan-900">
                <img :src="ambalan?.logo_url || '/images/Logo_Ambalan.png'" alt="Logo Ambalan" class="h-full w-full object-contain" />
              </span>
              <div class="min-w-0">
                <span class="block truncate text-xs font-bold text-emas-400">{{ ambalan?.nama || 'Pramuka SMAN 2 Maros' }}</span>
                <span class="block text-[10px] font-medium text-lumut-400">Ekosistem Digital</span>
              </div>
            </div>
            <button
                type="button"
                class="rounded-lg border-2 border-daun-500 p-1.5 text-lumut-400 transition hover:bg-daun-500/30 hover:text-emas-400"
                aria-label="Tutup menu"
                @click="closeMobileMenu"
            >
              <NavIcon name="close" :stroke="2" class="h-5 w-5" />
            </button>
          </div>

          <div class="flex min-h-0 flex-1 flex-col px-4 py-4">
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

      <aside
          class="app-header-offset app-rail-height hidden lg:sticky lg:flex lg:flex-col lg:overflow-hidden lg:border-r-2 lg:border-daun-400/15 lg:py-5 lg:pr-4"
      >
        <AppSidebar
            v-model:query="query"
            :user="user"
            :sections="sections"
            :footer="footer"
            :collapsed="collapsed"
            :no-results="noResults"
            :result-count="resultCount"
            @toggle-collapse="toggleCollapsed"
        />
      </aside>

      <main class="min-w-0 px-4 pb-24 pt-6 sm:px-6 lg:px-8 lg:pb-6">
        <OfflineBanner />
        <FlashMessage />
        <slot />
      </main>
    </div>

    <nav
        v-show="!mobileMenuOpen"
        class="fixed inset-x-0 bottom-0 z-50 border-t-2 border-daun-500 bg-hutan-900/98 backdrop-blur lg:hidden"
        aria-label="Navigasi bawah"
    >
      <div class="mx-auto flex h-16 max-w-lg items-stretch justify-around">
        <Link
            v-for="item in bottomItems"
            :key="item.key"
            :href="item.href"
            class="bottom-nav-item"
            :class="{ 'is-active': item.active }"
            :aria-current="item.active ? 'page' : undefined"
        >
          <span class="bottom-nav-indicator" aria-hidden="true" />
          <NavIcon :name="item.icon" class="h-5 w-5" />
          <span class="leading-tight">{{ item.label }}</span>
        </Link>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
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
    collapsed,
    sections,
    bottomItems,
    footer,
    context,
    noResults,
    resultCount,
    toggleCollapsed,
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

// Laci menu menutup halaman di belakangnya; gulir harus dikunci selama drawer
// terbuka, dan dilepas lagi saat komponen dilepas.
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
    // Antrean presensi milik pengguna ini tidak boleh terkirim sebagai akun lain,
    // dan cache halaman berisi data pribadi harus hilang dari perangkat.
    clearQueue();
    await purgeUserScopedCaches();

    router.post('/logout');
}
</script>