<template>
  <div class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-950/95">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <Link href="/dashboard" class="flex items-center gap-3">
          <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-700 font-bold text-white shadow-lg shadow-blue-700/20">P</span>
          <span>
            <span class="block text-sm font-semibold">Pramuka SMAN 2 Maros</span>
            <span class="block text-xs text-gray-500 dark:text-gray-400">Ekosistem Digital Ambalan</span>
          </span>
        </Link>
        <div class="flex items-center gap-3">
          <span class="hidden rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-800 dark:bg-blue-950 dark:text-blue-200 sm:inline-flex">{{ user?.role }}</span>
          <form method="POST" action="/logout" @submit.prevent="logout">
            <button class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-900">Keluar</button>
          </form>
        </div>
      </div>
    </header>

    <div class="mx-auto grid min-h-[calc(100vh-4.25rem)] max-w-7xl lg:grid-cols-[15rem_1fr]">
      <aside class="hidden border-r border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950 lg:block">
        <nav class="space-y-1">
          <Link v-for="item in visibleNavItems" :key="item.href" :href="item.href" :class="navClass(item.href)">
            <span v-html="item.icon" class="mr-3 inline-flex h-5 w-5 items-center justify-center"></span>
            {{ item.label }}
          </Link>
        </nav>
        <div class="mt-8 rounded-xl bg-blue-50 p-4 text-xs leading-5 text-blue-900 dark:bg-blue-950/50 dark:text-blue-200">
          <p class="font-semibold">Siap berlatih?</p>
          <p class="mt-1 text-blue-700 dark:text-blue-300">Gunakan menu di atas untuk mengelola kegiatan ambalan.</p>
        </div>
      </aside>

      <main class="min-w-0 px-4 py-6 sm:px-6 lg:px-8">
        <FlashMessage />
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user || null);

const icons = {
  dashboard: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5M9 3.75V20.25" /></svg>',
  members: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>',
  attendance: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M9 9.563a3 3 0 105.138-2.121 3 3 0 00-5.138 2.121zM15 12l3.6-3.6m0 0L16.8 6.6m1.8 1.8h-3.6" /></svg>',
  sku: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>',
  finance: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125h20.25M12 6V5.25m0 13.5h.008v.008H12V18.75zm0-4.5h.008v.008H12v-.008zm0-4.5h.008v.008H12v-.008z" /></svg>',
  inventory: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>',
  alumni: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.412c1.11 0 2.21.048 3.25.143V15" /></svg>',
};

const items = [
  { href: '/dashboard', label: 'Dashboard', icon: icons.dashboard, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/members', label: 'Anggota', icon: icons.members, roles: ['Admin', 'Pembina', 'Pengurus'] },
  { href: '/attendance', label: 'Kehadiran', icon: icons.attendance, roles: ['Admin', 'Pembina', 'Pengurus'] },
  { href: '/sku', label: 'SKU / TKU', icon: icons.sku, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/finance', label: 'Keuangan', icon: icons.finance, roles: ['Admin', 'Pembina', 'Pengurus'] },
  { href: '/inventory', label: 'Inventaris', icon: icons.inventory, roles: ['Admin', 'Pembina', 'Pengurus'] },
  { href: '/announcements', label: 'Pengumuman', icon: icons.alumni, roles: ['Admin', 'Pembina', 'Pengurus', 'Anggota'] },
  { href: '/alumni/dashboard', label: 'Portal Alumni', icon: icons.alumni, roles: ['Alumni'] },
];
const visibleNavItems = computed(() => items.filter((item) => item.roles.includes(user.value?.role)));
const currentPath = computed(() => page.url.split('?')[0]);
function navClass(href) {
  const active = currentPath.value === href || (href !== '/dashboard' && currentPath.value.startsWith(href + '/'));
  return `mb-1 flex rounded-lg px-3 py-2.5 text-sm font-medium transition ${active ? 'bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-900'}`;
}
function logout() {
  router.post('/logout');
}
</script>
