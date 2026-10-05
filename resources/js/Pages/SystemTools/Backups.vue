<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Sistem</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Backup Database</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola backup database dan riwayatnya.</p>
      </div>
      <button v-if="canManageTools" type="button" class="btn-primary" :disabled="creating" @click="createBackupFn">
        <AppIcon v-if="creating" name="spinner" class="h-4 w-4 animate-spin" />
        <AppIcon v-else name="plus" :stroke="2.2" class="h-4 w-4" />
        {{ creating ? 'Membuat...' : 'Backup Sekarang' }}
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
            <!-- Unduh dumping database hanya untuk pengelola sistem. Tautan ini menuju
             berkas statis, bukan rute download, jadi gate di sini satu-satunya
             penyaring sebelum berkas benar-benar terekspos. -->
             <a v-if="canManageTools && log.file_path" :href="`/storage/${log.file_path}`" target="_blank" class="rounded-lg border border-[#EDD330] px-3 py-1.5 text-xs font-semibold text-[#EDD330] hover:bg-[#EDD330]/10">Unduh</a>
          </div>
        </div>
        <Pagination :links="logs.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </div>
      <p v-else class="empty-state">
        <AppIcon name="materials" class="empty-state-icon h-6 w-6" />
        <span class="empty-state-text">Belum ada backup.</span>
      </p>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import Pagination from '@/Components/Pagination.vue';
import { useAccess } from '@/Composables/useAccess.js';

defineProps({ logs: Object });

/**
 * Membuat backup, mengunduhnya, dan menghapus lognya adalah satu kelompok
 * hak akses: semuanya menyangkut data internal sistem, bukan data keanggotaan.
 */
const { can } = useAccess();
const canManageTools = computed(() => can('tools.manage'));

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
