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
</head>
<body class="antialiased">
    @inertia
</body>
</html>
