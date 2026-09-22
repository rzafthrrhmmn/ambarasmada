<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Sertifikat</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Sertifikat Anggota</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Terbitkan dan kelola sertifikat anggota ambalan.</p>
      </div>
      <button v-if="canManage" @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Terbitkan</button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="cert in certificates.data" :key="cert.id" class="rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
        <div class="mb-2 flex items-center justify-between">
          <span class="rounded-full px-2.5 py-1 text-[10px] font-bold" :class="{
            'bg-[#A7B92B]/20 text-[#A7B92B]': cert.status === 'Diterbitkan',
            'bg-[#EDD330]/20 text-[#EDD330]': cert.status === 'Draft',
            'bg-[#ef4419]/20 text-[#ef4419]': cert.status === 'Dibatalkan',
          }">{{ cert.status }}</span>
        </div>
        <p class="text-[10px] text-[#8fa06a]">{{ cert.nomor_sertifikat }}</p>
        <h3 class="mt-1 text-sm font-bold text-[#f0ead8]">{{ cert.judul }}</h3>
        <p class="text-[10px] text-[#8fa06a]">{{ cert.member?.nama_lengkap }} • {{ cert.jenis }}</p>
        <p class="text-[10px] text-[#8fa06a]">{{ cert.tanggal_diterbitkan ? formatDate(cert.tanggal_diterbitkan) : '-' }}</p>
        <div class="mt-2 flex gap-2">
          <a v-if="cert.file_path" :href="`/certificates/${cert.id}/download`" target="_blank" class="text-[10px] rounded border border-[#6F9435] px-2 py-1 text-[#d4dc9a] hover:bg-[#6F9435]/20">Download</a>
          <button v-if="canManage" @click="cert.status === 'Diterbitkan' ? cert.status = 'Dibatalkan' : cert.status = 'Diterbitkan'" class="text-[10px] rounded border border-[#6F9435] px-2 py-1 text-[#d4dc9a] hover:bg-[#6F9435]/20">
            {{ cert.status === 'Diterbitkan' ? 'Cabut' : 'Terbitkan' }}
          </button>
        </div>
      </div>
      <p v-if="!certificates.data.length" class="col-span-full rounded-xl border-2 border-[#6F9435]/30 bg-[#335233] p-10 text-center text-sm text-[#8fa06a]">Belum ada sertifikat.</p>
    </div>
    <Pagination :links="certificates.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />

    <Modal v-if="showCreate" :title="'Terbitkan Sertifikat'" @close="reset">
      <form @submit.prevent="submit" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Anggota</span><select v-model="form.member_id" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option value="">Pilih</option><option v-for="m in members" :key="m.id" :value="m.id">{{ m.nama_lengkap }}</option></select></label>
        <label class="block"><span class="text-xs font-medium">Jenis</span><input v-model="form.jenis" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Judul</span><input v-model="form.judul" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Deskripsi</span><textarea v-model="form.deskripsi" rows="3" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"></textarea></label>
        <label class="block"><span class="text-xs font-medium">Tanggal Diterbitkan</span><input v-model="form.tanggal_diterbitkan" type="date" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">File (opsional)</span><input type="file" @change="form.file = $event.target.files[0]" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-xs text-[#d4dc9a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#6F9435] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white" /></label>
        <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Terbitkan</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({ certificates: Object, members: Array });
const canManage = true;
const showCreate = ref(false);
const form = useForm({ member_id: '', jenis: '', judul: '', deskripsi: '', tanggal_diterbitkan: '', file: null });

function submit() {
  form.post('/certificates', { onSuccess: () => reset() });
}
function reset() {
  showCreate.value = false;
  form.reset();
}
function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}
</script>
