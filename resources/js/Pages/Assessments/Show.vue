<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-emas-400">Penilaian</p>
      <h1 class="mt-1 text-2xl font-bold text-krem-100">{{ assessment.member?.nama_lengkap }}</h1>
      <p class="mt-1 text-sm text-krem-300">{{ assessment.periode }} • {{ assessment.assessor?.name }}</p>
    </div>

    <div class="grid gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-5">
      <div v-for="(label, key) in labels" :key="key" class="rounded-xl border-2 border-[#6F9435]/40 bg-hutan-600 p-4 text-center">
        <p class="text-[10px] font-medium text-krem-300">{{ label }}</p>
        <p class="mt-1 text-2xl font-bold text-krem-100">{{ assessment[key] || '-' }}</p>
      </div>
      <div class="rounded-xl border-2 border-[#A7B92B]/40 bg-[#A7B92B]/10 p-4 text-center sm:col-span-2 lg:col-span-1">
        <p class="text-[10px] font-medium text-[#A7B92B]">Keseluruhan</p>
        <p class="mt-1 text-2xl font-bold text-[#A7B92B]">{{ assessment.nilai_keseluruhan || '-' }}</p>
      </div>
    </div>

    <section v-if="assessment.details?.length" class="mb-6 rounded-2xl border-2 border-[#6F9435]/40 bg-hutan-600 p-5">
      <h2 class="mb-3 font-semibold text-krem-100">Detail Penilaian</h2>
      <div class="space-y-2">
        <div v-for="d in assessment.details" :key="d.id" class="flex items-center justify-between rounded-lg bg-hutan-800 px-4 py-2">
          <div>
            <p class="text-sm text-krem-100">{{ d.kategori }}</p>
            <p class="text-[10px] text-krem-300">{{ d.deskripsi }}</p>
          </div>
          <span class="font-bold text-emas-400">{{ d.nilai }}</span>
        </div>
      </div>
    </section>

    <a href="/assessments" class="btn-ghost">
      <AppIcon name="arrowLeft" class="h-4 w-4" />
      Kembali
    </a>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Components/AppLayout.vue';

defineProps({ assessment: Object });
const labels = computed(() => ({
  nilai_kehadiran: 'Kehadiran',
  nilai_disiplin: 'Disiplin',
  nilai_keterampilan: 'Keterampilan',
  nilai_kepemimpinan: 'Kepemimpinan',
}));
</script>
