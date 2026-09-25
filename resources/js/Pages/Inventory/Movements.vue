<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Inventaris</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Riwayat Perubahan {{ inventory.nama_barang }}</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">
          Kode: <span class="font-semibold">{{ inventory.kode_barang }}</span> |
          Status: {{ inventory.status_pinjam }} |
          Kondisi: {{ inventory.kondisi }}
        </p>
      </div>
      <Link
        :href="`/inventory`"
        class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/20"
      >
        Kembali ke Inventaris
      </Link>
    </div>

    <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-sm">
      <h2 class="mb-4 font-semibold text-[#f0ead8]">Riwayat Pergerakan Stok</h2>

      <div v-if="movements.length" class="space-y-2">
        <div
          v-for="movement in movements"
          :key="movement.id"
          class="flex flex-col gap-2 rounded-xl border border-[#6F9435] p-4"
        >
          <div class="flex items-center justify-between">
            <p class="flex items-center gap-2 text-sm font-semibold text-[#f0ead8]">
              {{ movement.jenis }}
              <span
                class="rounded px-2 py-0.5 text-xs font-bold"
                :class="movement.jumlah >= 0 ? 'bg-[#A7B92B]/20 text-[#A7B92B]' : 'bg-[#ef4419]/20 text-[#ef4419]'"
              >{{ movement.jumlah >= 0 ? '+' : '' }}{{ movement.jumlah }}</span>
            </p>
            <span class="text-xs text-[#8fa06a]">
              {{ formatDate(movement.created_at) }}
            </span>
          </div>
          <p v-if="movement.referensi" class="text-xs text-[#d4dc9a]">
            Referensi: {{ movement.referensi.split(':')[0]?.split('.').pop() || movement.referensi }}
          </p>
          <p v-if="movement.catatan" class="text-sm text-[#d4dc9a]">
            {{ movement.catatan }}
          </p>
          <p class="text-xs text-[#8fa06a]">
            Oleh: {{ movement.actor?.name || 'Sistem' }}
          </p>
        </div>
      </div>

      <p v-else class="empty-state empty-state-text">
        Belum ada riwayat pergerakan untuk barang ini.
      </p>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

defineProps({
  inventory: Object,
  movements: Array,
});

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
