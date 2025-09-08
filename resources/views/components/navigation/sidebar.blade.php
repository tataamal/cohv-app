@php
    // Menyediakan nilai default untuk $menuItems untuk mencegah galat jika variabel tidak terdefinisi
    $menuItems = $menuItems ?? [];
    // Asumsi Auth::user() tersedia. Jika tidak, sediakan nilai default.
    $user = Auth::user() ?? (object)['name' => 'User', 'role' => 'Guest'];
@endphp

<!-- Sidebar Component -->
<aside 
    x-cloak
    :class="{
        'translate-x-0': sidebarOpen, 
        '-translate-x-full': !sidebarOpen,
        'lg:w-20': sidebarCollapsed,
        'lg:w-64': !sidebarCollapsed
    }" 
    class="fixed inset-y-0 left-0 z-40 w-64 bg-white text-gray-800 transform transition-all duration-300 ease-in-out lg:static lg:inset-0 lg:transform-none lg:translate-x-0 border-r border-gray-200"
    style="transition: width 0.3s ease-in-out;">
    
    <div class="flex flex-col h-full">
        <!-- Header & Toggle Button -->
        <a href="{{ route('dashboard-landing') }}"><div class="flex items-center h-16 shrink-0 px-4 border-b border-gray-200" :class="sidebarCollapsed ? 'justify-center' : 'justify-start'">
            <img src="{{ asset('images/KMI.png') }}" alt="Logo" class="rounded-full bg-gray-100 p-1 transition-all flex-shrink-0" :class="{'h-8 w-8': !sidebarCollapsed, 'h-9 w-9': sidebarCollapsed}">
            <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200 delay-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="text-lg font-semibold ml-3 whitespace-nowrap">KMI-COHV</span>
        </div></a>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">
            <!-- Parent Loop Starts Here -->
            @forelse($menuItems as $section)
                <div>
                    <!-- Section Title (e.g., 'Semua Task') -->
                    <h2 x-show="!sidebarCollapsed" x-transition:enter="transition-opacity ease-out duration-200 delay-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="px-2 mb-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        {{ $section['title'] }}
                    </h2>
                    
                    <!-- Nested Loop Starts Here ($section is available inside this loop) -->
                    @foreach($section['items'] as $item)
                        {{-- Check if the item is a dropdown with a submenu --}}
                        @if(!empty($item['submenu']))
                            @php
                                // Logic for submenu drop-down
                                $currentPath = request()->path();
                                $currentKode = last(explode('/', $currentPath));

                                $isParentActive = collect($item['submenu'])->contains(function ($subitem) use ($currentKode) {
                                    $routeKode = $subitem['route_params']['kode'] ?? null;
                                    return $currentKode && $routeKode && $currentKode === $routeKode;
                                });
                            @endphp
                            <div x-data="{ open: {{ $isParentActive ? 'true' : 'false' }} }" class="relative">
                                <!-- Tombol Parent Menu (Manufaktur) -->
                                <button 
                                    @click="sidebarCollapsed ? (sidebarCollapsed = false, open = true) : (open = !open)" 
                                    class="w-full flex items-center p-2.5 rounded-lg text-left text-gray-600 hover:bg-gray-100 group" 
                                    :class="{ 'bg-gray-100 text-gray-900': {{ $isParentActive ? 'true' : 'false' }} }" 
                                    :title="sidebarCollapsed ? '{{ $item['name'] }}' : ''">
                                    <svg class="w-6 h-6 shrink-0 text-gray-400 group-hover:text-gray-800 transition-colors" :class="{'text-purple-600': {{ $isParentActive ? 'true' : 'false' }}}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path></svg>
                                    <span x-show="!sidebarCollapsed" class="ml-3 flex-1 whitespace-nowrap font-medium transition-opacity duration-200">{{ $item['name'] }}</span>
                                    <svg x-show="!sidebarCollapsed" :class="{'rotate-180': open}" class="w-5 h-5 transform transition-transform duration-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                
                                <!-- Daftar Submenu -->
                                <div x-show="open && !sidebarCollapsed" x-collapse class="pl-6 pt-1 space-y-1">
                                    @foreach($item['submenu'] as $subitem)
                                        @php
                                            $routeKode = $subitem['route_params']['kode'] ?? null;
                                            $isSubmenuActive = $currentKode && $routeKode && $currentKode === $routeKode;
                                        @endphp
                                        <a href="{{ route($subitem['route_name'], $subitem['route_params']) }}" 
                                        @click.prevent="isLoading = true; setTimeout(() => { window.location.href = $el.href }, 150)"
                                        class="flex items-center p-2 pl-5 rounded-md text-sm font-medium relative" 
                                        :class="{ 'bg-purple-50 text-purple-700 font-semibold': {{ $isSubmenuActive ? 'true' : 'false' }}, 'text-gray-500 hover:text-gray-800 hover:bg-gray-100': !{{ $isSubmenuActive ? 'true' : 'false' }} }">
                                            @if($isSubmenuActive)
                                                <span class="absolute left-0 top-1/2 -translate-y-1/2 h-5 w-1 bg-purple-600 rounded-r-full"></span>
                                            @endif
                                            <span>{{ $subitem['name'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                        @else
                            {{-- This is for a single menu link without a submenu --}}
                            @php
                                $isActive = request()->routeIs($item['route_name']);
                            @endphp
                            <a href="{{ route($item['route_name']) }}"
                            @click.prevent="isLoading = true; setTimeout(() => { window.location.href = $el.href }, 150)"
                            class="w-full flex items-center p-2.5 rounded-lg text-left group"
                            :class="{ 
                                'bg-purple-100 text-purple-800 font-semibold': {{ $isActive ? 'true' : 'false' }},
                                'text-gray-600 hover:bg-gray-100': !{{ $isActive ? 'true' : 'false' }}
                            }"
                            :title="sidebarCollapsed ? '{{ $item['name'] }}' : ''">
                                <svg class="w-6 h-6 shrink-0 text-gray-400 group-hover:text-gray-800 transition-colors" :class="{'text-purple-600': {{ $isActive ? 'true' : 'false' }}}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path></svg>
                                <span x-show="!sidebarCollapsed" class="ml-3 flex-1 whitespace-nowrap font-medium transition-opacity duration-200">{{ $item['name'] }}</span>
                            </a>
                        @endif
                    @endforeach <!-- Nested Loop Ends Here -->

                </div>
            @empty
                <p class="px-4 text-sm text-gray-500">No menu items found.</p>
            @endforelse <!-- Parent Loop Ends Here -->

        </nav>


        <!-- Sidebar Footer -->
        <div class="px-3 pb-3 shrink-0">
             <a href="#" class="flex items-center w-full p-2.5 rounded-lg text-gray-600 hover:bg-gray-100" :class="{'justify-center': sidebarCollapsed}" :title="sidebarCollapsed ? '{{ $user->name }}' : ''">
                <i class="fa-solid fa-user"></i>
                <div x-show="!sidebarCollapsed" class="ml-3 transition-opacity duration-200">
                    <p class="text-sm font-semibold text-gray-800 whitespace-nowrap">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500 whitespace-nowrap capitalize">{{ $user->role }}</p>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="flex items-center w-full p-2.5 text-gray-600 hover:bg-red-50 hover:text-red-600 rounded-lg group" :class="{'justify-center': sidebarCollapsed}" :title="sidebarCollapsed ? 'Logout' : ''">
                    <svg class="w-6 h-6 shrink-0 text-gray-400 group-hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span x-show="!sidebarCollapsed" class="ml-3 whitespace-nowrap font-medium transition-opacity duration-200">Logout</span>
                </button>
            </form>
        </div>
    </div>
</aside>

