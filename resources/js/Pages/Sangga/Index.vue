<template>
  <AppLayout>
    <div class="page-head">
      <div>
        <p class="page-eyebrow">Organisasi</p>
        <h1 class="page-title">Sangga</h1>
        <p class="page-subtitle">Kelola satuan anak anggota ambalan.</p>
      </div>
      <button v-if="canManage" type="button" class="btn-primary" @click="openCreate">
        <AppIcon name="plus" :stroke="2.2" class="h-4 w-4" />
        Sangga
      </button>
    </div>

    <SkeletonLoader v-if="loading" variant="card" :lines="4" />

    <p v-else-if="!sanggas.length" class="empty-state">
      <AppIcon name="groups" class="empty-state-icon h-6 w-6" />
      <span class="empty-state-text">Belum ada sangga.</span>
      <button v-if="canManage" type="button" class="btn-ghost btn-sm mt-1" @click="openCreate">
        <AppIcon name="plus" :stroke="2.2" class="h-3.5 w-3.5" />
        Buat sangga pertama
      </button>
    </p>

    <div v-else class="grid gap-4 xl:grid-cols-2">
      <section v-for="sangga in sanggas" :key="sangga.id" class="card flex flex-col">
        <span class="card-glow" aria-hidden="true" />

        <div class="relative flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h2 class="flex flex-wrap items-center gap-2 text-base font-bold text-krem-100">
              <AppIcon name="groups" class="h-4 w-4 shrink-0 text-[#A7B92B]" />
              {{ sangga.nama }}
              <span class="rounded bg-daun-500/20 px-1.5 py-0.5 text-[10px] font-medium text-krem-300">
                {{ sangga.kode }}
              </span>
            </h2>
            <p class="mt-1 text-xs text-krem-300">{{ sangga.deskripsi || 'Tanpa deskripsi' }}</p>
            <p class="mt-1 text-xs text-krem-300 flex items-center gap-1">
              <AppIcon name="members" class="h-3 w-3 shrink-0 text-[#A7B92B]" />
              {{ sangga.members?.length || 0 }} anggota
            </p>
          </div>

          <div v-if="canManage" class="flex shrink-0 flex-wrap justify-end gap-1.5">
            <button type="button" class="btn-ghost btn-sm" @click="openEdit(sangga)">
              <AppIcon name="note" class="h-3.5 w-3.5" />
              Edit
            </button>
            <button type="button" class="btn-ghost btn-sm" @click="showDetail(sangga)">
              <AppIcon name="search" class="h-3.5 w-3.5" />
              Detail
            </button>
            <button type="button" class="btn-danger" @click="destroy(sangga)">
              <AppIcon name="trash" class="h-3.5 w-3.5" />
              Hapus
            </button>
          </div>
        </div>
      </section>
    </div>

    <Modal v-if="showCreate" :title="editingSangga ? 'Edit Sangga' : 'Tambah Sangga'" @close="reset">
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
        <label class="block">
          <span class="field-label">Kepala Sangga</span>
          <select v-model="form.kepala_sanga_id" class="field mt-1">
            <option value="">Pilih</option>
            <option v-for="member in members" :key="member.id" :value="member.id">
              {{ member.nama_lengkap }} ({{ member.nta }})
            </option>
          </select>
        </label>
        <button type="submit" :disabled="form.processing" class="btn-primary">
          {{ editingSangga ? 'Simpan Perubahan' : 'Tambah' }}
        </button>
      </form>
    </Modal>

    <Modal v-if="showDetailModal" :title="detailSangga?.nama" @close="closeDetail">
      <div class="grid gap-3">
        <label class="block">
          <span class="field-label">Kode</span>
          <p class="field mt-1">{{ detailSangga?.kode }}</p>
        </label>
        <label class="block">
          <span class="field-label">Deskripsi</span>
          <p class="field mt-1 whitespace-pre-wrap">{{ detailSangga?.deskripsi || 'Tanpa deskripsi' }}</p>
        </label>
        <label class="block">
          <span class="field-label">Kepala Sangga</span>
          <p class="field mt-1">{{ detailSangga?.kepalaSanga?.nama_lengkap || 'Belum ditetapkan' }}</p>
        </label>
        <label class="block">
          <span class="field-label">Anggota</span>
          <div class="flex flex-wrap gap-1.5 mt-1">
            <span
              v-for="member in detailSangga?.members || []"
              :key="member.id"
              class="badge-neutral border border-[#6F9435]/40"
            >
              {{ member.nama_lengkap }}
              <span class="text-krem-300">{{ member.tingkatan }} {{ member.kelas }}</span>
            </span>
          </div>
        </label>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-primary" @click="manageMembers(detailSangga)">
            Kelola Anggota
          </button>
          <button type="button" class="btn-ghost btn-sm" @click="closeDetail">
            Tutup
          </button>
        </div>
      </div>
    </Modal>

    <Modal v-if="showManageMembers" :title="'Kelola Anggota - ' + selectedSangga?.nama" @close="closeManageMembers">
      <form class="grid gap-3" @submit.prevent="assignMembers">
        <label class="block">
          <span class="field-label">Pilih Anggota</span>
          <select v-model="manageForm.member_ids" multiple size="4" class="field mt-1">
            <option v-for="member in availableMembers" :key="member.id" :value="member.id">
              {{ member.nama_lengkap }} ({{ member.nta }})
            </option>
          </select>
          <p class="mt-1 text-xs text-krem-300">Tekan Ctrl/Cmd untuk memilih banyak anggota.</p>
        </label>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn-ghost btn-sm" @click="closeManageMembers">
            Batal
          </button>
          <button type="submit" :disabled="manageForm.processing" class="btn-primary">
            Tambah ke Sangga
          </button>
        </div>
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

const props = defineProps({
  sanggas: { type: Array, default: () => [] },
  members: { type: Array, default: () => [] },
});

const { can } = useAccess();

const canManage = computed(() => can('sanggas.manage'));

const loading = computed(() => props.sanggas === null);

const showCreate = ref(false);
const showDetailModal = ref(false);
const showManageMembers = ref(false);

const editingSangga = ref(null);
const detailSangga = ref(null);
const selectedSangga = ref(null);
const availableMembers = ref([]);

const form = useForm({ nama: '', kode: '', deskripsi: '', kepala_sanga_id: '' });
const manageForm = useForm({ member_ids: [] });

function openCreate() {
  editingSangga.value = null;
  form.reset();
  form.nama = '';
  form.kode = '';
  form.deskripsi = '';
  form.kepala_sanga_id = '';
  showCreate.value = true;
}

function openEdit(sangga) {
  editingSangga.value = sangga;
  form.nama = sangga.nama;
  form.kode = sangga.kode;
  form.deskripsi = sangga.deskripsi || '';
  form.kepala_sanga_id = sangga.kepala_sanga_id || '';
  showCreate.value = true;
}

function reset() {
  showCreate.value = false;
  editingSangga.value = null;
  form.reset();
}

function showDetail(sangga) {
  detailSangga.value = sangga;
  showDetailModal.value = true;
}

function closeDetail() {
  showDetailModal.value = false;
  detailSangga.value = null;
}

function manageMembers() {
  const sangga = detailSangga.value;
  if (!sangga) return;
  selectedSangga.value = sangga;
  availableMembers.value = sangga.members?.length
    ? props.members.filter((m) => !sangga.members.find((sm) => sm.id === m.id))
    : props.members;
  manageForm.member_ids = [];
  showManageMembers.value = true;
}

function closeManageMembers() {
  showManageMembers.value = false;
  selectedSangga.value = null;
  availableMembers.value = [];
  manageForm.reset();
}

function submit() {
  const id = editingSangga.value?.id;
  const options = { onSuccess: reset };

  if (id) {
    form.patch(`/sanggas/${id}`, options);
  } else {
    form.post('/sanggas', options);
  }
}

function destroy(sangga) {
  if (!confirm(`Hapus sangga "${sangga.nama}"?`)) {
    return;
  }

  router.delete(`/sanggas/${sangga.id}`, {
    onSuccess: () => {
      detailSangga.value = null;
      showDetailModal.value = false;
    },
  });
}

function assignMembers() {
  const sangga = selectedSangga.value;
  if (!sangga) return;

  manageForm.post(`/sanggas/${sangga.id}/members`, {
    onSuccess: () => {
      closeManageMembers();
      // Refresh detail to reflect new members
      if (detailSangga.value?.id === sangga.id) {
        router.route('sanggas.show', sangga);
      } else {
        router.reload();
      }
    },
  });
}
</script>
