<template>
  <AppLayout>
    <SectionTransition type="slide-up">
      <div class="mb-6">
        <p class="text-sm font-medium text-emas-400">Pengaturan</p>
        <h1 class="mt-1 text-2xl font-bold text-krem-100">Pengaturan Akun</h1>
        <p class="mt-1 text-sm text-krem-300">Personalisasi pengalaman Anda di aplikasi.</p>
      </div>
    </SectionTransition>

    <div class="grid gap-6 lg:grid-cols-3">
      <SectionTransition type="slide-up" class="lg:col-span-2">
        <div class="rounded-2xl border border-[#6F9435] bg-hutan-600 p-5 shadow-sm">
          <h2 class="font-semibold text-krem-100 mb-4">Tampilan</h2>
          <form @submit.prevent="saveSettings" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-[#d4dc9a] mb-2">Tema</label>
              <select v-model="settingsForm.theme" class="w-full rounded-lg border border-[#6F9435] bg-hutan-800 px-3 py-2 text-sm text-krem-100 outline-none focus:border-[#EDD330]">
                <option value="dark">Gelap</option>
                <option value="light">Terang</option>
                <option value="system">Sistem</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-[#d4dc9a] mb-2">Bahasa</label>
              <select v-model="settingsForm.language" class="w-full rounded-lg border border-[#6F9435] bg-hutan-800 px-3 py-2 text-sm text-krem-100 outline-none focus:border-[#EDD330]">
                <option value="id">Bahasa Indonesia</option>
                <option value="en">English</option>
              </select>
            </div>
            <button type="submit" :disabled="settingsForm.processing" class="rounded-lg bg-gradient-to-r from-daun-400 to-[#6F9435] px-6 py-2 text-sm font-semibold text-white hover:brightness-110 disabled:opacity-50">
              {{ settingsForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
            </button>
          </form>
        </div>
      </SectionTransition>

      <SectionTransition type="slide-up">
        <div class="rounded-2xl border border-[#6F9435] bg-hutan-600 p-5 shadow-sm">
          <h2 class="font-semibold text-krem-100 mb-4">Notifikasi</h2>
          <div class="space-y-3">
            <label class="flex items-center justify-between">
              <span class="text-sm text-[#d4dc9a]">Notifikasi Email</span>
              <input type="checkbox" v-model="settingsForm.notifications.email" @change="saveSettings" class="rounded border-[#6F9435] bg-hutan-800 text-[#6F9435] focus:ring-[#EDD330]" />
            </label>
            <label class="flex items-center justify-between">
              <span class="text-sm text-[#d4dc9a]">Notifikasi Browser</span>
              <input type="checkbox" v-model="settingsForm.notifications.browser" @change="saveSettings" class="rounded border-[#6F9435] bg-hutan-800 text-[#6F9435] focus:ring-[#EDD330]" />
            </label>
          </div>
        </div>
      </SectionTransition>
    </div>

    <SectionTransition type="slide-up" class="mt-6">
      <div class="rounded-2xl border border-[#6F9435] bg-hutan-600 p-5 shadow-sm max-w-2xl">
        <h2 class="font-semibold text-krem-100 mb-4">Ubah Kata Sandi</h2>
        <form @submit.prevent="changePassword" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-[#d4dc9a] mb-2">Kata Sandi Saat Ini</label>
            <input v-model="passwordForm.current_password" type="password" required class="w-full rounded-lg border border-[#6F9435] bg-hutan-800 px-3 py-2 text-sm text-krem-100 outline-none focus:border-[#EDD330]" />
            <p v-if="passwordForm.errors.current_password" class="text-xs text-[#ef4419] mt-1">{{ passwordForm.errors.current_password }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-[#d4dc9a] mb-2">Kata Sandi Baru</label>
            <input v-model="passwordForm.password" type="password" required class="w-full rounded-lg border border-[#6F9435] bg-hutan-800 px-3 py-2 text-sm text-krem-100 outline-none focus:border-[#EDD330]" />
            <p v-if="passwordForm.errors.password" class="text-xs text-[#ef4419] mt-1">{{ passwordForm.errors.password }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-[#d4dc9a] mb-2">Konfirmasi Kata Sandi Baru</label>
            <input v-model="passwordForm.password_confirmation" type="password" required class="w-full rounded-lg border border-[#6F9435] bg-hutan-800 px-3 py-2 text-sm text-krem-100 outline-none focus:border-[#EDD330]" />
          </div>
          <button type="submit" :disabled="passwordForm.processing" class="rounded-lg bg-gradient-to-r from-daun-400 to-[#6F9435] px-6 py-2 text-sm font-semibold text-white hover:brightness-110 disabled:opacity-50">
            {{ passwordForm.processing ? 'Mengubah...' : 'Ubah Kata Sandi' }}
          </button>
        </form>
      </div>
    </SectionTransition>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import AppLayout from '@/Components/AppLayout.vue';
import SectionTransition from '@/Components/SectionTransition.vue';

const props = defineProps({
  user: Object,
  settings: Object,
});

const toast = useToast();

const settingsForm = useForm({
  theme: props.settings?.theme || 'dark',
  language: props.settings?.language || 'id',
  notifications: {
    email: props.settings?.notifications?.email ?? true,
    browser: props.settings?.notifications?.browser ?? true,
  },
  sidebar_collapsed: props.settings?.sidebar_collapsed ?? false,
});

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

function saveSettings() {
  settingsForm.patch('/settings', {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Pengaturan berhasil disimpan');
    },
  });
}

function changePassword() {
  passwordForm.patch('/settings/password', {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset();
      toast.success('Kata sandi berhasil diubah');
    },
  });
}
</script>
