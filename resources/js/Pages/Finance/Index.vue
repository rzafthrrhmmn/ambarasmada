<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Keuangan Ambalan</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ isAnggota ? 'Iuran Saya' : 'Kas dan Tabungan' }}</h1>
        <p v-if="isAnggota" class="mt-1 text-sm text-[#8fa06a]">Riwayat pembayaran iuran dan ajukan pembayaran baru.</p>
        <p v-else class="mt-1 text-sm text-[#8fa06a]">Catat transaksi, pantau neraca, dan jaga transparansi kas ambalan.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button v-if="isAnggota" @click="showPay = true" type="button" class="inline-flex items-center rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Bayar iuran</button>
        <button v-if="! isAnggota && canManage" @click="showCreate = true" type="button" class="inline-flex items-center rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Transaksi baru</button>
      </div>
    </div>

    <section class="mb-6 grid gap-4 sm:grid-cols-3">
      <div class="group rounded-2xl border border-[#6F9435] bg-[#335233] p-5 transition hover:border-[#A7B92B]/50">
        <div class="mb-2 flex items-center gap-2">
          <span class="rounded-lg bg-[#263D26] p-2 text-[#A7B92B]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 0V4m0 8v8" /></svg>
          </span>
          <p class="text-xs text-[#8fa06a]">
            <span v-if="isAnggota">Total dibayarkan</span>
            <span v-else>Saldo kas</span>
          </p>
        </div>
        <p class="text-2xl font-bold text-[#f0ead8]">{{ formatRupiah(isAnggota ? memberPayments : balance) }}</p>
      </div>

      <div v-if="! isAnggota" class="group rounded-2xl border border-[#A7B92A]/50 bg-[#A7B92A]/20 p-5 transition hover:bg-[#A7B92A]/30">
        <div class="mb-2 flex items-center gap-2">
          <span class="rounded-lg bg-[#263D26] p-2 text-[#A7B92A]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v4m0 12v-4m6-6H6" /></svg>
          </span>
          <p class="text-xs font-medium text-[#A7B92A]">Pemasukan</p>
        </div>
        <p class="text-2xl font-bold text-[#A7B92A]">{{ formatRupiah(income) }}</p>
      </div>

      <div v-if="! isAnggota" class="group rounded-2xl border border-[#ef4419]/50 bg-[#ef4419]/20 p-5 transition hover:bg-[#ef4419]/30">
        <div class="mb-2 flex items-center gap-2">
          <span class="rounded-lg bg-[#263D26] p-2 text-[#ef4419]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v4m0 12v-4m-6-6h12" /></svg>
          </span>
          <p class="text-xs font-medium text-[#ef4419]">Pengeluaran</p>
        </div>
        <p class="text-2xl font-bold text-[#ef4419]">{{ formatRupiah(expense) }}</p>
      </div>

      <div v-if="isAnggota" class="group rounded-2xl border border-[#6F9435] bg-[#335233] p-5 transition hover:border-[#A7B92B]/50">
        <div class="mb-2 flex items-center gap-2">
          <span class="rounded-lg bg-[#263D26] p-2 text-[#A7B92B]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m0 0v8m0-8V4" /></svg>
          </span>
          <p class="text-xs text-[#8fa06a]">Riwayat pembayaran</p>
        </div>
        <p class="text-xl font-bold text-[#f0ead8]">{{ paidCount }} transaksi</p>
      </div>
    </section>

    <section v-if="! isAnggota" class="mb-6 rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="font-semibold text-[#f0ead8]">Cash Flow 6 Bulan Terakhir</h2>
        <div class="flex gap-3 text-xs">
          <div class="flex items-center gap-1.5"><span class="block h-3 w-3 rounded bg-[#A7B92B]"></span>Pemasukan</div>
          <div class="flex items-center gap-1.5"><span class="block h-3 w-3 rounded bg-[#ef4419]"></span>Pengeluaran</div>
        </div>
      </div>
      <div class="relative h-64 w-full">
        <ChartCard type="bar" :data="cashFlowData" :options="cashFlowOptions" />
      </div>
    </section>

    <section class="rounded-2xl border border-[#6F9435] bg-[#335233] shadow-sm border-[#6F9435]">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-[#6F9435]/30">
          <thead class="bg-[#263D26]">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-[#8fa06a]">Tanggal</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-[#8fa06a]">Keterangan</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-[#8fa06a]">Nominal</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-[#8fa06a]">Status</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-[#8fa06a]">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#6F9435]/30">
            <tr v-for="item in transactions.data" :key="item.id" class="transition hover:bg-[#335233]/40">
              <td class="whitespace-nowrap px-4 py-3 text-sm">{{ formatDate(item.tgl_transaksi) }}</td>
              <td class="px-4 py-3 text-sm">
                {{ item.keterangan }}
                <br />
                <span class="text-xs text-[#8fa06a]">{{ item.category?.nama || 'Umum' }}</span>
              </td>
              <td class="px-4 py-3 text-sm font-semibold" :class="item.jenis_transaksi === 'Masuk' ? 'text-[#A7B92B]' : 'text-[#ef4419]'">{{ item.jenis_transaksi }} {{ formatRupiah(item.nominal) }}</td>
              <td class="px-4 py-3 text-sm">
                <span class="rounded-full px-2.5 py-1 text-xs" :class="statusClass(item.status)">{{ item.status }}</span>
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                <button v-if="item.status === 'Draft' && canManage" @click="router.post(`/finance/${item.id}/post`)" class="mr-2 text-[#EDD330] hover:underline">Post</button>
                <button v-if="item.status === 'Posted' && canManage" @click="router.post(`/finance/${item.id}/reverse`)" class="mr-2 text-[#EDD330] hover:underline">Balik</button>
                <button v-if="item.status === 'Draft' && canManage" @click="router.delete(`/finance/${item.id}`)" class="text-[#ef4419] hover:underline">Hapus</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination v-if="transactions.links?.length > 1" :links="transactions.links" class="p-3" />
      <p v-if="! transactions.data?.length" class="px-4 py-6 text-center text-xs text-[#8fa06a]">Belum ada transaksi.</p>
    </section>

    <Modal v-if="showCreate && canManage" title="Tambah transaksi kas" @close="showCreate = false">
      <form @submit.prevent="form.post('/finance', { onSuccess: () => showCreate = false })" class="grid gap-4 sm:grid-cols-2">
        <label class="block sm:col-span-2">
          <span class="text-xs font-medium">Jenis transaksi</span>
          <select v-model="form.jenis_transaksi" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]">
            <option>Masuk</option>
            <option>Keluar</option>
          </select>
        </label>
        <label class="block">
          <span class="text-xs font-medium">Nominal</span>
          <input v-model="form.nominal" type="number" min="1" step="0.01" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]" />
        </label>
        <label class="block">
          <span class="text-xs font-medium">Tanggal</span>
          <input v-model="form.tgl_transaksi" type="date" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]" />
        </label>
        <label class="block sm:col-span-2">
          <span class="text-xs font-medium">Keterangan</span>
          <textarea v-model="form.keterangan" required rows="3" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]"></textarea>
        </label>
        <div class="flex justify-end gap-2 sm:col-span-2">
          <button type="button" @click="showCreate = false" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showPay && isAnggota" title="Bayar iuran" @close="showPay = false">
      <form @submit.prevent="payForm.post('/finance', { onSuccess: () => showPay = false })" class="grid gap-4">
        <div class="rounded-xl border border-[#A7B92A]/40 bg-[#263D26] p-4">
          <p class="text-xs font-semibold text-[#A7B92B]">Pembayaran iuran wajib</p>
          <p class="mt-1 text-xs text-[#8fa06a]">Nominal akan tercatat otomatis sebagai pemasukan kas.</p>
        </div>
        <label class="block">
          <span class="text-xs font-medium">Jumlah pembayaran</span>
          <input v-model="payForm.nominal" type="number" min="1000" step="1000" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]" />
          <p v-if="payForm.errors.nominal" class="mt-1 text-xs text-[#ef4419]">{{ payForm.errors.nominal }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium">Keterangan (opsional)</span>
          <textarea v-model="payForm.keterangan" rows="2" placeholder="cth: Pembayaran iuran September" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]"></textarea>
        </label>
        <div class="flex justify-end gap-2">
          <button type="button" @click="showPay = false" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="payForm.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Bayar</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import ChartCard from '@/Components/ChartCard.vue';

const props = defineProps({
  transactions: Object,
  balance: Number,
  income: Number,
  expense: Number,
  canManage: { type: Boolean, default: false },
  isJuruUang: { type: Boolean, default: false },
  memberId: Number,
  chartLabels: Array,
  chartIncome: Array,
  chartExpense: Array,
});

const page = usePage();
const isAnggota = computed(() => page.props.auth?.user?.role === 'Anggota');

const memberPayments = computed(() =>
  props.transactions.data
    ? props.transactions.data
        .filter((t) => t.jenis_transaksi === 'Masuk' && t.status === 'Posted')
        .reduce((sum, t) => sum + Number(t.nominal), 0)
    : 0
);

const paidCount = computed(() =>
  props.transactions.data?.filter((t) => t.status === 'Posted' && t.jenis_transaksi === 'Masuk').length ?? 0
);

const cashFlowData = computed(() => ({
  labels: props.chartLabels,
  datasets: [
    {
      label: 'Pemasukan',
      data: props.chartIncome,
      backgroundColor: 'rgba(167, 185, 42, 0.75)',
      borderColor: 'rgba(167, 185, 42, 1)',
      borderWidth: 1,
      borderRadius: 4,
    },
    {
      label: 'Pengeluaran',
      data: props.chartExpense,
      backgroundColor: 'rgba(239, 68, 23, 0.75)',
      borderColor: 'rgba(239, 68, 23, 1)',
      borderWidth: 1,
      borderRadius: 4,
    },
  ],
}));

const cashFlowOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top' },
    tooltip: {
      callbacks: {
        label: (ctx) => `${ctx.dataset.label}: ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(ctx.parsed.y)}`,
      },
    },
  },
  scales: {
    x: { stacked: true },
    y: { stacked: true, ticks: { callback: (v) => (v === 0 ? '0' : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v)) } },
  },
};

const showCreate = ref(false);
const showPay = ref(false);
const form = useForm({ jenis_transaksi: 'Masuk', nominal: '', keterangan: '', tgl_transaksi: new Date().toISOString().slice(0, 10) });
const payForm = useForm({ nominal: '', keterangan: '' });

function formatRupiah(value) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 }).format(Number(value || 0));
}
function formatDate(value) {
  return new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}
function statusClass(status) {
  return status === 'Posted' ? 'bg-[#A7B92B]/20 text-[#A7B92B]' : status === 'Reversed' ? 'bg-[#335233] text-[#d4dc9a]' : 'bg-[#EDD330]/20 text-[#EDD330]';
}
</script>

