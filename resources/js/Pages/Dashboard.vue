<template>
  <AppLayout>
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end section-animate animate-fade-up">
      <div>
        <SkeletonLoader v-if="loading" variant="text" :lines="2" class="mb-1 h-6 w-40" />
        <p v-else class="text-sm font-medium text-emas-400">Dashboard</p>
        <SkeletonLoader v-if="loading" variant="text" :lines="1" class="mt-1 h-8 w-56" />
        <h1 v-else class="mt-1 text-2xl font-bold text-krem-100">Pantau Ambalan</h1>
        <SkeletonLoader v-if="loading" variant="text" :lines="1" class="mt-1 h-5 w-72" />
        <p v-else class="mt-1 text-sm text-krem-300">Semua aktivitas ambalan dalam satu layar.</p>
      </div>
      <div class="flex items-center gap-2">
        <SkeletonLoader v-if="loading" variant="card" class="h-9 w-36" />
        <a
          v-else
          href="/notifications"
          class="relative inline-flex items-center gap-2 rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition-all duration-300 hover:border-emas-400/70 hover:bg-daun-500/30 hover:text-emas-400 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-daun-500/20 active:scale-95"
        >
          <AppIcon name="bell" class="h-4 w-4 transition-transform duration-300 group-hover:rotate-6" />
          Notifikasi
          <span
            v-if="unreadCount > 0"
            class="absolute -right-2 -top-2 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-emas-400 px-1 text-[10px] font-bold text-[#263D26] animate-soft-pulse"
          >
            {{ unreadCount > 99 ? '99+' : unreadCount }}
          </span>
        </a>
        <SkeletonLoader v-if="loading" variant="card" class="h-9 w-20" />
        <a
          v-else
          href="/reports"
          class="inline-flex items-center gap-2 rounded-lg border-2 border-[#6F9435] px-3 py-2 text-xs font-bold text-[#d4dc9a] transition-all duration-300 hover:border-emas-400/70 hover:bg-daun-500/30 hover:text-emas-400 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-daun-500/20 active:scale-95"
        >
          <AppIcon name="reports" class="h-4 w-4" />
          Laporan
        </a>
      </div>
    </div>

    <!-- Stat Cards Section -->
    <section v-if="canViewMetrics" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 section-animate animate-stagger-reveal">
      <StatCard
        v-for="(value, label) in stats"
        :key="label"
        :label="labels[label]"
        :value="value"
        :icon="icons[label]"
        class="group hover:-translate-y-1 hover:shadow-xl hover:shadow-emas-400/10 transition-all duration-300"
        v-if="!loading"
      />
      <SkeletonLoader v-for="i in (loading ? 4 : 0)" :key="i" variant="card" class="h-24 animate-pulse" />
    </section>

    <!-- Activity / Upcoming Section -->
    <section class="mt-6 grid gap-6 section-animate animate-fade-up" :class="isAnggota ? 'xl:grid-cols-3' : 'xl:grid-cols-2'">
      <div v-if="loading" class="space-y-4 xl:col-span-1 animate-pulse">
        <SkeletonLoader variant="chart" />
      </div>
      <ActivityCalendar v-else-if="isAnggota" :sessions="upcomingSessions" class="xl:col-span-1 group hover:shadow-xl hover:shadow-daun-500/10 transition-all duration-300" />
      <SkeletonLoader v-if="loading" variant="chart" :class="{'xl:col-span-2': !isAnggota, 'xl:col-span-1': isAnggota}" animate-pulse />
      <UpcomingActivities v-else-if="isAnggota" :sessions="upcomingSessions" :attended-session-ids="attendedSessionIds" :class="isAnggota ? 'xl:col-span-2' : 'xl:col-span-1'" class="group hover:shadow-xl hover:shadow-daun-500/10 transition-all duration-300" />
    </section>

    <!-- SKU Pending + Announcements -->
    <section class="mt-6 grid gap-6 xl:grid-cols-3 section-animate animate-fade-up">
      <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-hutan-600 p-5 shadow-lg xl:col-span-2 group hover:shadow-xl hover:shadow-daun-500/10 transition-all duration-300">
        <div class="mb-4 flex items-center justify-between">
          <div v-if="loading">
            <SkeletonLoader variant="text" :lines="1" class="h-6 w-48 animate-pulse" />
            <SkeletonLoader variant="text" :lines="1" class="mt-1 h-4 w-36 animate-pulse" />
          </div>
          <div v-else>
            <h2 class="font-extrabold text-emas-400">Pengajuan SKU menunggu verifikasi</h2>
            <p class="text-xs font-medium text-krem-300">Antrean terbaru dari anggota ambalan</p>
          </div>
          <SkeletonLoader v-if="loading" variant="text" :lines="1" class="h-5 w-24 animate-pulse" />
          <a v-else-if="!isAnggota" href="/sku" class="text-xs font-bold text-[#A7B92B] hover:text-emas-400 transition-colors">Lihat semua</a>
        </div>
        <div v-if="loading" class="space-y-3 animate-pulse">
          <SkeletonLoader v-for="i in 3" :key="i" variant="list" :lines="2" class="h-14" />
        </div>
        <div v-else-if="pendingSku.length" class="space-y-3">
          <div v-for="submission in pendingSku" :key="submission.id" class="flex items-center justify-between gap-3 rounded-xl border-2 border-[#6F9435]/40 bg-hutan-800 p-3 group hover:border-daun-400/60 hover:bg-hutan-600/60 transition-all duration-200">
            <div class="min-w-0">
              <p class="truncate text-sm font-bold text-krem-100">{{ submission.member?.nama_lengkap }}</p>
              <p class="truncate text-xs font-medium text-krem-300">{{ submission.sku_point?.tingkatan }} • Poin {{ submission.sku_point?.nomor_poin }}</p>
            </div>
            <span class="rounded-full border-2 border-emas-400/50 bg-emas-400/20 px-2.5 py-1 text-xs font-bold text-emas-400 animate-soft-pulse">Pending</span>
          </div>
        </div>
        <p v-else class="rounded-xl border-2 border-[#6F9435]/30 bg-hutan-800 p-5 text-center text-sm font-medium text-krem-300">Tidak ada pengajuan SKU baru.</p>
      </div>

      <div class="rounded-2xl border-2 border-[#A7B92B]/40 bg-hutan-600 p-5 shadow-lg group hover:shadow-xl hover:shadow-daun-500/10 transition-all duration-300">
        <SkeletonLoader v-if="loading" variant="text" :lines="1" class="h-6 w-40 animate-pulse" />
        <h2 v-else class="font-extrabold text-emas-400">Pengumuman terbaru</h2>
        <div v-if="loading" class="mt-4 space-y-4 animate-pulse">
          <SkeletonLoader v-for="i in 3" :key="i" variant="text" :lines="3" class="h-16" />
        </div>
        <div v-else class="mt-4 space-y-4">
          <article v-for="announcement in announcements" :key="announcement.id" class="border-b-2 border-[#6F9435]/30 pb-4 last:border-[#A7B92B] last:pb-0 group hover:border-daun-400/60 transition-colors duration-200">
            <h3 class="text-sm font-bold text-krem-100 group-hover:text-emas-400 transition-colors">{{ announcement.judul }}</h3>
            <p class="mt-1 line-clamp-2 text-xs leading-5 font-medium text-krem-300">{{ announcement.isi }}</p>
            <time class="mt-2 block text-[11px] font-bold text-krem-300">{{ formatDate(announcement.published_at) }}</time>
          </article>
        </div>
      </div>
    </section>

    <!-- Upcoming Events + Teams -->
    <section class="mt-6 grid gap-6 xl:grid-cols-2 section-animate animate-fade-up">
      <div class="rounded-2xl border-2 border-[#6F9435]/30 bg-hutan-600 p-5 shadow-lg group hover:shadow-xl hover:shadow-daun-500/10 transition-all duration-300">
        <SkeletonLoader v-if="loading" variant="text" :lines="1" class="h-6 w-32 animate-pulse" />
        <h2 v-else class="mb-3 font-extrabold text-emas-400">Kegiatan Mendatang</h2>
        <div v-if="loading" class="space-y-2 animate-pulse">
          <SkeletonLoader v-for="i in 3" :key="i" variant="text" :lines="2" class="h-14" />
        </div>
        <div v-else-if="upcomingEvents.length" class="space-y-2">
          <div v-for="event in upcomingEvents" :key="event.id" class="rounded-lg border border-[#6F9435]/30 bg-hutan-800 p-3 group hover:border-daun-400/60 hover:bg-hutan-600/60 transition-all duration-200">
            <p class="text-sm font-bold text-krem-100 group-hover:text-emas-400 transition-colors">{{ event.nama }}</p>
            <p class="text-[10px] text-krem-300">{{ formatDate(event.tanggal) }} • {{ event.jenis }}</p>
          </div>
        </div>
        <p v-else class="empty-inline">Tidak ada kegiatan terdaftar.</p>
      </div>

      <div class="rounded-2xl border-2 border-[#6F9435]/30 bg-hutan-600 p-5 shadow-lg group hover:shadow-xl hover:shadow-daun-500/10 transition-all duration-300">
        <SkeletonLoader v-if="loading" variant="text" :lines="1" class="h-6 w-28 animate-pulse" />
        <h2 v-else class="mb-3 font-extrabold text-emas-400">Gugus Depan Aktif</h2>
        <div v-if="loading" class="mt-3 flex flex-wrap gap-2 animate-pulse">
          <SkeletonLoader v-for="i in 4" :key="i" variant="text" :lines="1" class="h-6 w-16" />
        </div>
        <div v-else-if="teams.length" class="mt-3 flex flex-wrap gap-2">
          <span v-for="team in teams" :key="team.id" class="rounded-full bg-daun-500/20 border border-[#6F9435]/50 px-3 py-1 text-xs font-bold text-[#d4dc9a] group hover:bg-daun-500/30 hover:border-emas-400 transition-all duration-200">{{ team.nama }}</span>
        </div>
        <p v-else class="empty-inline">Belum ada gugus depan.</p>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import StatCard from '@/Components/StatCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import ActivityCalendar from '@/Components/ActivityCalendar.vue';
import UpcomingActivities from '@/Components/UpcomingActivities.vue';
import SkeletonLoader from '@/Components/SkeletonLoader.vue';
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/AppLayout.vue';
import { useAccess } from '@/Composables/useAccess.js';

defineProps({ member: Object, stats: Object, pendingSku: Array, announcements: Array, upcomingSessions: Array, attendedSessionIds: Array, tkuData: Array, tkkPoints: Array, skuPointsByLevel: Object, upcomingEvents: Array, teams: Array, unreadCount: Number });
const page = usePage();
const { can, isAnggota } = useAccess();

const canViewMetrics = computed(() => can('dashboard.view_metrics'));

const loading = computed(() => !page.props.stats && !page.props.pendingSku && !page.props.announcements);
const labels = { members: 'Total anggota', attendance: 'Rekap kehadiran', sku: 'Pengajuan SKU', finance: 'Transaksi kas' };
const icons = { members: 'members', attendance: 'attendance', sku: 'audit', finance: 'wallet' };
function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
}
</script>
