<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Syarat Kecakapan Umum</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Digitalisasi SKU / TKU</h1>
        <p v-if="nonAnggota" class="mt-1 text-sm text-[#8fa06a]">{{ canApprove ? 'Kelola poin SKU dan TKK, serta verifikasi pengajuan anggota.' : 'Lihat pengajuan dan statistik SKU.' }}</p>
        <p v-else class="mt-1 text-sm text-[#8fa06a]">Pilih poin yang sudah diselesaikan, lalu kirim dokumentasi untuk diverifikasi.</p>
      </div>
      <button v-if="$page.props.auth?.user?.role === 'Anggota' && !completed" type="button" @click="openSubmission()" class="inline-flex w-fit items-center rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Ajukan SKU</button>
    </div>

    <div v-if="progress" class="mb-6 rounded-2xl border border-[#6F9435]/50 bg-[#335233] p-5">
      <div class="flex items-end justify-between">
        <div>
          <p class="text-sm font-semibold text-[#EDD330]">Progress SKU anggota</p>
          <p class="mt-1 text-xs text-[#8fa06a]">{{ progress.approved }} dari {{ progress.total }} poin disetujui</p>
        </div>
        <strong class="text-2xl font-bold text-[#EDD330]">{{ progress.percentage }}%</strong>
      </div>
      <div class="mt-3 h-3 overflow-hidden rounded-full bg-[#335233]"><div class="h-full rounded-full bg-gradient-to-r from-[#A7B92B] to-[#6F9435] transition-all" :style="{ width: progress.percentage + '%' }"></div></div>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
      <section class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435] xl:col-span-2">
        <h2 class="mb-4 font-semibold text-[#f0ead8]">Antrean pengajuan</h2>
        <div class="space-y-3">
          <div v-for="item in submissions.data" :key="item.id" class="flex flex-col gap-3 rounded-xl border border-[#6F9435] p-4 border-[#6F9435] sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold">{{ item.member?.nama_lengkap }}</p>
              <p class="mt-1 text-xs text-[#8fa06a]">{{ item.sku_point?.tingkatan }} • Poin {{ item.sku_point?.nomor_poin }} • {{ item.status }}</p>
              <p v-if="item.catatan" class="mt-2 text-xs leading-5 text-[#8fa06a]">{{ item.catatan }}</p>
              <a v-if="item.bukti_kegiatan" :href="`/storage/${item.bukti_kegiatan}`" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-xs font-semibold text-[#EDD330] hover:underline">Lihat dokumentasi bukti</a>
              <div v-if="item.media?.length" class="mt-2 flex flex-wrap gap-2">
                <img v-for="(media, idx) in item.media" :key="idx" :src="media.url" alt="Bukti" class="h-12 w-12 rounded-lg object-cover border border-[#6F9435]/30" />
              </div>
            </div>
            <div class="flex flex-wrap gap-2">
              <span v-if="item.status === 'Pending'" class="rounded-full bg-[#EDD330]/20 px-2.5 py-1 text-xs font-medium text-[#EDD330]">Pending</span>
              <span v-else-if="item.status === 'Approved'" class="rounded-full bg-[#A7B92B]/20 px-2.5 py-1 text-xs font-medium text-[#A7B92B]">Disetujui</span>
              <span v-else class="rounded-full bg-[#ef4419]/20 px-2.5 py-1 text-xs font-medium text-[#ef4419]">Ditolak</span>
              <template v-if="item.status === 'Pending' && canApprove"><button type="button" @click="decision(item, 'approve')" class="rounded-lg bg-[#A7B92B] px-3 py-1.5 text-xs font-semibold text-white">Setujui</button><button type="button" @click="rejectId = item.id; rejectCatatan = ''; showReject = true" class="rounded-lg border border-[#ef4419]/50 px-3 py-1.5 text-xs font-semibold text-[#ef4419]">Tolak</button></template>
              <button v-if="item.status === 'Pending' && $page.props.auth?.user?.role === 'Anggota'" type="button" @click="router.post(`/sku/${item.id}/cancel`)" class="rounded-lg border border-[#6F9435] px-3 py-1.5 text-xs font-semibold text-[#d4dc9a]">Batalkan</button>
            </div>
          </div>
          <p v-if="!submissions.data.length" class="text-center text-xs text-[#8fa06a]">Belum ada pengajuan.</p>
        </div>
        <Pagination :links="submissions.links" class="mt-4 border-t border-[#6F9435] p-3 border-[#6F9435]" />
      </section>

      <aside class="rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm border-[#6F9435]">
        <template v-if="progress">
          <h2 class="mb-3 font-semibold text-[#f0ead8]">Progress pribadi</h2>
          <div class="h-3 overflow-hidden rounded-full bg-[#263D26]"><div class="h-full rounded-full bg-gradient-to-r from-[#A7B92B] to-[#EDD330] transition-all" :style="{ width: progress.percentage + '%' }"></div></div>
          <p class="mt-2 text-sm font-bold text-[#EDD330]">{{ progress.percentage }}%</p>
          <p class="mt-1 text-xs text-[#8fa06a]">{{ progress.approved }} dari {{ progress.total }} poin disetujui</p>
        </template>
        <template v-else>
          <h2 class="mb-3 font-semibold text-[#f0ead8]">Statistik pengajuan</h2>
          <div class="space-y-2">
            <div class="flex items-center justify-between rounded-lg bg-[#263D26] p-3">
              <span class="text-xs font-medium text-[#EDD330]">Pending</span>
              <strong class="text-lg font-bold text-[#EDD330]">{{ submissionStats.pending }}</strong>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-[#263D26] p-3">
              <span class="text-xs font-medium text-[#A7B92B]">Disetujui</span>
              <strong class="text-lg font-bold text-[#A7B92B]">{{ submissionStats.approved }}</strong>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-[#263D26] p-3">
              <span class="text-xs font-medium text-[#ef4419]">Ditolak</span>
              <strong class="text-lg font-bold text-[#ef4419]">{{ submissionStats.rejected }}</strong>
            </div>
          </div>
        </template>
      </aside>
    </div>

    <section class="mt-6 rounded-2xl border border-[#6F9435] bg-[#335233] p-5 shadow-sm">
      <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
          <h2 class="font-semibold text-[#f0ead8]">{{ sectionTitle }}</h2>
          <template v-if="nonAnggota">
            <select v-model="filterTingkatan" @change="onFilterChange" class="mt-2 w-full rounded-lg border border-[#6F9435] bg-[#263D26] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330] sm:w-auto">
              <option value="">Semua Tingkatan</option>
              <option value="Bantara">Bantara</option>
              <option value="Laksana">Laksana</option>
            </select>
          </template>
        </div>
        <button v-if="isEditable()" type="button" @click="openEdit(null)" class="inline-flex items-center rounded-lg bg-[#EDD330] px-4 py-2 text-xs font-bold text-[#263D26] hover:bg-white">Kelola Poin SKU</button>
      </div>

      <template v-for="(level, levelName) in skuPointsByLevel" :key="levelName">
        <div class="mb-5">
          <h3 class="mb-2 flex items-center justify-between text-sm font-bold text-[#EDD330]"><span>{{ levelName }}</span><span class="text-[10px] font-medium text-[#8fa06a]">{{ level.length }} poin</span></h3>
          <div class="space-y-2">
            <div v-for="point in level" :key="point.id" class="flex items-center gap-3 rounded-xl border border-[#6F9435]/30 bg-[#263D26] p-3 transition hover:border-[#A7B92B]/60" :class="isSubmissionAvailable(point) ? 'cursor-pointer' : ''" @click="openSubmission(point)">
              <span class="flex h-5 w-5 items-center justify-center rounded border border-[#6F9435]/50 text-xs" :class="point.status === 'Approved' ? 'bg-[#A7B92B]/30 border-[#A7B92B] text-[#A7B92B]' : 'text-[#8fa06a]'">
                <span v-if="point.status === 'Approved'">✓</span>
              </span>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-[#f0ead8]">{{ point.nomor_poin }}. {{ point.deskripsi_poin }}</p>
                <p v-if="nonAnggota && point.aggregate" class="mt-1 flex flex-wrap gap-1">
                  <span class="rounded-full bg-[#A7B92B]/20 px-2 py-0.5 text-[10px] font-bold text-[#A7B92B]">{{ point.aggregate.Approved || 0 }} disetujui</span>
                  <span class="rounded-full bg-[#EDD330]/20 px-2 py-0.5 text-[10px] font-bold text-[#EDD330]">{{ point.aggregate.Pending || 0 }} pending</span>
                  <span v-if="point.aggregate.Rejected" class="rounded-full bg-[#ef4419]/20 px-2 py-0.5 text-[10px] font-bold text-[#ef4419]">{{ point.aggregate.Rejected }} ditolak</span>
                </p>
                <p v-else-if="point.status === 'Pending'" class="mt-1 text-[10px] text-[#EDD330]">Pengajuan sedang menunggu verifikasi.</p>
                <p v-else-if="point.status === 'Rejected'" class="mt-1 text-[10px] text-[#ef4419]">Pengajuan ditolak. Perbaiki bukti lalu ajukan ulang.</p>
              </div>
              <template v-if="point.status === 'Approved'">
                <span v-if="isEditable(point)" class="shrink-0 rounded-lg bg-[#EDD330] px-3 py-1.5 text-[10px] font-bold text-[#263D26] hover:bg-white cursor-pointer" @click.stop="openEdit(point)">Edit</span>
              </template>
              <template v-else>
                <span v-if="isEditable(point)" class="shrink-0 rounded-lg border border-[#EDD330]/50 px-3 py-1.5 text-[10px] font-bold text-[#EDD330] hover:bg-[#EDD330]/20 cursor-pointer" @click.stop="openEdit(point)">Edit</span>
              </template>
              <template v-if="$page.props.auth?.user?.role === 'Anggota'">
                <span v-if="point.status === 'Approved'" class="shrink-0 rounded-full bg-[#A7B92B]/20 px-2 py-0.5 text-[10px] font-bold text-[#A7B92B]">Disetujui</span>
                <span v-else-if="point.status === 'Pending'" class="shrink-0 rounded-full bg-[#EDD330]/20 px-2 py-0.5 text-[10px] font-bold text-[#EDD330]">Pending</span>
                <button v-else-if="isSubmissionAvailable(point)" type="button" @click.stop="openSubmission(point)" class="shrink-0 rounded-lg bg-[#A7B92B] px-3 py-1.5 text-[10px] font-bold text-[#263D26] hover:bg-[#EDD330]">Ajukan</button>
                <span v-else class="shrink-0 rounded-full bg-[#8fa06a]/10 px-2 py-0.5 text-[10px] font-medium text-[#8fa06a]">Belum Diajukan</span>
              </template>
            </div>
          </div>
        </div>
      </template>

      <template v-if="completed">
        <div class="py-8 text-center">
          <span class="mb-2 block text-5xl">✓</span>
          <p class="text-sm font-semibold text-[#A7B92B]">Selamat!</p>
          <p class="mt-1 text-xs text-[#8fa06a]">Anda telah menyelesaikan seluruh poin SKU dan TKK.</p>
        </div>
      </template>
      <template v-else-if="!hasFilteredResults">
        <p class="text-center text-xs text-[#8fa06a]">Tidak ada poin SKU untuk tingkatan ini.</p>
      </template>

      <div v-if="tkkPointsWithStatus.length" class="mt-4 border-t border-[#6F9435]/30 pt-4">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="text-sm font-bold text-[#EDD330]">TKK Wajib Penegak</h3>
          <button v-if="canApprove" type="button" @click="openTkkEdit(null)" class="inline-flex items-center rounded-lg bg-[#EDD330] px-3 py-1.5 text-xs font-bold text-[#263D26] hover:bg-white">Kelola Poin TKK</button>
        </div>
        <div class="space-y-2">
          <div v-for="tkk in tkkPointsWithStatus" :key="tkk.id" class="flex items-center gap-3 rounded-xl border border-[#6F9435]/30 bg-[#263D26] p-3">
            <span class="flex h-5 w-5 items-center justify-center rounded border border-[#6F9435]/50 text-xs" :class="tkk.status === 'Approved' ? 'bg-[#A7B92B]/30 border-[#A7B92B] text-[#A7B92B]' : 'text-[#8fa06a]'">
              <span v-if="tkk.status === 'Approved'">✓</span>
            </span>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-[#f0ead8]">{{ tkk.nama }}</p>
              <p v-if="tkk.deskripsi" class="text-xs text-[#8fa06a]">{{ tkk.deskripsi }}</p>
              <template v-if="nonAnggota && tkk.aggregate">
                <div class="mt-1 flex flex-wrap gap-1">
                  <span class="rounded-full bg-[#A7B92B]/20 px-2 py-0.5 text-[10px] font-bold text-[#A7B92B]">{{ tkk.aggregate.Approved || 0 }} disetujui</span>
                  <span class="rounded-full bg-[#EDD330]/20 px-2 py-0.5 text-[10px] font-bold text-[#EDD330]">{{ tkk.aggregate.Pending || 0 }} pending</span>
                  <span v-if="tkk.aggregate.Rejected" class="rounded-full bg-[#ef4419]/20 px-2 py-0.5 text-[10px] font-bold text-[#ef4419]">{{ tkk.aggregate.Rejected }} ditolak</span>
                </div>
              </template>
            </div>
            <template v-if="$page.props.auth?.user?.role === 'Anggota'">
              <span v-if="tkk.status === 'Approved'" class="shrink-0 rounded-full bg-[#A7B92B]/20 px-2 py-0.5 text-[10px] font-bold text-[#A7B92B]">Disetujui</span>
              <span v-else-if="tkk.status === 'Pending'" class="shrink-0 rounded-full bg-[#EDD330]/20 px-2 py-0.5 text-[10px] font-bold text-[#EDD330]">Pending</span>
              <span v-else-if="tkk.status === 'Rejected'" class="shrink-0 rounded-full bg-[#ef4419]/20 px-2 py-0.5 text-[10px] font-bold text-[#ef4419]">Ditolak</span>
              <span v-else class="shrink-0 rounded-full bg-[#8fa06a]/10 px-2 py-0.5 text-[10px] font-medium text-[#8fa06a]">Belum Diajukan</span>
            </template>
            <template v-else>
              <span class="shrink-0 rounded-lg border border-[#EDD330]/50 px-3 py-1.5 text-[10px] font-bold text-[#EDD330] hover:bg-[#EDD330]/20 cursor-pointer" @click.stop="openTkkEdit(tkk)">Edit</span>
            </template>
          </div>
        </div>
      </div>
    </section>

    <Modal v-if="showCreate" :title="selectedPoint ? `Ajukan poin ${selectedPoint.nomor_poin}` : 'Ajukan poin SKU'" @close="closeSubmission">
      <form @submit.prevent="submitSku" class="grid gap-3">
        <div v-if="selectedPoint" class="rounded-xl border border-[#A7B92B]/40 bg-[#263D26] p-3">
          <p class="text-xs font-bold text-[#EDD330]">{{ selectedPoint.tingkatan }} • Poin {{ selectedPoint.nomor_poin }}</p>
          <p class="mt-1 text-xs leading-5 text-[#d4dc9a]">{{ selectedPoint.deskripsi_poin }}</p>
        </div>
        <label v-else class="block">
          <span class="text-xs font-medium">Poin SKU</span>
          <select v-model="form.sku_point_id" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm">
            <option v-for="point in points" :key="point.id" :value="point.id">{{ point.tingkatan }} - Poin {{ point.nomor_poin }}</option>
          </select>
        </label>
        <label class="block">
          <span class="text-xs font-medium">Deskripsi penyelesaian</span>
          <textarea v-model="form.description" required rows="4" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"></textarea>
          <p v-if="form.errors.description" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.description }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium">Dokumentasi penyelesaian (wajib)</span>
          <input type="file" @change="onEvidenceChange" required accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="mt-1 text-sm" />
          <p class="mt-1 text-xs text-[#8fa06a]">Unggah foto, PDF, DOC, atau DOCX sebagai bukti penyelesaian poin.</p>
          <p v-if="form.errors.bukti_kegiatan" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.bukti_kegiatan }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium">Foto kegiatan tambahan (opsional, max 6)</span>
          <input type="file" @change="onFotoChange" multiple accept="image/jpeg,image/png,image/webp" class="mt-1 text-sm" />
          <p v-if="fotoFiles.length" class="mt-1 text-xs text-[#8fa06a]">{{ fotoFiles.length }} file dipilih</p>
          <p v-if="form.errors.foto" class="mt-1 text-xs text-[#ef4419]">{{ form.errors.foto }}</p>
        </label>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeSubmission" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="form.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">Kirim pengajuan</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showReject" title="Tolak pengajuan SKU" @close="showReject = false">
      <form @submit.prevent="formReject.post(`/sku/${rejectId}/reject`, { onSuccess: () => showReject = false })" class="grid gap-3">
        <label class="block">
          <span class="text-xs font-medium">Catatan koreksi</span>
          <textarea v-model="formReject.catatan" required rows="5" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm"></textarea>
        </label>
        <button class="rounded-lg bg-[#ef4419] px-4 py-2 text-sm font-semibold text-white">Tolak pengajuan</button>
      </form>
    </Modal>

    <Modal v-if="showEdit" :title="editingPoint ? `Edit Poin ${editingPoint.nomor_poin}` : 'Tambah Poin SKU'" @close="closeEdit">
      <form @submit.prevent="submitEdit" class="grid gap-3">
        <label v-if="!editingPoint" class="block">
          <span class="text-xs font-medium">Tingkatan</span>
          <select v-model="formEdit.tingkatan" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]">
            <option value="">Pilih</option>
            <option value="Bantara">Bantara</option>
            <option value="Laksana">Laksana</option>
          </select>
          <p v-if="formEdit.errors.tingkatan" class="mt-1 text-xs text-[#ef4419]">{{ formEdit.errors.tingkatan }}</p>
        </label>
        <label v-else class="block">
          <span class="text-xs font-medium">Tingkatan</span>
          <input v-model="formEdit.tingkatan" type="text" readonly class="mt-1 w-full rounded-lg border border-[#6F9435]/30 bg-[#263D26] px-3 py-2 text-sm text-[#8fa06a]" />
        </label>
        <label class="block">
          <span class="text-xs font-medium">Nomor Poin</span>
          <input v-model.number="formEdit.nomor_poin" type="number" min="1" max="999" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
          <p v-if="formEdit.errors.nomor_poin" class="mt-1 text-xs text-[#ef4419]">{{ formEdit.errors.nomor_poin }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium">Deskripsi Poin</span>
          <textarea v-model="formEdit.deskripsi_poin" required rows="4" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"></textarea>
          <p v-if="formEdit.errors.deskripsi_poin" class="mt-1 text-xs text-[#ef4419]">{{ formEdit.errors.deskripsi_poin }}</p>
        </label>
        <label class="flex items-center gap-2">
          <input v-model="formEdit.is_active" type="checkbox" class="h-4 w-4 rounded border-[#6F9435] bg-[#335233] text-[#A7B92B]" />
          <span class="text-xs font-medium">Aktif</span>
        </label>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeEdit" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="formEdit.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">{{ editingPoint ? 'Simpan' : 'Tambah' }}</button>
        </div>
      </form>
    </Modal>

    <Modal v-if="showTkkEdit" :title="editingTkkPoint ? `Edit Poin ${editingTkkPoint.nama}` : 'Tambah Poin TKK'" @close="closeTkkEdit">
      <form @submit.prevent="submitTkkEdit" class="grid gap-3">
        <label class="block">
          <span class="text-xs font-medium">Nama Poin TKK</span>
          <input v-model="formTkkEdit.nama" type="text" required class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]" />
          <p v-if="formTkkEdit.errors.nama" class="mt-1 text-xs text-[#ef4419]">{{ formTkkEdit.errors.nama }}</p>
        </label>
        <label class="block">
          <span class="text-xs font-medium">Deskripsi</span>
          <textarea v-model="formTkkEdit.deskripsi" rows="4" class="mt-1 w-full rounded-lg border border-[#6F9435] bg-[#335233] px-3 py-2 text-sm text-[#f0ead8] outline-none focus:border-[#EDD330]"></textarea>
          <p v-if="formTkkEdit.errors.deskripsi" class="mt-1 text-xs text-[#ef4419]">{{ formTkkEdit.errors.deskripsi }}</p>
        </label>
        <label class="flex items-center gap-2">
          <input v-model="formTkkEdit.is_active" type="checkbox" class="h-4 w-4 rounded border-[#6F9435] bg-[#335233] text-[#A7B92B]" />
          <span class="text-xs font-medium">Aktif</span>
        </label>
        <div class="flex justify-end gap-2">
          <button type="button" @click="closeTkkEdit" class="rounded-lg border border-[#6F9435] px-4 py-2 text-sm font-semibold text-[#d4dc9a]">Batal</button>
          <button type="submit" :disabled="formTkkEdit.processing" class="rounded-lg bg-gradient-to-r from-[#A7B92B] to-[#6F9435] px-4 py-2 text-sm font-semibold text-white">{{ editingTkkPoint ? 'Simpan' : 'Tambah' }}</button>
        </div>
      </form>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  submissions: Object,
  points: Array,
  progress: Object,
  skuPointsByLevel: Object,
  tkkPointsWithStatus: Array,
  submissionStats: Object,
  completed: Boolean,
});
const page = usePage();

const showCreate = ref(false);
const showReject = ref(false);
const showEdit = ref(false);
const showTkkEdit = ref(false);
const editingPoint = ref(null);
const editingTkkPoint = ref(null);
const rejectId = ref(null);
const rejectCatatan = ref('');
const selectedPoint = ref(null);
const fotoFiles = ref([]);
const filterTingkatan = ref(new URLSearchParams(page.url?.split('?')[1] || '').get('tingkatan') || '');

const form = useForm({ sku_point_id: '', description: '', bukti_kegiatan: null, foto: [] });
const formReject = useForm({ catatan: '' });
const formEdit = useForm({ tingkatan: '', nomor_poin: null, deskripsi_poin: '', is_active: true });
const formTkkEdit = useForm({ nama: '', deskripsi: '', is_active: true });

const nonAnggota = computed(() => page.props.auth?.user?.role !== 'Anggota');

const canApprove = computed(() => {
  const r = page.props.auth?.user?.role;
  return r === 'Admin' || r === 'Pembina';
});

const sectionTitle = computed(() => {
  const t = filterTingkatan.value;
  if (!t) {
    return 'Poin SKU';
  }

  return `Poin SKU ${t}`;
});

const hasFilteredResults = computed(() => {
  return Object.values(props.skuPointsByLevel).some((level) => level && level.length > 0);
});

function isEditable(point = null) {
  return page.props.auth?.user?.role === 'Pembina';
}

function isSubmissionAvailable(point) {
  return page.props.auth?.user?.role === 'Anggota' && point.status !== 'Approved' && point.status !== 'Pending';
}

function openSubmission(point = null) {
  if (page.props.auth?.user?.role !== 'Anggota') {
    return;
  }

  if (point && (point.status === 'Approved' || point.status === 'Pending')) {
    return;
  }

  selectedPoint.value = point;
  form.sku_point_id = point?.id ?? '';
  form.description = '';
  form.bukti_kegiatan = null;
  form.foto = [];
  fotoFiles.value = [];
  showCreate.value = true;
}

function closeSubmission() {
  showCreate.value = false;
  selectedPoint.value = null;
  form.reset();
  fotoFiles.value = [];
}

function submitSku() {
  form.post('/sku', {
    onSuccess: () => {
      closeSubmission();
    },
  });
}

function onEvidenceChange(event) {
  form.bukti_kegiatan = event.target.files[0] ?? null;
}

function onFotoChange(event) {
  fotoFiles.value = Array.from(event.target.files);
  form.foto = Array.from(event.target.files);
}

function decision(item, action) {
  router.post(`/sku/${item.id}/${action}`);
}

function openEdit(point = null) {
  editingPoint.value = point;

  if (point) {
    const fullPoint = props.points.find((p) => p.id === point.id);
    formEdit.tingkatan = fullPoint?.tingkatan ?? '';
    formEdit.nomor_poin = point.nomor_poin;
    formEdit.deskripsi_poin = point.deskripsi_poin;
    formEdit.is_active = fullPoint?.is_active ?? true;
  } else {
    formEdit.tingkatan = '';
    formEdit.nomor_poin = null;
    formEdit.deskripsi_poin = '';
    formEdit.is_active = true;
  }

  showEdit.value = true;
}

function closeEdit() {
  showEdit.value = false;
  editingPoint.value = null;
  formEdit.reset();
}

function submitEdit() {
  if (editingPoint.value) {
    formEdit.patch(`/sku/points/${editingPoint.value.id}`, {
      onSuccess: () => {
        closeEdit();
      },
    });
  } else {
    formEdit.post('/sku/points', {
      onSuccess: () => {
        closeEdit();
      },
    });
  }
}

function openTkkEdit(point = null) {
  editingTkkPoint.value = point;

  if (point) {
    formTkkEdit.nama = point.nama;
    formTkkEdit.deskripsi = point.deskripsi || '';
    formTkkEdit.is_active = point.is_active ?? true;
  } else {
    formTkkEdit.nama = '';
    formTkkEdit.deskripsi = '';
    formTkkEdit.is_active = true;
  }

  showTkkEdit.value = true;
}

function closeTkkEdit() {
  showTkkEdit.value = false;
  editingTkkPoint.value = null;
  formTkkEdit.reset();
}

function submitTkkEdit() {
  if (editingTkkPoint.value) {
    formTkkEdit.patch(`/tkk/points/${editingTkkPoint.value.id}`, {
      onSuccess: () => {
        closeTkkEdit();
      },
    });
  } else {
    formTkkEdit.post('/tkk/points', {
      onSuccess: () => {
        closeTkkEdit();
      },
    });
  }
}

function onFilterChange() {
  router.get('/sku', { tingkatan: filterTingkatan.value }, { preserveState: true });
}
</script>
