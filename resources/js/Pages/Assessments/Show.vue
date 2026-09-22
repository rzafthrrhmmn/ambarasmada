<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Penilaian</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ assessment.member?.nama_lengkap }}</h1>
      <p class="mt-1 text-sm text-[#8fa06a]">{{ assessment.periode }} • {{ assessment.assessor?.name }}</p>
    </div>

    <div class="grid gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-5">
      <div v-for="(label, key) in labels" :key="key" class="rounded-xl border-2 border-[#6F9435]/40 bg-[#335233] p-4 text-center">
        <p class="text-[10px] font-medium text-[#8fa06a]">{{ label }}</p>
        <p class="mt-1 text-2xl font-bold text-[#f0ead8]">{{ assessment[key] || '-' }}</p>
      </div>
      <div class="rounded-xl border-2 border-[#A7B92B]/40 bg-[#A7B92B]/10 p-4 text-center sm:col-span-2 lg:col-span-1">
        <p class="text-[10px] font-medium text-[#A7B92B]">Keseluruhan</p>
        <p class="mt-1 text-2xl font-bold text-[#A7B92B]">{{ assessment.nilai_keseluruhan || '-' }}</p>
      </div>
    </div>

    <section v-if="assessment.details?.length" class="mb-6 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5">
      <h2 class="mb-3 font-semibold text-[#f0ead8]">Detail Penilaian</h2>
      <div class="space-y-2">
        <div v-for="d in assessment.details" :key="d.id" class="flex items-center justify-between rounded-lg bg-[#263D26] px-4 py-2">
          <div>
            <p class="text-sm text-[#f0ead8]">{{ d.kategori }}</p>
            <p class="text-[10px] text-[#8fa06a]">{{ d.deskripsi }}</p>
          </div>
          <span class="font-bold text-[#EDD330]">{{ d.nilai }}</span>
        </div>
      </div>
    </section>

    <Link :href="`/assessments`" class="inline-flex items-center rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">← Kembali</Link>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

defineProps({ assessment: Object });
const labels = computed(() => ({
  nilai_kehadiran: 'Kehadiran',
  nilai_disiplin: 'Disiplin',
  nilai_keterampilan: 'Keterampilan',
  nilai_kepemimpinan: 'Kepemimpinan',
}));
</script>
