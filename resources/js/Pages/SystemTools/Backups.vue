<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Sistem</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Backup Database</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola backup database dan riwayatnya.</p>
      </div>
      <button @click="createBackupFn" :disabled="creating" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50">
        <span v-if="creating">Membuat...</span>
        <span v-else>+ Backup Sekarang</span>
      </button>
    </div>

    <div v-if="message" :class="messageType === 'success' ? 'border-[#A7B92A]' : 'border-[#ef4419]'" class="mb-4 rounded-xl border-2 bg-[#263D26] px-4 py-3 text-sm font-medium text-[#f0ead8]">
      {{ message }}
    </div>

    <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-sm">
      <h2 class="mb-4 font-semibold text-[#f0ead8]">Daftar Backup</h2>
      <div v-if="logs.data.length" class="space-y-3">
        <div v-for="log in logs.data" :key="log.id" class="rounded-xl border border-[#6F9435] p-4">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-[#f0ead8]">{{ log.keterangan || 'Backup #' + log.id }}</p>
              <p class="mt-1 text-xs text-[#8fa06a]">
                {{ log.file_path }} • {{ formatSize(log.size) }} • {{ log.createdBy?.name || '-' }}
              </p>
              <p class="text-xs text-[#8fa06a]">{{ formatDate(log.created_at) }}</p>
            </div>
            <a v-if="log.file_path" :href="`/storage/${log.file_path}`" target="_blank" class="rounded-lg border border-[#EDD330] px-3 py-1.5 text-xs font-semibold text-[#EDD330] hover:bg-[#EDD330]/10">Unduh</a>
          </div>
        </div>
        <Pagination :links="logs.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </div>
      <p v-else class="empty-state empty-state-text">Belum ada backup.</p>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({ logs: Object });

const creating = ref(false);
const message = ref('');
const messageType = ref('success');

function formatDate(value) { return value ? new Date(value).toLocaleDateString('id-ID') : '-'; }
function formatSize(value) { return value ? (value / 1024).toFixed(1) + ' KB' : '0 KB'; }

function createBackupFn() {
  creating.value = true;
  message.value = '';
  router.post('/system/tools/backup', { keterangan: 'Backup manual' }, {
    onSuccess: () => { creating.value = false; message.value = 'Backup created successfully.'; messageType.value = 'success'; },
    onError: () => { creating.value = false; message.value = 'Backup failed.'; messageType.value = 'error'; },
  });
}
</script>
