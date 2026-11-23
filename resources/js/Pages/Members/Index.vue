<template>
  <AppLayout>
    <PageHeader
      eyebrow="Manajemen Anggota"
      title="Direktori Anggota"
      subtitle="Kelola anggota, angkatan, dan role secara menyeluruh."
    >
      <template #actions>
        <div class="flex items-center gap-2">
          <button v-if="canManageAngkatan" @click="showAngkatan = true" class="btn-ghost btn-sm">
            <AppIcon name="calendar" class="h-4 w-4" />
            Kelola Angkatan
          </button>
          <button v-if="canManage" type="button" class="btn-accent btn-sm" @click="showCreate = true">
            <AppIcon name="plus" class="h-4 w-4" />
            Tambah anggota
          </button>
        </div>
      </template>
    </PageHeader>

    <!-- Bulk Role Change -->
    <div v-if="canBulkRole" class="mb-6 rounded-xl border-2 border-emas-400/30 bg-hutan-600 p-5 shadow-lg border-emas-400/30">
      <div class="mb-3 flex items-center gap-2">
        <AppIcon name="users" class="h-5 w-5 text-emas-400" />
        <h3 class="text-sm font-bold text-emas-400">Ubah Role Massal</h3>
      </div>
      <p class="text-xs text-krem-300 mb-3">Ubah role seluruh anggota satu angkatan sekaligus.</p>
      <form @submit.prevent="applyBulkChange" class="grid gap-3 sm:grid-cols-4 items-end">
        <select v-model="bulkAngkatan" required class="field">
          <option value="">Pilih angkatan</option>
          <option v-for="a in angkatanList" :key="a.nomor" :value="a.nomor">{{ a.nomor }} - {{ a.angkatan }}{{ a.is_active ? '' : ' (Arsip)' }}</option>
        </select>
        <select v-model="bulkNewRole" required class="field">
          <option value="">Role baru</option>
          <option value="Anggota">Anggota</option>
          <option value="Pengurus">Pengurus</option>
          <option value="Pembina">Pembina</option>
          <option value="Alumni">Alumni</option>
        </select>
        <button type="submit" :disabled="!bulkAngkatan || !bulkNewRole" class="btn-accent">
          Terapkan
        </button>
      </form>
      <p v-if="bulkMessage" :class="bulkSuccess ? 'text-daun-400' : 'text-ef4419'" class="mt-2 text-xs font-medium">{{ bulkMessage }}</p>
    </div>

    <!-- Pengurus Management (Pembina) -->
    <div v-if="isPembina" class="mb-6 rounded-xl border-2 border-daun-400/40 bg-hutan-600 p-5 shadow-lg border-daun-400/40">
      <div class="mb-3 flex items-center gap-2">
        <AppIcon name="star" class="h-5 w-5 text-daun-400" />
        <h3 class="text-sm font-bold text-emas-400">Kelola Posisi Pengurus</h3>
      </div>
      <p class="text-xs text-krem-300 mb-3">Tetapkan posisi untuk setiap anggota pengurus satu angkatan.</p>
      <form @submit.prevent="applyBulkPosition" class="mb-4 grid gap-3 sm:grid-cols-4 items-end">
        <select v-model="bulkPosAngkatan" required class="field">
          <option value="">Pilih angkatan</option>
          <option v-for="a in angkatanList" :key="a.nomor" :value="a.nomor">{{ a.nomor }} - {{ a.angkatan }}{{ a.is_active ? '' : ' (Arsip)' }}</option>
        </select>
        <select v-model="bulkPosPosition" required class="field">
          <option value="">Pilih posisi</option>
          <option v-for="p in positions" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <button type="submit" :disabled="!bulkPosAngkatan || !bulkPosPosition" class="btn-accent">
          Tetapkan Massal
        </button>
      </form>
      <p v-if="bulkPosMessage" :class="bulkPosSuccess ? 'text-daun-400' : 'text-ef4419'" class="mt-2 text-xs font-medium">{{ bulkPosMessage }}</p>

      <div class="mt-5">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h4 class="text-sm font-bold text-emas-400">Daftar Pengurus</h4>
            <p class="text-[11px] text-krem-300">Semua anggota dengan role Pengurus beserta posisinya.</p>
          </div>
          <span class="rounded-full bg-daun-500/20 px-2.5 py-0.5 text-[10px] font-bold text-emas-400">{{ pengurus.length }} pengurus</span>
        </div>

        <div class="overflow-hidden rounded-lg border border-daun-400/30 bg-hutan-800">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-daun-400/20">
              <thead class="bg-hutan-700">
                <tr>
                  <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-krem-300">No</th>
                  <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-krem-300">Nama</th>
                  <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-krem-300">NTA / Angkatan</th>
                  <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-krem-300">Posisi</th>
                  <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-krem-300">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-daun-400/20">
                <tr v-for="(p, idx) in pengurus" :key="p.id" class="hover:bg-daun-400/10 transition-colors">
                  <td class="whitespace-nowrap px-4 py-3 text-sm text-[#d4dc9a]">{{ idx + 1 }}</td>
                  <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-krem-100">{{ p.nama_lengkap }}</td>
                  <td class="px-4 py-3 text-sm text-[#d4dc9a]">
                    {{ p.nta_username || p.nta || '-' }}
                    <br><span class="text-[10px] text-krem-300">Angkatan {{ p.angkatan || '-' }}</span>
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <span class="rounded-full bg-daun-500/20 px-2 py-1 text-[10px] font-bold text-emas-400">{{ p.position_label }}</span>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                    <button v-if="p.memberPositions?.[0]" type="button" @click="removePosition(p)" class="text-ef4419 hover:underline mr-2 text-xs">Hapus Posisi</button>
                    <button v-else type="button" @click="editMember(p)" class="text-emas-400 hover:underline text-xs">
                      <AppIcon name="edit" class="h-3 w-3 inline" />
                    </button>
                  </td>
                </tr>
                <tr v-if="!pengurus.length">
                  <td colspan="5" class="empty-cell">Belum ada anggota dengan role Pengurus.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Logo Management (Pembina) -->
    <div v-if="isPembina" class="mb-6 rounded-xl border-2 border-daun-400/40 bg-hutan-600 p-5 shadow-lg border-daun-400/40">
      <div class="flex items-center justify-between">
        <div>
          <h4 class="text-sm font-bold text-emas-400">Logo Ambalan</h4>
          <p class="text-[11px] text-krem-300">Perbarui logo yang tampil di seluruh situs.</p>
        </div>
        <button @click="showLogoUpload = true" class="btn-ghost btn-sm">Kelola Logo</button>
      </div>
    </div>

    <!-- Search & Filter -->
    <form @submit.prevent="searchMembers" class="mb-6 grid gap-3 rounded-xl border border-daun-400/40 bg-hutan-600 p-4 shadow-lg border-daun-400/40 sm:grid-cols-4 items-end">
      <input v-model="filters.search" placeholder="Cari nama anggota..." class="field" />
      <select v-model="filters.status" class="field">
        <option value="">Semua status</option>
        <option>Aktif</option>
        <option>Non-Aktif</option>
        <option>Alumni</option>
      </select>
      <button class="btn-accent">
        <AppIcon name="search" class="h-4 w-4" />
        Filter
      </button>
    </form>

    <!-- Members Table -->
    <div class="overflow-hidden rounded-xl border border-daun-400/30 bg-hutan-600 shadow-lg border-daun-400/30">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-daun-400/20">
          <thead class="bg-hutan-700">
            <tr>
              <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-krem-300">Anggota</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-krem-300">NTA / Angkatan</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-krem-300">Kelas</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-krem-300">Tingkatan</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-krem-300">Status</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-krem-300">Posisi</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-krem-300">Role</th>
              <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-krem-300">Aksi</th>
            </tr>
          </thead>
          <tbody v-if="members && members.data" class="divide-y divide-daun-400/20">
            <tr v-for="member in members.data" :key="member.id" class="hover:bg-daun-400/10 transition-colors">
              <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-krem-100">{{ member.nama_lengkap }}</td>
              <td class="px-4 py-3 text-sm text-[#d4dc9a]">
                {{ member.nta_username || member.nta || '-' }}
                <br><span class="text-[10px] text-krem-300">Angkatan {{ member.angkatan || '-' }}</span>
              </td>
              <td class="px-4 py-3 text-sm text-[#d4dc9a]">{{ member.kelas }}</td>
              <td class="px-4 py-3 text-sm"><span class="rounded-full bg-hutan-600 px-2 py-1 text-[10px] font-bold text-emas-400">{{ member.tingkatan }}</span></td>
              <td class="px-4 py-3 text-sm"><span class="rounded-full bg-daun-400/20 px-2 py-1 text-[10px] font-bold text-daun-400">{{ member.status_aktif }}</span></td>
              <td class="px-4 py-3 text-sm"><span class="rounded-full bg-daun-500/20 px-2 py-1 text-[10px] font-bold text-emas-400">{{ member.position_label }}</span></td>
              <td class="px-4 py-3 text-sm"><span class="rounded-full bg-daun-500/20 px-2 py-1 text-[10px] font-bold text-emas-400">{{ member.user?.role || '-' }}</span></td>
              <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                <template v-if="isPembina">
                  <button v-if="member.memberPositions?.[0]" type="button" @click="removePosition(member)" class="text-ef4419 hover:underline mr-2 text-xs">Hapus Posisi</button>
                  <button type="button" @click="editMember(member)" class="text-emas-400 hover:underline text-xs">
                    <AppIcon name="edit" class="h-3 w-3 inline" />
                  </button>
                </template>
                <button v-else type="button" @click="editMember(member)" class="text-emas-400 hover:underline text-xs">
                  <AppIcon name="edit" class="h-3 w-3 inline" />
                </button>
              </td>
            </tr>
            <tr v-if="!members.data.length">
              <td colspan="8" class="empty-cell">Data anggota belum tersedia.</td>
            </tr>
          </tbody>
          <tbody v-else class="divide-y divide-daun-400/20">
            <tr>
              <td colspan="8" class="empty-cell">Memuat data anggota...</td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="members.links" class="border-t border-daun-400/20 p-3 border-daun-400/20" />
    </div>

    <!-- Modals -->
    <Modal v-if="showAngkatan" title="Kelola Angkatan" @close="showAngkatan = false">
      <div class="space-y-5">
        <form @submit.prevent="addAngkatan" class="rounded-xl border border-daun-400/40 bg-hutan-800 p-5 shadow-lg">
          <h4 class="text-sm font-bold text-emas-400 mb-3">Tambah angkatan</h4>
          <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div class="space-y-1">
              <label class="block text-[10px] font-bold uppercase text-krem-300">Tahun angkatan</label>
              <input v-model="newAngkatanTahun" required inputmode="numeric" maxlength="4" placeholder="2026" class="field" />
            </div>
            <div class="space-y-1">
              <label class="block text-[10px] font-bold uppercase text-krem-300">Nomor angkatan</label>
              <input v-model="newAngkatanNomor" required inputmode="numeric" maxlength="3" placeholder="018" class="field" />
            </div>
          </div>
          <div class="space-y-1 mt-3">
            <label class="block text-[10px] font-bold uppercase text-krem-300">Nama angkatan</label>
            <input v-model="newAngkatanNama" required placeholder="Angkatan 018 - 2026" class="field" />
          </div>
          <div class="mt-3 flex flex-wrap gap-4 text-sm text-[#d4dc9a]">
            <label class="flex items-center gap-2"><input v-model="newAngkatanActive" type="checkbox" class="accent-daun-400" /> Aktif</label>
            <label class="flex items-center gap-2"><input v-model="newAngkatanCurrent" type="checkbox" class="accent-daun-400" /> Jadikan angkatan berjalan</label>
          </div>
          <button type="submit" class="mt-4 btn-accent">Tambah Angkatan</button>
        </form>

        <div class="space-y-2">
          <div v-for="a in angkatanList" :key="a.id" class="flex items-center justify-between gap-3 rounded-lg border border-daun-400/30 bg-hutan-800 p-3">
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-krem-100">{{ a.nomor }} - {{ a.angkatan }} - {{ a.nama }}</p>
              <p class="mt-1 text-[10px] text-krem-300">
                {{ a.members_count ?? 0 }} anggota
                <span v-if="a.is_current" class="ml-2 rounded-full bg-emas-400/20 px-2 py-0.5 font-bold text-emas-400">Berjalan</span>
                <span v-if="!a.is_active" class="ml-2 rounded-full bg-ef4419/20 px-2 py-0.5 font-bold text-ef4419">Arsip</span>
              </p>
            </div>
            <div class="flex shrink-0 gap-2">
              <button type="button" @click="openEditAngkatan(a)" class="rounded border border-daun-400 px-2 py-1 text-xs text-[#d4dc9a] transition hover:bg-daun-400/20">Edit</button>
              <button v-if="a.is_active" type="button" @click="archiveAngkatan(a)" class="rounded border border-ef4419/50 px-2 py-1 text-xs text-ef4419 transition hover:bg-ef4419/10">Arsipkan</button>
            </div>
          </div>
          <p v-if="!angkatanList.length" class="empty-inline">Belum ada angkatan.</p>
        </div>
      </div>
    </Modal>

    <Modal v-if="editingAngkatan" title="Edit Angkatan" @close="editingAngkatan = null">
      <form @submit.prevent="saveEditedAngkatan" class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-2">
          <div class="space-y-1">
            <label class="block text-[10px] font-bold uppercase text-krem-300">Tahun angkatan</label>
            <input v-model="editAngkatanTahun" required inputmode="numeric" maxlength="4" class="field" />
          </div>
          <div class="space-y-1">
            <label class="block text-[10px] font-bold uppercase text-krem-300">Nomor angkatan</label>
            <input v-model="editAngkatanNomor" required inputmode="numeric" maxlength="3" class="field" />
          </div>
        </div>
        <div class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Nama angkatan</label>
          <input v-model="editAngkatanNama" required class="field" />
        </div>
        <div class="flex flex-wrap gap-4 text-sm text-[#d4dc9a]">
          <label class="flex items-center gap-2"><input v-model="editAngkatanActive" type="checkbox" class="accent-daun-400" /> Aktif</label>
          <label class="flex items-center gap-2"><input v-model="editAngkatanCurrent" type="checkbox" class="accent-daun-400" /> Angkatan berjalan</label>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" @click="editingAngkatan = null" class="btn-ghost btn-sm">Batal</button>
          <button type="submit" class="btn-accent btn-sm">Simpan</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showLogoUpload" title="Kelola Logo Ambalan" @close="showLogoUpload = false">
      <div class="space-y-4">
        <div class="flex items-center gap-4">
          <img v-if="$page.props.ambalan?.logo_url" :src="$page.props.ambalan.logo_url" alt="Logo" class="h-16 w-16 rounded-lg object-contain border border-daun-400 bg-hutan-800" />
          <span v-else class="flex h-16 w-16 items-center justify-center rounded-lg bg-gradient-to-br from-daun-400 to-emas-400 text-xl font-extrabold text-[#263D26]">P</span>
        </div>
        <div class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Upload logo baru</label>
          <input type="file" @change="logoFile = $event.target.files[0]" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-[#d4dc9a] file:mr-3 file:rounded-lg file:border-0 file:bg-daun-500 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white" />
        </div>
        <div class="flex gap-2">
          <button @click="submitLogo" :disabled="!logoFile" class="btn-accent btn-sm">Simpan Logo</button>
          <button @click="deleteLogo" :disabled="!$page.props.ambalan?.logo_path" class="btn-danger-solid btn-sm disabled:opacity-30">Hapus Logo</button>
        </div>
      </div>
    </Modal>

    <Modal v-if="showCreate" :title="editing ? 'Edit anggota' : 'Tambah anggota'" @close="resetForm">
      <form @submit.prevent="submitForm" class="grid gap-4 sm:grid-cols-2">
        <section class="sm:col-span-2 rounded-xl border border-daun-400/30 bg-hutan-600 p-5 shadow-lg border-daun-400/30">
          <h4 class="text-sm font-bold text-emas-400 mb-3">Preview NTA</h4>
          <div class="mt-3 grid gap-3 sm:grid-cols-3">
            <div class="space-y-1">
              <label class="block text-[10px] font-bold uppercase text-emas-400">Prefix Gudep</label>
              <input :value="gudepPrefix" disabled class="field text-krem-100" />
            </div>
            <div class="space-y-1">
              <label class="block text-[10px] font-bold uppercase text-emas-400">Kode Angkatan</label>
              <input :value="form.angkatan" disabled class="field text-krem-100" />
            </div>
            <div class="space-y-1">
              <label class="block text-[10px] font-bold uppercase text-emas-400">Nomor Urut</label>
              <input value="[Otomatis oleh Sistem]" readonly class="field text-krem-300" />
            </div>
          </div>
          <p class="mt-3 break-all text-sm font-mono font-bold text-emas-400">{{ previewNta }}</p>
        </section>

        <div class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Ambalan</label>
          <select v-model="form.ambalan_id" required class="field">
            <option v-for="ambalan in ambalans" :key="ambalan.id" :value="ambalan.id">{{ ambalan.nama }}</option>
          </select>
        </div>

        <div class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Angkatan</label>
          <select v-model="form.angkatan" required class="field">
            <option v-for="a in availableAngkatan" :key="a.nomor" :value="a.nomor">Angkatan {{ a.nomor }} ({{ a.angkatan }})</option>
            <option v-if="editing && form.angkatan && !availableAngkatan.some(a => a.nomor === form.angkatan)" :value="form.angkatan">Angkatan {{ form.angkatan }} (arsip)</option>
          </select>
        </div>

        <div class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Nama lengkap</label>
          <input v-model="form.nama_lengkap" required class="field" />
        </div>

        <div class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Tempat lahir</label>
          <input v-model="form.tempatlahir" class="field" />
        </div>

        <div class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Tanggal lahir</label>
          <input v-model="form.tanggallahir" type="date" class="field" />
        </div>

        <div class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Jenis kelamin</label>
          <select v-model="form.jeniskelamin" class="field">
            <option value="">Pilih</option>
            <option>Laki-laki</option>
            <option>Perempuan</option>
          </select>
        </div>

        <div class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Kelas</label>
          <input v-model="form.kelas" required class="field" />
        </div>

        <div class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Tingkatan</label>
          <select v-model="form.tingkatan" required class="field">
            <option>Tamu</option>
            <option>Calon</option>
            <option>Bantara</option>
            <option>Laksana</option>
            <option>Alumni</option>
          </select>
        </div>

        <div class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Status</label>
          <select v-model="form.status_aktif" required class="field">
            <option>Aktif</option>
            <option>Non-Aktif</option>
            <option>Alumni</option>
          </select>
        </div>

        <div class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Nomor HP</label>
          <input v-model="form.no_hp" class="field" />
        </div>

        <div v-if="!editing" class="space-y-1">
          <label class="block text-[10px] font-bold uppercase text-krem-300">Password akun</label>
          <input v-model="form.password" type="password" required minlength="8" class="field" />
        </div>

        <div class="flex items-end gap-2 sm:col-span-2">
          <button v-if="!editing" type="button" @click="generatePassword" class="btn-ghost btn-sm">
            <AppIcon name="refresh" class="h-4 w-4" />
            Generate Password
          </button>
          <span class="flex-1"></span>
          <button type="button" @click="resetForm" class="btn-ghost btn-sm">Batal</button>
          <button :disabled="form.processing" class="btn-accent btn-sm disabled:opacity-60">Simpan</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { useAccess } from '@/Composables/useAccess.js';

const props = defineProps({
  members: Object,
  filters: Object,
  ambalans: Array,
  angkatanOptions: Array,
  angkatanList: Array,
  gudepPrefix: String,
  positions: Array,
  pengurus: Array,
});
const page = usePage();

const { can, user, isPembina } = useAccess(page);
const canManage = computed(() => can('members.manage'));
const canBulkRole = computed(() => can('members.role.bulk_update'));
const canManageAngkatan = computed(() => can('members.angkatan.manage'));

const showCreate = ref(false);
const showAngkatan = ref(false);
const showLogoUpload = ref(false);
const logoFile = ref(null);
const editing = ref(null);
const editingAngkatan = ref(null);
const bulkAngkatan = ref('');
const bulkNewRole = ref('');
const bulkMessage = ref('');
const bulkSuccess = ref(false);
const newAngkatanTahun = ref('');
const newAngkatanNomor = ref('');
const newAngkatanNama = ref('');
const newAngkatanActive = ref(true);
const newAngkatanCurrent = ref(false);
const editAngkatanTahun = ref('');
const editAngkatanNomor = ref('');
const editAngkatanNama = ref('');
const editAngkatanActive = ref(true);
const editAngkatanCurrent = ref(false);

const filters = reactive({ search: props.filters?.search || '', status: props.filters?.status || '' });
const availableAngkatan = computed(() => (props.angkatanList || []).filter(a => a.is_active).sort((a, b) => a.nomor.localeCompare(b.nomor)));
const form = useForm({
  ambalan_id: props.ambalans?.[0]?.id || null,
  angkatan: availableAngkatan.value[0]?.nomor || props.angkatanOptions?.[0] || '',
  nama_lengkap: '',
  tempatlahir: '',
  tanggallahir: '',
  jeniskelamin: '',
  kelas: '',
  tingkatan: 'Tamu',
  status_aktif: 'Aktif',
  no_hp: '',
  password: '',
});
const previewNta = computed(() => `${props.gudepPrefix || '31082008'}.${form.angkatan || '001'}.[Otomatis oleh Sistem]`);
const bulkPosAngkatan = ref('');
const bulkPosPosition = ref('');
const bulkPosMessage = ref('');
const bulkPosSuccess = ref(false);

function searchMembers() {
  router.get('/members', filters, { preserveState: true });
}

function submitForm() {
  if (editing.value) {
    form.patch(`/members/${editing.value.id}`, {
      data: {
        angkatan: form.angkatan,
        nama_lengkap: form.nama_lengkap,
        tempatlahir: form.tempatlahir,
        tanggallahir: form.tanggallahir,
        jeniskelamin: form.jeniskelamin,
        kelas: form.kelas,
        tingkatan: form.tingkatan,
        status_aktif: form.status_aktif,
        no_hp: form.no_hp,
      },
      onSuccess: resetForm,
    });
    return;
  }

  form.post('/members', { onSuccess: resetForm });
}

function editMember(member) {
  editing.value = member;
  form.ambalan_id = member.ambalan_id;
  form.angkatan = member.angkatan || availableAngkatan.value[0]?.nomor;
  form.nama_lengkap = member.nama_lengkap;
  form.kelas = member.kelas;
  form.tingkatan = member.tingkatan;
  form.status_aktif = member.status_aktif;
  form.no_hp = member.no_hp || '';
  showCreate.value = true;
}

function generatePassword() {
  const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#%';
  const values = new Uint32Array(12);
  crypto.getRandomValues(values);
  form.password = Array.from(values, (value) => characters[value % characters.length]).join('');
}

function resetForm() {
  showCreate.value = false;
  editing.value = null;
  form.reset();
  form.ambalan_id = props.ambalans?.[0]?.id || null;
  form.angkatan = availableAngkatan.value[0]?.nomor || props.angkatanOptions?.[0] || '';
}

function openEditAngkatan(a) {
  editingAngkatan.value = a;
  editAngkatanTahun.value = a.angkatan;
  editAngkatanNomor.value = a.nomor;
  editAngkatanNama.value = a.nama;
  editAngkatanActive.value = a.is_active;
  editAngkatanCurrent.value = a.is_current;
}

function addAngkatan() {
  router.post('/angkatan', {
    tahun: newAngkatanTahun.value,
    nomor: newAngkatanNomor.value,
    nama: newAngkatanNama.value,
    is_active: newAngkatanActive.value,
    is_current: newAngkatanCurrent.value,
  }, {
    onSuccess: () => {
      newAngkatanTahun.value = '';
      newAngkatanNomor.value = '';
      newAngkatanNama.value = '';
      newAngkatanActive.value = true;
      newAngkatanCurrent.value = false;
    },
  });
}

function saveEditedAngkatan() {
  if (!editingAngkatan.value) return;
  router.patch(`/angkatan/${editingAngkatan.value.id}`, {
    tahun: editAngkatanTahun.value,
    nomor: editAngkatanNomor.value,
    nama: editAngkatanNama.value,
    is_active: editAngkatanActive.value,
    is_current: editAngkatanCurrent.value,
  }, {
    onSuccess: () => {
      editingAngkatan.value = null;
    },
  });
}

function archiveAngkatan(a) {
  if (!confirm(`Arsipkan angkatan ${a.nomor}? Anggota yang sudah terdaftar tetap tersimpan dan tetap menggunakan angkatan ini.`)) return;
  router.delete(`/angkatan/${a.id}`, {
    onSuccess: () => {},
  });
}

function applyBulkChange() {
  router.post('/members/bulk-change-role', {
    angkatan: bulkAngkatan.value,
    new_role: bulkNewRole.value,
  }, {
    onSuccess: (response) => {
      bulkSuccess.value = true;
      bulkMessage.value = response.props?.flash?.success || 'Role berhasil diubah.';
      bulkAngkatan.value = '';
      bulkNewRole.value = '';
      setTimeout(() => {
        bulkMessage.value = '';
        bulkSuccess.value = false;
      }, 5000);
    },
    onError: () => {
      bulkSuccess.value = false;
      bulkMessage.value = 'Gagal mengubah role.';
    },
  });
}

function openSetPosition(memberId) {
  showSetPosition.value = memberId;
  positionForm.position_id = '';
}

function setPosition() {
  if (!showSetPosition.value) return;
  positionForm.post(`/members/${showSetPosition.value}/position`, {
    onSuccess: () => {
      showSetPosition.value = null;
      positionForm.reset();
    },
    onError: () => {
      bulkPosSuccess.value = false;
      bulkPosMessage.value = 'Gagal menetapkan posisi.';
    },
  });
}

function removePosition(member) {
  if (!confirm(`Hapus posisi ${member.memberPositions?.[0]?.position?.name} untuk ${member.nama_lengkap}?`)) return;
  const position = member.memberPositions?.[0];
  if (!position) return;
  router.delete(`/members/${member.id}/position/${position.id}`, {
    onSuccess: () => {},
    onError: () => {},
  });
}

function applyBulkPosition() {
  router.post('/members/bulk-position', {
    angkatan: bulkPosAngkatan.value,
    position_id: bulkPosPosition.value,
  }, {
    onSuccess: (response) => {
      bulkPosSuccess.value = true;
      bulkPosMessage.value = response.props?.flash?.success || 'Posisi berhasil ditetapkan.';
      bulkPosAngkatan.value = '';
      bulkPosPosition.value = '';
      setTimeout(() => {
        bulkPosMessage.value = '';
        bulkPosSuccess.value = false;
      }, 5000);
    },
    onError: () => {
      bulkPosSuccess.value = false;
      bulkPosMessage.value = 'Gagal menetapkan posisi.';
    },
  });
}

function submitLogo() {
  if (!logoFile.value) return;
  const form = new FormData();
  form.append('logo', logoFile.value);
  router.post('/ambalan/logo', form, {
    onSuccess: () => {
      showLogoUpload.value = false;
      logoFile.value = null;
    },
    onError: () => {},
  });
}

function deleteLogo() {
  if (!confirm('Hapus logo ambalan?')) return;
  router.delete('/ambalan/logo', {
    onSuccess: () => {
      showLogoUpload.value = false;
    },
    onError: () => {},
  });
}
</script>
