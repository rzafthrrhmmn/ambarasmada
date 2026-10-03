<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Persuratan Digital</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Template Surat</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">
          Unggah acuan .docx sekali saja, lalu pakai berulang saat membuat surat.
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <Link
          href="/letters"
          class="inline-flex items-center rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/20"
        >
          Kembali ke surat
        </Link>
        <button
          type="button"
          data-test="btn-unggah-template"
          @click="showCreate = true"
          class="inline-flex items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white"
        >
          + Unggah template
        </button>
      </div>
    </div>

    <div class="mb-6 rounded-2xl border border-[#6F9435] bg-[#335233] p-5">
      <p class="text-sm font-bold text-[#EDD330]">Cara memakai template</p>
      <ol class="mt-2 list-decimal space-y-1 pl-5 text-xs text-[#8fa06a]">
        <li>Buka dokumen di Microsoft Word, lalu sisipkan penanda di bagian yang akan berubah, contoh <code class="text-[#d4dc9a]">{{ '{' }}{{ '{' }}perihal{{ '}' }}{{ '}' }}</code>.</li>
        <li>Unggah berkas .docx di bawah. Penanda dideteksi otomatis dan disimpan bersama template.</li>
        <li>Saat membuat surat, form otomatis memuat isian untuk setiap penanda yang belum punya kolom di form utama.</li>
        <li>Pratinjau, lalu ekspor sebagai .docx (mengisi template aslinya) atau .pdf.</li>
      </ol>
      <div v-if="catalog.length" class="mt-4">
        <p class="text-xs font-semibold text-[#d4dc9a]">Penanda yang punya label siap pakai di form</p>
        <div class="mt-2 flex flex-wrap gap-1">
          <code
            v-for="(label, key) in catalog"
            :key="key"
            class="rounded bg-[#263D26] px-1.5 py-0.5 text-[10px] text-[#8fa06a]"
          >{{ '{' }}{{ '{' }}{{ key }}{{ '}' }}{{ '}' }} &mdash; {{ label }}</code>
        </div>
      </div>
    </div>

    <div v-if="!templates.length" class="rounded-2xl border border-[#6F9435] bg-[#335233] p-10 text-center">
      <p class="text-sm text-[#8fa06a]">Belum ada template surat.</p>
    </div>

    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="template in templates"
        :key="template.id"
        class="flex flex-col rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm"
      >
        <h2 class="text-lg font-semibold text-[#f0ead8]">{{ template.name }}</h2>
        <p v-if="template.description" class="mt-1 text-sm text-[#8fa06a]">{{ template.description }}</p>
        <p class="mt-2 text-xs text-[#8fa06a]">
          {{ template.placeholders.length }} penanda
          <span v-if="template.created_at">&bull; diunggah {{ formatDate(template.created_at) }}</span>
        </p>

        <div v-if="template.placeholders.length" class="mt-3 flex flex-wrap gap-1">
          <code
            v-for="name in template.placeholders"
            :key="name"
            class="rounded bg-[#263D26] px-1.5 py-0.5 text-[10px] text-[#d4dc9a]"
          >{{ '{' }}{{ '{' }}{{ name }}{{ '}' }}{{ '}' }}</code>
        </div>
        <p v-else class="mt-3 rounded-lg border border-[#ef4419]/60 p-2 text-xs text-[#ef4419]">
          Template ini tidak memiliki penanda, jadi akan memakai tata letak bawaan.
        </p>

        <div class="mt-auto flex items-center gap-3 border-t border-[#6F9435]/40 pt-3 text-xs">
          <a :href="template.download_url" class="font-semibold text-[#EDD330] hover:underline">Unduh</a>
          <span class="flex-1"></span>
          <button type="button" @click="deleteTemplate(template)" class="text-[#ef4419] hover:underline">Hapus</button>
        </div>
      </article>
    </div>

    <Modal v-if="showCreate" title="Unggah template surat" @close="closeCreate">
      <form @submit.prevent="submitForm" class="grid gap-4">
        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Nama template</span>
          <input
            v-model="form.name"
            required
            class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
          />
          <p v-if="form.errors.name" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.name }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Deskripsi</span>
          <textarea
            v-model="form.description"
            rows="2"
            class="mt-1 w-full resize-y rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
          ></textarea>
          <p v-if="form.errors.description" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.description }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Berkas (.docx, maks 10 MB)</span>
          <input
            type="file"
            accept=".docx"
            class="mt-1 w-full text-sm text-[#d4dc9a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#6F9435] file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white"
            @change="selectFile"
          />
          <p v-if="form.errors.file" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.file }}</p>
        </label>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeCreate" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm text-[#d4dc9a]">Batal</button>
          <button
            type="submit"
            :disabled="form.processing || !form.file"
            class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
          >
            {{ form.processing ? 'Mengunggah...' : 'Unggah' }}
          </button>
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
  templates: { type: Array, default: () => [] },
  catalog: { type: Object, default: () => ({}) },
});

const showCreate = ref(false);
const form = useForm({ name: '', description: '', file: null });

function selectFile(event) {
  form.file = event.target.files?.[0] ?? null;
}

function closeCreate() {
  showCreate.value = false;
  form.reset();
}

function submitForm() {
  form.post('/letters/templates', { onSuccess: closeCreate });
}

function deleteTemplate(template) {
  if (confirm(`Hapus template "${template.name}"? Surat yang sudah memakai template ini tidak ikut terhapus.`)) {
    router.delete(`/letters/templates/${template.id}`);
  }
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
}
</script>
