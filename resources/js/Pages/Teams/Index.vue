<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Organisasi</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Gugus Depan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola tim dan tugas.</p>
      </div>
      <button v-if="canManage" @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Gugus Depan</button>
    </div>

    <div class="space-y-4">
      <div v-for="team in teams" :key="team.id" class="rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-[#f0ead8]">{{ team.nama }} <span class="text-[10px] font-medium text-[#8fa06a]">({{ team.kode }})</span></h3>
            <p class="text-xs text-[#8fa06a]">{{ team.deskripsi || 'Tanpa deskripsi' }}</p>
          </div>
          <div class="flex gap-2">
            <button v-if="canManage" @click="showCreateTask(team)" class="rounded-lg border border-[#6F9435] px-2 py-1 text-[10px] font-bold text-[#d4dc9a] hover:bg-[#6F9435]/20">+ Tugas</button>
            <button v-if="canManage" @click="openEdit(team)" class="rounded-lg border border-[#6F9435] px-2 py-1 text-[10px] font-bold text-[#d4dc9a] hover:bg-[#6F9435]/20">Edit</button>
            <button v-if="canManage" @click="$inertia.delete(`/teams/${team.id}`)" class="rounded-lg border border-[#ef4419]/50 px-2 py-1 text-[10px] font-bold text-[#ef4419] hover:bg-[#ef4419]/20">Hapus</button>
          </div>
        </div>
        <div class="mt-3">
          <p class="text-[10px] font-bold text-[#8fa06a]">ANGGOTA ({{ team.members?.length }})</p>
          <div class="mt-1 flex flex-wrap gap-2">
            <span v-for="m in team.members" :key="m.id" class="rounded-full bg-[#6F9435]/20 px-2 py-0.5 text-[10px] text-[#d4dc9a]">{{ m.nama_lengkap }} - {{ m.peran }}</span>
          </div>
        </div>
        <div v-if="team.tasks?.length" class="mt-3 border-t border-[#6F9435]/30 pt-3">
          <p class="text-[10px] font-bold text-[#8fa06a]">TUGAS</p>
          <div v-for="t in team.tasks" :key="t.id" class="mt-1 flex items-center justify-between rounded-lg bg-[#263D26] px-3 py-2">
            <p class="text-xs text-[#f0ead8]">{{ t.judul }}</p>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" :class="{
              'bg-[#EDD330]/20 text-[#EDD330]': t.status === 'Belum Dimulai',
              'bg-[#A7B92B]/20 text-[#A7B92B]': t.status === 'Berlangsung',
              'bg-[#6F9435]/20 text-[#6F9435]': t.status === 'Selesai',
              'bg-[#ef4419]/20 text-[#ef4419]': t.status === 'Terlewat',
            }">{{ t.status }}</span>
          </div>
        </div>
      </div>
      <p v-if="!teams.length" class="rounded-xl border-2 border-[#6F9435]/30 bg-[#335233] p-10 text-center text-sm text-[#8fa06a]">Belum ada gugus depan.</p>
    </div>

    <Modal v-if="showCreate" :title="editingTeam ? 'Edit Gugus Depan' : 'Tambah Gugus Depan'" @close="reset">
      <form @submit.prevent="submit" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Nama</span><input v-model="form.nama" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Kode</span><input v-model="form.kode" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Deskripsi</span><textarea v-model="form.deskripsi" rows="3" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"></textarea></label>
        <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">{{ editingTeam ? 'Simpan' : 'Tambah' }}</button>
      </form>
    </Modal>

    <Modal v-if="showTaskModal" :title="'Tambah Tugas'" @close="showTaskModal = false">
      <form @submit.prevent="submitTask" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Judul</span><input v-model="taskForm.judul" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Deskripsi</span><textarea v-model="taskForm.deskripsi" rows="2" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"></textarea></label>
        <label class="block"><span class="text-xs font-medium">Ditetapkan Kepada</span><select v-model="taskForm.assigned_to" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option value="">Pilih</option><option v-for="m in taskMembers" :key="m.id" :value="m.id">{{ m.nama_lengkap }}</option></select></label>
        <label class="block"><span class="text-xs font-medium">Tenggat</span><input v-model="taskForm.tenggat" type="date" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <button type="submit" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Tambah</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';

defineProps({ teams: Array, taskMembers: Array });
const canManage = true;
const showCreate = ref(false);
const showTaskModal = ref(false);
const editingTeam = ref(null);
const currentTeamId = ref(null);
const form = useForm({ nama: '', kode: '', deskripsi: '' });
const taskForm = useForm({ judul: '', deskripsi: '', assigned_to: '', tenggat: '' });

function openEdit(team) {
  editingTeam.value = team;
  form.nama = team.nama;
  form.kode = team.kode;
  form.deskripsi = team.deskripsi || '';
  showCreate.value = true;
}
function reset() {
  showCreate.value = false;
  editingTeam.value = null;
  form.reset();
}
function submit() {
  const url = editingTeam.value ? `/teams/${editingTeam.value.id}` : '/teams';
  const method = editingTeam.value ? 'patch' : 'post';
  form[method](url, { onSuccess: () => reset() });
}
function showCreateTask(team) {
  currentTeamId.value = team.id;
  showTaskModal.value = true;
}
function submitTask() {
  taskForm.post(`/teams/${currentTeamId.value}/task`, { onSuccess: () => { showTaskModal.value = false; taskForm.reset(); } });
}
</script>
