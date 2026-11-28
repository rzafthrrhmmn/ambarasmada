<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#263D26">
    <meta name="description" content="Sistem Ekosistem Digital Kepramukaan Ambalan UPT SMAN 2 Maros">
    <title inertia>{{ config('app.name') }}</title>
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/images/icons/favicon.ico" sizes="48x48">
    <link rel="icon" href="/images/icons/favicon-32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="/images/icons/favicon-16.png" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="/images/icons/apple-touch-icon.png">
    <link rel="mask-icon" href="/images/icons/favicon-32.png" color="#263D26">
    <style>
        /* Prevent white blink on load — ensure dark background before CSS hydrates */
        html, body {
            background-color: #263D26 !important;
            color: #f0ead8;
        }

        /* Gentle section reveal — no initial opacity:0 so no white flash */
        .section-animate > * {
            transform: translateY(12px);
            opacity: 0.92;
            animation: sectionSlideIn 0.55s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }
        .section-animate > *:nth-child(1) { animation-delay: 0.04s; }
        .section-animate > *:nth-child(2) { animation-delay: 0.10s; }
        .section-animate > *:nth-child(3) { animation-delay: 0.16s; }
        .section-animate > *:nth-child(4) { animation-delay: 0.22s; }
        .section-animate > *:nth-child(5) { animation-delay: 0.28s; }
        .section-animate > *:nth-child(6) { animation-delay: 0.34s; }
        .section-animate > *:nth-child(7) { animation-delay: 0.40s; }
        .section-animate > *:nth-child(8) { animation-delay: 0.46s; }
        .section-animate > *:nth-child(9) { animation-delay: 0.52s; }
        .section-animate > *:nth-child(10) { animation-delay: 0.58s; }

        @keyframes sectionSlideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Fade-in untuk elemen tunggal (misal card di dalam section) */
        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0.92;
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }

        /* Guest header & footer — truly fixed, never scroll with page */
        #guest-header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
        }
        #guest-footer {
            position: fixed !important;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 40;
        }
        body.menu-open {
            overflow: hidden !important;
            position: fixed !important;
            width: 100%;
            top: 0;
        }
    </style>
</head>
<body class="antialiased" style="background-color:#263D26;">
    @php
        use App\Models\Ambalan;
        $ambalan = Ambalan::first();
        $isGuest = request()->routeIs(['home', 'guest.about', 'guest.adat-sandi']);
        $ambalanNama = $ambalan ? $ambalan->nama : 'Ambalan UPT SMAN 2 Maros';
        $ambalanLogo = $ambalan && $ambalan->logo_url ? $ambalan->logo_url : asset('images/Logo_Ambalan.png');
    @endphp

    @if($isGuest)
    <!-- Guest Header (outside #app, truly fixed — never scrolls) -->
    <header id="guest-header" class="fixed top-0 left-0 right-0 z-50 w-full bg-hutan-800/95 backdrop-blur-md border-b border-daun-500/20">
        <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between px-4 py-2.5 sm:px-6 sm:py-3">
                <a
                    href="/"
                    class="flex items-center gap-3 group"
                    aria-label="Beranda {{ $ambalanNama }}"
                >
                    <div class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-daun-500/60 bg-hutan-800 transition-all duration-300 group-hover:border-emas-400/60">
                        <img
                            src="{{ $ambalanLogo }}"
                            alt="Logo Ambalan UPT SMAN 2 Maros"
                            class="h-10 w-10 object-contain"
                            width="48"
                            height="48"
                            loading="eager"
                        />
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-sm font-black text-krem-100">{{ $ambalanNama }}</p>
                        <p class="text-[11px] font-bold text-lumut-400">Ekosistem Digital Kepramukaan</p>
                    </div>
                </a>

                <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
                    <a
                        href="#fitur"
                        class="rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                        scroll-margin-top="80px"
                    >
                        Fitur
                    </a>
                    <a
                        href="#galeri"
                        class="rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                        scroll-margin-top="80px"
                    >
                        Galeri
                    </a>
                    <a
                        href="#pengumatan"
                        class="rounded-xl px-3 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                        scroll-margin-top="80px"
                    >
                        Pengumuman
                    </a>
                    <span class="mx-2 h-4 w-px bg-daun-500/30" aria-hidden="true"></span>
                    <a
                        href="/login"
                        class="rounded-xl px-4 py-2 text-sm font-bold text-krem-300 transition hover:bg-daun-500/20 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                    >
                        Masuk
                    </a>
                    <a
                        href="/register"
                        class="rounded-xl bg-gradient-to-r from-daun-400 to-emas-400 px-4 py-2 text-sm font-black text-hutan-800 shadow-lg shadow-emas-400/20 transition hover:brightness-110 hover:shadow-xl hover:shadow-emas-400/30 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                    >
                        Daftar
                    </a>
                </nav>

                <button
                    id="mobile-menu-button"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border-2 border-daun-500/40 text-krem-300 transition hover:border-emas-400 hover:text-emas-400 lg:hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                    onclick="toggleMobileMenu()"
                    aria-expanded="false"
                    aria-controls="mobile-menu-overlay"
                    aria-label="Buka menu navigasi"
                >
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Overlay -->
        <div
            id="mobile-menu-overlay"
            class="fixed inset-0 z-50 flex items-center justify-center bg-hutan-900/95 backdrop-blur-xl hidden"
            onclick="closeMobileMenu()"
            role="dialog"
            aria-modal="true"
            aria-label="Menu navigasi"
        >
            <div class="mx-4 w-full max-w-sm rounded-3xl border-2 border-daun-500/40 bg-hutan-800 p-6 shadow-2xl">
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img src="{{ $ambalanLogo }}" alt="Logo Ambalan" class="h-10 w-10 object-contain p-1" width="40" height="40" />
                        <span class="text-lg font-black text-krem-100">Menu</span>
                    </div>
                    <button
                        onclick="closeMobileMenu()"
                        class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-daun-500 text-krem-300 transition hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                        aria-label="Tutup menu navigasi"
                    >
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="flex flex-col gap-3">
                    <a
                        href="#fitur"
                        onclick="closeMobileMenu()"
                        class="rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                        scroll-margin-top="80px"
                    >
                        Fitur
                    </a>
                    <a
                        href="#galeri"
                        onclick="closeMobileMenu()"
                        class="rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                        scroll-margin-top="80px"
                    >
                        Galeri
                    </a>
                    <a
                        href="#pengumatan"
                        onclick="closeMobileMenu()"
                        class="rounded-2xl border-2 border-daun-500/30 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                        scroll-margin-top="80px"
                    >
                        Pengumuman
                    </a>
                    <a
                        href="/login"
                        onclick="closeMobileMenu()"
                        class="rounded-2xl border-2 border-daun-500 bg-hutan-700/50 px-4 py-3 text-center font-bold text-krem-100 transition hover:border-emas-400/50 hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                    >
                        Masuk
                    </a>
                    <a
                        href="/register"
                        onclick="closeMobileMenu()"
                        class="rounded-2xl bg-gradient-to-r from-daun-400 via-emas-400 to-daun-400 px-4 py-3 text-center font-black text-hutan-800 shadow-lg transition hover:brightness-110 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400"
                    >
                        Bergabung Sekarang
                    </a>
                </div>
            </div>
        </div>
    </header>
    @endif

    @inertia

    @if($isGuest)
    <!-- Guest Footer (outside #app, truly fixed — never scrolls) -->
    <footer id="guest-footer" class="fixed bottom-0 left-0 right-0 z-40 w-full bg-hutan-800 border-t border-daun-500/20">
        <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8 pt-8 pb-4">
            <div class="flex flex-col items-center justify-center gap-6 text-center">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl overflow-hidden border-2 border-daun-500/40 bg-hutan-800 text-emas-400">
                        <img src="{{ $ambalanLogo }}" alt="Logo Ambalan" class="h-8 w-8 object-contain" width="64" height="64" loading="eager" />
                    </div>
                    <span class="text-base font-bold text-krem-300">{{ $ambalanNama }}</span>
                </div>

                <nav class="flex flex-wrap items-center justify-center gap-6 text-xs font-medium text-lumut-400">
                    <a href="/privacy" class="transition hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400 rounded">Kebijakan Privasi</a>
                    <span class="h-3 w-px bg-daun-500/30" aria-hidden="true"></span>
                    <a href="mailto:admin@sman2maros.sch.id" class="transition hover:text-emas-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emas-400 rounded">Hubungi Admin</a>
                </nav>

                <p class="text-xs font-medium text-lumut-400">
                    &copy; {{ date('Y') }} {{ $ambalanNama }} &middot; Dibangun dengan kebanggaan.
                </p>
            </div>
        </div>
    </footer>
    @endif

    <noscript>
        <div style="
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #263D26;
            color: #f0ead8;
            font-family: ui-sans-serif, system-ui, sans-serif;
            padding: 2rem;
            text-align: center;
        ">
            <div>
                <h1 style="color: #EDD330; margin-bottom: 1rem; font-size: 1.5rem;">JavaScript Diperlukan</h1>
                <p style="opacity: 0.8; max-width: 400px; margin: 0 auto;">
                    Aplikasi ini memerlukan JavaScript untuk berfungsi. 
                    Silakan aktifkan JavaScript di browser Anda dan muat ulang halaman.
                </p>
            </div>
        </div>
    </noscript>

    <script>
        function toggleMobileMenu() {
            const overlay = document.getElementById('mobile-menu-overlay');
            const btn = document.getElementById('mobile-menu-button');
            const isNowHidden = overlay.classList.toggle('hidden');
            const isOpen = !isNowHidden;
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            document.body.classList.toggle('menu-open', isOpen);
            document.documentElement.classList.toggle('menu-open', isOpen);
        }

        function closeMobileMenu() {
            const overlay = document.getElementById('mobile-menu-overlay');
            const btn = document.getElementById('mobile-menu-button');
            overlay.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('menu-open');
            document.documentElement.classList.remove('menu-open');
        }
    </script>
</body>
</html>
