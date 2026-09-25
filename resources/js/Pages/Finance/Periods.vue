<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Keuangan</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Periode Keuangan</h1>
        <p class="mt-1 text-sm text-[#8fa06a">
          Kelola periode keuangan dan tutup periode ketika selesai.
        </p>
      </div>
      <button
        v-if="!showForm"
        @click="showForm = true; editForm = null"
        class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110"
      >
        + Tambah Periode
      </button>
    </div>

    <div v-if="showForm" class="mb-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
      <p class="text-sm font-semibold text-[#EDD330]">{{ editForm ? 'Edit' : 'Tambah' }} Periode</p>
      <form @submit.prevent="savePeriod" class="mt-3 grid gap-3 sm:grid-cols-4">
        <input
          v-model="form.nama"
          placeholder="Nama periode (contoh: Agustus 2026)"
          required
          class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        />
        <input
          v-model="form.starts_at"
          type="date"
          required
          class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        />
        <input
          v-model="form.ends_at"
          type="date"
          required
          class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        />
        <div class="flex items-end gap-2">
          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-lg bg-gradient-to-r from-[#6F9435] to-[#A7B92A] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50"
          >
            Simpan
          </button>
          <button
            type="button"
            @click="showForm = false"
            class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/20"
          >
            Batal
          </button>
        </div>
      </form>
    </div>

    <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="font-semibold text-[#f0ead8]">Daftar Periode</h2>
        <span class="rounded-full bg-[#6F9435]/20 px-2.5 py-0.5 text-xs font-semibold text-[#EDD330]">{{ periods.total }} data</span>
      </div>

      <div v-if="periods.data.length" class="space-y-2">
        <div
          v-for="period in periods.data"
          :key="period.id"
          class="flex flex-col gap-2 rounded-xl border border-[#6F9435] p-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="min-w-0 flex-1">
            <p class="flex items-center gap-2 text-sm font-semibold text-[#f0ead8]">
              {{ period.nama }}
              <span
                class="rounded px-2 py-0.5 text-xs font-bold"
                :class="period.is_closed ? 'bg-[#ef4419]/20 text-[#ef4419]' : 'bg-[#6F9435]/20 text-[#A7B92B]'"
              >{{ period.is_closed ? 'Ditutup' : 'Buka' }}</span>
            </p>
            <p class="mt-1 text-xs text-[#8fa06a]">
              {{ formatDate(period.starts_at) }} → {{ formatDate(period.ends_at) }}
            </p>
          </div>
          <div class="flex items-center gap-1">
            <button
              v-if="!period.is_closed"
              @click="closePeriod(period)"
              class="rounded-lg border border-[#6F9435] px-2.5 py-1.5 text-xs font-semibold text-[#d4dc9a] hover:bg-[#6F9435]/20"
              title="Tutup periode ini"
            >
              Tutup
            </button>
            <button
              v-if="!period.is_closed"
              @click="editPeriod(period)"
              class="rounded-lg border border-[#6F9435] px-2.5 py-1.5 text-xs font-semibold text-[#d4dc9a] hover:bg-[#6F9435]/20"
            >
              Edit
            </button>
            <button
              @click="deletePeriod(period)"
              class="rounded-lg border border-[#ef4419]/50 px-2.5 py-1.5 text-xs font-semibold text-[#ef4419] hover:bg-[#ef4419]/10"
            >
              Hapus
            </button>
          </div>
        </div>

        <Pagination :links="periods.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </div>

      <p v-else class="empty-state empty-state-text">Belum ada periode keuangan.</p>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  periods: Object,
  ambalans: Array,
});

const showForm = ref(false);
const editForm = ref(null);

const form = reactive({
  nama: '',
  starts_at: '',
  ends_at: '',
  processing: false,
});

function editPeriod(period) {
  if (period.is_closed) return;
  editForm.value = period;
  form.nama = period.nama;
  form.starts_at = period.starts_at.split('T')[0];
  form.ends_at = period.ends_at.split('T')[0];
  showForm.value = true;
}

function savePeriod() {
  form.processing = true;
  if (editForm.value) {
    router.patch(`/finance/periods/${editForm.value.id}`, form, {
      onSuccess: () => { showForm.value = false; editForm.value = null; },
      onError: () => { form.processing = false; },
    });
  } else {
    router.post('/finance/periods', form, {
      onSuccess: () => { showForm.value = false; form.nama = ''; form.starts_at = ''; form.ends_at = ''; },
      onError: () => { form.processing = false; },
    });
  }
}

function closePeriod(period) {
  if (!confirm(`Tutup periode "${period.nama}"? Transaksi tidak dapat ditambahkan lagi setelah menutup.`)) return;
  router.patch(`/finance/periods/${period.id}/close`);
}

function deletePeriod(period) {
  if (!confirm('Hapus periode ini?')) return;
  router.delete(`/finance/periods/${period.id}`);
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  return dateStr.split('T')[0];
}
</script>
