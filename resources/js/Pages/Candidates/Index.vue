<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Calon Anggota</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Pendaftar Baru</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola calon anggota yang ingin bergabung.</p>
      </div>
      <button @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#6F9435]">+ Tambah Calon</button>
    </div>

    <div v-if="showCreate" class="mb-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
      <p class="text-sm font-semibold text-[#EDD330]">Data Calon Anggota</p>
      <form @submit.prevent="createCandidate" class="mt-3 grid gap-3 sm:grid-cols-3">
        <input v-model="form.nama_lengkap" placeholder="Nama Lengkap" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="form.tempat_lahir" placeholder="Tempat Lahir" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="form.tanggal_lahir" type="date" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <select v-model="form.jenis_kelamin" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
          <option value="">Pilih</option>
          <option value="Laki-laki">Laki-laki</option>
          <option value="Perempuan">Perempuan</option>
        </select>
        <input v-model="form.kelas" placeholder="Kelas" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="form.no_hp" placeholder="Nomor HP" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <textarea v-model="form.riwayat_pramuka" placeholder="Riwayat Pramuka" rows="2" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330] resize-none sm:col-span-3"></textarea>
        <div class="sm:col-span-3 flex gap-2">
          <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50">Simpan</button>
          <button type="button" @click="showCreate = false; form.reset()" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/30">Batal</button>
        </div>
      </form>
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2">
      <select v-model="filters.status" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
        <option value="">Semua status</option>
        <option value="Pending">Pending</option>
        <option value="Diterima">Diterima</option>
        <option value="Ditolak">Ditolak</option>
      </select>
      <input v-model="filters.nama_lengkap" placeholder="Cari nama..." class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
    </div>

    <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-sm">
      <h2 class="mb-4 font-semibold text-[#f0ead8]">Daftar Calon Anggota</h2>
      <div v-if="candidates.data.length" class="space-y-3">
        <div v-for="c in candidates.data" :key="c.id" class="rounded-xl border border-[#6F9435] p-4">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-[#f0ead8]">{{ c.nama_lengkap }}</p>
              <p class="mt-1 text-xs text-[#8fa06a]">{{ c.tempat_lahir }} • {{ c.tanggal_lahir }} • {{ c.jenis_kelamin }} • {{ c.kelas }}</p>
              <p v-if="c.no_hp" class="text-xs text-[#8fa06a]">📱 {{ c.no_hp }}</p>
              <p v-if="c.riwayat_pramuka" class="mt-1 text-xs text-[#8fa06a]">📋 {{ c.riwayat_pramuka }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <span :class="{
                'bg-[#EDD330]/20 text-[#EDD330]': c.status === 'Pending',
                'bg-[#A7B92A]/20 text-[#A7B92A]': c.status === 'Diterima',
                'bg-[#ef4419]/20 text-[#ef4419]': c.status === 'Ditolak',
              }" class="rounded-full px-2.5 py-1 text-xs font-bold">{{ c.status }}</span>
              <select v-model="c.status" @change="updateStatus(c)" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-2 py-1 text-xs text-[#f0ead8] outline-none focus:border-[#EDD330]">
                <option value="Pending">Pending</option>
                <option value="Diterima">Diterima</option>
                <option value="Ditolak">Ditolak</option>
              </select>
              <button @click="deleteCandidate(c)" class="rounded-lg border border-[#ef4419]/50 px-3 py-1.5 text-xs font-semibold text-[#ef4419] hover:bg-[#ef4419]/10">Hapus</button>
            </div>
          </div>
        </div>
        <Pagination :links="candidates.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </div>
      <p v-else class="empty-state empty-state-text">Belum ada calon anggota.</p>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({ candidates: Object, filters: Object });

const showCreate = ref(false);
const form = useForm({ nama_lengkap: '', tempat_lahir: '', tanggal_lahir: '', jenis_kelamin: '', kelas: '', no_hp: '', riwayat_pramuka: '', catatan: '', status: 'Pending' });

function createCandidate() {
  form.post('/candidates', {
    onSuccess: () => { form.reset(); showCreate.value = false; },
  });
}

function updateStatus(c) {
  form.status = c.status;
  form.patch(`/candidates/${c.id}`, {
    onSuccess: () => { form.reset(); },
    onError: () => { c.status = 'Pending'; },
  });
}

function deleteCandidate(c) {
  if (!confirm('Hapus calon ini?')) return;
  router.delete(`/candidates/${c.id}`);
}
</script>
