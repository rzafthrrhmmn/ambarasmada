<template>
  <AppLayout>
    <div class="flex flex-col items-center justify-center py-20 text-center">
      <div class="w-20 h-20 rounded-full bg-[#EDD330]/20 flex items-center justify-center mb-6">
        <svg class="w-10 h-10 text-[#EDD330]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <h1 class="text-2xl font-extrabold text-[#f0ead8]">Menunggu Persetujuan Pembina</h1>
      <p class="mt-3 text-base text-[#8fa06a] max-w-md">
        Akun Anda telah berhasil dibuat dengan NTA <strong class="text-[#EDD330]">{{ auth.user?.username }}</strong>. Anda perlu menunggu persetujuan dari Pembina sebelum dapat mengakses seluruh fitur sistem.
      </p>
      <div class="mt-8 rounded-xl border-2 border-[#A7B92A]/40 bg-[#335233] p-5 max-w-md">
        <p class="text-sm text-[#d4dc9a]">Langkah selanjutnya:</p>
        <ol class="mt-2 text-left text-sm text-[#d4dc9a] list-decimal list-inside space-y-1">
          <li>Simpan NTA Anda: <strong class="text-[#EDD330]">{{ auth.user?.username }}</strong></li>
          <li>Tunggu konfirmasi dari Pembina</li>
          <li>Setelah disetujui, Anda dapat langsung masuk</li>
        </ol>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Components/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const auth = page.props.auth;
</script>
