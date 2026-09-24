<template>
  <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-[#263D26] via-[#2d4a2d] to-[#263D26] px-4 py-8 md:py-0">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
      <div class="animate-float absolute left-10 top-20 h-72 w-72 rounded-full bg-[#A7B92A]/10 blur-3xl animate-pulse-glow"></div>
      <div class="animate-float absolute right-10 top-1/3 h-60 w-60 rounded-full bg-[#EDD330]/8 blur-3xl animate-pulse-glow" style="animation-delay: 2s;"></div>
      <div class="animate-float absolute bottom-20 left-1/3 h-56 w-56 rounded-full bg-[#6F9435]/10 blur-3xl animate-pulse-glow" style="animation-delay: 4s;"></div>
    </div>

    <div class="relative grid w-full max-w-5xl overflow-hidden rounded-3xl border-2 border-[#A7B92A]/30 bg-[#335233]/80 shadow-2xl shadow-black/40 backdrop-blur-sm md:grid-cols-5 min-h-[600px]">
      <div class="relative col-span-2 hidden flex-col justify-center gap-6 bg-gradient-to-br from-[#263D26] to-[#2d4a2d] p-10 md:flex">
        <div class="animate-float">
          <div class="flex items-center justify-center gap-3">
            <div class="flex h-20 w-20 items-center justify-center rounded-2xl border-2 border-[#6F9435] bg-[#263D26] overflow-hidden shadow-lg shadow-[#A7B92A]/20">
              <img v-if="$page.props.ambalan?.logo_url" :src="$page.props.ambalan.logo_url" alt="Logo" class="h-full w-full object-contain" />
              <img v-else :src="'/images/Logo_Ambalan.png'" alt="Logo" class="h-full w-full object-contain" />
            </div>
          </div>
          <p class="mt-5 text-center text-sm font-bold tracking-wide text-[#EDD330]">{{ $page.props.ambalan?.nama || "Ambalan UPT SMAN 2 Maros" }}</p>
        </div>
        <div>
          <h2 class="text-3xl font-black text-[#f0ead8]" style="text-shadow: 2px 2px 0 rgba(0,0,0,0.3);">Satya dan Darma</h2>
          <p class="mt-2 text-base font-medium leading-relaxed text-[#d4dc9a]/80">Ekosistem digital untuk anggota, pembina, pengurus, dan alumni kepramukaan.</p>
        </div>
        <div class="space-y-3">
          <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#A7B92A]/20">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4 text-[#EDD330]"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" /></svg>
            </div>
            <span class="text-sm font-medium text-[#d4dc9a]">SKU Digital</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#A7B92A]/20">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4 text-[#EDD330]"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
            </div>
            <span class="text-sm font-medium text-[#d4dc9a]">Presensi Online</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#A7B92A]/20">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4 text-[#EDD330]"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6H2.25m0 0v12.75A2.25 2.25 0 004.5 21h15a2.25 2.25 0 002.25-2.25V5.25A2.25 2.25 0 0019.5 3H3" /></svg>
            </div>
            <span class="text-sm font-medium text-[#d4dc9a]">Kas Terintegrasi</span>
          </div>
        </div>
        <div class="mt-4 rounded-xl border border-[#A7B92A]/20 bg-[#263D26]/50 p-4 text-center">
          <p class="text-xs font-bold text-[#8fa06a]">Akses mudah di ponsel</p>
          <p class="mt-1 text-2xl font-black text-[#EDD330]">PWA</p>
        </div>
      </div>

      <div class="col-span-3 flex flex-col justify-center p-6 md:p-10">
        <div class="w-full max-w-md mx-auto">
          <div class="mb-8 md:hidden">
            <div class="flex items-center justify-center gap-3">
              <div class="flex h-14 w-14 items-center justify-center rounded-xl border-2 border-[#6F9435] bg-[#263D26] overflow-hidden">
                <img v-if="$page.props.ambalan?.logo_url" :src="$page.props.ambalan.logo_url" alt="Logo" class="h-full w-full object-contain" />
                <img v-else :src="'/images/Logo_Ambalan.png'" alt="Logo" class="h-full w-full object-contain" />
              </div>
              <p class="text-sm font-bold text-[#EDD330]">{{ $page.props.ambalan?.nama || "Ambalan UPT SMAN 2 Maros" }}</p>
            </div>
          </div>

          <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg border border-[#6F9435] bg-[#6F9435]/20 p-3 text-sm font-medium text-[#EDD330]">
            {{ $page.props.flash.success }}
          </div>
          <div class="mb-7">
            <p class="text-sm font-bold tracking-widest text-[#A7B92A] uppercase">Welcome Back</p>
            <h1 class="mt-2 text-3xl font-black text-[#f0ead8]" style="text-shadow: 2px 2px 0 rgba(0,0,0,0.3);">
              {{ isRegistration ? 'Buat Akun Baru' : 'Masuk ke Ambalan' }}
            </h1>
            <p class="mt-2 text-sm font-medium text-[#8fa06a]">
              {{ isRegistration ? 'Gabung sekarang dan akses seluruh ekosistem digital.' : 'Masuk untuk mengelola kegiatan kepramukaan.' }}
            </p>
          </div>

          <form @submit.prevent="isRegistration ? submitRegister() : submitLogin()" class="space-y-4">
            <template v-if="isRegistration">
              <div>
                <label class="block">
                  <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Nama Lengkap</span>
                  <input v-model="regForm.nama_lengkap" type="text" required placeholder="Masukkan nama lengkap" class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-3 text-sm text-[#f0ead8] outline-none transition placeholder:text-[#8fa06a]/50 focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
                </label>
                <span v-if="$page.props.errors.nama_lengkap" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.nama_lengkap }}</span>
              </div>
              <div>
                <label class="block">
                  <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Email</span>
                  <input v-model="regForm.email" type="email" required placeholder="contoh@email.com" class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-3 text-sm text-[#f0ead8] outline-none transition placeholder:text-[#8fa06a]/50 focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
                </label>
                <span v-if="$page.props.errors.email" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.email }}</span>
              </div>
              <div>
                <label class="block">
                  <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">NTA (otomatis)</span>
                  <input :value="previewNta" disabled class="w-full rounded-lg border-2 border-[#6F9435]/50 bg-[#263D26]/50 px-4 py-3 text-sm font-mono text-[#EDD330] outline-none" />
                </label>
                <span v-if="latestAngkatan && latestAngkatanNomor" class="mt-1 block text-xs text-[#8fa06a]">{{ latestAngkatanCurrent ? 'Angkatan berjalan' : 'Angkatan aktif terbaru' }}: {{ latestAngkatanNomor }} / Tahun {{ latestAngkatan }}</span>
                <span v-else class="mt-1 block text-xs text-[#ef4419]">Belum ada angkatan aktif. Hubungi Pembina.</span>
              </div>
              <div>
                <label class="block">
                  <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Password</span>
                  <input v-model="regForm.password" type="password" required minlength="8" placeholder="Minimal 8 karakter" class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-3 text-sm text-[#f0ead8] outline-none transition placeholder:text-[#8fa06a]/50 focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
                </label>
                <span v-if="$page.props.errors.password" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.password }}</span>
              </div>
              <div>
                <label class="block">
                  <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Konfirmasi Password</span>
                  <input v-model="regForm.password_confirmation" type="password" required minlength="8" placeholder="Ulangi password" class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-3 text-sm text-[#f0ead8] outline-none transition placeholder:text-[#8fa06a]/50 focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
                </label>
                <span v-if="$page.props.errors.password_confirmation" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.password_confirmation }}</span>
              </div>
            </template>

            <template v-else>
              <div>
                <label class="block">
                  <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Username atau email</span>
                  <input v-model="form.identity" type="text" required autocomplete="username" placeholder="Masukkan username atau email" class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-3 text-sm text-[#f0ead8] outline-none transition placeholder:text-[#8fa06a]/50 focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
                </label>
                <span v-if="$page.props.errors.identity" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.identity }}</span>
              </div>
              <div>
                <label class="block">
                  <span class="mb-1 block text-sm font-bold text-[#d4dc9a]">Password</span>
                  <input v-model="form.password" type="password" required autocomplete="current-password" placeholder="Masukkan password" class="w-full rounded-lg border-2 border-[#6F9435] bg-[#263D26] px-4 py-3 text-sm text-[#f0ead8] outline-none transition placeholder:text-[#8fa06a]/50 focus:border-[#EDD330] focus:ring-2 focus:ring-[#EDD330]/50" />
                </label>
                <span v-if="$page.props.errors.password" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.password }}</span>
                <span v-if="$page.props.errors.identity" class="mt-1 block text-xs font-bold text-[#ef4419]">{{ $page.props.errors.identity }}</span>
              </div>
            </template>

            <label v-if="!isRegistration" class="flex items-center gap-2 text-sm font-medium text-[#d4dc9a]">
              <input v-model="form.remember" type="checkbox" class="rounded border-2 border-[#6F9435] bg-[#263D26] text-[#EDD330]" /> Ingat saya
            </label>

            <button :disabled="form.processing || regForm.processing" :class="['group relative w-full overflow-hidden rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#EDD330] px-4 py-3 font-extrabold text-[#263D26] shadow-lg shadow-[#EDD330]/30 transition hover:shadow-xl hover:shadow-[#EDD330]/40 hover:-translate-y-0.5 disabled:cursor-wait border-2 border-[#EDD330]', (form.processing || regForm.processing) ? 'animate-pulse shadow-[#EDD330]/50' : '']">
              <span class="relative z-10 flex items-center justify-center gap-2">
                <svg v-if="form.processing || regForm.processing" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-5 w-5 animate-spin text-[#263D26]">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.5" opacity="0.25"></circle>
                  <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-if="form.processing || regForm.processing">
                  {{ isRegistration ? 'Mendaftar' : 'Masuk' }}
                  <span class="inline-flex gap-0.5">
                    <span class="animate-bounce inline-block h-1.5 w-1.5 rounded-full bg-[#263D26]" style="animation-delay: 0ms;"></span>
                    <span class="animate-bounce inline-block h-1.5 w-1.5 rounded-full bg-[#263D26]" style="animation-delay: 150ms;"></span>
                    <span class="animate-bounce inline-block h-1.5 w-1.5 rounded-full bg-[#263D26]" style="animation-delay: 300ms;"></span>
                  </span>
                </span>
                <span v-else>{{ isRegistration ? 'Daftar Akun' : 'Masuk' }}</span>
                <svg v-if="!form.processing && !regForm.processing && !isRegistration" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4 transition group-hover:translate-x-1"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                <svg v-else-if="!form.processing && !regForm.processing && isRegistration" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4 transition group-hover:translate-x-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m-7.5-7.5H21" /></svg>
              </span>
            </button>
          </form>

          <div class="mt-4 text-center">
            <p class="text-sm text-[#8fa06a]">
              {{ isRegistration ? 'Sudah punya akun?' : 'Belum punya akun?' }}
              <button @click="toggleMode" class="font-bold text-[#EDD330] hover:underline">{{ isRegistration ? 'Masuk' : 'Daftar Akun' }}</button>
            </p>
          </div>

          <div v-if="!isRegistration && hasSavedCredentials" class="mt-3 rounded-lg border-2 border-[#EDD330]/40 bg-[#2d4a2d]/60 p-3 text-center text-sm font-medium text-[#EDD330]">
            Kredensial tersimpan — klik <span class="font-bold">Masuk</span> untuk melanjutkan.
          </div>

          <div class="mt-5 border-t-2 border-[#6F9435]/20 pt-5 text-center">
            <Link href="/" class="group inline-flex items-center gap-2 rounded-lg border-2 border-[#6F9435] px-6 py-2.5 text-sm font-bold text-[#d4dc9a] transition hover:-translate-y-0.5 hover:bg-[#6F9435]/20 hover:text-[#EDD330] hover:shadow-lg hover:shadow-[#6F9435]/20">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4 transition group-hover:-translate-x-1"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
              Kembali ke Halaman Guest
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

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
  const url = props.isRegistration ? '/login' : '/register';
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
    onError: () => {
      localStorage.removeItem(CREDENTIALS_STORAGE_KEY);
    },
  });
}

function submitRegister() {
  regForm.post('/register', {
    onSuccess: () => {
      regForm.reset();
    },
    onError: () => {
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