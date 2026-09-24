<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Permusyawaratan</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ meeting.judul }}</h1>
      <p class="mt-1 text-sm text-[#8fa06a]">{{ meeting.jenis }} • {{ formatDate(meeting.tanggal) }}</p>
    </div>

    <section class="mb-6 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
      <h2 class="mb-3 font-semibold text-[#f0ead8]">Daftar Hadir</h2>
      <div class="space-y-2">
        <div v-for="att in meeting.attendees" :key="att.id" class="flex items-center justify-between rounded-lg bg-[#263D26] px-4 py-2">
          <p class="text-sm text-[#f0ead8]">{{ att.member?.nama_lengkap }}</p>
          <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" :class="{
            'bg-[#A7B92B]/20 text-[#A7B92B]': att.status === 'Hadir',
            'bg-[#6F9435]/20 text-[#6F9435]': att.status === 'Izin',
            'bg-[#ef4419]/20 text-[#ef4419]': att.status === 'Tidak Hadir',
          }">{{ att.status }}</span>
        </div>
      </div>
    </section>

    <section v-if="meeting.agendas?.length" class="mb-6 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
      <h2 class="mb-3 font-semibold text-[#f0ead8]">Agenda</h2>
      <div class="space-y-2">
        <div v-for="agenda in meeting.agendas" :key="agenda.id" class="rounded-lg bg-[#263D26] p-3">
          <p class="text-sm font-bold text-[#f0ead8]">{{ agenda.urutan }}. {{ agenda.judul }}</p>
          <p v-if="agenda.votes?.length" class="mt-1 text-[10px] text-[#8fa06a]">
            <span class="text-[#A7B92B]">{{ ag.votes.filter(v => v.pilihan === 'Setuju').length }} Setuju</span> •
            <span class="text-[#ef4419]">{{ ag.votes.filter(v => v.pilihan === 'Tidak Setuju').length }} Tolak</span> •
            <span class="text-[#EDD330]">{{ ag.votes.filter(v => v.pilihan === 'Abstain').length }} Abstain</span>
          </p>
        </div>
      </div>
    </section>

    <section v-if="meeting.minutes?.length" class="mb-6 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
      <h2 class="mb-3 font-semibold text-[#f0ead8]">Notulen</h2>
      <div v-for="m in meeting.minutes" :key="m.id" class="space-y-2 rounded-lg bg-[#263D26] p-3">
        <p class="text-sm whitespace-pre-line text-[#f0ead8]">{{ m.notulen }}</p>
        <p v-if="m.keputusan" class="text-xs text-[#A7B92B]"><strong>Keputusan:</strong> {{ m.keputusan }}</p>
        <p v-if="m.tindak_lanjut" class="text-xs text-[#EDD330]"><strong>Tindak Lanjut:</strong> {{ m.tindak_lanjut }}</p>
      </div>
    </section>

    <Link v-if="isAdmin" :href="`/meetings`" class="inline-flex items-center rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">← Kembali</Link>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

defineProps({ meeting: Object });
const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'Admin' || page.props.auth?.user?.role === 'Pembina');

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}
</script>
