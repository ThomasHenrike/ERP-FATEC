@props(['label', 'active' => false])

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button @click="open = !open"
            class="inline-flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium transition {{ $active ? 'bg-merp-superficie text-merp-verde-300 shadow-[inset_0_-2px_0_#3FBF87]' : 'text-gray-300 hover:text-merp-nevoa hover:bg-merp-superficie' }}">
        {{ $label }}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <div x-show="open" x-cloak x-transition
         class="absolute left-0 mt-1 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-40">
        {{ $slot }}
    </div>
</div>
