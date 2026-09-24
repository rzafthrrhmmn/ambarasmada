<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Pengingat</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Pengingat & Tugas</h1>
      <p class="mt-1 text-sm text-[#8fa06a]">Atur pengingat untuk kegiatan dan tugas.</p>
    </div>

    <div class="space-y-3">
      <div v-for="r in reminders.data" :key="r.id" class="rounded-xl border-2 p-4" :class="r.is_sent ? 'border-[#6F9435]/30 bg-[#335233]' : 'border-[#EDD330]/50 bg-[#335233]/80'">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h3 class="text-sm font-bold text-[#f0ead8]">{{ r.judul }}</h3>
            <p class="text-xs text-[#8fa06a]">{{ r.deskripsi || '-' }}</p>
            <p class="mt-1 text-[10px] text-[#EDD330]">⏰ {{ formatDateTime(r.jadwal) }} • {{ r.jenis }}</p>
          </div>
          <div class="flex gap-2 shrink-0">
            <button v-if="!r.is_sent && canManage" @click="$inertia.post(`/reminders/${r.id}/sent`)" class="rounded-lg border border-[#6F9435] px-2 py-1 text-[10px] font-bold text-[#d4dc9a] hover:bg-[#6F9435]/20">Kirim</button>
            <button v-if="!r.is_sent" @click="$inertia.delete(`/reminders/${r.id}`)" class="rounded-lg border border-[#ef4419]/50 px-2 py-1 text-[10px] font-bold text-[#ef4419] hover:bg-[#ef4419]/20">Hapus</button>
          </div>
        </div>
      </div>
      <p v-if="!reminders.data.length" class="rounded-xl border-2 border-[#6F9435]/30 bg-[#335233] p-10 text-center text-sm text-[#8fa06a]">Belum ada pengingat.</p>
    </div>

    <Modal v-if="showCreate" :title="'Tambah Pengingat'" @close="reset">
      <form @submit.prevent="submit" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Untuk</span><select v-model="form.user_id" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option value="">Pilih</option><option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.role }})</option></select></label>
        <label class="block"><span class="text-xs font-medium">Judul</span><input v-model="form.judul" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Deskripsi</span><textarea v-model="form.deskripsi" rows="2" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"></textarea></label>
        <div class="grid gap-3 sm:grid-cols-2">
          <label class="block"><span class="text-xs font-medium">Jenis</span><select v-model="form.jenis" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option value="Pengingat">Pengingat</option><option value="Tugas">Tugas</option><option value="Iuran">Iuran</option><option value="Kegiatan">Kegiatan</option><option value="Lainnya">Lainnya</option></select></label>
          <label class="block"><span class="text-xs font-medium">Jadwal</span><input v-model="form.jadwal" type="datetime-local" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        </div>
        <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Tambah</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import Modal from '@/Components/Modal.vue';

defineProps({ reminders: Object, users: Array });
const showCreate = ref(false);
const form = useForm({ user_id: '', judul: '', deskripsi: '', jadwal: '', jenis: 'Pengingat' });

function submit() {
  form.post('/reminders', { onSuccess: () => reset() });
}
function reset() {
  showCreate.value = false;
  form.reset();
}
function formatDateTime(value) {
  if (!value) return '-';
  return new Date(value).toLocaleDateString('id-ID') + ' ' + new Date(value).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}
</script>

