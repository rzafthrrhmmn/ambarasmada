<template>
  <div ref="container" class="relative min-h-screen overflow-hidden bg-hutan-800 font-sans guest-scroll">
    <ParticleBackground
      :particle-count="isMobile ? 40 : 80"
      :colors="['#A7B92B', '#EDD330', '#6F9435', '#f0ead8']"
      :interactive="true"
      :connection-distance="140"
      :mouse-influence="180"
      class="absolute inset-0"
    />

    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
      <div class="absolute -top-40 -left-40 h-[500px] w-[500px] rounded-full bg-gradient-to-br from-daun-400/15 via-daun-400/5 to-transparent blur-[100px] will-change-transform" data-parallax-bg></div>
      <div class="absolute -right-40 top-20 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-emas-400/15 via-emas-400/5 to-transparent blur-[100px] will-change-transform"></div>
      <div class="absolute -bottom-40 left-1/3 h-[350px] w-[350px] rounded-full bg-gradient-to-br from-daun-500/20 to-transparent blur-[100px] will-change-transform"></div>
      <div class="guest-noise-overlay absolute inset-0 opacity-30"></div>
    </div>

    <a href="#main-content" class="guest-focus-ring skip-link">Lewati ke konten utama</a>

    <nav class="sticky top-4 z-40 mx-auto w-full max-w-[1400px] guest-container">
      <div class="glass-panel flex items-center justify-between px-4 py-2.5 sm:px-6 sm:py-3">
        <Link href="/" class="flex items-center gap-3 group">
          <div class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-daun-500/60 bg-hutan-800 transition-all duration-300 group-hover:border-emas-400/60">
            <img
              :src="logoUrl"
              alt="Logo Ambalan UPT SMAN 2 Maros"
              class="guest-logo guest-logo-sm"
            />
          </div>
          <div class="hidden sm:block">
            <p class="text-sm font-black text-krem-100">{{ ambalanNama }}</p>
            <p class="text-[11px] font-bold text-lumut-400">Ekosistem Digital Kepramukaan</p>
          </div>
        </Link>

        <div class="hidden items-center gap-1 lg:flex">
          <a href="#fitur" class="guest-focus-ring rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400">Fitur</a>
          <a href="#dokumentasi" class="guest-focus-ring rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400">Dokumentasi</a>
          <a href="#testimoni" class="guest-focus-ring rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400">Testimoni</a>
          <a href="#pengumatan" class="guest-focus-ring rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400">Pengumuman</a>
          <span class="mx-2 h-4 w-px bg-daun-500/30"></span>
          <Link href="/login" class="guest-focus-ring rounded-xl px-4 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400">Masuk</Link>
          <Link href="/register" class="guest-focus-ring guest-btn-shimmer rounded-xl bg-gradient-to-r from-daun-400 to-emas-400 px-4 py-2 text-sm font-black text-hutan-800 shadow-lg shadow-emas-400/20 transition hover:brightness-110 hover:shadow-xl hover:shadow-emas-400/30 active:scale-95">Daftar</Link>
        </div>

        <button
          id="mobile-menu-button"
          class="guest-focus-ring flex h-10 w-10 items-center justify-center rounded-xl border-2 border-daun-500/40 text-krem-300 transition hover:border-emas-400 hover:text-emas-400 lg:hidden"
          @click="openMobileMenu"
          :aria-expanded="uiStore.mobileMenuOpen"
          aria-controls="mobile-menu-overlay"
          aria-label="Buka menu"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
        </button>
      </div>
    </nav>

    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="uiStore.mobileMenuOpen"
          id="mobile-menu-overlay"
          class="fixed inset-0 z-50 flex items-center justify-center bg-hutan-900/90 backdrop-blur-xl"
          @click.self="closeMobileMenu"
          role="dialog"
          aria-modal="true"
          aria-label="Menu navigasi"
        >
          <div class="mx-4 w-full max-w-sm rounded-3xl border-2 border-daun-500/40 bg-hutan-800 p-6 shadow-2xl">
            <div class="mb-6 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <img :src="logoUrl" alt="Logo Ambalan" class="h-10 w-10 object-contain p-1" />
                <span class="text-lg font-black text-krem-100">Menu</span>
              </div>
              <button
                id="mobile-menu-close"
                @click="closeMobileMenu"
                class="guest-focus-ring flex h-10 w-10 items-center justify-center rounded-full border-2 border-daun-500 text-krem-300 transition hover:text-emas-400"
                aria-label="Tutup menu"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="flex flex-col gap-3">
              <a href="#fitur" @click="closeMobileMenu" class="guest-focus-ring rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Fitur</a>
              <a href="#dokumentasi" @click="closeMobileMenu" class="guest-focus-ring rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Dokumentasi</a>
              <a href="#testimoni" @click="closeMobileMenu" class="guest-focus-ring rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Testimoni</a>
              <a href="#pengumatan" @click="closeMobileMenu" class="guest-focus-ring rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Pengumuman</a>
              <Link href="/login" @click="closeMobileMenu" class="guest-focus-ring rounded-2xl border-2 border-daun-500 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Masuk</Link>
              <Link href="/register" @click="closeMobileMenu" class="guest-focus-ring rounded-2xl bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 px-4 py-3 text-center font-black text-hutan-800 shadow-lg transition hover:brightness-110 active:scale-95">Bergabung Sekarang</Link>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <main id="main-content" class="relative mx-auto w-full px-4 py-10 sm:px-6 sm:py-14 lg:px-[50px] lg:py-20">
      <header class="mb-16" data-parallax-hero>
        <section v-if="sliderSlides.length > 0" class="mb-10">
          <PhotoSlider :slides="sliderSlides" :interval="6000" />
        </section>

        <div class="flex flex-col items-center text-center">
          <ScrollReveal animation="zoom-in" :delay="100">
            <div class="group relative mb-8 flex h-36 w-36 items-center justify-center rounded-3xl border-2 border-daun-500/50 bg-hutan-800/80 shadow-2xl shadow-daun-400/20 transition-all duration-500 hover:rotate-[5deg] hover:scale-105 sm:h-44 sm:w-44">
              <img
                :src="logoUrl"
                alt="Logo Ambalan UPT SMAN 2 Maros"
                class="guest-logo guest-logo-lg"
              />
              <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 opacity-0 blur transition-opacity duration-500 group-hover:opacity-60"></div>
            </div>
          </ScrollReveal>

          <ScrollReveal animation="fade-up" :delay="200">
            <h1 class="text-4xl font-black text-transparent sm:text-5xl lg:text-6xl xl:text-7xl mb-4">
              <span class="guest-text-gradient">Satya dan Darma</span>
              <br class="sm:hidden" />
              <span class="relative inline-block guest-text-gradient-gold"> dalam satu genggaman.</span>
            </h1>
          </ScrollReveal>

          <ScrollReveal animation="fade-up" :delay="300">
            <p class="mx-auto mt-4 max-w-2xl text-lg leading-relaxed font-medium text-krem-300/80 sm:text-xl">
              Ekosistem digital untuk anggota, pembina, pengurus, dan alumni. Pantau SKU, presensi, kas, inventaris, dan kabar ambalan dengan lebih tertib.
            </p>
          </ScrollReveal>

          <ScrollReveal animation="fade-up" :delay="400">
            <div class="mt-8 flex flex-wrap justify-center gap-4">
              <Link
                href="/register"
                class="guest-focus-ring group relative isolate overflow-hidden rounded-2xl bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 px-8 py-4 text-sm font-black text-hutan-800 shadow-xl shadow-emas-400/30 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-emas-400/40 active:scale-95 before:absolute before:inset-0 before:bg-white before:opacity-0 before:transition-opacity before:duration-300 hover:before:opacity-20"
              >
                <span class="relative z-10 flex items-center gap-2">
                  Bergabung Sekarang
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4 transition group-hover:translate-x-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                  </svg>
                </span>
              </Link>
              <Link
                href="/login"
                class="guest-focus-ring rounded-2xl border-2 border-daun-500 bg-hutan-800/40 px-8 py-4 text-sm font-bold text-krem-300 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-emas-400 hover:bg-daun-500/20 hover:text-emas-400 hover:shadow-lg hover:shadow-daun-500/20 active:scale-95"
              >
                Masuk
              </Link>
            </div>
          </ScrollReveal>
        </div>
      </header>

      <section class="guest-section" data-animate="stats" id="stats">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <ScrollReveal v-for="(stat, idx) in statsItems" :key="stat.label" :delay="idx * 100" :stagger="true" :style="`--stagger-index: ${idx}`">
            <div class="guest-stat-card group">
              <span aria-hidden="true" class="guest-stat-card-glow" />
              <div class="relative flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-xs font-bold uppercase tracking-wide text-lumut-400">{{ stat.label }}</p>
                  <p class="mt-2 text-3xl font-extrabold text-emas-400 sm:text-4xl">{{ animatedStats[stat.key] }}</p>
                </div>
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border-2 border-daun-500/40 bg-hutan-800 text-emas-400 transition duration-200 group-hover:border-emas-400/50 group-hover:text-emas-400">
                  <AppIcon :name="stat.icon" class="h-6 w-6" />
                </span>
              </div>
            </div>
          </ScrollReveal>
        </div>
      </section>

      <section id="cara-kerja" class="guest-section scroll-mt-24">
        <div class="mb-12 text-center">
          <ScrollReveal animation="fade-up">
            <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
              <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse"></span>
              Cara Kerja
            </div>
          </ScrollReveal>
          <ScrollReveal animation="fade-up" :delay="100">
            <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl lg:text-5xl">
              <span class="guest-text-gradient">Mulai dalam</span>
              <span class="guest-text-gradient-gold"> 3 Langkah</span>
            </h2>
          </ScrollReveal>
        </div>
        <div class="grid gap-6 sm:grid-cols-3">
          <ScrollReveal v-for="(step, idx) in howItWorks" :key="idx" :delay="idx * 150" :stagger="true" :style="`--stagger-index: ${idx}`" animation="fade-up">
            <div class="relative flex h-full flex-col items-center rounded-3xl border-2 border-daun-500/30 bg-hutan-600/60 p-8 text-center shadow-lg transition-all duration-300 hover:-translate-y-2 hover:border-daun-400/60 hover:bg-hutan-600/80">
              <div class="absolute -top-4 left-1/2 -translate-x-1/2 flex h-8 w-8 items-center justify-center rounded-full border-2 border-daun-400 bg-hutan-800 text-sm font-black text-emas-400">
                {{ idx + 1 }}
              </div>
              <div class="mt-4 mb-4 flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-daun-500/30 bg-hutan-800 text-2xl font-black text-emas-400 transition-transform hover:rotate-6">
                {{ step.emoji }}
              </div>
              <h3 class="text-xl font-extrabold text-krem-100">{{ step.title }}</h3>
              <p class="mt-3 text-sm leading-relaxed font-medium text-krem-300/80">{{ step.description }}</p>
            </div>
          </ScrollReveal>
        </div>
      </section>

      <section v-if="guestStore.gallery.length > 0" id="dokumentasi" class="guest-section scroll-mt-24">
        <div class="mb-8 flex items-end justify-between">
          <div>
            <ScrollReveal animation="fade-up">
              <span class="section-eyebrow text-daun-400">Dokumentasi Kegiatan</span>
              <h2 class="text-2xl font-black text-transparent sm:text-3xl">
                <span class="guest-text-gradient">Dokumentasi</span>
                <span class="guest-text-gradient-gold"> Kegiatan</span>
              </h2>
              <p class="mt-1 text-sm font-medium text-lumut-400">Foto-foto dokumentasi dari kegiatan kepramukaan terbaru.</p>
            </ScrollReveal>
          </div>
          <ScrollReveal animation="fade-up" :delay="100">
            <Link href="/galleries" class="text-sm font-bold text-daun-400 opacity-70 transition-opacity hover:opacity-100 hover:text-emas-400">
              Lihat semua &rarr;
            </Link>
          </ScrollReveal>
        </div>
        <ScrollReveal animation="fade-up" :delay="150">
          <div class="relative">
            <div
              ref="galleryContainer"
              class="guest-gallery-track"
            >
              <button
                v-for="(item, idx) in guestStore.gallery"
                :key="idx"
                class="guest-gallery-item"
                style="width: min(300px, 80vw);"
                @click="openLightbox(idx)"
                :aria-label="`Lihat ${item.title || 'foto'}`"
              >
                <div class="relative h-52 overflow-hidden">
                  <img
                    v-if="item.src"
                    :src="item.src"
                    :alt="item.title"
                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                    loading="lazy"
                  />
                  <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-hutan-800 to-hutan-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-lumut-400">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.25-5.25a2.25 2.25 0 013 0l3.75 3.75M9.75 12.75l.75.75m0 0l.75.75m-.75-.75v-6.75m-.75 6.75h6" />
                    </svg>
                  </div>
                  <div class="absolute inset-0 bg-gradient-to-t from-hutan-800/95 via-hutan-800/20 to-transparent opacity-60 transition-opacity group-hover:opacity-80"></div>
                  <div class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-emas-400 bg-hutan-800/90 text-emas-400 backdrop-blur-sm">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                      </svg>
                    </div>
                  </div>
                </div>
                <div class="p-4">
                  <span class="mb-1 inline-block rounded-full bg-daun-400/15 px-2.5 py-0.5 text-xs font-bold text-emas-400">{{ item.kategori || 'Dokumentasi' }}</span>
                  <h3 class="text-sm font-extrabold text-krem-100 transition-colors group-hover:text-emas-400">{{ item.title || 'Tanpa judul' }}</h3>
                  <p v-if="item.description" class="mt-1 line-clamp-2 text-xs leading-relaxed font-medium text-krem-300/70">{{ item.description }}</p>
                </div>
              </button>
            </div>

            <button
              v-if="guestStore.gallery.length > 1"
              @click="scrollGallery('left')"
              :disabled="galleryScrollLeft <= 0"
              class="guest-gallery-nav left-2"
              aria-label="Gulir kiri"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
              </svg>
            </button>
            <button
              v-if="guestStore.gallery.length > 1"
              @click="scrollGallery('right')"
              :disabled="galleryScrollRight <= 0"
              class="guest-gallery-nav right-2"
              aria-label="Gulir kanan"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
              </svg>
            </button>
          </div>
        </ScrollReveal>
      </section>

      <section id="fitur" class="guest-section scroll-mt-24">
        <div class="mb-12 text-center">
          <ScrollReveal animation="fade-up">
            <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
              <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse"></span>
              Layanan Unggulan
            </div>
          </ScrollReveal>
          <ScrollReveal animation="fade-up" :delay="100">
            <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl lg:text-5xl">
              <span class="guest-text-gradient">Sistem Informasi</span>
              <span class="guest-text-gradient-gold"> Terintegrasi</span>
            </h2>
            <p class="mx-auto mt-4 max-w-lg text-sm font-medium text-krem-300/80 sm:text-base">
              Lima layanan digital yang saling terhubung untuk mendukung operasional ambalan secara efisien.
            </p>
          </ScrollReveal>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <ScrollReveal v-for="(feature, idx) in features" :key="idx" :delay="idx * 100" :stagger="true" :style="`--stagger-index: ${idx}`" animation="fade-up">
            <div class="guest-feature-card">
              <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-daun-400/5 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
              <div class="relative flex items-start gap-5">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-hutan-800 to-hutan-600 border-2 border-daun-500/30 text-emas-400 shadow-lg shadow-daun-500/10 transition-all duration-300 group-hover:rotate-[5deg] group-hover:border-emas-400/50 group-hover:shadow-xl group-hover:shadow-daun-500/20">
                  <AppIcon :name="feature.iconName" class="h-7 w-7 transition-transform duration-300 group-hover:scale-110" />
                </div>
                <div>
                  <h3 class="text-xl font-extrabold text-krem-100 transition-colors duration-300 group-hover:text-emas-400">{{ feature.title }}</h3>
                  <p class="mt-2 text-sm leading-relaxed font-medium text-krem-300/80">{{ feature.description }}</p>
                </div>
              </div>
              <div class="absolute bottom-0 right-0 h-24 w-24 rounded-full bg-daun-400/5 blur-2xl transition-all duration-500 group-hover:bg-daun-400/10 group-hover:scale-150"></div>
            </div>
          </ScrollReveal>
        </div>
      </section>

      <section id="testimoni" class="guest-section scroll-mt-24">
        <div class="mb-12 text-center">
          <ScrollReveal animation="fade-up">
            <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
              <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse"></span>
              Testimoni
            </div>
          </ScrollReveal>
          <ScrollReveal animation="fade-up" :delay="100">
            <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl lg:text-5xl">
              <span class="guest-text-gradient">Apa Kata</span>
              <span class="guest-text-gradient-gold"> Mereka</span>
            </h2>
          </ScrollReveal>
        </div>
        <ScrollReveal animation="fade-up" :delay="150">
          <TestimonialsCarousel :testimonials="testimonials" :auto-play="true" :interval="5000" />
        </ScrollReveal>
      </section>

      <section id="pengumatan" class="guest-section scroll-mt-24">
        <div class="mb-12 text-center">
          <ScrollReveal animation="fade-up">
            <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
              <span class="h-1.5 w-1.5 rounded-full bg-emas-400 animate-pulse"></span>
              Aktivitas Terbaru
            </div>
          </ScrollReveal>
          <ScrollReveal animation="fade-up" :delay="100">
            <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl">
              <span class="guest-text-gradient">Pengumuman</span>
              <span class="guest-text-gradient-gold"> Terbaru</span>
            </h2>
            <p class="mx-auto mt-4 max-w-lg text-sm font-medium text-krem-300/80">
              Ikuti perkembangan terbaru dari ambalan.
            </p>
          </ScrollReveal>
        </div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <ScrollReveal v-for="(item, idx) in guestStore.announcements.slice(0, 6)" :key="item.id" :delay="idx * 80" :stagger="true" :style="`--stagger-index: ${idx}`" animation="fade-up">
            <article
              class="guest-announcement-card"
            >
              <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-daun-400/5 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
              <div class="relative mb-4 flex items-center justify-between">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-daun-400/15 px-3 py-1 text-xs font-bold text-emas-400">
                  <AppIcon :name="categoryIcon(item.kategori)" class="h-3.5 w-3.5" />
                  {{ item.kategori || 'Pengumuman' }}
                </span>
                <time class="text-xs font-bold text-lumut-400" :datetime="item.published_at">
                  {{ formatDate(item.published_at) }}
                </time>
              </div>
              <div class="relative flex-1">
                <h3 class="text-lg font-extrabold text-krem-100 transition-colors duration-300 group-hover:text-emas-400">{{ item.judul }}</h3>
                <p class="mt-2 text-sm leading-relaxed font-medium text-krem-300/70 line-clamp-3">{{ item.isi }}</p>
                <span class="mt-4 inline-flex items-center gap-1 text-xs font-bold text-daun-400 transition-all duration-300 group-hover:text-emas-400 group-hover:translate-x-1">
                  Baca selengkapnya
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                  </svg>
                </span>
              </div>
            </article>
          </ScrollReveal>
          <div v-if="!guestStore.announcements.length" class="empty-state md:col-span-2 lg:col-span-3">
            <div class="empty-state-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
              </svg>
            </div>
            <p class="empty-state-text">Belum ada pengumuman.</p>
          </div>
        </div>
      </section>

      <section id="trust" class="guest-section scroll-mt-24">
        <div class="mb-10 text-center">
          <ScrollReveal animation="fade-up">
            <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
              <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse"></span>
              Didukung Oleh
            </div>
          </ScrollReveal>
        </div>
        <ScrollReveal animation="fade-up" :delay="100">
          <div class="flex flex-wrap items-center justify-center gap-8 md:gap-12">
            <div v-for="(partner, idx) in partners" :key="idx" class="group flex flex-col items-center gap-2 opacity-80 transition hover:opacity-100">
              <div class="flex h-14 w-14 items-center justify-center rounded-2xl border-2 border-daun-500/40 bg-hutan-700 text-emas-400 transition-all duration-300 group-hover:border-emas-400/60 group-hover:bg-hutan-600 group-hover:shadow-lg group-hover:shadow-emas-400/10">
                <AppIcon :name="partner.icon" class="h-7 w-7 transition-transform duration-300 group-hover:scale-110" />
              </div>
              <span class="text-xs font-bold text-krem-300">{{ partner.name }}</span>
            </div>
          </div>
        </ScrollReveal>
      </section>

      <section class="guest-section">
        <ScrollReveal animation="zoom-in">
          <div class="relative isolate overflow-hidden rounded-3xl border-2 border-emas-400/40 bg-gradient-to-r from-daun-400 via-daun-500 to-hutan-800 p-10 text-center shadow-2xl sm:p-16">
            <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-emas-400/20 blur-3xl"></div>
            <div class="absolute -bottom-24 -right-24 h-80 w-80 rounded-full bg-daun-400/20 blur-3xl"></div>
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
                  class="guest-focus-ring rounded-2xl bg-hutan-800 px-9 py-4 text-sm font-black text-emas-400 shadow-xl shadow-black/30 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-black/40 active:scale-95"
                >
                  Bergabung Sekarang
                </Link>
                <Link
                  href="/login"
                  class="guest-focus-ring rounded-2xl border-2 border-hutan-800 bg-white/10 px-9 py-4 text-sm font-black text-hutan-800 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-transparent hover:bg-white/20 active:scale-95"
                >
                  Masuk Akun
                </Link>
              </div>
            </div>
          </div>
        </ScrollReveal>
      </section>

      <section id="newsletter" class="guest-section scroll-mt-24">
        <ScrollReveal animation="fade-up">
          <div class="mx-auto max-w-2xl rounded-3xl border-2 border-daun-500/30 bg-hutan-600/60 p-8 text-center shadow-lg backdrop-blur-sm sm:p-10">
            <h2 class="text-2xl font-black text-krem-100 sm:text-3xl">Dapatkan Kabar Terbaru</h2>
            <p class="mt-2 text-sm font-medium text-krem-300/80">
              Berlangganan buletin kami untuk kabar kegiatan, pengumuman, dan update sistem.
            </p>
            <form @submit.prevent="subscribeNewsletter" class="mt-6 flex flex-col gap-3 sm:flex-row">
              <input
                v-model="newsletterEmail"
                type="email"
                required
                placeholder="email@contoh.com"
                class="guest-focus-ring flex-1 rounded-xl border-2 border-daun-500/40 bg-hutan-800 px-4 py-3 text-sm font-semibold text-krem-100 outline-none transition focus:border-emas-400 focus:shadow-lg focus:shadow-emas-400/10"
              />
              <button type="submit" class="btn-primary rounded-xl px-6 py-3 text-sm font-black">
                Berlangganan
              </button>
            </form>
            <p v-if="newsletterSubscribed" class="mt-3 text-xs font-bold text-daun-400">Terima kasih, Anda berhasil berlangganan.</p>
          </div>
        </ScrollReveal>
      </section>

      <footer class="guest-section border-t border-daun-500/20">
        <div class="flex flex-col items-center justify-center gap-5">
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl overflow-hidden border-2 border-daun-500/40 bg-hutan-800 text-emas-400">
              <img :src="logoUrl" alt="Logo Ambalan" class="h-8 w-8 object-contain" />
            </div>
            <span class="text-base font-bold text-krem-300">{{ ambalanNama }}</span>
          </div>
          <nav class="flex flex-wrap items-center justify-center gap-4 text-xs font-medium text-lumut-400">
            <Link href="/privacy" class="transition hover:text-emas-400">Kebijakan Privasi</Link>
            <span class="h-3 w-px bg-daun-500/30"></span>
            <a href="mailto:admin@sman2maros.sch.id" class="transition hover:text-emas-400">Hubungi Admin</a>
          </nav>
          <p class="text-xs font-medium text-lumut-400">
            &copy; {{ currentYear }} Ekosistem Digital Kepramukaan Ambalan UPT SMAN 2 Maros &middot; Dibangun dengan kebanggaan.
          </p>
        </div>
      </footer>
    </main>

    <nav v-if="isMobile" class="guest-bottom-nav" aria-label="Navigasi bawah">
      <a href="#fitur" class="guest-bottom-nav-item" @click.prevent="scrollToSection('fitur')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
        </svg>
        <span>Fitur</span>
      </a>
      <a href="#dokumentasi" class="guest-bottom-nav-item" @click.prevent="scrollToSection('dokumentasi')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.25-5.25a2.25 2.25 0 013 0l3.75 3.75M9.75 12.75l.75.75m0 0l.75.75m-.75-.75v-6.75m-.75 6.75h6" />
        </svg>
        <span>Galeri</span>
      </a>
      <a href="#testimoni" class="guest-bottom-nav-item" @click.prevent="scrollToSection('testimoni')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 011.037-.443 48.282 48.282 0 005.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.012z" />
        </svg>
        <span>Testimoni</span>
      </a>
      <a href="#pengumatan" class="guest-bottom-nav-item" @click.prevent="scrollToSection('pengumatan')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        <span>Pengumuman</span>
      </a>
      <Link href="/login" class="guest-bottom-nav-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
        </svg>
        <span>Masuk</span>
      </Link>
    </nav>

    <LightboxModal
      v-model:open="lightboxOpen"
      :items="guestStore.gallery"
      :current-index="lightboxIndex"
      @close="lightboxOpen = false"
      @navigate="onLightboxNavigate"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import PhotoSlider from '@/Components/PhotoSlider.vue';
import AppIcon from '@/Components/AppIcon.vue';
import ParticleBackground from '@/Components/ParticleBackground.vue';
import ScrollReveal from '@/Components/ScrollReveal.vue';
import TestimonialsCarousel from '@/Components/TestimonialsCarousel.vue';
import LightboxModal from '@/Components/LightboxModal.vue';
import { useGuestStore } from '@/Stores/guest';
import { useUIStore } from '@/Stores/ui';

const guestStore = useGuestStore();
const uiStore = useUIStore();

const props = defineProps({
  announcements: Array,
  stats: { type: Object, default: () => ({ members: 0, alumni: 0 }) },
  sliderSlides: Array,
  gallery: { type: Array, default: () => [] },
});

const isMobile = ref(false);
const mobileMenuOpen = computed({
  get: () => uiStore.mobileMenuOpen,
  set: (v) => uiStore.setMobileMenuOpen(v),
});
const container = ref(null);
const galleryContainer = ref(null);
const galleryScrollLeft = ref(0);
const galleryScrollRight = ref(0);
const newsletterEmail = ref('');
const newsletterSubscribed = ref(false);
const animatedStats = ref({ members: 0, alumni: 0, services: 0, access: 0 });
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);
let animationFrameId = null;
let scrollHandler = null;
let revealObserver = null;
let lastFocusedElement = null;
let resizeObserver = null;

const ambalanNama = computed(() => {
  try {
    return $page?.props?.ambalan?.nama || 'Ambalan UPT SMAN 2 Maros';
  } catch (e) {
    return 'Ambalan UPT SMAN 2 Maros';
  }
});
const logoUrl = computed(() => {
  try {
    return $page?.props?.ambalan?.logo_url || '/images/Logo_Ambalan.png';
  } catch (e) {
    return '/images/Logo_Ambalan.png';
  }
});

const howItWorks = [
  { emoji: '📝', title: 'Daftar', description: 'Buat akun dengan email atau NISN. Proses pendaftaran hanya membutuhkan waktu 2 menit.' },
  { emoji: '✅', title: 'Verifikasi', description: 'Aktivasi akun melalui tautan yang dikirim ke email. Pembina melakukan validasi data.' },
  { emoji: '🚀', title: 'Akses Fitur', description: 'Setelah disetujui, Anda bisa menggunakan SKU, presensi, kas, inventaris, dan dashboard.' },
];

const features = [
  { iconName: 'sku', title: 'SKU Anggota', description: 'Pengelolaan Surat Keputusan Anggota secara digital, dari penerbitan hingga arsip, lengkap dengan status dan riwayat.' },
  { iconName: 'attendance', title: 'Presensi', description: 'Sistem kehadiran modern dengan pencatatan digital. Pantau kehadiran anggota secara real-time dari mana saja.' },
  { iconName: 'wallet', title: 'Kas Ambalan', description: 'Pengelolaan keuangan ambalan dengan pencatatan pemasukan dan pengeluaran yang transparan dan terstruktur.' },
  { iconName: 'inventory', title: 'Inventaris', description: 'Daftar barang ambalan lengkap dengan kondisi, lokasi, dan riwayat penggunaan dalam satu sistem terpusat.' },
  { iconName: 'dashboard', title: 'Dashboard', description: 'Ringkasan visual aktivitas ambalan dalam satu pandangan. Pantau tren, capaian, dan perkembangan secara cepat.' },
  { iconName: 'profile', title: 'Profil Ambalan', description: 'Informasi lengkap tentang sejarah, visi, misi, dan struktur pengurus ambalan dalam tampilan yang profesional.' },
];

const testimonials = [
  { quote: 'Sistem ini membuat administrasi ambalan jauh lebih mudah. Semua data tersedia dalam satu platform.', name: 'Pembina A', role: 'Pembina Ambalan SMA 2 Maros', rating: 5 },
  { quote: 'Presensi digital dan SKU elektronik sangat membantu. Tidak perlu lagi formulir kertas yang mudah hilang.', name: 'Ketua Ambalan', role: 'Pengurus Ambalan', rating: 5 },
  { quote: 'Sebagai alumni, saya tetap bisa mengikuti perkembangan dan berkontribusi melalui fitur donasi.', name: 'Alumni B', role: 'Anggota Alumni', rating: 5 },
];

const partners = [
  { name: 'Kwartir Cabang Maros', icon: 'teams' },
  { name: 'SMA Negeri 2 Maros', icon: 'dashboard' },
  { name: 'Kwarcab', icon: 'members' },
];

const statsItems = computed(() => [
  { label: 'Anggota Aktif', key: 'members', value: props.stats.members, icon: 'members' },
  { label: 'Alumni Tercatat', key: 'alumni', value: props.stats.alumni, icon: 'alumni' },
  { label: 'Layanan Digital', key: 'services', value: '5+', icon: 'tools' },
  { label: 'Akses Ponsel', key: 'access', value: 'PWA', icon: 'download' },
]);

const logoFallback = '/images/Logo_Ambalan.png';
const currentYear = computed(() => new Date().getFullYear());

function categoryIcon(kategori) {
  const map = {
    'Kegiatan': 'events',
    'Latihan': 'assessments',
    'Outbound': 'map',
    'Jambore': 'teams',
    'Peringatan': 'announcements',
    'Umum': 'announcements',
  };
  return map[kategori] || 'announcements';
}

function easeOutExpo(t) {
  return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
}

function animateCounter(target, key, duration = 1800) {
  if (typeof target !== 'number') {
    animatedStats.value[key] = target;
    return;
  }

  const startTime = performance.now();
  const numTarget = target;
  const step = (timestamp) => {
    const elapsed = timestamp - startTime;
    const progress = Math.min(elapsed / duration, 1);
    animatedStats.value[key] = Math.floor(numTarget * easeOutExpo(progress));
    if (progress < 1) animationFrameId = requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}

function updateGalleryScrollState() {
  if (!galleryContainer.value) return;
  const { scrollLeft, scrollWidth, clientWidth } = galleryContainer.value;
  galleryScrollLeft.value = scrollLeft;
  galleryScrollRight.value = Math.max(0, scrollWidth - clientWidth - scrollLeft);
}

function scrollGallery(direction) {
  if (!galleryContainer.value) return;
  const { scrollWidth, clientWidth } = galleryContainer.value;
  const scrollAmount = Math.min(300, clientWidth * 0.8);
  galleryContainer.value.scrollBy({
    left: direction === 'right' ? scrollAmount : -scrollAmount,
    behavior: 'smooth',
  });
  setTimeout(updateGalleryScrollState, 400);
}

function formatDate(value) {
  return value
    ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
    : '-';
}

function openMobileMenu() {
  uiStore.setMobileMenuOpen(true);
  lastFocusedElement = document.activeElement;
  document.body.style.overflow = 'hidden';
  nextTick(() => {
    const closeBtn = document.getElementById('mobile-menu-close');
    if (closeBtn) closeBtn.focus();
  });
}

function closeMobileMenu() {
  uiStore.setMobileMenuOpen(false);
  document.body.style.overflow = '';
  if (lastFocusedElement) lastFocusedElement.focus();
}

function trapFocus(event) {
  if (!uiStore.mobileMenuOpen) return;
  const overlay = document.getElementById('mobile-menu-overlay');
  if (!overlay) return;
  const focusable = overlay.querySelectorAll('a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])');
  if (focusable.length === 0) return;
  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  if (event.key === 'Tab') {
    if (event.shiftKey) {
      if (document.activeElement === first) { event.preventDefault(); last.focus(); }
    } else {
      if (document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
  }
  if (event.key === 'Escape') closeMobileMenu();
}

function subscribeNewsletter() {
  newsletterSubscribed.value = true;
  newsletterEmail.value = '';
  setTimeout(() => { newsletterSubscribed.value = false; }, 4000);
}

function openLightbox(idx) {
  lightboxIndex.value = idx;
  lightboxOpen.value = true;
}

function onLightboxNavigate(direction) {
  if (direction === 'prev') {
    lightboxIndex.value = (lightboxIndex.value - 1 + guestStore.gallery.length) % guestStore.gallery.length;
  } else if (direction === 'next') {
    lightboxIndex.value = (lightboxIndex.value + 1) % guestStore.gallery.length;
  }
}

function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function handleResize() {
  isMobile.value = window.innerWidth < 1024;
}

function setMetaTags() {
  const url = window.location.origin + '/';
  const title = 'AMBARA — Sistem Digital Ambalan SMA 2 Maros';
  const description = 'Ekosistem digital kepramukaan: SKU, presensi, kas, inventaris, dan dashboard dalam satu platform untuk anggota SMA 2 Maros.';

  document.title = title;

  const setMeta = (attr, content) => {
    let tag = document.querySelector(`meta[${attr}="${content}"]`);
    if (!tag) {
      tag = document.createElement('meta');
      tag.setAttribute(attr, content);
      document.head.appendChild(tag);
    }
    tag.setAttribute('content', description);
  };

  setMeta('name', 'description');

  let ogTitle = document.querySelector('meta[property="og:title"]');
  if (!ogTitle) { ogTitle = document.createElement('meta'); ogTitle.setAttribute('property', 'og:title'); document.head.appendChild(ogTitle); }
  ogTitle.setAttribute('content', title);

  let ogDesc = document.querySelector('meta[property="og:description"]');
  if (!ogDesc) { ogDesc = document.createElement('meta'); ogDesc.setAttribute('property', 'og:description'); document.head.appendChild(ogDesc); }
  ogDesc.setAttribute('content', description);

  let ogImage = document.querySelector('meta[property="og:image"]');
  if (!ogImage) { ogImage = document.createElement('meta'); ogImage.setAttribute('property', 'og:image'); document.head.appendChild(ogImage); }
  ogImage.setAttribute('content', url + 'images/Logo_Ambalan.png');

  let ogUrl = document.querySelector('meta[property="og:url"]');
  if (!ogUrl) { ogUrl = document.createElement('meta'); ogUrl.setAttribute('property', 'og:url'); document.head.appendChild(ogUrl); }
  ogUrl.setAttribute('content', url);

  let twCard = document.querySelector('meta[name="twitter:card"]');
  if (!twCard) { twCard = document.createElement('meta'); twCard.setAttribute('name', 'twitter:card'); document.head.appendChild(twCard); }
  twCard.setAttribute('content', 'summary_large_image');

  let twTitle = document.querySelector('meta[name="twitter:title"]');
  if (!twTitle) { twTitle = document.createElement('meta'); twTitle.setAttribute('name', 'twitter:title'); document.head.appendChild(twTitle); }
  twTitle.setAttribute('content', title);

  let twDesc = document.querySelector('meta[name="twitter:description"]');
  if (!twDesc) { twDesc = document.createElement('meta'); twDesc.setAttribute('name', 'twitter:description'); document.head.appendChild(twDesc); }
  twDesc.setAttribute('content', description);

  let canonical = document.querySelector('link[rel="canonical"]');
  if (!canonical) { canonical = document.createElement('link'); canonical.setAttribute('rel', 'canonical'); document.head.appendChild(canonical); }
  canonical.setAttribute('href', url);

  let jsonLd = document.getElementById('guest-jsonld');
  if (!jsonLd) {
    jsonLd = document.createElement('script');
    jsonLd.id = 'guest-jsonld';
    jsonLd.type = 'application/ld+json';
    document.head.appendChild(jsonLd);
  }
  jsonLd.textContent = JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: 'Ambalan UPT SMAN 2 Maros',
    url: url,
    logo: url + 'images/Logo_Ambalan.png',
    description: description,
    address: { '@type': 'PostalAddress', addressLocality: 'Maros', addressRegion: 'Sulawesi Selatan', addressCountry: 'ID' },
  });
}

onMounted(() => {
  setMetaTags();
  handleResize();
  window.addEventListener('resize', handleResize);

  if (props.announcements?.length) {
    guestStore.$patch({ announcements: props.announcements });
  }
  if (props.gallery?.length) {
    guestStore.$patch({ gallery: props.gallery });
  }
  if (props.sliderSlides?.length) {
    guestStore.$patch({ sliderSlides: props.sliderSlides });
  }

  guestStore.fetchGuestData();

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          if (entry.target.dataset.animate === 'stats') {
            animateCounter(props.stats.members, 'members');
            animateCounter(props.stats.alumni, 'alumni');
            animateCounter('5+', 'services');
            animateCounter('PWA', 'access');
          }
          revealObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.1 },
  );

  container.value?.querySelectorAll('[data-animate]').forEach((el) => revealObserver.observe(el));

  scrollHandler = () => {
    if (!container.value || prefersReducedMotion) return;
    const scrollY = window.scrollY;
    const bg = container.value.querySelector('[data-parallax-bg]');
    if (bg) bg.style.transform = `translateY(${scrollY * 0.3}px)`;
    const hero = container.value.querySelector('[data-parallax-hero]');
    if (hero) hero.style.transform = `translateY(${scrollY * 0.1}px)`;
  };
  window.addEventListener('scroll', scrollHandler, { passive: true });

  requestAnimationFrame(() => {
    scrollHandler();
  });

  galleryContainer.value?.addEventListener('scroll', updateGalleryScrollState, { passive: true });
  updateGalleryScrollState();

  window.addEventListener('keydown', trapFocus);
});

onUnmounted(() => {
  window.removeEventListener('scroll', scrollHandler);
  window.removeEventListener('resize', handleResize);
  if (animationFrameId) cancelAnimationFrame(animationFrameId);
  revealObserver?.disconnect();
  window.removeEventListener('keydown', trapFocus);
  document.body.style.overflow = '';
});
</script>

<style scoped>
.skip-link {
  position: absolute;
  top: -40px;
  left: 0;
  z-index: 100;
  padding: 8px 16px;
  background: var(--color-emas-400);
  color: var(--color-hutan-800);
  font-weight: 900;
  text-decoration: none;
  border-radius: 0 0 0.5rem 0;
}
.skip-link:focus {
  top: 0;
}

.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
