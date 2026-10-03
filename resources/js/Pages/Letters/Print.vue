<template>
  <AppLayout>
    <div class="mx-auto max-w-4xl">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-sm font-medium text-[#EDD330]">Pratinjau cetak</p>
          <p class="mt-1 text-sm text-[#8fa06a]">
            {{ letter.perihal }} &bull; {{ letter.jenis_surat }}
            <span v-if="letter.template">&bull; template {{ letter.template.name }}</span>
          </p>
        </div>
        <div class="flex gap-2">
          <Link href="/letters" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">
            Kembali
          </Link>
          <a
            :href="`/letters/${letter.id}/generate?format=pdf`"
            class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#A7B92A]"
          >
            Unduh PDF
          </a>
          <button
            type="button"
            data-test="btn-cetak"
            @click="printPage"
            class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white"
          >
            Cetak
          </button>
        </div>
      </div>

      <p v-if="problem" class="mb-3 rounded-lg border border-[#ef4419]/60 p-3 text-xs text-[#ef4419]" data-test="peringatan-template">
        {{ problem }}
      </p>

      <p v-if="unfilled.length" class="mb-3 rounded-lg border border-[#ef4419]/60 p-3 text-xs text-[#ef4419]">
        Penanda template belum diisi: {{ unfilled.join(', ') }}
      </p>

      <article class="rounded-2xl border border-[#6F9435] bg-white p-8 shadow-lg print:border-0 print:bg-white print:p-0 print:shadow-none">
        <div v-if="letter.file_path" class="mb-4">
          <p class="rounded-lg bg-[#335233] p-3 text-xs text-[#f0ead8]">
            Surat ini memiliki lampiran terpisah. Isi di bawah adalah isi yang diekspor dari template.
          </p>
        </div>
        <div class="isi-surat" v-html="body"></div>
      </article>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

defineProps({
  letter: { type: Object, required: true },
  body: { type: String, default: '' },
  unfilled: { type: Array, default: () => [] },
  problem: { type: String, default: null },
});

function printPage() {
  window.print();
}
</script>

<style scoped>
.isi-surat :deep(p) {
  margin: 0 0 4px;
  color: #1f2b1f;
  font-family: 'DejaVu Sans', 'Times New Roman', serif;
  font-size: 11pt;
  line-height: 1.5;
}

.isi-surat :deep(p.kosong) {
  margin: 0 0 10px;
}

.isi-surat :deep(table) {
  width: 100%;
  margin: 0 0 14px;
  border-collapse: collapse;
  table-layout: fixed;
}

.isi-surat :deep(td) {
  padding: 2px 6px 2px 0;
  vertical-align: top;
}

.isi-surat :deep(table.tabel-bergaris td) {
  border: 1px solid #9aa08c;
  padding: 4px 6px;
}

.isi-surat :deep(td.label) {
  width: 190px;
  color: #4c6b2a;
}

.isi-surat :deep(.kop) {
  margin-bottom: 18px;
  text-align: center;
}

.isi-surat :deep(.kop .lembaga) {
  font-size: 12pt;
  font-weight: bold;
}

.isi-surat :deep(.kop .sub) {
  color: #4c6b2a;
  font-size: 10pt;
}

.isi-surat :deep(hr.garis) {
  margin: 10px 0 18px;
  border: none;
  border-top: 1.5px solid #6f9435;
}

.isi-surat :deep(.isi) {
  margin-top: 12px;
  text-align: justify;
}

.isi-surat :deep(.isi p) {
  margin: 0 0 8px;
}

.isi-surat :deep(.ttd) {
  margin-top: 34px;
  text-align: right;
}

.isi-surat :deep(.ttd .nama) {
  margin-top: 58px;
  font-weight: bold;
  text-decoration: underline;
}

.isi-surat :deep(.tab) {
  display: inline-block;
  width: 2.2em;
}

@media print {
  .isi-surat :deep(p) {
    font-size: 12pt;
  }
}
</style>
