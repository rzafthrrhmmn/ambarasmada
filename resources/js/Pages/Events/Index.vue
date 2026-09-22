<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Kegiatan</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Manajemen Kegiatan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Buat, kelola, dan pantau seluruh kegiatan ambalan.</p>
      </div>
      <button v-if="canManage" @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Kegiatan Baru</button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="event in events.data" :key="event.id" class="rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5 shadow-lg transition hover:border-[#A7B92B]/60">
        <div class="mb-3 flex items-center justify-between">
          <span class="rounded-full bg-[#6F9435]/20 px-2.5 py-1 text-[10px] font-bold text-[#EDD330]">{{ event.jenis }}</span>
          <span class="rounded-full px-2.5 py-1 text-[10px] font-bold" :class="{
            'bg-[#A7B92B]/20 text-[#A7B92B]': event.status === 'Aktif',
            'bg-[#EDD330]/20 text-[#EDD330]': event.status === 'Draft',
            'bg-[#ef4419]/20 text-[#ef4419]': event.status === 'Dibatalkan',
            'bg-[#8fa06a]/20 text-[#8fa06a]': event.status === 'Selesai',
          }">{{ event.status }}</span>
        </div>
        <h3 class="text-base font-bold text-[#f0ead8]">{{ event.nama }}</h3>
        <p class="mt-1 line-clamp-2 text-xs leading-5 text-[#8fa06a]">{{ event.deskripsi || 'Tanpa deskripsi' }}</p>
        <div class="mt-3 flex items-center gap-3 text-[10px] font-medium text-[#8fa06a]">
          <span>📅 {{ formatDate(event.tanggal) }}</span>
          <span v-if="event.lokasi">📍 {{ event.lokasi }}</span>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
          <span v-if="event.participants_count !== undefined" class="text-[10px] font-medium text-[#8fa06a]">{{ event.participants_count }} peserta</span>
          <button v-if="canManage" @click="openEdit(event)" class="text-[10px] text-[#EDD330] hover:underline">Edit</button>
          <Link v-if="isAnggota" :href="`/events/${event.id}/join`" class="text-[10px] rounded-lg border border-[#6F9435] px-2 py-0.5 text-[#d4dc9a]">Daftar</Link>
        </div>
      </div>
      <p v-if="!events.data.length" class="col-span-full rounded-xl border-2 border-[#6F9435]/30 bg-[#335233] p-10 text-center text-sm text-[#8fa06a]">Belum ada kegiatan.</p>
    </div>
    <Pagination :links="events.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />

    <Modal v-if="showCreate" :title="editingEvent ? 'Edit Kegiatan' : 'Tambah Kegiatan'" @close="reset">
      <form @submit.prevent="submit" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Nama Kegiatan</span><input v-model="form.nama" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Deskripsi</span><textarea v-model="form.deskripsi" rows="3" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"></textarea></label>
        <div class="grid gap-3 sm:grid-cols-2">
          <label class="block"><span class="text-xs font-medium">Tanggal</span><input v-model="form.tanggal" type="date" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
          <label class="block"><span class="text-xs font-medium">Jenis</span><select v-model="form.jenis" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option value="">Pilih</option><option value="Latihan">Latihan</option><option value="Kegiatan">Kegiatan</option><option value="Pertemuan">Pertemuan</option><option value="Outbound">Outbound</option><option value="Jambore">Jambore</option><option value="Lainnya">Lainnya</option></select></label>
        </div>
        <label class="block"><span class="text-xs font-medium">Lokasi</span><input v-model="form.lokasi" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Status</span><select v-model="form.status" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option value="Draft">Draft</option><option value="Aktif">Aktif</option><option value="Selesai">Selesai</option><option value="Dibatalkan">Dibatalkan</option></select></label>
        <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">{{ editingEvent ? 'Simpan' : 'Tambah' }}</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({ events: Object, canManage: Boolean });
const showCreate = ref(false);
const editingEvent = ref(null);
const form = useForm({ nama: '', deskripsi: '', tanggal: '', lokasi: '', jenis: '', status: 'Draft' });

function openEdit(event) {
  editingEvent.value = event;
  form.nama = event.nama;
  form.deskripsi = event.deskripsi || '';
  form.tanggal = event.tanggal;
  form.lokasi = event.lokasi || '';
  form.jenis = event.jenis;
  form.status = event.status;
  showCreate.value = true;
}
function reset() {
  showCreate.value = false;
  editingEvent.value = null;
  form.reset();
}
function submit() {
  const url = editingEvent.value ? `/events/${editingEvent.value.id}` : '/events';
  const method = editingEvent.value ? 'patch' : 'post';
  form[method](url, { onSuccess: () => reset() });
}
function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}
const isAnggota = computed(() => page.props.auth?.user?.role === 'Anggota');
const page = usePage();
</script>
