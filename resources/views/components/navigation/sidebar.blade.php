@php
    // Variabel dari kode Anda, tidak perlu diubah
    $menuItems = $menuItems ?? [];
    $user = Auth::user() ?? (object)['name' => 'User', 'role' => 'Guest'];
@endphp

<aside id="sidebar" class="sidebar d-flex flex-column">
    {{-- 1. Bagian Header/Brand Sidebar --}}
    <div class="sidebar-brand">
        <a href="{{ route('dashboard-landing') }}" class="d-flex align-items-center text-decoration-none">
            <img src="{{ asset('images/KMI.png') }}" alt="Logo KMI" class="sidebar-brand-logo">
            <span class="sidebar-brand-text">KMI-COHV</span>
        </a>
    </div>

    {{-- 2. Bagian Navigasi Utama --}}
    <nav class="sidebar-nav flex-grow-1">
        <ul class="list-unstyled">
            @forelse($menuItems as $section)
                <li class="nav-section-title">
                    <span class="nav-section-text">{{ $section['title'] }}</span>
                </li>

                @foreach($section['items'] as $item)
                    <li class="nav-item">
                        @if(!empty($item['submenu']))
                            @php
                                // Logika active state dari kode Anda, tidak diubah
                                $currentPath = request()->path();
                                $currentKode = last(explode('/', $currentPath));
                                $isParentActive = collect($item['submenu'])->contains(function ($subitem) use ($currentKode) {
                                    $routeKode = $subitem['route_params']['kode'] ?? null;
                                    return $currentKode && $routeKode && $currentKode === $routeKode;
                                });
                                $collapseId = 'submenu-' . Str::slug($item['name']);
                            @endphp
                            <a class="nav-link {{ $isParentActive ? 'active-parent' : '' }}" 
                               data-bs-toggle="collapse" 
                               href="#{{ $collapseId }}" 
                               role="button" 
                               aria-expanded="{{ $isParentActive ? 'true' : 'false' }}">
                                <i class="nav-icon {{ $item['icon'] ?? 'fa-solid fa-folder' }}"></i>
                                <span class="nav-link-text">{{ $item['name'] }}</span>
                                <i class="collapse-arrow fa-solid fa-chevron-down ms-auto"></i>
                            </a>
                            <div class="collapse {{ $isParentActive ? 'show' : '' }}" id="{{ $collapseId }}">
                                <ul class="submenu list-unstyled">
                                    @foreach($item['submenu'] as $subitem)
                                        @php
                                            // Logika active state dari kode Anda, tidak diubah
                                            $routeKode = $subitem['route_params']['kode'] ?? null;
                                            $isSubmenuActive = $currentKode && $routeKode && $currentKode === $routeKode;
                                        @endphp
                                        <li class="submenu-item">
                                            <a href="{{ route($subitem['route_name'], $subitem['route_params']) }}" class="submenu-link {{ $isSubmenuActive ? 'active' : '' }}">
                                                {{ $subitem['name'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            @php $isActive = request()->routeIs($item['route_name']); @endphp
                            <a href="{{ route($item['route_name']) }}" class="nav-link {{ $isActive ? 'active' : '' }}">
                                <i class="nav-icon {{ $item['icon'] ?? 'fa-solid fa-file-lines' }}"></i>
                                <span class="nav-link-text">{{ $item['name'] }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach

            @empty
                <li class="nav-item px-3 text-muted small">Menu tidak ditemukan.</li>
            @endforelse
        </ul>
    </nav>

    {{-- 3. Bagian Footer/User Info Sidebar --}}
    <div class="sidebar-footer">
        <div class="user-profile">
            <i class="user-avatar fa-solid fa-circle-user"></i>
            <div class="user-details">
                <div class="user-name">{{ $user->name }}</div>
                <div class="user-role">{{ $user->role }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="w-100">
            @csrf
            <button type="submit" class="btn-logout">
                <i class="btn-logout-icon fa-solid fa-right-from-bracket"></i>
                <span class="btn-logout-text">Logout</span>
            </button>
        </form>
    </div>

    {{-- Tombol untuk Collapse di Desktop --}}
    <button id="sidebar-collapse-toggle" class="sidebar-collapse-toggle d-none d-lg-block">
        <i class="fa-solid fa-chevron-left"></i>
    </button>
</aside>