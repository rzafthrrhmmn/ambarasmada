<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Manajemen Anggota</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Daftar Angkatan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola tahun, nomor, dan status angkatan anggota.</p>
      </div>
      <button v-if="canManage" @click="openCreate" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Tambah angkatan</button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-[#6F9435] bg-[#335233] shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-[#6F9435]/30">
          <thead class="bg-[#263D26]">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Nomor</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Tahun</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Nama</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Anggota</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Status</th>
              <th v-if="canManage" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#6F9435]/30">
            <tr v-for="item in angkatan.data" :key="item.id" class="hover:bg-[#263D26]/60">
              <td class="px-4 py-3 text-sm font-semibold text-[#EDD330]">{{ item.nomor }}</td>
              <td class="px-4 py-3 text-sm text-[#d4dc9a]">{{ item.angkatan }}</td>
              <td class="px-4 py-3 text-sm text-[#f0ead8]">{{ item.nama }}</td>
              <td class="px-4 py-3 text-sm text-[#d4dc9a]">{{ item.members_count ?? 0 }}</td>
              <td class="px-4 py-3 text-sm">
                <span v-if="item.is_current" class="rounded-full bg-[#EDD330]/20 px-2.5 py-1 text-xs font-semibold text-[#EDD330]">Berjalan</span>
                <span v-else-if="item.is_active" class="rounded-full bg-[#A7B92A]/20 px-2.5 py-1 text-xs font-semibold text-[#A7B92A]">Aktif</span>
                <span v-else class="rounded-full bg-[#ef4419]/20 px-2.5 py-1 text-xs font-semibold text-[#ef4419]">Arsip</span>
              </td>
              <td v-if="canManage" class="px-4 py-3 text-right text-sm whitespace-nowrap">
                <button @click="openEdit(item)" class="text-[#EDD330] hover:underline">Edit</button>
                <button v-if="item.is_active" @click="archive(item)" class="ml-3 text-[#ef4419] hover:underline">Arsipkan</button>
              </td>
            </tr>
            <tr v-if="!angkatan.data.length">
              <td colspan="6" class="px-4 py-8 text-center text-sm text-[#8fa06a]">Belum ada angkatan.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="angkatan.links" class="border-t border-[#6F9435] p-3" />
    </div>

    <Modal v-if="showModal" :title="editing ? 'Edit angkatan' : 'Tambah angkatan'" @close="closeModal">
      <form @submit.prevent="submitForm" class="grid gap-4">
        <div class="grid gap-4 sm:grid-cols-2">
          <label class="block">
            <span class="text-xs font-medium text-[#d4dc9a]">Tahun angkatan</span>
            <input v-model="form.tahun" required inputmode="numeric" maxlength="4" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-[#d4dc9a]">Nomor angkatan</span>
            <input v-model="form.nomor" required inputmode="numeric" maxlength="3" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
          </label>
        </div>
        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Nama angkatan</span>
          <input v-model="form.nama" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        </label>
        <div class="flex flex-wrap gap-4 text-sm text-[#d4dc9a]">
          <label class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox" class="accent-[#EDD330]" /> Aktif</label>
          <label class="flex items-center gap-2"><input v-model="form.is_current" type="checkbox" class="accent-[#EDD330]" /> Jadikan angkatan berjalan</label>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeModal" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm text-[#d4dc9a]">Batal</button>
          <button :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Simpan</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  angkatan: Object,
});

const page = usePage();
const canManage = computed(() => page.props.auth?.user?.role === 'Pembina');
const showModal = ref(false);
const editing = ref(null);
const form = useForm({
  tahun: '',
  nomor: '',
  nama: '',
  is_active: true,
  is_current: false,
});

function openCreate() {
  editing.value = null;
  form.reset();
  form.is_active = true;
  form.is_current = false;
  showModal.value = true;
}

function openEdit(item) {
  editing.value = item;
  form.tahun = item.angkatan;
  form.nomor = item.nomor;
  form.nama = item.nama;
  form.is_active = item.is_active;
  form.is_current = item.is_current;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editing.value = null;
  form.reset();
}

function submitForm() {
  if (editing.value) {
    form.patch(`/angkatan/${editing.value.id}`, {
      onSuccess: closeModal,
    });
    return;
  }

  form.post('/angkatan', {
    onSuccess: closeModal,
  });
}

function archive(item) {
  if (!confirm(`Arsipkan angkatan ${item.nomor}? Anggota yang sudah terdaftar tetap tersimpan.`)) {
    return;
  }

  router.delete(`/angkatan/${item.id}`);
}
</script>
