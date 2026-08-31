<x-core::layouts.master>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('estoque.produtos.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 uppercase tracking-wider">
                    &larr; Voltar para Produtos
                </a>
                <h1 class="text-2xl font-bold text-gray-900 mt-1">Extrato do Livro-Razao (Kardex)</h1>
                <p class="text-sm text-gray-500">Historico cronologico e imutavel de entradas, saidas e perdas de estoque</p>
            </div>
            <div>
                <a href="{{ route('estoque.movimentacoes.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">
                    + Lancar Ajuste Manual
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Filtros do Extrato --}}
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
            <form method="GET" action="{{ route('estoque.movimentacoes.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="relative"
                     x-data="{ productId: '{{ request('product_id') }}', products: {{ Js::from($products) }} }"
                     @product-selected="productId = $event.detail.id">
                    <input type="hidden" name="product_id" :value="productId">
                    <template x-if="!productId">
                        <x-core::product-search :products="$products" placeholder="Filtrar por produto..." />
                    </template>
                    <template x-if="productId">
                        <div class="flex items-center justify-between gap-1.5 px-2.5 py-2 bg-indigo-50 border border-indigo-200 rounded-lg text-sm font-bold text-indigo-700">
                            <span class="truncate" x-text="products.find(p => p.id == productId)?.name || ''"></span>
                            <button type="button" @click="productId = ''" class="text-indigo-400 hover:text-indigo-700 font-bold flex-shrink-0">&times;</button>
                        </div>
                    </template>
                </div>

                <div>
                    <select name="type" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Todos os Tipos</option>
                        <option value="in" @selected(request('type') == 'in')>Entrada / Compra</option>
                        <option value="out" @selected(request('type') == 'out')>Saida / Venda PDV</option>
                        <option value="adjustment" @selected(request('type') == 'adjustment')>Ajuste Manual</option>
                        <option value="loss" @selected(request('type') == 'loss')>Avaria / Perda</option>
                    </select>
                </div>

                <div>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full rounded-lg border-gray-300 text-sm">
                </div>

                <div class="flex items-center gap-2">
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full rounded-lg border-gray-300 text-sm">
                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        {{-- Tabela Cronologica do Kardex com Protecao Null-Safe --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Data / Hora</th>
                            <th class="px-6 py-4">Produto</th>
                            <th class="px-6 py-4">Operacao</th>
                            <th class="px-6 py-4">Qtd</th>
                            <th class="px-6 py-4">Saldo Anterior</th>
                            <th class="px-6 py-4">Saldo Resultante</th>
                            <th class="px-6 py-4">Operador / Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($movements as $m)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500 font-mono text-xs">
                                    {{ $m->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <div class="flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 bg-indigo-50 border border-indigo-200 text-indigo-700 font-mono font-extrabold text-xs rounded">
                                            #{{ $m->product?->code ?? $m->product_id }}
                                        </span>
                                        <span class="font-bold text-gray-900">{{ $m->product?->name ?? 'Produto #' . $m->product_id . ' (Desativado)' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($m->type === 'in')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            Entrada
                                        </span>
                                    @elseif ($m->type === 'out')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                            Saida PDV
                                        </span>
                                    @elseif ($m->type === 'loss')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                            Avaria / Perda
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            Ajuste Manual
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-gray-900">
                                    {{ in_array($m->type, ['out', 'loss']) ? '-' : '+' }}{{ number_format((float) $m->quantity, 3, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 font-mono text-gray-500">
                                    {{ number_format((float) $m->previous_stock, 3, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600">
                                    {{ number_format((float) $m->current_stock, 3, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    <div class="font-medium text-gray-800">{{ $m->user?->name ?? 'Sistema' }}</div>
                                    @if ($m->notes)
                                        <div class="text-gray-500 italic">{{ $m->notes }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    Nenhuma movimentacao registrada no periodo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200">
                {{ $movements->links() }}
            </div>
        </div>
    </div>
</x-core::layouts.master>