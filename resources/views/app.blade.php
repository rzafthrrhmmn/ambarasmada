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
        #pwa-loading-screen {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #263D26;
            color: #f0ead8;
            font-family: ui-sans-serif, system-ui, sans-serif;
            transition: opacity 0.3s ease;
        }
        #pwa-loading-screen.hidden {
            opacity: 0;
            pointer-events: none;
        }
        #pwa-loading-screen .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(240, 234, 216, 0.2);
            border-top-color: #EDD330;
            border-radius: 50%;
            animation: pwa-spin 0.8s linear infinite;
        }
        @keyframes pwa-spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="antialiased">
    <div id="pwa-loading-screen">
        <div class="text-center">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-sm font-medium text-krem-300">Memuat aplikasi...</p>
        </div>
    </div>
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
    @inertia
    <script>
        // Sembunyikan loading screen saat Vue siap.
        // Jika Vue gagal dimuat (misal chunk tidak ter-cache saat offline),
        // loading screen tetap terlihat sebagai fallback.
        window.hidePwaLoadingScreen = function() {
            var screen = document.getElementById('pwa-loading-screen');
            if (screen) {
                screen.classList.add('hidden');
                setTimeout(function() { screen.remove(); }, 300);
            }
        };
    </script>
</body>
</html>
