@props([
    'navigation' => [
        [
            'name' => 'Dashboard',
            'route_name' => 'manufaktur.dashboard.show',
            // Gunakan nama rute lengkap
            'active_on' => ['manufaktur.dashboard.show', 'manufaktur.pro.transaction.detail*']
        ],
        [
            'name' => 'Monitoring PRO',
            'route_name' => 'monitoring-pro.index',
            // Diasumsikan rute ini TIDAK memiliki prefix 'manufaktur.'
            'active_on' => ['monitoring-pro.index*', 'pro.detail.buyer*']
        ],
        ['name' => 'Data COHV', 'route_name' => 'manufaktur.show.detail.data2'],
        ['name' => 'Monitoring COGI', 'route_name' => 'cogi.report'],
        ['name' => 'Monitoring GR', 'route_name' => 'list.gr'],
    ],
])

@php
    $user = Auth::user();

    // 1. Logika untuk mendapatkan KODE AKTIF
    $kodeAktif = request()->route('kode');

    // Jika tidak ada 'kode', cek rute detail transaksi dan ambil 'werksCode'
    if (!$kodeAktif) {
        if (request()->routeIs('manufaktur.pro.transaction.detail*')) {
            $kodeAktif = request()->route('werksCode');
        }
    }

    // Defaultkan ke null jika tidak ditemukan
    $kodeAktif = $kodeAktif ?? null;


    // 2. Normalisasi Navigasi
    $normalizedNav = collect($navigation)->map(function ($item) use ($kodeAktif) {
        $name = $item['name'] ?? 'Menu';
        $active = false;
        $href = '#';

        if (isset($item['route_name']) && $kodeAktif && Route::has($item['route_name'])) {

            // Perbaikan: Pastikan parameter 'kode' selalu dilewatkan saat memanggil route()
            $params = array_merge(['kode' => $kodeAktif], $item['params'] ?? []);
            $href = route($item['route_name'], $params);

            // Logika Aktivasi
            $activePattern = $item['active_on'] ?? $item['route_name'].'*';
            $active = request()->routeIs($activePattern);
        }

        return compact('name','href','active');
    })->all();
@endphp

<nav class="navbar navbar-expand bg-white shadow-sm">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button id="mobile-sidebar-toggle" class="btn d-lg-none">
                <i class="fas fa-bars"></i>
            </button>

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

            <div class="dropdown d-md-none">
                <button class="btn btn-light" type="button" id="topbar-menu-mobile" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="topbar-menu-mobile">
                    @foreach($normalizedNav as $item)
                        <li>
                            <a class="dropdown-item {{ $item['active'] ? 'active' : '' }}" href="{{ $item['href'] }}">
                                {{ $item['name'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>

        <div class="ms-auto d-flex align-items-center">
            <div class="text-end d-none d-sm-block">
                <p class="small fw-semibold text-dark mb-0">{{ $user->name ?? 'Guest User' }}</p>
                <p class="small text-muted mb-0 text-capitalize">{{ $user->role ?? 'Guest' }}</p>
            </div>
            <i class="fa-solid fa-user ms-3"></i>
        </div>
    </div>
</nav>
