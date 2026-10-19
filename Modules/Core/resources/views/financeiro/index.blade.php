<x-core::layouts.master>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Financeiro</h1>
            <p class="text-sm text-gray-500">Faturas e cobrancas da assinatura do seu plano no MERP</p>
        </div>

        @if ($nextDue && $nextDue->status === 'overdue')
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm rounded-r-lg shadow-sm">
                <span class="font-bold">Fatura vencida!</span>
                A fatura de {{ $nextDue->reference_period ?? $nextDue->reference }} (R$ {{ number_format((float) $nextDue->amount, 2, ',', '.') }})
                venceu em {{ $nextDue->due_date->format('d/m/Y') }}. Regularize o quanto antes para evitar suspensao do acesso.
            </div>
        @elseif ($nextDue && $nextDue->due_date->isBefore(now()->addDays(5)))
            @php
                // due_date e' uma data pura (sem hora - o cast 'date' zera a
                // hora ao gravar), entao compara data-com-data (today(), nao
                // now()) pra sempre dar um numero inteiro limpo de dias, sem
                // fracao pra arredondar.
                $daysUntilDue = today()->diffInDays($nextDue->due_date, false);
                $daysUntilDueLabel = $daysUntilDue < 1 ? 'menos de um dia' : $daysUntilDue . ' dia(s)';
            @endphp
            <div class="p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-800 text-sm rounded-r-lg shadow-sm">
                <span class="font-bold">Fatura proxima do vencimento.</span>
                A fatura de {{ $nextDue->reference_period ?? $nextDue->reference }} (R$ {{ number_format((float) $nextDue->amount, 2, ',', '.') }})
                vence em {{ $nextDue->due_date->format('d/m/Y') }} ({{ $daysUntilDueLabel }}).
            </div>
        @elseif (!$nextDue)
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                Nenhuma fatura pendente no momento. Tudo em dia.
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Referencia</th>
                            <th class="px-6 py-4">Vencimento</th>
                            <th class="px-6 py-4">Valor</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Pago em</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @forelse ($invoices as $invoice)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $invoice->reference_period ?? $invoice->reference ?? '-' }}</div>
                                    @if ($invoice->notes)
                                        <div class="text-xs text-gray-400">{{ $invoice->notes }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">{{ $invoice->due_date->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 font-mono font-bold text-gray-800">
                                    R$ {{ number_format((float) $invoice->amount, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4">
                                    @if ($invoice->status === 'paid')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-emerald-100 text-emerald-800">Paga</span>
                                    @elseif ($invoice->status === 'overdue')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-rose-100 text-rose-800">Vencida</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-amber-100 text-amber-800">Pendente</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    {{ $invoice->paid_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">Nenhuma fatura registrada ainda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</x-core::layouts.master>
