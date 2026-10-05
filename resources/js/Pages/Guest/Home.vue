<template>
  <div ref="container" class="relative min-h-screen overflow-hidden bg-hutan-800 font-sans">
    <div class="pointer-events-none absolute inset-0">
      <div class="absolute -top-40 -left-40 h-[500px] w-[500px] rounded-full bg-gradient-to-br from-daun-400/15 via-daun-400/5 to-transparent blur-[80px] will-change-transform" data-parallax-bg></div>
      <div class="absolute -right-40 top-20 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-emas-400/15 via-emas-400/5 to-transparent blur-[80px] will-change-transform"></div>
      <div class="absolute -bottom-40 left-1/3 h-[350px] w-[350px] rounded-full bg-gradient-to-br from-daun-500/20 to-transparent blur-[80px] will-change-transform"></div>
    </div>

    <div class="absolute inset-0 pointer-events-none">
      <svg ref="particlesSvg" class="h-full w-full" preserveAspectRatio="none" aria-hidden="true">
        <circle
          v-for="p in particles"
          :key="p.id"
          :cx="p.x"
          :cy="p.y"
          :r="p.size"
          :fill="p.color"
          opacity="0.6"
        >
          <animate
            attributeName="cy"
            :from="p.y"
            :to="p.y + 20"
            dur="3s"
            repeatCount="indefinite"
          />
        </circle>
      </svg>
    </div>

    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>

    <nav class="sticky top-0 z-30 mx-auto w-full max-w-[1400px] px-4 py-3 sm:px-6 lg:px-[50px]">
      <div class="glass-panel inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-krem-300">
        <span class="relative flex h-2 w-2">
          <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-daun-400 opacity-75"></span>
          <span class="relative inline-flex h-2 w-2 rounded-full bg-daun-400"></span>
        </span>
        <span>Sistem Online</span>
      </div>

      <div class="glass-panel float-right hidden items-center gap-4 px-4 py-2 text-sm font-medium md:flex">
        <a href="#fitur" class="text-krem-300 transition-colors hover:text-emas-400">Fitur</a>
        <span class="h-3 w-px bg-daun-500/30"></span>
        <a href="#dokumentasi" class="text-krem-300 transition-colors hover:text-emas-400">Dokumentasi</a>
        <span class="h-3 w-px bg-daun-500/30"></span>
        <a href="#pengumatan" class="text-krem-300 transition-colors hover:text-emas-400">Pengumuman</a>
        <span class="h-3 w-px bg-daun-500/30"></span>
        <Link href="/login" class="text-krem-300 transition-colors hover:text-emas-400">Masuk</Link>
      </div>

      <button
        id="mobile-menu-button"
        class="glass-panel float-right flex md:hidden h-10 w-10 items-center justify-center rounded-full p-0 text-krem-300"
        @click="openMobileMenu"
        :aria-expanded="mobileMenuOpen"
        aria-controls="mobile-menu-overlay"
        aria-label="Buka menu"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
      </button>
    </nav>

    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="mobileMenuOpen"
          id="mobile-menu-overlay"
          class="fixed inset-0 z-50 flex items-center justify-center bg-hutan-900/80 backdrop-blur-md"
          @click.self="closeMobileMenu"
          role="dialog"
          aria-modal="true"
          aria-label="Menu navigasi"
        >
          <div class="mx-4 w-full max-w-sm rounded-3xl border-2 border-daun-500/40 bg-hutan-800 p-6 shadow-2xl">
            <div class="mb-6 flex items-center justify-between">
              <span class="text-lg font-black text-krem-100">Menu</span>
              <button
                id="mobile-menu-close"
                @click="closeMobileMenu"
                class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-daun-500 text-krem-300 transition hover:text-emas-400"
                aria-label="Tutup menu"
              >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="flex flex-col gap-3">
              <a href="#fitur" @click="closeMobileMenu" class="rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Fitur</a>
              <a href="#dokumentasi" @click="closeMobileMenu" class="rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Dokumentasi</a>
              <a href="#pengumatan" @click="closeMobileMenu" class="rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Pengumuman</a>
              <Link href="/login" @click="closeMobileMenu" class="rounded-2xl border-2 border-daun-500 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400">Masuk</Link>
              <Link href="/register" @click="closeMobileMenu" class="rounded-2xl bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 px-4 py-3 text-center font-black text-hutan-800 shadow-lg transition hover:brightness-110">Bergabung Sekarang</Link>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <main id="main-content" class="relative mx-auto w-full px-4 py-10 sm:px-6 sm:py-14 lg:px-[50px] lg:py-20">
      <header class="mb-12 text-center" data-parallax-hero>
        <section v-if="combinedSlides.length > 0" class="mb-10">
          <PhotoSlider :slides="combinedSlides" :interval="6000" />
        </section>

        <div class="mb-12 flex justify-center">
          <div class="group relative flex h-32 w-32 items-center justify-center rounded-3xl border-2 border-daun-500/50 bg-hutan-800/80 shadow-2xl shadow-daun-400/20 transition-transform duration-500 hover:rotate-[5deg] hover:scale-105">
            <img
              v-if="$page.props.ambalan?.logo_url"
              :src="$page.props.ambalan.logo_url"
              alt="Logo Ambalan UPT SMAN 2 Maros"
              class="relative h-full w-full object-contain"
            />
            <img
              v-else
              :src="logoFallback"
              alt="Logo Ambalan UPT SMAN 2 Maros"
              class="relative h-full w-full object-contain"
            />
            <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 opacity-0 blur transition-opacity duration-500 group-hover:opacity-60"></div>
          </div>
        </div>

        <h1 class="text-4xl font-black text-transparent sm:text-5xl lg:text-6xl xl:text-7xl mb-3">
          <span class="bg-gradient-to-r from-krem-100 to-krem-300 bg-clip-text text-transparent">Satya dan Darma</span>
          <span class="relative inline-block bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 bg-clip-text text-transparent"> dalam satu genggaman.</span>
        </h1>

        <p class="mx-auto mt-4 max-w-lg text-lg leading-relaxed font-medium text-krem-300/80">
          Ekosistem digital untuk anggota, pembina, pengurus, dan alumni.
          Pantau SKU, presensi, kas, inventaris, dan kabar ambalan dengan lebih tertib.
        </p>

        <div class="mt-8 flex flex-wrap justify-center gap-4">
          <Link
            href="/register"
            class="group relative isolate overflow-hidden rounded-2xl bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 px-8 py-4 text-sm font-black text-hutan-800 shadow-xl shadow-emas-400/30 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-emas-400/40 active:scale-95 before:absolute before:inset-0 before:bg-white before:opacity-0 before:transition-opacity before:duration-300 group-hover:before:opacity-20"
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
            class="rounded-2xl border-2 border-daun-500 bg-hutan-800/40 px-8 py-4 text-sm font-bold text-krem-300 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-emas-400 hover:bg-daun-500/20 hover:text-emas-400 hover:shadow-lg hover:shadow-daun-500/20 active:scale-95"
          >
            Masuk
          </Link>
        </div>
      </header>

      <section class="mt-12" data-animate="stats">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <StatCard label="Anggota Aktif" :value="animatedMembers" icon="members" />
          <StatCard label="Alumni Tercatat" :value="animatedAlumni" icon="alumni" />
          <StatCard label="Layanan Digital" value="5+" icon="tools" />
          <StatCard label="Akses Ponsel" value="PWA" icon="download" />
        </div>
      </section>

      <section class="mt-20" id="cara-kerja">
        <div class="mb-12 text-center">
          <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
            <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse"></span>
            Cara Kerja
          </div>
          <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl lg:text-5xl">
            <span class="bg-gradient-to-r from-krem-100 to-krem-300 bg-clip-text text-transparent">Mulai dalam</span>
            <span class="bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 bg-clip-text text-transparent"> 3 Langkah</span>
          </h2>
        </div>
        <div class="grid gap-6 sm:grid-cols-3">
          <div v-for="(step, idx) in howItWorks" :key="idx" class="relative rounded-2xl border-2 border-daun-500/30 bg-hutan-600/60 p-6 text-center shadow-lg transition hover:-translate-y-1 hover:border-daun-400/60">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full border-2 border-daun-400 bg-hutan-800 text-lg font-black text-emas-400">
              {{ idx + 1 }}
            </div>
            <h3 class="text-lg font-extrabold text-krem-100">{{ step.title }}</h3>
            <p class="mt-2 text-sm leading-relaxed font-medium text-krem-300/80">{{ step.description }}</p>
          </div>
        </div>
      </section>

      <section v-if="gallery && gallery.length > 0" id="dokumentasi" class="mt-20 scroll-mt-24">
        <div class="mb-6 flex items-end justify-between">
          <div>
            <span class="section-eyebrow text-daun-400">Dokumentasi Kegiatan</span>
            <h2 class="text-2xl font-black text-transparent sm:text-3xl">
              <span class="bg-gradient-to-r from-krem-100 to-krem-300 bg-clip-text text-transparent">Dokumentasi</span>
              <span class="bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 bg-clip-text text-transparent"> Kegiatan</span>
            </h2>
            <p class="mt-1 text-sm font-medium text-lumut-400">
              Foto-foto dokumentasi dari kegiatan kepramukaan terbaru.
            </p>
          </div>
          <Link href="/galleries" class="text-sm font-bold text-daun-400 opacity-70 transition-opacity hover:opacity-100 hover:text-emas-400">
            Lihat semua &rarr;
          </Link>
        </div>
        <div class="relative">
          <div
            ref="galleryContainer"
            class="hide-scrollbar flex gap-3 overflow-x-auto pb-2 scroll-snap-type-x mandatory"
          >
            <div
              v-for="(item, idx) in gallery"
              :key="idx"
              class="group relative flex-shrink-0 overflow-hidden rounded-2xl border-2 border-daun-400/20 bg-hutan-600/60 shadow-lg transition-all duration-300 first:ml-0 hover:-translate-y-1 hover:border-daun-400/40 hover:shadow-2xl hover:shadow-daun-400/10 scroll-snap-align-start"
              style="width: 280px;"
            >
              <div class="relative h-48 overflow-hidden">
                <img
                  v-if="item.src"
                  :src="item.src"
                  :alt="item.title"
                  class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                  loading="lazy"
                />
                <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-hutan-800 to-hutan-600">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-lumut-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.25-5.25a2.25 2.25 0 013 0l3.75 3.75M9.75 12.75l.75.75m0 0l.75.75m-.75-.75v-6.75m-.75 6.75h6" />
                  </svg>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-hutan-800/95 via-hutan-800/30 to-transparent"></div>
              </div>
              <div class="p-4">
                <span class="mb-1 inline-block rounded-full bg-daun-400/15 px-2.5 py-0.5 text-xs font-bold text-emas-400">{{ item.kategori || 'Dokumentasi' }}</span>
                <h3 class="text-sm font-extrabold text-krem-100 transition-colors group-hover:text-emas-400">{{ item.title || 'Tanpa judul' }}</h3>
                <p v-if="item.description" class="mt-1 line-clamp-2 text-xs leading-relaxed font-medium text-krem-300/70">{{ item.description }}</p>
              </div>
            </div>
          </div>

          <button
            v-if="gallery.length > 1"
            @click="scrollGallery('left')"
            :disabled="galleryScrollLeft <= 0"
            class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-hutan-800/80 p-2.5 text-krem-100 shadow-lg transition hover:bg-daun-500/40 hover:text-emas-400 active:scale-95 disabled:opacity-30 disabled:pointer-events-none"
            aria-label="Gulir kiri"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
          </button>
          <button
            v-if="gallery.length > 1"
            @click="scrollGallery('right')"
            :disabled="galleryScrollRight <= 0"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-hutan-800/80 p-2.5 text-krem-100 shadow-lg transition hover:bg-daun-500/40 hover:text-emas-400 active:scale-95 disabled:opacity-30 disabled:pointer-events-none"
            aria-label="Gulir kanan"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
          </button>
        </div>
      </section>

      <section id="fitur" class="mt-20 scroll-mt-24">
        <div class="mb-12 text-center">
          <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
            <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse"></span>
            Layanan Unggulan
          </div>
          <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl lg:text-5xl">
            <span class="bg-gradient-to-r from-krem-100 to-krem-300 bg-clip-text text-transparent">Sistem Informasi</span>
            <span class="bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 bg-clip-text text-transparent"> Terintegrasi</span>
          </h2>
          <p class="mx-auto mt-4 max-w-lg text-sm font-medium text-krem-300/80">
            Lima layanan digital yang saling terhubung untuk mendukung operasional ambalan secara efisien.
          </p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="(feature, idx) in features"
            :key="idx"
            class="group relative overflow-hidden rounded-2xl border border-daun-400/20 bg-hutan-600/60 p-6 shadow-lg transition-all duration-300 hover:-translate-y-2 hover:border-daun-400/40 hover:bg-hutan-600/80 hover:shadow-2xl hover:shadow-daun-400/10"
          >
            <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-daun-400/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>
            <div class="relative flex items-start gap-5">
              <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-hutan-800 to-hutan-600 border border-daun-500/30 text-emas-400 shadow-lg shadow-daun-500/10 transition-transform group-hover:rotate-[5deg]">
                <AppIcon :name="feature.iconName" class="h-7 w-7" />
              </div>
              <div>
                <h3 class="text-xl font-extrabold text-krem-100 transition-colors group-hover:text-emas-400">{{ feature.title }}</h3>
                <p class="mt-2 text-sm leading-relaxed font-medium text-krem-300/80">{{ feature.description }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="testimoni" class="mt-20 scroll-mt-24">
        <div class="mb-12 text-center">
          <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
            <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse"></span>
            Testimoni
          </div>
          <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl lg:text-5xl">
            <span class="bg-gradient-to-r from-krem-100 to-krem-300 bg-clip-text text-transparent">Apa Kata</span>
            <span class="bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 bg-clip-text text-transparent"> Mereka</span>
          </h2>
        </div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <div v-for="(t, idx) in testimonials" :key="idx" class="rounded-2xl border-2 border-daun-500/30 bg-hutan-600/60 p-6 shadow-lg transition hover:-translate-y-1 hover:border-daun-400/60">
            <p class="text-sm leading-relaxed font-medium text-krem-300/90">"{{ t.quote }}"</p>
            <div class="mt-4 flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-daun-400 bg-hutan-800 text-sm font-black text-emas-400">
                {{ t.initials }}
              </div>
              <div>
                <p class="text-sm font-extrabold text-krem-100">{{ t.name }}</p>
                <p class="text-xs font-medium text-lumut-400">{{ t.role }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="pengumatan" class="mt-20 scroll-mt-24">
        <div class="mb-12 text-center">
          <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
            <span class="h-1.5 w-1.5 rounded-full bg-emas-400 animate-pulse"></span>
            Aktivitas Terbaru
          </div>
          <h2 class="mt-5 text-3xl font-black text-transparent sm:text-4xl">
            <span class="bg-gradient-to-r from-krem-100 to-krem-300 bg-clip-text text-transparent">Pengumuman</span>
            <span class="bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 bg-clip-text text-transparent"> Terbaru</span>
          </h2>
          <p class="mx-auto mt-4 max-w-lg text-sm font-medium text-krem-300/80">
            Ikuti perkembangan terbaru dari ambalan.
          </p>
        </div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="(item, idx) in announcements.slice(0, 6)"
            :key="item.id"
            class="group relative flex flex-col rounded-2xl border border-daun-400/10 bg-hutan-800/50 p-6 shadow-lg backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-daun-400/30"
          >
            <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-daun-400/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>
            <div class="relative mb-4 flex items-center justify-between">
              <span class="inline-flex items-center gap-1.5 rounded-full bg-daun-400/15 px-3 py-1 text-xs font-bold text-emas-400">
                <AppIcon :name="categoryIcon(item.kategori)" class="h-3.5 w-3.5" />
                {{ item.kategori || 'Pengumuman' }}
              </span>
              <time class="text-xs font-bold text-lumut-400" :datetime="item.published_at">
                {{ formatDate(item.published_at) }}
              </time>
            </div>
            <div class="relative">
              <h3 class="text-lg font-extrabold text-krem-100 transition-colors group-hover:text-emas-400">{{ item.judul }}</h3>
              <p class="mt-2 text-sm leading-relaxed font-medium text-krem-300/70 line-clamp-3">{{ item.isi }}</p>
              <span class="mt-4 inline-flex items-center gap-1 text-xs font-bold text-daun-400 transition group-hover:text-emas-400 group-hover:translate-x-1">
                Baca selengkapnya
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
              </span>
            </div>
          </article>
          <div v-if="!announcements.length" class="empty-state md:col-span-2 lg:col-span-3">
            <div class="empty-state-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
              </svg>
            </div>
            <p class="empty-state-text">Belum ada pengumuman.</p>
          </div>
        </div>
      </section>

      <section id="trust" class="mt-20 scroll-mt-24">
        <div class="mb-10 text-center">
          <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-xs font-bold text-emas-400">
            <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse"></span>
            Didukung Oleh
          </div>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-8 md:gap-12">
          <div v-for="(partner, idx) in partners" :key="idx" class="flex flex-col items-center gap-2 opacity-80 transition hover:opacity-100">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl border-2 border-daun-500/40 bg-hutan-700 text-emas-400">
              <AppIcon :name="partner.icon" class="h-6 w-6" />
            </div>
            <span class="text-xs font-bold text-krem-300">{{ partner.name }}</span>
          </div>
        </div>
      </section>

      <section class="mt-20">
        <div class="relative isolate overflow-hidden rounded-3xl border border-emas-400/40 bg-gradient-to-r from-daun-400 via-daun-500 to-hutan-800 p-12 text-center shadow-2xl sm:p-16">
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
                class="rounded-2xl bg-hutan-800 px-9 py-4 text-sm font-black text-emas-400 shadow-xl shadow-black/30 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-black/40 active:scale-95"
              >
                Bergabung Sekarang
              </Link>
              <Link
                href="/login"
                class="rounded-2xl border-2 border-hutan-800 bg-white/10 px-9 py-4 text-sm font-black text-hutan-800 transition-all duration-300 hover:-translate-y-0.5 hover:border-transparent hover:bg-white/20 active:scale-95"
              >
                Masuk Akun
              </Link>
            </div>
          </div>
        </div>
      </section>

      <section id="newsletter" class="mt-20 scroll-mt-24">
        <div class="mx-auto max-w-2xl rounded-3xl border-2 border-daun-500/30 bg-hutan-600/60 p-8 text-center shadow-lg sm:p-10">
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
              class="flex-1 rounded-xl border-2 border-daun-500/40 bg-hutan-800 px-4 py-3 text-sm font-semibold text-krem-100 outline-none transition focus:border-emas-400"
            />
            <button type="submit" class="btn-primary rounded-xl px-6 py-3 text-sm font-black">
              Berlangganan
            </button>
          </form>
          <p v-if="newsletterSubscribed" class="mt-3 text-xs font-bold text-daun-400">Terima kasih, Anda berhasil berlangganan.</p>
        </div>
      </section>

      <footer class="mt-20 border-t border-daun-500/20 pt-10 pb-6">
        <div class="flex flex-col items-center justify-center gap-5">
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-daun-400/20 text-emas-400">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
              </svg>
            </div>
            <span class="text-base font-bold text-krem-300">{{ $page.props.ambalan?.nama || 'Ambalan UPT SMAN 2 Maros' }}</span>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import PhotoSlider from '@/Components/PhotoSlider.vue';
import StatCard from '@/Components/StatCard.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  announcements: Array,
  stats: { type: Object, default: () => ({ members: 0, alumni: 0 }) },
  sliderSlides: Array,
  gallery: { type: Array, default: () => [] },
});

const mobileMenuOpen = ref(false);
const animatedMembers = ref(0);
const animatedAlumni = ref(0);
const particles = ref([]);
const container = ref(null);
const galleryContainer = ref(null);
const galleryScrollLeft = ref(0);
const galleryScrollRight = ref(0);
const newsletterEmail = ref('');
const newsletterSubscribed = ref(false);
let animationFrameId;
let scrollHandler;
let revealObserver;
let lastFocusedElement;

const howItWorks = [
  { title: 'Daftar', description: 'Buat akun dengan email atau NISN. Proses pendaftaran hanya membutuhkan waktu 2 menit.' },
  { title: 'Verifikasi', description: 'Aktivasi akun melalui tautan yang dikirim ke email. Pembina melakukan validasi data.' },
  { title: 'Akses Fitur', description: 'Setelah disetujui, Anda bisa menggunakan SKU, presensi, kas, inventaris, dan dashboard.' },
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
  { quote: 'Sistem ini membuat administrasi ambalan jauh lebih mudah. Semua data tersedia dalam satu platform.', name: 'Pembina A', role: 'Pembina Ambalan SMA 2 Maros' },
  { quote: 'Presensi digital dan SKU elektronik sangat membantu. Tidak perlu lagi formulir kertas yang mudah hilang.', name: 'Ketua Ambalan', role: 'Pengurus Ambalan' },
  { quote: 'Sebagai alumni, saya tetap bisa mengikuti perkembangan dan berkontribusi melalui fitur donasi.', name: 'Alumni B', role: 'Anggota Alumni' },
];

const partners = [
  { name: 'Kwartir Cabang Maros', icon: 'teams' },
  { name: 'SMA Negeri 2 Maros', icon: 'dashboard' },
  { name: 'Kwarcab', icon: 'members' },
];

const fallbackSlides = [
  { src: '/images/slider/slide-1.svg', title: 'Pramuka SMAN 2 Maros', description: 'Satya dan Darma dalam satu genggaman', alt: 'Foto kegiatan pramuka di SMA Negeri 2 Maros' },
  { src: '/images/slider/slide-2.svg', title: 'Kegiatan Inti', description: 'Pengembangan bakat dan kepribadian anggota', alt: 'Kegiatan inti kepramukaan di Ambalan SMA 2 Maros' },
  { src: '/images/slider/slide-3.svg', title: 'Sistem Informasi', description: 'SKU, presensi, kas, inventaris — terintegrasi', alt: 'Tampilan sistem digital ekosistem ambalan' },
];

const logoFallback = '/images/Logo_Ambalan.png';

const combinedSlides = computed(() => {
  if (props.sliderSlides && props.sliderSlides.length > 0) {
    return props.sliderSlides.map(s => ({ ...s, alt: s.title || 'Dokumentasi kegiatan ambalan' }));
  }
  return fallbackSlides;
});

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

function generateParticles() {
  const count = 15;
  const colors = ['#A7B92B', '#EDD330', '#6F9435'];
  particles.value = Array.from({ length: count }, (_, i) => ({
    id: i,
    x: Math.random() * (window.innerWidth || 1200),
    y: Math.random() * (window.innerHeight || 800),
    size: Math.random() * 3 + 1,
    color: colors[Math.floor(Math.random() * colors.length)],
    speed: Math.random() * 0.3 + 0.1,
  }));
}

function easeOutExpo(t) {
  return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
}

function animateStat(target, ref) {
  const duration = 1800;
  const startTime = performance.now();
  const step = (timestamp) => {
    const elapsed = timestamp - startTime;
    const progress = Math.min(elapsed / duration, 1);
    ref.value = Math.floor(target * easeOutExpo(progress));
    if (progress < 1) requestAnimationFrame(step);
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
  mobileMenuOpen.value = true;
  lastFocusedElement = document.activeElement;
  document.body.style.overflow = 'hidden';
  nextTick(() => {
    const closeBtn = document.getElementById('mobile-menu-close');
    if (closeBtn) closeBtn.focus();
  });
}

function closeMobileMenu() {
  mobileMenuOpen.value = false;
  document.body.style.overflow = '';
  if (lastFocusedElement) lastFocusedElement.focus();
}

function trapFocus(event) {
  if (!mobileMenuOpen.value) return;
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
  generateParticles();
  setMetaTags();

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          if (entry.target.dataset.animate === 'stats') {
            animateStat(props.stats?.members || 0, animatedMembers);
            animateStat(props.stats?.alumni || 0, animatedAlumni);
          }
          revealObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.1 },
  );

  container.value?.querySelectorAll('[data-animate]').forEach((el) => revealObserver.observe(el));

  const animateParticles = () => {
    if (!prefersReducedMotion) {
      particles.value.forEach((p) => {
        p.y += p.speed;
        if (p.y > window.innerHeight) {
          p.y = 0;
          p.x = Math.random() * window.innerWidth;
        }
      });
    }
    animationFrameId = requestAnimationFrame(animateParticles);
  };
  animateParticles();

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

[data-animate] {
  opacity: 0;
  transform: translateY(20px);
  transition: opacity 0.6s ease-out, transform 0.6s ease-out;
}

.is-visible {
  opacity: 1;
  transform: translateY(0);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in-up {
  animation: fadeInUp 0.7s ease-out both;
}
</style>
