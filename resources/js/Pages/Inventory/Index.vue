<template>
  <AppLayout>
    <div class="page-head">
      <div>
        <p class="page-eyebrow">Inventaris Ambalan</p>
        <h1 class="page-title">Barang dan Aset</h1>
        <p class="page-subtitle">Pantau ketersediaan, kondisi, dan riwayat peminjaman barang.</p>
      </div>
      <button v-if="canManage" type="button" class="btn-primary" @click="openCreate">
        <AppIcon name="plus" :stroke="2.2" class="h-4 w-4" />
        Tambah barang
      </button>
    </div>

    <p v-if="!items.data.length" class="empty-state">
      <AppIcon name="inventory" class="empty-state-icon h-6 w-6" />
      <span class="empty-state-text">Belum ada barang di inventaris.</span>
      <button v-if="canManage" type="button" class="btn-ghost btn-sm mt-1" @click="openCreate">
        <AppIcon name="plus" :stroke="2.2" class="h-3.5 w-3.5" />
        Tambah barang pertama
      </button>
    </p>

    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article v-for="item in items.data" :key="item.id" class="card flex flex-col">
        <span class="card-glow" aria-hidden="true" />

        <div class="relative flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-wide text-[#A7B92B]">{{ item.kode_barang }}</p>
            <h2 class="mt-1 text-lg font-bold text-[#f0ead8]">{{ item.nama_barang }}</h2>
          </div>
          <span class="badge shrink-0" :class="statusBadge(item.status_pinjam)">
            <AppIcon :name="statusIcon(item.status_pinjam)" class="h-3 w-3" />
            {{ item.status_pinjam }}
          </span>
        </div>

        <dl class="relative mt-5 grid grid-cols-3 gap-2 text-center">
          <div v-for="stat in stats(item)" :key="stat.label" class="rounded-xl border border-[#6F9435]/25 bg-[#263D26] p-3">
            <dt class="text-[11px] text-[#8fa06a]">{{ stat.label }}</dt>
            <dd class="mt-1 text-xl font-bold text-[#f0ead8]">{{ stat.value }}</dd>
          </div>
        </dl>

        <div class="relative mt-4 flex flex-wrap gap-2">
          <Link :href="`/inventory/${item.id}/movements`" class="btn-ghost btn-sm">
            <AppIcon name="clipboard" class="h-3.5 w-3.5" />
            Riwayat
          </Link>
          <button v-if="canManage" type="button" class="btn-primary btn-sm" @click="openLoan(item)">
            <AppIcon name="members" class="h-3.5 w-3.5" />
            Pinjam
          </button>
          <button v-if="canManage" type="button" class="btn-ghost btn-sm" @click="openAdjust(item)">
            <AppIcon name="settings" class="h-3.5 w-3.5" />
            Sesuaikan stok
          </button>
        </div>
      </article>
    </div>

    <Modal v-if="showCreate && canManage" title="Tambah inventaris" @close="closeCreate">
      <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitItem">
        <label class="block">
          <span class="field-label">Kode barang</span>
          <input v-model="form.kode_barang" required class="field mt-1" />
        </label>
        <label class="block">
          <span class="field-label">Nama barang</span>
          <input v-model="form.nama_barang" required class="field mt-1" />
        </label>
        <label class="block">
          <span class="field-label">Jenis</span>
          <select v-model="form.jenis" class="field mt-1">
            <option>Aset</option>
            <option>Stok</option>
          </select>
        </label>
        <label class="block">
          <span class="field-label">Satuan</span>
          <input v-model="form.satuan" class="field mt-1" />
        </label>
        <label class="block">
          <span class="field-label">Jumlah</span>
          <input v-model="form.jumlah" type="number" min="0" required class="field mt-1" />
        </label>
        <button type="submit" :disabled="form.processing" class="btn-primary sm:col-span-2">Simpan barang</button>
      </form>
    </Modal>

    <Modal v-if="showLoan && selected && canManage" title="Catat peminjaman" @close="closeLoan">
      <form class="grid gap-3" @submit.prevent="submitLoan">
        <input v-model="loan.inventory_id" type="hidden" />
        <label class="block">
          <span class="field-label">Peminjam</span>
          <input v-model="loan.peminjam_nama" required class="field mt-1" />
        </label>
        <label class="block">
          <span class="field-label">Tanggal pinjam</span>
          <input v-model="loan.tgl_pinjam" type="date" required class="field mt-1" />
        </label>
        <button type="submit" :disabled="loan.processing" class="btn-primary">Simpan peminjaman</button>
      </form>
    </Modal>

    <Modal v-if="showAdjust && selected && canManage" title="Sesuaikan stok" @close="closeAdjust">
      <form class="grid gap-3" @submit.prevent="submitAdjust">
        <input v-model="adjust.inventory_id" type="hidden" />
        <label class="block">
          <span class="field-label">Selisih stok</span>
          <input v-model="adjust.jumlah" type="number" required class="field mt-1" />
        </label>
        <label class="block">
          <span class="field-label">Catatan</span>
          <textarea v-model="adjust.catatan" required rows="3" class="field mt-1 resize-y"></textarea>
        </label>
        <button type="submit" :disabled="adjust.processing" class="btn-primary">Simpan penyesuaian</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import Modal from '@/Components/Modal.vue';
import { useAccess } from '@/Composables/useAccess.js';

defineProps({ items: { type: Object, required: true } });

const { can } = useAccess();
const canManage = computed(() => can('inventory.manage'));

const today = () => new Date().toISOString().slice(0, 10);

const showCreate = ref(false);
const showLoan = ref(false);
const showAdjust = ref(false);
const selected = ref(null);

const form = useForm({ kode_barang: '', nama_barang: '', jenis: 'Aset', satuan: 'Unit', jumlah: 0 });
const loan = useForm({ inventory_id: '', peminjam_nama: '', tgl_pinjam: today() });
const adjust = useForm({ inventory_id: '', jumlah: 0, catatan: '' });

// Status dan triplet angka ditulis sebagai data supaya warna, ikon, dan urutan
// labelnya hanya ada di satu tempat.
const STATUS_STATES = {
  Tersedia: { badge: 'badge-approved', icon: 'checkCircle' },
  Dipinjam: { badge: 'badge-pending', icon: 'clock' },
};

const statusBadge = (status) => STATUS_STATES[status]?.badge ?? 'badge-neutral';
const statusIcon = (status) => STATUS_STATES[status]?.icon ?? 'dot';

function stats(item) {
  return [
    { label: 'Jumlah', value: item.jumlah },
    { label: 'Kondisi', value: item.kondisi },
    { label: 'Pinjam', value: item.loans_count },
  ];
}

function openCreate() {
  form.reset();
  showCreate.value = true;
}

function closeCreate() {
  showCreate.value = false;
  form.reset();
}

function submitItem() {
  form.post('/inventory', { onSuccess: closeCreate });
}

function openLoan(item) {
  selected.value = item;
  loan.reset();
  loan.inventory_id = String(item.id);
  loan.tgl_pinjam = today();
  showLoan.value = true;
}

function closeLoan() {
  showLoan.value = false;
  selected.value = null;
  loan.reset();
}

function submitLoan() {
  loan.post('/inventory-loans', { onSuccess: closeLoan });
}

function openAdjust(item) {
  selected.value = item;
  adjust.reset();
  adjust.inventory_id = String(item.id);
  showAdjust.value = true;
}

function closeAdjust() {
  showAdjust.value = false;
  selected.value = null;
  adjust.reset();
}

function submitAdjust() {
  adjust.post('/inventory-adjustments', { onSuccess: closeAdjust });
}
</script>