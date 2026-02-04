<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="sap-username" content="{{ session('username') }}">
    <meta name="sap-password" content="{{ session('password') }}">

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
    <div id="global-loader">
        <div class="spinner"></div>
        <span class="loader-text">Processing your request, please wait a moment...</span>
    </div>
    {{-- Sidebar dipanggil langsung di dalam body --}}
    <x-navigation.sidebar />

    
    {{-- Wrapper konten juga langsung di dalam body --}}
    <div id="content-wrapper" class="d-flex flex-column flex-grow-1">
        <div class="sticky-top" style="z-index: 1020;">
            <x-navigation.topbar />
        </div>
        <main class="flex-grow-1" style="overflow-y: auto;">
            <div class="container-fluid p-4">
                {{ $slot }}
            </div>
        </main>
    </div>
    
    {{-- Overlay dipindah ke sini agar berada di atas segalanya --}}
    {{-- Overlay untuk mobile --}}
    <div id="sidebar-overlay" class="sidebar-overlay"></div>

    @stack('scripts')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.js'></script>
    <script>
        // Ambil waktu lifetime session dari konfigurasi Laravel (dalam menit) dan ubah ke milidetik
        const sessionLifetime = {{ config('session.lifetime') }} * 60 * 1000;
    
        // Tambahkan sedikit waktu buffer, misalnya 5 detik setelah session berakhir
        const redirectTime = sessionLifetime + 5000; 
    
        setTimeout(function() {
            // Alihkan ke halaman login
            window.location.href = '{{ route('login') }}'; 
        }, redirectTime);
    </script>
    <script>
        // Logika untuk Global Loader
        document.addEventListener("DOMContentLoaded", function() {
            const links = document.querySelectorAll('a[href]:not([target="_blank"]):not([href^="#"])');
            const loader = document.getElementById('global-loader');

            if (loader) {
                links.forEach(link => {
                    link.addEventListener('click', function(e) {
                         // Don't show loader if ctrl/cmd/shift click (new tab)
                        if (e.ctrlKey || e.metaKey || e.shiftKey) return;
                        loader.style.display = 'flex';
                    });
                });
            }
        });

        // FIX: Sembunyikan loader saat browser back/forward (BFCache)
        window.addEventListener('pageshow', function(event) {
            const loader = document.getElementById('global-loader');
            if (loader) {
                loader.style.display = 'none';
            }
        });
    </script>
    
</body>
</html>