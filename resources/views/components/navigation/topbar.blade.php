@props([
    'navigation' => [
        ['name' => 'Dashboard', 'route_name' => 'manufaktur.dashboard.show'],
        ['name' => 'List Data', 'route_name' => 'manufaktur.show.detail.data2'],
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

<!-- Topbar yang Sudah Diperbaiki dengan Bootstrap & Vanilla JS -->
<nav class="navbar navbar-expand bg-white shadow-sm">
    <div class="container-fluid">
        <!-- Grup Tombol Kiri: Mobile Toggle & Desktop Collapse -->
        <div class="d-flex align-items-center">
            <!-- Tombol Hamburger untuk Mobile (muncul di layar kecil) -->
            <button id="sidebar-mobile-toggle" class="btn btn-light d-lg-none me-3">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Navigasi Utama Topbar (muncul di layar medium ke atas) -->
            <nav class="d-none d-md-block">
                <ul class="nav nav-pills">
                    @foreach($normalizedNav as $item)
                        <li class="nav-item">
                            <a href="{{ $item['href'] }}"
                               class="nav-link nav-loader-link {{ $item['active'] ? 'active' : '' }}">
                                {{ $item['name'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>

        <!-- Konten Navbar Kanan: Info Pengguna -->
        <div class="ms-auto d-flex align-items-center">
            <div class="text-end d-none d-sm-block">
                <p class="small fw-semibold text-dark mb-0">{{ $user->name ?? 'Guest User' }}</p>
                <p class="small text-muted mb-0 text-capitalize">{{ $user->role ?? 'Guest' }}</p>
            </div>
            <i class="fa-solid fa-user ms-3"></i>
        </div>
    </div>
</nav>