<template>
  <div class="min-h-screen bg-[#263D26] text-[#f0ead8]">
    <header class="sticky top-0 z-30 border-b-2 border-[#EDD330]/50 bg-[#263D26]/95 backdrop-blur">
      <div class="mx-auto flex max-w-[1500px] items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="rounded-lg border-2 p-2 text-[#EDD330] transition lg:hidden" :class="mobileMenuOpen ? 'border-[#EDD330] bg-[#EDD330]/10' : 'border-[#6F9435] hover:bg-[#6F9435]/20'" aria-label="Toggle menu">
            <svg class="h-5 w-5 transition-transform duration-200" :class="mobileMenuOpen ? 'rotate-45' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
          </button>
          <Link href="/dashboard" class="flex items-center gap-3">
            <span v-if="ambalan?.logo_url" class="flex h-12 w-12 items-center justify-center rounded-xl border-2 border-[#6F9435] bg-[#263D26] overflow-hidden"><img :src="ambalan.logo_url" alt="Logo" class="h-full w-full object-contain" /></span>
            <span v-else class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-[#A7B92A] to-[#EDD330] font-extrabold text-[#263D26] shadow-lg shadow-[#EDD330]/40 border-2 border-[#EDD330]">P</span>
            <span>
              <span class="block text-sm font-bold text-[#EDD330]">{{ ambalan?.nama || 'Pramuka SMAN 2 Maros' }}</span>
              <span class="block text-xs font-medium text-[#8fa06a]">Ekosistem Digital Ambalan</span>
            </span>
          </Link>
        </div>
        <div class="flex items-center gap-3">
          <span class="hidden rounded-full border-2 border-[#A7B92A] bg-[#2d4a2d] px-3 py-1 text-xs font-bold text-[#EDD330] sm:inline-flex">{{ user?.role }}</span>
          <button v-if="pwaInstallAvailable" @click="installPwa" class="rounded-lg border-2 border-[#EDD330] px-3 py-2 text-xs font-bold text-[#EDD330] transition hover:bg-[#EDD330]/10">Pasang Aplikasi</button>
          <form method="POST" action="/logout" @submit.prevent="logout">
            <button class="rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">Keluar</button>
          </form>
        </div>
      </div>
    </header>

    <div class="mx-auto grid min-h-[calc(100vh-4.25rem)] w-full max-w-[1500px] lg:grid-cols-[15rem_1fr]">
      <!-- Mobile Drawer -->
      <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden" @click="mobileMenuOpen = false"></div>
      </transition>

      <transition
        enter-active-class="transition ease-out duration-300 transform"
        enter-from-class="-translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition ease-in duration-200 transform"
        leave-from-class="translate-x-0"
        leave-to-class="-translate-x-full"
      >
        <aside v-if="mobileMenuOpen" class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col border-r-2 border-[#A7B92A]/30 bg-[#263D26] p-4 shadow-2xl lg:hidden">
          <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span v-if="ambalan?.logo_url" class="flex h-10 w-10 items-center justify-center rounded-xl border-2 border-[#6F9435] bg-[#263D26] overflow-hidden"><img :src="ambalan.logo_url" alt="Logo" class="h-full w-full object-contain" /></span>
              <span v-else class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#A7B92A] to-[#EDD330] font-extrabold text-[#263D26] shadow-lg border-2 border-[#EDD330]">P</span>
              <div>
                <span class="block text-sm font-bold text-[#EDD330]">{{ ambalan?.nama || 'Pramuka SMAN 2 Maros' }}</span>
                <span class="block text-xs font-medium text-[#8fa06a]">Ekosistem Digital</span>
              </div>
            </div>
            <button @click="mobileMenuOpen = false" class="rounded-lg border-2 border-[#6F9435] p-1.5 text-[#8fa06a] hover:bg-[#6F9435]/30 hover:text-[#EDD330]" aria-label="Tutup menu">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <nav class="flex-1 space-y-1 overflow-y-auto py-2">
            <Link v-for="item in visibleNavItems" :key="item.href" :href="item.href" :class="navClass(item.href)" @click="mobileMenuOpen = false">
              <span v-html="item.icon" class="mr-3 inline-flex h-5 w-5 items-center justify-center"></span>
              <span class="flex items-center gap-2">
                {{ item.label }}
                <span v-if="item.href === '/members/pending' && page.props.pendingCount > 0" class="rounded-full bg-[#EDD330] px-1.5 py-0.5 text-[10px] font-bold text-[#263D26]">{{ page.props.pendingCount }}</span>
              </span>
            </Link>
          </nav>

          <div class="mt-4 space-y-3 border-t-2 border-[#A7B92A]/30 pt-4">
            <div class="flex items-center gap-3 rounded-xl border-2 border-[#6F9435]/40 bg-[#263D26] p-3">
              <span class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-[#A7B92A] bg-[#2d4a2d] text-xs font-bold text-[#EDD330]">
                {{ user?.name?.charAt(0) || 'U' }}
              </span>
              <div class="min-w-0">
                <p class="truncate text-sm font-bold text-[#f0ead8]">{{ user?.name }}</p>
                <p class="truncate text-[11px] font-medium text-[#8fa06a]">{{ user?.role }}</p>
              </div>
            </div>
            <div class="rounded-xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-4 text-xs leading-5 text-[#d4dc9a]">
              <p class="font-bold text-[#EDD330]">Siap berlatih?</p>
              <p class="mt-1 text-[#8fa06a]">Kelola kegiatan ambalan dengan data yang tertib dan transparan.</p>
            </div>
          </div>
        </aside>
      </transition>

      <!-- Desktop Sidebar -->
      <aside class="hidden w-64 border-r-2 border-[#A7B92A]/30 bg-[#263D26] p-4 lg:sticky lg:top-[4.25rem] lg:h-[calc(100vh-4.25rem)] lg:overflow-y-auto lg:block">
        <nav class="space-y-1">
          <Link v-for="item in visibleNavItems" :key="item.href" :href="item.href" :class="navClass(item.href)">
            <span v-html="item.icon" class="mr-3 inline-flex h-5 w-5 items-center justify-center"></span>
            <span class="flex items-center gap-2">
              {{ item.label }}
              <span v-if="item.href === '/members/pending' && page.props.pendingCount > 0" class="rounded-full bg-[#EDD330] px-1.5 py-0.5 text-[10px] font-bold text-[#263D26]">{{ page.props.pendingCount }}</span>
            </span>
          </Link>
        </nav>
        <div class="mt-8 rounded-xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-4 text-xs leading-5 text-[#d4dc9a]">
          <p class="font-bold text-[#EDD330]">Siap berlatih?</p>
          <p class="mt-1 text-[#8fa06a]">Kelola kegiatan ambalan dengan data yang tertib dan transparan.</p>
        </div>
      </aside>

      <main class="min-w-0 px-4 pb-20 pt-6 sm:px-6 sm:pb-6 lg:pb-6 lg:px-8">
        <FlashMessage />
        <slot />
      </main>
    </div>

    <nav
      :class="{ 'hidden': mobileMenuOpen }"
      class="lg:hidden fixed inset-x-0 bottom-0 z-50 bg-[#1e2e1e] border-t-2 border-[#6F9435]"
    >
      <div class="flex h-16 items-center justify-around">
        <template v-for="item in visibleBottomNavItems" :key="item.href">
          <Link
            :href="item.href"
            :class="bottomNavClass(item.href)"
          >
            <div v-html="item.icon" class="mx-auto mb-1 h-5 w-5"></div>
            <span class="text-[10px] font-bold leading-tight">{{ item.label }}</span>
          </Link>
        </template>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user || null);
const ambalan = computed(() => page.props.ambalan || null);
const mobileMenuOpen = ref(false);
const pwaInstallAvailable = ref(!!window.pwaInstallAvailable);

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

onMounted(() => {
    window.addEventListener('pwa-install-ready', handlePwaInstallReady);
    window.addEventListener('pwa-install-unavailable', handlePwaInstallUnavailable);
});

onBeforeUnmount(() => {
    window.removeEventListener('pwa-install-ready', handlePwaInstallReady);
    window.removeEventListener('pwa-install-unavailable', handlePwaInstallUnavailable);
});

const icons = {
  dashboard: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5M9 3.75V20.25" /></svg>',
  members: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>',
  attendance: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M9 9.563a3 3 0 105.138-2.121 3 3 0 00-5.138 2.121zM15 12l3.6-3.6m0 0L16.8 6.6m1.8 1.8h-3.6" /></svg>',
  sku: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>',
  finance: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125h20.25M12 6V5.25m0 13.5h.008v.008H12V18.75zm0-4.5h.008v.008H12v-.008zm0-4.5h.008v.008H12v-.008z" /></svg>',
  inventory: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>',
  announcements: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.54.847.92.617.427-.256.898-.427 1.398-.427.5 0 .97.171 1.397.427.38.23.85-.193.92-.617l.149-.894c.09-.542.56-.94 1.11-.94h.001c.55 0 1.02.398 1.11.94l.149.894c.07.424.54.847.92.617.427-.256.898-.427 1.398-.427.5 0 .97.171 1.397.427.38.23.85-.193.92-.617l.149-.894c.09-.542.56-.94 1.11-.94" /></svg>',
  alumni: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.412c1.11 0 2.21.048 3.25.143V15" /></svg>',
  letters: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.02M4.5 11.25h.008m0 0h.008M4.5 11.25v11.25h13.5V11.25M4.5 6.75v4.5m13.5-4.5v4.5M4.5 6.75h13.5M4.5 6.75L3 5.25m16.5 1.5L19.5 5.25m0 0L21 3.75M3 3.75l1.5 1.5" /></svg>',
  materials: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.2527L12 6.2527M12 6.2527C12 6.2527 7.5 10.5 7.5 14.25C7.5 16.7811 9.46946 18.75 12 18.75C14.5305 18.75 16.5 16.7811 16.5 14.25C16.5 10.5 12 6.2527 12 6.2527ZM12 6.2527L12 6.2527M12 6.2527L12 6.2527M12 18.75V18.75" /></svg>',
  guides: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.02M4.5 11.25h.008m0 0h.008M4.5 11.25v11.25h13.5V11.25M4.5 6.75v4.5m13.5-4.5v4.5M4.5 6.75h13.5M4.5 6.75L3 5.25m16.5 1.5L19.5 5.25m0 0L21 3.75M3 3.75l1.5 1.5" /></svg>',
  pending: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m4-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
  profile: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>',
  medias: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.194 12A4.5 4.5 0 009 17.88V21a.75.75 0 001.5 0v-3.115A4.5 4.5 0 0014.806 12H19.5a.75.75 0 000-1.5h-4.694A4.5 4.5 0 009.194 12zm0 0A4.5 4.5 0 019 6.12V3a.75.75 0 00-1.5 0v3.115A4.5 4.5 0 012.694 12H-2.5a.75.75 0 000 1.5h5.194A4.5 4.5 0 019 17.88V21a.75.75 0 001.5 0v-3.115A4.5 4.5 0 0114.806 12H19.5a.75.75 0 000-1.5h-4.694A4.5 4.5 0 019.194 12z" /></svg>',
  peta: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>',
  notifications: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>',
  events: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>',
  meetings: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M9 9.563a3 3 0 105.138-2.121 3 3 0 00-5.138 2.121zM15 12l3.6-3.6m0 0L16.8 6.6m1.8 1.8h-3.6" /></svg>',
  assessments: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
  certificates: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" /></svg>',
  reports: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>',
  teams: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m0 0a3 3 0 00-4.681 2.72m0 0a9.093 9.093 0 01-3.741.479 9.093 9.093 0 01-3.741-.479m3.741.479a3 3 0 004.681 2.72m0 0a3 3 0 004.681-2.72m0 0a9.094 9.094 0 003.741.479 9.094 9.094 0 003.741-.479m-12.122.479a9.093 9.093 0 013.741-.479 9.093 9.093 0 013.741.479m3.741-.479a3 3 0 004.681 2.72m0 0a3 3 0 004.681-2.72m0 0a9.094 9.094 0 003.741.479 9.094 9.094 0 003.741-.479m-12.122.479a9.093 9.093 0 013.741-.479 9.093 9.093 0 013.741.479" /></svg>',
  medias: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.194 12A4.5 4.5 0 009 17.88V21a.75.75 0 001.5 0v-3.115A4.5 4.5 0 0014.806 12H19.5a.75.75 0 000-1.5h-4.694A4.5 4.5 0 009.194 12zm0 0A4.5 4.5 0 019 6.12V3a.75.75 0 00-1.5 0v3.115A4.5 4.5 0 012.694 12H-2.5a.75.75 0 000 1.5h5.194A4.5 4.5 0 019 17.88V21a.75.75 0 001.5 0v-3.115A4.5 4.5 0 0114.806 12H19.5a.75.75 0 000-1.5h-4.694A4.5 4.5 0 019.194 12z" /></svg>',
};

const items = [
  { href: '/dashboard', label: 'Dashboard', icon: icons.dashboard, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/profile', label: 'Profil', icon: icons.profile, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/peta', label: 'Peta Kontur', icon: icons.peta, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/notifications', label: 'Notifikasi', icon: icons.notifications, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/events', label: 'Kegiatan', icon: icons.events, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/meetings', label: 'Rapat', icon: icons.meetings, roles: ['Admin', 'Pembina', 'Pengurus'] },
  { href: '/members', label: 'Anggota', icon: icons.members, roles: ['Admin', 'Pembina', 'Pengurus'] },
  { href: '/members/pending', label: 'Menunggu Persetujuan', icon: icons.pending, roles: ['Admin', 'Pembina'] },
  { href: '/attendance', label: 'Kehadiran', icon: icons.attendance, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/sku', label: 'SKU / TKU', icon: icons.sku, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/assessments', label: 'Penilaian', icon: icons.assessments, roles: ['Admin', 'Pembina'] },
  { href: '/certificates', label: 'Sertifikat', icon: icons.certificates, roles: ['Admin', 'Pembina'] },
  { href: '/field-guides', label: 'Buku Saku', icon: icons.guides, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/articles', label: 'Blog', icon: icons.announcements, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/galleries', label: 'Galeri', icon: icons.medias, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/teams', label: 'Gugus Depan', icon: icons.teams, roles: ['Admin', 'Pembina', 'Pengurus'] },
  { href: '/finance', label: 'Keuangan', icon: icons.finance, roles: ['Admin', 'Pembina', 'Pengurus'] },
  { href: '/finance', label: 'Iuran Saya', icon: icons.finance, roles: ['Anggota'] },
  { href: '/inventory', label: 'Inventaris', icon: icons.inventory, roles: ['Admin', 'Pembina', 'Pengurus'] },
  { href: '/announcements', label: 'Pengumuman', icon: icons.announcements, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/letters', label: 'Persuratan', icon: icons.letters, roles: ['Pembina', 'Pengurus'] },
  { href: '/materials', label: 'Materi', icon: icons.materials, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/reports', label: 'Laporan', icon: icons.reports, roles: ['Admin', 'Pembina', 'Pengurus'] },
  { href: '/ambalan', label: 'Pengaturan', icon: icons.guides, roles: ['Admin', 'Pembina'] },
  { href: '/alumni/dashboard', label: 'Portal Alumni', icon: icons.alumni, roles: ['Alumni'] },
];
const visibleNavItems = computed(() => {
  const role = user.value?.role;
  if (!role) {
    return [];
  }

  return items.filter((item) => {
    if (! item.roles.includes(role)) {
      return false;
    }

    if (item.href === '/finance' && item.label === 'Keuangan' && role === 'Pengurus') {
      return user.value.is_juru_uang ?? false;
    }

    return true;
  });
});
const bottomNavItems = [
  { href: '/dashboard', label: 'Beranda', icon: icons.dashboard, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/attendance', label: 'Kehadiran', icon: icons.attendance, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/sku', label: 'SKU', icon: icons.sku, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/peta', label: 'Peta', icon: icons.peta, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/profile', label: 'Profil', icon: icons.profile, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/notifications', label: 'Notif', icon: icons.notifications, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/events', label: 'Kegiatan', icon: icons.events, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
];
const visibleBottomNavItems = computed(() => {
  const role = user.value?.role;
  if (!role) {
    return [];
  }
  return bottomNavItems.filter((item) => item.roles.includes(role));
});
const currentPath = computed(() => page.url.split('?')[0]);
function navClass(href) {
  const active = currentPath.value === href || (href !== '/dashboard' && currentPath.value.startsWith(href + '/'));
  return `mb-1 flex rounded-lg px-3 py-2.5 text-sm font-bold transition border-l-4 ${
    active
      ? 'bg-[#6F9435]/40 text-[#EDD330] border-[#EDD330]'
      : 'text-[#d4dc9a] hover:bg-[#335233] hover:text-[#EDD330] border-transparent'
  }`;
}
function bottomNavClass(href) {
  const active = currentPath.value === href || (href !== '/dashboard' && currentPath.value.startsWith(href + '/'));
  return `flex flex-col items-center justify-center flex-1 text-xs ${
    active
      ? 'text-[#EDD330]'
      : 'text-[#d4dc9a]'
  }`;
}
function logout() {
  router.post('/logout');
}
</script>


