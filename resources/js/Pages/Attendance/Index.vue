<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Latihan Rutin</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Presensi Anggota</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Buat sesi latihan, gunakan QR Code, dan lihat rekap kehadiran.</p>
      </div>
      <button v-if="canManage" @click="openCreate" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Sesi latihan</button>
    </div>

    <section v-if="canManage" class="mb-5 rounded-2xl border border-[#6F9435] bg-[#335233] p-4">
      <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="applyFilters">
        <label class="block lg:col-span-2">
          <span class="text-xs font-medium text-[#8fa06a]">Cari</span>
          <input v-model="filters.q" type="search" placeholder="Nama sesi, lokasi, atau kode QR" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8]" />
        </label>
        <label class="block">
          <span class="text-xs font-medium text-[#8fa06a]">Dari tanggal</span>
          <input v-model="filters.from" type="date" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8]" />
        </label>
        <label class="block">
          <span class="text-xs font-medium text-[#8fa06a]">Sampai tanggal</span>
          <input v-model="filters.to" type="date" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8]" />
        </label>
        <label class="block">
          <span class="text-xs font-medium text-[#8fa06a]">Presensi</span>
          <select v-model="filters.status" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8]">
            <option value="all">Semua</option>
            <option value="ada">Sudah ada</option>
            <option value="kosong">Belum ada</option>
          </select>
        </label>
      </form>
      <div class="mt-3 flex flex-wrap items-center gap-2">
        <button @click="applyFilters" class="rounded-lg bg-[#6F9435] px-3 py-1.5 text-xs font-semibold text-white">Terapkan</button>
        <button v-if="hasActiveFilter" @click="resetFilters" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">Reset</button>
        <span class="text-xs text-[#8fa06a]">{{ sessions?.total ?? 0 }} sesi ditemukan</span>
      </div>
    </section>

    <div class="grid gap-5 lg:grid-cols-3">
      <section class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-semibold text-[#f0ead8]">Riwayat sesi</h2>
          <label v-if="canManage && allSelected" class="flex items-center gap-2 text-xs text-[#8fa06a]">
            <input type="checkbox" checked class="h-4 w-4 rounded border-[#6F9435] bg-[#263D26] text-[#EDD330]" disabled />
            Semua di halaman ini dipilih
          </label>
          <button v-else-if="canManage" @click="selectAllOnPage" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">Pilih semua di halaman</button>
        </div>

        <div class="space-y-3">
          <SkeletonLoader v-if="!sessions || !sessions.data" variant="list" :lines="5" />
          <div v-else-if="!sessions.data.length" class="rounded-xl border border-dashed border-[#6F9435] bg-[#263D26] p-6 text-center">
            <p class="text-sm text-[#8fa06a]">Tidak ada sesi yang cocok dengan filter ini.</p>
          </div>
          <div
            v-for="session in sessions.data"
            :key="session.id"
            class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
            :class="isSelected(session.id) ? 'border-[#EDD330] bg-[#3d5c3b]' : 'border-[#6F9435] bg-[#335233]'"
          >
            <div class="flex items-start gap-3">
              <input
                v-if="canManage"
                type="checkbox"
                :checked="isSelected(session.id)"
                @change="toggleSelect(session.id)"
                class="mt-1 h-4 w-4 rounded border-[#6F9435] bg-[#263D26] text-[#EDD330]"
                :aria-label="`Pilih sesi ${session.nama}`"
              />
              <div>
                <Link :href="`/attendance/${session.id}`" class="font-semibold text-[#EDD330] hover:underline">{{ session.nama }}</Link>
                <p class="mt-1 text-xs text-[#8fa06a]">{{ formatDate(session.tanggal) }} • {{ session.lokasi || '-' }} • {{ session.attendances_count }} presensi</p>
                <p v-if="session.materi_nama" class="mt-1 text-xs text-[#8fa06a]">Materi: {{ session.materi_nama }}</p>
                <p v-if="session.latitude !== null" class="mt-1 text-xs text-[#8fa06a]">Geofence aktif • radius {{ session.radius }} m</p>
              </div>
            </div>
            <div class="flex flex-wrap gap-2 items-center">
              <span class="rounded-full bg-[#263D26] px-2.5 py-1 text-xs text-[#EDD330]">{{ session.qr_token }}</span>
              <Link v-if="canManage" :href="`/attendance/${session.id}`" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">Detail</Link>
              <button v-if="canManage" @click="openEdit(session)" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">Edit</button>
              <button v-if="canManage" @click="openDelete(session)" class="rounded-lg border border-[#ef4419]/60 px-3 py-1.5 text-xs font-semibold text-[#ef4419]">Hapus</button>
              <button v-if="canManage" @click="openQr(session)" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">QR Code</button>
              <Link v-if="isAnggota" :href="route('attendance.scan', session.qr_token)" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">Scan</Link>
            </div>
          </div>
        </div>
        <Pagination :links="sessions.links" class="mt-4 border-t border-[#6F9435] p-3" />
      </section>

      <aside class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
        <h2 class="font-semibold text-[#f0ead8]">QR Code presensi</h2>
        <p class="mt-2 text-sm leading-6 text-[#8fa06a]">Tampilkan QR Code ini kepada anggota untuk memindai kehadiran.</p>
        <div v-if="selectedQr" class="mt-4 rounded-xl bg-[#263D26] p-4 text-center">
          <p class="mb-2 text-xs text-[#8fa06a]">{{ selectedQr.nama }}</p>
          <img :src="selectedQr.imageDataUrl" alt="QR Code presensi" class="mx-auto h-48 w-48" />
          <p class="mt-2 text-xs text-[#8fa06a]">Kode: {{ selectedQr.qr_token }}</p>
          <p class="mt-1 text-[10px] text-[#8fa06a]">Scan via halaman /attendance/scan/{{ selectedQr.qr_token }}</p>
        </div>
        <div v-else class="mt-4 rounded-xl bg-[#263D26] p-4 text-center">
          <p class="text-xs text-[#8fa06a]">Pilih sesi di atas untuk menampilkan QR Code</p>
        </div>
      </aside>
    </div>

    <div v-if="canManage && selectedIds.length" class="mt-5 flex flex-wrap items-center gap-3 rounded-2xl border border-[#EDD330]/60 bg-[#335233] px-4 py-3">
      <p class="text-sm text-[#f0ead8]">{{ selectedIds.length }} sesi dipilih</p>
      <button @click="openBulkDelete" class="rounded-lg bg-[#ef4419] px-3 py-1.5 text-xs font-semibold text-white">Hapus terpilih</button>
      <button @click="clearSelection" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">Batalkan pilihan</button>
    </div>

    <Modal v-if="showCreate && canManage" title="Buat sesi latihan" @close="closeCreate">
      <form @submit.prevent="submitCreate" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Nama sesi</span><input v-model="form.nama" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <div class="grid gap-3 sm:grid-cols-2">
          <label class="block"><span class="text-xs font-medium">Tanggal</span><input v-model="form.tanggal" type="date" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
          <label class="block"><span class="text-xs font-medium">Lokasi</span><input v-model="form.lokasi" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        </div>
        <label class="block"><span class="text-xs font-medium">Materi latihan (opsional)</span><input @change="onMateriChange" type="file" class="mt-1 text-sm" accept=".pdf,.jpg,.jpeg,.png,.mp4,.webm,.doc,.docx" /></label>
        <p v-if="form.errors.materi" class="text-xs text-[#ef4419]">{{ form.errors.materi }}</p>

        <div class="mt-1">
          <span class="text-xs font-medium">Titik lokasi &amp; radius presensi</span>
          <p class="mb-2 mt-1 text-xs text-[#8fa06a]">Presensi hanya diterima jika anggota berada dalam radius dari titik ini.</p>
          <LocationPicker v-if="showMapPicker" v-model="location" />
          <button v-else type="button" class="w-full rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/20 hover:text-[#EDD330]" @click="showMapPicker = true">
            Tentukan lokasi di peta
          </button>
          <p v-if="form.errors.latitude || form.errors.longitude || form.errors.radius" class="mt-1 text-xs text-[#ef4419]">
            {{ form.errors.latitude || form.errors.longitude || form.errors.radius }}
          </p>
        </div>

        <button class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Buat sesi</button>
      </form>
    </Modal>

    <Modal v-if="showEdit && canManage && editTarget" title="Edit sesi latihan" @close="closeEdit">
      <form @submit.prevent="submitEdit" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Nama sesi</span><input v-model="editForm.nama" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8]" /></label>
        <div class="grid gap-3 sm:grid-cols-2">
          <label class="block"><span class="text-xs font-medium">Tanggal</span><input v-model="editForm.tanggal" type="date" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8]" /></label>
          <label class="block"><span class="text-xs font-medium">Lokasi</span><input v-model="editForm.lokasi" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8]" /></label>
        </div>
        <label class="block"><span class="text-xs font-medium">Ganti materi (opsional)</span><input @change="onEditMateriChange" type="file" class="mt-1 text-sm" accept=".pdf,.jpg,.jpeg,.png,.mp4,.webm,.doc,.docx" /></label>
        <p v-if="editForm.errors.materi" class="text-xs text-[#ef4419]">{{ editForm.errors.materi }}</p>
        <div v-if="editTarget.materi_nama" class="flex items-center gap-2 rounded-lg border border-[#6F9435]/30 bg-[#263D26] px-3 py-2">
          <label class="flex items-center gap-2">
            <input v-model="editForm.remove_materi" type="checkbox" class="h-4 w-4 rounded border-[#6F9435] bg-[#335233] text-[#ef4419]" />
            <span class="text-xs text-[#8fa06a]">Hapus materi saat ini ({{ editTarget.materi_nama }})</span>
          </label>
        </div>

        <div>
          <span class="text-xs font-medium">Titik lokasi &amp; radius presensi</span>
          <p class="mb-2 mt-1 text-xs text-[#8fa06a]">Presensi hanya diterima jika anggota berada dalam radius dari titik ini.</p>
          <LocationPicker v-if="showEditMapPicker" v-model="editLocation" />
          <button v-else type="button" class="w-full rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/20 hover:text-[#EDD330]" @click="showEditMapPicker = true">
            {{ editLocation.latitude === null ? 'Tentukan lokasi di peta' : 'Ubah titik lokasi' }}
          </button>
          <button v-if="editLocation.latitude !== null" type="button" @click="editLocation = { latitude: null, longitude: null, radius: null }" class="mt-2 w-full rounded-lg border border-[#ef4419]/50 px-3 py-2 text-xs font-bold text-[#ef4419]">Matikan geofence</button>
          <p v-if="editForm.errors.latitude || editForm.errors.longitude || editForm.errors.radius" class="mt-1 text-xs text-[#ef4419]">
            {{ editForm.errors.latitude || editForm.errors.longitude || editForm.errors.radius }}
          </p>
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" @click="closeEdit" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="editForm.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showDelete && canManage && deleteTarget" title="Hapus sesi latihan" @close="closeDelete">
      <div class="grid gap-3">
        <p class="text-sm text-[#f0ead8]">Hapus sesi "<strong>{{ deleteTarget.nama }}</strong>"?</p>
        <p v-if="deleteTarget.attendances_count" class="text-xs text-[#ef4419]">
          Sesi ini sudah punya {{ deleteTarget.attendances_count }} baris presensi sehingga tidak dapat dihapus.
          Hapus baris presensinya lebih dulu dari halaman detail sesi.
        </p>
        <p v-else class="text-xs text-[#8fa06a]">Sesi dihapus permanen dari daftar. Tindakan ini tidak bisa dibatalkan.</p>
        <div class="flex justify-end gap-2">
          <button @click="closeDelete" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button
            :disabled="deleteTarget.attendances_count > 0 || deleteForm.processing"
            @click="submitDelete"
            class="rounded-lg bg-[#ef4419] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
          >
            {{ deleteForm.processing ? 'Menghapus...' : 'Hapus' }}
          </button>
        </div>
      </div>
    </Modal>

    <Modal v-if="showBulkDelete && canManage" title="Hapus sesi terpilih" @close="showBulkDelete = false">
      <div class="grid gap-3">
        <p class="text-sm text-[#f0ead8]">Hapus {{ selectedIds.length }} sesi yang dipilih?</p>
        <div v-if="blockedPreview.length" class="rounded-lg border border-[#ef4419]/50 bg-[#263D26] p-3">
          <p class="text-xs font-semibold text-[#ef4419]">{{ blockedPreview.length }} sesi tidak akan dihapus</p>
          <p class="mt-1 text-xs text-[#8fa06a]">Sesi berikut sudah punya presensi:</p>
          <ul class="mt-1 max-h-32 list-inside list-disc overflow-y-auto text-xs text-[#d4dc9a]">
            <li v-for="nama in blockedPreview" :key="nama">{{ nama }}</li>
          </ul>
        </div>
        <p class="text-xs text-[#8fa06a]">Sesi tanpa presensi akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.</p>
        <div class="flex justify-end gap-2">
          <button @click="showBulkDelete = false" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button :disabled="deleteableCount === 0 || bulkForm.processing" @click="submitBulkDelete" class="rounded-lg bg-[#ef4419] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
            {{ bulkForm.processing ? 'Menghapus...' : `Hapus ${deleteableCount} sesi` }}
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import LocationPicker from '@/Components/LocationPicker.vue';

const props = defineProps({
  sessions: Object,
  members: Array,
  filters: { type: Object, default: () => ({ q: '', from: '', to: '', status: 'all' }) },
});

const page = usePage();
const isAnggota = computed(() => page.props.auth?.user?.role === 'Anggota');
const canManage = computed(() => {
  const role = page.props.auth?.user?.role;
  return role === 'Admin' || role === 'Pembina' || role === 'Pengurus';
});

const showCreate = ref(false);
const showMapPicker = ref(false);
const location = ref({ latitude: null, longitude: null, radius: 100 });
const selectedQr = ref(null);

const filters = ref({ ...props.filters });

const showEdit = ref(false);
const showEditMapPicker = ref(false);
const editTarget = ref(null);
const editLocation = ref({ latitude: null, longitude: null, radius: null });

const showDelete = ref(false);
const deleteTarget = ref(null);

const showBulkDelete = ref(false);
const selectedIds = ref([]);

const form = useForm({ nama: '', tanggal: new Date().toISOString().slice(0, 10), lokasi: '', materi: null, latitude: null, longitude: null, radius: null });
const editForm = useForm({ nama: '', tanggal: '', lokasi: '', materi: null, remove_materi: false, latitude: null, longitude: null, radius: null });
const deleteForm = useForm({});
const bulkForm = useForm({ ids: [] });

const allSelected = computed(
  () => (props.sessions?.data?.length ?? 0) > 0 && selectedIds.value.length === props.sessions.data.length
);

const hasActiveFilter = computed(
  () => filters.value.q !== '' || filters.value.from !== '' || filters.value.to !== '' || filters.value.status !== 'all'
);

// Sesi yang sudah punya presensi tidak akan dihapus, jadi tampilkan lebih dulu
// supaya petugas tahu sebagian pilihan memang akan ditolak.
const blockedPreview = computed(() =>
  selectedIds.value
    .map((id) => props.sessions?.data?.find((s) => s.id === id))
    .filter((s) => s && s.attendances_count > 0)
    .map((s) => s.nama)
);

const deleteableCount = computed(() => selectedIds.value.length - blockedPreview.value.length);

function isSelected(id) {
  return selectedIds.value.includes(id);
}

function toggleSelect(id) {
  selectedIds.value = isSelected(id) ? selectedIds.value.filter((x) => x !== id) : [...selectedIds.value, id];
}

function selectAllOnPage() {
  selectedIds.value = (props.sessions?.data ?? []).map((s) => s.id);
}

function clearSelection() {
  selectedIds.value = [];
}

function applyFilters() {
  selectedIds.value = [];
  router.get(
    '/attendance',
    {
      q: filters.value.q || undefined,
      from: filters.value.from || undefined,
      to: filters.value.to || undefined,
      status: filters.value.status !== 'all' ? filters.value.status : undefined,
    },
    { preserveState: true, preserveScroll: true, replace: true }
  );
}

function resetFilters() {
  filters.value = { q: '', from: '', to: '', status: 'all' };
  applyFilters();
}

// Baris yang tidak lagi tampil setelah filter atau paginasi tidak boleh tetap
// terpilih, karena ID-nya tidak ada di halaman yang sedang aktif.
watch(
  () => props.sessions?.data?.map((s) => s.id) ?? [],
  (ids) => {
    selectedIds.value = selectedIds.value.filter((id) => ids.includes(id));
  }
);

function submitCreate() {
  form.latitude = location.value.latitude;
  form.longitude = location.value.longitude;
  form.radius = location.value.latitude === null ? null : location.value.radius;

  form.post('/attendance', {
    onSuccess: () => {
      showCreate.value = false;
      showMapPicker.value = false;
      location.value = { latitude: null, longitude: null, radius: 100 };
      form.reset();
    },
  });
}

function closeCreate() {
  showCreate.value = false;
  showMapPicker.value = false;
}

function openCreate() {
  form.reset();
  form.tanggal = new Date().toISOString().slice(0, 10);
  location.value = { latitude: null, longitude: null, radius: 100 };
  showMapPicker.value = false;
  showCreate.value = true;
}

function onMateriChange(event) {
  form.materi = event.target.files[0] ?? null;
}

function openEdit(session) {
  editTarget.value = session;
  editForm.nama = session.nama || '';
  editForm.tanggal = session.tanggal ? new Date(session.tanggal).toISOString().slice(0, 10) : new Date().toISOString().slice(0, 10);
  editForm.lokasi = session.lokasi || '';
  editForm.materi = null;
  editForm.remove_materi = false;
  editLocation.value = {
    latitude: session.latitude === null || session.latitude === undefined ? null : Number(session.latitude),
    longitude: session.longitude === null || session.longitude === undefined ? null : Number(session.longitude),
    radius: session.radius ? Number(session.radius) : 100,
  };
  showEditMapPicker.value = false;
  editForm.clearErrors();
  showEdit.value = true;
}

function closeEdit() {
  showEdit.value = false;
  showEditMapPicker.value = false;
}

function onEditMateriChange(event) {
  editForm.materi = event.target.files[0] ?? null;
}

function submitEdit() {
  editForm.latitude = editLocation.value.latitude;
  editForm.longitude = editLocation.value.longitude;
  editForm.radius = editLocation.value.latitude === null ? null : editLocation.value.radius;

  editForm.patch(`/attendance/${editTarget.value.id}`, {
    onSuccess: () => {
      showEdit.value = false;
      showEditMapPicker.value = false;
    },
  });
}

function openDelete(session) {
  deleteTarget.value = session;
  deleteForm.clearErrors();
  showDelete.value = true;
}

function closeDelete() {
  showDelete.value = false;
  deleteTarget.value = null;
}

function submitDelete() {
  deleteForm.delete(`/attendance/${deleteTarget.value.id}`, {
    onSuccess: () => {
      closeDelete();
      selectedIds.value = selectedIds.value.filter((id) => id !== deleteTarget.value?.id);
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
  bulkForm.post('/attendance/bulk-destroy', {
    onSuccess: () => {
      showBulkDelete.value = false;
      selectedIds.value = [];
    },
    preserveScroll: true,
  });
}

async function openQr(session) {
  const QRCode = (await import('qrcode')).default;
  const scannerUrl = `${window.location.origin}/attendance/scan/${session.qr_token}`;
  selectedQr.value = {
    id: session.id,
    nama: session.nama,
    qr_token: session.qr_token,
    imageDataUrl: await QRCode.toDataURL(scannerUrl, { width: 256, margin: 2, color: { dark: '#1a1a1a', light: '#ffffff' } }),
  };
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}
</script>