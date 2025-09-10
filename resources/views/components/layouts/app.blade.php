<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- PERBAIKAN: Cukup panggil file scss dan js utama -->
    @vite(['resources/js/app.js', 'resources/scss/app.scss'])
    
    <title>{{ $title ?? 'KMI System' }}</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    @stack('styles')
</head>
<body class="h-100 bg-light">

    <!-- PERBAIKAN: Semua atribut Alpine.js dihapus -->
    <!-- State sidebar akan dikontrol oleh kelas pada tag <body> ini dari JavaScript -->

    <div class="d-flex h-100">
        <!-- Komponen Sidebar -->
        <x-navigation.sidebar />
        
        <!-- Overlay untuk mobile, dikontrol oleh JS -->
        <div id="sidebar-overlay" class="sidebar-overlay d-lg-none"></div>

        <div class="d-flex flex-column flex-grow-1">
            <!-- Komponen Topbar -->
            <x-navigation.topbar />

            <!-- Konten Utama Halaman -->
            <main class="flex-grow-1" style="overflow-y: auto;">
                <div class="container-fluid p-4">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
    
    <!-- Loader global, dipindahkan dari layout landing agar konsisten -->
    <div id="loading-overlay" class="loading-overlay d-none">
        <div class="loader mb-4"></div>
        <h2 class="h4 fw-semibold text-secondary">Memuat Halaman...</h2>
    </div>

    @stack('scripts')
</body>
</html>