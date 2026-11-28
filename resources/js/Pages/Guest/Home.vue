<template>
  <div ref="container" class="relative min-h-screen bg-hutan-800 font-sans text-krem-100 section-animate overflow-x-hidden">
    <!-- Particle Background -->
    <ParticleBackground
      :particle-count="isMobile ? 40 : 80"
      :colors="['#A7B92B', '#EDD330', '#6F9435', '#f0ead8']"
      :interactive="true && !prefersReducedMotion.value"
      :connection-distance="140"
      :mouse-influence="180"
      :reduced-motion="prefersReducedMotion.value"
      class="absolute inset-0"
    />

    <!-- Decorative Background Blobs -->
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
      <div class="absolute -top-40 -left-40 h-[600px] w-[600px] max-w-[85vw] rounded-full bg-gradient-to-br from-daun-400/10 via-daun-400/5 to-transparent blur-[120px] will-change-transform" data-parallax-bg></div>
      <div class="absolute -right-40 top-20 h-[500px] w-[500px] max-w-[85vw] rounded-full bg-gradient-to-br from-emas-400/10 via-emas-400/5 to-transparent blur-[100px] will-change-transform"></div>
      <div class="absolute -bottom-40 left-1/4 h-[450px] w-[450px] max-w-[85vw] rounded-full bg-gradient-to-br from-daun-500/10 to-transparent blur-[100px] will-change-transform"></div>
      <div class="guest-noise-overlay absolute inset-0 opacity-30" aria-hidden="true"></div>
    </div>

    <!-- Skip Link -->
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>

    <!-- Offline Banner -->
    <OfflineBanner :is-online="isOnline" :can-dismiss="true" @dismiss="dismissOfflineBanner" />

    <main id="main-content" class="relative mx-auto w-full max-w-[1400px] px-4 sm:px-6 lg:px-8 pt-20 sm:pt-24 lg:pt-32 pb-24 sm:pb-28 lg:pb-32">
      <!-- ========== HERO SECTION ========== -->
      <section id="hero" class="relative pt-12 sm:pt-20 pb-16 sm:pb-24 overflow-hidden scroll-mt-20" data-parallax-hero>
        <div class="mx-auto max-w-3xl text-center z-10 relative">
          <ScrollReveal animation="fade-up" :delay="50">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-daun-500/40 bg-hutan-800/60 backdrop-blur-sm mb-4 sm:mb-6">
              <AppIcon name="sparkles" class="h-4 w-4 text-emas-400" />
              <span class="text-xs sm:text-sm font-black uppercase tracking-wider text-daun-400">Sistem Digital Kepramukaan</span>
            </div>
          </ScrollReveal>

          <ScrollReveal animation="fade-up" :delay="150">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-transparent mb-6 sm:mb-8 leading-[1.1]" aria-level="1">
              <span class="guest-text-gradient block">Satya dan Darma</span>
              <span class="relative inline-block guest-text-gradient-gold">dalam satu genggaman.</span>
            </h1>
          </ScrollReveal>

          <ScrollReveal animation="fade-up" :delay="250">
            <p class="mx-auto mt-4 sm:mt-6 max-w-2xl text-base sm:text-lg leading-relaxed font-medium text-krem-300/90">
              Ekosistem digital terpadu untuk anggota, pembina, pengurus, dan alumni. Kelola SKU, presensi, kas, inventaris, dan kabar ambalan dengan lebih tertib dan transparan.
            </p>
          </ScrollReveal>

          <ScrollReveal animation="fade-up" :delay="350">
            <div class="mt-8 sm:mt-10 flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
              <a
                href="/daftar"
                class="guest-focus-ring group relative isolate overflow-hidden rounded-[1.25rem] bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 px-6 sm:px-10 py-3.5 sm:py-4.5 text-sm sm:text-base font-black text-hutan-800 shadow-xl shadow-emas-400/30 transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-emas-400/40 active:scale-95 w-full sm:w-auto before:absolute before:inset-0 before:bg-white before:opacity-0 before:transition-opacity before:duration-300 hover:before:opacity-20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
              >
                <span class="relative z-10 flex items-center gap-1.5">
                  Bergabung Sekarang
                  <AppIcon name="arrowRight" class="h-4 w-4 sm:h-5 sm:w-5 transition group-hover:translate-x-1" />
                </span>
              </a>
              <a
                href="/login"
                class="guest-focus-ring rounded-[1.25rem] border-2 border-daun-500 bg-hutan-800/40 px-6 sm:px-10 py-3.5 sm:py-4.5 text-sm sm:text-base font-bold text-krem-300 backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-emas-400 hover:bg-daun-500/20 hover:text-emas-400 hover:shadow-2xl hover:shadow-daun-500/20 active:scale-95 w-full sm:w-auto focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
              >
                Masuk Portal
              </a>
            </div>
          </ScrollReveal>

          <!-- Hero Stat Badge (real data only) -->
          <ScrollReveal animation="fade-up" :delay="450">
            <div class="mt-10 sm:mt-12 flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-6 p-4 sm:p-6 rounded-[1.5rem] border border-daun-500/20 bg-hutan-800/40 backdrop-blur-lg shadow-xl shadow-black/30">
              <div class="text-center sm:text-left">
                <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-lumut-400">Anggota Aktif</span>
                <span class="text-xl sm:text-2xl font-black text-krem-100 mt-1 block font-variant-numeric tabular-nums">{{ statsItems[0]?.value }}</span>
              </div>
              <div class="w-px h-6 sm:h-8 bg-daun-500/30 mx-auto sm:mx-0"></div>
              <div class="text-center sm:text-left">
                <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-lumut-400">Purna Ambalan</span>
                <span class="text-xl sm:text-2xl font-black text-krem-100 mt-1 block font-variant-numeric tabular-nums">{{ statsItems[1]?.value }}</span>
              </div>
            </div>
          </ScrollReveal>
        </div>

        <!-- Decorative hero glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 h-[500px] w-[500px] rounded-full bg-daun-400/10 blur-[100px] -z-10" aria-hidden="true"></div>
        <div class="absolute bottom-10 right-10 h-[350px] w-[350px] rounded-full bg-emas-400/10 blur-[100px] -z-10" aria-hidden="true"></div>
      </section>

      <!-- ========== SAMBUTAN PEMBINA ========== -->
      <section id="sambutan" class="py-16 sm:py-20 lg:py-28 scroll-mt-20" aria-label="Sambutan pembina">
        <div class="mx-auto max-w-5xl">
          <div class="relative rounded-[1.75rem] overflow-hidden border border-daun-500/30 bg-hutan-700/50 shadow-2xl shadow-daun-500/10">
            <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=1600')] bg-cover bg-center bg-no-repeat opacity-35 blend-mode-multiply" aria-hidden="true"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-hutan-800 via-hutan-800/70 to-transparent" aria-hidden="true"></div>
            <div class="relative p-6 sm:p-10 lg:p-16 text-center z-10">
              <ScrollReveal animation="fade-up">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-daun-500/40 bg-daun-500/12 mb-4 sm:mb-6 inline-block">
                  <AppIcon name="profile" class="h-4 w-4 text-emas-400" />
                  <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-daun-400">Sambutan Pembina</span>
                </div>
              </ScrollReveal>

              <ScrollReveal animation="fade-up" :delay="100">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-transparent mb-4 sm:mb-6 guest-text-gradient">
                  Sambutan dari Pembina {{ ambalanNama }}
                </h2>
              </ScrollReveal>

              <ScrollReveal animation="fade-up" :delay="200">
                <p class="mx-auto mt-2 sm:mt-4 max-w-2xl text-base sm:text-lg leading-relaxed font-medium text-krem-300/90">
                  {{ sambutanPembina || defaultSambutan }}
                </p>
              </ScrollReveal>

              <ScrollReveal animation="fade-up" :delay="300">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 mt-8 sm:mt-10">
                  <button
                    type="button"
                    class="guest-focus-ring group relative isolate overflow-hidden rounded-[1.25rem] bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 px-6 sm:px-10 py-3.5 sm:py-4.5 text-sm sm:text-base font-black text-hutan-800 shadow-xl shadow-emas-400/30 transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-emas-400/40 active:scale-95 w-full sm:w-auto before:absolute before:inset-0 before:bg-white before:opacity-0 before:transition-opacity before:duration-300 hover:before:opacity-20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                    @click="navigateTo('/daftar')"
                  >
                    <span class="relative z-10 flex items-center gap-1.5">
                      Daftar Anggota Baru
                      <AppIcon name="arrow-right" class="h-4 w-4 sm:h-5 sm:w-5 transition group-hover:translate-x-1" />
                    </span>
                  </button>
                  <button
                    type="button"
                    class="guest-focus-ring rounded-[1.25rem] border-2 border-daun-500/50 bg-hutan-800/30 px-6 sm:px-10 py-3.5 sm:py-4.5 text-sm sm:text-base font-bold text-krem-300 backdrop-blur-sm transition-all duration-300 hover:border-daun-500 hover:bg-daun-500/20 hover:text-emas-400 hover:-translate-y-1 active:scale-95 w-full sm:w-auto focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                    @click="navigateTo('/login')"
                  >
                    Masuk Portal
                  </button>
                </div>
              </ScrollReveal>
            </div>
          </div>
        </div>
      </section>

      <!-- ========== PROFIL AMBALAN ========== -->
      <section id="profil-ambalan" class="py-16 sm:py-20 lg:py-28 bg-hutan-700/20 scroll-mt-20" aria-label="Profil ambalan">
        <div class="mx-auto max-w-6xl">
          <div class="grid gap-8 sm:gap-12 lg:gap-16 md:grid-cols-2 items-center">
            <div class="space-y-6 sm:space-y-8">
              <ScrollReveal animation="fade-left" :delay="50">
                <div class="flex items-center gap-3">
                  <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-daun-400 to-emas-400 flex items-center justify-center shadow-lg shadow-daun-500/20">
                    <AppIcon name="map-pin" class="h-6 w-6 text-hutan-800" />
                  </div>
                  <h3 class="text-2xl sm:text-3xl font-extrabold text-krem-100 tracking-tight">Profil Ambalan</h3>
                </div>
              </ScrollReveal>

              <ScrollReveal animation="fade-left" :delay="150">
                <p class="text-base sm:text-lg leading-relaxed font-medium text-krem-300/90">
                  {{ ambalanProfile || 'Ambalan ' + ambalanNama + ' adalah wadah pengembangan kepemudaan dan kepramukaan di SMAN 2 Maros, berkomitmen untuk membentuk generasi muda yang berakhlak mulia, berdisiplin, dan siap berjasa melalui kegiatan kepramukaan.' }}
                </p>
              </ScrollReveal>

              <ScrollReveal animation="fade-left" :delay="250">
                <div class="grid gap-4 sm:gap-6">
                  <div class="p-4 sm:p-6 rounded-xl border border-daun-500/20 bg-hutan-800/30 shadow-lg shadow-daun-500/10">
                    <div class="flex items-center gap-3 sm:gap-4 mb-2 sm:mb-3">
                      <div class="h-8 sm:h-10 w-8 sm:w-10 rounded-lg bg-daun-500/20 flex items-center justify-center text-emas-400">
                        <AppIcon name="calendar" class="h-4 sm:h-5 w-4 sm:w-5" />
                      </div>
                      <div>
                        <p class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-lumut-400">Terdiri sejak</p>
                        <p class="text-lg sm:text-xl font-extrabold text-krem-100">{{ ambalanTanggalBerdiri }}</p>
                        <p class="text-[10px] sm:text-xs text-krem-300/70">SMAN 2 Maros</p>
                      </div>
                    </div>
                  </div>
                </div>
              </ScrollReveal>
            </div>

            <div class="relative h-64 sm:h-80 lg:h-[400px] rounded-[1.75rem] overflow-hidden border border-daun-500/30 shadow-2xl shadow-daun-500/10 group">
              <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1544717297-fa6524885623?w=1600')] bg-cover bg-center bg-no-repeat scale-105 transition-transform duration-1000 group-hover:scale-105" aria-hidden="true"></div>
              <div class="absolute inset-0 bg-gradient-to-tr from-hutan-800/80 via-hutan-800/40 to-transparent" aria-hidden="true"></div>
              <div class="absolute bottom-6 sm:bottom-8 left-6 sm:left-8 right-6 sm:right-8 p-4 sm:p-6 rounded-[1.25rem] bg-hutan-800/70 backdrop-blur-md border border-daun-500/20 z-10 shadow-xl shadow-black/40">
                <div class="flex items-start gap-3 sm:gap-4">
                  <div class="h-10 sm:h-12 w-10 sm:w-12 rounded-full bg-daun-500 flex items-center justify-center text-white flex-shrink-0 shadow-lg shadow-daun-500/30">
                    <AppIcon name="checkCircle" class="h-5 sm:h-6 w-5 sm:w-6" />
                  </div>
                  <div>
                    <p class="text-sm sm:text-base font-black text-krem-100">Terpercaya oleh anggota</p>
                    <p class="text-[10px] sm:text-xs text-krem-300/80">Terdaftar dan terakreditasi sebagai wadah kepramukaan resmi</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ========== STATISTIK ========== -->
      <section id="stats" class="py-16 sm:py-20 lg:py-28 scroll-mt-20" aria-label="Statistik ambalan">
        <div class="mx-auto max-w-5xl">
          <div class="mb-8 sm:mb-12 text-center">
            <ScrollReveal animation="fade-up">
              <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/30 bg-hutan-800/50 px-4 py-1.5 text-[10px] sm:text-xs font-black text-emas-400 inline-block mb-4 sm:mb-6 backdrop-blur-sm">
                <span class="h-1.5 w-1.5 rounded-full bg-daun-400 animate-pulse" aria-hidden="true"></span>
                Angka Terkini
              </div>
            </ScrollReveal>
            <ScrollReveal animation="fade-up" :delay="100">
              <h2 class="text-3xl sm:text-4xl font-extrabold text-transparent mb-3 sm:mb-5 guest-text-gradient">Statistik Ambalan</h2>
            </ScrollReveal>
          </div>

          <div class="grid gap-6 sm:gap-8 lg:gap-10 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
            <StatsSection :stats-items="statsItems" :enable-repeat-animation="false" />
          </div>
        </div>
      </section>

      <!-- ========== FITUR UNGGULAN ========== -->
      <section id="fitur" class="py-16 sm:py-20 lg:py-28 bg-hutan-700/20 scroll-mt-20" aria-label="Layanan unggulan ambalan">
        <div class="mx-auto max-w-6xl">
          <div class="mb-8 sm:mb-12 text-center">
            <ScrollReveal animation="fade-up">
              <div class="inline-flex items-center gap-2 rounded-full border border-daun-500/40 bg-hutan-800/50 px-4 py-1.5 text-[10px] sm:text-xs font-black text-daun-400 inline-block mb-4 sm:mb-6 backdrop-blur-sm">
                <AppIcon name="layers" class="h-4 w-4" />
                Layanan Unggulan
              </div>
            </ScrollReveal>
            <ScrollReveal animation="fade-up" :delay="100">
              <h2 class="text-3xl sm:text-4xl font-extrabold text-transparent mb-3 sm:mb-5 guest-text-gradient">Sistem Informasi Terintegrasi</h2>
            </ScrollReveal>
            <ScrollReveal animation="fade-up" :delay="200">
              <p class="mx-auto mt-2 sm:mt-4 max-w-2xl text-sm sm:text-base leading-relaxed font-medium text-krem-300/80">
                Lima layanan digital yang saling terhubung untuk mendukung operasional ambalan secara efisien dan transparan.
              </p>
            </ScrollReveal>
          </div>

          <FeaturesSection :features="features" @feature-click="handleFeatureClick" section-id="fitur" :show-header="false" />
        </div>
      </section>

      <!-- ========== GALERI KEGIATAN ========== -->
      <section id="galeri" class="py-16 sm:py-20 lg:py-28 scroll-mt-20" aria-label="Galeri kegiatan">
        <div class="mx-auto max-w-6xl">
          <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 sm:gap-6 mb-8 sm:mb-12">
            <div>
              <ScrollReveal animation="fade-up">
                <div class="inline-flex items-center gap-2 rounded-full border border-emas-400/40 bg-hutan-800/50 px-4 py-1.5 text-[10px] sm:text-xs font-black text-emas-400 inline-block mb-4 sm:mb-6 backdrop-blur-sm">
                  <AppIcon name="gallery" class="h-4 w-4" />
                  Dokumentasi Kegiatan
                </div>
              </ScrollReveal>
              <ScrollReveal animation="fade-up" :delay="100">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-transparent mb-3 sm:mb-5 guest-text-gradient">Galeri Kegiatan</h2>
              </ScrollReveal>
            </div>
            <div class="text-right sm:text-left">
              <ScrollReveal animation="fade-up" :delay="200">
                <button type="button" class="guest-focus-ring rounded-xl border border-daun-500/40 bg-hutan-800/50 px-4 sm:px-5 py-2.5 sm:py-3 text-[10px] sm:text-sm font-black text-krem-300 hover:bg-daun-500/20 hover:text-emas-400 hover:shadow-lg hover:shadow-daun-500/20 transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400" @click="navigateTo('/galeri')">
                  Lihat Semua Galeri
                  <AppIcon name="arrow-right" class="h-4 w-4 ml-1 sm:ml-2 transition group-hover:translate-x-1" />
                </button>
              </ScrollReveal>
            </div>
          </div>

          <GallerySection :gallery="guestStore.gallery" @gallery-item-click="handleGalleryItemClick" section-id="galeri" :show-header="false" />
        </div>
      </section>

      <!-- ========== PENGUMUMAN TERKINI ========== -->
      <section id="pengumatan" class="py-16 sm:py-20 lg:py-28 scroll-mt-20" aria-label="Pengumuman terbaru">
        <div class="mx-auto max-w-6xl">
          <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 sm:gap-6 mb-8 sm:mb-12">
            <div>
              <ScrollReveal animation="fade-up">
                <div class="inline-flex items-center gap-2 rounded-full border border-daun-400/40 bg-hutan-800/50 px-4 py-1.5 text-[10px] sm:text-xs font-black text-daun-400 inline-block mb-4 sm:mb-6 backdrop-blur-sm">
                  <AppIcon name="announcements" class="h-4 w-4" />
                  Kabar Terkini
                </div>
              </ScrollReveal>
              <ScrollReveal animation="fade-up" :delay="100">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-transparent mb-3 sm:mb-5 guest-text-gradient">Pengumuman Terbaru</h2>
              </ScrollReveal>
            </div>
          </div>

          <AnnouncementsSection :announcements="guestStore.announcements" :display-limit="6" section-id="pengumatan" :show-header="false" />
        </div>
      </section>

      <!-- ========== CTA ========== -->
      <section id="cta" class="py-20 sm:py-24 lg:py-32 relative overflow-hidden scroll-mt-20" aria-label="Akses cepat">
        <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=1600')] bg-cover bg-center bg-no-repeat opacity-30 blend-mode-multiply" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-hutan-800 via-hutan-800/80 to-hutan-800" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-4xl rounded-[1.75rem] overflow-hidden border border-daun-500/40 shadow-2xl shadow-daun-500/20 p-6 sm:p-10 lg:p-16 text-center z-10">
          <ScrollReveal animation="fade-up">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-daun-500/40 bg-hutan-800/40 px-4 py-1.5 text-[10px] sm:text-xs font-black text-daun-400 inline-block mb-4 sm:mb-6 backdrop-blur-sm">
              <AppIcon name="sparkles" class="h-4 w-4 text-emas-400" />
              <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-daun-400">Siap Bergabung?</span>
            </div>
          </ScrollReveal>
          <ScrollReveal animation="fade-up" :delay="100">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-transparent mb-4 sm:mb-6 guest-text-gradient">Wujudkan Prestasimu bersama Ambalan</h2>
          </ScrollReveal>
          <ScrollReveal animation="fade-up" :delay="200">
            <p class="mx-auto mt-2 sm:mt-4 max-w-xl text-base sm:text-lg leading-relaxed font-medium text-krem-300/90 mb-8 sm:mb-10">
              Jadilah bagian dari generasi muda yang berakhlak, berdisiplin, dan siap berjasa. Daftarkan diri Anda sekarang dan rasakan manfaat keanggotaan digital ambalan.
            </p>
          </ScrollReveal>
          <ScrollReveal animation="fade-up" :delay="300">
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
              <button type="button" class="guest-focus-ring group relative isolate overflow-hidden rounded-[1.25rem] bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 px-8 sm:px-14 py-4 sm:py-5 text-base sm:text-lg font-black text-hutan-800 shadow-2xl shadow-emas-400/30 transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-emas-400/40 active:scale-95 w-full sm:w-auto before:absolute before:inset-0 before:bg-white before:opacity-0 before:transition-opacity before:duration-300 hover:before:opacity-20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400" @click="navigateTo('/daftar')">
                <span class="relative z-10 flex items-center gap-1.5">
                  Daftar Sekarang
                  <AppIcon name="arrow-right" class="h-4 sm:h-5 w-4 sm:w-5 transition group-hover:translate-x-1" />
                </span>
              </button>
            </div>
          </ScrollReveal>
        </div>
      </section>

      <!-- ========== NEWSLETTER ========== -->
      <section id="newsletter" class="py-16 sm:py-20 lg:py-28 bg-hutan-700/20" aria-label="Newsletter">
        <div class="mx-auto max-w-3xl">
          <NewsletterSection :initial-email="newsletterEmail" @subscribe="handleNewsletterSubscribe" />
        </div>
      </section>

    </main>

    <!-- Mobile Bottom Nav -->
    <GuestBottomNav
      :is-mobile="isMobile"
      :active-section="activeSection"
      @navigate="onBottomNavNavigate"
    />

    <!-- Dismissed Offline Banner State -->
    <template v-if="offlineBannerDismissed">
      <!-- Banner is hidden, nothing to render -->
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useGuestStore } from '@/Stores/guest';
import { useUIStore } from '@/Stores/ui';

// Section Components
import OfflineBanner from '@/Components/Guest/OfflineBanner.vue';
import StatsSection from '@/Components/Guest/StatsSection.vue';
import FeaturesSection from '@/Components/Guest/FeaturesSection.vue';
import GallerySection from '@/Components/Guest/GallerySection.vue';
import AnnouncementsSection from '@/Components/Guest/AnnouncementsSection.vue';
import GuestBottomNav from '@/Components/Guest/GuestBottomNav.vue';
import NewsletterSection from '@/Components/Guest/NewsletterSection.vue';

// Shared Components
import AppIcon from '@/Components/AppIcon.vue';
import ScrollReveal from '@/Components/ScrollReveal.vue';
import ParticleBackground from '@/Components/ParticleBackground.vue';
import { categoryIcon, formatDate } from '@/utils/guest';

const guestStore = useGuestStore();
const uiStore = useUIStore();
const page = usePage();

const props = defineProps({
  announcements: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({ members: 0, alumni: 0 }) },
  sliderSlides: { type: Array, default: () => [] },
  gallery: { type: Array, default: () => [] },
});

// Refs
const container = ref(null);
const isMobile = ref(false);
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const offlineBannerDismissed = ref(false);
const activeSection = ref('');
const newsletterEmail = ref('');
const prefersReducedMotion = ref(
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches
);
let scrollHandler = null;
let revealObserver = null;
let lastFocusedElement = null;
let onlineHandler = null;
let offlineHandler = null;
let motionMediaQuery = null;
let motionHandler = null;
let parallaxBg = null;

// Computed
const ambalanNama = computed(() => {
  return page.props?.ambalan?.nama || 'Ambalan UPT SMAN 2 Maros';
});

const logoUrl = computed(() => {
  return page.props?.ambalan?.logo_url || '/images/Logo_Ambalan.png';
});

const sambutanPembina = computed(() => page.props?.ambalan?.sambutan_pembina || null);

const ambalanProfile = computed(() => page.props?.ambalan?.profil_singkat || null);

const ambalanTanggalBerdiri = computed(() => page.props?.ambalan?.tanggal_berdiri || '-');

const defaultSambutan = computed(() => `Selamat datang di Ambalan ${ambalanNama.value}. Mari bersama-sama membina generasi muda yang berakhlak mulia, disiplin, dan siap berjasa melalui kegiatan kepramukaan.`);

const statsItems = computed(() => [
  {
    label: 'Anggota Aktif',
    key: 'members',
    value: guestStore.stats.value?.members ?? props.stats.members,
    icon: 'members',
  },
  {
    label: 'Purna Ambalan',
    key: 'alumni',
    value: guestStore.stats.value?.alumni ?? props.stats.alumni,
    icon: 'alumni',
  },
]);

const features = [
  { iconName: 'sku', title: 'SKU Anggota', description: 'Pengelolaan Surat Keputusan Anggota secara digital, dari penerbitan hingga arsip.' },
  { iconName: 'attendance', title: 'Presensi Digital', description: 'Sistem kehadiran modern dengan QR Code dan pencatatan real-time.' },
  { iconName: 'wallet', title: 'Kas Ambalan', description: 'Pengelolaan keuangan ambalan dengan pencatatan pemasukan dan pengeluaran yang transparan.' },
  { iconName: 'inventory', title: 'Inventaris', description: 'Daftar barang ambalan lengkap dengan kondisi, lokasi, dan riwayat penggunaan.' },
  { iconName: 'dashboard', title: 'Pangkalan', description: 'Ringkasan visual aktivitas ambalan dalam satu pandangan.' },
  { iconName: 'profile', title: 'Profil Ambalan', description: 'Sejarah, visi, misi, dan struktur pengurus ambalan secara profesional.' },
];

// Helper Functions
function handleLogoError() {
  console.warn('[Guest/Home] Logo image failed to load, using fallback.');
}

function handleFeatureClick(feature) {
  console.log(`[Guest/Home] Feature clicked: ${feature.title}`);
}

function handleNewsletterSubscribe(email) {
  console.log(`[Guest/Home] Newsletter subscribed: ${email}`);
}

function handleGalleryItemClick(idx) {
  console.log(`[Guest/Home] Gallery item ${idx} clicked.`);
}

function navigateTo(path) {
  router.visit(path, { preserveScroll: false });
}

function toggleMobileMenu() {
  uiStore.setMobileMenuOpen(true);
  lastFocusedElement = document.activeElement;
  document.documentElement.style.overflow = 'hidden';
  document.body.style.overflow = 'hidden';
  document.body.style.position = 'fixed';
  document.body.style.width = '100%';
  nextTick(() => {
    const closeBtn = document.getElementById('mobile-menu-close');
    if (closeBtn) closeBtn.focus();
  });
}

function closeMobileMenu() {
  uiStore.setMobileMenuOpen(false);
  document.documentElement.style.overflow = '';
  document.body.style.overflow = '';
  document.body.style.position = '';
  document.body.style.width = '';
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

function onBottomNavNavigate(sectionId) {
  activeSection.value = sectionId;
  const el = document.getElementById(sectionId);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function dismissOfflineBanner() {
  offlineBannerDismissed.value = true;
}

function handleResize() {
  isMobile.value = window.innerWidth < 1024;
}

function setMetaTags() {
  const baseUrl = window.location.origin.replace(/\/+$/, '');
  const homeUrl = baseUrl + '/';
  const logoImg = baseUrl + '/images/Logo_Ambalan.png';
  const title = 'AMBARA — Sistem Digital Ambalan SMA 2 Maros';
  const description = 'Ekosistem digital kepramukaan: SKU, presensi, kas, inventaris, dan dashboard dalam satu platform untuk anggota SMA 2 Maros.';

  document.title = title;

  const ensureTag = (selector, attr, attrValue) => {
    let tag = document.querySelector(selector);
    if (!tag) {
      tag = document.createElement('meta');
      tag.setAttribute(attr, attrValue);
      document.head.appendChild(tag);
    }
    return tag;
  };

  ensureTag('meta[name="description"]', 'name', 'description').setAttribute('content', description);
  ensureTag('meta[property="og:title"]', 'property', 'og:title').setAttribute('content', title);
  ensureTag('meta[property="og:description"]', 'property', 'og:description').setAttribute('content', description);
  ensureTag('meta[property="og:image"]', 'property', 'og:image').setAttribute('content', logoImg);
  ensureTag('meta[property="og:url"]', 'property', 'og:url').setAttribute('content', homeUrl);
  ensureTag('meta[name="twitter:card"]', 'name', 'twitter:card').setAttribute('content', 'summary_large_image');
  ensureTag('meta[name="twitter:title"]', 'name', 'twitter:title').setAttribute('content', title);
  ensureTag('meta[name="twitter:description"]', 'name', 'twitter:description').setAttribute('content', description);

  const canonicalSelector = 'link[rel="canonical"]';
  let canonical = document.querySelector(canonicalSelector);
  if (!canonical) {
    canonical = document.createElement('link');
    canonical.setAttribute('rel', 'canonical');
    document.head.appendChild(canonical);
  }
  canonical.setAttribute('href', homeUrl);

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
    url: homeUrl,
    logo: logoImg,
    description: description,
    address: { '@type': 'PostalAddress', addressLocality: 'Maros', addressRegion: 'Sulawesi Selatan', addressCountry: 'ID' },
  });
}

function updateActiveSection() {
  if (!container.value) return;
  if (prefersReducedMotion.value) return;

  const scrollY = window.scrollY;
  const viewMiddle = scrollY + window.innerHeight * 0.4;
  let current = '';

  const sections = ['hero', 'sambutan', 'profil-ambalan', 'stats', 'fitur', 'galeri', 'pengumatan', 'cta', 'newsletter'];
  sections.forEach((id) => {
    const el = document.getElementById(id);
    if (el && el.offsetTop <= viewMiddle) current = id;
  });
  activeSection.value = current;
}

function handleScroll() {
  if (!container.value) return;
  if (prefersReducedMotion.value) return;

  const scrollY = window.scrollY;
  if (parallaxBg) parallaxBg.style.transform = `translateY(${scrollY * 0.2}px)`;

  updateActiveSection();
}

function setupIntersectionObserver() {
  if (prefersReducedMotion.value) return;

  const observerOptions = { threshold: 0.1 };
  revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObserver.unobserve(entry.target);
        }
      });
    },
    observerOptions
  );

  if (container.value) {
    container.value.querySelectorAll('[data-animate]').forEach((el) => revealObserver.observe(el));
  }
}

// Lifecycle
onMounted(() => {
  setMetaTags();
  handleResize();
  window.addEventListener('resize', handleResize);
  onlineHandler = () => { isOnline.value = true; };
  offlineHandler = () => { isOnline.value = false; };
  window.addEventListener('online', onlineHandler);
  window.addEventListener('offline', offlineHandler);

  if (props.announcements?.length) {
    guestStore.setFromProps({ announcements: props.announcements });
  }
  if (props.gallery?.length) {
    guestStore.setFromProps({ gallery: props.gallery });
  }
  if (props.sliderSlides?.length) {
    guestStore.setFromProps({ sliderSlides: props.sliderSlides });
  }
  if (props.stats) {
    guestStore.setFromProps({ stats: props.stats });
  }

  setupIntersectionObserver();
  parallaxBg = container.value?.querySelector('[data-parallax-bg]') ?? null;
  scrollHandler = handleScroll;
  window.addEventListener('scroll', scrollHandler, { passive: true });
  window.addEventListener('keydown', trapFocus);
  requestAnimationFrame(() => handleScroll());

  motionMediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  motionHandler = (e) => {
    prefersReducedMotion.value = e.matches;
  };
  motionMediaQuery.addEventListener('change', motionHandler);
});

onUnmounted(() => {
  window.removeEventListener('scroll', scrollHandler);
  window.removeEventListener('resize', handleResize);
  if (onlineHandler) window.removeEventListener('online', onlineHandler);
  if (offlineHandler) window.removeEventListener('offline', offlineHandler);
  revealObserver?.disconnect();
  window.removeEventListener('keydown', trapFocus);
  document.documentElement.style.overflow = '';
  document.body.style.overflow = '';
  document.body.style.position = '';
  document.body.style.width = '';
  if (motionMediaQuery && motionHandler) {
    motionMediaQuery.removeEventListener('change', motionHandler);
  }
});
</script>

<style scoped>
.skip-link {
  position: absolute;
  top: -40px;
  left: 0;
  z-index: 100;
  padding: 8px 16px;
  background: var(--color-emas-400, #EDD330);
  color: var(--color-hutan-800, #263D26);
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

.guest-focus-ring {
  outline: none;
}
.guest-focus-ring:focus-visible {
  outline: 2px solid #EDD330;
  outline-offset: 2px;
}

.guest-text-gradient {
  background: linear-gradient(135deg, #f0ead8 0%, #d4dc9a 50%, #A7B92B 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.guest-text-gradient-gold {
  background: linear-gradient(135deg, #EDD330 0%, #A7B92B 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.guest-noise-overlay {
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.15'/%3E%3C/svg%3E");
}
</style>
