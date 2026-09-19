<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Panduan Operasional</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Panduan Kegiatan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">SOP dan checklist pelaksanaan kegiatan utama ambalan.</p>
      </div>
      <button v-if="canManage" @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Tambah panduan</button>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
      <select v-model="filters.kecamatan" class="rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm">
        <option value="">Semua kecamatan</option>
        <option v-for="k in kecamatanOptions" :key="k" :value="k">{{ k }}</option>
      </select>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <article v-for="item in guides.data" :key="item.id" class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435]">
        <span class="rounded-full bg-[#335233] px-2.5 py-1 text-xs font-medium text-[#EDD330]">{{ item.kecamatan }}</span>
        <h2 class="mt-3 text-lg font-semibold text-[#f0ead8]">{{ item.judul }}</h2>
        <p class="mt-1 text-xs text-[#8fa06a]">Kegiatan: {{ item.kecamatan }}</p>
        <div class="mt-4 flex gap-2">
          <button @click="router.visit(`/guides/${item.id}`)" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-3 py-2 text-xs font-semibold text-white">Baca</button>
          <button v-if="canManage" @click="openEdit(item)" class="rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-semibold text-[#d4dc9a]">Edit</button>
        </div>
      </article>
    </div>
    <p v-if="!guides.data.length" class="mt-6 text-center text-sm text-[#8fa06a]">Belum ada panduan.</p>

    <Pagination :links="guides.links" />

    <Modal v-if="showCreate" :title="editItem ? 'Edit panduan' : 'Nama panduan'" @close="reset">
      <form @submit.prevent="submitForm" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Kegiatan</span><select v-model="form.kecamatan" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option v-for="k in kecamatanOptions" :key="k" :value="k">{{ k }}</option></select></label>
        <label class="block"><span class="text-xs font-medium">Nama</span><input v-model="form.judul" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Susunan Upacara</span><textarea v-model="form.teks_susunan_upacara" rows="6" class="mt-1 w-full rounded-lg border border-[#6F9435] px-3 py-2 text-sm"></textarea></label>
        <label class="block"><span class="text-xs font-medium">Checklist Perlengkapan (satu per baris)</span><textarea v-model="checklistText" rows="4" class="mt-1 w-full rounded-lg border border-[#6F9435] px-3 py-2 text-sm"></textarea></label>
        <button :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Simpan</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({ guides: Object, ambalans: Array, kecamatanOptions: Array, filters: Object });
const page = usePage();
const canManage = computed(() => {
  const role = page.props.auth?.user?.role;
  return role === 'Admin' || role === 'Pembina' || role === 'Pengurus';
});

const showCreate = ref(false);
const editItem = ref(null);
const filters = reactive({ kecamatan: props.filters?.kecamatan || '' });
const checklistText = ref('');
const form = useForm({ judul: '', kecamatan: '', teks_susunan_upacara: '', checklist_perlengkapan: [] });

function openEdit(item) {
  editItem.value = item;
  form.judul = item.judul;
  form.kecamatan = item.kecamatan;
  form.teks_susunan_upacara = item.teks_susunan_upacara || '';
  checklistText.value = (item.checklist_perlengkapan || []).join('\n');
  showCreate.value = true;
}

function reset() {
  showCreate.value = false;
  editItem.value = null;
  form.reset();
  checklistText.value = '';
}

function submitForm() {
  form.checklist_perlengkapan = checklistText.value.split('\n').map(s => s.trim()).filter(Boolean);
  const url = editItem.value ? `/guides/${editItem.value.id}` : '/guides';
  const method = editItem.value ? 'patch' : 'post';
  form.post(url, { method, onSuccess: reset });
}
</script>