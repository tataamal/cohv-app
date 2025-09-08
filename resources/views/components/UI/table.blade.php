@props([
    'headers' => [],
    'rows' => [],
    'striped' => false,
    'hoverable' => true
])

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'w-full text-sm text-left text-gray-500 responsive-table']) }}>
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
            <tr>
                @foreach($headers as $header)
                    <th scope="col" class="px-6 py-3 font-medium">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr class="border-b md:border-b-0 
                          {{ $striped && $index % 2 === 1 ? 'bg-gray-50' : '' }}
                          {{ $hoverable ? 'hover:bg-gray-50 transition-colors' : '' }}">
                    @foreach($row as $key => $cell)
                        <td data-label="{{ $headers[$key] ?? '' }}" class="px-6 py-4 
                            {{ $loop->first ? 'font-medium text-gray-900' : '' }}">
                            {!! $cell !!}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-6 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p>Tidak ada data yang tersedia</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>