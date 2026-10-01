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
        Akun Anda telah berhasil dibuat dengan email <strong class="text-[#EDD330]">{{ auth.user?.email }}</strong><span v-if="auth.user?.nta"> dan NTA <strong class="text-[#EDD330]">{{ auth.user.nta }}</strong></span>. Anda perlu menunggu persetujuan dari Pembina sebelum dapat mengakses seluruh fitur sistem.
      </p>
      <div class="mt-8 rounded-xl border-2 border-[#A7B92A]/40 bg-[#335233] p-5 max-w-md">
        <p class="text-sm text-[#d4dc9a]">Langkah selanjutnya:</p>
        <ol class="mt-2 text-left text-sm text-[#d4dc9a] list-decimal list-inside space-y-1">
          <li v-if="auth.user?.nta">Simpan NTA Anda: <strong class="text-[#EDD330]">{{ auth.user.nta }}</strong></li>
          <li>Aktifkan email Anda melalui tautan yang kami kirim</li>
          <li>Tunggu konfirmasi dari Pembina</li>
          <li>Setelah disetujui, Anda dapat langsung masuk</li>
        </ol>
      </div>

      <div v-if="!emailVerified" class="mt-6 w-full max-w-md rounded-xl border-2 border-[#ef4419]/40 bg-[#ef4419]/10 p-5 text-left">
        <p class="text-sm font-bold text-[#EDD330]">Email belum diverifikasi</p>
        <p class="mt-1 text-sm text-[#d4dc9a]">
          Belum menerima email di <span class="font-bold">{{ email }}</span>? Kirim ulang tautan aktivasi di bawah ini.
        </p>
        <button
          type="button"
          @click="resend"
          :disabled="form.processing || cooldown > 0"
          :class="['mt-4 w-full rounded-lg border-2 border-[#EDD330] bg-gradient-to-r from-[#A7B92A] to-[#EDD330] px-4 py-2.5 text-sm font-extrabold text-[#263D26] transition hover:-translate-y-0.5 disabled:cursor-wait disabled:opacity-60', form.processing ? 'animate-pulse' : '']"
        >
          <span v-if="form.processing">Mengirim...</span>
          <span v-else-if="cooldown > 0">Tunggu {{ cooldown }} detik...</span>
          <span v-else>Kirim Ulang Email Aktivasi</span>
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

const COOLDOWN_SECONDS = 30;

const page = usePage();
const auth = page.props.auth;
const email = auth?.user?.email ?? '';
const emailVerified = auth?.user?.email_verified_at != null;

const form = useForm({});
const cooldown = ref(0);
let timer = null;

function startCooldown() {
  cooldown.value = COOLDOWN_SECONDS;
  if (timer) clearInterval(timer);
  timer = setInterval(() => {
    cooldown.value -= 1;
    if (cooldown.value <= 0 && timer) {
      clearInterval(timer);
      timer = null;
    }
  }, 1000);
}

function resend() {
  if (cooldown.value > 0) return;

  form.post('/email/verification-notification', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      startCooldown();
    },
    onError: () => form.reset(),
  });
}

onMounted(() => startCooldown());
onBeforeUnmount(() => {
  if (timer) clearInterval(timer);
});
</script>