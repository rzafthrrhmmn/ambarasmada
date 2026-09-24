<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Pelatihan</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Modul Pelatihan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola modul pelatihan dan progres anggota.</p>
      </div>
      <button @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#6F9435]">+ Tambah Pelatihan</button>
    </div>

    <div v-if="showCreate" class="mb-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
      <p class="text-sm font-semibold text-[#EDD330]">Modul Pelatihan Baru</p>
      <form @submit.prevent="createTraining" class="mt-3 grid gap-3 sm:grid-cols-3">
        <input v-model="form.judul" placeholder="Judul pelatihan" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <select v-model="form.kategori" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
          <option value="">Pilih kategori</option>
          <option value="Keterampilan">Keterampilan</option>
          <option value="Kepramukaan">Kepramukaan</option>
          <option value="Kebangsaan">Kebangsaan</option>
          <option value="Pramuka Upas">Pramuka Upas</option>
        </select>
        <input v-model="form.tanggal" type="date" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="form.konten" placeholder="Konten (opsional)" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="form.video_url" placeholder="URL Video (opsional)" type="url" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <select v-model="form.status" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
          <option value="Draft">Draft</option>
          <option value="Aktif">Aktif</option>
          <option value="Selesai">Selesai</option>
        </select>
        <div class="sm:col-span-3 flex gap-2">
          <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50">Simpan</button>
          <button type="button" @click="showCreate = false; form.reset()" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/30">Batal</button>
        </div>
      </form>
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2">
      <input v-model="filters.kategori" placeholder="Cari kategori..." class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
      <select v-model="filters.status" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
        <option value="">Semua status</option>
        <option value="Draft">Draft</option>
        <option value="Aktif">Aktif</option>
        <option value="Selesai">Selesai</option>
      </select>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
      <section class="xl:col-span-2 rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-sm">
        <h2 class="mb-4 font-semibold text-[#f0ead8]">Daftar Pelatihan</h2>
        <SkeletonLoader v-if="!trainings || !trainings.data" variant="list" class="h-auto" />
        <div v-else-if="trainings.data.length" class="space-y-3">
          <div v-for="t in trainings.data" :key="t.id" class="rounded-xl border border-[#6F9435] p-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-[#f0ead8]">{{ t.judul }}</p>
                <p class="mt-1 text-xs text-[#8fa06a]">{{ t.kategori }} • {{ t.status }} • {{ t.createdBy?.name || '-' }}</p>
                <p v-if="t.tanggal" class="text-xs text-[#8fa06a]">{{ t.tanggal }}</p>
              </div>
              <div class="flex gap-2">
                <button @click="editTraining(t)" class="rounded-lg border border-[#EDD330] px-3 py-1.5 text-xs font-semibold text-[#EDD330] hover:bg-[#EDD330]/10">Edit</button>
                <button @click="deleteTraining(t)" class="rounded-lg border border-[#ef4419]/50 px-3 py-1.5 text-xs font-semibold text-[#ef4419] hover:bg-[#ef4419]/10">Hapus</button>
              </div>
            </div>
          </div>
          <Pagination :links="trainings.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
        </div>
        <p v-else class="empty-state empty-state-text">Belum ada modul pelatihan.</p>
      </section>

      <aside class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435]">
        <h2 class="mb-3 font-semibold text-[#f0ead8]">Ringkasan</h2>
        <div class="space-y-2">
          <div class="flex items-center justify-between rounded-lg bg-[#263D26] p-3">
            <span class="text-xs font-medium text-[#EDD330]">Total</span>
            <strong class="text-lg font-bold text-[#EDD330]">{{ trainings.total }}</strong>
          </div>
          <div class="flex items-center justify-between rounded-lg bg-[#263D26] p-3">
            <span class="text-xs font-medium text-[#A7B92A]">Draft</span>
            <strong class="text-lg font-bold text-[#A7B92A]">{{ trainings.data.filter(t => t.status === 'Draft').length }}</strong>
          </div>
          <div class="flex items-center justify-between rounded-lg bg-[#263D26] p-3">
            <span class="text-xs font-medium text-[#6F9435]">Aktif</span>
            <strong class="text-lg font-bold text-[#6F9435]">{{ trainings.data.filter(t => t.status === 'Aktif').length }}</strong>
          </div>
          <div class="flex items-center justify-between rounded-lg bg-[#263D26] p-3">
            <span class="text-xs font-medium text-[#EDD330]">Selesai</span>
            <strong class="text-lg font-bold text-[#EDD330]">{{ trainings.data.filter(t => t.status === 'Selesai').length }}</strong>
          </div>
        </div>
      </aside>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';

defineProps({ trainings: Object, filters: Object, user: Object });

const showCreate = ref(false);
const form = useForm({ judul: '', kategori: '', konten: '', video_url: '', file_path: '', tanggal: '', status: 'Draft' });

function createTraining() {
  form.post('/trainings', {
    onSuccess: () => { form.reset(); showCreate.value = false; },
  });
}

function editTraining(t) {
  form.judul = t.judul;
  form.kategori = t.kategori;
  form.konten = t.konten || '';
  form.video_url = t.video_url || '';
  form.tanggal = t.tanggal || '';
  form.status = t.status;
  showCreate.value = true;
}

function deleteTraining(t) {
  if (!confirm('Hapus pelatihan ini?')) return;
  router.delete(`/trainings/${t.id}`);
}
</script>




