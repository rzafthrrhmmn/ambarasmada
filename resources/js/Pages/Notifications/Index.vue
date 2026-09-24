<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Notifikasi</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Pusat Notifikasi</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Tetap update dengan kabar terkini dari ambalan.</p>
      </div>
      <button @click="markAllRead" class="rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">Tandai Semua Dibaca</button>
    </div>

    <div v-if="unreadCount > 0" class="mb-4 rounded-xl border-2 border-[#EDD330]/50 bg-[#EDD330]/10 px-4 py-3 text-sm text-[#EDD330]">
      Anda memiliki <strong>{{ unreadCount }}</strong> notifikasi belum dibaca.
    </div>

    <div class="space-y-3">
      <div v-for="notif in notifications.data" :key="notif.id" class="rounded-xl border-2 p-4 transition" :class="notif.is_read ? 'border-[#6F9435]/30 bg-[#335233]' : 'border-[#EDD330]/50 bg-[#335233]/80'">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h3 class="text-sm font-bold text-[#f0ead8]">{{ notif.title }}</h3>
            <p class="mt-1 text-xs leading-5 text-[#d4dc9a]">{{ notif.message }}</p>
            <time class="mt-2 block text-[10px] font-medium text-[#8fa06a]">{{ formatDate(notif.created_at) }}</time>
          </div>
          <div class="flex gap-2 shrink-0">
            <button v-if="!notif.is_read" @click="markRead(notif)" class="rounded-lg border border-[#6F9435] px-2 py-1 text-[10px] font-bold text-[#d4dc9a] hover:bg-[#6F9435]/20">Baca</button>
            <button @click="deleteNotif(notif)" class="rounded-lg border border-[#ef4419]/50 px-2 py-1 text-[10px] font-bold text-[#ef4419] hover:bg-[#ef4419]/20">Hapus</button>
          </div>
        </div>
      </div>
      <p v-if="!notifications.data.length" class="rounded-xl border-2 border-[#6F9435]/30 bg-[#335233] p-10 text-center text-sm text-[#8fa06a]">Belum ada notifikasi.</p>
    </div>
    <Pagination :links="notifications.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({ notifications: Object, unreadCount: Number });
const page = usePage();
const form = useForm({});

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

