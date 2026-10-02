<template>
  <div ref="root" class="relative">
    <button
        type="button"
        class="app-header-user-trigger"
        :class="{ 'is-open': open }"
        aria-haspopup="menu"
        :aria-expanded="open"
        aria-label="Menu pengguna"
        @click="toggle"
    >
      <span class="app-header-avatar" aria-hidden="true">{{ initials }}</span>

      <span class="app-header-user-meta">
        <span class="app-header-user-name">{{ user?.name || 'Pengguna' }}</span>
        <span class="app-header-user-role">{{ user?.role || '-' }}</span>
      </span>

      <NavIcon
          name="chevronDown"
          class="hidden h-4 w-4 text-lumut-400 transition-transform duration-150 sm:block"
          :class="{ 'rotate-180': open }"
      />
    </button>

    <transition
        enter-active-class="transition ease-out duration-150"
        enter-from-class="translate-y-1 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-1 opacity-0"
    >
      <div v-if="open" class="app-header-menu" role="menu" @keydown.esc.stop="close">
        <div class="app-header-menu-head">
          <span class="app-header-avatar h-10 w-10 text-xs" aria-hidden="true">{{ initials }}</span>

          <div class="min-w-0">
            <p class="truncate text-sm font-bold text-krem-100">{{ user?.name || 'Pengguna' }}</p>
            <p class="truncate text-[11px] font-medium text-lumut-400">{{ user?.role || '-' }}</p>
            <p v-if="user?.nta" class="truncate text-[10px] font-medium text-daun-500">NTA {{ user.nta }}</p>
          </div>
        </div>

        <p class="app-header-menu-label mt-2">Akun</p>

        <Link href="/profile" class="app-header-menu-item" role="menuitem" @click="close">
          <NavIcon name="profile" class="h-4 w-4 shrink-0 text-lumut-400" />
          <span class="truncate">Profil Saya</span>
        </Link>

        <Link href="/notifications" class="app-header-menu-item" role="menuitem" @click="close">
          <NavIcon name="bell" class="h-4 w-4 shrink-0 text-lumut-400" />
          <span class="truncate">Notifikasi</span>
          <span v-if="unreadNotifications > 0" class="app-header-menu-count">
            {{ unreadNotifications > 99 ? '99+' : unreadNotifications }}
          </span>
        </Link>

        <button
            v-if="pwaInstallAvailable"
            type="button"
            class="app-header-menu-item"
            role="menuitem"
            @click="handleInstall"
        >
          <NavIcon name="download" class="h-4 w-4 shrink-0 text-lumut-400" />
          <span class="truncate">Pasang Aplikasi</span>
        </button>

        <div class="app-header-menu-sepx" role="separator"></div>

        <button type="button" class="app-header-menu-item is-danger" role="menuitem" @click="requestLogout">
          <NavIcon name="logout" class="h-4 w-4 shrink-0" />
          <span class="truncate">Keluar</span>
        </button>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import NavIcon from '@/Components/NavIcon.vue';

/**
 * Menu akun di kanan header.
 *
 * Tombol "Pasang Aplikasi" dan "Keluar" sebelumnya selalu terlihat sebagai
 * tombol teks, sehingga header menjadi sempit di layar kecil dan "Keluar"
 * mudah tidak sengaja tersentuh. Keduanya kini berada di sini: header cukup
 * berisi ikon di mobile, sementara nama dan peran tetap terbaca di layar lebar.
 */
const props = defineProps({
    user: { type: Object, default: null },
    unreadNotifications: { type: Number, default: 0 },
    pwaInstallAvailable: { type: Boolean, default: false },
});

const emit = defineEmits(['logout', 'install']);

const page = usePage();
const open = ref(false);
const root = ref(null);

const initials = computed(() => {
    const parts = String(props.user?.name || '')
        .split(/\s+/)
        .filter(Boolean);

    if (parts.length === 0) {
        return 'U';
    }

    return parts
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
});

function toggle() {
    open.value = !open.value;
}

function close() {
    open.value = false;
}

function handleInstall() {
    close();
    emit('install');
}

function requestLogout() {
    close();
    emit('logout');
}

function handlePointerDown(event) {
    if (open.value && root.value && !root.value.contains(event.target)) {
        close();
    }
}

function handleEscape(event) {
    if (event.key === 'Escape') {
        close();
    }
}

// Inertia hanya mengganti isi halaman, jadi komponen header tidak pernah
// dilepas. Menutup menu saat navigasi selesai mencegah menu tertinggal terbuka.
watch(() => page.url, close);

onMounted(() => {
    document.addEventListener('pointerdown', handlePointerDown);
    document.addEventListener('keydown', handleEscape);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', handlePointerDown);
    document.removeEventListener('keydown', handleEscape);
});
</script>