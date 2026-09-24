<template>
  <AppLayout>
    <Link href="/attendance" class="mb-5 inline-flex items-center text-sm font-semibold text-[#EDD330] hover:underline">← Kembali ke daftar sesi</Link>
    <div class="mb-6"><p class="text-sm font-medium text-[#EDD330]">Detail Sesi</p><h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ session.nama }}</h1><p class="mt-1 text-sm text-[#8fa06a]">{{ formatDate(session.tanggal) }} • {{ session.lokasi || '-' }}</p></div>

    <div v-if="session.materi_nama" class="mb-6 rounded-2xl border border-[#6F9435]/40 bg-[#335233] p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <svg class="h-6 w-6 text-[#A7B92B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7l-5-5H7a2 2 0 00-2 2v14a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l4 4m0 0l-4 4m4-4H9" /></svg>
          <div>
            <p class="text-sm font-medium text-[#f0ead8]">{{ session.materi_nama }}</p>
            <p class="text-xs text-[#8fa06a]">{{ formatFileSize(session.materi_size) }} • {{ session.materi_mime_type }}</p>
          </div>
        </div>
        <Link :href="route('attendance.materi.download', session.id)" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a] hover:bg-[#6F9435]/30">Unduh</Link>
      </div>
    </div>

    <button v-if="canManage" @click="openSessionEdit()" class="mb-4 rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a] hover:bg-[#6F9435]/30">+ Edit sesi</button>

    <section class="overflow-hidden rounded-2xl border border-[#6F9435] bg-[#335233] shadow-sm border-[#6F9435]"><table class="min-w-full divide-y divide-[#6F9435]/30"><thead class="bg-[#263D26]"><tr><th class="px-4 py-3 text-left text-xs font-semibold text-[#8fa06a]">Anggota</th><th class="px-4 py-3 text-left text-xs font-semibold text-[#8fa06a]">Keterangan</th><th class="px-4 py-3 text-left text-xs font-semibold text-[#8fa06a]">Catatan</th><th class="px-4 py-3 text-right text-xs font-semibold text-[#8fa06a]">Aksi</th></tr></thead><tbody class="divide-y divide-[#6F9435]/30"><tr v-for="item in session.attendances" :key="item.id"><td class="px-4 py-3 text-sm font-medium">{{ item.member?.nama_lengkap }}</td><td class="px-4 py-3 text-sm"><span class="rounded-full bg-[#335233] px-2.5 py-1 text-xs text-[#EDD330]">{{ item.keterangan }}</span></td><td class="px-4 py-3 text-sm text-[#8fa06a]">{{ item.catatan || '-' }}</td><td class="px-4 py-3 text-right text-sm">    <button v-if="canManage" @click="openEdit(item)" class="text-[#EDD330] hover:underline">Edit</button></td></tr></tbody></table></section>

    <Modal v-if="showSessionEdit" title="Edit sesi latihan" @close="showSessionEdit = false">
      <form @submit.prevent="sessionForm.patch(`/attendance/${session.id}`, { onSuccess: () => showSessionEdit = false })" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Nama sesi</span><input v-model="sessionForm.nama" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]" /></label>
        <div class="grid gap-3 sm:grid-cols-2"><label class="block"><span class="text-xs font-medium">Tanggal</span><input v-model="sessionForm.tanggal" type="date" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]" /></label><label class="block"><span class="text-xs font-medium">Lokasi</span><input v-model="sessionForm.lokasi" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]" /></label></div>
        <label class="block"><span class="text-xs font-medium">Materi latihan (ganti, opsional)</span><input @change="onSessionMateriChange" type="file" class="mt-1 text-sm" accept=".pdf,.jpg,.jpeg,.png,.mp4,.webm,.doc,.docx" /></label>
        <p v-if="sessionForm.errors.materi" class="text-xs text-[#ef4419]">{{ sessionForm.errors.materi }}</p>
        <div v-if="session.materi_nama" class="flex items-center gap-2 rounded-lg border border-[#6F9435]/30 bg-[#263D26] px-3 py-2">
          <label class="flex items-center gap-2"><input v-model="sessionForm.remove_materi" type="checkbox" class="h-4 w-4 rounded border-[#6F9435] bg-[#335233] text-[#ef4419]" /><span class="text-xs text-[#8fa06a]">Hapus materi saat ini</span></label>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" @click="showSessionEdit = false" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="sessionForm.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showEdit && editItem" title="Perbarui presensi" @close="showEdit = false">
      <form @submit.prevent="form.patch(`/attendance-records/${editItem.id}`, { onSuccess: () => showEdit = false })" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Keterangan</span><select v-model="form.keterangan" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option>Hadir</option><option>Izin</option><option>Sakit</option><option>Alpa</option></select></label>
        <label class="block"><span class="text-xs font-medium">Catatan</span><textarea v-model="form.catatan" rows="4" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]"></textarea></label>
        <button class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Simpan</button>
      </form>
    </Modal>
  </AppLayout>
</template>
<script setup>
import { computed, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
const props = defineProps({ session: Object });
const page = usePage();
const canManage = computed(() => {
  const role = page.props.auth?.user?.role;
  return role === 'Admin' || role === 'Pembina' || role === 'Pengurus';
});
const showEdit = ref(false);
const editItem = ref(null);
const showSessionEdit = ref(false);
const form = useForm({ keterangan: 'Hadir', catatan: '' });
const sessionForm = useForm({ nama: '', tanggal: '', lokasi: '', materi: null, remove_materi: false });
function openEdit(item) {
  editItem.value = item;
  form.keterangan = item.keterangan;
  form.catatan = item.catatan || '';
  showEdit.value = true;
}
function openSessionEdit() {
  showSessionEdit.value = true;
  sessionForm.nama = props.session.nama || '';
  sessionForm.tanggal = props.session.tanggal ? new Date(props.session.tanggal).toISOString().slice(0, 10) : new Date().toISOString().slice(0, 10);
  sessionForm.lokasi = props.session.lokasi || '';
  sessionForm.materi = null;
  sessionForm.remove_materi = false;
  sessionForm.clearErrors();
}
function onSessionMateriChange(event) {
  sessionForm.materi = event.target.files[0] ?? null;
}
function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}
function formatFileSize(bytes) {
  if (!bytes) {
    return '-';
  }
  const units = ['B', 'KB', 'MB', 'GB'];
  let size = bytes;
  let unitIndex = 0;
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex++;
  }
  return `${unitIndex === 0 ? size : size.toFixed(1)} ${units[unitIndex]}`;
}
</script>
