<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Persuratan Digital</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Surat Masuk &amp; Keluar</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Unggah template, isi form surat, lalu ekspor ke Word atau PDF.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <Link
          v-if="can('letters.templates.manage')"
          href="/letters/templates"
          class="inline-flex items-center rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/20"
        >
          Kelola template
        </Link>
        <button
          v-if="can('letters.templates.manage')"
          type="button"
          @click="showTemplate = true"
          class="inline-flex items-center rounded-lg border border-[#A7B92A] px-4 py-2 text-sm font-semibold text-[#A7B92A] transition hover:bg-[#6F9435]/20"
        >
          + Unggah template
        </button>
        <button
          v-if="can('letters.manage')"
          data-test="btn-buat"
          @click="openCreate"
          class="inline-flex items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white"
        >
          + Tambah surat
        </button>
      </div>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
      <input
        v-model="filters.search"
        type="search"
        placeholder="Cari perihal..."
        class="w-full max-w-xs rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"
      />
      <select v-model="filters.jenis_surat" class="rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm">
        <option value="">Semua jenis</option>
        <option v-for="jenis in jenisOptions" :key="jenis" :value="jenis">{{ jenis }}</option>
      </select>
    </div>

    <div v-if="!letters.data.length" class="rounded-2xl border border-[#6F9435] bg-[#335233] p-8 text-center">
      <p class="text-sm text-[#8fa06a]">Belum ada surat.</p>
    </div>

    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="item in letters.data"
        :key="item.id"
        class="flex flex-col rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <span
              class="rounded-full px-2.5 py-1 text-xs font-medium"
              :class="badgeClass(item.jenis_surat)"
            >{{ item.jenis_surat }}</span>
            <p class="mt-2 text-xs text-[#8fa06a]">{{ item.nomor_surat || '-' }} &bull; {{ formatDate(item.tgl_surat) }}</p>
            <h2 class="mt-1 text-lg font-semibold text-[#f0ead8]">{{ item.perihal }}</h2>
            <p class="mt-1 text-xs text-[#8fa06a]">Tujuan: {{ item.tujuan_pengirim || '-' }}</p>
            <p v-if="item.template" class="mt-2 text-xs text-[#A7B92A]">Template: {{ item.template.name }}</p>
          </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-[#6F9435]/40 pt-3 text-xs">
          <Link :href="`/letters/${item.id}`" class="font-semibold text-[#f0ead8] hover:text-[#EDD330]">Detail</Link>
          <a
            :href="`/letters/${item.id}/generate?format=docx`"
            class="text-[#A7B92A] hover:underline"
          >DOCX</a>
          <a
            :href="`/letters/${item.id}/generate?format=pdf`"
            class="text-[#A7B92A] hover:underline"
          >PDF</a>
          <a v-if="item.file_path" :href="`/letters/${item.id}/download`" class="text-[#d4dc9a] hover:underline">Lampiran</a>
          <span class="flex-1"></span>
          <button v-if="can('letters.manage')" @click="openEdit(item)" class="text-[#EDD330] hover:underline">Edit</button>
          <button v-if="can('letters.manage')" @click="confirmDelete(item)" class="text-[#ef4419] hover:underline">Hapus</button>
        </div>
      </article>
    </div>

    <Pagination :links="letters.links" />

    <Modal v-if="showLetter && can('letters.manage')" :title="editItem ? 'Edit surat' : 'Surat baru'" @close="reset">
      <form @submit.prevent="submitForm" class="grid gap-4">
        <section class="rounded-xl border border-[#6F9435]/60 bg-[#263D26] p-4">
          <p class="mb-3 text-xs font-bold uppercase tracking-wide text-[#EDD330]">1. Template</p>
          <label class="block">
            <span class="text-xs font-medium text-[#d4dc9a]">Pilih template (.docx)</span>
            <select v-model="form.template_id" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm">
              <option :value="null">Tanpa template (tata letak bawaan)</option>
              <option v-for="tpl in templates" :key="tpl.id" :value="tpl.id">{{ tpl.name }}</option>
            </select>
            <p v-if="form.errors.template_id" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.template_id }}</p>
          </label>

          <div v-if="selectedTemplate" class="mt-3 rounded-lg border border-[#6F9435]/50 p-3">
            <div class="flex items-center justify-between gap-3">
              <p class="text-sm text-[#f0ead8]">{{ selectedTemplate.name }}</p>
              <a :href="selectedTemplate.download_url" class="text-xs text-[#EDD330] hover:underline">Unduh</a>
            </div>
            <p v-if="selectedTemplate.description" class="mt-1 text-xs text-[#8fa06a]">{{ selectedTemplate.description }}</p>
            <p class="mt-2 text-xs text-[#8fa06a]">
              {{ selectedTemplate.placeholders.length }} penanda &mdash; isian form dibuat otomatis dari penanda template.
            </p>
            <div class="mt-2 flex flex-wrap gap-1">
              <!--
                "${name}" ditulis sebagai teks biasa oleh Vue, jadi bentuk
                penanda harus dirakit sendiri. Kalau tidak, semua penanda
                tampil sama saja sebagai "${name}".
              -->
              <code
                v-for="name in selectedTemplate.placeholders"
                :key="name"
                class="rounded bg-[#335233] px-1.5 py-0.5 text-[10px] text-[#d4dc9a]"
              >{{ '${' + name + '}' }}</code>
            </div>
          </div>
          <p v-else class="mt-3 text-xs text-[#8fa06a]">
            Tanpa template, surat memakai tata letak bawaan dan tetap bisa diekspor ke Word atau PDF.
          </p>
        </section>

        <section class="rounded-xl border border-[#6F9435]/60 bg-[#263D26] p-4">
          <p class="mb-3 text-xs font-bold uppercase tracking-wide text-[#EDD330]">2. Isi surat</p>
          <div class="grid gap-3 sm:grid-cols-2">
            <Field
              v-model="form.ambalan_id"
              label="Ambalan"
              type="select"
              :options="ambalanOptions"
              :error="form.errors.ambalan_id"
            />
            <Field v-model="form.nomor_surat" label="Nomor surat" :error="form.errors.nomor_surat" />
            <Field v-model="form.jenis_surat" label="Jenis surat" type="select" :options="jenisOptions" :error="form.errors.jenis_surat" />
            <Field v-model="form.tgl_surat" label="Tanggal surat" type="date" :error="form.errors.tgl_surat" />
            <Field v-model="form.waktu_kegiatan" label="Waktu kegiatan" :error="form.errors.waktu_kegiatan" />
            <Field v-model="form.lokasi_kegiatan" label="Lokasi kegiatan" :error="form.errors.lokasi_kegiatan" />
            <Field v-model="form.perihal" label="Perihal" required :error="form.errors.perihal" />
            <Field v-model="form.tujuan_pengirim" label="Tujuan / Pengirim" required :error="form.errors.tujuan_pengirim" />
            <div class="sm:col-span-2">
              <Field
                v-model="form.isi_surat"
                label="Isi surat"
                type="textarea"
                rows="6"
                :error="form.errors.isi_surat"
              />
              <p class="mt-1 text-[11px] text-[#8fa06a]">
                Baris baru pada isi surat menjadi paragraf baru di dokumen.
              </p>
            </div>
            <div class="sm:col-span-2">
              <span class="text-xs font-medium text-[#d4dc9a]">Lampiran (opsional)</span>
              <input
                ref="fileInput"
                type="file"
                accept=".pdf,.jpg,.jpeg,.png"
                class="mt-1 w-full text-sm text-[#d4dc9a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#6F9435] file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white"
                @change="pickAttachment"
              />
              <p v-if="form.errors.file" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.file }}</p>
            </div>
          </div>
        </section>

        <section v-if="extraFields.length" class="rounded-xl border border-[#6F9435]/60 bg-[#263D26] p-4">
          <p class="mb-1 text-xs font-bold uppercase tracking-wide text-[#EDD330]">3. Data penanda template</p>
          <p class="mb-3 text-[11px] text-[#8fa06a]">
            Isian di bawah dibaca dari penanda <code class="text-[#EDD330]">${nama_field}</code> pada template. Nilai disimpan pada surat, bukan pada data anggota.
          </p>
          <div class="grid gap-3 sm:grid-cols-2">
            <Field
              v-for="field in extraFields"
              :key="field.name"
              v-model="extraValues[field.name]"
              :label="field.label"
              :type="field.type"
              :placeholder="field.placeholder"
            />
          </div>
        </section>

        <div class="flex flex-wrap items-center gap-2">
          <!--
            Pesan penolakan server juga ditampilkan di dekat tombol simpan.
            Kalau hanya di bagian atas form, penyebabnya tidak terlihat saat
            pengguna sedang berada di bagian bawah.
          -->
          <p
            v-if="submitError"
            class="w-full rounded-lg border border-[#ef4419]/70 bg-[#ef4419]/10 p-3 text-xs text-[#ef4419]"
          >
            {{ submitError }}
          </p>
          <button
            type="button"
            data-test="btn-preview"
            :disabled="previewLoading"
            @click="previewLetter"
            class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-medium text-[#d4dc9a] transition hover:bg-[#6F9435]/20 disabled:opacity-60"
          >
            {{ previewLoading ? 'Menyiapkan...' : 'Pratinjau' }}
          </button>
          <span class="flex-1"></span>
          <button type="button" @click="reset" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm text-[#d4dc9a]">Batal</button>
          <button
            type="submit"
            data-test="btn-simpan"
            :disabled="form.processing"
            class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:to-[#6F9435] disabled:opacity-60"
          >
            {{ form.processing ? 'Menyimpan...' : 'Simpan surat' }}
          </button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showPreview" title="Pratinjau surat" @close="closePreview">
      <div class="h-[65vh]">
        <p v-if="previewLoading" class="p-6 text-center text-sm text-[#8fa06a]">Menyiapkan pratinjau...</p>
        <p v-else-if="previewError" class="rounded-lg border border-[#ef4419] p-4 text-sm text-[#ef4419]">{{ previewError }}</p>
        <iframe v-else-if="previewUrl" :src="previewUrl" class="h-full w-full rounded-lg border border-[#6F9435] bg-white" title="Pratinjau surat"></iframe>
      </div>
      <div class="mt-4 flex justify-end gap-2">
        <button @click="previewLetter" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm text-[#d4dc9a]">Muat ulang</button>
        <button @click="closePreview" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Tutup</button>
      </div>
    </Modal>

    <Modal v-if="showTemplate && can('letters.templates.manage')" title="Unggah template surat" @close="closeTemplate">
      <form @submit.prevent="submitTemplate" class="grid gap-3">
        <p class="rounded-lg border border-[#6F9435]/60 bg-[#263D26] p-3 text-xs text-[#8fa06a]">
          Berkas .docx boleh memuat penanda <code class="text-[#EDD330]">${perihal}</code>,
          <code class="text-[#EDD330]">${isi_surat}</code>,
          <code class="text-[#EDD330]">${nama_pradana_putra}</code>, dan setiap placeholder
          yang Anda buat sendiri. Penanda boleh berada di body, kop, maupun footer.
        </p>
        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Nama template</span>
          <input v-model="templateForm.name" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm" />
          <p v-if="templateForm.errors.name" class="mt-1 text-xs text-[#ef4419]">{{ templateForm.errors.name }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Deskripsi</span>
          <input v-model="templateForm.description" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm" />
          <p v-if="templateForm.errors.description" class="mt-1 text-xs text-[#ef4419]">{{ templateForm.errors.description }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Berkas (.docx, maks 10 MB)</span>
          <input
            type="file"
            accept=".docx"
            class="mt-1 w-full text-sm text-[#d4dc9a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#6F9435] file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white"
            @change="templateForm.file = $event.target.files?.[0] ?? null"
          />
          <p v-if="templateForm.errors.file" class="mt-1 text-xs text-[#ef4419]">{{ templateForm.errors.file }}</p>
        </label>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeTemplate" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm text-[#d4dc9a]">Batal</button>
          <button
            type="submit"
            :disabled="templateForm.processing || !templateForm.file"
            class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
          >
            {{ templateForm.processing ? 'Mengunggah...' : 'Unggah' }}
          </button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Field from '@/Components/FormField.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { useAccess } from '@/Composables/useAccess.js';

const props = defineProps({
  letters: { type: Object, required: true },
  templates: { type: Array, default: () => [] },
  ambalans: { type: Array, default: () => [] },
  jenisOptions: { type: Array, default: () => ['Masuk', 'Keluar', 'Keputusan'] },
  filters: { type: Object, default: () => ({ search: '', jenis_surat: '' }) },
});

const { can } = useAccess();

const showLetter = ref(false);
const showPreview = ref(false);
const showTemplate = ref(false);
const editItem = ref(null);
const previewUrl = ref('');
const previewLoading = ref(false);
const previewError = ref('');
const submitError = ref('');
const fileInput = ref(null);

const filters = reactive({
  search: props.filters?.search ?? '',
  jenis_surat: props.filters?.jenis_surat ?? '',
});

/** Filter yang sudah dikirim ke server, supaya tidak ada permintaan ulang. */
let appliedFilters = { ...filters };

/** Tipe diketik pada kolom pencarian akan menumpuk permintaan. */
let filterTimer = null;

const ambalanOptions = computed(() => [{ value: null, label: 'Tanpa ambalan' }, ...props.ambalans.map((a) => ({ value: a.id, label: a.nama }))]);

const form = useForm({
  ambalan_id: null,
  nomor_surat: '',
  jenis_surat: 'Masuk',
  perishal: '',
  isi_surat: '',
  tujuan_pengirim: '',
  tgl_surat: today(),
  waktu_kegiatan: '',
  lokasi_kegiatan: '',
  template_id: null,
  file: null,
  placeholder_values: {},
});

const templateForm = useForm({ name: '', description: '', file: null });
const extraValues = reactive({});

/** Template yang sedang dipilih pada form surat. */
const selectedTemplate = computed(() => props.templates.find((tpl) => tpl.id === form.template_id) ?? null);

/**
 * Penanda template yang belum punya kolom inti di form, ditulis sebagai isian
 * tambahan. Daftar penanda inti disimpan di server (LetterValues) dan dikirim
 * sebagai penanda "core", jadi halaman ini tidak perlu daftar sendiri yang
 * bisa berbeda dengan bawaan nama kolom.
 */
const extraFields = computed(() => {
  if (!selectedTemplate.value) {
    return [];
  }

  return selectedTemplate.value.placeholders
    .filter((name) => !selectedTemplate.value.fields?.[name]?.core)
    .map((name) => {
      const field = selectedTemplate.value.fields?.[name];

      return {
        name,
        label: field?.label ?? humanize(name),
        type: field?.type ?? 'text',
        placeholder: field?.type === 'textarea' ? 'Isi beberapa baris' : '',
      };
    });
});

watch(
  extraFields,
  (fields) => {
    const allowed = new Set(fields.map((field) => field.name));
    const values = {};

    for (const field of fields) {
      values[field.name] = extraValues[field.name] ?? form.placeholder_values?.[field.name] ?? '';
    }

    for (const key of Object.keys(extraValues)) {
      if (!allowed.has(key)) {
        delete extraValues[key];
      }
    }

    Object.assign(extraValues, values);
  },
  { immediate: true, deep: true }
);

watch(filters, (value) => {
  window.clearTimeout(filterTimer);
  filterTimer = window.setTimeout(() => {
    if (value.search === appliedFilters.search && value.jenis_surat === appliedFilters.jenis_surat) {
      return;
    }

    appliedFilters = { search: value.search, jenis_surat: value.jenis_surat };

    router.get(
      '/letters',
      { search: value.search || undefined, jenis_surat: value.jenis_surat || undefined },
      { preserveState: true, replace: true }
    );
  }, 350);
});

onBeforeUnmount(() => window.clearTimeout(filterTimer));

function openCreate() {
  editItem.value = null;
  rejectedValues = null;
  form.reset();
  form.clearErrors();
  form.ambalan_id = null;
  form.jenis_surat = 'Masuk';
  form.tgl_surat = today();
  form.template_id = null;
  form.placeholder_values = {};
  submitError.value = '';
  clearExtras();
  showLetter.value = true;
}

function openEdit(item) {
  editItem.value = item;
  rejectedValues = null;
  form.reset();
  form.clearErrors();
  form.ambalan_id = item.ambalan_id ?? null;
  form.nomor_surat = item.nomor_surat ?? '';
  form.jenis_surat = item.jenis_surat ?? 'Masuk';
  form.perihal = item.perihal ?? '';
  form.isi_surat = item.isi_surat ?? '';
  form.tujuan_pengirim = item.tujuan_pengirim ?? '';
  form.tgl_surat = (item.tgl_surat ?? '').slice(0, 10);
  form.waktu_kegiatan = item.waktu_kegiatan ?? '';
  form.lokasi_kegiatan = item.lokasi_kegiatan ?? '';
  form.template_id = item.template_id ?? null;
  form.file = null;
  form.placeholder_values = { ...(item.placeholder_values ?? {}) };
  submitError.value = '';
  clearExtras();
  showLetter.value = true;
}

function reset() {
  showLetter.value = false;
  editItem.value = null;
  rejectedValues = null;
  clearExtras();
  closePreview();
  form.reset();
  form.clearErrors();
  form.ambalan_id = null;
  form.jenis_surat = 'Masuk';
  form.tgl_surat = today();
  form.template_id = null;
  form.placeholder_values = {};
  // Input berkas menyimpan nama berkas di DOM, jadi harus dikosongkan juga.
  // Kalau tidak, nama berkas lama tetap terlihat padahal form.file sudah null
  // dan berkas itu tidak ikut terkirim.
  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

/** Isian surat yang bisa punya pesan galat dari server atau dari cek sebelum pratinjau. */
const LETTER_FIELDS = [
  'template_id',
  'ambalan_id',
  'nomor_surat',
  'jenis_surat',
  'tgl_surat',
  'waktu_kegiatan',
  'lokasi_kegiatan',
  'perihal',
  'tujuan_pengirim',
  'isi_surat',
  'file',
];

/**
 * Nilai isian pada saat percobaan simpan atau pratinjau terakhir ditolak.
 *
 * Inertia tidak menghapus pesan galat sendiri ketika isiannya diperbaiki, jadi
 * pesan dari percobaan yang gagal ikut tertawa. Akibatnya pesan "perihal wajib
 * diisi" tetap tampil setelah isiannya diperbaiki, termasuk setelah menekan
 * tombol Simpan. Pesan hanya dihapus untuk isian yang berubah dibanding nilai
 * saat penolakan.
 */
let rejectedValues = null;

function noteRejectedValues() {
  rejectedValues = LETTER_FIELDS.reduce((values, field) => ({ ...values, [field]: form[field] }), {});
}

watch(
  () => LETTER_FIELDS.map((field) => form[field]),
  () => {
    if (!rejectedValues) {
      return;
    }

    for (const field of LETTER_FIELDS) {
      if (form.errors[field] && form[field] !== rejectedValues[field]) {
        form.clearErrors(field);
      }
    }
  }
);

function pickAttachment(event) {
  form.file = event.target.files?.[0] ?? null;
  form.clearErrors('file');
}

function clearExtras() {
  for (const key of Object.keys(extraValues)) {
    delete extraValues[key];
  }
}

function submitForm() {
  form.placeholder_values = { ...extraValues };
  submitError.value = '';
  noteRejectedValues();

  const options = {
    onSuccess: () => {
      rejectedValues = null;
      reset();
    },
    // Error validasi tampil per-field. Kalau tidak ada satu pun field yang
    // salah, berarti permintaan ditolak server (429 terlalu sering mencoba,
    // 419 token kedaluwarsa, atau 500 kesalahan server). Pesan per-field dari
    // percobaan sebelumnya sudah tidak relevan dan harus ikut dibuang.
    onError: (errors) => {
      if (!Object.keys(errors ?? {}).length) {
        form.clearErrors();
        rejectedValues = null;
        submitError.value =
          'Surat gagal disimpan karena server menolak permintaan. Penyebab paling sering: '
          + 'terlalu banyak percobaan menyimpan dalam waktu singkat. Tunggu sebentar lalu coba lagi, '
          + 'atau periksa log aplikasi.';
      }
    },
  };

  if (editItem.value) {
    form.patch(`/letters/${editItem.value.id}`, options);
  } else {
    form.post('/letters', options);
  }
}

function previewLetter() {
  if (!form.perihal) {
    noteRejectedValues();
    form.setError('perihal', 'Perihal surat wajib diisi sebelum melihat pratinjau.');
    return;
  }
  releasePreviewUrl();
  showPreview.value = true;
  previewLoading.value = true;
  previewError.value = '';

  fetch('/letters/preview', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'text/html',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfToken(),
    },
    body: JSON.stringify({
      ambalan_id: form.ambalan_id || null,
      nomor_surat: form.nomor_surat || null,
      jenis_surat: form.jenis_surat,
      perihal: form.perihal,
      isi_surat: form.isi_surat || '',
      tujuan_pengirim: form.tujuan_pengirim || null,
      tgl_surat: form.tgl_surat || null,
      waktu_kegiatan: form.waktu_kegiatan || null,
      lokasi_kegiatan: form.lokasi_kegiatan || null,
      template_id: form.template_id || null,
      placeholder_values: { ...extraValues },
    }),
  })
    .then(async (response) => {
      const text = await response.text();

      if (!response.ok) {
        throw new Error(previewErrorMessage(response.status, text));
      }

      previewUrl.value = URL.createObjectURL(new Blob([text], { type: 'text/html' }));
      previewLoading.value = false;
    })
    .catch((error) => {
      previewError.value = error.message;
      previewLoading.value = false;
    });
}

function previewErrorMessage(status, text) {
  if (status === 419) {
    return 'Sesi halaman sudah kedaluwarsa. Muat ulang halaman, lalu buka pratinjau lagi.';
  }

  if (status === 429) {
    return 'Terlalu banyak permintaan pratinjau. Tunggu sebentar sebelum mencoba lagi.';
  }

  return extractMessage(text, 'Pratinjau gagal. Periksa kembali isian surat.');
}

function closePreview() {
  showPreview.value = false;
  previewLoading.value = false;
  previewError.value = '';
  releasePreviewUrl();
}

function releasePreviewUrl() {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = '';
  }
}

function closeTemplate() {
  showTemplate.value = false;
  templateForm.reset();
}

function submitTemplate() {
  templateForm.post('/letters/templates', { onSuccess: closeTemplate });
}

function confirmDelete(item) {
  if (confirm(`Hapus surat "${item.perihal}"?`)) {
    router.delete(`/letters/${item.id}`);
  }
}

function humanize(name) {
  return String(name)
    .replace(/[_-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .replace(/^./, (char) => char.toUpperCase());
}

function badgeClass(jenis) {
  if (jenis === 'Masuk') return 'bg-[#A7B92A]/20 text-[#A7B92A]';
  if (jenis === 'Keluar') return 'bg-[#263D26] text-[#EDD330]';
  return 'bg-[#EDD330]/20 text-[#EDD330]';
}

/**
 * Tanggal hari ini menurut waktu lokal peramban.
 *
 * toISOString() memakai UTC, jadi sebelum pukul 07.00 WITA tanggal yang
 * dipakai sebagai bawaan tanggal surat sudah maju satu hari.
 */
function today() {
  const now = new Date();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');

  return `${now.getFullYear()}-${month}-${day}`;
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
}

/**
 * Token CSRF diambil dari meta tag, sama seperti OfflineQueue.js.
 *
 * Cookie XSRF-TOKEN disimpan dalam bentuk terenkripsi oleh EncryptCookies,
 * jadi nilainya tidak sama dengan token di sesi dan tidak bisa dipakai
 * langsung sebagai header X-CSRF-TOKEN (permintaan jadi ditolak 419).
 */
function csrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

function extractMessage(text, fallback) {
  try {
    const payload = JSON.parse(text);
    return payload.message || Object.values(payload.errors ?? {})[0]?.[0] || fallback;
  } catch {
    return htmlMessage(text) || fallback;
  }
}

/** Pesan galat dari halaman pratinjau server, misal "Pratinjau gagal: ...". */
function htmlMessage(html) {
  const match = String(html).match(/<p class="catatan">([\s\S]*?)<\/p>/i);

  if (!match) {
    return '';
  }

  return match[1]
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}
</script>
