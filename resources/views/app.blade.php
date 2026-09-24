<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#263D26">
    <meta name="description" content="Sistem Ekosistem Digital Kepramukaan Ambalan UPT SMAN 2 Maros">
    <title inertia>{{ config('app.name') }}</title>
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fonts
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/images/Logo_Ambalan.png" type="image/png" sizes="any">
    <link rel="apple-touch-icon" href="/images/Logo_Ambalan.png">
</head>
<body class="antialiased">
    @inertia
</body>
</html>
