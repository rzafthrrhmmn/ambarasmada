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
    </style>
</head>
<body class="antialiased" style="background-color:#263D26;">
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
</body>
</html>
