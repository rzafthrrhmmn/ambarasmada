<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Media</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Galeri Ambalan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Dokumentasi foto kegiatan ambalan.</p>
      </div>
      <button v-if="canManage" @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Upload Foto</button>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <button v-for="cat in categories" :key="cat" @click="filterKategori = cat" class="rounded-full px-3 py-1 text-[10px] font-bold transition" :class="filterKategori === cat ? 'bg-[#6F9435] text-white' : 'border-2 border-[#6F9435] bg-[#335233] text-[#d4dc9a]'">
        {{ cat }}
      </button>
    </div>

    <div class="grid gap-4 grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
      <div v-for="g in filteredGalleries" :key="g.id" class="group relative overflow-hidden rounded-2xl border-2 border-[#6F9435]/40">
        <img :src="g.image_url" alt="" class="h-40 w-full object-cover transition group-hover:scale-105" />
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 transition group-hover:opacity-100 flex flex-col justify-end p-3">
          <p class="text-xs font-bold text-white">{{ g.judul }}</p>
          <p class="text-[10px] text-[#8fa06a]">{{ g.kategori }}</p>
        </div>
      </div>
      <p v-if="!filteredGalleries.length" class="col-span-full rounded-xl border-2 border-[#6F9435]/30 bg-[#335233] p-10 text-center text-sm text-[#8fa06a]">Belum ada foto.</p>
    </div>

    <Modal v-if="showCreate" :title="'Upload Foto'" @close="reset">
      <form @submit.prevent="submit" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Judul</span><input v-model="form.judul" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Deskripsi</span><textarea v-model="form.deskripsi" rows="2" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"></textarea></label>
        <label class="block"><span class="text-xs font-medium">Kategori</span><select v-model="form.kategori" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option value="">Pilih</option><option>Umum</option><option>Kegiatan</option><option>Latihan</option><option>Outbound</option><option>Jambore</option><option>Peringatan</option></select></label>
        <label class="block"><span class="text-xs font-medium">Foto</span><input type="file" @change="form.image = $event.target.files[0]" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-xs text-[#d4dc9a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#6F9435] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white" required /></label>
        <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Upload</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import Modal from '@/Components/Modal.vue';

defineProps({ galleries: Object });
const canManage = true;
const showCreate = ref(false);
const filterKategori = ref('');
const categories = ['Umum', 'Kegiatan', 'Latihan', 'Outbound', 'Jambore', 'Peringatan'];
const form = useForm({ judul: '', deskripsi: '', image: null, kategori: 'Umum' });

function submit() {
  form.post('/galleries', { onSuccess: () => reset() });
}
function reset() {
  showCreate.value = false;
  form.reset();
}
const filteredGalleries = computed(() => {
  if (!filterKategori.value) return galleries.value?.data || [];
  return (galleries.value?.data || []).filter(g => g.kategori === filterKategori.value);
});
</script>

