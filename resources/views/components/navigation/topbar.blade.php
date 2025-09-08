@props([
    'navigation' => [
        // [DIPERBARUI] Mengembalikan navigasi ke halaman List Data
        ['name' => 'Dashboard', 'route_name' => 'dashboard.show'],
        ['name' => 'List Data', 'route_name' => 'show.detail.data2'],
        ['name' => 'List GR', 'route_name' => 'list.gr'],
    ],
])

@php
    $user = Auth::user();
    $kodeAktif = request()->route('kode');

    $normalizedNav = collect($navigation)->map(function ($item) use ($kodeAktif) {
        $name   = $item['name'] ?? 'Menu';
        $active = false;
        $href   = '#';

        if (isset($item['route_name']) && $kodeAktif && Route::has($item['route_name'])) {
            $params = array_merge(['kode' => $kodeAktif], $item['params'] ?? []);
            $href   = route($item['route_name'], $params);
            
            $activePattern = $item['active_on'] ?? $item['route_name'].'*';
            $active = request()->routeIs($activePattern);
        }

        return compact('name','href','active');
    })->all();
@endphp

<header class="flex items-center justify-between h-16 bg-white border-b border-gray-200 px-4 sm:px-6 shrink-0">
    <!-- Sisi Kiri: Tombol & Navigasi -->
    <div class="flex items-center">
        <!-- Tombol Buka/Tutup Sidebar (Hanya di Mobile) -->
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 focus:outline-none mr-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        
        <!-- Tombol Collapse Sidebar (Hanya di Desktop) -->
        <button @click="sidebarCollapsed = !sidebarCollapsed; window.dispatchEvent(new CustomEvent('sidebar-toggled'))" class="hidden lg:flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 text-gray-600 focus:outline-none transition-transform duration-300 mr-4" :class="{'rotate-180': !sidebarCollapsed}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>

        <!-- Navigasi Utama (Hanya di Desktop) -->
        <nav class="hidden md:flex items-center space-x-2">
            @foreach($normalizedNav as $item)
                <a href="{{ $item['href'] }}"
                   @if($item['href'] !== '#')
                       @click.prevent="$dispatch('link-clicked', { href: '{{ $item['href'] }}' })"
                   @endif
                   class="px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200
                          {{ $item['active'] ? 'bg-purple-50 text-purple-700 font-semibold' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' }}">
                    {{ $item['name'] }}
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Sisi Kanan: Info Pengguna -->
    <div class="flex items-center space-x-4">
        <div class="text-right hidden sm:block">
            <p class="text-sm font-semibold text-gray-800">{{ $user->name ?? 'Guest User' }}</p>
            <p class="text-xs text-gray-500 capitalize">{{ $user->role ?? 'Guest' }}</p>
        </div>
        <i class="fa-solid fa-user"></i>
    </div>
</header>