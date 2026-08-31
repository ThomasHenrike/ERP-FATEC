@props(['href', 'active' => false, 'mobile' => false])

@if ($mobile)
    <a href="{{ $href }}" class="block px-3 py-2 rounded-md text-sm font-medium {{ $active ? 'bg-brand-soft text-brand font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
        {{ $slot }}
    </a>
@else
    <a href="{{ $href }}" class="block px-4 py-2 text-sm {{ $active ? 'bg-brand-soft text-brand font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
        {{ $slot }}
    </a>
@endif
