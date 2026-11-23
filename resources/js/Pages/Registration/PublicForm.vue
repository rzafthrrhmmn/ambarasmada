<template>
  <div class="min-h-screen bg-hutan-800 font-sans flex items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-hutan-700/80 backdrop-blur-sm rounded-2xl border border-daun-400/30 shadow-2xl p-8 sm:p-12">
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-daun-500/20 border border-daun-500/30 mb-4">
          <AppIcon name="users" class="h-4 w-4 text-emas-400" />
          <span class="text-xs font-bold uppercase tracking-wider text-daun-100">Pendaftaran Anggota Baru</span>
        </div>
        <h2 class="text-2xl font-bold text-krem-100 mb-2">Daftar Menjadi Anggota</h2>
        <p class="text-krem-300 text-sm">
          Isi formulir berikut untuk bergabung dengan Ambalan {{ ambalanNama }}.
          Data Anda akan diverifikasi oleh Pembina sebelum diaktifkan.
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-krem-300 mb-1">Nama Lengkap</label>
            <input v-model="form.nama_lengkap" type="text" required class="field w-full" placeholder="Nama lengkap sesuai KTP" />
            <span v-if="errors.nama_lengkap" class="mt-1 block text-xs text-[#ef4419]">{{ errors.nama_lengkap }}</span>
          </div>
          <div>
            <label class="block text-xs font-medium text-krem-300 mb-1">Email</label>
            <input v-model="form.email" type="email" required class="field w-full" placeholder="email@contoh.com" />
            <span v-if="errors.email" class="mt-1 block text-xs text-[#ef4419]">{{ errors.email }}</span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-krem-300 mb-1">Username</label>
            <input v-model="form.username" type="text" required class="field w-full" placeholder="Nama pengguna" />
            <span v-if="errors.username" class="mt-1 block text-xs text-[#ef4419]">{{ errors.username }}</span>
          </div>
          <div>
            <label class="block text-xs font-medium text-krem-300 mb-1">Password</label>
            <input v-model="form.password" type="password" required class="field w-full" placeholder="Minimal 8 karakter" />
            <span v-if="errors.password" class="mt-1 block text-xs text-[#ef4419]">{{ errors.password }}</span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-krem-300 mb-1">Tempat Lahir</label>
            <input v-model="form.tempat_lahir" type="text" required class="field w-full" />
            <span v-if="errors.tempat_lahir" class="mt-1 block text-xs text-[#ef4419]">{{ errors.tempat_lahir }}</span>
          </div>
          <div>
            <label class="block text-xs font-medium text-krem-300 mb-1">Tanggal Lahir</label>
            <input v-model="form.tanggal_lahir" type="date" required class="field w-full" />
            <span v-if="errors.tanggal_lahir" class="mt-1 block text-xs text-[#ef4419]">{{ errors.tanggal_lahir }}</span>
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-krem-300 mb-1">Jenis Kelamin</label>
          <select v-model="form.jenis_kelamin" class="field w-full">
            <option value="Laki-laki">Laki-laki</option>
            <option value="Perempuan">Perempuan</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-krem-300 mb-1">Kelas</label>
            <input v-model="form.kelas" type="text" required class="field w-full" placeholder="Contoh: 10, 11, 12" />
            <span v-if="errors.kelas" class="mt-1 block text-xs text-[#ef4419]">{{ errors.kelas }}</span>
          </div>
          <div>
            <label class="block text-xs font-medium text-krem-300 mb-1">Tingkatan</label>
            <select v-model="form.tingkatan" class="field w-full">
              <option value="Tamu">Tamu</option>
              <option value="Bantara">Bantara</option>
              <option value="Laksana">Laksana</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-krem-300 mb-1">Nomor HP</label>
          <input v-model="form.no_hp" type="tel" required class="field w-full" />
          <span v-if="errors.no_hp" class="mt-1 block text-xs text-[#ef4419]">{{ errors.no_hp }}</span>
        </div>

        <div>
          <label class="block text-xs font-medium text-krem-300 mb-1">Riwayat Pramuka (opsional)</label>
          <textarea v-model="form.riwayat_pramuka" rows="2" class="field w-full" placeholder="Apakah Anda pernah mengikuti pramuka sebelumnya?" />
        </div>

        <div>
          <label class="block text-xs font-medium text-krem-300 mb-1">Alasan Bergabung</label>
          <textarea v-model="form.alasan_gabung" rows="3" required class="field w-full" placeholder="Mengapa Anda ingin menjadi anggota ambalan?" />
          <span v-if="errors.alasan_gabung" class="mt-1 block text-xs text-[#ef4419]">{{ errors.alasan_gabung }}</span>
        </div>

        <div>
          <label class="block text-xs font-medium text-krem-300 mb-1">Konfirmasi Password</label>
          <input v-model="form.password_confirmation" type="password" required class="field w-full" />
          <span v-if="errors.password_confirmation" class="mt-1 block text-xs text-[#ef4419]">{{ errors.password_confirmation }}</span>
        </div>

        <button type="submit" :disabled="processing" class="w-full btn-primary py-3">
          {{ processing ? 'Mengirim...' : 'Daftar Sekarang' }}
        </button>

        <p class="mt-6 text-center text-xs text-krem-300">
          Sudah punya akun?
          <InertiaLink href="/login" class="text-emas-400 hover:underline font-medium">Masuk di sini</InertiaLink>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import { Link as InertiaLink } from '@inertiajs/vue3';

const page = usePage();

const ambalanNama = computed(() => page.props?.ambalan?.nama || 'Ambalan UPT SMAN 2 Maros');

const form = useForm({
  nama_lengkap: '',
  email: '',
  username: '',
  password: '',
  password_confirmation: '',
  tempat_lahir: '',
  tanggal_lahir: '',
  jenis_kelamin: 'Laki-laki',
  kelas: '',
  tingkatan: 'Tamu',
  no_hp: '',
  riwayat_pramuka: '',
  alasan_gabung: '',
});

const errors = computed(() => form.errors);
const processing = computed(() => form.processing);

function submit() {
  form.post('/daftar', {
    onSuccess: () => {
      form.reset();
    },
    onError: () => {},
  });
}
</script>
