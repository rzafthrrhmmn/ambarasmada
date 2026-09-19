<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Informasi Ambalan</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Pengumuman</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Publikasikan kabar kegiatan dan informasi penting ambalan.</p>
      </div>
      <button v-if="canManage" @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">+ Pengumuman</button>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
      <article v-for="item in announcements.data" :key="item.id" class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
        <div v-if="item.image_url" class="mb-3">
          <img :src="item.image_url" alt="Gambar pengumuman" class="h-48 w-full rounded-lg object-cover border-2 border-[#6F9435]/30" />
        </div>
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-xs text-[#8fa06a]">{{ formatDate(item.published_at) }}</p>
            <h2 class="mt-1 text-lg font-semibold text-[#f0ead8]">{{ item.judul }}</h2>
          </div>
          <button v-if="canManage" @click="openEdit(item)" class="text-xs text-[#EDD330] hover:underline">Edit</button>
        </div>
        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#d4dc9a]">{{ item.isi }}</p>
      </article>
    </div>
    <Modal v-if="showCreate" :title="editItem ? 'Edit pengumuman' : 'Tambah pengumuman'" @close="reset">
      <form @submit.prevent="form.post(editItem ? `/announcements/${editItem.id}` : '/announcements', { method: editItem ? 'patch' : 'post', onSuccess: reset })" class="grid gap-3">
        <label class="block">
          <span class="text-xs font-medium">Foto dokumentasi</span>
          <input type="file" @change="form.image = $event.target.files[0]" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-[#d4dc9a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#6F9435] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white" />
          <p v-if="form.errors.image" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.image }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium">Judul</span>
          <input v-model="form.judul" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm" />
          <p v-if="form.errors.judul" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.judul }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium">Isi pengumuman</span>
          <textarea v-model="form.isi" required rows="7" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"></textarea>
          <p v-if="form.errors.isi" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.isi }}</p>
        </label>
        <button type="submit" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Terbitkan</button>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
defineProps({ announcements: Object });
const page = usePage();
const canManage = computed(() => {
  const role = page.props.auth?.user?.role;
  return role === 'Admin' || role === 'Pembina' || role === 'Pengurus';
});
const showCreate = ref(false);
const editItem = ref(null);
const form = useForm({ judul: '', isi: '', image: null });
function openEdit(item) {
  editItem.value = item;
  form.judul = item.judul;
  form.isi = item.isi;
  form.image = null;
  showCreate.value = true;
}
function reset() {
  showCreate.value = false;
  editItem.value = null;
  form.reset();
}
function formatDate(value) { return new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }); }
</script>