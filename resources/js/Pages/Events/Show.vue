<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Kegiatan</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ event.nama }}</h1>
      <p class="mt-1 text-sm text-[#8fa06a]">{{ event.deskripsi }}</p>
    </div>

    <div class="grid gap-4 mb-6 sm:grid-cols-2">
      <div class="rounded-xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
        <p class="text-[10px] font-medium text-[#8fa06a]">Tanggal</p>
        <p class="text-sm font-bold text-[#f0ead8]">{{ formatDate(event.tanggal) }}</p>
      </div>
      <div class="rounded-xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
        <p class="text-[10px] font-medium text-[#8fa06a]">Lokasi</p>
        <p class="text-sm font-bold text-[#f0ead8]">{{ event.lokasi || '-' }}</p>
      </div>
      <div class="rounded-xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
        <p class="text-[10px] font-medium text-[#8fa06a]">Jenis</p>
        <p class="text-sm font-bold text-[#f0ead8]">{{ event.jenis }}</p>
      </div>
      <div class="rounded-xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
        <p class="text-[10px] font-medium text-[#8fa06a]">Status</p>
        <p class="text-sm font-bold" :class="{
          'text-[#A7B92B]': event.status === 'Aktif',
          'text-[#EDD330]': event.status === 'Draft',
          'text-[#ef4419]': event.status === 'Dibatalkan',
        }">{{ event.status }}</p>
      </div>
    </div>

    <section class="mb-6 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
      <h2 class="mb-4 font-semibold text-[#f0ead8]">Daftar Peserta</h2>
      <div class="space-y-2">
        <div v-for="p in participants" :key="p.id" class="flex items-center justify-between rounded-lg bg-[#263D26] px-4 py-3">
          <div>
            <p class="text-sm font-bold text-[#f0ead8]">{{ p.member?.nama_lengkap }}</p>
            <p class="text-[10px] text-[#8fa06a]">{{ p.member?.user?.role }}</p>
          </div>
          <span class="rounded-full px-2.5 py-1 text-[10px] font-bold" :class="{
            'bg-[#EDD330]/20 text-[#EDD330]': p.status === 'Pending',
            'bg-[#A7B92B]/20 text-[#A7B92B]': p.status === 'Hadir',
            'bg-[#6F9435]/20 text-[#6F9435]': p.status === 'Izin',
            'bg-[#ef4419]/20 text-[#ef4419]': p.status === 'Tidak Hadir',
          }">{{ p.status }}</span>
        </div>
      </div>
    </section>

    <div v-if="event.agendas?.length" class="rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
      <h2 class="mb-4 font-semibold text-[#f0ead8]">Agenda</h2>
      <div class="space-y-2">
        <div v-for="agenda in event.agendas" :key="agenda.id" class="rounded-lg bg-[#263D26] p-3">
          <p class="text-sm font-bold text-[#f0ead8]">{{ agenda.urutan }}. {{ agenda.judul }}</p>
        </div>
      </div>
    </div>

    <Link v-if="isAdmin" :href="`/events`" class="mt-4 inline-flex items-center rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">← Kembali</Link>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

defineProps({ event: Object, participants: Array });
const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'Admin' || page.props.auth?.user?.role === 'Pembina');

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}
</script>
