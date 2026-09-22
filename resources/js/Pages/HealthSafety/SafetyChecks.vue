<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Keselamatan</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Pemeriksaan Keselamatan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Pemeriksaan keselamatan untuk kegiatan ambalan.</p>
      </div>
    </div>

    <div class="mb-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
      <p class="text-sm font-semibold text-[#EDD330]">Tambah Pemeriksaan</p>
      <form @submit.prevent="addCheck" class="mt-3 grid gap-3 sm:grid-cols-3">
        <input v-model="form.kategori" placeholder="Kategori (contoh: Fasilitas, Kendaraan)" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <textarea v-model="form.deskripsi" placeholder="Deskripsi pemeriksaan" rows="2" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330] resize-none"></textarea>
        <input v-model="form.event_id" type="number" placeholder="ID Event (opsional)" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <div class="sm:col-span-3 flex gap-2">
          <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50">Simpan</button>
          <button type="button" @click="form.reset()" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/30">Reset</button>
        </div>
      </form>
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2">
      <input v-model="filters.kategori" placeholder="Cari kategori..." class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
      <select v-model="filters.passed" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
        <option value="">Semua</option>
        <option :value="1">Lulus</option>
        <option :value="0">Tidak Lulus</option>
      </select>
    </div>

    <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-sm">
      <h2 class="mb-4 font-semibold text-[#f0ead8]">Daftar Pemeriksaan</h2>
      <div v-if="checks.data.length" class="space-y-3">
        <div v-for="c in checks.data" :key="c.id" class="rounded-xl border border-[#6F9435] p-4">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-[#f0ead8]">{{ c.kategori }}</p>
              <p class="mt-1 text-xs text-[#8fa06a]">{{ c.deskripsi }}</p>
              <p class="mt-1 text-xs text-[#8fa06a]">
                <span :class="c.passed ? 'text-[#A7B92A]' : 'text-[#ef4419]'">
                  {{ c.passed ? '✅ Lulus' : '❌ Tidak Lulus' }}
                </span>
                <span v-if="c.event"> • {{ c.event?.nama || 'Event' }}</span>
                • {{ c.createdBy?.name || '-' }}
              </p>
            </div>
          </div>
        </div>
        <Pagination :links="checks.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </div>
      <p v-else class="empty-state empty-state-text">Belum ada pemeriksaan keselamatan.</p>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({ checks: Object, filters: Object });

const form = useForm({ kategori: '', deskripsi: '', event_id: null, passed: false });

function addCheck() {
  form.post('/health/safety/checks', {
    onSuccess: () => { form.reset(); },
  });
}
</script>
