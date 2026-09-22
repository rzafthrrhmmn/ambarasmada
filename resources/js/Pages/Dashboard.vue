<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-sm font-medium text-[#EDD330]">Dashboard</p>
        <h1 class="mt-1 text-2xl font-bold text-[#f0ead8]">Pantau Ambalan</h1>
        <p class="mt-1 text-sm text-[#8fa06a]">Semua aktivitas ambalan dalam satu layar.</p>
      </div>
      <div class="flex items-center gap-2">
        <Link href="/notifications" class="relative inline-flex items-center rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">
          🔔 Notifikasi
          <span v-if="unreadCount > 0" class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-[#EDD330] text-[10px] font-bold text-[#263D26]">{{ unreadCount }}</span>
        </Link>
        <Link href="/reports" class="inline-flex items-center rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">📊 Laporan</Link>
      </div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard v-for="(value, label) in stats" :key="label" :label="labels[label]" :value="value" :icon="icons[label]" />
    </section>

    <section class="mt-6 grid gap-6" :class="isMember ? 'xl:grid-cols-3' : 'xl:grid-cols-2'">
      <ActivityCalendar v-if="isMember" :sessions="upcomingSessions" class="xl:col-span-1" />
      <UpcomingActivities v-if="isMember" :sessions="upcomingSessions" :attended-session-ids="attendedSessionIds" :class="isMember ? 'xl:col-span-2' : 'xl:col-span-1'" />
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-3">
      <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-lg xl:col-span-2">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h2 class="font-extrabold text-[#EDD330]">Pengajuan SKU menunggu verifikasi</h2>
            <p class="text-xs font-medium text-[#8fa06a]">Antrean terbaru dari anggota ambalan</p>
          </div>
          <Link v-if="!isMember" href="/sku" class="text-xs font-bold text-[#A7B92B] hover:text-[#EDD330]">Lihat semua</Link>
        </div>
        <div v-if="pendingSku.length" class="space-y-3">
          <div v-for="submission in pendingSku" :key="submission.id" class="flex items-center justify-between gap-3 rounded-xl border-2 border-[#6F9435]/40 bg-[#263D26] p-3">
            <div class="min-w-0">
              <p class="truncate text-sm font-bold text-[#f0ead8]">{{ submission.member?.nama_lengkap }}</p>
              <p class="truncate text-xs font-medium text-[#8fa06a]">{{ submission.sku_point?.tingkatan }} • Poin {{ submission.sku_point?.nomor_poin }}</p>
            </div>
            <span class="rounded-full border-2 border-[#EDD330]/50 bg-[#EDD330]/20 px-2.5 py-1 text-xs font-bold text-[#EDD330]">Pending</span>
          </div>
        </div>
        <p v-else class="rounded-xl border-2 border-[#6F9435]/30 bg-[#263D26] p-5 text-center text-sm font-medium text-[#8fa06a]">Tidak ada pengajuan SKU baru.</p>
      </div>

      <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-[#335233] p-5 shadow-lg">
        <h2 class="font-extrabold text-[#EDD330]">Pengumuman terbaru</h2>
        <div class="mt-4 space-y-4">
          <article v-for="announcement in announcements" :key="announcement.id" class="border-b-2 border-[#6F9435]/30 pb-4 last:border-[#A7B92B] last:pb-0">
            <h3 class="text-sm font-bold text-[#f0ead8]">{{ announcement.judul }}</h3>
            <p class="mt-1 line-clamp-2 text-xs leading-5 font-medium text-[#8fa06a]">{{ announcement.isi }}</p>
            <time class="mt-2 block text-[11px] font-bold text-[#8fa06a]">{{ formatDate(announcement.published_at) }}</time>
          </article>
        </div>
      </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
      <div class="rounded-2xl border-2 border-[#6F9435]/30 bg-[#335233] p-5 shadow-lg">
        <h2 class="mb-3 font-extrabold text-[#EDD330]">Kegiatan Mendatang</h2>
        <div v-if="upcomingEvents.length" class="space-y-2">
          <div v-for="event in upcomingEvents" :key="event.id" class="rounded-lg border border-[#6F9435]/30 bg-[#263D26] p-3">
            <p class="text-sm font-bold text-[#f0ead8]">{{ event.nama }}</p>
            <p class="text-[10px] text-[#8fa06a]">{{ formatDate(event.tanggal) }} • {{ event.jenis }}</p>
          </div>
        </div>
        <p v-else class="text-center text-sm text-[#8fa06a]">Tidak ada kegiatan terdaftar.</p>
      </div>

      <div class="rounded-2xl border-2 border-[#6F9435]/30 bg-[#335233] p-5 shadow-lg">
        <h2 class="mb-3 font-extrabold text-[#EDD330]">Gugus Depan Aktif</h2>
        <div v-if="teams.length" class="flex flex-wrap gap-2">
          <span v-for="team in teams" :key="team.id" class="rounded-full bg-[#6F9435]/20 border border-[#6F9435]/50 px-3 py-1 text-xs font-bold text-[#d4dc9a]">{{ team.nama }}</span>
        </div>
        <p v-else class="text-center text-sm text-[#8fa06a]">Belum ada gugus depan.</p>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import StatCard from '@/Components/StatCard.vue';
import ActivityCalendar from '@/Components/ActivityCalendar.vue';
import UpcomingActivities from '@/Components/UpcomingActivities.vue';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';

defineProps({ member: Object, stats: Object, pendingSku: Array, announcements: Array, upcomingSessions: Array, attendedSessionIds: Array, tkuData: Array, tkkPoints: Array, skuPointsByLevel: Object, upcomingEvents: Array, teams: Array, unreadCount: Number });
const page = usePage();
const role = computed(() => page.props?.auth?.user?.role);
const isMember = computed(() => role.value === 'Anggota');
const isAdminOrPembina = computed(() => role.value === 'Pembina' || role.value === 'Admin');
const labels = { members: 'Total anggota', attendance: 'Rekap kehadiran', sku: 'Pengajuan SKU', finance: 'Transaksi kas' };
const icons = {
  members: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0z" /></svg>',
  attendance: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M9 9.563a3 3 0 1 0 5.138-2.121 3 3 0 0 0-5.138 2.121zM15 12l3.6-3.6m0 0L16.8 6.6m1.8 1.8h-3.6" /></svg>',
  sku: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>',
  finance: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125h20.25M12 6V5.25m0 13.5h.008v.008H12V18.75zm0-4.5h.008v.008H12v-.008zm0-4.5h.008v.008H12v-.008z" /></svg>',
};
function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
}
</script>
