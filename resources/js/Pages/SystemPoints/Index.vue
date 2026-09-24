<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Kelembagaan</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Sistem Point</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola poin kelembagaan ambalan.</p>
      </div>
    </div>

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
      <div class="rounded-2xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-lg">
        <p class="text-xs font-bold uppercase tracking-wide text-[#8fa06a]">Total Poin</p>
        <p class="mt-2 text-3xl font-extrabold text-[#EDD330]">{{ totalPoints }}</p>
      </div>
      <div class="rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5 shadow-lg">
        <p class="text-xs font-bold uppercase tracking-wide text-[#8fa06a]">Jumlah Data</p>
        <p class="mt-2 text-3xl font-extrabold text-[#EDD330]">{{ points.total }}</p>
      </div>
      <div class="rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5 shadow-lg">
        <p class="text-xs font-bold uppercase tracking-wide text-[#8fa06a]">Kategori</p>
        <p class="mt-2 text-3xl font-extrabold text-[#EDD330]">{{ categories.length }}</p>
      </div>
    </div>

    <div class="mb-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
      <p class="text-sm font-semibold text-[#EDD330]">Tambah Poin</p>
      <form @submit.prevent="addPoint" class="mt-3 grid gap-3 sm:grid-cols-3">
        <select v-model="form.member_id" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
          <option value="">Pilih anggota</option>
          <option v-for="m in allMembers" :key="m.id" :value="m.id">{{ m.nama_lengkap }}</option>
        </select>
        <input v-model="form.kategori" placeholder="Kategori" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="form.deskripsi" placeholder="Deskripsi" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model.number="form.poin" type="number" placeholder="Poin" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <div class="sm:col-span-3">
          <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50">Tambah Poin</button>
        </div>
      </form>
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2">
      <input v-model="filters.kategori" placeholder="Cari kategori..." class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
      <select v-model="filters.member_id" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
        <option value="">Semua anggota</option>
        <option v-for="m in allMembers" :key="m.id" :value="m.id">{{ m.nama_lengkap }}</option>
      </select>
    </div>

    <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-sm">
      <h2 class="mb-4 font-semibold text-[#f0ead8]">Daftar Poin</h2>
      <div v-if="points.data.length" class="space-y-3">
        <div v-for="p in points.data" :key="p.id" class="rounded-xl border border-[#6F9435] p-4">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-[#f0ead8]">{{ p.member?.nama_lengkap || 'Tidak diketahui' }}</p>
              <p class="mt-1 text-xs text-[#8fa06a]">{{ p.kategori }} • {{ p.deskripsi }}</p>
              <p class="text-xs text-[#8fa06a]">{{ formatDate(p.created_at) }}</p>
            </div>
            <div class="flex items-center gap-2">
              <span class="rounded-full bg-[#A7B92A]/20 px-2.5 py-1 text-xs font-bold text-[#A7B92A]">+{{ p.poin }}</span>
              <button @click="deletePoint(p)" class="rounded-lg border border-[#ef4419]/50 px-3 py-1.5 text-xs font-semibold text-[#ef4419] hover:bg-[#ef4419]/10">Hapus</button>
            </div>
          </div>
        </div>
        <Pagination :links="points.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </div>
      <p v-else class="empty-state empty-state-text">Belum ada poin.</p>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({ points: Object, totalPoints: Number, filters: Object, allMembers: Array });

const categories = computed(() => [...new Set(points.data.map(p => p.kategori))]);

const form = useForm({ member_id: '', kategori: '', deskripsi: '', poin: 0 });

function addPoint() {
  form.post('/system/points', {
    onSuccess: () => { form.reset(); },
  });
}

function deletePoint(p) {
  if (!confirm('Hapus poin ini?')) return;
  router.delete(`/system/points/${p.id}`);
}

function formatDate(value) { return value ? new Date(value).toLocaleDateString('id-ID') : '-'; }
</script>

