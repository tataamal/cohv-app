@php
    $menuItems = $menuItems ?? [];
    $user = Auth::user() ?? (object)['name' => 'User', 'role' => 'Guest'];
@endphp

<!-- Struktur HTML Sidebar yang Disederhanakan -->
<aside id="sidebar" class="sidebar d-flex flex-column flex-shrink-0">
    
    <a href="{{ route('dashboard-landing') }}" class="sidebar-brand d-flex align-items-center text-decoration-none text-dark">
        <img src="{{ asset('images/KMI.png') }}" alt="Logo" class="logo-img rounded-circle">
        <span class="sidebar-text ms-2 sidebar-brand-text">KMI-COHV</span>
    </a>

    <nav class="flex-grow-1" style="overflow-y: auto;">
        <ul class="nav nav-pills flex-column">
            @forelse($menuItems as $section)
                <li class="nav-section-title">
                    <h2 class="sidebar-text text-uppercase">{{ $section['title'] }}</h2>
                </li>
                
                @foreach($section['items'] as $item)
                    <li class="nav-item">
                        @if(!empty($item['submenu']))
                            @php
                                $currentPath = request()->path();
                                $currentKode = last(explode('/', $currentPath));
                                $isParentActive = collect($item['submenu'])->contains(function ($subitem) use ($currentKode) {
                                    $routeKode = $subitem['route_params']['kode'] ?? null;
                                    return $currentKode && $routeKode && $currentKode === $routeKode;
                                });
                                $collapseId = 'submenu-' . Str::slug($item['name']);
                            @endphp
                            <a href="#{{ $collapseId }}" 
                               data-bs-toggle="collapse" 
                               role="button" 
                               aria-expanded="{{ $isParentActive ? 'true' : 'false' }}"
                               class="nav-link d-flex justify-content-between align-items-center {{ $isParentActive ? 'active-parent' : '' }}"
                               title="{{ $item['name'] }}">
                                <!-- IKON DIHAPUS DARI SINI -->
                                <span class="sidebar-text">{{ $item['name'] }}</span>
                                <i class="sidebar-text fa-solid fa-chevron-down collapse-arrow"></i>
                            </a>
                            <div class="collapse submenu {{ $isParentActive ? 'show' : '' }}" id="{{ $collapseId }}">
                                <ul class="nav flex-column">
                                    @foreach($item['submenu'] as $subitem)
                                        @php
                                            $routeKode = $subitem['route_params']['kode'] ?? null;
                                            $isSubmenuActive = $currentKode && $routeKode && $currentKode === $routeKode;
                                        @endphp
                                        <li class="nav-item">
                                            <a href="{{ route($subitem['route_name'], $subitem['route_params']) }}" 
                                               class="nav-link {{ $isSubmenuActive ? 'active' : '' }}">
                                                {{ $subitem['name'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            @php $isActive = request()->routeIs($item['route_name']); @endphp
                            <a href="{{ route($item['route_name']) }}" 
                               class="nav-link {{ $isActive ? 'active' : '' }}"
                               title="{{ $item['name'] }}">
                                <!-- IKON DIHAPUS DARI SINI -->
                                <span class="sidebar-text">{{ $item['name'] }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            @empty
                <li class="p-3 text-muted small">No menu items found.</li>
            @endforelse
        </ul>
    </nav>

    <div class="sidebar-footer p-2 mt-auto">
        <div class="user-info d-flex align-items-center">
            <i class="fa-solid fa-user fa-fw fs-5"></i>
            <div class="sidebar-text ms-3">
                <p class="small fw-semibold text-dark mb-0">{{ $user->name }}</p>
                <p class="small text-muted mb-0 text-capitalize">{{ $user->role }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mb-0">
            @csrf
            <button type="submit" class="btn w-100 d-flex align-items-center sidebar-footer-btn">
                <i class="fas fa-sign-out-alt fa-fw nav-icon"></i>
                <span class="sidebar-text">Logout</span>
            </button>
        </form>

        <button id="sidebar-collapse-toggle" class="btn w-100 d-flex align-items-center sidebar-footer-btn">
            <i class="fas fa-chevron-left fa-fw collapse-icon nav-icon"></i>
            <span class="sidebar-text">Collapse</span>
        </button>
    </div>
</aside>