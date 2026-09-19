<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Manajemen Anggota</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Direktori Anggota</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Kelola anggota, angkatan, dan role secara menyeluruh.</p>
      </div>
      <div class="flex gap-2">
        <button v-if="isPembina" @click="showAngkatan = true" class="inline-flex items-center rounded-lg border border-[#EDD330] px-4 py-2 text-sm font-semibold text-[#EDD330] transition hover:bg-[#EDD330]/10">Kelola Angkatan</button>
        <button v-if="canManage" @click="showCreate = true" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#6F9435]">+ Tambah anggota</button>
      </div>
    </div>

    <div v-if="isPembina" class="mb-5 rounded-2xl border-2 border-[#EDD330]/40 bg-[#335233] p-4 shadow-sm border-[#EDD330]/40">
      <p class="text-sm font-semibold text-[#EDD330]">Ubah Role Massal</p>
      <p class="mt-1 text-xs text-[#8fa06a]">Ubah role seluruh anggota satu angkatan sekaligus.</p>
      <form @submit.prevent="applyBulkChange" class="mt-3 grid gap-3 sm:grid-cols-3">
        <select v-model="bulkAngkatan" required class="rounded-lg border border-[#EDD330]/40 bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
          <option value="">Pilih angkatan</option>
          <option v-for="a in angkatanList" :key="a.nomor" :value="a.nomor">{{ a.nomor }} - {{ a.angkatan }}{{ a.is_active ? '' : ' (Arsip)' }}</option>
        </select>
        <select v-model="bulkNewRole" required class="rounded-lg border border-[#EDD330]/40 bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
          <option value="">Role baru</option>
          <option value="Anggota">Anggota</option>
          <option value="Pengurus">Pengurus</option>
          <option value="Pembina">Pembina</option>
          <option value="Alumni">Alumni</option>
        </select>
        <button type="submit" :disabled="!bulkAngkatan || !bulkNewRole" class="rounded-lg bg-gradient-to-r from-[#EDD330] to-[#A7B92A] px-4 py-2 text-sm font-semibold text-[#263D26] transition hover:brightness-110 disabled:opacity-50">Terapkan</button>
      </form>
      <p v-if="bulkMessage" :class="bulkSuccess ? 'text-[#A7B92A]' : 'text-[#ef4419]'" class="mt-2 text-xs font-medium">{{ bulkMessage }}</p>
    </div>

    <div v-if="isPembina" class="mb-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
      <p class="text-sm font-semibold text-[#EDD330]">Kelola Posisi Pengurus</p>
      <p class="mt-1 text-xs text-[#8fa06a]">Tetapkan posisi untuk setiap anggota pengurus satu angkatan.</p>
      <form @submit.prevent="applyBulkPosition" class="mt-3 grid gap-3 sm:grid-cols-3">
        <select v-model="bulkPosAngkatan" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
          <option value="">Pilih angkatan</option>
          <option v-for="a in angkatanList" :key="a.nomor" :value="a.nomor">{{ a.nomor }} - {{ a.angkatan }}{{ a.is_active ? '' : ' (Arsip)' }}</option>
        </select>
        <select v-model="bulkPosPosition" required class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
          <option value="">Pilih posisi</option>
          <option v-for="p in positions" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <button type="submit" :disabled="!bulkPosAngkatan || !bulkPosPosition" class="rounded-lg bg-gradient-to-r from-[#6F9435] to-[#A7B92A] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-110 disabled:opacity-50">Tetapkan Massal</button>
      </form>
      <p v-if="bulkPosMessage" :class="bulkPosSuccess ? 'text-[#A7B92A]' : 'text-[#ef4419]'" class="mt-2 text-xs font-medium">{{ bulkPosMessage }}</p>

      <div class="mt-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-semibold text-[#EDD330]">Daftar Pengurus</p>
            <p class="mt-1 text-xs text-[#8fa06a]">Semua anggota dengan role Pengurus beserta posisinya.</p>
          </div>
          <span class="rounded-full bg-[#6F9435]/20 px-2.5 py-0.5 text-xs font-semibold text-[#EDD330]">{{ pengurus.length }} pengurus</span>
        </div>

        <div class="mt-3 overflow-hidden rounded-xl border border-[#6F9435]/40 bg-[#263D26]">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#6F9435]/30">
              <thead class="bg-[#335233]">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">No</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Nama</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">NTA / Angkatan</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Posisi</th>
                  <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#6F9435]/30">
                <tr v-for="(p, idx) in pengurus" :key="p.id" class="hover:bg-[#335233]/60">
                  <td class="whitespace-nowrap px-4 py-3 text-sm text-[#d4dc9a]">{{ idx + 1 }}</td>
                  <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-[#f0ead8]">{{ p.nama_lengkap }}</td>
                  <td class="px-4 py-3 text-sm text-[#d4dc9a]">
                    {{ p.nta_username || p.nta || '-' }}
                    <br><span class="text-xs text-[#8fa06a]">Angkatan {{ p.angkatan || '-' }}</span>
                  </td>
                  <td class="px-4 py-3 text-sm">
                    <span class="rounded-full bg-[#6F9435]/20 px-2 py-1 text-xs text-[#EDD330]">{{ p.position_label }}</span>
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                    <button v-if="p.memberPositions?.[0]" type="button" @click="removePosition(p)" class="text-[#ef4419] hover:underline mr-2">Hapus Posisi</button>
                    <button v-else type="button" @click="editMember(p)" class="text-[#EDD330] hover:underline">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                    </button>
                  </td>
                </tr>
                <tr v-if="!pengurus.length">
                  <td colspan="5" class="px-4 py-6 text-center text-sm text-[#8fa06a]">Belum ada anggota dengan role Pengurus.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div v-if="isPembina" class="mb-5 rounded-2xl border-2 border-[#6F9435]/40 bg-[#335233] p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold text-[#EDD330]">Logo Ambalan</p>
          <p class="mt-1 text-xs text-[#8fa06a]">Perbarui logo yang tampil di seluruh situs.</p>
        </div>
        <button @click="showLogoUpload = true" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30">Kelola Logo</button>
      </div>
    </div>

    <form @submit.prevent="searchMembers" class="mb-5 grid gap-3 rounded-2xl border border-[#6F9435] bg-[#335233] p-4 shadow-sm border-[#6F9435] sm:grid-cols-3">
      <input v-model="filters.search" placeholder="Cari nama anggota..." class="rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]" />
      <select v-model="filters.status" class="rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]">
        <option value="">Semua status</option>
        <option>Aktif</option>
        <option>Non-Aktif</option>
        <option>Alumni</option>
      </select>
      <button class="rounded-lg bg-[#263D26] px-4 py-2 text-sm font-semibold text-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-[#6F9435] bg-[#335233] shadow-sm border-[#6F9435]">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-[#6F9435]/30">
          <thead class="bg-[#263D26]">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Anggota</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">NTA / Angkatan</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Kelas</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Tingkatan</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Status</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Posisi</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Role</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[#8fa06a]">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#6F9435]/30">
            <tr v-for="member in members.data" :key="member.id" class="hover:bg-[#263D26]/60">
              <td class="whitespace-nowrap px-4 py-3 text-sm font-medium">{{ member.nama_lengkap }}</td>
              <td class="px-4 py-3 text-sm text-[#d4dc9a]">
                {{ member.nta_username || member.nta || '-' }}
                <br><span class="text-xs text-[#8fa06a]">Angkatan {{ member.angkatan || '-' }}</span>
              </td>
              <td class="px-4 py-3 text-sm text-[#d4dc9a]">{{ member.kelas }}</td>
              <td class="px-4 py-3 text-sm"><span class="rounded-full bg-[#335233] px-2 py-1 text-xs text-[#EDD330]">{{ member.tingkatan }}</span></td>
              <td class="px-4 py-3 text-sm"><span class="rounded-full bg-[#A7B92A]/20 px-2 py-1 text-xs text-[#A7B92A]">{{ member.status_aktif }}</span></td>
              <td class="px-4 py-3 text-sm"><span class="rounded-full bg-[#6F9435]/20 px-2 py-1 text-xs text-[#EDD330]">{{ member.position_label }}</span></td>
              <td class="px-4 py-3 text-sm"><span class="rounded-full bg-[#6F9435]/20 px-2 py-1 text-xs text-[#EDD330]">{{ member.user?.role || '-' }}</span></td>
              <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                <template v-if="isPembina">
                  <button v-if="member.memberPositions?.[0]" type="button" @click="removePosition(member)" class="text-[#ef4419] hover:underline mr-2">Hapus Posisi</button>
                  <button type="button" @click="editMember(member)" class="text-[#EDD330] hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                  </button>
                </template>
                <button v-else type="button" @click="editMember(member)" class="text-[#EDD330] hover:underline">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                </button>
              </td>
            </tr>
            <tr v-if="!members.data.length">
              <td colspan="7" class="px-4 py-8 text-center text-sm text-[#8fa06a]">Data anggota belum tersedia.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="members.links" class="border-t border-[#6F9435] p-3 border-[#6F9435]" />
    </div>

    <Modal v-if="showAngkatan" title="Kelola Angkatan" @close="showAngkatan = false">
      <div class="space-y-5">
        <form @submit.prevent="addAngkatan" class="rounded-xl border border-[#6F9435]/40 bg-[#263D26] p-4">
          <p class="text-sm font-semibold text-[#EDD330]">Tambah angkatan</p>
          <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <label class="block">
              <span class="text-xs font-medium text-[#d4dc9a]">Tahun angkatan</span>
              <input v-model="newAngkatanTahun" required inputmode="numeric" maxlength="4" placeholder="2026" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
            </label>
            <label class="block">
              <span class="text-xs font-medium text-[#d4dc9a]">Nomor angkatan</span>
              <input v-model="newAngkatanNomor" required inputmode="numeric" maxlength="3" placeholder="018" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
            </label>
          </div>
          <label class="mt-3 block">
            <span class="text-xs font-medium text-[#d4dc9a]">Nama angkatan</span>
            <input v-model="newAngkatanNama" required placeholder="Angkatan 018 - 2026" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
          </label>
          <div class="mt-3 flex flex-wrap gap-4 text-sm text-[#d4dc9a]">
            <label class="flex items-center gap-2"><input v-model="newAngkatanActive" type="checkbox" class="accent-[#EDD330]" /> Aktif</label>
            <label class="flex items-center gap-2"><input v-model="newAngkatanCurrent" type="checkbox" class="accent-[#EDD330]" /> Jadikan angkatan berjalan</label>
          </div>
          <button type="submit" class="mt-4 rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#6F9435]">Tambah Angkatan</button>
        </form>

        <div class="space-y-2">
          <div v-for="a in angkatanList" :key="a.id" class="flex items-center justify-between gap-3 rounded-lg border border-[#6F9435]/30 bg-[#263D26] p-3">
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-[#f0ead8]">{{ a.nomor }} - {{ a.angkatan }} - {{ a.nama }}</p>
              <p class="mt-1 text-xs text-[#8fa06a]">
                {{ a.members_count ?? 0 }} anggota
                <span v-if="a.is_current" class="ml-2 rounded-full bg-[#EDD330]/20 px-2 py-0.5 font-semibold text-[#EDD330]">Berjalan</span>
                <span v-if="!a.is_active" class="ml-2 rounded-full bg-[#ef4419]/20 px-2 py-0.5 font-semibold text-[#ef4419]">Arsip</span>
              </p>
            </div>
            <div class="flex shrink-0 gap-2">
              <button type="button" @click="openEditAngkatan(a)" class="rounded border border-[#6F9435] px-2 py-1 text-xs text-[#d4dc9a] transition hover:bg-[#6F9435]/20">Edit</button>
              <button v-if="a.is_active" type="button" @click="archiveAngkatan(a)" class="rounded border border-[#ef4419]/50 px-2 py-1 text-xs text-[#ef4419] transition hover:bg-[#ef4419]/10">Arsipkan</button>
            </div>
          </div>
          <p v-if="!angkatanList.length" class="text-center text-xs text-[#8fa06a]">Belum ada angkatan.</p>
        </div>
      </div>
    </Modal>

    <Modal v-if="editingAngkatan" title="Edit Angkatan" @close="editingAngkatan = null">
      <form @submit.prevent="saveEditedAngkatan" class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-2">
          <label class="block">
            <span class="text-xs font-medium text-[#d4dc9a]">Tahun angkatan</span>
            <input v-model="editAngkatanTahun" required inputmode="numeric" maxlength="4" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
          </label>
          <label class="block">
            <span class="text-xs font-medium text-[#d4dc9a]">Nomor angkatan</span>
            <input v-model="editAngkatanNomor" required inputmode="numeric" maxlength="3" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
          </label>
        </div>
        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Nama angkatan</span>
          <input v-model="editAngkatanNama" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
        </label>
        <div class="flex flex-wrap gap-4 text-sm text-[#d4dc9a]">
          <label class="flex items-center gap-2"><input v-model="editAngkatanActive" type="checkbox" class="accent-[#EDD330]" /> Aktif</label>
          <label class="flex items-center gap-2"><input v-model="editAngkatanCurrent" type="checkbox" class="accent-[#EDD330]" /> Angkatan berjalan</label>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" @click="editingAngkatan = null" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm text-[#d4dc9a]">Batal</button>
          <button type="submit" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showLogoUpload" title="Kelola Logo Ambalan" @close="showLogoUpload = false">
      <div class="space-y-4">
        <div class="flex items-center gap-4">
          <img v-if="$page.props.ambalan?.logo_url" :src="$page.props.ambalan.logo_url" alt="Logo" class="h-16 w-16 rounded-lg object-contain border border-[#6F9435] bg-[#263D26]" />
          <span v-else class="flex h-16 w-16 items-center justify-center rounded-lg bg-gradient-to-br from-[#A7B92A] to-[#EDD330] text-xl font-extrabold text-[#263D26]">P</span>
        </div>
        <label class="block">
          <span class="text-sm font-medium text-[#d4dc9a]">Upload logo baru</span>
          <input type="file" @change="logoFile = $event.target.files[0]" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-[#d4dc9a] file:mr-3 file:rounded-lg file:border-0 file:bg-[#6F9435] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white" />
        </label>
        <div class="flex gap-2">
          <button @click="submitLogo" :disabled="!logoFile" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#EDD330] px-4 py-2 text-sm font-extrabold text-[#263D26] transition">Simpan Logo</button>
          <button @click="deleteLogo" :disabled="!$page.props.ambalan?.logo_path" class="rounded-lg bg-[#ef4419]/20 px-4 py-2 text-sm font-bold text-[#ef4419] transition disabled:opacity-30">Hapus Logo</button>
        </div>
      </div>
    </Modal>

    <Modal v-if="showCreate" :title="editing ? 'Edit anggota' : 'Tambah anggota'" @close="resetForm">
      <form @submit.prevent="submitForm" class="grid gap-4 sm:grid-cols-2">
        <section class="sm:col-span-2 rounded-xl border border-[#6F9435]/50 bg-[#335233] p-4">
          <p class="text-sm font-semibold text-[#EDD330]">Preview NTA</p>
          <div class="mt-3 grid gap-3 sm:grid-cols-3">
            <label class="block">
              <span class="text-xs font-medium text-[#EDD330]">Prefix Gudep</span>
              <input :value="gudepPrefix" disabled class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#d4dc9a] outline-none text-[#f0ead8]" />
            </label>
            <label class="block">
              <span class="text-xs font-medium text-[#EDD330]">Kode Angkatan</span>
              <input :value="form.angkatan" disabled class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#d4dc9a] outline-none text-[#f0ead8]" />
            </label>
            <label class="block">
              <span class="text-xs font-medium text-[#EDD330]">Nomor Urut</span>
              <input value="[Otomatis oleh Sistem]" readonly class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#8fa06a] outline-none" />
            </label>
          </div>
          <p class="mt-3 break-all text-sm font-mono font-semibold text-[#EDD330]">{{ previewNta }}</p>
        </section>

        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Ambalan</span>
          <select v-model="form.ambalan_id" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]">
            <option v-for="ambalan in ambalans" :key="ambalan.id" :value="ambalan.id">{{ ambalan.nama }}</option>
          </select>
        </label>

        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Angkatan</span>
          <select v-model="form.angkatan" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]">
            <option v-for="a in availableAngkatan" :key="a.nomor" :value="a.nomor">Angkatan {{ a.nomor }} ({{ a.angkatan }})</option>
            <option v-if="editing && form.angkatan && !availableAngkatan.some(a => a.nomor === form.angkatan)" :value="form.angkatan">Angkatan {{ form.angkatan }} (arsip)</option>
          </select>
        </label>

        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Nama lengkap</span>
          <input v-model="form.nama_lengkap" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]" />
        </label>

        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Tempat lahir</span>
          <input v-model="form.tempatlahir" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]" />
        </label>

        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Tanggal lahir</span>
          <input v-model="form.tanggallahir" type="date" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]" />
        </label>

        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Jenis kelamin</span>
          <select v-model="form.jeniskelamin" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]">
            <option value="">Pilih</option>
            <option>Laki-laki</option>
            <option>Perempuan</option>
          </select>
        </label>

        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Kelas</span>
          <input v-model="form.kelas" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]" />
        </label>

        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Tingkatan</span>
          <select v-model="form.tingkatan" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]">
            <option>Tamu</option>
            <option>Calon</option>
            <option>Bantara</option>
            <option>Laksana</option>
            <option>Alumni</option>
          </select>
        </label>

        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Status</span>
          <select v-model="form.status_aktif" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]">
            <option>Aktif</option>
            <option>Non-Aktif</option>
            <option>Alumni</option>
          </select>
        </label>

        <label class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Nomor HP</span>
          <input v-model="form.no_hp" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]" />
        </label>

        <label v-if="!editing" class="block">
          <span class="text-xs font-medium text-[#d4dc9a]">Password akun</span>
          <input v-model="form.password" type="password" required minlength="8" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm outline-none focus:border-[#EDD330]" />
        </label>

        <div class="flex items-end gap-2 sm:col-span-2">
          <button v-if="!editing" type="button" @click="generatePassword" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-medium text-[#d4dc9a] transition hover:bg-[#263D26] text-[#f0ead8]">Generate Password</button>
          <span class="flex-1"></span>
          <button type="button" @click="resetForm" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm">Batal</button>
          <button :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#6F9435] disabled:opacity-60">Simpan</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

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
const user = page.props.auth?.user;
const canManage = computed(() => {
  const role = user?.role;
  return role === 'Admin' || role === 'Pembina' || role === 'Pengurus';
});
const isPembina = computed(() => user?.role === 'Pembina');

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