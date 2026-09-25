<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Keuangan</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Kategori Transaksi</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">
          Kelola kategori transaksi keuangan (Iuran, Pengeluaran, dsb).
        </p>
      </div>
      <button
        @click="showForm = true; editForm = null"
        class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110"
      >
        + Tambah Kategori
      </button>
    </div>

    <div v-if="showForm" class="mb-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
      <p class="text-sm font-semibold text-[#EDD330]">{{ editForm ? 'Edit' : 'Tambah' }} Kategori</p>
      <form @submit.prevent="saveCategory" class="mt-3 grid gap-3 sm:grid-cols-4">
        <input
          v-model="form.nama"
          placeholder="Nama kategori"
          required
          class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        />
        <select
          v-model="form.jenis"
          required
          class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        >
          <option value="Masuk">Masuk</option>
          <option value="Keluar">Keluar</option>
        </select>
        <select
          v-model="form.ambalan_id"
          class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"
        >
          <option value="">Semua Ambalan</option>
          <option v-for="a in ambalans" :key="a.id" :value="a.id">{{ a.nama }}</option>
        </select>
        <div class="flex items-end gap-2">
          <label class="flex items-center gap-2 text-sm text-[#d4dc9a]">
            <input type="checkbox" v-model="form.is_active" class="h-4 w-4" />
            Aktif
          </label>
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
        <h2 class="font-semibold text-[#f0ead8]">Daftar Kategori</h2>
        <span class="rounded-full bg-[#6F9435]/20 px-2.5 py-0.5 text-xs font-semibold text-[#EDD330]">{{ categories.total }} data</span>
      </div>

      <div v-if="categories.data.length" class="space-y-2">
        <div
          v-for="category in categories.data"
          :key="category.id"
          class="flex flex-col gap-2 rounded-xl border border-[#6F9435] p-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="min-w-0 flex-1">
            <p class="flex items-center gap-2 text-sm font-semibold text-[#f0ead8]">
              {{ category.nama }}
              <span
                class="rounded px-2 py-0.5 text-xs font-bold"
                :class="category.is_active ? 'bg-[#6F9435]/20 text-[#A7B92B]' : 'bg-[#ef4419]/20 text-[#ef4419]'"
              >{{ category.is_active ? 'Aktif' : 'Non-Aktif' }}</span>
              <span class="rounded bg-[#263D26] px-2 py-0.5 text-xs font-bold text-[#EDD330]">{{ category.jenis }}</span>
            </p>
            <p v-if="category.ambalan" class="mt-1 text-xs text-[#8fa06a]">{{ category.ambalan.nama }}</p>
          </div>
          <div class="flex items-center gap-1">
            <button
              @click="editCategory(category)"
              class="rounded-lg border border-[#6F9435] px-2.5 py-1.5 text-xs font-semibold text-[#d4dc9a] hover:bg-[#6F9435]/20"
            >
              Edit
            </button>
            <button
              @click="toggleCategory(category)"
              class="rounded-lg border border-[#6F9435] px-2.5 py-1.5 text-xs font-semibold text-[#d4dc9a] hover:bg-[#6F9435]/20"
              :title="category.is_active ? 'Nonaktifkan' : 'Aktifkan'"
            >
              {{ category.is_active ? 'Non-Aktif' : 'Aktif' }}
            </button>
            <button
              @click="deleteCategory(category)"
              class="rounded-lg border border-[#ef4419]/50 px-2.5 py-1.5 text-xs font-semibold text-[#ef4419] hover:bg-[#ef4419]/10"
            >
              Hapus
            </button>
          </div>
        </div>

        <Pagination :links="categories.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </div>

      <p v-else class="empty-state empty-state-text">Belum ada kategori transaksi.</p>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  categories: Object,
  ambalans: Array,
});

const showForm = ref(false);
const editForm = ref(null);

const form = reactive({
  nama: '',
  jenis: 'Masuk',
  ambalan_id: '',
  is_active: true,
  processing: false,
});

function editCategory(category) {
  editForm.value = category;
  form.nama = category.nama;
  form.jenis = category.jenis;
  form.ambalan_id = category.ambalan_id || '';
  form.is_active = category.is_active;
  showForm.value = true;
}

function saveCategory() {
  form.processing = true;
  if (editForm.value) {
    router.patch(`/finance/categories/${editForm.value.id}`, form, {
      onSuccess: () => { showForm.value = false; editForm.value = null; },
      onError: () => { form.processing = false; },
    });
  } else {
    router.post('/finance/categories', form, {
      onSuccess: () => { showForm.value = false; form.nama = ''; },
      onError: () => { form.processing = false; },
    });
  }
}

function toggleCategory(category) {
  router.patch(`/finance/categories/${category.id}/toggle`);
}

function deleteCategory(category) {
  if (!confirm('Hapus kategori ini?')) return;
  router.delete(`/finance/categories/${category.id}`);
}
</script>
