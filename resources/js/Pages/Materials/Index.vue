<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Perpustakaan Digital</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Materi Kepramukaan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kumpulan panduan, sandi, navigasi, dan materi kepramukaan lainnya.</p>
      </div>
      <button v-if="canManage" @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Tambah materi</button>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
      <input v-model="filters.search" type="text" placeholder="Cari materi..." class="w-full max-w-xs rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" />
      <select v-model="filters.deskripsi" class="rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm">
        <option value="">Semua kategori</option>
        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
      </select>
      <select v-model="filters.restricted" class="rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm">
        <option value="">Semua akses</option>
        <option value="false">Publik</option>
        <option value="true">Terbatas</option>
      </select>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <article v-for="item in materials.data" :key="item.id" class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435]">
        <div class="flex items-start justify-between gap-3">
          <span v-if="item.is_restricted" class="rounded-full bg-[#EDD330]/20 px-2.5 py-1 text-xs font-medium text-[#EDD330]">Terbatas</span>
          <span v-else class="rounded-full bg-[#A7B92A]/20 px-2.5 py-1 text-xs font-medium text-[#A7B92A]">Publik</span>
        </div>
        <h2 class="mt-3 text-lg font-semibold text-[#f0ead8]">{{ item.nama }}</h2>
        <p v-if="item.deskripsi" class="mt-1 text-xs text-[#8fa06a]">Kategori: {{ item.deskripsi }}</p>
        <div class="mt-4 flex gap-2">
          <button @click="router.visit(`/materials/${item.id}`)" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-3 py-2 text-xs font-semibold text-white">Baca</button>
          <button v-if="item.file_path" @click="openDownload(item)" class="rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-semibold text-[#d4dc9a]">Unduh</button>
        </div>
      </article>
    </div>
    <p v-if="!materials.data.length" class="mt-6 text-center text-sm text-[#8fa06a]">Belum ada materi.</p>

    <Pagination :links="materials.links" />

    <Modal v-if="showCreate" :title="editItem ? 'Edit materi' : 'Nama materi'" @close="reset">
      <form @submit.prevent="submitForm" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Nama</span><input v-model="form.nama" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Kategori</span><input v-model="form.deskripsi" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Konten</span><textarea v-model="form.konten" rows="5" class="mt-1 w-full rounded-lg border border-[#6F9435] px-3 py-2 text-sm"></textarea></label>
        <label class="block"><span class="text-xs font-medium">File (PDF/Gambar/Video)</span><input type="file" @input="form.file = $event.target.files[0]" class="mt-1 text-sm" /></label>
        <label class="flex items-center gap-2"><input type="checkbox" v-model="form.is_restricted" class="rounded" /><span class="text-xs font-medium">Akses terbatas (hanya Pengurus/Pembina)</span></label>
        <button :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Simpan</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({ materials: Object, categories: Array, filters: Object });
const page = usePage();
const canManage = computed(() => {
  const role = page.props.auth?.user?.role;
  return role === 'Admin' || role === 'Pembina' || role === 'Pengurus';
});

const showCreate = ref(false);
const editItem = ref(null);
const filters = reactive({ search: props.filters?.search || '', deskripsi: props.filters?.deskripsi || '', restricted: props.filters?.restricted || '' });
const form = useForm({ nama: '', deskripsi: '', konten: '', file: null, is_restricted: false });

function openEdit(item) {
  editItem.value = item;
  form.nama = item.nama;
  form.deskripsi = item.deskripsi || '';
  form.konten = item.konten || '';
  form.file = null;
  form.is_restricted = item.is_restricted;
  showCreate.value = true;
}

function reset() {
  showCreate.value = false;
  editItem.value = null;
  form.reset();
  form.is_restricted = false;
}

function submitForm() {
  const url = editItem.value ? `/materials/${editItem.value.id}` : '/materials';
  const method = editItem.value ? 'patch' : 'post';
  form.post(url, { method, onSuccess: reset });
}

function confirmDelete(item) {
    if (confirm(`Hapus materi "${item.nama}"?`)) {
    router.delete(`/materials/${item.id}`);
  }
}

function openDownload(item) {
  window.open(`/materials/${item.id}/download`, '_blank');
}
</script>
