<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end section-animate animate-fade-up">
      <div>
        <p class="text-sm font-medium text-emas-400">Notifikasi</p>
        <h1 class="mt-1 text-2xl font-bold text-krem-100">Pusat Notifikasi</h1>
        <p class="mt-1 text-sm text-krem-300">Tetap update dengan kabar terkini dari ambalan.</p>
      </div>
      <button @click="markAllRead" class="rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-daun-500/30 hover:text-emas-400">Tandai Semua Dibaca</button>
    </div>

    <!-- Notifikasi milik penerima, jadi daftar dan aksi per-notifikasi (Baca,
         Hapus, Tandai Semua Dibaca) tidak diberi gate peran: NotificationController
         menolak aksi atas notifikasi orang lain dengan 403 lewat pengecekan
         user_id. Menyembunyikannya berdasarkan peran justru membuat anggota
         tidak bisa membersihkan kotaknya sendiri. -->
    <template v-if="canAccessNotifications">
    <div v-if="unreadCount > 0" class="mb-4 rounded-xl border-2 border-emas-400/50 bg-emas-400/10 px-4 py-3 text-sm text-emas-400">
      Anda memiliki <strong>{{ unreadCount }}</strong> notifikasi belum dibaca.
    </div>

    <div class="space-y-3 section-animate animate-fade-up" style="animation-delay: 0.1s;">
      <div v-for="notif in notifications.data" :key="notif.id" class="rounded-xl border-2 p-4 transition" :class="notif.is_read ? 'border-[#6F9435]/30 bg-hutan-600' : 'border-emas-400/50 bg-hutan-600/80'">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h3 class="text-sm font-bold text-krem-100">{{ notif.title }}</h3>
            <p class="mt-1 text-xs leading-5 text-[#d4dc9a]">{{ notif.message }}</p>
            <time class="mt-2 block text-[10px] font-medium text-krem-300">{{ formatDate(notif.created_at) }}</time>
          </div>
          <div class="flex gap-2 shrink-0">
            <button v-if="!notif.is_read" @click="markRead(notif)" class="rounded-lg border border-[#6F9435] px-2 py-1 text-[10px] font-bold text-[#d4dc9a] hover:bg-daun-500/20">Baca</button>
            <button @click="deleteNotif(notif)" class="rounded-lg border border-[#ef4419]/50 px-2 py-1 text-[10px] font-bold text-[#ef4419] hover:bg-[#ef4419]/20">Hapus</button>
          </div>
        </div>
      </div>
      <p v-if="!notifications.data.length" class="empty-state">
        <AppIcon name="bell" class="empty-state-icon h-6 w-6" />
        <span class="empty-state-text">Belum ada notifikasi.</span>
      </p>
    </div>
    <Pagination :links="notifications.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
    </template>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import Pagination from '@/Components/Pagination.vue';
import { useAccess } from '@/Composables/useAccess.js';

defineProps({ notifications: Object, unreadCount: Number });
const page = usePage();
const form = useForm({});

/**
 * Halaman ini untuk semua akun aktif, sesuai kelompok middleware
 * auth+approved+verified+not-alumni yang menaungi /notifications.
 */
const { can } = useAccess();
const canAccessNotifications = computed(() => can('notifications.access'));

function markRead(notif) {
  form.patch(`/notifications/${notif.id}/read`, {
    onSuccess: () => { window.location.reload(); },
  });
}

function markAllRead() {
  form.post('/notifications/read-all', {
    onSuccess: () => { window.location.reload(); },
  });
}

function deleteNotif(notif) {
  form.delete(`/notifications/${notif.id}`, {
    onSuccess: () => { window.location.reload(); },
  });
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';
}
</script>

