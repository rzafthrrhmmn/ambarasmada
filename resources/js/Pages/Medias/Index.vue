<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Media Ambalan</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Lirik & Audio Mars Ambalan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Hafalkan lagu wajib ambalan dengan lirik dan audio.</p>
      </div>
      <button v-if="canManage" @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Tambah media</button>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
      <input v-model="filters.search" type="text" placeholder="Cari judul..." class="w-full max-w-xs rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" />
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <article v-for="item in medias.data" :key="item.id" class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435]">
        <h2 class="text-lg font-semibold text-[#f0ead8]">{{ item.judul }}</h2>
        <div class="mt-4 flex gap-2">
          <button @click="router.visit(`/medias/${item.id}`)" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-3 py-2 text-xs font-semibold text-white">Lihat</button>
          <button v-if="item.file_path" @click="openDownload(item)" class="rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-semibold text-[#d4dc9a]">Unduh</button>
        </div>
      </article>
    </div>
    <p v-if="!medias.data.length" class="mt-6 text-center text-sm text-[#8fa06a]">Belum ada media.</p>

    <Pagination :links="medias.links" />

    <Modal v-if="showCreate" :title="editItem ? 'Edit media' : 'Nama media'" @close="reset">
      <form @submit.prevent="submitForm" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Nama</span><input v-model="form.judul" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Lirik</span><textarea v-model="form.lirik" rows="5" class="mt-1 w-full rounded-lg border border-[#6F9435] px-3 py-2 text-sm"></textarea></label>
        <label class="block"><span class="text-xs font-medium">File Audio</span><input type="file" @input="form.file = $event.target.files[0]" class="mt-1 text-sm" /></label>
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

const props = defineProps({ medias: Object, ambalans: Array, filters: Object });
const page = usePage();
const canManage = computed(() => {
  const role = page.props.auth?.user?.role;
  return role === 'Admin' || role === 'Pembina' || role === 'Pengurus';
});

const showCreate = ref(false);
const editItem = ref(null);
const filters = reactive({ search: props.filters?.search || '' });
const form = useForm({ judul: '', lirik: '', file: null });

function openEdit(item) {
  editItem.value = item;
  form.judul = item.judul;
  form.lirik = item.lirik || '';
  form.file = null;
  showCreate.value = true;
}

function reset() {
  showCreate.value = false;
  editItem.value = null;
  form.reset();
}

function submitForm() {
  const url = editItem.value ? `/medias/${editItem.value.id}` : '/medias';
  const method = editItem.value ? 'patch' : 'post';
  form.post(url, { method, onSuccess: reset });
}

function openDownload(item) {
  window.open(`/medias/${item.id}/download`, '_blank');
}
</script>
