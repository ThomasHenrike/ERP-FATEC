@props(['href', 'active' => false, 'mobile' => false])

{{-- mobile: dentro da barra superior (fundo grafite). Sem mobile: dentro do painel claro do dropdown. --}}
@if ($mobile)
    <a href="{{ $href }}" class="block px-3 py-2 rounded-md text-sm font-medium {{ $active ? 'bg-merp-superficie text-merp-verde-300 shadow-[inset_2px_0_0_#3FBF87]' : 'text-gray-300 hover:text-merp-nevoa hover:bg-merp-superficie' }}">
        {{ $slot }}
    </a>
@else
    <a href="{{ $href }}" class="block px-4 py-2 text-sm {{ $active ? 'bg-brand-soft text-brand font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
        {{ $slot }}
    </a>
@endif
