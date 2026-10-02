<template>
  <AppLayout>
    <nav class="mb-5" aria-label="Remah roti">
      <Link href="/attendance" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#EDD330] transition hover:gap-2.5 hover:underline">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        Kembali ke daftar sesi
      </Link>
    </nav>

    <header class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
      <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-[#6F9435]/40 px-2.5 py-1 text-xs font-semibold text-[#d4f0a0]">Detail Sesi</span>
            <span v-if="session.qr_dynamic" class="rounded-full bg-[#EDD330]/20 px-2.5 py-1 text-xs font-semibold text-[#EDD330]">QR dinamis</span>
          </div>
          <h1 class="mt-3 break-words text-2xl font-bold text-[#f0ead8] sm:text-3xl">{{ session.nama }}</h1>
          <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#263D26] px-2.5 py-1 text-xs text-[#d4dc9a]">
              <svg class="h-3.5 w-3.5 text-[#A7B92B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M3 10h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" /></svg>
              {{ formatDate(session.tanggal) }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#263D26] px-2.5 py-1 text-xs text-[#d4dc9a]">
              <svg class="h-3.5 w-3.5 text-[#A7B92B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
              {{ session.lokasi || 'Lokasi belum diisi' }}
            </span>
            <span v-if="session.latitude !== null" class="inline-flex items-center gap-1.5 rounded-lg bg-[#263D26] px-2.5 py-1 text-xs text-[#d4dc9a]">
              <svg class="h-3.5 w-3.5 text-[#A7B92B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011-1.894l5.447 2.724A1 1 0 0111 7.382v15a1 1 0 01-1 1z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 20l-5.447-2.724A1 1 0 0015 16.382V5.618a1 1 0 00-1-1.894l-5.447 2.724A1 1 0 008 7.382V15" /></svg>
              Geofence {{ session.radius }} m
            </span>
          </div>
        </div>
        <div v-if="canManage" class="flex shrink-0 flex-wrap gap-2">
          <button @click="openAdd" class="inline-flex items-center rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-3.5 py-2 text-xs font-semibold text-white transition hover:shadow-lg hover:shadow-[#6F9435]/30">
            + Tambah presensi
          </button>
          <button @click="openSessionEdit()" class="rounded-lg border border-[#6F9435] px-3.5 py-2 text-xs font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/30">Edit sesi</button>
          <button @click="openDeleteSession" class="rounded-lg border border-[#ef4419]/60 px-3.5 py-2 text-xs font-semibold text-[#ef4419] transition hover:bg-[#ef4419]/15">Hapus sesi</button>
        </div>
      </div>
    </header>

    <section class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5" aria-label="Ringkasan kehadiran">
      <div
        v-for="card in summaryCards"
        :key="card.label"
        class="rounded-xl border border-[#6F9435] bg-[#335233] px-4 py-3 shadow-sm"
        :class="card.emphasis ? 'border-[#EDD330]/60' : ''"
      >
        <p class="text-xs font-medium text-[#8fa06a]">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-bold" :class="card.textClass">{{ card.value }}</p>
      </div>
    </section>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
      <section class="overflow-hidden rounded-2xl border border-[#6F9435] bg-[#335233] shadow-sm lg:col-span-2">
        <div class="border-b border-[#6F9435]/30 p-4">
          <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
              <h2 class="font-semibold text-[#f0ead8]">Daftar presensi</h2>
              <p class="mt-0.5 text-xs text-[#8fa06a]">
                <template v-if="rows.length">
                  Menampilkan {{ visibleRows.length }} dari {{ rows.length }} baris
                </template>
                <template v-else>Belum ada data</template>
              </p>
            </div>
            <div class="flex flex-wrap gap-2">
              <input v-model="search" type="search" placeholder="Cari nama atau catatan" class="w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-1.5 text-xs text-[#f0ead8] sm:w-52" />
              <select v-model="keteranganFilter" class="rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-1.5 text-xs text-[#f0ead8]">
                <option value="">Semua keterangan</option>
                <option value="Hadir">Hadir</option>
                <option value="Izin">Izin</option>
                <option value="Sakit">Sakit</option>
                <option value="Alpa">Alpa</option>
              </select>
              <button v-if="hasTableFilter" @click="resetTableFilters" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">Reset</button>
            </div>
          </div>

          <div v-if="canManage && visibleRows.length" class="mt-3 flex flex-wrap items-center gap-3 border-t border-[#6F9435]/20 pt-3">
            <label class="flex cursor-pointer items-center gap-2 text-xs text-[#8fa06a]">
              <input type="checkbox" :checked="allSelected" @change="toggleAll" class="h-4 w-4 rounded border-[#6F9435] bg-[#263D26] text-[#EDD330]" />
              Pilih semua yang tampil
            </label>
            <span v-if="selectedIds.length" class="text-xs font-semibold text-[#EDD330]">{{ selectedIds.length }} dipilih</span>
          </div>
        </div>

        <div v-if="rows.length" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-[#6F9435]/30">
            <thead class="bg-[#263D26]">
              <tr>
                <th v-if="canManage" class="w-10 px-4 py-3"><span class="sr-only">Pilih</span></th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#8fa06a]">Anggota</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#8fa06a]">Keterangan</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#8fa06a]">Catatan</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#8fa06a]">Waktu</th>
                <th v-if="canManage" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[#8fa06a]">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#6F9435]/30">
              <tr v-if="!visibleRows.length">
                <td :colspan="canManage ? 6 : 4" class="px-4 py-10 text-center">
                  <p class="text-sm text-[#8fa06a]">Tidak ada baris yang cocok dengan pencarian ini.</p>
                  <button @click="resetTableFilters" class="mt-2 rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">Reset pencarian</button>
                </td>
              </tr>
              <tr
                v-for="item in visibleRows"
                :key="item.id"
                class="transition hover:bg-[#3d5c3b]/40"
                :class="isSelected(item.id) ? 'bg-[#3d5c3b]' : ''"
              >
                <td v-if="canManage" class="px-4 py-3">
                  <input
                    type="checkbox"
                    :checked="isSelected(item.id)"
                    @change="toggleSelect(item.id)"
                    class="h-4 w-4 rounded border-[#6F9435] bg-[#263D26] text-[#EDD330]"
                    :aria-label="`Pilih presensi ${item.member?.nama_lengkap}`"
                  />
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#263D26] text-xs font-bold text-[#A7B92B]">{{ initials(item.member?.nama_lengkap) }}</span>
                    <div class="min-w-0">
                      <p class="truncate text-sm font-medium text-[#f0ead8]">{{ item.member?.nama_lengkap || 'Anggota dihapus' }}</p>
                      <p v-if="item.member?.nta" class="text-xs text-[#8fa06a]">NTA {{ item.member.nta }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="keteranganClass(item.keterangan)">{{ item.keterangan }}</span></td>
                <td class="max-w-[16rem] px-4 py-3 text-sm text-[#8fa06a]"><span class="line-clamp-2">{{ item.catatan || '-' }}</span></td>
                <td class="whitespace-nowrap px-4 py-3 text-xs text-[#8fa06a]">{{ formatTime(item.checked_at) }}</td>
                <td v-if="canManage" class="whitespace-nowrap px-4 py-3 text-right text-xs">
                  <div class="flex justify-end gap-3">
                    <button @click="openEdit(item)" class="font-semibold text-[#EDD330] hover:underline">Edit</button>
                    <button @click="openDeleteRecord(item)" class="font-semibold text-[#ef4419] hover:underline">Hapus</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="p-10 text-center">
          <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-[#263D26] text-[#A7B92B]">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </span>
          <p class="mt-3 text-sm text-[#8fa06a]">Belum ada presensi pada sesi ini.</p>
          <button v-if="canManage" @click="openAdd" class="mt-3 rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-xs font-semibold text-white">+ Tambah presensi pertama</button>
        </div>
      </section>

      <aside class="space-y-5">
        <section class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
          <h2 class="font-semibold text-[#f0ead8]">Informasi sesi</h2>
          <dl class="mt-4 space-y-3 text-sm">
            <div class="flex items-start justify-between gap-3">
              <dt class="shrink-0 text-xs text-[#8fa06a]">Kode QR</dt>
              <dd class="break-all text-right font-mono text-xs font-semibold text-[#EDD330]">{{ session.qr_token }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3">
              <dt class="shrink-0 text-xs text-[#8fa06a]">Geofence</dt>
              <dd class="text-right text-xs text-[#d4dc9a]">
                <template v-if="session.latitude !== null">Aktif • radius {{ session.radius }} m</template>
                <template v-else>Nonaktif</template>
              </dd>
            </div>
            <div v-if="session.latitude !== null" class="flex items-start justify-between gap-3">
              <dt class="shrink-0 text-xs text-[#8fa06a]">Koordinat</dt>
              <dd class="text-right font-mono text-[10px] text-[#d4dc9a]">{{ session.latitude }}, {{ session.longitude }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3">
              <dt class="shrink-0 text-xs text-[#8fa06a]">Ambalan</dt>
              <dd class="text-right text-xs text-[#d4dc9a]">{{ session.ambalan?.nama || '-' }}</dd>
            </div>
          </dl>
          <Link v-if="isAnggota" :href="route('attendance.scan', session.qr_token)" class="mt-4 block rounded-lg border border-[#6F9435] px-3 py-2 text-center text-xs font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/30">
            Buka halaman pindai QR
          </Link>
        </section>

        <section v-if="session.materi_nama" class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
          <h2 class="font-semibold text-[#f0ead8]">Materi latihan</h2>
          <div class="mt-3 flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#263D26] text-[#A7B92B]">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7l-5-5H7a2 2 0 00-2 2v14a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l4 4m0 0l-4 4m4-4H9" /></svg>
            </span>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium text-[#f0ead8]">{{ session.materi_nama }}</p>
              <p class="mt-0.5 text-xs text-[#8fa06a]">{{ formatFileSize(session.materi_size) }} • {{ session.materi_mime_type }}</p>
            </div>
          </div>
          <Link :href="route('attendance.materi.download', session.id)" class="mt-4 block rounded-lg border border-[#6F9435] px-3 py-2 text-center text-xs font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/30">
            Unduh materi
          </Link>
        </section>

        <section class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
          <h2 class="font-semibold text-[#f0ead8]">Komposisi kehadiran</h2>
          <div v-if="stats.total" class="mt-4 space-y-3">
            <div v-for="bar in compositionBars" :key="bar.label">
              <div class="flex items-center justify-between text-xs">
                <span class="text-[#d4dc9a]">{{ bar.label }}</span>
                <span class="text-[#8fa06a]">{{ bar.count }} • {{ bar.persen }}%</span>
              </div>
              <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-[#263D26]">
                <div class="h-full rounded-full transition-all" :class="bar.barClass" :style="{ width: bar.persen + '%' }"></div>
              </div>
            </div>
          </div>
          <p v-else class="mt-3 text-xs text-[#8fa06a]">Belum ada data untuk dihitung.</p>
        </section>
      </aside>
    </div>

    <div v-if="canManage && selectedIds.length" class="mt-5 flex flex-wrap items-center gap-3 rounded-2xl border border-[#EDD330]/60 bg-[#335233] px-4 py-3 shadow-sm">
      <p class="text-sm font-semibold text-[#f0ead8]">{{ selectedIds.length }} presensi dipilih</p>
      <button @click="openBulkUpdate" class="rounded-lg bg-[#6F9435] px-3 py-1.5 text-xs font-semibold text-white transition hover:opacity-90">Ubah keterangan</button>
      <button @click="openBulkDelete" class="rounded-lg bg-[#ef4419] px-3 py-1.5 text-xs font-semibold text-white transition hover:opacity-90">Hapus terpilih</button>
      <button @click="selectedIds = []" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a] transition hover:bg-[#6F9435]/30">Batalkan pilihan</button>
    </div>

    <Modal v-if="showSessionEdit" title="Edit sesi latihan" @close="closeSessionEdit">
      <form @submit.prevent="submitSessionEdit" class="grid gap-3">
        <label class="block"><span class="text-xs font-medium">Nama sesi</span><input v-model="sessionForm.nama" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]" /></label>
        <div class="grid gap-3 sm:grid-cols-2"><label class="block"><span class="text-xs font-medium">Tanggal</span><input v-model="sessionForm.tanggal" type="date" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]" /></label><label class="block"><span class="text-xs font-medium">Lokasi</span><input v-model="sessionForm.lokasi" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]" /></label></div>
        <label class="block"><span class="text-xs font-medium">Materi latihan (ganti, opsional)</span><input @change="onSessionMateriChange" type="file" class="mt-1 text-sm" accept=".pdf,.jpg,.jpeg,.png,.mp4,.webm,.doc,.docx" /></label>
        <p v-if="sessionForm.errors.materi" class="text-xs text-[#ef4419]">{{ sessionForm.errors.materi }}</p>
        <div v-if="session.materi_nama" class="flex items-center gap-2 rounded-lg border border-[#6F9435]/30 bg-[#263D26] px-3 py-2">
          <label class="flex items-center gap-2"><input v-model="sessionForm.remove_materi" type="checkbox" class="h-4 w-4 rounded border-[#6F9435] bg-[#335233] text-[#ef4419]" /><span class="text-xs text-[#8fa06a]">Hapus materi saat ini</span></label>
        </div>

        <div>
          <span class="text-xs font-medium">Titik lokasi &amp; radius presensi</span>
          <p class="mb-2 mt-1 text-xs text-[#8fa06a]">Presensi hanya diterima jika anggota berada dalam radius dari titik ini.</p>
          <LocationPicker v-if="showMapPicker" v-model="sessionLocation" />
          <button v-else type="button" class="w-full rounded-lg border border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/20 hover:text-[#EDD330]" @click="showMapPicker = true">
            {{ sessionLocation.latitude === null ? 'Tentukan lokasi di peta' : 'Ubah titik lokasi' }}
          </button>
          <p v-if="sessionForm.errors.latitude || sessionForm.errors.longitude || sessionForm.errors.radius" class="mt-1 text-xs text-[#ef4419]">
            {{ sessionForm.errors.latitude || sessionForm.errors.longitude || sessionForm.errors.radius }}
          </p>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeSessionEdit" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="sessionForm.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Simpan</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showEdit && editItem" title="Perbarui presensi" @close="closeEditModal">
      <form @submit.prevent="submitEdit" class="grid gap-3">
        <p class="text-xs text-[#8fa06a]">{{ editItem.member?.nama_lengkap || 'Anggota dihapus' }}</p>
        <label class="block">
          <span class="text-xs font-medium">Keterangan</span>
          <select v-model="form.keterangan" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm">
            <option>Hadir</option><option>Izin</option><option>Sakit</option><option>Alpa</option>
          </select>
        </label>
        <label class="block">
          <span class="text-xs font-medium">Catatan</span>
          <textarea v-model="form.catatan" rows="4" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]"></textarea>
        </label>
        <p v-if="form.errors.keterangan || form.errors.catatan" class="text-xs text-[#ef4419]">{{ form.errors.keterangan || form.errors.catatan }}</p>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeEditModal" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Simpan</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showAdd && canManage" title="Tambah presensi manual" @close="closeAdd">
      <form @submit.prevent="submitAdd" class="grid gap-3">
        <p class="text-xs text-[#8fa06a]">Gunakan ini ketika seorang anggota hadir tetapi tidak sempat memindai QR Code.</p>
        <label class="block">
          <span class="text-xs font-medium">Anggota</span>
          <input v-model="addForm.member_search" type="search" placeholder="Cari nama atau NTA" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8]" />
          <select v-model="addForm.member_id" required class="mt-2 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8]">
            <option value="">Pilih anggota</option>
            <option v-for="m in filteredMembers" :key="m.id" :value="m.id">{{ m.nama_lengkap }} ({{ m.nta }})</option>
          </select>
        </label>
        <p v-if="addForm.errors.member_id" class="text-xs text-[#ef4419]">{{ addForm.errors.member_id }}</p>
        <label class="block"><span class="text-xs font-medium">Keterangan</span><select v-model="addForm.keterangan" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option>Hadir</option><option>Izin</option><option>Sakit</option><option>Alpa</option></select></label>
        <label class="block"><span class="text-xs font-medium">Catatan</span><textarea v-model="addForm.catatan" rows="3" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]"></textarea></label>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeAdd" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="addForm.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Tambah</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="deleteRecordTarget && canManage" title="Hapus presensi" @close="deleteRecordTarget = null">
      <div class="grid gap-3">
        <p class="text-sm text-[#f0ead8]">Hapus presensi <strong>{{ deleteRecordTarget.member?.nama_lengkap }}</strong> pada sesi ini?</p>
        <p class="text-xs text-[#ef4419]">Baris presensi ini akan dihapus permanen dan tidak bisa dibatalkan.</p>
        <div class="flex justify-end gap-2">
          <button @click="deleteRecordTarget = null" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button :disabled="recordForm.processing" @click="submitDeleteRecord" class="rounded-lg bg-[#ef4419] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Hapus</button>
        </div>
      </div>
    </Modal>

    <Modal v-if="showDeleteSession && canManage" title="Hapus sesi latihan" @close="showDeleteSession = false">
      <div class="grid gap-3">
        <p class="text-sm text-[#f0ead8]">Hapus sesi "<strong>{{ session.nama }}</strong>" beserta {{ rows.length }} baris presensinya?</p>
        <p class="text-xs text-[#ef4419]">Baris presensi ikut terhapus permanen. Tindakan ini tidak bisa dibatalkan.</p>
        <div class="flex justify-end gap-2">
          <button @click="showDeleteSession = false" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button :disabled="rows.length > 0 || sessionForm.processing" @click="submitDeleteSession" class="rounded-lg bg-[#ef4419] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
            {{ rows.length > 0 ? 'Hapus presensi dulu' : 'Hapus sesi' }}
          </button>
        </div>
      </div>
    </Modal>

    <Modal v-if="showBulkUpdate && canManage" title="Ubah keterangan terpilih" @close="showBulkUpdate = false">
      <form @submit.prevent="submitBulkUpdate" class="grid gap-3">
        <p class="text-xs text-[#8fa06a]">Perubahan diterapkan ke {{ selectedIds.length }} baris presensi.</p>
        <label class="block"><span class="text-xs font-medium">Keterangan</span><select v-model="bulkForm.keterangan" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"><option>Hadir</option><option>Izin</option><option>Sakit</option><option>Alpa</option></select></label>
        <label class="block"><span class="text-xs font-medium">Catatan (opsional, isi untuk menimpa semua)</span><textarea v-model="bulkForm.catatan" rows="3" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8]"></textarea></label>
        <p v-if="bulkForm.errors.keterangan" class="text-xs text-[#ef4419]">{{ bulkForm.errors.keterangan }}</p>
        <div class="flex justify-end gap-2">
          <button type="button" @click="showBulkUpdate = false" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="bulkForm.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Terapkan</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showBulkDelete && canManage" title="Hapus presensi terpilih" @close="showBulkDelete = false">
      <div class="grid gap-3">
        <p class="text-sm text-[#f0ead8]">Hapus {{ selectedIds.length }} baris presensi?</p>
        <ul class="max-h-40 list-inside list-disc overflow-y-auto text-xs text-[#8fa06a]">
          <li v-for="id in selectedIds" :key="id">{{ memberName(id) }}</li>
        </ul>
        <p class="text-xs text-[#ef4419]">Baris presensi yang dihapus tidak bisa dikembalikan.</p>
        <div class="flex justify-end gap-2">
          <button @click="showBulkDelete = false" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button :disabled="bulkForm.processing" @click="submitBulkDelete" class="rounded-lg bg-[#ef4419] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
            {{ bulkForm.processing ? 'Menghapus...' : `Hapus ${selectedIds.length} baris` }}
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import LocationPicker from '@/Components/LocationPicker.vue';

const props = defineProps({ session: Object, members: { type: Array, default: () => [] } });

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);
const canManage = computed(() => ['Admin', 'Pembina', 'Pengurus'].includes(role.value));
const isAnggota = computed(() => role.value === 'Anggota');

const rows = computed(() => props.session?.attendances ?? []);
const selectedIds = ref([]);

const search = ref('');
const keteranganFilter = ref('');

const hasTableFilter = computed(() => search.value.trim() !== '' || keteranganFilter.value !== '');

const visibleRows = computed(() => {
  const needle = search.value.trim().toLowerCase();

  return rows.value.filter((row) => {
    if (keteranganFilter.value && row.keterangan !== keteranganFilter.value) {
      return false;
    }
    if (!needle) {
      return true;
    }
    return (
      (row.member?.nama_lengkap ?? '').toLowerCase().includes(needle) ||
      (row.member?.nta ?? '').toString().toLowerCase().includes(needle) ||
      (row.catatan ?? '').toLowerCase().includes(needle)
    );
  });
});

const allSelected = computed(() => visibleRows.value.length > 0 && visibleRows.value.every((row) => selectedIds.value.includes(row.id)));

const stats = computed(() => {
  const counts = { Hadir: 0, Izin: 0, Sakit: 0, Alpa: 0 };

  rows.value.forEach((row) => {
    if (counts[row.keterangan] !== undefined) {
      counts[row.keterangan] += 1;
    }
  });

  const total = rows.value.length;

  return { ...counts, total, persen: total ? Math.round((counts.Hadir / total) * 100) : 0 };
});

const summaryCards = computed(() => [
  { label: 'Total presensi', value: stats.value.total, textClass: 'text-[#f0ead8]' },
  { label: 'Hadir', value: stats.value.Hadir, textClass: 'text-[#d4f0a0]' },
  { label: 'Izin', value: stats.value.Izin, textClass: 'text-[#EDD330]' },
  { label: 'Sakit', value: stats.value.Sakit, textClass: 'text-[#ff9f8a]' },
  { label: 'Tingkat kehadiran', value: stats.value.persen + '%', textClass: 'text-[#EDD330]', emphasis: true },
]);

const compositionBars = computed(() => {
  const total = stats.value.total || 1;

  return [
    { label: 'Hadir', count: stats.value.Hadir, persen: Math.round((stats.value.Hadir / total) * 100), barClass: 'bg-[#6F9435]' },
    { label: 'Izin', count: stats.value.Izin, persen: Math.round((stats.value.Izin / total) * 100), barClass: 'bg-[#EDD330]' },
    { label: 'Sakit', count: stats.value.Sakit, persen: Math.round((stats.value.Sakit / total) * 100), barClass: 'bg-[#ef4419]' },
    { label: 'Alpa', count: stats.value.Alpa, persen: Math.round((stats.value.Alpa / total) * 100), barClass: 'bg-[#8fa06a]' },
  ];
});

const showEdit = ref(false);
const editItem = ref(null);
const showSessionEdit = ref(false);
const form = useForm({ keterangan: 'Hadir', catatan: '' });
const sessionForm = useForm({ nama: '', tanggal: '', lokasi: '', materi: null, remove_materi: false, latitude: null, longitude: null, radius: null });
const showMapPicker = ref(false);
const sessionLocation = ref({ latitude: null, longitude: null, radius: 100 });

const showAdd = ref(false);
const addForm = useForm({ member_id: '', member_search: '', keterangan: 'Hadir', catatan: '' });

const deleteRecordTarget = ref(null);
const recordForm = useForm({});

const showDeleteSession = ref(false);
const showBulkUpdate = ref(false);
const showBulkDelete = ref(false);
const bulkForm = useForm({ ids: [], action: 'update', keterangan: 'Hadir', catatan: '' });

const filteredMembers = computed(() => {
  const needle = addForm.member_search.trim().toLowerCase();
  if (!needle) return props.members;
  return props.members.filter(
    (m) => m.nama_lengkap.toLowerCase().includes(needle) || String(m.nta).includes(needle)
  );
});

function initials(name) {
  return (name || '?')
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((word) => word[0] ?? '')
    .join('')
    .toUpperCase();
}

function memberName(id) {
  return rows.value.find((row) => row.id === id)?.member?.nama_lengkap ?? 'ID ' + id;
}

function keteranganClass(keterangan) {
  if (keterangan === 'Hadir') return 'bg-[#6F9435]/40 text-[#d4f0a0]';
  if (keterangan === 'Izin') return 'bg-[#EDD330]/20 text-[#EDD330]';
  if (keterangan === 'Sakit') return 'bg-[#ef4419]/20 text-[#ff9f8a]';
  return 'bg-[#8fa06a]/20 text-[#d4dc9a]';
}

function isSelected(id) {
  return selectedIds.value.includes(id);
}

function toggleSelect(id) {
  selectedIds.value = isSelected(id) ? selectedIds.value.filter((x) => x !== id) : [...selectedIds.value, id];
}

// "Pilih semua" hanya berlaku pada baris yang sedang tampil, bukan seluruh sesi.
function toggleAll() {
  const visibleIds = visibleRows.value.map((row) => row.id);
  selectedIds.value = allSelected.value
    ? selectedIds.value.filter((id) => !visibleIds.includes(id))
    : [...new Set([...selectedIds.value, ...visibleIds])];
}

function resetTableFilters() {
  search.value = '';
  keteranganFilter.value = '';
}

// Baris yang hilang setelah reload tidak boleh tetap terpilih.
watch(
  () => rows.value.map((row) => row.id),
  (ids) => {
    selectedIds.value = selectedIds.value.filter((id) => ids.includes(id));
  }
);

function openAdd() {
  addForm.reset();
  addForm.keterangan = 'Hadir';
  showAdd.value = true;
}

function closeAdd() {
  showAdd.value = false;
}

function submitAdd() {
  addForm.post(`/attendance/${props.session.id}/records`, {
    onSuccess: () => closeAdd(),
  });
}

function openDeleteRecord(item) {
  recordForm.clearErrors();
  deleteRecordTarget.value = item;
}

function submitDeleteRecord() {
  // Id harus ditangkap lebih dulu; setelah delete() target dikosongkan.
  const id = deleteRecordTarget.value.id;

  recordForm.delete(`/attendance-records/${id}`, {
    onSuccess: () => {
      deleteRecordTarget.value = null;
      selectedIds.value = selectedIds.value.filter((selected) => selected !== id);
    },
    preserveScroll: true,
  });
}

function openDeleteSession() {
  sessionForm.clearErrors();
  showDeleteSession.value = true;
}

function submitDeleteSession() {
  sessionForm.delete(`/attendance/${props.session.id}`);
}

function openBulkUpdate() {
  bulkForm.clearErrors();
  bulkForm.keterangan = 'Hadir';
  bulkForm.catatan = '';
  showBulkUpdate.value = true;
}

function submitBulkUpdate() {
  bulkForm.ids = [...selectedIds.value];
  bulkForm.action = 'update';
  bulkForm.attendance_session_id = props.session.id;
  bulkForm.post('/attendance-records/bulk', {
    onSuccess: () => {
      showBulkUpdate.value = false;
      selectedIds.value = [];
    },
    preserveScroll: true,
  });
}

function openBulkDelete() {
  bulkForm.clearErrors();
  showBulkDelete.value = true;
}

function submitBulkDelete() {
  bulkForm.ids = [...selectedIds.value];
  bulkForm.action = 'delete';
  bulkForm.attendance_session_id = props.session.id;
  bulkForm.post('/attendance-records/bulk', {
    onSuccess: () => {
      showBulkDelete.value = false;
      selectedIds.value = [];
    },
    preserveScroll: true,
  });
}

function submitSessionEdit() {
  sessionForm.latitude = sessionLocation.value.latitude;
  sessionForm.longitude = sessionLocation.value.longitude;
  sessionForm.radius = sessionLocation.value.latitude === null ? null : sessionLocation.value.radius;

  sessionForm.patch(`/attendance/${props.session.id}`, {
    onSuccess: () => {
      showSessionEdit.value = false;
      showMapPicker.value = false;
    },
  });
}

function closeSessionEdit() {
  showSessionEdit.value = false;
  showMapPicker.value = false;
}

function openEdit(item) {
  editItem.value = item;
  form.clearErrors();
  form.keterangan = item.keterangan;
  form.catatan = item.catatan || '';
  showEdit.value = true;
}

function closeEditModal() {
  showEdit.value = false;
}

function submitEdit() {
  form.patch(`/attendance-records/${editItem.value.id}`, {
    onSuccess: () => closeEditModal(),
    preserveScroll: true,
  });
}

function openSessionEdit() {
  showSessionEdit.value = true;
  sessionForm.nama = props.session.nama || '';
  sessionForm.tanggal = props.session.tanggal ? new Date(props.session.tanggal).toISOString().slice(0, 10) : new Date().toISOString().slice(0, 10);
  sessionForm.lokasi = props.session.lokasi || '';
  sessionForm.materi = null;
  sessionForm.remove_materi = false;
  sessionLocation.value = {
    latitude: props.session.latitude === null ? null : Number(props.session.latitude),
    longitude: props.session.longitude === null ? null : Number(props.session.longitude),
    radius: props.session.radius ? Number(props.session.radius) : 100,
  };
  showMapPicker.value = false;
  sessionForm.clearErrors();
}

function onSessionMateriChange(event) {
  sessionForm.materi = event.target.files[0] ?? null;
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-';
}

function formatTime(value) {
  if (!value) {
    return '-';
  }
  const date = new Date(value);

  return `${date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })} • ${date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`;
}

function formatFileSize(bytes) {
  if (!bytes) {
    return '-';
  }
  const units = ['B', 'KB', 'MB', 'GB'];
  let size = bytes;
  let unitIndex = 0;
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex++;
  }
  return `${unitIndex === 0 ? size : size.toFixed(1)} ${units[unitIndex]}`;
}
</script>