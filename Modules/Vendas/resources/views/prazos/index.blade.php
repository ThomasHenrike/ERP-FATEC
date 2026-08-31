<x-core::layouts.master>
    <div class="p-6 max-w-7xl mx-auto space-y-6" x-data="{ expandedSale: null, selectedReceivables: [] }">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Controle de Crediario & Vendas a Prazo</h1>
                <p class="text-sm text-gray-500">Gestao de faturas por cliente, detalhamento de itens e preparacao para emissao de NF-e</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('vendas.pos.index') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition shadow-sm">
                    Ir para o PDV
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Cards de Resumo Financeiro --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase text-gray-500">Total a Receber em Aberto</span>
                    <p class="text-2xl font-extrabold text-indigo-600 mt-1">R$ {{ number_format($totalPending, 2, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $totalCustomersInDebt }} clientes com faturas ativas</p>
                </div>
                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center font-bold text-lg">R$</div>
            </div>

            <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase text-rose-600">Total Vencido / Inadimplente</span>
                    <p class="text-2xl font-extrabold text-rose-600 mt-1">R$ {{ number_format($totalOverdue, 2, ',', '.') }}</p>
                    <p class="text-[11px] text-rose-500 font-semibold mt-0.5">Titulos expirados</p>
                </div>
                <div class="w-10 h-10 bg-rose-50 text-rose-600 rounded-lg flex items-center justify-center font-bold text-lg">!</div>
            </div>

            <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase text-gray-500">Acoes em Lote</span>
                    <p class="text-sm font-bold text-gray-700 mt-1">Faturamento Fiscal</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Selecione faturas para emitir NF-e</p>
                </div>
                <button type="button" @click="if(selectedReceivables.length > 0) alert('Preparado para emissao em lote de ' + selectedReceivables.length + ' vendas junto ao modulo Fiscal SEFAZ.')" 
                        :disabled="selectedReceivables.length === 0"
                        :class="selectedReceivables.length > 0 ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                        class="px-3 py-2 rounded-lg text-xs font-bold transition">
                    Emitir NF-e Selecionadas (<span x-text="selectedReceivables.length"></span>)
                </button>
            </div>
        </div>

        {{-- Filtros de Busca --}}
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
            <form method="GET" action="{{ route('vendas.prazos.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-2 relative"
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
                        <option value="pending" @selected(request('status') === 'pending')>Em Aberto</option>
                        <option value="paid" @selected(request('status') === 'paid')>Quitados</option>
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

        {{-- Tabela de Crediario com Detalhamento de Itens por Venda --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 w-10 text-center">
                                <input type="checkbox" @click="selectedReceivables = selectedReceivables.length ? [] : {{ json_encode($receivables->pluck('id')) }}" class="rounded border-gray-300 text-indigo-600">
                            </th>
                            <th class="px-4 py-3">Cliente / Contato</th>
                            <th class="px-4 py-3">Venda Origem</th>
                            <th class="px-4 py-3">Parcela</th>
                            <th class="px-4 py-3">Vencimento</th>
                            <th class="px-4 py-3">Valor Fatura</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Acoes Comerciais & Fiscais</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @forelse ($receivables as $r)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" value="{{ $r->id }}" x-model="selectedReceivables" class="rounded border-gray-300 text-indigo-600">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-gray-900">{{ $r->customer?->name ?? 'Cliente Nao Identificado' }}</div>
                                    <div class="text-[11px] text-gray-400">CPF/CNPJ: {{ $r->customer?->document ?? 'Nao informado' }} &bull; {{ $r->customer?->phone ?? $r->customer?->whatsapp ?? 'Sem contato' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <button type="button" @click="expandedSale = (expandedSale === {{ $r->sale_id }}) ? null : {{ $r->sale_id }}" 
                                            class="inline-flex items-center gap-1 font-mono font-bold text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-2 py-0.5 rounded text-xs">
                                        <span>#{{ $r->sale->code ?? $r->sale_id }}</span>
                                        <span class="text-[10px]" x-text="expandedSale === {{ $r->sale_id }} ? '▲' : '▼'"></span>
                                    </button>
                                </td>
                                <td class="px-4 py-3 font-semibold text-xs text-gray-700">
                                    {{ $r->installment_number }} / {{ $r->total_installments }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-700">
                                    {{ $r->due_date ? $r->due_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-gray-900">
                                    R$ {{ number_format((float) $r->amount, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($r->status === 'paid')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800">Quitado</span>
                                    @elseif ($r->due_date && $r->due_date->isPast())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800">Vencido</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">Em Aberto</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-1.5">
                                    <form action="{{ route('fiscal.documents.emit', $r->sale_id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="Emitir Nota Fiscal Eletronica da Venda"
                                                class="px-2.5 py-1 bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-50 rounded text-xs font-bold transition shadow-sm">
                                            Emitir NF-e
                                        </button>
                                    </form>

                                    @if ($r->status !== 'paid')
                                        <form action="{{ route('vendas.prazos.settle', $r) }}" method="POST" class="inline" onsubmit="return confirm('Confirmar o recebimento e quitacao desta fatura?')">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-bold transition shadow-sm">
                                                Quitar
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Pago em {{ $r->paid_at?->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                            </tr>

                            {{-- Linha Expansivel com os Itens Comprados na Venda --}}
                            <tr x-show="expandedSale === {{ $r->sale_id }}" class="bg-gray-50/80 border-b border-indigo-100" style="display: none;">
                                <td colspan="8" class="p-4">
                                    <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-inner space-y-2">
                                        <div class="flex justify-between items-center text-xs font-bold text-gray-700 border-b pb-2">
                                            <span>Itens Comprados na Venda #{{ $r->sale->code ?? $r->sale_id }} (Total: R$ {{ number_format((float) $r->sale->total_amount, 2, ',', '.') }})</span>
                                            <span class="text-gray-400 font-normal">Operador: {{ $r->sale->user->name ?? 'Caixa' }}</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                            @foreach ($r->sale->items as $item)
                                                <div class="flex justify-between p-2 bg-gray-50 rounded border">
                                                    <div>
                                                        <strong class="text-gray-900">{{ $item->product?->name ?? 'Produto #' . $item->product_id }}</strong>
                                                        <div class="text-[10px] text-gray-400">Cod: #{{ $item->product?->code ?? $item->product_id }} &bull; Qtd: {{ number_format((float) $item->quantity, 3, ',', '.') }} {{ $item->product?->unit ?? 'UN' }}</div>
                                                    </div>
                                                    <span class="font-mono font-bold text-gray-900">R$ {{ number_format((float) $item->total_price, 2, ',', '.') }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-400 italic">Nenhum titulo a prazo registrado com os filtros atuais.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t">
                {{ $receivables->links() }}
            </div>
        </div>
    </div>
</x-core::layouts.master>