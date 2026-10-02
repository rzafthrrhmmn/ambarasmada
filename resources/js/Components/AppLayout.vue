<template>
  <div class="min-h-screen bg-hutan-800 text-krem-100">
    <header class="sticky top-0 z-30 border-b-2 border-emas-400/50 bg-hutan-800/95 backdrop-blur">
      <div class="mx-auto flex w-full items-center justify-between px-4 py-3 sm:px-6 lg:px-[50px]">
        <div class="flex items-center gap-3">
          <button
              type="button"
              class="rounded-lg border-2 p-2 text-emas-400 transition lg:hidden"
              :class="mobileMenuOpen ? 'border-emas-400 bg-emas-400/10' : 'border-daun-500 hover:bg-daun-500/20'"
              aria-label="Buka menu navigasi"
              :aria-expanded="mobileMenuOpen"
              @click="mobileMenuOpen = !mobileMenuOpen"
          >
            <NavIcon :name="mobileMenuOpen ? 'close' : 'menu'" :stroke="2" class="h-5 w-5" />
          </button>

          <Link href="/dashboard" class="flex items-center gap-3">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-daun-500 bg-hutan-900">
              <img :src="ambalan?.logo_url || '/images/Logo_Ambalan.png'" alt="Logo Ambalan" class="h-full w-full object-contain" />
            </span>
            <span>
              <span class="block text-sm font-bold text-emas-400">{{ ambalan?.nama || 'Pramuka SMAN 2 Maros' }}</span>
              <span class="block text-xs font-medium text-lumut-400">Ekosistem Digital Ambalan</span>
            </span>
          </Link>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
          <Link
              href="/notifications"
              class="relative rounded-lg border-2 border-daun-500 p-2 text-krem-300 transition hover:bg-daun-500/30 hover:text-emas-400"
              aria-label="Notifikasi"
          >
            <NavIcon name="bell" class="h-5 w-5" />
            <span
                v-if="unreadNotifications > 0"
                class="absolute -right-1.5 -top-1.5 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-[#ef4419] px-1 text-[10px] font-bold leading-tight text-white"
            >
              {{ unreadNotifications > 99 ? '99+' : unreadNotifications }}
            </span>
          </Link>

          <span class="hidden rounded-full border-2 border-daun-400 bg-hutan-700 px-3 py-1 text-xs font-bold text-emas-400 sm:inline-flex">
            {{ user?.role }}
          </span>

          <button
              v-if="pwaInstallAvailable"
              type="button"
              class="rounded-lg border-2 border-emas-400 px-3 py-2 text-xs font-bold text-emas-400 transition hover:bg-emas-400/10"
              @click="installPwa"
          >
            Pasang Aplikasi
          </button>

          <form method="POST" action="/logout" @submit.prevent="logout">
            <button class="rounded-lg border-2 border-daun-500 px-3 py-2 text-xs font-bold text-krem-300 transition hover:bg-daun-500/30 hover:text-emas-400">
              Keluar
            </button>
          </form>
        </div>
      </div>
    </header>

    <div
        class="mx-auto grid min-h-[calc(100vh-4.25rem)] w-full transition-[grid-template-columns] duration-300 ease-out lg:px-[50px]"
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
          class="hidden lg:sticky lg:top-[4.25rem] lg:flex lg:h-[calc(100vh-4.25rem)] lg:flex-col lg:overflow-hidden lg:border-r-2 lg:border-daun-400/15 lg:py-5 lg:pr-4"
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
const mobileMenuOpen = ref(false);
const pwaInstallAvailable = ref(!!window.pwaInstallAvailable);

const {
    user,
    query,
    collapsed,
    sections,
    bottomItems,
    footer,
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