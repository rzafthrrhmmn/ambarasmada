<template>
  <AppLayout>
    <div class="mb-6">
      <p class="text-sm font-medium text-[#EDD330]">Akun Saya</p>
      <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Profil Anggota</h1>
      <p class="mt-1 text-sm text-[#8fa06a]">Perbarui data pribadi, foto profil, dan cetak KTA.</p>
    </div>

    <section class="grid gap-5 xl:grid-cols-3">
      <div class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435] xl:col-span-2">
        <h2 class="font-semibold text-[#f0ead8]">Data Profil</h2>
        <form @submit.prevent="form.patch('/profile', { onSuccess: () => { form.reset('foto'); } })" @change="form.clearErrors()" class="mt-4 grid gap-3 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="block"><span class="text-xs font-medium">Foto Profil</span></label>
            <div class="mt-1 flex items-center gap-4">
              <div class="h-16 w-16 rounded-lg border-2 border-[#6F9435] bg-[#263D26] overflow-hidden flex items-center justify-center">
                <img v-if="photoUrl" :src="photoUrl" alt="Foto" class="h-full w-full object-cover" />
                <span v-else class="text-xs text-[#8fa06a]">No foto</span>
              </div>
              <div>
                <input type="file" @change="form.foto = $event.target.files[0]" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-[#d4dc9a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#6F9435] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white" />
                <button v-if="member.user?.foto" type="button" @click="form.foto = null" class="mt-1 text-xs text-[#ef4419] hover:underline">Hapus foto</button>
                <p v-if="form.errors.foto" class="text-xs text-[#ef4419] mt-1">{{ form.errors.foto }}</p>
              </div>
            </div>
          </div>
          <label class="block"><span class="text-xs font-medium">Nama Lengkap</span><input v-model="form.nama_lengkap" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" /></label>
          <p v-if="form.errors.nama_lengkap" class="text-xs text-[#ef4419] mt-1">{{ form.errors.nama_lengkap }}</p>
          
          <label class="block"><span class="text-xs font-medium">Tempat Lahir</span><input v-model="form.tempat_lahir" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" /></label>
          <p v-if="form.errors.tempat_lahir" class="text-xs text-[#ef4419] mt-1">{{ form.errors.tempat_lahir }}</p>
          
          <label class="block"><span class="text-xs font-medium">Tanggal Lahir</span><input v-model="form.tanggal_lahir" type="date" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" /></label>
          <p v-if="form.errors.tanggal_lahir" class="text-xs text-[#ef4419] mt-1">{{ form.errors.tanggal_lahir }}</p>
          
          <label class="block"><span class="text-xs font-medium">Jenis Kelamin</span><select v-model="form.jenis_kelamin" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"><option value="">Pilih</option><option>Laki-laki</option><option>Perempuan</option></select></label>
          <p v-if="form.errors.jenis_kelamin" class="text-xs text-[#ef4419] mt-1">{{ form.errors.jenis_kelamin }}</p>
          
          <label class="block"><span class="text-xs font-medium">Nomor HP</span><input v-model="form.no_hp" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" /></label>
          <p v-if="form.errors.no_hp" class="text-xs text-[#ef4419] mt-1">{{ form.errors.no_hp }}</p>
          
          <label class="block"><span class="text-xs font-medium">NTA</span><input :value="member.nta || '-'" disabled class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#8fa06a] border-[#6F9435]" /></label>
          
          <label class="block"><span class="text-xs font-medium">Kelas</span><input v-model="form.kelas" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" /></label>
          <p v-if="form.errors.kelas" class="text-xs text-[#ef4419] mt-1">{{ form.errors.kelas }}</p>
          
          <label class="block"><span class="text-xs font-medium">Tingkatan</span><select v-model="form.tingkatan" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"><option>Tamu</option><option>Calon</option><option>Bantara</option><option>Laksana</option><option>Alumni</option></select></label>
          <p v-if="form.errors.tingkatan" class="text-xs text-[#ef4419] mt-1">{{ form.errors.tingkatan }}</p>
          
          <label class="block"><span class="text-xs font-medium">Tahun Lulus</span><input v-model.number="form.tahun_lulus" type="number" min="2000" max="2100" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" /></label>
          <p v-if="form.errors.tahun_lulus" class="text-xs text-[#ef4419] mt-1">{{ form.errors.tahun_lulus }}</p>
          
          <label class="block"><span class="text-xs font-medium">Status Aktif</span><select v-model="form.status_aktif" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"><option>Aktif</option><option>Non-Aktif</option><option>Alumni</option></select></label>
          <p v-if="form.errors.status_aktif" class="text-xs text-[#ef4419] mt-1">{{ form.errors.status_aktif }}</p>
          <div class="sm:col-span-2 flex items-end gap-3">
            <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-6 py-2.5 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50">Perbarui Profil</button>
            <button type="button" @click="printKTA" class="rounded-lg border-2 border-[#EDD330] px-4 py-2 text-sm font-semibold text-[#EDD330] transition hover:bg-[#EDD330]/10">Cetak KTA</button>
            <button type="button" @click="toggleKtaPreview" class="rounded-lg border-2 border-[#A7B92A] px-4 py-2 text-sm font-semibold text-[#A7B92A] transition hover:bg-[#A7B92A]/10">{{ showKtaPreview ? 'Sembunyikan Preview' : 'Tampilkan Preview' }}</button>
          </div>
        </form>
      </div>
      <aside class="space-y-4">
        <div class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435]">
          <p class="text-xs text-[#8fa06a]">Tingkatan</p>
          <p class="mt-1 text-xl font-bold">{{ member.tingkatan }}</p>
          <p class="mt-3 text-xs text-[#8fa06a]">Status keanggotaan</p>
          <p class="mt-1 text-lg font-semibold">{{ member.status_aktif }}</p>
        </div>
        <div class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435]">
          <p class="text-xs text-[#8fa06a]">Total presensi</p>
          <p class="mt-1 text-2xl font-bold">{{ member.attendances?.length || 0 }}</p>
        </div>
      </aside>
    </section>

    <section v-show="showKtaPreview" class="mt-6">
      <div class="rounded-2xl border border-[#EDD330]/40 bg-gradient-to-br from-[#335233] to-[#263D26] p-4 shadow-lg">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold text-[#f0ead8] flex items-center gap-2">
            <svg class="h-5 w-5 text-[#EDD330]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m-16.5 0a2.25 2.25 0 00-2.25 2.25v11.25A2.25 2.25 0 006 16.5h2.25m-9.75 0l9 9" /></svg>
            Preview KTA
          </h2>
          <button @click="toggleKtaPreview" class="text-xs text-[#8fa06a] hover:text-[#EDD330] transition">Tutup</button>
        </div>
        <div class="flex justify-center">
          <div ref="ktaPreviewCard" class="w-[340px] h-[200px] rounded-xl overflow-hidden shadow-2xl relative" style="font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;">
        <div class="absolute inset-0 bg-gradient-to-br from-[#1a2e1a] via-[#263D26] to-[#1a2e1a]">
          <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23EDD330%22 fill-opacity=%220.03%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50" />
        </div>
        
        <div class="absolute inset-0 border-2 border-[#EDD330]/60 rounded-xl pointer-events-none" />
        
        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-[#EDD330] via-[#A7B92A] to-[#EDD330]" />
        <div class="absolute bottom-0 left-0 right-0 h-2 bg-gradient-to-r from-[#EDD330] via-[#A7B92A] to-[#EDD330]" />
        
        <div class="relative z-10 p-4 h-full flex flex-col">
          <div class="flex items-start justify-between mb-3">
            <div class="text-left">
              <p class="text-[11px] font-extrabold text-[#EDD330] tracking-wider uppercase">{{ ambalan?.nama || 'AMBALAN PRAMUKA' }}</p>
              <p class="text-[8px] text-[#8fa06a] tracking-widest mt-0.5">KARTU TANDA ANGGOTA</p>
              <p class="text-[7px] text-[#6F9435] tracking-wider mt-0.5">Ekosistem Digital Ambalan UPT SMAN 2 Maros</p>
            </div>
            <div class="flex items-center gap-2">
              <span class="rounded-full border border-[#6F9435] bg-[#263D26]/80 px-2 py-1 text-[8px] font-bold text-[#EDD330] uppercase tracking-wider">{{ member.tingkatan }}</span>
              <span class="rounded-full bg-gradient-to-r from-[#EDD330] to-[#A7B92A] px-2 py-1 text-[8px] font-bold text-[#263D26] uppercase">{{ member.status_aktif }}</span>
            </div>
          </div>
          
          <div class="flex gap-3 flex-1 min-h-0">
            <div class="relative w-[80px] h-[80px] flex-shrink-0 rounded-lg overflow-hidden border-2 border-[#EDD330]/40 bg-[#1a2e1a] shadow-lg shadow-[#EDD330]/10">
              <div class="absolute inset-0 bg-gradient-to-br from-[#EDD330]/10 via-transparent to-[#A7B92A]/10" />
              <img v-if="member.user?.foto" :src="`/storage/${member.user.foto}`" alt="Foto" class="absolute inset-0 h-full w-full object-cover" />
              <div v-else class="absolute inset-0 flex items-center justify-center">
                <span class="text-3xl font-extrabold text-[#EDD330]/60">{{ (member.nama_lengkap || 'PA').charAt(0) }}</span>
              </div>
              <div class="absolute bottom-0 left-0 right-0 h-6 bg-gradient-to-t from-[#000]/80 to-transparent flex items-center justify-center">
                <span class="text-[7px] font-bold text-[#EDD330] tracking-widest">FOTO 3x4</span>
              </div>
            </div>
            
            <div class="flex-1 min-w-0 space-y-2.5 text-[#f0ead8]">
              <div class="flex items-baseline gap-2">
                <span class="text-xs font-bold text-[#EDD330] tracking-wide">NAMA</span>
                <span class="text-sm font-semibold leading-tight truncate">{{ member.nama_lengkap }}</span>
              </div>
              
              <div class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-1.5 text-[10px]">
                <span class="text-[#8fa06a] font-medium">TTL</span>
                <span class="text-[#d4dc9a]">{{ member.tempatlahir || '-' }}, {{ member.tanggallahir ? new Date(member.tanggallahir).toLocaleDateString('id-ID') : '-' }}</span>
                
                <span class="text-[#8fa06a] font-medium">Jenis Kelamin</span>
                <span class="text-[#d4dc9a]">{{ member.jeniskelamin || '-' }}</span>
                
                <span class="text-[#8fa06a] font-medium">NTA</span>
                <span class="text-[#EDD330] font-mono font-bold tracking-wider">{{ member.nta || '-' }}</span>
                
                <span class="text-[#8fa06a] font-medium">Angkatan</span>
                <span class="text-[#d4dc9a] font-medium">{{ member.angkatan || '-' }}</span>
                
                <span class="text-[#8fa06a] font-medium">Kelas</span>
                <span class="text-[#d4dc9a] font-medium">{{ member.kelas || '-' }}</span>
              </div>
            </div>
          </div>
          
          <div class="flex items-center justify-between pt-3 border-t border-[#EDD330]/20 mt-auto">
            <div class="text-left">
              <p class="text-[7px] text-[#6F9435] tracking-wider">Diterbitkan oleh</p>
              <p class="text-[9px] font-bold text-[#EDD330]">{{ ambalan?.nama || 'Ambalan Pramuka SMAN 2 Maros' }}</p>
            </div>
            <div class="text-right">
              <p class="text-[7px] text-[#6F9435] tracking-wider">Tanggal Cetak</p>
              <p class="text-[9px] font-mono text-[#d4dc9a]">{{ new Date().toLocaleDateString('id-ID') }}</p>
            </div>
          </div>
        </div>
        </div>
      </div>
    </div>
    </section>

    <section class="mt-6 print-only">
      <div ref="ktaCard" class="mx-auto w-[340px] h-[200px] rounded-xl overflow-hidden shadow-2xl relative" style="font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;">
        <div class="absolute inset-0 bg-gradient-to-br from-[#1a2e1a] via-[#263D26] to-[#1a2e1a]">
          <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23EDD330%22 fill-opacity=%220.03%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50" />
        </div>
        
        <div class="absolute inset-0 border-2 border-[#EDD330]/60 rounded-xl pointer-events-none" />
        
        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-[#EDD330] via-[#A7B92A] to-[#EDD330]" />
        <div class="absolute bottom-0 left-0 right-0 h-2 bg-gradient-to-r from-[#EDD330] via-[#A7B92A] to-[#EDD330]" />
        
        <div class="relative z-10 p-4 h-full flex flex-col">
          <div class="flex items-start justify-between mb-3">
            <div class="text-left">
              <p class="text-[11px] font-extrabold text-[#EDD330] tracking-wider uppercase">{{ ambalan?.nama || 'AMBALAN PRAMUKA' }}</p>
              <p class="text-[8px] text-[#8fa06a] tracking-widest mt-0.5">KARTU TANDA ANGGOTA</p>
              <p class="text-[7px] text-[#6F9435] tracking-wider mt-0.5">Ekosistem Digital Ambalan UPT SMAN 2 Maros</p>
            </div>
            <div class="flex items-center gap-2">
              <span class="rounded-full border border-[#6F9435] bg-[#263D26]/80 px-2 py-1 text-[8px] font-bold text-[#EDD330] uppercase tracking-wider">{{ member.tingkatan }}</span>
              <span class="rounded-full bg-gradient-to-r from-[#EDD330] to-[#A7B92A] px-2 py-1 text-[8px] font-bold text-[#263D26] uppercase">{{ member.status_aktif }}</span>
            </div>
          </div>
          
          <div class="flex gap-3 flex-1 min-h-0">
            <div class="relative w-[80px] h-[80px] flex-shrink-0 rounded-lg overflow-hidden border-2 border-[#EDD330]/40 bg-[#1a2e1a] shadow-lg shadow-[#EDD330]/10">
              <div class="absolute inset-0 bg-gradient-to-br from-[#EDD330]/10 via-transparent to-[#A7B92A]/10" />
              <img v-if="member.user?.foto" :src="`/storage/${member.user.foto}`" alt="Foto" class="absolute inset-0 h-full w-full object-cover" />
              <div v-else class="absolute inset-0 flex items-center justify-center">
                <span class="text-3xl font-extrabold text-[#EDD330]/60">{{ (member.nama_lengkap || 'PA').charAt(0) }}</span>
              </div>
              <div class="absolute bottom-0 left-0 right-0 h-6 bg-gradient-to-t from-[#000]/80 to-transparent flex items-center justify-center">
                <span class="text-[7px] font-bold text-[#EDD330] tracking-widest">FOTO 3x4</span>
              </div>
            </div>
            
            <div class="flex-1 min-w-0 space-y-2.5 text-[#f0ead8]">
              <div class="flex items-baseline gap-2">
                <span class="text-xs font-bold text-[#EDD330] tracking-wide">NAMA</span>
                <span class="text-sm font-semibold leading-tight truncate">{{ member.nama_lengkap }}</span>
              </div>
              
              <div class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-1.5 text-[10px]">
                <span class="text-[#8fa06a] font-medium">TTL</span>
                <span class="text-[#d4dc9a]">{{ member.tempatlahir || '-' }}, {{ member.tanggallahir ? new Date(member.tanggallahir).toLocaleDateString('id-ID') : '-' }}</span>
                
                <span class="text-[#8fa06a] font-medium">Jenis Kelamin</span>
                <span class="text-[#d4dc9a]">{{ member.jeniskelamin || '-' }}</span>
                
                <span class="text-[#8fa06a] font-medium">NTA</span>
                <span class="text-[#EDD330] font-mono font-bold tracking-wider">{{ member.nta || '-' }}</span>
                
                <span class="text-[#8fa06a] font-medium">Angkatan</span>
                <span class="text-[#d4dc9a] font-medium">{{ member.angkatan || '-' }}</span>
                
                <span class="text-[#8fa06a] font-medium">Kelas</span>
                <span class="text-[#d4dc9a] font-medium">{{ member.kelas || '-' }}</span>
              </div>
            </div>
          </div>
          
          <div class="flex items-center justify-between pt-3 border-t border-[#EDD330]/20 mt-auto">
            <div class="text-left">
              <p class="text-[7px] text-[#6F9435] tracking-wider">Diterbitkan oleh</p>
              <p class="text-[9px] font-bold text-[#EDD330]">{{ ambalan?.nama || 'Ambalan Pramuka SMAN 2 Maros' }}</p>
            </div>
            <div class="text-right">
              <p class="text-[7px] text-[#6F9435] tracking-wider">Tanggal Cetak</p>
              <p class="text-[9px] font-mono text-[#d4dc9a]">{{ new Date().toLocaleDateString('id-ID') }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="mt-6 rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435]">
      <h2 class="font-semibold text-[#f0ead8]">Riwayat presensi</h2>
      <div class="mt-4 space-y-2">
        <div v-for="item in member.attendances || []" :key="item.id" class="flex items-center justify-between rounded-xl bg-[#263D26] p-3 text-sm">
          <span>{{ item.session?.nama || formatDate(item.created_at) }}</span>
          <span class="rounded-full bg-[#335233] px-2.5 py-1 text-xs text-[#EDD330]">{{ item.keterangan }}</span>
        </div>
        <p v-if="!member.attendances?.length" class="text-sm text-[#8fa06a]">Belum ada presensi.</p>
      </div>
    </section>
  </AppLayout>
</template>
<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

const props = defineProps({ member: Object });
const page = usePage();
const ambalan = computed(() => page.props.ambalan || null);
const ktaCard = ref(null);
const ktaPreviewCard = ref(null);
const member = computed(() => props.member);
const showKtaPreview = ref(false);

const form = useForm({
  nama_lengkap: props.member?.nama_lengkap || '',
  tempat_lahir: props.member?.tempat_lahir || '',
  tanggal_lahir: props.member?.tanggal_lahir || '',
  jenis_kelamin: props.member?.jenis_kelamin || '',
  kelas: props.member?.kelas || '',
  tingkatan: props.member?.tingkatan || '',
  tahun_lulus: props.member?.tahun_lulus ?? null,
  status_aktif: props.member?.status_aktif || '',
  no_hp: props.member?.no_hp || '',
  foto: null,
});

const photoUrl = computed(() => {
  if (form.foto) {
    return URL.createObjectURL(form.foto);
  }
  if (member.value?.user?.foto) {
    return `/storage/${member.value.user.foto}`;
  }
  return null;
});

function formatDate(value) { return value ? new Date(value).toLocaleDateString('id-ID') : '-'; }

function printKTA() {
  const card = ktaPreviewCard.value || ktaCard.value;
  if (card) {
    const printWindow = window.open('', '_blank');
    if (printWindow) {
      const cardHtml = card.outerHTML;
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>Cetak KTA</title>
          <style>
            @page {
              size: 86mm 54mm;
              margin: 0;
            }
            * {
              -webkit-print-color-adjust: exact;
              print-color-adjust: exact;
            }
            body {
              margin: 0;
              padding: 0;
              font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
              display: flex;
              justify-content: center;
              align-items: center;
              min-height: 100vh;
              background: #263D26;
            }
            .kta-wrapper {
              width: 86mm;
              height: 54mm;
              overflow: hidden;
              transform-origin: top left;
            }
            @media print {
              body { padding: 0; margin: 0; background: #263D26; }
              .kta-wrapper { width: 86mm; height: 54mm; }
              @page { margin: 0; size: 86mm 54mm; }
            }
          </style>
        </head>
        <body>
          <div class="kta-wrapper">${cardHtml}</div>
        </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.focus();
      setTimeout(() => {
        printWindow.print();
      }, 300);
    }
  }
}

function toggleKtaPreview() {
  showKtaPreview.value = !showKtaPreview.value;
}
</script>
<style scoped>
.print-only {
  display: none;
}
@media print {
  .print-only {
    display: block !important;
  }
  body * {
    visibility: hidden;
  }
  .print-only, .print-only * {
    visibility: visible !important;
  }
  .print-only {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}
</style>
