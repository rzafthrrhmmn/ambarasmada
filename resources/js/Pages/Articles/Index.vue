<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Blog Ambalan</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Artikel & Laporan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Berbagi pengalaman, laporan kegiatan, dan artikel.</p>
      </div>
      <button v-if="canManage" @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Artikel Baru</button>
    </div>

    <div class="mb-4 rounded-xl border border-[#6F9435] bg-[#335233] p-3">
      <select v-model="filterKategori" @change="onFilter" class="w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
        <option value="">Semua Kategori</option>
        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
      </select>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <article v-for="article in articles.data" :key="article.id" class="rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] overflow-hidden">
        <img v-if="article.image_url" :src="article.image_url" alt="" class="h-40 w-full object-cover" />
        <div class="p-5">
          <span class="rounded-full bg-[#6F9435]/20 px-2.5 py-1 text-[10px] font-bold text-[#EDD330]">{{ article.kategori }}</span>
          <h3 class="mt-2 text-sm font-bold text-[#f0ead8]">{{ article.judul }}</h3>
          <p class="mt-2 line-clamp-3 text-xs leading-5 text-[#8fa06a]">{{ article.konten }}</p>
          <div class="mt-3 flex items-center justify-between">
            <span class="text-[10px] text-[#8fa06a]">Oleh {{ article.author?.name }} • {{ formatDate(article.created_at) }}</span>
            <span v-if="article.is_published" class="text-[10px] font-bold text-[#A7B92B]">✓ Terbit</span>
          </div>
        </div>
      </article>
      <p v-if="!articles.data.length" class="col-span-full rounded-xl border-2 border-[#6F9435]/30 bg-[#335233] p-10 text-center text-sm text-[#8fa06a]">Belum ada artikel.</p>
    </div>
    <Pagination :links="articles.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />

    <Modal v-if="showCreate" :title="editingArticle ? 'Edit Artikel' : 'Tambah Artikel'" @close="reset">
      <form @submit.prevent="submit" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Judul</span><input v-model="form.judul" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" /></label>
        <label class="block"><span class="text-xs font-medium">Kategori</span><select v-model="form.kategori" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option value="">Pilih</option><option>Laporan Kegiatan</option><option>Artikel</option><option>Berita</option><option>Tips & Trik</option><option>Lainnya</option></select></label>
        <label class="block"><span class="text-xs font-medium">Konten</span><textarea v-model="form.konten" required rows="6" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"></textarea></label>
        <label class="block"><span class="text-xs font-medium">Gambar</span><input type="file" @change="form.image = $event.target.files[0]" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-xs text-[#d4dc9a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#6F9435] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white" /></label>
        <label class="flex items-center gap-2"><input v-model="form.is_published" type="checkbox" class="h-4 w-4 rounded border-[#6F9435] bg-[#335233] text-[#A7B92B]" /><span class="text-xs font-medium">Terbitkan</span></label>
        <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">{{ editingArticle ? 'Simpan' : 'Tambah' }}</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({ articles: Object });
const canManage = true;
const showCreate = ref(false);
const editingArticle = ref(null);
const filterKategori = ref('');
const form = useForm({ judul: '', konten: '', kategori: 'Laporan Kegiatan', image: null, is_published: false });
const page = usePage();
const categories = ['Laporan Kegiatan', 'Artikel', 'Berita', 'Tips & Trik', 'Lainnya'];

function openEdit(article) {
  editingArticle.value = article;
  form.judul = article.judul;
  form.konten = article.konten;
  form.kategori = article.kategori;
  form.is_published = article.is_published;
  form.image = null;
  showCreate.value = true;
}
function reset() {
  showCreate.value = false;
  editingArticle.value = null;
  form.reset();
}
function submit() {
  const url = editingArticle.value ? `/articles/${editingArticle.value.id}` : '/articles';
  const method = editingArticle.value ? 'patch' : 'post';
  form[method](url, { onSuccess: () => reset() });
}
function onFilter() {
  router.get('/articles', { kategori: filterKategori.value });
}
function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
}
</script>
