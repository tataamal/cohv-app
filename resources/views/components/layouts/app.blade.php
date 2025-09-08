<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>{{ $title ?? 'KMI System' }}</title>
    
    {{-- ✅ SEMUA ASET (CSS & JS) SEKARANG DIKELOLA OLEH VITE --}}
    <style>
        [x-cloak] { display: none !important; }
    </style>
    
    @stack('styles')
</head>
<body 
    x-data="{ sidebarOpen: false, sidebarCollapsed: window.innerWidth < 1024, isLoading: false }" 
    @resize.window="sidebarCollapsed = window.innerWidth < 1024"
    x-init="$watch('isLoading', value => { if (value) startLoaderTypingEffect() })"
    @link-clicked.window="isLoading = true; setTimeout(() => { window.location.href = $event.detail.href }, 150)"
    class="h-full bg-gray-100 antialiased font-sans">

    <div class="flex h-full">
        <x-navigation.sidebar />
        
        <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak class="fixed inset-0 bg-black/30 z-30 lg:hidden"></div>

        <div class="flex flex-col flex-1 min-w-0">
            <x-navigation.topbar />

            <main class="flex-1 overflow-y-auto">
                <div class="max-w-7xl mx-auto px-6 sm:px-8 py-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>