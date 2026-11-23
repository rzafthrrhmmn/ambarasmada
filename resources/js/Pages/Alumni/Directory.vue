<template>
  <AppLayout>
    <div class="page-head">
      <div>
        <p class="page-eyebrow">Portal Alumni</p>
        <h1 class="page-title">Direktori Purna Ambalan</h1>
        <p class="page-subtitle">Data sebaran alumni, domisili, profesi, dan kampus untuk jejaring lintas generasi.</p>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <template v-for="alumnus in directoryData" :key="alumnus.id">
        <section class="card p-5 flex flex-col">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-daun-500 flex items-center justify-center text-white font-bold text-xl">
              {{ alumnus.nama_lengkap?.charAt(0) || 'A' }}
            </div>
            <div>
              <h3 class="text-base font-bold text-krem-100">{{ alumnus.nama_lengkap }}</h3>
              <p class="text-xs text-daun-400 uppercase tracking-wider">{{ alumnus.alumni_profile?.status_saat_ini || 'Purna Ambalan' }}</p>
            </div>
          </div>
          <div class="space-y-2 flex-1">
            <div class="flex items-center gap-2 text-sm text-krem-300">
              <AppIcon name="map-pin" class="h-4 w-4" />
              <span>{{ alumnus.alumni_profile?.domisili || 'Domisili tidak diketahui' }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-krem-300">
              <AppIcon name="school" class="h-4 w-4" />
              <span>{{ alumnus.alumni_profile?.instansi_kampus || '-' }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-krem-300">
              <AppIcon name="briefcase" class="h-4 w-4" />
              <span>{{ alumnus.alumni_profile?.pekerjaan || '-' }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-krem-300">
              <AppIcon name="users" class="h-4 w-4" />
              <span>{{ alumnus.sangga?.nama || 'Sangga tidak tercatat' }}</span>
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-[#6F9435]/30">
            <div v-if="alumnus.alumni_profile?.show_contact && alumnus.user" class="flex items-center gap-2 text-sm text-emas-400">
              <AppIcon name="phone" class="h-4 w-4" />
              <span>{{ alumnus.user?.name }}</span>
            </div>
            <div v-else class="text-xs text-krem-500">Kontak tidak dipublikasikan</div>
          </div>
        </section>
      </template>
    </div>

    <div v-if="pagination.last_page > 1" class="mt-8 flex justify-center">
      <Pagination
        :page="pagination.current_page"
        :pages="pagination.last_page"
        :url="window => router.visit(window, { preserveState: true })"
      />
    </div>

    <p v-if="directoryData.length === 0" class="empty-state text-center py-12">
      <AppIcon name="users" class="empty-state-icon h-8 w-8 mx-auto mb-3" />
      <span class="empty-state-text">Belum ada data alumni tercatat.</span>
    </p>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Components/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import Pagination from '@/Components/Pagination.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const directory = computed(() => page.props?.directory || { data: [], current_page: 1, last_page: 1 });
const directoryData = computed(() => directory.value.data || []);
const pagination = computed(() => ({
  current_page: directory.value.current_page || 1,
  last_page: directory.value.last_page || 1,
}));
</script>
