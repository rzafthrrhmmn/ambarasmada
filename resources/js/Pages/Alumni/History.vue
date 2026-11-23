<template>
  <AppLayout>
    <div class="page-head">
      <div>
        <p class="page-eyebrow">Portal Alumni</p>
        <h1 class="page-title">Arsip Sejarah Kepengurusan</h1>
        <p class="page-subtitle">Rekam jejak susunan Dewan Ambalan dari periode pertama hingga periode terkini.</p>
      </div>
    </div>

    <div class="space-y-4">
      <template v-for="meeting in meetingsData" :key="meeting.id">
        <section class="card p-5 border-l-4 border-daun-500">
          <div class="flex items-start justify-between gap-4 mb-3">
            <div>
              <h3 class="text-lg font-bold text-krem-100">{{ meeting.judul }}</h3>
              <p class="text-xs text-krem-300 flex items-center gap-1.5">
                <AppIcon name="calendar" class="h-3 w-3 shrink-0" />
                <span>{{ meeting.tanggal }}</span>
              </p>
            </div>
            <span class="badge px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider text-krem-100 border border-daun-500">
              Rapat Dewan
            </span>
          </div>
          <div class="space-y-3">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div class="bg-hutan-700/50 p-3 rounded-lg">
                <p class="text-xs text-daun-400 uppercase">Tanggal</p>
                <p class="text-krem-100">{{ meeting.tanggal }}</p>
              </div>
              <div class="bg-hutan-700/50 p-3 rounded-lg">
                <p class="text-xs text-daun-400 uppercase">Waktu</p>
                <p class="text-krem-100">{{ meeting.waktu_mulai }} - {{ meeting.waktu_selesai }}</p>
              </div>
              <div class="bg-hutan-700/50 p-3 rounded-lg">
                <p class="text-xs text-daun-400 uppercase">Lokasi</p>
                <p class="text-krem-100">{{ meeting.lokasi }}</p>
              </div>
              <div class="bg-hutan-700/50 p-3 rounded-lg">
                <p class="text-xs text-daun-400 uppercase">Pembina</p>
                <p class="text-krem-100">{{ meeting.createdBy?.name || '-' }}</p>
              </div>
            </div>
            <div class="mt-3 p-3 rounded-lg bg-hutan-700/30 border border-daun-500/20">
              <p class="text-xs font-bold uppercase text-daun-400 mb-1">Agenda</p>
              <p class="text-sm text-krem-300 leading-relaxed">{{ meeting.agenda || 'Tidak ada catatan agenda.' }}</p>
            </div>
          </div>
        </section>
      </template>
    </div>

    <div v-if="pagination.last_page > 1" class="mt-8 flex justify-center">
      <Pagination
        :page="pagination.current_page"
        :pages="pagination.last_page"
        :url="window => router.visit(window, { preserveState: true })"
      />
    </div>

    <p v-if="meetingsData.length === 0" class="empty-state text-center py-12">
      <AppIcon name="archive" class="empty-state-icon h-8 w-8 mx-auto mb-3" />
      <span class="empty-state-text">Belum ada arsip rapat yang tercatat.</span>
    </p>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import Pagination from '@/Components/Pagination.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const meetings = computed(() => page.props?.meetings || { data: [], current_page: 1, last_page: 1 });
const meetingsData = computed(() => meetings.value.data || []);
const pagination = computed(() => ({
  current_page: meetings.value.current_page || 1,
  last_page: meetings.value.last_page || 1,
}));
</script>
