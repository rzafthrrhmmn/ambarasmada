<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#263D26">
    <meta name="description" content="Sistem Ekosistem Digital Kepramukaan Ambalan UPT SMAN 2 Maros">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fonts
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/images/icons/icon-192x192.svg" type="image/svg+xml" sizes="any">
    <link rel="apple-touch-icon" href="/images/icons/icon-512x512.svg" type="image/svg+xml">
</head>
<body class="antialiased">
    @inertia
</body>
</html>
