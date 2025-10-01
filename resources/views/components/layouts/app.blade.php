<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @vite(['resources/js/app.js', 'resources/scss/app.scss'])
    
    <title>{{ $title ?? 'COHV-PT.Kayu Mebel Indonesia' }}</title>
    <link rel="icon" href="{{ asset('images/KMI.png') }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    {{-- PASTIKAN PATH INI BENAR --}}
    {{-- <link rel="stylesheet" href="{{ asset('css/sidenav.css') }}">  --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    @stack('styles')
</head>
<body class="h-100 bg-light">

    {{-- Sidebar dipanggil langsung di dalam body --}}
    <x-navigation.sidebar />
    
    {{-- Wrapper konten juga langsung di dalam body --}}
    <div id="content-wrapper" class="d-flex flex-column flex-grow-1">
        <x-navigation.topbar />
        <main class="flex-grow-1" style="overflow-y: auto;">
            <div class="container-fluid p-4">
                {{ $slot }}
            </div>
        </main>
    </div>
    
    {{-- Overlay dipindah ke sini agar berada di atas segalanya --}}
    <div id="sidebar-overlay" class="sidebar-overlay d-lg-none"></div>

    <div id="loading-overlay" class="loading-overlay d-none">
        <div class="loader mb-4"></div>
        <h2 class="h4 fw-semibold text-color-loading">Memuat Halaman...</h2>
    </div>

    @stack('scripts')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.js'></script>
    {{-- PASTIKAN PATH INI BENAR --}}
    {{-- <script src="{{ asset('js/sidenav.js') }}"></script> --}}
    
</body>
</html>