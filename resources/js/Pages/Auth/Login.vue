<template>
  <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-[#263D26] via-[#2d4a2d] to-[#263D26] px-4 py-10">
    <div class="w-full max-w-md rounded-2xl border-2 border-[#A7B92A]/50 bg-[#335233] p-7 shadow-2xl shadow-[#1f3320]/70">
      <div class="mb-7">
        <div class="flex items-center justify-center gap-3">
          <span v-if="$page.props.ambalan?.logo_url" class="flex h-14 w-14 items-center justify-center rounded-xl border-2 border-[#6F9435] bg-[#263D26] overflow-hidden">
            <img :src="$page.props.ambalan.logo_url" alt="Logo" class="h-full w-full object-contain" />
          </span>
          <span v-else class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-r from-[#A7B92A] to-[#EDD330] text-xl font-extrabold text-[#263D26] shadow-lg shadow-[#EDD330]/40 border-2 border-[#EDD330]">P</span>
        </div>
        <h1 class="mt-4 text-2xl font-extrabold text-[#f0ead8]" style="text-shadow: 2px 2px 0 rgba(0,0,0,0.3);">{{ isRegistration ? 'Daftar Akun' : 'Masuk ke Ambalan' }}</h1>
        <p class="mt-2 text-sm font-medium text-[#8fa06a]">
          {{ isRegistration ? 'Buat akun untuk akses sistem. NTA otomatis dibuat oleh sistem.' : 'Kelola kegiatan kepramukaan SMAN 2 Maros dalam satu ekosistem.' }}
        </p>
      </div>

      <form @submit.prevent="isRegistration ? submitRegister() : submitLogin()" class="space-y-4">
        <template v-if="isRegistration">
          <label class="block">
            <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Nama Lengkap</span>
            <input v-model="regForm.nama_lengkap" type="text" required class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-3 py-2.5 text-sm text-[#f0ead8] outline-none transition focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
            <span v-if="$page.props.errors.nama_lengkap" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.nama_lengkap }}</span>
          </label>
          <label class="block">
            <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Email</span>
            <input v-model="regForm.email" type="email" required class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-3 py-2.5 text-sm text-[#f0ead8] outline-none transition focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
            <span v-if="$page.props.errors.email" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.email }}</span>
          </label>
          <label class="block">
            <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">NTA (otomatis)</span>
            <input :value="previewNta" disabled class="w-full rounded-lg border-2 border-[#6F9435]/50 bg-[#263D26]/50 px-3 py-2.5 text-sm font-mono text-[#EDD330] outline-none" />
            <span v-if="latestAngkatan && latestAngkatanNomor" class="mt-1 block text-xs text-[#8fa06a]">{{ latestAngkatanCurrent ? 'Angkatan berjalan' : 'Angkatan aktif terbaru' }}: {{ latestAngkatanNomor }} / Tahun {{ latestAngkatan }}</span>
            <span v-else class="mt-1 block text-xs text-[#ef4419]">Belum ada angkatan aktif. Hubungi Pembina.</span>
          </label>
          <label class="block">
            <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Password</span>
            <input v-model="regForm.password" type="password" required minlength="8" class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-3 py-2.5 text-sm text-[#f0ead8] outline-none transition focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
            <span v-if="$page.props.errors.password" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.password }}</span>
          </label>
          <label class="block">
            <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Konfirmasi Password</span>
            <input v-model="regForm.password_confirmation" type="password" required minlength="8" class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-3 py-2.5 text-sm text-[#f0ead8] outline-none transition focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
          </label>
        </template>

        <template v-else>
          <label class="block">
            <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Username atau email</span>
            <input v-model="form.identity" type="text" required autocomplete="username" class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-3 py-2.5 text-sm text-[#f0ead8] outline-none transition focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
            <span v-if="$page.props.errors.identity" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.identity }}</span>
          </label>
          <label class="block">
            <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Password</span>
            <input v-model="form.password" type="password" required autocomplete="current-password" class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-3 py-2.5 text-sm text-[#f0ead8] outline-none transition focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
            <span v-if="$page.props.errors.password" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.password }}</span>
          </label>
        </template>

        <label v-if="!isRegistration" class="flex items-center gap-2 text-sm font-medium text-[#d4dc9a]">
          <input v-model="form.remember" type="checkbox" class="rounded border-2 border-[#6F9435] bg-[#263D26] text-[#EDD330]" /> Ingat saya
        </label>

        <button :disabled="form.processing || regForm.processing" class="w-full rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#EDD330] px-4 py-2.5 font-extrabold text-[#263D26] shadow-lg shadow-[#EDD330]/30 transition hover:from-[#EDD330] hover:to-[#A7B92A] disabled:cursor-wait disabled:opacity-70 border-2 border-[#EDD330]">
          {{ isRegistration ? (regForm.processing ? 'Mendaftar...' : 'Daftar Akun') : (form.processing ? 'Memproses...' : 'Masuk') }}
        </button>
      </form>

      <div v-if="isRegistration" class="mt-4 text-center">
        <p class="text-sm text-[#8fa06a]">
          Sudah punya akun?
          <button @click="toggleMode" class="font-bold text-[#EDD330] hover:underline">Masuk</button>
        </p>
      </div>
      <div v-else class="mt-4 text-center">
        <p class="text-sm text-[#8fa06a]">
          Belum punya akun?
          <button @click="toggleMode" class="font-bold text-[#EDD330] hover:underline">Daftar Akun</button>
        </p>
      </div>

      <div v-if="!isRegistration && hasSavedCredentials" class="mt-3 rounded-lg border-2 border-[#EDD330]/40 bg-[#2d4a2d]/60 p-3 text-center text-sm font-medium text-[#EDD330]">
        Kredensial tersimpan — klik <span class="font-bold">Masuk</span> untuk melanjutkan.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

const CREDENTIALS_STORAGE_KEY = 'ambalan_login_credentials';

const props = defineProps({
  isRegistration: { type: Boolean, default: false },
  latestAngkatan: { type: String, default: null },
  latestAngkatanNomor: { type: String, default: null },
  latestAngkatanCurrent: { type: Boolean, default: false },
  gudepPrefix: { type: String, default: '31082008' },
});

const form = useForm({ identity: '', password: '', remember: false });
const regForm = useForm({ nama_lengkap: '', email: '', password: '', password_confirmation: '' });
const hasSavedCredentials = ref(false);

const previewNta = computed(() => {
  if (! props.latestAngkatanNomor) return '';
  return `${props.gudepPrefix}.${props.latestAngkatanNomor}.001`;
});

function toggleMode() {
  const url = props.isRegistration ? '/register' : '/login';
  router.get(url, {}, {
    preserveState: true,
    preserveScroll: true,
  });
}

function submitLogin() {
  form.post('/login', {
    onSuccess: () => {
      if (form.remember && form.identity) {
        localStorage.setItem(CREDENTIALS_STORAGE_KEY, JSON.stringify({
          identity: form.identity,
        }));
      } else {
        localStorage.removeItem(CREDENTIALS_STORAGE_KEY);
      }
    },
  });
}

function submitRegister() {
  regForm.post('/register', {
    onSuccess: () => {
      regForm.reset();
    },
  });
}

function loadSavedCredentials() {
  const saved = localStorage.getItem(CREDENTIALS_STORAGE_KEY);
  if (saved) {
    try {
      const creds = JSON.parse(saved);
      form.identity = creds.identity || '';
      form.remember = true;
      hasSavedCredentials.value = !!creds.identity;
    } catch {
      localStorage.removeItem(CREDENTIALS_STORAGE_KEY);
    }
  }
}

onMounted(() => {
  loadSavedCredentials();
});
</script>
