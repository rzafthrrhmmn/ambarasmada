<template>
  <div
    class="w-[340px] h-[200px] rounded-xl overflow-hidden shadow-2xl relative"
    style="font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;"
  >
    <div class="absolute inset-0 bg-gradient-to-br from-[#1a2e1a] via-[#263D26] to-[#1a2e1a]">
      <div
        class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23EDD330%22 fill-opacity=%220.03%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"
        opacity-50
      />
    </div>

    <div class="absolute inset-0 border-2 border-[#EDD330]/60 rounded-xl pointer-events-none" />

    <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-[#EDD330] via-[#A7B92A] to-[#EDD330]" />
    <div class="absolute bottom-0 left-0 right-0 h-2 bg-gradient-to-r from-[#EDD330] via-[#A7B92A] to-[#EDD330]" />

    <div class="relative z-10 p-4 h-full flex flex-col">
      <div class="flex items-start justify-between mb-3">
        <div class="text-left">
          <p class="text-[11px] font-extrabold text-[#EDD330] tracking-wider uppercase">
            {{ ambalan?.nama || 'AMBALAN PRAMUKA' }}
          </p>
          <p class="text-[8px] text-[#8fa06a] tracking-widest mt-0.5">KARTU TANDA ANGGOTA</p>
          <p class="text-[7px] text-[#6F9435] tracking-wider mt-0.5">
            Ekosistem Digital Ambalan UPT SMAN 2 Maros
          </p>
        </div>
        <div class="flex items-center gap-2">
          <span
            class="rounded-full border border-[#6F9435] bg-[#263D26]/80 px-2 py-1 text-[8px] font-bold text-[#EDD330] uppercase tracking-wider"
          >
            {{ member.tingkatan }}
          </span>
          <span class="rounded-full bg-gradient-to-r from-[#EDD330] to-[#A7B92A] px-2 py-1 text-[8px] font-bold text-[#263D26] uppercase">
            {{ member.status_aktif }}
          </span>
        </div>
      </div>

      <div class="flex gap-3 flex-1 min-h-0">
        <div class="relative w-[80px] h-[80px] flex-shrink-0 rounded-lg overflow-hidden border-2 border-[#EDD330]/40 bg-[#1a2e1a] shadow-lg shadow-[#EDD330]/10">
          <div class="absolute inset-0 bg-gradient-to-br from-[#EDD330]/10 via-transparent to-[#A7B92A]/10" />
          <img
            v-if="member.user?.foto"
            :src="`/storage/${member.user.foto}`"
            alt="Foto"
            class="absolute inset-0 h-full w-full object-cover"
          />
          <div v-else class="absolute inset-0 flex items-center justify-center">
            <span class="text-3xl font-extrabold text-[#EDD330]/60">
              {{ (member.nama_lengkap || 'PA').charAt(0) }}
            </span>
          </div>
          <div class="absolute bottom-0 left-0 right-0 h-6 bg-gradient-to-t from-[#000]/80 to-transparent flex items-center justify-center">
            <span class="text-[7px] font-bold text-[#EDD330] tracking-widest">FOTO 3x4</span>
          </div>
        </div>

        <div class="flex-1 min-w-0 space-y-2.5 text-[#f0ead8]">
          <div class="flex items-baseline gap-2">
            <span class="text-xs font-bold text-[#EDD330] tracking-wide">NAMA</span>
            <span class="text-sm font-semibold leading-tight truncate">
              {{ member.nama_lengkap }}
            </span>
          </div>

          <div class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-1.5 text-[10px]">
            <span class="text-[#8fa06a] font-medium">TTL</span>
            <span class="text-[#d4dc9a]">
              {{ member.tempat_lahir || '-' }}, {{ member.tanggal_lahir ? new Date(member.tanggal_lahir).toLocaleDateString('id-ID') : '-' }}
            </span>

            <span class="text-[#8fa06a] font-medium">Jenis Kelamin</span>
            <span class="text-[#d4dc9a]">{{ member.jenis_kelamin || '-' }}</span>

            <span class="text-[#8fa06a] font-medium">NTA</span>
            <span class="text-[#EDD330] font-mono font-bold tracking-wider">
              {{ member.nta || '-' }}
            </span>

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
          <p class="text-[9px] font-bold text-[#EDD330]">
            {{ ambalan?.nama || 'Ambalan Pramuka SMAN 2 Maros' }}
          </p>
        </div>
        <div class="text-right">
          <p class="text-[7px] text-[#6F9435] tracking-wider">Tanggal Cetak</p>
          <p class="text-[9px] font-mono text-[#d4dc9a]">
            {{ new Date().toLocaleDateString('id-ID') }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  member: { type: Object, required: true },
  ambalan: { type: Object, default: null },
});
</script>