<template>
  <div class="min-h-screen bg-gradient-to-br from-[#263D26] via-[#2d4a2d] to-[#263D26] px-4 py-10">
    <div class="mx-auto max-w-5xl">
      <PhotoSlider
        v-if="combinedSlides.length > 0"
        :key="combinedSlides.length"
        :slides="combinedSlides"
        :interval="5000"
      />

      <div class="mb-10 mt-10 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
        <div>
          <div class="flex items-center gap-3">
            <span v-if="$page.props.ambalan?.logo_url" class="flex h-14 w-14 items-center justify-center rounded-xl border-2 border-[#6F9435] bg-[#263D26] overflow-hidden"><img :src="$page.props.ambalan.logo_url" alt="Logo Ambalan" class="h-full w-full object-contain" /></span>
            <span v-else class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-[#A7B92A] to-[#EDD330] text-xl font-extrabold text-[#263D26] shadow-lg shadow-[#EDD330]/40 border-2 border-[#EDD330]">P</span>
            <div>
              <p class="text-sm font-bold text-[#EDD330]">{{ $page.props.ambalan?.nama || "Ambalan UPT SMAN 2 Maros" }}</p>
              <h1 class="text-3xl font-extrabold text-[#f0ead8] sm:text-4xl" style="text-shadow: 2px 2px 0 rgba(0,0,0,0.3);">Satya dan Darma dalam satu genggaman.</h1>
            </div>
          </div>
          <p class="mt-4 max-w-2xl text-sm leading-6 font-medium text-[#d4dc9a]">Ekosistem digital untuk anggota, pembina, pengurus, dan alumni. Pantau SKU, presensi, kas, inventaris, dan kabar ambalan dengan lebih tertib.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <Link href="/login" class="rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#EDD330] px-5 py-2.5 text-sm font-extrabold text-[#263D26] shadow-lg shadow-[#EDD330]/40 transition hover:from-[#EDD330] hover:to-[#A7B92A] border-2 border-[#EDD330]">Masuk</Link>
          <Link href="/register" class="rounded-lg border-2 border-[#6F9435] px-5 py-2.5 text-sm font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">Daftar</Link>
          <a href="#pengumuman" class="rounded-lg border-2 border-[#6F9435] px-5 py-2.5 text-sm font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">Pengumuman</a>
        </div>
      </div>

      <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-lg"><p class="text-3xl font-extrabold text-[#EDD330]">{{ stats.members }}</p><p class="mt-1 text-sm font-bold text-[#d4dc9a]">Anggota aktif</p></div>
        <div class="rounded-2xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-lg"><p class="text-3xl font-extrabold text-[#EDD330]">{{ stats.alumni }}</p><p class="mt-1 text-sm font-bold text-[#d4dc9a]">Alumni tercatat</p></div>
        <div class="rounded-2xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-lg"><p class="text-3xl font-extrabold text-[#A7B92A]">5+</p><p class="mt-1 text-sm font-bold text-[#d4dc9a]">Layanan digital</p></div>
        <div class="rounded-2xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-lg"><p class="text-3xl font-extrabold text-[#A7B92A]">PWA</p><p class="mt-1 text-sm font-bold text-[#d4dc9a]">Akses mudah di ponsel</p></div>
      </section>

      <section id="pengumuman" class="mt-10 rounded-2xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-xl sm:p-7">
        <div class="mb-5 flex items-center justify-between">
          <div><p class="text-sm font-bold text-[#EDD330]">Kabar Ambalan</p><h2 class="mt-1 text-xl font-extrabold text-[#f0ead8]">Pengumuman terbaru</h2></div>
          <span class="rounded-full border-2 border-[#EDD330] bg-[#263D26] px-3 py-1 text-xs font-bold text-[#EDD330]">Publik</span>
        </div>
        <div class="grid gap-3 md:grid-cols-2">
          <article v-for="item in announcements" :key="item.id" class="rounded-xl border-2 border-[#A7B92A]/30 bg-[#263D26] p-4">
            <h3 class="font-extrabold text-[#EDD330]">{{ item.judul }}</h3>
            <p class="mt-2 text-sm leading-6 font-medium text-[#d4dc9a]">{{ item.isi }}</p>
            <time class="mt-3 block text-xs font-bold text-[#8fa06a]">{{ formatDate(item.published_at) }}</time>
          </article>
          <p v-if="!announcements.length" class="text-sm font-medium text-[#d4dc9a]">Belum ada pengumuman.</p>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import PhotoSlider from '@/Components/PhotoSlider.vue';
const props = defineProps({ announcements: Array, stats: Object, sliderSlides: Array });
const fallbackSlides = [
  { src: '/images/slider/slide-1.svg', title: 'Pramuka SMAN 2 Maros', description: 'Satya dan Darma dalam satu genggaman' },
  { src: '/images/slider/slide-2.svg', title: 'Kegiatan Inti', description: 'Pengembangan bakat dan kepribadian anggota' },
  { src: '/images/slider/slide-3.svg', title: 'Sistem Informasi', description: 'SKU, presensi, kas, inventaris — terintegrasi' },
];
const combinedSlides = computed(() => {
  if (props.sliderSlides && props.sliderSlides.length > 0) return props.sliderSlides;
  return fallbackSlides;
});
function formatDate(value) { return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'; }
</script>