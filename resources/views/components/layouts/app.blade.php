<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @vite(['resources/js/app.js', 'resources/scss/app.scss'])
    
    <title>{{ $title ?? 'KMI System' }}</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    @stack('styles')
</head>
<body class="h-100 bg-light">

    <div class="d-flex h-100">
        <!-- Komponen Sidebar -->
        <x-navigation.sidebar />
        
        <!-- Overlay untuk mobile, dikontrol oleh JS -->
        <div id="sidebar-overlay" class="sidebar-overlay d-lg-none"></div>

        <!-- ======================================================= -->
        <!-- PASTIKAN ID "content-wrapper" ADA DI DIV BERIKUT INI: -->
        <!-- ======================================================= -->
        <div id="content-wrapper" class="d-flex flex-column flex-grow-1">
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
    
    <!-- Loader global -->
    <div id="loading-overlay" class="loading-overlay d-none">
        <div class="loader mb-4"></div>
        <h2 class="h4 fw-semibold text-secondary">Memuat Halaman...</h2>
    </div>

    @stack('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.js'></script>
</body>
</html>
