<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Kesehatan</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Rekam Medis Anggota</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola data kesehatan anggota ambalan.</p>
      </div>
    </div>

    <div class="mb-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
      <p class="text-sm font-semibold text-[#EDD330]">Tambah Rekam Medis</p>
      <form @submit.prevent="addRecord" class="mt-3 grid gap-3 sm:grid-cols-3">
        <select v-model="form.member_id" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
          <option value="">Pilih anggota</option>
          <option v-for="m in allMembers" :key="m.id" :value="m.id">{{ m.nama_lengkap }}</option>
        </select>
        <input v-model="form.riwayat_penyakit" placeholder="Riwayat penyakit" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="form.alergi" placeholder="Alergi" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="form.darah" placeholder="Golongan Darah" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="form.tinggi_badan" placeholder="Tinggi Badan (cm)" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <input v-model="form.berat_badan" placeholder="Berat Badan (kg)" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        <div class="sm:col-span-3">
          <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50">Simpan Rekam Medis</button>
        </div>
      </form>
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2">
      <input v-model="filters.member_id" placeholder="Cari anggota..." class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
    </div>

    <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-sm">
      <h2 class="mb-4 font-semibold text-[#f0ead8]">Daftar Rekam Medis</h2>
      <div v-if="records.data.length" class="space-y-3">
        <div v-for="r in records.data" :key="r.id" class="rounded-xl border border-[#6F9435] p-4">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-[#f0ead8]">{{ r.member?.nama_lengkap || 'Tidak diketahui' }}</p>
              <div class="mt-1 grid gap-1 text-xs text-[#8fa06a]">
                <p v-if="r.riwayat_penyakit">🦠 Riwayat: {{ r.riwayat_penyakit }}</p>
                <p v-if="r.alergi">⚠️ Alergi: {{ r.alergi }}</p>
                <p v-if="r.darah">🩸 Darah: {{ r.darah }}</p>
                <p v-if="r.tinggi_badan || r.berat_badan">📏 TB: {{ r.tinggi_badan }}cm / BB: {{ r.berat_badan }}kg</p>
                <p v-if="r.catatan_tambahan">📝 {{ r.catatan_tambahan }}</p>
              </div>
            </div>
            <div class="flex gap-2">
              <button @click="editRecord(r)" class="rounded-lg border border-[#EDD330] px-3 py-1.5 text-xs font-semibold text-[#EDD330] hover:bg-[#EDD330]/10">Edit</button>
              <button @click="deleteRecord(r)" class="rounded-lg border border-[#ef4419]/50 px-3 py-1.5 text-xs font-semibold text-[#ef4419] hover:bg-[#ef4419]/10">Hapus</button>
            </div>
          </div>
        </div>
        <Pagination :links="records.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </div>
      <p v-else class="empty-state empty-state-text">Belum ada rekam medis.</p>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({ records: Object, filters: Object, allMembers: Array });

const form = useForm({ member_id: '', riwayat_penyakit: '', alergi: '', darah: '', tinggi_badan: '', berat_badan: '', catatan_tambahan: '' });
const editMode = ref(false);
const editId = ref(null);

function addRecord() {
  form.post('/health/safety/records', {
    onSuccess: () => { form.reset(); },
  });
}

function editRecord(r) {
  form.member_id = r.member_id;
  form.riwayat_penyakit = r.riwayat_penyakit || '';
  form.alergi = r.alergi || '';
  form.darah = r.darah || '';
  form.tinggi_badan = r.tinggi_badan || '';
  form.berat_badan = r.berat_badan || '';
  form.catatan_tambahan = r.catatan_tambahan || '';
  editMode.value = true;
  editId.value = r.id;
}

function updateRecord() {
  form.patch(`/health/safety/records/${editId.value}`, {
    onSuccess: () => { form.reset(); editMode.value = false; editId.value = null; },
  });
}

function deleteRecord(r) {
  if (!confirm('Hapus rekam medis ini?')) return;
  router.delete(`/health/safety/records/${r.id}`);
}
</script>
