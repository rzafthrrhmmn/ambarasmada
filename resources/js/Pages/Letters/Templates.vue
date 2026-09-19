<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Persuratan Digital</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Template Surat</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola acuan file untuk pembuatan surat ambalan.</p>
      </div>
      <div class="flex gap-2">
        <Link href="/letters" class="inline-flex items-center rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/20">Kembali ke surat</Link>
        <button @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Tambah template</button>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <article v-for="template in templates" :key="template.id" class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h2 class="text-lg font-semibold text-[#f0ead8]">{{ template.name }}</h2>
            <p v-if="template.description" class="mt-1 text-sm text-[#8fa06a]">{{ template.description }}</p>
            <p v-if="template.created_at" class="mt-3 text-xs text-[#d4dc9a]">Diunggah {{ formatDate(template.created_at) }}</p>
          </div>
          <div class="flex shrink-0 flex-col gap-1">
            <a :href="`/storage/${template.file_path}`" target="_blank" rel="noopener" class="text-xs text-[#EDD330] hover:underline">Unduh</a>
            <button @click="deleteTemplate(template)" class="text-xs text-[#ef4419] hover:underline">Hapus</button>
          </div>
        </div>
      </article>
      <p v-if="!templates.length" class="col-span-full py-10 text-center text-sm text-[#8fa06a]">Belum ada template surat.</p>
    </div>

    <Modal v-if="showCreate" title="Tambah template surat" @close="closeCreate">
      <form @submit.prevent="submitForm" class="grid gap-4">
        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Nama template</span>
          <input v-model="form.name" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
          <p v-if="form.errors.name" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.name }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Deskripsi</span>
          <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330] resize-y"></textarea>
          <p v-if="form.errors.description" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.description }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">File template (.docx)</span>
          <input type="file" @change="selectFile" accept=".docx" class="mt-1 text-sm text-[#d4dc9a]" />
          <p v-if="form.errors.file" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.file }}</p>
        </label>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeCreate" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm text-[#d4dc9a]">Batal</button>
          <button :disabled="form.processing || !form.file" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Simpan</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';

defineProps({
  templates: Array,
});

const showCreate = ref(false);
const form = useForm({
  name: '',
  description: '',
  file: null,
});

function selectFile(event) {
  form.file = event.target.files[0] || null;
}

function closeCreate() {
  showCreate.value = false;
  form.reset();
}

function submitForm() {
  form.post('/letters/templates', {
    onSuccess: closeCreate,
  });
}

function deleteTemplate(template) {
  if (!confirm(`Hapus template "${template.name}"?`)) {
    return;
  }

  router.delete(`/letters/templates/${template.id}`);
}

function formatDate(value) {
  return new Date(value).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}
</script>
