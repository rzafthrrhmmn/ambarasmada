<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#263D26">
    <meta name="description" content="Sistem Ekosistem Digital Kepramukaan Ambalan UPT SMAN 2 Maros">

    <title>{{ config('app.name') }}</title>

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            @import 'tailwindcss';
        </style>
    @endif
</head>
<body class="bg-[#263D26] text-[#f0ead8] font-sans">
    <div class="min-h-screen bg-gradient-to-br from-[#263D26] via-[#2d4a2d] to-[#263D26] px-4 py-10">
        <div class="mx-auto max-w-5xl">
            <div class="mb-10 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                <div class="flex items-center gap-3">
                    <span class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-[#A7B92A] to-[#EDD330] text-xl font-extrabold text-[#263D26] shadow-lg shadow-[#EDD330]/40 border-2 border-[#EDD330]">P</span>
                    <div>
                        <p class="text-sm font-bold text-[#EDD330]">Ambalan UPT SMAN 2 Maros</p>
                        <h1 class="text-3xl font-extrabold text-[#f0ead8] sm:text-4xl" style="text-shadow: 2px 2px 0 rgba(0,0,0,0.3);">Satya dan Darma dalam satu genggaman.</h1>
                    </div>
                </div>
                <div class="flex gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#EDD330] px-5 py-2.5 text-sm font-extrabold text-[#263D26] shadow-lg shadow-[#EDD330]/40 transition hover:from-[#EDD330] hover:to-[#A7B92A] border-2 border-[#EDD330]">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-[#A7B92A] to-[#EDD330] px-5 py-2.5 text-sm font-extrabold text-[#263D26] shadow-lg shadow-[#EDD330]/40 transition hover:from-[#EDD330] hover:to-[#A7B92A] border-2 border-[#EDD330]">
                                Masuk
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="inline-flex items-center justify-center rounded-lg border-2 border-[#6F9435] px-5 py-2.5 text-sm font-bold text-[#d4dc9a] transition hover:bg-[#6F9435]/30 hover:text-[#EDD330]">
                                    Daftar
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-lg">
                    <p class="text-3xl font-extrabold text-[#EDD330]">{{ \App\Models\Member::where('status_aktif', 'Aktif')->count() }}</p>
                    <p class="mt-1 text-sm font-bold text-[#d4dc9a]">Anggota aktif</p>
                </div>
                <div class="rounded-2xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-lg">
                    <p class="text-3xl font-extrabold text-[#EDD330]">{{ \App\Models\Member::where('status_aktif', 'Alumni')->count() }}</p>
                    <p class="mt-1 text-sm font-bold text-[#d4dc9a]">Alumni tercatat</p>
                </div>
                <div class="rounded-2xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-lg">
                    <p class="text-3xl font-extrabold text-[#A7B92A]">5+</p>
                    <p class="mt-1 text-sm font-bold text-[#d4dc9a]">Layanan digital</p>
                </div>
                <div class="rounded-2xl border-2 border-[#A7B92A]/40 bg-gradient-to-br from-[#335233] to-[#2d4a2d] p-5 shadow-lg">
                    <p class="text-3xl font-extrabold text-[#A7B92A]">PWA</p>
                    <p class="mt-1 text-sm font-bold text-[#d4dc9a]">Akses mudah di ponsel</p>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
