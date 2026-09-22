<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Penilaian</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Penilaian Anggota</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Nilai kehadiran, disiplin, keterampilan, dan kepemimpinan.</p>
      </div>
    </div>

    <div class="grid gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="(label, key) in labels" :key="key" class="rounded-xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
        <p class="text-[10px] font-medium text-[#8fa06a]">{{ label }}</p>
        <p class="text-2xl font-bold text-[#f0ead8]">{{ averages[key]?.toFixed(1) || '-' }}</p>
      </div>
    </div>

    <div class="space-y-3">
      <div v-for="a in assessments.data" :key="a.id" class="rounded-xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-bold text-[#f0ead8]">{{ a.member?.nama_lengkap }}</p>
            <p class="text-[10px] text-[#8fa06a]">{{ a.periode }} • {{ a.assessor?.name }}</p>
          </div>
          <Link :href="`/assessments/${a.id}`" class="rounded-lg border border-[#6F9435] px-2 py-1 text-[10px] font-bold text-[#d4dc9a] hover:bg-[#6F9435]/20">Detail</Link>
        </div>
        <div class="mt-2 flex gap-4">
          <span v-if="a.nilai_kehadiran" class="text-[10px] text-[#8fa06a]">Hadir: {{ a.nilai_kehadiran }}</span>
          <span v-if="a.nilai_disiplin" class="text-[10px] text-[#8fa06a]">Disiplin: {{ a.nilai_disiplin }}</span>
          <span v-if="a.nilai_keterampilan" class="text-[10px] text-[#8fa06a]">Keterampilan: {{ a.nilai_keterampilan }}</span>
          <span v-if="a.nilai_kepemimpinan" class="text-[10px] text-[#8fa06a]">Kepemimpinan: {{ a.nilai_kepemimpinan }}</span>
        </div>
        <div class="mt-1 font-bold text-[#EDD330] text-xs">Keseluruhan: {{ a.nilai_keseluruhan }}</div>
      </div>
      <p v-if="!assessments.data.length" class="rounded-xl border-2 border-[#6F9435]/30 bg-[#335233] p-10 text-center text-sm text-[#8fa06a]">Belum ada penilaian.</p>
    </div>
    <Pagination :links="assessments.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />

    <Modal v-if="showCreate" :title="'Tambah Penilaian'" @close="reset">
      <form @submit.prevent="submit" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Anggota</span><select v-model="form.member_id" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option value="">Pilih</option><option v-for="m in members" :key="m.id" :value="m.id">{{ m.nama_lengkap }}</option></select></label>
        <label class="block"><span class="text-xs font-medium">Periode</span><input v-model="form.periode" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <div class="grid gap-3 sm:grid-cols-2">
          <label class="block"><span class="text-xs font-medium">Kehadiran (0-100)</span><input v-model.number="form.nilai_kehadiran" type="number" min="0" max="100" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
          <label class="block"><span class="text-xs font-medium">Disiplin (0-100)</span><input v-model.number="form.nilai_disiplin" type="number" min="0" max="100" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
          <label class="block"><span class="text-xs font-medium">Keterampilan (0-100)</span><input v-model.number="form.nilai_keterampilan" type="number" min="0" max="100" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
          <label class="block"><span class="text-xs font-medium">Kepemimpinan (0-100)</span><input v-model.number="form.nilai_kepemimpinan" type="number" min="0" max="100" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        </div>
        <label class="block"><span class="text-xs font-medium">Catatan</span><textarea v-model="form.catatan" rows="3" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"></textarea></label>
        <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Tambah</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({ assessments: Object, members: Array });
const showCreate = ref(false);
const form = useForm({ member_id: '', periode: '', nilai_kehadiran: 0, nilai_disiplin: 0, nilai_keterampilan: 0, nilai_kepemimpinan: 0, catatan: '' });

function submit() {
  form.post('/assessments', { onSuccess: () => reset() });
}
function reset() {
  showCreate.value = false;
  form.reset();
}
const averages = computed(() => {
  if (!assessments.data?.length) return null;
  const data = assessments.data;
  return {
    kehadiran: data.reduce((s, a) => s + (a.nilai_kehadiran || 0), 0) / data.length,
    disiplin: data.reduce((s, a) => s + (a.nilai_disiplin || 0), 0) / data.length,
    keterampilan: data.reduce((s, a) => s + (a.nilai_keterampilan || 0), 0) / data.length,
    kepemimpinan: data.reduce((s, a) => s + (a.nilai_kepemimpinan || 0), 0) / data.length,
  };
});
const labels = { kehadiran: 'Rata-rata Kehadiran', disiplin: 'Rata-rata Disiplin', keterampilan: 'Rata-rata Keterampilan', kepemimpinan: 'Rata-rata Kepemimpinan' };
</script>
