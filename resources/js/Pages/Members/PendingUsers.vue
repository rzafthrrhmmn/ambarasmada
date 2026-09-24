<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Manajemen Akun</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Menunggu Persetujuan</h1>
      <p class="mt-1 text-sm text-[#8fa06a]">Kelola pendaftaran akun baru yang menunggu persetujuan Pembina.</p>
    </div>

    <div v-if="pendingUsers.data.length" class="overflow-hidden rounded-2xl border border-[#6F9435] bg-[#335233] shadow-sm border-[#6F9435]">
      <table class="min-w-full divide-y divide-[#6F9435]/30">
        <thead class="bg-[#263D26]">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Nama</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">NTA</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Email</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Terdaftar</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#6F9435]/30">
          <tr v-for="user in pendingUsers.data" :key="user.id" class="hover:bg-[#263D26]/60">
            <td class="px-4 py-3 text-sm font-medium text-[#f0ead8]">{{ user.name }}</td>
            <td class="px-4 py-3 text-sm font-mono text-[#EDD330]">{{ user.username }}</td>
            <td class="px-4 py-3 text-sm text-[#d4dc9a]">{{ user.email }}</td>
            <td class="px-4 py-3 text-sm text-[#8fa06a]">{{ user.created_at }}</td>
            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
              <button @click="approve(user.id)" class="rounded-lg bg-[#A7B92A] px-3 py-1.5 text-xs font-bold text-[#263D26] hover:brightness-110 mr-2">Setujui</button>
              <button @click="reject(user.id)" class="rounded-lg bg-[#ef4419]/20 px-3 py-1.5 text-xs font-bold text-[#ef4419] hover:bg-[#ef4419]/30">Tolak</button>
            </td>
          </tr>
        </tbody>
      </table>
      <Pagination :links="pendingUsers.links" class="border-t border-[#6F9435] p-3 border-[#6F9435]" />
    </div>

    <div v-else class="rounded-2xl border border-[#6F9435] bg-[#335233] p-10 text-center">
      <p class="text-sm text-[#8fa06a]">Tidak ada akun yang menunggu persetujuan.</p>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { defineProps } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  pendingUsers: Object,
});

function approve(userId) {
  if (!confirm('Setujui akun ini?')) return;
  router.post(`/members/pending/${userId}/approve`, {}, {
    onSuccess: () => {},
    onError: () => {},
  });
}

function reject(userId) {
  if (!confirm('Tolak akun ini?')) return;
  router.post(`/members/pending/${userId}/reject`, {}, {
    onSuccess: () => {},
    onError: () => {},
  });
}
</script>
