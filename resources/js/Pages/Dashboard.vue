<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-bold text-[#EDD330]">Dashboard</p>
        <h1 class="mt-1 text-2xl font-extrabold text-[#f0ead8]" style="text-shadow: 2px 2px 0 rgba(0,0,0,0.3);">Selamat datang, {{ $page.props.auth?.user?.name }}</h1>
        <p class="mt-1 text-sm font-medium text-[#8fa06a]">Pantau perkembangan ambalan dari satu layar.</p>
      </div>
      <Link href="/profile" class="inline-flex w-fit items-center rounded-lg border-2 border-[#6F9435] px-3 py-2 text-sm font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">Profil saya</Link>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <StatCard v-for="(value, label) in stats" :key="label" :label="labels[label]" :value="value" :icon="icons[label]" />
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-3">
      <ActivityCalendar
        v-if="isMember"
        :sessions="upcomingSessions"
        class="xl:col-span-1"
      />
      <UpcomingActivities
        v-if="isMember"
        :sessions="upcomingSessions"
        :attended-session-ids="attendedSessionIds"
        class="xl:col-span-2"
      />
    </section>

    <section v-if="isAdminOrPembina" class="mt-6 rounded-2xl border-2 border-[#A7B92A]/40 bg-[#335233] p-5 shadow-lg">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-extrabold text-[#EDD330]">Logo Ambalan</h2>
          <p class="mt-1 text-xs font-medium text-[#8fa06a]">Perbarui logo yang tampil di seluruh situs.</p>
        </div>
        <Link href="/ambalan" class="rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">Kelola Logo</Link>
      </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-3">
      <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-lg xl:col-span-2">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h2 class="font-extrabold text-[#EDD330]">Pengajuan SKU menunggu verifikasi</h2>
            <p class="text-xs font-medium text-[#8fa06a]">Antrean terbaru dari anggota ambalan</p>
          </div>
          <Link v-if="!isMember" href="/sku" class="text-xs font-bold text-[#A7B92B] hover:text-[#EDD330]">Lihat semua</Link>
        </div>
        <div v-if="pendingSku.length" class="space-y-3">
          <div v-for="submission in pendingSku" :key="submission.id" class="flex items-center justify-between gap-3 rounded-xl border-2 border-[#6F9435]/40 bg-[#263D26] p-3">
            <div class="min-w-0">
              <p class="truncate text-sm font-bold text-[#f0ead8]">{{ submission.member?.nama_lengkap }}</p>
              <p class="truncate text-xs font-medium text-[#8fa06a]">{{ submission.sku_point?.tingkatan }} • Poin {{ submission.sku_point?.nomor_poin }}</p>
            </div>
            <span class="rounded-full border-2 border-[#EDD330]/50 bg-[#EDD330]/20 px-2.5 py-1 text-xs font-bold text-[#EDD330]">Pending</span>
          </div>
        </div>
        <p v-else class="rounded-xl border-2 border-[#6F9435]/30 bg-[#263D26] p-5 text-center text-sm font-medium text-[#8fa06a]">Tidak ada pengajuan SKU baru.</p>
      </div>

      <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-lg">
        <h2 class="font-extrabold text-[#EDD330]">Pengumuman terbaru</h2>
        <div class="mt-4 space-y-4">
          <article v-for="announcement in announcements" :key="announcement.id" class="border-b-2 border-[#6F9435]/30 pb-4 last:border-[#A7B92B] last:pb-0">
            <h3 class="text-sm font-bold text-[#f0ead8]">{{ announcement.judul }}</h3>
            <p class="mt-1 line-clamp-2 text-xs leading-5 font-medium text-[#8fa06a]">{{ announcement.isi }}</p>
            <time class="mt-2 block text-[11px] font-bold text-[#8fa06a]">{{ formatDate(announcement.published_at) }}</time>
          </article>
          <p v-if="!announcements.length" class="text-sm font-medium text-[#8fa06a]">Belum ada pengumoman diterbitkan.</p>
        </div>
      </div>
    </section>

    <section v-if="isAdminOrPembina" class="mt-6 grid gap-6 xl:grid-cols-3">
      <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-lg xl:col-span-2">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h2 class="font-extrabold text-[#EDD330]">TKU — Poin SKU</h2>
            <p class="text-xs font-medium text-[#8fa06a]">Kelola poin SKU dan lihat statistik pengajuan.</p>
          </div>
          <button type="button" @click="openSkuEdit(null)" class="inline-flex items-center rounded-lg bg-[#EDD330] px-3 py-1.5 text-xs font-bold text-[#263D26] hover:bg-white">Kelola Poin SKU</button>
        </div>

        <div class="space-y-4" v-if="hasSkuPoints">
          <div v-for="(level, label) in skuPointsByLevel" :key="label">
            <h3 class="mb-2 flex items-center justify-between text-sm font-bold text-[#EDD330]"><span>{{ label }}</span><span class="text-[10px] font-medium text-[#8fa06a]">{{ level.length }} poin</span></h3>
            <div class="space-y-2">
              <div v-for="point in level" :key="point.id" class="flex items-center gap-3 rounded-xl border border-[#6F9435]/30 bg-[#263D26] p-3">
                <span class="flex h-5 w-5 items-center justify-center rounded border border-[#6F9435]/50 text-xs" :class="point.aggregate?.Approved ? 'bg-[#A7B92B]/30 border-[#A7B92B] text-[#A7B92B]' : 'text-[#8fa06a]'">
                  <span v-if="point.aggregate?.Approved">✓</span>
                </span>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium text-[#f0ead8]">{{ point.nomor_poin }}. {{ point.deskripsi_poin }}</p>
                  <div v-if="point.aggregate" class="mt-1 flex flex-wrap gap-1">
                    <span class="rounded-full bg-[#A7B92B]/20 px-2 py-0.5 text-[10px] font-bold text-[#A7B92B]">{{ point.aggregate.Approved || 0 }} disetujui</span>
                    <span class="rounded-full bg-[#EDD330]/20 px-2 py-0.5 text-[10px] font-bold text-[#EDD330]">{{ point.aggregate.Pending || 0 }} pending</span>
                    <span v-if="point.aggregate.Rejected" class="rounded-full bg-[#ef4419]/20 px-2 py-0.5 text-[10px] font-bold text-[#ef4419]">{{ point.aggregate.Rejected }} ditolak</span>
                  </div>
                </div>
                <span class="shrink-0 rounded-lg border border-[#EDD330]/50 px-3 py-1.5 text-[10px] font-bold text-[#EDD330] hover:bg-[#EDD330]/20 cursor-pointer" @click.stop="openSkuEdit(point)">Edit</span>
              </div>
            </div>
          </div>
        </div>
        <p v-else class="text-center text-sm text-[#8fa06a]">Tidak ada poin SKU tersedia.</p>
      </div>

      <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-lg">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h2 class="font-extrabold text-[#EDD330]">TKK — Tugas Khusus Khusus</h2>
            <p class="text-xs font-medium text-[#8fa06a]">Kelola poin TKK Wajib Penegak.</p>
          </div>
          <button type="button" @click="openTkkEdit(null)" class="inline-flex items-center rounded-lg bg-[#EDD330] px-3 py-1.5 text-xs font-bold text-[#263D26] hover:bg-white">Kelola Poin TKK</button>
        </div>

        <div v-if="tkkPoints.length" class="space-y-3">
          <div v-for="tkk in tkkPoints" :key="tkk.id" class="flex items-center gap-3 rounded-xl border border-[#6F9435]/30 bg-[#263D26] p-3">
            <span class="flex h-5 w-5 items-center justify-center rounded border border-[#6F9435]/50 text-xs" :class="tkk.aggregate?.Approved ? 'bg-[#A7B92B]/30 border-[#A7B92B] text-[#A7B92B]' : 'text-[#8fa06a]'">
              <span v-if="tkk.aggregate?.Approved">✓</span>
            </span>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-[#f0ead8]">{{ tkk.nama }}</p>
              <p v-if="tkk.deskripsi" class="text-xs text-[#8fa06a]">{{ tkk.deskripsi }}</p>
              <div v-if="tkk.aggregate" class="mt-1 flex flex-wrap gap-1">
                <span class="rounded-full bg-[#A7B92B]/20 px-2 py-0.5 text-[10px] font-bold text-[#A7B92B]">{{ tkk.aggregate.Approved || 0 }} disetujui</span>
                <span class="rounded-full bg-[#EDD330]/20 px-2 py-0.5 text-[10px] font-bold text-[#EDD330]">{{ tkk.aggregate.Pending || 0 }} pending</span>
                <span v-if="tkk.aggregate.Rejected" class="rounded-full bg-[#ef4419]/20 px-2 py-0.5 text-[10px] font-bold text-[#ef4419]">{{ tkk.aggregate.Rejected }} ditolak</span>
              </div>
            </div>
            <span class="shrink-0 rounded-lg border border-[#EDD330]/50 px-3 py-1.5 text-[10px] font-bold text-[#EDD330] hover:bg-[#EDD330]/20 cursor-pointer" @click.stop="openTkkEdit(tkk)">Edit</span>
          </div>
        </div>
        <p v-else class="text-center text-sm text-[#8fa06a]">Tidak ada poin TKK tersedia.</p>
      </div>
    </section>
  </AppLayout>

  <Modal v-if="skuEditOpen" :title="editingSkuPoint ? `Edit Poin ${editingSkuPoint.nomor_poin}` : 'Tambah Poin SKU'" @close="closeSkuEdit">
    <form @submit.prevent="submitSkuEdit" class="grid gap-3">
      <label v-if="!editingSkuPoint" class="block">
        <span class="text-xs font-medium">Tingkatan</span>
        <select v-model="formSkuEdit.tingkatan" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
          <option value="">Pilih</option>
          <option value="Bantara">Bantara</option>
          <option value="Laksana">Laksana</option>
        </select>
        <p v-if="formSkuEdit.errors.tingkatan" class="mt-1 text-xs text-[#ef4419]">{{ formSkuEdit.errors.tingkatan }}</p>
      </label>
      <label v-else class="block">
        <span class="text-xs font-medium">Tingkatan</span>
        <input v-model="formSkuEdit.tingkatan" type="text" readonly class="mt-1 w-full rounded-lg border border-[#6F9435]/30 bg-[#263D26] px-3 py-2 text-sm text-[#8fa06a]" />
      </label>
      <label class="block">
        <span class="text-xs font-medium">Nomor Poin</span>
        <input v-model.number="formSkuEdit.nomor_poin" type="number" min="1" max="999" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <p v-if="formSkuEdit.errors.nomor_poin" class="mt-1 text-xs text-[#ef4419]">{{ formSkuEdit.errors.nomor_poin }}</p>
      </label>
      <label class="block">
        <span class="text-xs font-medium">Deskripsi Poin</span>
        <textarea v-model="formSkuEdit.deskripsi_poin" required rows="4" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"></textarea>
        <p v-if="formSkuEdit.errors.deskripsi_poin" class="mt-1 text-xs text-[#ef4419]">{{ formSkuEdit.errors.deskripsi_poin }}</p>
      </label>
      <label class="flex items-center gap-2">
        <input v-model="formSkuEdit.is_active" type="checkbox" class="h-4 w-4 rounded border-[#6F9435] bg-[#335233] text-[#A7B92B]" />
        <span class="text-xs font-medium">Aktif</span>
      </label>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeSkuEdit" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
        <button type="submit" :disabled="formSkuEdit.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">{{ editingSkuPoint ? 'Simpan' : 'Tambah' }}</button>
      </div>
    </form>
  </Modal>

  <Modal v-if="tkkEditOpen" :title="editingTkkPoint ? `Edit Poin ${editingTkkPoint.nama}` : 'Tambah Poin TKK'" @close="closeTkkEdit">
    <form @submit.prevent="submitTkkEdit" class="grid gap-3">
      <label class="block">
        <span class="text-xs font-medium">Nama Poin TKK</span>
        <input v-model="formTkkEdit.nama" type="text" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <p v-if="formTkkEdit.errors.nama" class="mt-1 text-xs text-[#ef4419]">{{ formTkkEdit.errors.nama }}</p>
      </label>
      <label class="block">
        <span class="text-xs font-medium">Deskripsi</span>
        <textarea v-model="formTkkEdit.deskripsi" rows="4" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"></textarea>
        <p v-if="formTkkEdit.errors.deskripsi" class="mt-1 text-xs text-[#ef4419]">{{ formTkkEdit.errors.deskripsi }}</p>
      </label>
      <label class="flex items-center gap-2">
        <input v-model="formTkkEdit.is_active" type="checkbox" class="h-4 w-4 rounded border-[#6F9435] bg-[#335233] text-[#A7B92B]" />
        <span class="text-xs font-medium">Aktif</span>
      </label>
      <div class="flex justify-end gap-2">
        <button type="button" @click="closeTkkEdit" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
        <button type="submit" :disabled="formTkkEdit.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">{{ editingTkkPoint ? 'Simpan' : 'Tambah' }}</button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import ActivityCalendar from '@/Components/ActivityCalendar.vue';
import UpcomingActivities from '@/Components/UpcomingActivities.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
  member: Object,
  stats: Object,
  pendingSku: Array,
  announcements: Array,
  upcomingSessions: Array,
  attendedSessionIds: Array,
  tkuData: Array,
  tkkPoints: Array,
  skuPointsByLevel: Object,
});
const page = usePage();
const role = computed(() => page.props?.auth?.user?.role);
const isMember = computed(() => role.value === 'Anggota');
const isAdminOrPembina = computed(() => role.value === 'Pembina' || role.value === 'Admin');
const labels = { members: 'Total anggota', attendance: 'Rekap kehadiran', sku: 'Pengajuan SKU', finance: 'Transaksi kas' };
const icons = {
  members: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0z" /></svg>',
  attendance: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M9 9.563a3 3 0 1 0 5.138-2.121 3 3 0 0 0-5.138 2.121zM15 12l3.6-3.6m0 0L16.8 6.6m1.8 1.8h-3.6" /></svg>',
  sku: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>',
  finance: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125h20.25M12 6V5.25m0 13.5h.008v.008H12V18.75zm0-4.5h.008v.008H12v-.008zm0-4.5h.008v.008H12v-.008z" /></svg>',
};

const skuEditOpen = ref(false);
const editingSkuPoint = ref(null);
const formSkuEdit = useForm({ tingkatan: '', nomor_poin: null, deskripsi_poin: '', is_active: true });

const tkkEditOpen = ref(false);
const editingTkkPoint = ref(null);
const formTkkEdit = useForm({ nama: '', deskripsi: '', is_active: true });

const hasSkuPoints = computed(() => {
  return Object.values(props.skuPointsByLevel || {}).some((level) => level.length > 0);
});

function openSkuEdit(point = null) {
  editingSkuPoint.value = point;
  if (point) {
    formSkuEdit.tingkatan = point.tingkatan;
    formSkuEdit.nomor_poin = point.nomor_poin;
    formSkuEdit.deskripsi_poin = point.deskripsi_poin;
    formSkuEdit.is_active = point.is_active;
  } else {
    formSkuEdit.tingkatan = '';
    formSkuEdit.nomor_poin = null;
    formSkuEdit.deskripsi_poin = '';
    formSkuEdit.is_active = true;
  }
  skuEditOpen.value = true;
}

function closeSkuEdit() {
  skuEditOpen.value = false;
  editingSkuPoint.value = null;
  formSkuEdit.reset();
  formSkuEdit.clearErrors();
}

function submitSkuEdit() {
  if (editingSkuPoint.value) {
    formSkuEdit.patch(`/sku/points/${editingSkuPoint.value.id}`, {
      preserveScroll: true,
      onSuccess: () => closeSkuEdit(),
    });
  } else {
    formSkuEdit.post('/sku/points', {
      preserveScroll: true,
      onSuccess: () => closeSkuEdit(),
    });
  }
}

function openTkkEdit(point = null) {
  editingTkkPoint.value = point;
  if (point) {
    formTkkEdit.nama = point.nama;
    formTkkEdit.deskripsi = point.deskripsi || '';
    formTkkEdit.is_active = point.is_active;
  } else {
    formTkkEdit.nama = '';
    formTkkEdit.deskripsi = '';
    formTkkEdit.is_active = true;
  }
  tkkEditOpen.value = true;
}

function closeTkkEdit() {
  tkkEditOpen.value = false;
  editingTkkPoint.value = null;
  formTkkEdit.reset();
  formTkkEdit.clearErrors();
}

function submitTkkEdit() {
  if (editingTkkPoint.value) {
    formTkkEdit.patch(`/tkk/points/${editingTkkPoint.value.id}`, {
      preserveScroll: true,
      onSuccess: () => closeTkkEdit(),
    });
  } else {
    formTkkEdit.post('/tkk/points', {
      preserveScroll: true,
      onSuccess: () => closeTkkEdit(),
    });
  }
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
}
</script>
