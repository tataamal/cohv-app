@props([
    'title' => null,
    'description' => null,
    'padding' => 'p-4 sm:p-6',
    'shadow' => 'shadow-md',
    'rounded' => 'rounded-lg',
    'background' => 'bg-white'
])

<div {{ $attributes->merge(['class' => "{$background} {$rounded} {$shadow}"]) }}>
    @if($title || $description)
        <div class="{{ $padding }} border-b border-gray-200">
            @if($title)
                <h3 class="text-lg font-semibold text-gray-700">{{ $title }}</h3>
            @endif
            @if($description)
                <p class="text-sm text-gray-500 {{ $title ? 'mt-1' : '' }}">{{ $description }}</p>
            @endif
        </div>
    @endif
    
    @if(isset($header))
        <div class="{{ $padding }} border-b border-gray-200">
            {{ $header }}
        </div>
    @endif
    
    <div class="{{ $title || $description || isset($header) ? $padding : $padding }}">
        {{ $slot }}
    </div>
    
    @if(isset($footer))
        <div class="{{ $padding }} border-t border-gray-200 bg-gray-50 rounded-b-lg">
            {{ $footer }}
        </div>
    @endif
</div>