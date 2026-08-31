<x-core::layouts.master>
    <div class="p-6 max-w-3xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <a href="{{ route('core.ajuda.index') }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; Central de Ajuda</a>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ $article['title'] }}</h1>
            <div class="prose prose-sm max-w-none">
                {!! $html !!}
            </div>
        </div>

        <a href="{{ route('core.suporte.index') }}" class="block bg-brand-soft border border-brand rounded-xl p-4 hover:opacity-90 transition">
            <p class="text-sm font-bold text-brand">Ainda com dúvida?</p>
            <p class="text-xs text-gray-600 mt-1">Abra um chamado e fale com nossa equipe de suporte &rarr;</p>
        </a>
    </div>
</x-core::layouts.master>
