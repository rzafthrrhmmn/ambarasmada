<template>
  <div ref="container" class="relative min-h-screen overflow-hidden bg-[#263D26]">

    <div class="pointer-events-none absolute inset-0">
      <div
        class="absolute -top-40 -left-40 h-[500px] w-[500px] rounded-full bg-gradient-to-br from-[#A7B92B]/15 via-[#A7B92B]/5 to-transparent blur-[120px]"
        data-parallax-bg
      ></div>
      <div class="absolute -right-40 top-20 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-[#EDD330]/15 via-[#EDD330]/5 to-transparent blur-[120px]"></div>
      <div class="absolute -bottom-40 left-1/3 h-[350px] w-[350px] rounded-full bg-gradient-to-br from-[#6F9435]/20 to-transparent blur-[100px]"></div>
    </div>

    <div class="absolute inset-0 pointer-events-none">
      <svg ref="particlesSvg" class="h-full w-full" preserveAspectRatio="none">
        <circle v-for="p in particles" :key="p.id" :cx="p.x" :cy="p.y" :r="p.size" :fill="p.color" opacity="0.6">
          <animate attributeName="cy" :from="p.y" :to="p.y + 20" dur="3s" repeatCount="indefinite" />
        </circle>
      </svg>
    </div>

    <div class="absolute top-4 right-4 z-20 hidden md:block">
      <div class="flex items-center gap-2 rounded-full bg-[#335233]/60 px-4 py-2 text-xs font-bold text-[#d4dc9a] backdrop-blur-sm">
        <span class="relative flex h-2 w-2">
          <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#A7B92B] opacity-75"></span>
          <span class="relative inline-flex h-2 w-2 rounded-full bg-[#A7B92B]"></span>
        </span>
        <span>Sistem Online</span>
      </div>
    </div>

    <div class="relative mx-auto w-full px-4 py-10 sm:px-6 sm:py-14 lg:px-[50px] lg:py-20">

      <header class="animate-fade-in-up" data-parallax-hero>
        <nav class="mb-8 flex justify-center">
          <div class="inline-flex items-center gap-6 rounded-2xl border border-[#6F9435]/30 bg-[#263D26]/50 px-6 py-3 backdrop-blur-sm">
            <a href="#fitur" class="text-sm font-medium text-[#d4dc9a] transition-colors hover:text-[#EDD330]">Fitur</a>
            <span class="h-4 w-px bg-[#6F9435]/30"></span>
            <a href="#dokumentasi" class="text-sm font-medium text-[#d4dc9a] transition-colors hover:text-[#EDD330]">Dokumentasi</a>
            <span class="h-4 w-px bg-[#6F9435]/30"></span>
            <a href="#pengumatan" class="text-sm font-medium text-[#d4dc9a] transition-colors hover:text-[#EDD330]">Pengumuman</a>
            <span class="h-4 w-px bg-[#6F9435]/30"></span>
            <a href="/login" class="text-sm font-medium text-[#d4dc9a] transition-colors hover:text-[#EDD330]">Masuk</a>
          </div>
        </nav>

        <section v-if="combinedSlides.length > 0" class="mb-10 animate-fade-in-up delay-75">
          <PhotoSlider :slides="combinedSlides" :interval="6000" />
        </section>

        <div class="mb-12 flex justify-center">
          <div
            class="group relative flex h-32 w-32 items-center justify-center rounded-3xl border-2 border-[#6F9435]/50 bg-[#263D26]/80 shadow-2xl shadow-[#A7B92B]/20 transition-transform duration-500 hover:rotate-[5deg] hover:scale-105"
          >
            <img
              v-if="$page.props.ambalan?.logo_url"
              :src="$page.props.ambalan.logo_url"
              alt="Logo Ambalan"
              class="relative h-full w-full object-contain"
            />
            <img
              v-else
              :src="logoFallback"
              alt="Logo Ambalan"
              class="relative h-full w-full object-contain"
            />
            <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-[#A7B92B] via-[#EDD330] to-[#A7B92B] opacity-0 blur transition-opacity duration-500 group-hover:opacity-60"></div>
          </div>
        </div>

        <h1 class="text-4xl font-black text-transparent sm:text-5xl lg:text-6xl xl:text-7xl mb-3">
          <span class="bg-gradient-to-r from-[#f0ead8] to-[#d4dc9a] bg-clip-text text-transparent">Satya dan Darma</span>
          <span class="relative inline-block bg-gradient-to-r from-[#A7B92B] via-[#EDD330] to-[#A7B92B] bg-clip-text text-transparent"> dalam satu genggaman.</span>
        </h1>

        <p class="mx-auto mt-4 max-w-lg text-lg leading-relaxed font-medium text-[#d4dc9a]/80">
          Ekosistem digital untuk anggota, pembina, pengurus, dan alumni. Pantau SKU, presensi, kas, inventaris, dan kabar ambalan dengan lebih tertib.
        </p>

        <div class="mt-8 flex flex-wrap justify-center gap-4">
          <Link
            href="/register"
            class="group relative isolate overflow-hidden rounded-2xl bg-gradient-to-r from-[#A7B92B] via-[#EDD330] to-[#A7B92B] px-8 py-4 text-sm font-black text-[#263D26] shadow-xl shadow-[#EDD330]/30 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-[#EDD330]/40 before:absolute before:inset-0 before:bg-white before:opacity-0 before:transition-opacity before:duration-300 group-hover:before:opacity-20"
          >
            <span class="relative z-10 flex items-center gap-2">
              Bergabung Sekarang
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2.5"
                stroke="currentColor"
                class="h-4 w-4 transition group-hover:translate-x-1"
              ><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path></svg>
            </span>
          </Link>
          <Link
            href="/login"
            class="rounded-2xl border-2 border-[#6F9435] bg-[#263D26]/40 px-8 py-4 text-sm font-bold text-[#d4dc9a] backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[#EDD330] hover:bg-[#6F9435]/20 hover:text-[#EDD330] hover:shadow-lg hover:shadow-[#6F9435]/20"
          >
            Masuk
          </Link>
          <a
            href="#fitur"
            class="rounded-2xl border-2 border-[#6F9435] bg-[#263D26]/40 px-8 py-4 text-sm font-bold text-[#d4dc9a] backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-[#EDD330] hover:bg-[#6F9435]/20 hover:text-[#EDD330] hover:shadow-lg hover:shadow-[#6F9435]/20"
          >
            Jelajahi Fitur
          </a>
        </div>
      </header>

      <section class="animate-fade-in-up delay-100" data-animate="stats">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <StatCard label="Anggota Aktif" :value="animatedStats.members" :icon="membersIcon" />
          <StatCard label="Alumni Tercatat" :value="animatedStats.alumni" :icon="alumniIcon" />
          <StatCard label="Layanan Digital" value="5+" :icon="servicesIcon" />
          <StatCard label="Akses Ponsel" value="PWA" :icon="pwaIcon" />
        </div>
      </section>

      <section
        v-if="gallery && gallery.length > 0"
        id="dokumentasi"
        class="mt-16 animate-fade-in-up delay-200"
        data-animate
      >
        <div class="mb-6 flex items-end justify-between">
          <div>
            <span class="text-xs font-bold uppercase tracking-widest text-[#A7B92B]">Dokumentasi Kegiatan</span>
            <h2 class="text-2xl font-extrabold text-transparent sm:text-3xl">
              <span class="bg-gradient-to-r from-[#f0ead8] to-[#d4dc9a] bg-clip-text">Dokumentasi</span>
              <span class="bg-gradient-to-r from-[#A7B92B] via-[#EDD330] to-[#A7B92B] bg-clip-text"> Kegiatan</span>
            </h2>
            <p class="mt-1 text-sm font-medium text-[#8fa06a]">Foto-foto dokumentasi dari kegiatan kepramukaan terbaru.</p>
          </div>
          <a
            href="/galleries"
            class="text-sm font-bold text-[#A7B92B] opacity-0 transition-opacity hover:text-[#EDD330] hover:opacity-100"
          >Lihat semua &rarr;</a>
        </div>
        <div class="relative">
          <div
            class="hide-scrollbar relative flex gap-3 overflow-x-auto pb-2"
            @scroll="onGalleryScroll"
          >
            <div
              v-for="(item, idx) in gallery"
              :key="idx"
              class="group relative flex-shrink-0 overflow-hidden rounded-2xl border-2 border-[#A7B92B]/20 bg-[#335233]/60 shadow-lg transition-all duration-300 first:ml-0 hover:-translate-y-1 hover:border-[#A7B92B]/40 hover:shadow-2xl hover:shadow-[#A7B92B]/10"
              :style="{ width: '280px' }"
            >
              <div class="relative h-48 overflow-hidden">
                <img
                  v-if="item.src"
                  :src="item.src"
                  :alt="item.title"
                  class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                  loading="lazy"
                />
                <div
                  v-else
                  class="flex h-full w-full items-center justify-center bg-gradient-to-br from-[#263D26] to-[#335233]"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="h-8 w-8 text-[#8fa06a]"
                  ><path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.25 15.75l5.25-5.25a2.25 2.25 0 013 0l3.75 3.75M9.75 12.75l.75.75m0 0l.75.75m-.75-.75v-6.75m-.75 6.75h6"
                  ></path></svg>
                </div>
                <div
                  class="absolute inset-0 bg-gradient-to-t from-[#263D26]/95 via-[#263D26]/30 to-transparent"
                ></div>
              </div>
              <div class="p-4">
                <span
                  class="mb-1 inline-block rounded-full bg-[#A7B92B]/15 px-2.5 py-0.5 text-xs font-bold text-[#EDD330]"
                >{{ item.kategori || 'Dokumentasi' }}</span>
                <h3
                  class="text-sm font-extrabold text-[#f0ead8] transition-colors group-hover:text-[#EDD330]"
                >{{ item.title || 'Tanpa judul' }}</h3>
                <p
                  v-if="item.description"
                  class="mt-1 line-clamp-2 text-xs leading-relaxed font-medium text-[#d4dc9a]/70"
                >{{ item.description }}</p>
              </div>
            </div>
          </div>

          <div
            v-if="gallery.length > 1"
            class="absolute right-0 top-1/2 hidden -translate-y-1/2 md:flex"
          >
            <button
              @click="scrollGallery('right')"
              class="rounded-full bg-[#263D26]/80 p-2.5 text-[#f0ead8 shadow-lg transition hover:bg-[#6F9435]/40 hover:text-[#EDD330]"
              aria-label="Gulir kanan"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2.5"
                stroke="currentColor"
                class="h-5 w-5"
              ><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"></path></svg>
            </button>
          </div>
          <div
            v-if="gallery.length > 1"
            class="absolute left-0 top-1/2 hidden -translate-y-1/2 md:flex"
          >
            <button
              @click="scrollGallery('left')"
              class="rounded-full bg-[#263D26]/80 p-2.5 text-[#f0ead8 shadow-lg transition hover:bg-[#6F9435]/40 hover:text-[#EDD330]"
              aria-label="Gulir kiri"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2.5"
                stroke="currentColor"
                class="h-5 w-5"
              ><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"></path></svg>
            </button>
          </div>
        </div>
      </section>

      <section id="fitur" class="mt-20 animate-fade-in-up delay-300" data-animate>
        <div class="mb-12 text-center">
          <div
            class="inline-flex items-center gap-2 rounded-full border border-[#EDD330]/30 bg-[#263D26]/50 px-4 py-1.5 text-xs font-bold text-[#EDD330] backdrop-blur-sm"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-[#A7B92B] animate-pulse"></span>
            Layanan Unggulan
          </div>
          <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl lg:text-5xl">
            <span class="bg-gradient-to-r from-[#f0ead8] to-[#d4dc9a] bg-clip-text text-transparent">Sistem Informasi</span>
            <span class="bg-gradient-to-r from-[#A7B92B] via-[#EDD330] to-[#A7B92B] bg-clip-text text-transparent"> Terintegrasi</span>
          </h2>
          <p class="mx-auto mt-4 max-w-lg text-sm font-medium text-[#d4dc9a]/80">
            Lima layanan digital yang saling terhubung untuk mendukung operasional ambalan secara efisien.
          </p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="(feature, idx) in features"
            :key="idx"
            class="group relative overflow-hidden rounded-2xl border border-[#A7B92B]/20 bg-[#335233]/60 p-6 shadow-lg transition-all duration-300 hover:-translate-y-2 hover:border-[#A7B92B]/40 hover:bg-[#335233]/80 hover:shadow-2xl hover:shadow-[#A7B92B]/10"
          >
            <div
              class="absolute inset-0 rounded-2xl bg-gradient-to-br from-[#A7B92B]/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
            ></div>
            <div class="relative flex items-start gap-5">
              <div
                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#263D26] to-[#335233] border border-[#6F9435]/30 text-[#EDD330] shadow-lg shadow-[#6F9435]/10 transition-transform group-hover:rotate-[5deg]"
              >
                <span v-html="feature.icon" class="flex h-7 w-7 items-center justify-center"></span>
              </div>
              <div>
                <h3
                  class="text-xl font-extrabold text-[#f0ead8] transition-colors group-hover:text-[#EDD330]"
                >{{ feature.title }}</h3>
                <p class="mt-2 text-sm leading-relaxed font-medium text-[#d4dc9a]/80">
                  {{ feature.description }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="pengumatan" class="mt-20 animate-fade-in-up delay-400" data-animate>
        <div class="mb-12 text-center">
          <div
            class="inline-flex items-center gap-2 rounded-full border border-[#EDD330]/30 bg-[#263D26]/50 px-4 py-1.5 text-xs font-bold text-[#EDD330]"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-[#EDD330] animate-pulse"></span>
            Aktivitas Terbaru
          </div>
          <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl">
            <span class="bg-gradient-to-r from-[#f0ead8] to-[#d4dc9a] bg-clip-text text-transparent">Pengumuman</span>
            <span class="bg-gradient-to-r from-[#A7B92B] via-[#EDD330] to-[#A7B92B] bg-clip-text text-transparent"> Terbaru</span>
          </h2>
          <p class="mx-auto mt-4 max-w-lg text-sm font-medium text-[#d4dc9a]/80">
            Ikuti perkembangan terbaru dari ambalan.
          </p>
        </div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="(item, idx) in announcements.slice(0, 6)"
            :key="item.id"
            class="group relative flex flex-col rounded-2xl border border-[#A7B92B]/10 bg-[#263D26]/50 p-6 shadow-lg backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#A7B92B]/30"
          >
            <div
              class="absolute inset-0 rounded-2xl bg-gradient-to-br from-[#A7B92B]/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
            ></div>
            <div class="relative mb-4 flex items-center justify-between">
              <span
                class="rounded-full bg-[#A7B92B]/15 px-3 py-1 text-xs font-bold text-[#EDD330]"
                >Terkini</span
              >
              <time class="text-xs font-bold text-[#8fa06a]" :datetime="item.published_at">
                {{ formatDate(item.published_at) }}
              </time>
            </div>
            <div class="relative">
              <h3
                class="text-lg font-extrabold text-[#f0ead8] transition-colors group-hover:text-[#EDD330]"
              >{{ item.judul }}</h3>
              <p class="mt-2 text-sm leading-relaxed font-medium text-[#d4dc9a]/70 line-clamp-3">
                {{ item.isi }}
              </p>
              <span
                class="mt-4 inline-flex items-center gap-1 text-xs font-bold text-[#A7B92B] transition group-hover:text-[#EDD330] group-hover:translate-x-1"
              >
                Baca selengkapnya
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="2.5"
                  stroke="currentColor"
                  class="h-3.5 w-3.5"
                ><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path></svg>
              </span>
            </div>
          </article>
          <div
            v-if="!announcements.length"
            class="rounded-2xl border-2 border-dashed border-[#6F9435]/30 p-12 text-center md:col-span-2 lg:col-span-3"
          >
            <p class="text-base font-medium text-[#d4dc9a]">Belum ada pengumuman.</p>
          </div>
        </div>
      </section>

      <section class="mt-20 animate-fade-in-up delay-500" data-animate>
        <div
          class="relative isolate overflow-hidden rounded-3xl border border-[#EDD330]/40 bg-gradient-to-r from-[#A7B92B] via-[#6F9435] to-[#263D26] p-12 text-center shadow-2xl sm:p-16"
        >
          <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-[#EDD330]/20 blur-3xl"></div>
          <div class="absolute -bottom-24 -right-24 h-80 w-80 rounded-full bg-[#A7B92B]/20 blur-3xl"></div>
          <div class="relative">
            <h2 class="text-3xl font-black text-white sm:text-4xl lg:text-5xl">
              Siap Membangun Ambalan Lebih Digital?
            </h2>
            <p class="mx-auto mt-4 max-w-lg text-base font-medium text-white/80">
              Gabung sekarang dan jadilah bagian dari ekosistem informasi kepramukaan yang modern.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
              <Link
                href="/register"
                class="rounded-2xl bg-[#263D26] px-9 py-4 text-sm font-black text-[#EDD330] shadow-xl shadow-black/30 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-black/40"
              >
                Bergabung Sekarang
              </Link>
              <Link
                href="/login"
                class="rounded-2xl border-2 border-[#263D26] bg-white/10 px-9 py-4 text-sm font-black text-[#263D26] transition-all duration-300 hover:-translate-y-0.5 hover:border-transparent hover:bg-white/20"
              >
                Masuk Akun
              </Link>
            </div>
          </div>
        </div>
      </section>

      <footer class="mt-20 animate-fade-in-up delay-600" data-animate>
        <div class="flex flex-col items-center justify-center gap-5 border-t border-[#6F9435]/20 pt-10 pb-6">
          <div class="flex items-center gap-3">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#A7B92B]/20 text-[#EDD330]"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                class="h-5 w-5"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"
              ></path></svg>
            </div>
            <span class="text-base font-bold text-[#d4dc9a]">{{ $page.props.ambalan?.nama || "Ambalan UPT SMAN 2 Maros" }}</span>
          </div>
          <p class="text-xs font-medium text-[#8fa06a]">
            &copy; 2026 Ekosistem Digital Kepramukaan Ambalan UPT SMAN 2 Maros &middot; Dibangun dengan kebanggaan.
          </p>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import PhotoSlider from '@/Components/PhotoSlider.vue';
import StatCard from '@/Components/StatCard.vue';

const props = defineProps({
  announcements: Array,
  stats: { type: Object, default: () => ({ members: 0, alumni: 0 }) },
  sliderSlides: Array,
  gallery: { type: Array, default: () => [] },
});

const animatedStats = ref({ members: 0, alumni: 0 });
const particles = ref([]);
const container = ref(null);
let animationFrameId;
let scrollHandler;
let revealObserver;

const membersIcon =
  '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>';
const alumniIcon =
  '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5c-1.501-1.266-2.5-2.998-2.5-5.063V6.75A2.25 2.25 0 016.75 4.5h.75A2.25 2.25 0 019.75 6.75v.75a2.25 2.25 0 012.25 2.25h1.5A2.25 2.25 0 0115.75 7.5v-.75a2.25 2.25 0 012.25-2.25h.75A2.25 2.25 0 0121 6.75v6.687c0 .842-.57 1.577-1.38 1.853l-3.15 1.589a.75.75 0 01-.78 0l-3.15-1.589A2.25 2.25 0 009.75 19.5H4.5zM9 6.75h6v.75H9V6.75z" /></svg>';
const servicesIcon =
  '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>';
const pwaIcon =
  '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>';

const features = [
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>',
    title: 'SKU Anggota',
    description: 'Pengelolaan Surat Keputusan Anggota secara digital, dari penerbitan hingga arsip, lengkap dengan status dan riwayat.',
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>',
    title: 'Presensi',
    description: 'Sistem kehadiran modern dengan pencatatan digital. Pantau kehadiran anggota secara real-time dari mana saja.',
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6H2.25m0 0v12.75A2.25 2.25 0 004.5 21h15a2.25 2.25 0 002.25-2.25V5.25A2.25 2.25 0 0019.5 3H3" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM15 8.25v.008v.008H15v-.008z" /></svg>',
    title: 'Kas Ambalan',
    description: 'Pengelolaan keuangan ambalan dengan pencatatan pemasukan dan pengeluaran yang transparan dan terstruktur.',
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3.75h3.75m-3.75 6.75h3.75m-12-9.75h9m-9 6.75h9" /></svg>',
    title: 'Inventaris',
    description: 'Daftar barang ambalan lengkap dengan kondisi, lokasi, dan riwayat penggunaan dalam satu sistem terpusat.',
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>',
    title: 'Dashboard',
    description: 'Ringkasan visual aktivitas ambalan dalam satu pandangan. Pantau tren, capaian, dan perkembangan secara cepat.',
  },
  {
    icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>',
    title: 'Profil Ambalan',
    description: 'Informasi lengkap tentang sejarah, visi, misi, dan struktur pengurus ambalan dalam tampilan yang profesional.',
  },
];

const fallbackSlides = [
  { src: '/images/slider/slide-1.svg', title: 'Pramuka SMAN 2 Maros', description: 'Satya dan Darma dalam satu genggaman' },
  { src: '/images/slider/slide-2.svg', title: 'Kegiatan Inti', description: 'Pengembangan bakat dan kepribadian anggota' },
  { src: '/images/slider/slide-3.svg', title: 'Sistem Informasi', description: 'SKU, presensi, kas, inventaris — terintegrasi' },
];

const logoFallback = '/images/Logo_Ambalan.png';

const combinedSlides = computed(() => {
  if (props.sliderSlides && props.sliderSlides.length > 0) return props.sliderSlides;
  return fallbackSlides;
});

function generateParticles() {
  const count = 35;
  const colors = ['#A7B92B', '#EDD330', '#6F9435'];
  particles.value = Array.from({ length: count }, (_, i) => ({
    id: i,
    x: Math.random() * (window.innerWidth || 1200),
    y: Math.random() * (window.innerHeight || 800),
    size: Math.random() * 3 + 1,
    color: colors[Math.floor(Math.random() * colors.length)],
    speed: Math.random() * 0.5 + 0.2,
  }));
}

function animateStats(target, key) {
  const duration = 1800;
  const startTime = performance.now();
  const step = (timestamp) => {
    const elapsed = timestamp - startTime;
    const progress = Math.min(elapsed / duration, 1);
    animatedStats.value[key] = Math.floor((target) * progress);
    if (progress < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}

const galleryContainer = ref(null);

function scrollGallery(direction) {
  if (!galleryContainer.value) return;
  const scrollAmount = 300;
  const current = galleryContainer.value.scrollLeft;
  galleryContainer.value.scrollTo({
    left: direction === 'right' ? current + scrollAmount : current - scrollAmount,
    behavior: 'smooth',
  });
}

function onGalleryScroll() {
  if (!container.value) return;
  const gallery = container.value.querySelector('[data-animate="gallery-scroll"]');
  if (gallery) {
    const scrollLeft = gallery.scrollLeft;
    const maxScroll = gallery.scrollWidth - gallery.clientWidth;
    const progress = maxScroll > 0 ? scrollLeft / maxScroll : 0;
    const nav = container.value.querySelector('[data-gallery-nav]');
    if (nav) {
      nav.style.opacity = progress > 0.05 ? '1' : '0.5';
    }
  }
}

onMounted(() => {
  generateParticles();

  revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          if (entry.target.dataset.animate === 'stats') {
            animateStats(props.stats?.members || 0, 'members');
            animateStats(props.stats?.alumni || 0, 'alumni');
          }
          revealObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.1 },
  );

  const animatable = container.value?.querySelectorAll('[data-animate]');
  animatable?.forEach((el) => revealObserver.observe(el));

  const animateParticles = () => {
    particles.value.forEach((p) => {
      p.y += p.speed;
      if (p.y > window.innerHeight) {
        p.y = 0;
        p.x = Math.random() * window.innerWidth;
      }
    });
    animationFrameId = requestAnimationFrame(animateParticles);
  };
  animateParticles();

  scrollHandler = () => {
    if (!container.value) return;
    const scrollY = window.scrollY;
    const bg = container.value.querySelector('[data-parallax-bg]');
    if (bg) bg.style.transform = `translateY(${scrollY * 0.3}px)`;
    const hero = container.value.querySelector('[data-parallax-hero]');
    if (hero) hero.style.transform = `translateY(${scrollY * 0.1}px)`;
  };
  window.addEventListener('scroll', scrollHandler);
});

onUnmounted(() => {
  window.removeEventListener('scroll', scrollHandler);
  if (animationFrameId) cancelAnimationFrame(animationFrameId);
  revealObserver?.disconnect();
});

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
}
</script>

<style scoped>
[data-animate] {
  opacity: 0;
  transform: translateY(20px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
[data-animate].is-visible {
  opacity: 1;
  transform: translateY(0);
}
</style>
