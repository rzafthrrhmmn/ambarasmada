<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Pengaturan</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Kelola Logo Ambalan</h1>
    </div>

    <div class="max-w-lg rounded-2xl border-2 border-[#6F9435] bg-[#335233] p-6 shadow-sm border-[#6F9435]">
      <div class="mb-6">
        <p class="text-xs font-bold text-[#d4dc9a] mb-2">Logo Saat Ini</p>
        <div v-if="ambalan?.logo_url" class="flex items-center gap-4">
          <img :src="ambalan.logo_url" alt="Logo" class="h-20 w-20 rounded-xl object-contain border-2 border-[#6F9435] bg-[#263D26]" />
          <span class="text-sm text-[#8fa06a]">{{ ambalan?.nama }}</span>
        </div>
        <div v-else class="flex h-20 w-20 items-center justify-center rounded-xl bg-gradient-to-br from-[#A7B92A] to-[#EDD330] border-2 border-[#EDD330]">
          <span class="text-2xl font-extrabold text-[#263D26]">P</span>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <label class="block">
          <span class="text-xs font-bold text-[#d4dc9a]">Upload Logo</span>
          <input type="file" @change="onFileChange" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-[#d4dc9a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#6F9435] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white" />
          <p v-if="$page.props.errors.logo" class="mt-1 text-xs text-[#ef4419]">{{ $page.props.errors.logo }}</p>
          <p class="mt-1 text-xs text-[#8fa06a]">Format: JPG, PNG, WEBP. Max 2MB.</p>
        </label>

        <div v-if="previewUrl" class="mt-3">
          <img :src="previewUrl" alt="Preview" class="h-24 w-24 rounded-xl object-contain border-2 border-[#EDD330] bg-[#263D26]" />
        </div>

        <div class="flex gap-2 pt-2">
          <button :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#EDD330] px-4 py-2 text-sm font-extrabold text-[#263D26] transition hover:from-[#EDD330] hover:to-[#A7B92A] disabled:opacity-50">
            {{ form.processing ? 'Menyimpan...' : 'Simpan Logo' }}
          </button>
          <button type="button" @click="removeLogo" :disabled="!ambalan?.logo_path" class="rounded-lg border border-[#ef4419]/50 px-4 py-2 text-sm font-bold text-[#ef4419] transition hover:bg-[#ef4419]/20 disabled:opacity-30">
            Hapus Logo
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

const props = defineProps({
  ambalan: Object,
});

const previewUrl = ref(null);
const form = useForm({ logo: null });

function onFileChange(event) {
  const file = event.target.files[0];
  if (file) {
    form.logo = file;
    previewUrl.value = URL.createObjectURL(file);
  }
}

function submit() {
  form.post('/ambalan/logo', {
    onSuccess: () => {
      form.reset();
      previewUrl.value = null;
    },
    onError: () => {},
  });
}

function removeLogo() {
  if (!confirm('Hapus logo ambalan?')) return;
  form.delete('/ambalan/logo', {
    onSuccess: () => {
      form.reset();
      previewUrl.value = null;
    },
    onError: () => {},
  });
}
</script>
