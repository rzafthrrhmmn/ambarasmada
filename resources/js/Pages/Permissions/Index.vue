<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Role & Izin</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Manajemen Izin Pengguna</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola izin (permissions) untuk setiap pengguna.</p>
      </div>
    </div>

    <div class="mb-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
      <p class="text-sm font-semibold text-[#EDD330]">Tambah Izin</p>
      <form @submit.prevent="addPermission" class="mt-3 grid gap-3 sm:grid-cols-3">
        <select v-model="newForm.user_id" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
          <option value="">Pilih pengguna</option>
          <option v-for="u in allUsers" :key="u.id" :value="u.id">{{ u.name }} ({{ u.role }})</option>
        </select>
        <input v-model="newForm.permission" placeholder="Nama izin (contoh: manage_posts)" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <button type="submit" :disabled="newForm.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50">Tambah Izin</button>
      </form>
    </div>

    <div class="mb-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
      <p class="text-sm font-semibold text-[#EDD330]">Sync Izin Pengguna</p>
      <form @submit.prevent="syncPermissions" class="mt-3 grid gap-3 sm:grid-cols-3">
        <select v-model="syncForm.user_id" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
          <option value="">Pilih pengguna</option>
          <option v-for="u in allUsers" :key="u.id" :value="u.id">{{ u.name }} ({{ u.role }})</option>
        </select>
        <input v-model="syncForm.permissions" placeholder="Izin dipisah koma (contoh: view_posts,edit_posts)" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <button type="submit" :disabled="syncForm.processing" class="rounded-lg bg-gradient-to-r from-[#6F9435] to-[#A7B92A] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50">Sync Izin</button>
      </form>
    </div>

    <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="font-semibold text-[#f0ead8]">Daftar Izin</h2>
        <span class="rounded-full bg-[#6F9435]/20 px-2.5 py-0.5 text-xs font-semibold text-[#EDD330]">{{ permissions.total }} data</span>
      </div>

      <div class="mb-4 grid gap-3 sm:grid-cols-2">
        <input v-model="filters.user_id" placeholder="Cari user ID..." class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="filters.permission" placeholder="Cari izin..." class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
      </div>

      <div v-if="permissions.data.length" class="space-y-2">
        <div v-for="perm in permissions.data" :key="perm.id" class="flex flex-col gap-2 rounded-xl border border-[#6F9435] p-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-[#f0ead8]">{{ perm.user?.name || 'Tidak diketahui' }} <span class="text-xs text-[#8fa06a]">({{ perm.user?.role }})</span></p>
            <p class="mt-1 text-xs font-medium text-[#EDD330]">{{ perm.permission }}</p>
          </div>
          <button @click="deletePermission(perm)" class="rounded-lg border border-[#ef4419]/50 px-3 py-1.5 text-xs font-semibold text-[#ef4419] hover:bg-[#ef4419]/10">Hapus</button>
        </div>
        <Pagination :links="permissions.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </div>
      <p v-else class="empty-state empty-state-text">Belum ada izin yang diatur.</p>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const page = usePage();
const allUsers = computed(() => page.props.users || []);

defineProps({
  permissions: Object,
  filters: Object,
  users: Array,
});

const newForm = reactive({ user_id: '', permission: '', processing: false });
const syncForm = reactive({ user_id: '', permissions: '', processing: false });

function addPermission() {
  newForm.processing = true;
  router.post('/permissions', newForm, {
    onSuccess: () => { newForm.user_id = ''; newForm.permission = ''; },
    onError: () => { newForm.processing = false; },
  });
}

function syncPermissions() {
  syncForm.processing = true;
  router.post('/permissions/sync', syncForm, {
    onSuccess: () => { syncForm.user_id = ''; syncForm.permissions = ''; syncForm.processing = false; },
  });
}

function deletePermission(perm) {
  if (!confirm('Hapus izin ini?')) return;
  router.delete(`/permissions/${perm.id}`);
}
</script>
