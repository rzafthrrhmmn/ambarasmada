<template>
  <AppLayout>
    <Link href="/attendance" class="mb-5 inline-flex items-center text-sm font-semibold text-[#EDD330] hover:underline">← Kembali ke daftar sesi</Link>
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Detail Sesi</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ session.nama }}</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">{{ formatDate(session.tanggal) }} • {{ session.lokasi || '-' }}</p>
      </div>
      <div v-if="canManage" class="flex flex-wrap gap-2">
        <button @click="openAdd" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a] hover:bg-[#6F9435]/30">+ Tambah presensi</button>
        <button @click="openSessionEdit()" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a] hover:bg-[#6F9435]/30">Edit sesi</button>
        <button @click="openDeleteSession" class="rounded-lg border border-[#ef4419]/60 px-3 py-1.5 text-xs font-semibold text-[#ef4419]">Hapus sesi</button>
      </div>
    </div>

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

    <section class="overflow-hidden rounded-2xl border border-[#6F9435] bg-[#335233] shadow-sm">
      <div v-if="canManage" class="flex flex-wrap items-center gap-3 border-b border-[#6F9435]/30 px-4 py-3">
        <label class="flex items-center gap-2 text-xs text-[#8fa06a]">
          <input type="checkbox" :checked="allSelected" @change="toggleAll" class="h-4 w-4 rounded border-[#6F9435] bg-[#263D26] text-[#EDD330]" />
          Pilih semua ({{ rows.length }})
        </label>
        <span v-if="selectedIds.length" class="text-xs text-[#EDD330]">{{ selectedIds.length }} dipilih</span>
        <div v-if="selectedIds.length" class="ml-auto flex flex-wrap gap-2">
          <button @click="openBulkUpdate" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">Ubah keterangan</button>
          <button @click="openBulkDelete" class="rounded-lg border border-[#ef4419]/60 px-3 py-1.5 text-xs font-semibold text-[#ef4419]">Hapus terpilih</button>
        </div>
      </div>

      <table class="min-w-full divide-y divide-[#6F9435]/30">
        <thead class="bg-[#263D26]">
          <tr>
            <th v-if="canManage" class="w-10 px-4 py-3"></th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-[#8fa06a]">Anggota</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-[#8fa06a]">Keterangan</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-[#8fa06a]">Catatan</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-[#8fa06a]">Waktu</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-[#8fa06a]">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#6F9435]/30">
          <tr v-if="!rows.length">
            <td :colspan="canManage ? 6 : 5" class="px-4 py-6 text-center text-sm text-[#8fa06a]">Belum ada presensi pada sesi ini.</td>
          </tr>
          <tr v-for="item in rows" :key="item.id" :class="isSelected(item.id) ? 'bg-[#3d5c3b]' : ''">
            <td v-if="canManage" class="px-4 py-3">
              <input type="checkbox" :checked="isSelected(item.id)" @change="toggleSelect(item.id)" class="h-4 w-4 rounded border-[#6F9435] bg-[#263D26] text-[#EDD330]" :aria-label="`Pilih presensi ${item.member?.nama_lengkap}`" />
            </td>
            <td class="px-4 py-3 text-sm font-medium">{{ item.member?.nama_lengkap || 'Anggota dihapus' }}</td>
            <td class="px-4 py-3 text-sm"><span class="rounded-full px-2.5 py-1 text-xs" :class="keteranganClass(item.keterangan)">{{ item.keterangan }}</span></td>
            <td class="px-4 py-3 text-sm text-[#8fa06a]">{{ item.catatan || '-' }}</td>
            <td class="px-4 py-3 text-sm text-[#8fa06a]">{{ formatDateTime(item.checked_at) }}</td>
            <td class="px-4 py-3 text-right text-sm">
              <div v-if="canManage" class="flex justify-end gap-3">
                <button @click="openEdit(item)" class="text-[#EDD330] hover:underline">Edit</button>
                <button @click="openDeleteRecord(item)" class="text-[#ef4419] hover:underline">Hapus</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <Modal v-if="showSessionEdit" title="Edit sesi latihan" @close="closeSessionEdit">
      <form @submit.prevent="submitSessionEdit" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Nama sesi</span><input v-model="sessionForm.nama" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]" /></label>
        <div class="grid gap-3 sm:grid-cols-2"><label class="block"><span class="text-xs font-medium">Tanggal</span><input v-model="sessionForm.tanggal" type="date" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]" /></label><label class="block"><span class="text-xs font-medium">Lokasi</span><input v-model="sessionForm.lokasi" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]" /></label></div>
        <label class="block"><span class="text-xs font-medium">Materi latihan (ganti, opsional)</span><input @change="onSessionMateriChange" type="file" class="mt-1 text-sm" accept=".pdf,.jpg,.jpeg,.png,.mp4,.webm,.doc,.docx" /></label>
        <p v-if="sessionForm.errors.materi" class="text-xs text-[#ef4419]">{{ sessionForm.errors.materi }}</p>
        <div v-if="session.materi_nama" class="flex items-center gap-2 rounded-lg border border-[#6F9435]/30 bg-[#263D26] px-3 py-2">
          <label class="flex items-center gap-2"><input v-model="sessionForm.remove_materi" type="checkbox" class="h-4 w-4 rounded border-[#6F9435] bg-[#335233] text-[#ef4419]" /><span class="text-xs text-[#8fa06a]">Hapus materi saat ini</span></label>
        </div>

        <div>
          <span class="text-xs font-medium">Titik lokasi &amp; radius presensi</span>
          <p class="mb-2 mt-1 text-xs text-[#8fa06a]">Presensi hanya diterima jika anggota berada dalam radius dari titik ini.</p>
          <LocationPicker v-if="showMapPicker" v-model="sessionLocation" />
          <button v-else type="button" class="w-full rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/20 hover:text-[#EDD330]" @click="showMapPicker = true">
            {{ sessionLocation.latitude === null ? 'Tentukan lokasi di peta' : 'Ubah titik lokasi' }}
          </button>
          <p v-if="sessionForm.errors.latitude || sessionForm.errors.longitude || sessionForm.errors.radius" class="mt-1 text-xs text-[#ef4419]">
            {{ sessionForm.errors.latitude || sessionForm.errors.longitude || sessionForm.errors.radius }}
          </p>
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

    <Modal v-if="showAdd && canManage" title="Tambah presensi manual" @close="closeAdd">
      <form @submit.prevent="submitAdd" class="grid gap-3">
        <p class="text-xs text-[#8fa06a]">Gunakan ini ketika seorang anggota hadir tetapi tidak sempat memindai QR Code.</p>
        <label class="block">
          <span class="text-xs font-medium">Anggota</span>
          <input v-model="addForm.member_search" type="search" placeholder="Cari nama atau NTA" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8]" />
          <select v-model="addForm.member_id" required class="mt-2 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8]">
            <option value="">Pilih anggota</option>
            <option v-for="m in filteredMembers" :key="m.id" :value="m.id">{{ m.nama_lengkap }} ({{ m.nta }})</option>
          </select>
        </label>
        <p v-if="addForm.errors.member_id" class="text-xs text-[#ef4419]">{{ addForm.errors.member_id }}</p>
        <label class="block"><span class="text-xs font-medium">Keterangan</span><select v-model="addForm.keterangan" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option>Hadir</option><option>Izin</option><option>Sakit</option><option>Alpa</option></select></label>
        <label class="block"><span class="text-xs font-medium">Catatan</span><textarea v-model="addForm.catatan" rows="3" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]"></textarea></label>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeAdd" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="addForm.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Tambah</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="deleteRecordTarget && canManage" title="Hapus presensi" @close="deleteRecordTarget = null">
      <div class="grid gap-3">
        <p class="text-sm text-[#f0ead8]">Hapus presensi <strong>{{ deleteRecordTarget.member?.nama_lengkap }}</strong> pada sesi ini?</p>
        <p class="text-xs text-[#ef4419]">Baris presensi ini akan dihapus permanen dan tidak bisa dibatalkan.</p>
        <div class="flex justify-end gap-2">
          <button @click="deleteRecordTarget = null" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button :disabled="recordForm.processing" @click="submitDeleteRecord" class="rounded-lg bg-[#ef4419] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Hapus</button>
        </div>
      </div>
    </Modal>

    <Modal v-if="showDeleteSession && canManage" title="Hapus sesi latihan" @close="showDeleteSession = false">
      <div class="grid gap-3">
        <p class="text-sm text-[#f0ead8]">Hapus sesi "<strong>{{ session.nama }}</strong>" beserta {{ rows.length }} baris presensinya?</p>
        <p class="text-xs text-[#ef4419]">Baris presensi ikut terhapus permanen. Tindakan ini tidak bisa dibatalkan.</p>
        <div class="flex justify-end gap-2">
          <button @click="showDeleteSession = false" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button :disabled="rows.length > 0 || sessionForm.processing" @click="submitDeleteSession" class="rounded-lg bg-[#ef4419] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
            {{ rows.length > 0 ? 'Hapus presensi dulu' : 'Hapus sesi' }}
          </button>
        </div>
      </div>
    </Modal>

    <Modal v-if="showBulkUpdate && canManage" title="Ubah keterangan terpilih" @close="showBulkUpdate = false">
      <form @submit.prevent="submitBulkUpdate" class="grid gap-3">
        <p class="text-xs text-[#8fa06a]">Perubahan diterapkan ke {{ selectedIds.length }} baris presensi.</p>
        <label class="block"><span class="text-xs font-medium">Keterangan</span><select v-model="bulkForm.keterangan" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option>Hadir</option><option>Izin</option><option>Sakit</option><option>Alpa</option></select></label>
        <label class="block"><span class="text-xs font-medium">Catatan (opsional, isi untuk menimpa semua)</span><textarea v-model="bulkForm.catatan" rows="3" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]"></textarea></label>
        <p v-if="bulkForm.errors.keterangan" class="text-xs text-[#ef4419]">{{ bulkForm.errors.keterangan }}</p>
        <div class="flex justify-end gap-2">
          <button type="button" @click="showBulkUpdate = false" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="bulkForm.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Terapkan</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showBulkDelete && canManage" title="Hapus presensi terpilih" @close="showBulkDelete = false">
      <div class="grid gap-3">
        <p class="text-sm text-[#f0ead8]">Hapus {{ selectedIds.length }} baris presensi?</p>
        <ul class="max-h-40 list-inside list-disc overflow-y-auto text-xs text-[#8fa06a]">
          <li v-for="id in selectedIds" :key="id">{{ memberName(id) }}</li>
        </ul>
        <p class="text-xs text-[#ef4419]">Baris presensi yang dihapus tidak bisa dikembalikan.</p>
        <div class="flex justify-end gap-2">
          <button @click="showBulkDelete = false" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button :disabled="bulkForm.processing" @click="submitBulkDelete" class="rounded-lg bg-[#ef4419] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
            {{ bulkForm.processing ? 'Menghapus...' : `Hapus ${selectedIds.length} baris` }}
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>
<script setup>
import { computed, ref, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import LocationPicker from '@/Components/LocationPicker.vue';
const props = defineProps({ session: Object, members: { type: Array, default: () => [] } });
const page = usePage();
const canManage = computed(() => {
  const role = page.props.auth?.user?.role;
  return role === 'Admin' || role === 'Pembina' || role === 'Pengurus';
});
const showEdit = ref(false);
const editItem = ref(null);
const showSessionEdit = ref(false);
const form = useForm({ keterangan: 'Hadir', catatan: '' });
const sessionForm = useForm({ nama: '', tanggal: '', lokasi: '', materi: null, remove_materi: false, latitude: null, longitude: null, radius: null });
const showMapPicker = ref(false);
const sessionLocation = ref({ latitude: null, longitude: null, radius: 100 });

const rows = computed(() => props.session?.attendances ?? []);
const selectedIds = ref([]);

const showAdd = ref(false);
const addForm = useForm({ member_id: '', member_search: '', keterangan: 'Hadir', catatan: '' });

const deleteRecordTarget = ref(null);
const recordForm = useForm({});

const showDeleteSession = ref(false);
const showBulkUpdate = ref(false);
const showBulkDelete = ref(false);
const bulkForm = useForm({ ids: [], action: 'update', keterangan: 'Hadir', catatan: '' });

const allSelected = computed(() => rows.value.length > 0 && selectedIds.value.length === rows.value.length);

const filteredMembers = computed(() => {
  const needle = addForm.member_search.trim().toLowerCase();
  if (!needle) return props.members;
  return props.members.filter(
    (m) => m.nama_lengkap.toLowerCase().includes(needle) || String(m.nta).includes(needle)
  );
});

function memberName(id) {
  return rows.value.find((r) => r.id === id)?.member?.nama_lengkap ?? 'ID ' + id;
}

function keteranganClass(keterangan) {
  if (keterangan === 'Hadir') return 'bg-[#6F9435]/40 text-[#d4f0a0]';
  if (keterangan === 'Izin') return 'bg-[#EDD330]/20 text-[#EDD330]';
  if (keterangan === 'Sakit') return 'bg-[#ef4419]/20 text-[#ff9f8a]';
  return 'bg-[#8fa06a]/20 text-[#d4dc9a]';
}

function isSelected(id) {
  return selectedIds.value.includes(id);
}

function toggleSelect(id) {
  selectedIds.value = isSelected(id) ? selectedIds.value.filter((x) => x !== id) : [...selectedIds.value, id];
}

function toggleAll() {
  selectedIds.value = allSelected.value ? [] : rows.value.map((r) => r.id);
}

// Baris yang hilang setelah reload tidak boleh tetap terpilih.
watch(
  () => rows.value.map((r) => r.id),
  (ids) => {
    selectedIds.value = selectedIds.value.filter((id) => ids.includes(id));
  }
);

function openAdd() {
  addForm.reset();
  addForm.keterangan = 'Hadir';
  showAdd.value = true;
}

function closeAdd() {
  showAdd.value = false;
}

function submitAdd() {
  addForm.post(`/attendance/${props.session.id}/records`, {
    onSuccess: () => closeAdd(),
  });
}

function openDeleteRecord(item) {
  recordForm.clearErrors();
  deleteRecordTarget.value = item;
}

function submitDeleteRecord() {
  // Id harus ditangkap lebih dulu; setelah delete() target dikosongkan.
  const id = deleteRecordTarget.value.id;

  recordForm.delete(`/attendance-records/${id}`, {
    onSuccess: () => {
      deleteRecordTarget.value = null;
      selectedIds.value = selectedIds.value.filter((selected) => selected !== id);
    },
    preserveScroll: true,
  });
}

function openDeleteSession() {
  sessionForm.clearErrors();
  showDeleteSession.value = true;
}

function submitDeleteSession() {
  sessionForm.delete(`/attendance/${props.session.id}`);
}

function openBulkUpdate() {
  bulkForm.clearErrors();
  bulkForm.keterangan = 'Hadir';
  bulkForm.catatan = '';
  showBulkUpdate.value = true;
}

function submitBulkUpdate() {
  bulkForm.ids = [...selectedIds.value];
  bulkForm.action = 'update';
  bulkForm.attendance_session_id = props.session.id;
  bulkForm.post('/attendance-records/bulk', {
    onSuccess: () => {
      showBulkUpdate.value = false;
      selectedIds.value = [];
    },
    preserveScroll: true,
  });
}

function openBulkDelete() {
  bulkForm.clearErrors();
  showBulkDelete.value = true;
}

function submitBulkDelete() {
  bulkForm.ids = [...selectedIds.value];
  bulkForm.action = 'delete';
  bulkForm.attendance_session_id = props.session.id;
  bulkForm.post('/attendance-records/bulk', {
    onSuccess: () => {
      showBulkDelete.value = false;
      selectedIds.value = [];
    },
    preserveScroll: true,
  });
}

function submitSessionEdit() {
  sessionForm.latitude = sessionLocation.value.latitude;
  sessionForm.longitude = sessionLocation.value.longitude;
  sessionForm.radius = sessionLocation.value.latitude === null ? null : sessionLocation.value.radius;

  sessionForm.patch(`/attendance/${props.session.id}`, {
    onSuccess: () => {
      showSessionEdit.value = false;
      showMapPicker.value = false;
    },
  });
}

function closeSessionEdit() {
  showSessionEdit.value = false;
  showMapPicker.value = false;
}

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
  sessionLocation.value = {
    latitude: props.session.latitude === null ? null : Number(props.session.latitude),
    longitude: props.session.longitude === null ? null : Number(props.session.longitude),
    radius: props.session.radius ? Number(props.session.radius) : 100,
  };
  showMapPicker.value = false;
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
