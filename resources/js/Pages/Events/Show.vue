<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-emas-400">Kegiatan</p>
      <h1 class="mt-1 text-2xl font-bold text-krem-100">{{ event.nama }}</h1>
      <p class="mt-1 text-sm text-krem-300">{{ event.deskripsi }}</p>
    </div>

    <div class="grid gap-4 mb-6 sm:grid-cols-2">
      <div class="rounded-xl border-2 border-[#6F9435]/40 bg-hutan-600 p-4">
        <p class="text-[10px] font-medium text-krem-300">Tanggal</p>
        <p class="text-sm font-bold text-krem-100">{{ formatDate(event.tanggal) }}</p>
      </div>
      <div class="rounded-xl border-2 border-[#6F9435]/40 bg-hutan-600 p-4">
        <p class="text-[10px] font-medium text-krem-300">Lokasi</p>
        <p class="text-sm font-bold text-krem-100">{{ event.lokasi || '-' }}</p>
      </div>
      <div class="rounded-xl border-2 border-[#6F9435]/40 bg-hutan-600 p-4">
        <p class="text-[10px] font-medium text-krem-300">Jenis</p>
        <p class="text-sm font-bold text-krem-100">{{ event.jenis }}</p>
      </div>
      <div class="rounded-xl border-2 border-[#6F9435]/40 bg-hutan-600 p-4">
        <p class="text-[10px] font-medium text-krem-300">Status</p>
        <p class="text-sm font-bold" :class="{
          'text-[#A7B92B]': event.status === 'Aktif',
          'text-emas-400': event.status === 'Draft',
          'text-[#ef4419]': event.status === 'Dibatalkan',
        }">{{ event.status }}</p>
      </div>
    </div>

    <section class="mb-6 rounded-2xl border-2 border-[#6F9435]/40 bg-hutan-600 p-5">
      <h2 class="mb-4 font-semibold text-krem-100">Daftar Peserta</h2>
      <div class="space-y-2">
        <div v-for="p in participants" :key="p.id" class="flex items-center justify-between rounded-lg bg-hutan-800 px-4 py-3">
          <div>
            <p class="text-sm font-bold text-krem-100">{{ p.member?.nama_lengkap }}</p>
            <p class="text-[10px] text-krem-300">{{ p.member?.user?.role }}</p>
          </div>
          <span class="rounded-full px-2.5 py-1 text-[10px] font-bold" :class="{
            'bg-emas-400/20 text-emas-400': p.status === 'Pending',
            'bg-[#A7B92B]/20 text-[#A7B92B]': p.status === 'Hadir',
            'bg-daun-500/20 text-[#6F9435]': p.status === 'Izin',
            'bg-[#ef4419]/20 text-[#ef4419]': p.status === 'Tidak Hadir',
          }">{{ p.status }}</span>
        </div>
      </div>
    </section>

    <div v-if="event.agendas?.length" class="rounded-2xl border-2 border-[#6F9435]/40 bg-hutan-600 p-5">
      <h2 class="mb-4 font-semibold text-krem-100">Agenda</h2>
      <div class="space-y-2">
        <div v-for="agenda in event.agendas" :key="agenda.id" class="rounded-lg bg-hutan-800 p-3">
          <p class="text-sm font-bold text-krem-100">{{ agenda.urutan }}. {{ agenda.judul }}</p>
        </div>
      </div>
    </div>

    <a v-if="canManage" href="/events" class="btn-ghost mt-4">
      <AppIcon name="arrowLeft" class="h-4 w-4" />
      Kembali
    </a>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useAccess } from '@/Composables/useAccess.js';

defineProps({ event: Object, participants: Array });

const { can } = useAccess();

// Gate lama membandingkan nilai role langsung di template. Admin dan Pembina
// persis penjaga updateParticipant di EventController, jadi sekarang dibaca dari
// daftar kapabilitas: halaman ini tidak lagi menyimpan daftar peran sendiri
// yang bisa melenceng dari server.
const canManage = computed(() => can('events.participant.manage'));

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}
</script>
