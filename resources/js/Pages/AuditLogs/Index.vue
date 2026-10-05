<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Audit Log</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Riwayat Aktivitas Sistem</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">
          Catatan semua aktivitas pengguna di sistem (Admin only).
        </p>
      </div>
    </div>

    <!-- Log aktivitas memuat jejak siapa mengubah apa, jadi seluruh isi
         halaman disembunyikan bila capability ini tidak dimiliki, bukan hanya
         tombolnya. Rute /audit-logs sudah role:Admin; gate ini memastikan
         halaman kosong bila daftar kapabilitas dan middleware pernah berbeda. -->
    <template v-if="canViewAudit">
    <div class="mb-4 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
      <p class="text-sm font-semibold text-[#EDD330]">Filter</p>
      <div class="mt-3 grid gap-3 sm:grid-cols-4">
        <input
          v-model="filters.action"
          placeholder="Aksi (contoh: member.created)"
          class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        />
        <input
          v-model="filters.entity_type"
          placeholder="Tipe entitas (contoh: Member)"
          class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        />
        <input
          v-model="filters.date_from"
          type="date"
          class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        />
        <input
          v-model="filters.date_to"
          type="date"
          class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        />
      </div>
      <button
        @click="applyFilters"
        class="mt-3 rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110"
      >
        Terapkan Filter
      </button>
    </div>

    <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="font-semibold text-[#f0ead8]">Daftar Log Aktivitas</h2>
        <span class="rounded-full bg-[#6F9435]/20 px-2.5 py-0.5 text-xs font-semibold text-[#EDD330]">{{ logs.total }} data</span>
      </div>

      <div v-if="logs.data.length" class="space-y-2">
        <div
          v-for="log in logs.data"
          :key="log.id"
          class="flex flex-col gap-2 rounded-xl border border-[#6F9435] p-4 sm:flex-row sm:items-start sm:justify-between"
        >
          <div class="min-w-0 flex-1">
            <p class="text-xs font-medium text-[#8fa06a]">
              {{ formatDate(log.created_at) }}
            </p>
            <p class="mt-1 text-sm font-semibold text-[#f0ead8]">
              <span class="rounded bg-[#6F9435]/20 px-2 py-0.5 text-xs font-bold text-[#EDD330]">{{ log.action }}</span>
            </p>
            <p class="mt-1 text-xs text-[#d4dc9a]">
              <span class="font-medium">Oleh:</span>
              {{ log.actor?.name || 'Sistem' }}
              <span class="mx-1 text-[#8fa06a]">|</span>
              <span class="font-medium">Entitas:</span>
              {{ log.entity_type?.split('\\').pop() || '—' }}
              <span class="mx-1 text-[#8fa06a]">|</span>
              <span class="font-medium">ID:</span>
              {{ log.entity_id || '—' }}
            </p>
            <pre
              v-if="log.metadata && Object.keys(log.metadata).length"
              class="mt-2 max-h-24 overflow-y-auto rounded border border-[#6F9435]/30 bg-[#263D26] p-2 text-xs text-[#d4dc9a]"
            >{{ JSON.stringify(log.metadata, null, 2) }}</pre>
          </div>
          <span class="text-xs text-[#8fa06a]" title="IP Address">
            {{ log.ip_address }}
          </span>
        </div>

        <Pagination :links="logs.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </div>

      <p v-else class="empty-state">
        <AppIcon name="audit" class="empty-state-icon h-6 w-6" />
        <span class="empty-state-text">Belum ada log aktivitas.</span>
      </p>
    </div>
    </template>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import Pagination from '@/Components/Pagination.vue';
import { useAccess } from '@/Composables/useAccess.js';

const props = defineProps({
  logs: Object,
  filters: Object,
});

const { can } = useAccess();
const canViewAudit = computed(() => can('audit.view'));

const filters = ref({
  action: props.filters.action || '',
  entity_type: props.filters.entity_type || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
});

function applyFilters() {
  router.get(
    '/audit-logs',
    filters.value,
    { preserveState: true }
  );
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>
