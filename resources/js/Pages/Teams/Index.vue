<template>
  <AppLayout>
    <div class="page-head">
      <div>
        <p class="page-eyebrow">Organisasi</p>
        <h1 class="page-title">Gugus Depan</h1>
        <p class="page-subtitle">Kelola tim dan tugas.</p>
      </div>
      <button v-if="canManage" type="button" class="btn-primary" @click="openCreate">
        <AppIcon name="plus" :stroke="2.2" class="h-4 w-4" />
        Gugus Depan
      </button>
    </div>

    <SkeletonLoader v-if="loading" variant="card" :lines="4" />

    <p v-else-if="!teams.length" class="empty-state">
      <AppIcon name="teams" class="empty-state-icon h-6 w-6" />
      <span class="empty-state-text">Belum ada gugus depan.</span>
      <button v-if="canManage" type="button" class="btn-ghost btn-sm mt-1" @click="openCreate">
        <AppIcon name="plus" :stroke="2.2" class="h-3.5 w-3.5" />
        Buat gugus depan pertama
      </button>
    </p>

    <div v-else class="grid gap-4 xl:grid-cols-2">
      <section v-for="team in teams" :key="team.id" class="card flex flex-col">
        <span class="card-glow" aria-hidden="true" />

        <div class="relative flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h2 class="flex flex-wrap items-center gap-2 text-base font-bold text-[#f0ead8]">
              <AppIcon name="teams" class="h-4 w-4 shrink-0 text-[#A7B92B]" />
              {{ team.nama }}
              <span class="rounded bg-[#6F9435]/20 px-1.5 py-0.5 text-[10px] font-medium text-[#8fa06a]">
                {{ team.kode }}
              </span>
            </h2>
            <p class="mt-1 text-xs text-[#8fa06a]">{{ team.deskripsi || 'Tanpa deskripsi' }}</p>
          </div>

          <div v-if="canManage" class="flex shrink-0 flex-wrap justify-end gap-1.5">
            <button type="button" class="btn-ghost btn-sm" @click="showCreateTask(team)">
              <AppIcon name="plus" :stroke="2.2" class="h-3.5 w-3.5" />
              Tugas
            </button>
            <button type="button" class="btn-ghost btn-sm" @click="openEdit(team)">
              <AppIcon name="note" class="h-3.5 w-3.5" />
              Edit
            </button>
            <button type="button" class="btn-danger" @click="destroy(team)">
              <AppIcon name="trash" class="h-3.5 w-3.5" />
              Hapus
            </button>
          </div>
        </div>

        <div class="relative mt-4">
          <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-[#8fa06a]">
            <AppIcon name="members" class="h-3.5 w-3.5 text-[#A7B92B]" />
            Anggota ({{ team.members?.length || 0 }})
          </p>

          <div v-if="team.members?.length" class="mt-2 flex flex-wrap gap-1.5">
            <span
              v-for="member in team.members"
              :key="member.id"
              class="badge-neutral border border-[#6F9435]/40"
            >
              {{ member.nama_lengkap }}
              <span class="text-[#8fa06a]">{{ member.peran }}</span>
            </span>
          </div>
          <p v-else class="mt-2 text-xs text-[#8fa06a]">Belum ada anggota di gugus depan ini.</p>
        </div>

        <div v-if="team.tasks?.length" class="relative mt-4 border-t border-[#6F9435]/30 pt-3">
          <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-[#8fa06a]">
            <AppIcon name="clipboard" class="h-3.5 w-3.5 text-[#A7B92B]" />
            Tugas ({{ team.tasks.length }})
          </p>
          <ul class="mt-2 space-y-1.5">
            <li
              v-for="task in team.tasks"
              :key="task.id"
              class="flex items-center justify-between gap-3 rounded-lg border border-[#6F9435]/25 bg-[#263D26] px-3 py-2"
            >
              <span class="min-w-0 truncate text-xs text-[#f0ead8]">{{ task.judul }}</span>
              <span class="badge shrink-0" :class="taskBadge(task.status)">
                <AppIcon :name="taskIcon(task.status)" class="h-3 w-3" />
                {{ task.status }}
              </span>
            </li>
          </ul>
        </div>
      </section>
    </div>

    <Modal v-if="showCreate" :title="editingTeam ? 'Edit Gugus Depan' : 'Tambah Gugus Depan'" @close="reset">
      <form class="grid gap-3" @submit.prevent="submit">
        <label class="block">
          <span class="field-label">Nama</span>
          <input v-model="form.nama" required class="field mt-1" />
          <span v-if="form.errors.nama" class="mt-1 block text-xs text-[#ef4419]">{{ form.errors.nama }}</span>
        </label>
        <label class="block">
          <span class="field-label">Kode</span>
          <input v-model="form.kode" required class="field mt-1" />
          <span v-if="form.errors.kode" class="mt-1 block text-xs text-[#ef4419]">{{ form.errors.kode }}</span>
        </label>
        <label class="block">
          <span class="field-label">Deskripsi</span>
          <textarea v-model="form.deskripsi" rows="3" class="field mt-1 resize-y"></textarea>
        </label>
        <button type="submit" :disabled="form.processing" class="btn-primary">
          {{ editingTeam ? 'Simpan Perubahan' : 'Tambah' }}
        </button>
      </form>
    </Modal>

    <Modal v-if="showTaskModal" title="Tambah Tugas" @close="closeTask">
      <form class="grid gap-3" @submit.prevent="submitTask">
        <label class="block">
          <span class="field-label">Judul</span>
          <input v-model="taskForm.judul" required class="field mt-1" />
          <span v-if="taskForm.errors.judul" class="mt-1 block text-xs text-[#ef4419]">{{ taskForm.errors.judul }}</span>
        </label>
        <label class="block">
          <span class="field-label">Deskripsi</span>
          <textarea v-model="taskForm.deskripsi" rows="2" class="field mt-1 resize-y"></textarea>
        </label>
        <label class="block">
          <span class="field-label">Ditetapkan Kepada</span>
          <select v-model="taskForm.assigned_to" required class="field mt-1">
            <option value="">Pilih</option>
            <option v-for="member in taskMembers" :key="member.id" :value="member.id">
              {{ member.nama_lengkap }}
            </option>
          </select>
          <span v-if="taskForm.errors.assigned_to" class="mt-1 block text-xs text-[#ef4419]">
            {{ taskForm.errors.assigned_to }}
          </span>
        </label>
        <label class="block">
          <span class="field-label">Tenggat</span>
          <input v-model="taskForm.tenggat" type="date" class="field mt-1" />
        </label>
        <button type="submit" :disabled="taskForm.processing" class="btn-primary">Tambah Tugas</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import Modal from '@/Components/Modal.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import { useAccess } from '@/Composables/useAccess.js';

const props = defineProps({ teams: { type: Array, default: null }, taskMembers: { type: Array, default: () => [] } });

const { can } = useAccess();

// Dulu selalu true sehingga tombol terbit muncul untuk semua orang, lalu
// ditolak 403 saat dikirim.
const canManage = computed(() => can('teams.manage'));

const loading = computed(() => props.teams === null);

const showCreate = ref(false);
const showTaskModal = ref(false);
const editingTeam = ref(null);
const currentTeamId = ref(null);
const form = useForm({ nama: '', kode: '', deskripsi: '' });
const taskForm = useForm({ judul: '', deskripsi: '', assigned_to: '', tenggat: '' });

// Peta status ditulis sebagai data supaya warna dan ikon tiap status hanya
// ada di satu tempat, bukan empat blok `class` yang saling menimpa.
const TASK_STATES = {
  'Belum Dimulai': { badge: 'badge-neutral', icon: 'clock' },
  Berlangsung: { badge: 'badge-pending', icon: 'pulse' },
  Selesai: { badge: 'badge-approved', icon: 'checkCircle' },
  Terlewat: { badge: 'badge-rejected', icon: 'xCircle' },
};

const taskBadge = (status) => TASK_STATES[status]?.badge ?? 'badge-neutral';
const taskIcon = (status) => TASK_STATES[status]?.icon ?? 'dot';

function openCreate() {
  editingTeam.value = null;
  form.reset();
  showCreate.value = true;
}

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
  const id = editingTeam.value?.id;
  const options = { onSuccess: reset };

  if (id) {
    form.patch(`/teams/${id}`, options);
  } else {
    form.post('/teams', options);
  }
}

function showCreateTask(team) {
  currentTeamId.value = team.id;
  taskForm.reset();
  showTaskModal.value = true;
}

function closeTask() {
  showTaskModal.value = false;
  currentTeamId.value = null;
  taskForm.reset();
}

function submitTask() {
  taskForm.post(`/teams/${currentTeamId.value}/task`, {
    onSuccess: closeTask,
  });
}

function destroy(team) {
  if (!confirm(`Hapus gugus depan "${team.nama}"?`)) {
    return;
  }

  router.delete(`/teams/${team.id}`);
}
</script>