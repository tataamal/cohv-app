<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $title ?? config('app.name', 'Laravel') }} - KMI-PO System</title>
    
    {{-- Mengganti font menjadi Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Menerapkan font baru ke seluruh body */
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    
    @stack('styles')
</head>
<body class="h-full bg-gradient-to-br from-purple-50 via-indigo-50 to-pink-50">
    {{-- Layout ini hanya berisi slot, tanpa sidebar atau topbar --}}
    {{ $slot }}

    @stack('scripts')
</body>
</html>

