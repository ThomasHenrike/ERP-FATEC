<x-core::layouts.master>
    <div class="p-6 max-w-4xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Central de Ajuda</h1>
            <p class="text-sm text-gray-500">Tire suas dúvidas ou fale com o suporte</p>
        </div>

        <form action="{{ route('core.ajuda.index') }}" method="GET" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <input type="text" name="q" value="{{ $query }}" placeholder="Pesquisar na Central de Ajuda..."
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </form>

        @if ($activeTicket)
            <a href="{{ route('core.suporte.index') }}" class="block bg-amber-50 border border-amber-200 rounded-xl p-4 hover:bg-amber-100 transition">
                <p class="text-sm font-bold text-amber-800">Você tem um chamado em andamento</p>
                <p class="text-xs text-amber-700 mt-1">Chamado #{{ $activeTicket->id }} — clique para ver a conversa &rarr;</p>
            </a>
        @else
            <a href="{{ route('core.suporte.index') }}" class="block bg-brand-soft border border-brand rounded-xl p-4 hover:opacity-90 transition">
                <p class="text-sm font-bold text-brand">Não encontrou o que precisava?</p>
                <p class="text-xs text-gray-600 mt-1">Abra um chamado e fale com nossa equipe de suporte &rarr;</p>
            </a>
        @endif

        @forelse ($articles as $category => $categoryArticles)
            <div class="space-y-2">
                <h2 class="text-sm font-bold text-gray-700 uppercase">{{ $category }}</h2>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">
                    @foreach ($categoryArticles->sortBy('order') as $article)
                        <a href="{{ route('core.ajuda.show', $article['slug']) }}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand transition">
                            {{ $article['title'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-center text-gray-400 text-sm">
                Nenhum artigo encontrado para "{{ $query }}".
            </div>
        @endforelse
    </div>
</x-core::layouts.master>
