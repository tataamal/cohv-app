<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Pastikan app.scss tidak meng-import Bootstrap lagi untuk menghindari duplikasi --}}
    @vite(['resources/js/app.js', 'resources/scss/app.scss'])
    
    <title>{{ $title ?? 'COHV-PT.Kayu Mebel Indonesia' }}</title>
    <link rel="icon" href="{{ asset('images/KMI.png') }}">
    
    {{-- Semua CSS di sini sudah benar --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    @stack('styles')
</head>
<body class="h-100 bg-light">

    <x-navigation.sidebar />
    <div id="sidebar-overlay" class="sidebar-overlay"></div>
    
    <div id="content-wrapper" class="d-flex flex-column flex-grow-1">
        <x-navigation.topbar />
        <main class="flex-grow-1" style="overflow-y: auto;">
            <div class="container-fluid p-4">
                {{ $slot }}
            </div>
        </main>
    </div>
    
    {{-- Script dipindahkan dari <head> ke sini untuk performa yang lebih baik --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    {{-- [PERBAIKI URUTAN] @stack('scripts') dipindah ke paling akhir agar bisa menggunakan library di atasnya --}}
    @stack('scripts')
    
</body>
</html>