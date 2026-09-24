<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Persuratan Digital</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Surat Masuk & Keluar</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola surat keputusan, surat masuk, dan surat keluar ambalan.</p>
      </div>
      <button v-if="canManage" @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Tambah surat</button>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
      <input v-model="filters.search" type="text" placeholder="Cari perihal..." class="w-full max-w-xs rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" />
      <select v-model="filters.jenis_surat" class="rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm">
        <option value="">Semua jenis</option>
        <option value="Masuk">Masuk</option>
        <option value="Keluar">Keluar</option>
        <option value="Keputusan">Keputusan</option>
      </select>
    </div>

    <div v-if="canManage" class="mb-6 rounded-2xl border-2 border-[#6F9435] bg-[#335233] p-5 shadow-lg">
      <div class="mb-4 flex items-center justify-between">
        <div><p class="text-sm font-bold text-[#EDD330]">Template Surat</p><p class="mt-1 text-xs text-[#8fa06a]">Unggah file template sebagai dasar pembuatan surat.</p></div>
        <button @click="showTemplate = true" class="rounded-lg border-2 border-[#6F9435] px-3 py-1.5 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">+ Template</button>
      </div>
      <div class="grid gap-2">
        <div v-for="tpl in templates" :key="tpl.id" class="flex items-center justify-between rounded-lg bg-[#263D26] p-3">
          <div>
            <p class="text-sm font-semibold text-[#f0ead8]">{{ tpl.name }}</p>
            <p v-if="tpl.description" class="text-xs text-[#8fa06a]">{{ tpl.description }}</p>
          </div>
          <div class="flex items-center gap-2">
            <a :href="`/storage/${tpl.file_path}`" target="_blank" class="text-xs text-[#EDD330] hover:underline">Unduh</a>
            <button @click="deleteTemplate(tpl)" class="text-xs text-[#ef4419] hover:underline">Hapus</button>
          </div>
        </div>
        <p v-if="!templates.length" class="text-center py-4 text-sm text-[#8fa06a]">Belum ada template.</p>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <article v-for="item in letters.data" :key="item.id" class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435]">
        <div class="flex items-start justify-between gap-3">
          <div>
            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="item.jenis_surat === 'Masuk' ? 'bg-[#A7B92A]/20 text-[#A7B92A]' : item.jenis_surat === 'Keluar' ? 'bg-[#335233] text-[#EDD330]' : 'bg-[#EDD330]/20 text-[#EDD330]'">{{ item.jenis_surat }}</span>
            <p class="mt-2 text-xs text-[#8fa06a]">{{ item.nomor_surat || '-' }} • {{ formatDate(item.tgl_surat) }}</p>
            <h2 class="mt-1 text-lg font-semibold text-[#f0ead8]">{{ item.perihal }}</h2>
            <p class="mt-1 text-xs text-[#8fa06a]">Tujuan: {{ item.tujuan_pengirim }}</p>
          </div>
          <div class="flex flex-col gap-1">
            <a v-if="item.file_path" :href="`/letters/${item.id}/download`" target="_blank" class="text-xs text-[#EDD330] hover:underline">Unduh Berkas</a>
            <a :href="`/letters/${item.id}/generate?format=pdf`" target="_blank" v-if="item.perihal && item.isi_surat" class="text-xs text-[#A7B92A] hover:underline">Generate PDF</a>
            <a :href="`/letters/${item.id}/generate?format=docx`" target="_blank" v-if="item.perihal && item.isi_surat" class="text-xs text-[#6F9435] hover:underline">Generate DOCX</a>
            <button v-if="canManage" @click="openEdit(item)" class="text-xs text-[#EDD330] hover:underline">Edit</button>
            <button v-if="canManage" @click="confirmDelete(item)" class="text-xs text-[#ef4419] hover:underline">Hapus</button>
          </div>
        </div>
      </article>
    </div>
    <p v-if="!letters.data.length" class="mt-6 text-center text-sm text-[#8fa06a]">Belum ada surat.</p>

    <Pagination :links="letters.links" />

    <Modal v-if="showTemplate && canManage" title="Template surat" @close="showTemplate = false">
      <form @submit.prevent="submitTemplate" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Nama template</span><input v-model="templateForm.name" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" />
          <p v-if="templateForm.errors.name" class="mt-1 text-xs text-[#ef4419]">{{ templateForm.errors.name }}</p>
        </label>
        <label class="block"><span class="text-xs font-medium">Deskripsi</span><input v-model="templateForm.description" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" />
          <p v-if="templateForm.errors.description" class="mt-1 text-xs text-[#ef4419]">{{ templateForm.errors.description }}</p>
        </label>
        <label class="block"><span class="text-xs font-medium">File (.docx)</span><input type="file" @input="templateForm.file = $event.target.files[0]" accept=".docx" class="mt-1 text-sm" />
          <p v-if="templateForm.errors.file" class="mt-1 text-xs text-[#ef4419]">{{ templateForm.errors.file }}</p>
        </label>
        <div class="flex justify-end gap-2">
          <button type="button" @click="showTemplate = false" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm text-[#d4dc9a]">Batal</button>
          <button :disabled="templateForm.processing || !templateForm.file" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Simpan</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showCreate && canManage" :title="editItem ? 'Edit surat' : 'Nama surat'" @close="reset">
      <form @submit.prevent="submitForm" class="grid gap-3">
        <label v-if="selectedTemplate" class="block rounded-lg border-2 border-[#6F9435]/50 bg-[#263D26] p-3">
          <span class="text-xs font-medium text-[#EDD330]">Template Dipilih</span>
          <div class="mt-1 flex items-center justify-between">
            <span class="text-sm text-[#f0ead8]">{{ selectedTemplate.name }}</span>
            <a :href="`/storage/${selectedTemplate.file_path}`" target="_blank" class="text-xs text-[#EDD330] hover:underline">Unduh</a>
          </div>
        </label>
        <label class="block"><span class="text-xs font-medium">Pilih template</span><select v-model="form.template_id" @change="onSelectTemplate" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option :value="null">- Pilih template (opsional) -</option><option v-for="tpl in templates" :key="tpl.id" :value="tpl.id">{{ tpl.name }}</option></select></label>
        <label class="block"><span class="text-xs font-medium">Nomor surat</span><input v-model="form.nomor_surat" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Jenis surat</span><select v-model="form.jenis_surat" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option>Masuk</option><option>Keluar</option><option>Keputusan</option></select></label>
        <label class="block"><span class="text-xs font-medium">Waktu Kegiatan</span><input v-model="form.waktu_kegiatan" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Lokasi Kegiatan</span><input v-model="form.lokasi_kegiatan" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Perihal</span><input v-model="form.perihal" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Isi surat</span><textarea v-model="form.isi_surat" rows="6" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm resize-y"></textarea></label>
        <label class="block"><span class="text-xs font-medium">Tujuan / Pengirim</span><input v-model="form.tujuan_pengirim" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Tanggal surat</span><input v-model="form.tgl_surat" type="date" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">File (PDF/Gambar)</span><input type="file" @input="form.file = $event.target.files[0]" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 text-sm" /></label>
        <div class="flex items-end gap-2 sm:col-span-2">
          <button v-if="!editing" type="button" @click="previewLetter" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-medium text-[#d4dc9a] transition hover:bg-[#263D26]">Preview Surat</button>
          <span class="flex-1"></span>
          <button type="button" @click="reset" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm">Batal</button>
          <button :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#6F9435] disabled:opacity-60">Simpan</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showPreview" title="Preview Surat" @close="showPreview = false">
      <div class="h-[600px]">
        <div v-if="previewLoading" class="flex h-full items-center justify-center">
          <p class="text-sm text-[#8fa06a]">Menyiapkan preview...</p>
        </div>
        <div v-else-if="previewError" class="p-4 text-sm text-[#ef4419]">{{ previewError }}</div>
        <iframe v-else-if="previewUrl" :src="previewUrl" class="h-full w-full rounded-lg border border-[#6F9435]" title="Preview Surat"></iframe>
      </div>
      <div class="mt-4 flex justify-end gap-2">
        <button @click="previewLetter" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm text-[#d4dc9a] hover:bg-[#263D26]">Refresh</button>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({ letters: Object, templates: Array, ambalans: Array, filters: Object });
const page = usePage();
const canManage = computed(() => {
  const role = page.props.auth?.user?.role;
  return role === 'Admin' || role === 'Pembina' || role === 'Pengurus';
});
const showCreate = ref(false);
const showTemplate = ref(false);
const showPreview = ref(false);
const editItem = ref(null);
const selectedTemplate = ref(null);
const previewUrl = ref(null);
const previewLoading = ref(false);
const previewError = ref(null);
const filters = reactive({ search: props.filters?.search || '', jenis_surat: props.filters?.jenis_surat || '' });
const templateForm = useForm({ name: '', description: '', file: null });
const form = useForm({ nomor_surat: '', jenis_surat: 'Masuk', perihal: '', isi_surat: '', tujuan_pengirim: '', tgl_surat: '', waktu_kegiatan: '', lokasi_kegiatan: '', file: null, template_id: null });

watch(() => form.template_id, (id) => {
  selectedTemplate.value = id ? props.templates.find((t) => t.id === id) ?? null : null;
});

function openEdit(item) {
  editItem.value = item;
  form.nomor_surat = item.nomor_surat || '';
  form.jenis_surat = item.jenis_surat;
  form.perihal = item.perihal;
  form.isi_surat = item.isi_surat || '';
  form.tujuan_pengirim = item.tujuan_pengirim;
  form.tgl_surat = item.tgl_surat || '';
  form.waktu_kegiatan = item.waktu_kegiatan || '';
  form.lokasi_kegiatan = item.lokasi_kegiatan || '';
  form.file = null;
  form.template_id = item.template_id || null;
  showCreate.value = true;
}

function getCsrfToken() {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
  return match ? decodeURIComponent(match[1]) : null;
}

function previewLetter() {
  if (!form.perihal) {
    alert('Masukkan perihal surat terlebih dahulu.');
    return;
  }
  showPreview.value = true;
  previewLoading.value = true;
  previewError.value = null;
  previewUrl.value = null;

  const payload = {
    ambalan_id: form.ambalan_id || null,
    nomor_surat: form.nomor_surat || null,
    jenis_surat: form.jenis_surat,
    perihal: form.perihal,
    isi_surat: form.isi_surat || '',
    tujuan_pengirim: form.tujuan_pengirim,
    tgl_surat: form.tgl_surat,
    waktu_kegiatan: form.waktu_kegiatan || null,
    lokasi_kegiatan: form.lokasi_kegiatan || null,
    template_id: form.template_id || null,
  };

  fetch('/letters/preview', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify(payload),
  })
    .then(async (response) => {
      if (!response.ok) {
        const text = await response.text();
        throw new Error(text || 'Gagal memproses preview');
      }
      return response.blob();
    })
    .then((blob) => {
      previewUrl.value = URL.createObjectURL(blob);
      previewLoading.value = false;
    })
    .catch((err) => {
      previewError.value = err.message;
      previewLoading.value = false;
    });
}

function reset() {
  showCreate.value = false;
  editItem.value = null;
  selectedTemplate.value = null;
  showPreview.value = false;
  previewUrl.value = null;
  previewLoading.value = false;
  previewError.value = null;
  form.reset();
  form.jenis_surat = 'Masuk';
  form.template_id = null;
}

function submitForm() {
  if (editItem.value) {
    form.patch(`/letters/${editItem.value.id}`, { onSuccess: reset });
  } else {
    form.post('/letters', { onSuccess: reset });
  }
}

function confirmDelete(item) {
  if (confirm(`Hapus surat "${item.perihal}"?`)) {
    router.delete(`/letters/${item.id}`);
  }
}

function generate(item, format) {
    const url = `/letters/${item.id}/generate?format=${format}`;
    
    // Use fetch to get error messages
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
    })
    .then(async (response) => {
        if (!response.ok) {
            const contentType = response.headers.get('content-type');
            let errorMsg = 'Gagal generate ' + format.toUpperCase();
            if (contentType?.includes('application/json')) {
                const data = await response.json();
                errorMsg = data.message || errorMsg;
            } else {
                const text = await response.text();
                if (text) errorMsg = text;
            }
            alert(errorMsg);
            return;
        }
        
        // Success - download the file
        const blob = await response.blob();
        const downloadUrl = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = `surat-${item.perihal.replace(/[^a-zA-Z0-9\u00C0-\u017f\s]/g, '').replace(/\s+/g, '-')}.${format}`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(downloadUrl);
    })
    .catch((err) => {
        if (err.message.includes('500') || err.message.includes('Internal Server')) {
            alert('Gagal generate surat. Pastikan perihal dan isi surat sudah diisi.');
        } else {
            alert('Terjadi kesalahan: ' + err.message);
        }
    });
}

function onSelectTemplate() {
  if (!form.template_id) {
    selectedTemplate.value = null;
  }
}

function deleteTemplate(template) {
  if (confirm(`Hapus template "${template.name}"?`)) {
    router.delete(`/letters/templates/${template.id}`);
  }
}

function submitTemplate() {
  templateForm.post('/letters/templates', {
    onSuccess: () => {
      showTemplate.value = false;
      templateForm.reset();
    },
  });
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
}
</script>
