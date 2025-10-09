<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/js/app.js', 'resources/scss/app.scss'])
    
    <title>{{ $title ?? config('app.name', 'Laravel') }} - KMI-PO System</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    {{-- Font Awesome boleh tetap ada karena tidak di-bundle via Vite --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    @stack('styles')
</head>
<body class="h-full primary-bg-subtle">
    {{ $slot }}
    
    @stack('scripts')
</body>
</html>