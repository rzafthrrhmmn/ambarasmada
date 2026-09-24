<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Sistem</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Webhook Integration</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola webhook untuk integrasi eksternal.</p>
      </div>
    </div>

    <div class="mb-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
      <p class="text-sm font-semibold text-[#EDD330]">Tambah Webhook</p>
      <form @submit.prevent="createWebhook" class="mt-3 grid gap-3 sm:grid-cols-3">
        <input v-model="form.nama" placeholder="Nama webhook" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="form.url" placeholder="URL endpoint" type="url" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="form.event" placeholder="Event (opsional)" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <textarea v-model="form.headers" placeholder="Headers (JSON, opsional)" rows="2" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330] resize-none sm:col-span-3"></textarea>
        <label class="flex items-center gap-2 text-sm text-[#f0ead8] cursor-pointer">
          <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-[#6F9435] bg-[#263D26] text-[#A7B92A] focus:ring-[#A7B92A]" />
          Aktif
        </label>
        <div class="sm:col-span-3">
          <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50">Simpan</button>
        </div>
      </form>
    </div>

    <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-sm">
      <h2 class="mb-4 font-semibold text-[#f0ead8]">Daftar Webhook</h2>
      <div v-if="webhooks.data.length" class="space-y-3">
        <div v-for="w in webhooks.data" :key="w.id" class="rounded-xl border border-[#6F9435] p-4">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-[#f0ead8]">{{ w.nama }}</p>
              <p class="mt-1 break-all text-xs text-[#8fa06a]">{{ w.url }}</p>
              <p v-if="w.event" class="text-xs text-[#8fa06a]">Event: {{ w.event }}</p>
              <p class="mt-1 text-xs">
                <span :class="w.is_active ? 'text-[#A7B92A]' : 'text-[#ef4419]'" class="font-bold">
                  {{ w.is_active ? '🟢 Aktif' : '🔴 Nonaktif' }}
                </span>
                • {{ w.logs?.length || 0 }} log
              </p>
            </div>
            <div class="flex gap-2">
              <button @click="triggerWebhook(w)" class="rounded-lg border border-[#EDD330] px-3 py-1.5 text-xs font-semibold text-[#EDD330] hover:bg-[#EDD330]/10">Test</button>
              <button @click="deleteWebhook(w)" class="rounded-lg border border-[#ef4419]/50 px-3 py-1.5 text-xs font-semibold text-[#ef4419] hover:bg-[#ef4419]/10">Hapus</button>
            </div>
          </div>
        </div>
        <Pagination :links="webhooks.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </div>
      <p v-else class="empty-state empty-state-text">Belum ada webhook.</p>
    </div>

    <div v-if="triggerResult !== null" class="mt-4 rounded-xl border-2 bg-[#263D26] p-4 text-sm">
      <p class="font-bold text-[#EDD330]">Hasil Test:</p>
      <p :class="triggerResult.success ? 'text-[#A7B92A]' : 'text-[#ef4419]'">
        Status: {{ triggerResult.status }} • {{ triggerResult.success ? 'Berhasil' : 'Gagal' }}
      </p>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({ webhooks: Object });

const form = reactive({ nama: '', url: '', event: '', headers: '', is_active: true });
const triggerResult = ref(null);

function createWebhook() {
  router.post('/system/tools/webhooks', { ...form }, {
    onSuccess: () => { form.nama = ''; form.url = ''; form.event = ''; form.headers = ''; form.is_active = true; },
  });
}

function triggerWebhook(w) {
  triggerResult.value = null;
  router.post(`/system/tools/webhooks/${w.id}/trigger`, {}, {
    onSuccess: (resp) => { triggerResult.value = { success: resp.success, status: resp.status_code }; },
  });
}

function deleteWebhook(w) {
  if (!confirm('Hapus webhook ini?')) return;
  router.delete(`/system/tools/webhooks/${w.id}`);
}
</script>
