<x-superadmin::layouts.admin>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Faturas</h1>
            <p class="text-sm text-gray-500">Cobranca de todas as empresas da plataforma</p>
        </div>

        <div class="flex gap-2 text-xs font-bold">
            <a href="{{ route('admin.invoices.index') }}"
               class="px-3 py-1.5 rounded-full transition {{ $status === '' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Todas
            </a>
            <a href="{{ route('admin.invoices.index', ['status' => 'pending']) }}"
               class="px-3 py-1.5 rounded-full transition {{ $status === 'pending' ? 'bg-amber-500 text-gray-900' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Pendentes
            </a>
            <a href="{{ route('admin.invoices.index', ['status' => 'overdue']) }}"
               class="px-3 py-1.5 rounded-full transition {{ $status === 'overdue' ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Atrasadas
            </a>
            <a href="{{ route('admin.invoices.index', ['status' => 'paid']) }}"
               class="px-3 py-1.5 rounded-full transition {{ $status === 'paid' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Pagas
            </a>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Empresa</th>
                            <th class="px-6 py-4">Referencia</th>
                            <th class="px-6 py-4">Valor</th>
                            <th class="px-6 py-4">Vencimento</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($invoices as $invoice)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-bold text-gray-900">
                                    <a href="{{ route('admin.companies.edit', $invoice->tenant) }}" class="hover:text-amber-600">
                                        {{ $invoice->tenant->trade_name ?: $invoice->tenant->name }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">{{ $invoice->reference ?: '-' }}</td>
                                <td class="px-6 py-4 font-mono">R$ {{ number_format((float) $invoice->amount, 2, ',', '.') }}</td>
                                <td class="px-6 py-4">{{ $invoice->due_date->format('d/m/Y') }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusStyle = match ($invoice->status) {
                                            'paid' => 'bg-emerald-50 text-emerald-700',
                                            'overdue' => 'bg-rose-50 text-rose-700',
                                            'cancelled' => 'bg-gray-100 text-gray-500',
                                            default => 'bg-amber-50 text-amber-700',
                                        };
                                        $statusLabel = match ($invoice->status) {
                                            'paid' => 'Paga',
                                            'overdue' => 'Atrasada',
                                            'cancelled' => 'Cancelada',
                                            default => 'Pendente',
                                        };
                                    @endphp
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold {{ $statusStyle }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if (in_array($invoice->status, ['pending', 'overdue']))
                                        <form action="{{ route('admin.invoices.markPaid', $invoice) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-emerald-600 hover:text-emerald-800 font-semibold text-xs">Marcar como paga</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400">Nenhuma fatura encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-100">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</x-superadmin::layouts.admin>
