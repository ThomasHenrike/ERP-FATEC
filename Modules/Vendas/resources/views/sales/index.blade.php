<x-core::layouts.master>
    <div class="p-6 max-w-7xl mx-auto space-y-6" x-data="{ openCancelModal: false, selectedSaleId: null, selectedSaleCode: null }">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Historico de Vendas</h1>
                <p class="text-sm text-gray-500">Consulta de cupons emitidos, vendedores responsaveis e cancelamento com estorno no Kardex</p>
            </div>
            <a href="{{ route('vendas.pos.index') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition shadow-sm">
                + Nova Venda (PDV)
            </a>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Cards de Faturamento --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase text-gray-500">Total Faturado Liquido</span>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1">R$ {{ number_format($totalCompleted, 2, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center font-bold">R$</div>
            </div>

            <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase text-rose-600">Total de Vendas Canceladas / Estornadas</span>
                    <p class="text-2xl font-extrabold text-rose-600 mt-1">R$ {{ number_format($totalCanceled, 2, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-rose-50 text-rose-600 rounded-lg flex items-center justify-center font-bold">X</div>
            </div>
        </div>

        {{-- Filtros de Busca --}}
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
            <form method="GET" action="{{ route('vendas.sales.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div>
                    <input type="text" name="code" value="{{ request('code') }}" placeholder="Buscar por #Numero..."
                           class="w-full rounded-lg border-gray-300 text-xs font-bold py-2">
                </div>

                <div class="relative"
                     x-data="{ customerId: '{{ request('customer_id') }}', customers: {{ Js::from($customers) }} }"
                     @customer-selected="customerId = $event.detail.id">
                    <input type="hidden" name="customer_id" :value="customerId">
                    <template x-if="!customerId">
                        <x-core::customer-search :customers="$customers" placeholder="Filtrar por cliente..."
                            input-class="w-full rounded-lg border-gray-300 text-xs py-2" />
                    </template>
                    <template x-if="customerId">
                        <div class="flex items-center justify-between gap-1.5 px-2.5 py-2 bg-indigo-50 border border-indigo-200 rounded-lg text-xs font-bold text-indigo-700">
                            <span class="truncate" x-text="customers.find(c => c.id == customerId)?.name || ''"></span>
                            <button type="button" @click="customerId = ''" class="text-indigo-400 hover:text-indigo-700 font-bold flex-shrink-0">&times;</button>
                        </div>
                    </template>
                </div>

                <div>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-xs py-2">
                        <option value="">Todos os Status</option>
                        <option value="completed" @selected(request('status') === 'completed')>Concluida</option>
                        <option value="canceled" @selected(request('status') === 'canceled')>Cancelada / Estornada</option>
                    </select>
                </div>

                <div>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full rounded-lg border-gray-300 text-xs py-2">
                </div>

                <div class="flex gap-2">
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full rounded-lg border-gray-300 text-xs py-2">
                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-xs font-bold rounded-lg hover:bg-gray-800">Filtrar</button>
                </div>
            </form>
        </div>

        {{-- Tabela de Vendas com Navegacao Segura --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Venda</th>
                            <th class="px-6 py-4">Data / Hora</th>
                            <th class="px-6 py-4">Cliente</th>
                            <th class="px-6 py-4">Atendimento</th>
                            <th class="px-6 py-4">Itens</th>
                            <th class="px-6 py-4">Total Liquido</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($sales as $sale)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600">
                                    #{{ $sale->code }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 font-mono">
                                    {{ $sale->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $sale->customer?->name ?? 'Consumidor Final' }}</div>
                                    <div class="text-[11px] text-gray-400 font-mono">{{ $sale->customer?->document ?? 'Sem documento' }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <div class="font-bold text-indigo-950">Vendedor: {{ $sale->seller?->name ?? 'Venda Direta' }}</div>
                                    <div class="text-[11px] text-gray-400">Caixa: {{ $sale->user?->name ?? 'Sistema' }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    <span class="font-bold">{{ $sale->items->count() }} itens</span>
                                    <div class="text-[11px] text-gray-400 truncate max-w-xs">{{ $sale->items->pluck('product.name')->filter()->join(', ') }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-600">
                                    R$ {{ number_format((float) $sale->total_amount, 2, ',', '.') }}
                                    @if ((float) $sale->discount_amount > 0)
                                        <div class="text-[10px] text-rose-500">Desc: - R$ {{ number_format((float) $sale->discount_amount, 2, ',', '.') }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($sale->status === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Concluida</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">Cancelada</span>
                                        @if ($sale->cancel_reason)
                                            <div class="text-[10px] text-rose-600 italic mt-0.5">{{ $sale->cancel_reason }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="{{ route('vendas.pos.receipt', $sale) }}" target="_blank" 
                                       class="px-2.5 py-1 bg-white border border-gray-300 rounded text-xs font-bold text-gray-700 hover:bg-gray-50">
                                        Reimprimir
                                    </a>
                                    @if ($sale->status === 'completed')
                                        @if ($isManagerOrAbove ?? false)
                                            <form action="{{ route('fiscal.documents.emit', $sale) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 bg-indigo-50 border border-indigo-200 rounded text-xs font-bold text-indigo-700 hover:bg-indigo-100">
                                                    Emitir NF-e
                                                </button>
                                            </form>
                                        @endif
                                        <button type="button" @click="selectedSaleId = {{ $sale->id }}; selectedSaleCode = '{{ $sale->code }}'; openCancelModal = true"
                                                class="px-2.5 py-1 bg-rose-50 border border-rose-200 rounded text-xs font-bold text-rose-700 hover:bg-rose-100">
                                            Cancelar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-400 italic">Nenhuma venda localizada com os filtros informados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t">
                {{ $sales->links() }}
            </div>
        </div>

        {{-- Modal de Cancelamento --}}
        <div x-show="openCancelModal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4" style="display: none;">
            <div @click.away="openCancelModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex justify-between items-center border-b pb-2">
                    <h3 class="text-base font-bold text-rose-700" x-text="'Cancelar Venda #' + selectedSaleCode"></h3>
                    <button type="button" @click="openCancelModal = false" class="text-gray-400 font-bold text-xl">&times;</button>
                </div>
                <p class="text-xs text-gray-600">
                    O cancelamento devolvera todas as quantidades de produtos automaticamente ao estoque no Kardex e cancelara as parcelas do crediario geradas.
                </p>
                <form :action="'/vendas/historico/' + selectedSaleId + '/cancelar'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Motivo do Cancelamento *</label>
                        <textarea name="cancel_reason" rows="3" required placeholder="Informe a justificativa do cancelamento / devolucao..."
                                  class="w-full rounded-lg border-gray-300 text-xs shadow-sm focus:ring-rose-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" @click="openCancelModal = false" class="px-3 py-1.5 bg-gray-100 rounded text-xs font-bold text-gray-700">Voltar</button>
                        <button type="submit" class="px-4 py-1.5 bg-rose-600 text-white rounded text-xs font-bold hover:bg-rose-700">Confirmar Estorno</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-core::layouts.master>