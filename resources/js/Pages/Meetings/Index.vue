<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Permusyawaratan</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Rapat & Musyawarah</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Catat notulen, voting, dan keputusan rapat.</p>
      </div>
      <button v-if="canManage" @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Rapat Baru</button>
    </div>

    <div class="space-y-3">
      <div v-for="meeting in meetings.data" :key="meeting.id" class="rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5 transition hover:border-[#A7B92B]/60">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h3 class="text-sm font-bold text-[#f0ead8]">{{ meeting.judul }}</h3>
            <p class="mt-1 text-[10px] text-[#8fa06a]">{{ meeting.jenis }} • {{ formatDate(meeting.tanggal) }}</p>
            <p class="text-[10px] text-[#8fa06a]">{{ meeting.attendees_count || 0 }} hadir</p>
          </div>
          <div class="flex gap-2 shrink-0">
            <Link :href="`/meetings/${meeting.id}`" class="rounded-lg border border-[#6F9435] px-2 py-1 text-[10px] font-bold text-[#d4dc9a] hover:bg-[#6F9435]/20">Detail</Link>
          </div>
        </div>
      </div>
      <p v-if="!meetings.data.length" class="rounded-xl border-2 border-[#6F9435]/30 bg-[#335233] p-10 text-center text-sm text-[#8fa06a]">Belum ada rapat.</p>
    </div>
    <Pagination :links="meetings.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />

    <Modal v-if="showCreate" :title="editingMeeting ? 'Edit Rapat' : 'Tambah Rapat'" @close="reset">
      <form @submit.prevent="submit" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Judul</span><input v-model="form.judul" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Agenda (pisahkan per baris)</span><textarea v-model="form.agenda" rows="4" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"></textarea></label>
        <div class="grid gap-3 sm:grid-cols-2">
          <label class="block"><span class="text-xs font-medium">Tanggal</span><input v-model="form.tanggal" type="date" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
          <label class="block"><span class="text-xs font-medium">Jenis</span><select v-model="form.jenis" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option value="">Pilih</option><option value="Musyawarah">Musyawarah</option><option value="Rapat Pembina">Rapat Pembina</option><option value="Rapat Anggota">Rapat Anggota</option><option value="Sidang">Sidang</option><option value="Lainnya">Lainnya</option></select></label>
        </div>
        <label class="block"><span class="text-xs font-medium">Lokasi</span><input v-model="form.lokasi" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Status</span><select v-model="form.status" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option value="Draft">Draft</option><option value="Berlangsung">Berlangsung</option><option value="Selesai">Selesai</option><option value="Dibatalkan">Dibatalkan</option></select></label>
        <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">{{ editingMeeting ? 'Simpan' : 'Tambah' }}</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({ meetings: Object, canManage: Boolean });
const showCreate = ref(false);
const editingMeeting = ref(null);
const form = useForm({ judul: '', agenda: '', tanggal: '', lokasi: '', jenis: 'Musyawarah', status: 'Draft' });

function openEdit(meeting) {
  editingMeeting.value = meeting;
  form.judul = meeting.judul;
  form.agenda = meeting.agenda || '';
  form.tanggal = meeting.tanggal;
  form.lokasi = meeting.lokasi || '';
  form.jenis = meeting.jenis;
  form.status = meeting.status;
  showCreate.value = true;
}
function reset() {
  showCreate.value = false;
  editingMeeting.value = null;
  form.reset();
}
function submit() {
  const url = editingMeeting.value ? `/meetings/${editingMeeting.value.id}` : '/meetings';
  const method = editingMeeting.value ? 'patch' : 'post';
  form[method](url, { onSuccess: () => reset() });
}
function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}
</script>
