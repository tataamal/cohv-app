<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>{{ $title ?? 'KMI System' }}</title>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body x-data="{ isLoading: false }"
      class="h-full bg-gray-50 antialiased font-sans">
    
    {{-- Overlay Loading --}}
    <div x-show="isLoading" x-cloak class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm">
        <div class="loader mb-8"></div>
        <h2 class="text-xl font-semibold text-gray-700">Memuat Halaman...</h2>
    </div>
    
    {{-- Ini adalah tempat "Surat" atau konten halaman akan dimasukkan --}}
    {{ $slot }}

    @stack('scripts')
</body>
</html>