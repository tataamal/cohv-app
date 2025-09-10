<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Menggunakan Vite untuk memuat SCSS Bootstrap dan JS utama --}}
    @vite(['resources/js/app.js', 'resources/scss/app.scss'])
    
    <title>{{ $title ?? 'KMI System' }}</title>
    {{-- Font Awesome untuk ikon, karena kita menggunakannya di halaman dashboard --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

    @stack('styles')
</head>
<body class="h-100 bg-body-tertiary">
    
    {{-- Overlay Loading (DIUBAH KE VANILLA JS) --}}
    <div id="loading-overlay" class="loading-overlay d-none">
        <div class="loader mb-4"></div>
        <h2 class="h4 fw-semibold text-secondary">Memuat Halaman...</h2>
    </div>
    
    {{-- Ini adalah tempat konten halaman akan dimasukkan --}}
    {{ $slot }}
    {{-- Tumpukan skrip custom dari setiap halaman --}}
    @stack('custom-scripts')
</body>
</html>