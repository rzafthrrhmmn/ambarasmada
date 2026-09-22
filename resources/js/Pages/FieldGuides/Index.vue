<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Panduan Lapangan</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Buku Saku Digital</h1>
      <p class="mt-1 text-sm text-[#8fa06a]">Referensi cepat tanda isyarat, simpul, teknik lapangan, dan lainnya.</p>
    </div>

    <div class="mb-4 rounded-xl border border-[#6F9435] bg-[#335233] p-3">
      <input v-model="search" placeholder="Cari panduan..." class="w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div v-for="guide in filteredGuides" :key="guide.id" class="rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-5 transition hover:border-[#A7B92B]/60">
        <span class="rounded-full bg-[#6F9435]/20 px-2.5 py-1 text-[10px] font-bold text-[#EDD330]">{{ guide.kategori }}</span>
        <h3 class="mt-3 text-sm font-bold text-[#f0ead8]">{{ guide.judul }}</h3>
        <p class="mt-2 line-clamp-3 text-xs leading-5 text-[#8fa06a]">{{ guide.konten }}</p>
        <div class="mt-3 flex items-center justify-between">
          <span class="text-[10px] text-[#8fa06a]">{{ guide.createdBy?.name }}</span>
          <button v-if="guide.is_favorited" class="text-[10px]">⭐</button>
        </div>
      </div>
      <p v-if="!filteredGuides.length" class="col-span-full rounded-xl border-2 border-[#6F9435]/30 bg-[#335233] p-10 text-center text-sm text-[#8fa06a]">Belum ada panduan.</p>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

defineProps({ guides: Object });
const search = ref('');
const page = usePage();

const filteredGuides = computed(() => {
  if (!search.value) return guides.value?.data || [];
  const q = search.value.toLowerCase();
  return (guides.value?.data || []).filter(g =>
    g.judul.toLowerCase().includes(q) ||
    g.konten.toLowerCase().includes(q) ||
    g.kategori.toLowerCase().includes(q)
  );
});
</script>
