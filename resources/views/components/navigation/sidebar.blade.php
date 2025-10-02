@php
    $menuItems = $menuItems ?? [];
    $user = Auth::user() ?? (object)['name' => 'User', 'role' => 'Guest'];
@endphp

<aside id="sidebar" class="sidebar">
    {{-- Brand --}}
    <div class="sidebar-brand">
        <a href="{{ route('dashboard-landing') }}">
            <img src="{{ asset('images/KMI.png') }}" alt="Logo KMI" class="sidebar-brand-logo">
            <span class="sidebar-brand-text">KMI-COHV</span>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="sidebar-nav">
        <ul class="nav-list">
            @forelse($menuItems as $section)
                <li class="nav-section">
                    <span class="nav-section-title">{{ $section['title'] }}</span>
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
                            
                            <a class="nav-link {{ $isParentActive ? 'active-parent' : '' }}"
                               data-bs-toggle="collapse"
                               href="#{{ $collapseId }}"
                               aria-expanded="{{ $isParentActive ? 'true' : 'false' }}">
                                <span class="nav-text">{{ $item['name'] }}</span>
                                <i class="nav-arrow fas fa-chevron-down"></i>
                            </a>
                            
                            <div class="collapse {{ $isParentActive ? 'show' : '' }}" id="{{ $collapseId }}">
                                <ul class="submenu">
                                    @foreach($item['submenu'] as $subitem)
                                        @php
                                            $routeKode = $subitem['route_params']['kode'] ?? null;
                                            $isSubmenuActive = $currentKode && $routeKode && $currentKode === $routeKode;
                                        @endphp
                                        <li>
                                            <a href="{{ route($subitem['route_name'], $subitem['route_params']) }}" 
                                               class="submenu-link {{ $isSubmenuActive ? 'active' : '' }}">
                                                <i class="fas fa-database"></i>
                                                <span>{{ $subitem['name'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            @php $isActive = request()->routeIs($item['route_name']); @endphp
                            <a href="{{ route($item['route_name']) }}" class="nav-link {{ $isActive ? 'active' : '' }}">
                                <i class="nav-icon {{ $item['icon'] ?? 'fas fa-file' }}"></i>
                                <span class="nav-text">{{ $item['name'] }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            @empty
                <li class="nav-empty">Menu tidak ditemukan.</li>
            @endforelse
        </ul>
    </nav>

    {{-- Footer --}}
    <div class="sidebar-footer">
        <div class="user-info">
            <i class="fas fa-circle-user"></i>
            <div class="user-details">
                <div class="user-name">{{ $user->name }}</div>
                <div class="user-role">{{ $user->role }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">
                <i class="fas fa-right-from-bracket"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>

    {{-- Toggle Button --}}
    <button id="sidebar-toggle" class="sidebar-toggle">
        <i class="fas fa-chevron-left"></i>
    </button>
</aside>

<div id="sidebar-overlay" class="sidebar-overlay"></div>