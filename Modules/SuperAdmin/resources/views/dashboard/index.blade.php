<x-superadmin::layouts.admin>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-500">Visao geral da plataforma - faturamento, crescimento e ocupacao</p>
        </div>

        {{-- KPI row --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="text-xs text-gray-500 font-semibold uppercase">MRR (receita mensal recorrente)</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($mrr, 2, ',', '.') }}</div>
                <div class="text-[11px] text-gray-400 mt-1">Soma dos planos ativos com cobranca configurada</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="text-xs text-gray-500 font-semibold uppercase">Empresas Ativas</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">{{ $activeTenants }}</div>
                <div class="text-[11px] text-gray-400 mt-1">{{ $totalTenants }} cadastradas no total</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="text-xs text-gray-500 font-semibold uppercase">Faturas em Aberto</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($pendingAmount + $overdueAmount, 2, ',', '.') }}</div>
                <div class="text-[11px] mt-1">
                    @if ($overdueAmount > 0)
                        <span class="text-rose-600 font-semibold">R$ {{ number_format($overdueAmount, 2, ',', '.') }} vencida</span>
                    @else
                        <span class="text-gray-400">Nenhuma vencida</span>
                    @endif
                </div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <div class="text-xs text-gray-500 font-semibold uppercase">Volume Total do Sistema</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">{{ \Modules\SuperAdmin\App\Support\DataVolumeCalculator::humanize($totalDbBytes + $totalStorageBytes) }}</div>
                <div class="text-[11px] text-gray-400 mt-1">Banco + arquivos armazenados</div>
            </div>
        </div>

        {{-- Charts: revenue and tenant growth --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h2 class="text-sm font-bold text-gray-700 uppercase mb-1">Faturamento Recebido</h2>
                <p class="text-xs text-gray-500 mb-3">Faturas pagas por mes, ultimos 6 meses</p>
                <x-superadmin::charts.monthly-bar :data="$revenueByMonth" color="#2a78d6" prefix="R$ " />
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                <h2 class="text-sm font-bold text-gray-700 uppercase mb-1">Novas Empresas</h2>
                <p class="text-xs text-gray-500 mb-3">Cadastros por mes, ultimos 6 meses</p>
                <x-superadmin::charts.monthly-bar :data="$tenantsByMonth" color="#eb6834" />
            </div>
        </div>

        {{-- Invoice status breakdown --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <h2 class="text-sm font-bold text-gray-700 uppercase mb-1">Status das Faturas</h2>
            <p class="text-xs text-gray-500 mb-4">Valor total faturado, por situacao (todo o historico)</p>

            @php
                $totalInvoiced = $paidAmount + $pendingAmount + $overdueAmount;
            @endphp

            @if ($totalInvoiced <= 0)
                <p class="text-sm text-gray-400 italic py-6 text-center">Nenhuma fatura registrada ainda.</p>
            @else
                @php
                    $paidPct = max(($paidAmount / $totalInvoiced) * 100, $paidAmount > 0 ? 1 : 0);
                    $pendingPct = max(($pendingAmount / $totalInvoiced) * 100, $pendingAmount > 0 ? 1 : 0);
                    $overduePct = max(($overdueAmount / $totalInvoiced) * 100, $overdueAmount > 0 ? 1 : 0);
                @endphp
                <div class="flex h-9 w-full rounded-lg overflow-hidden gap-[2px] bg-gray-100">
                    @if ($paidAmount > 0)
                        <div style="width: {{ $paidPct }}%; background-color: #0ca30c;" title="Pagas: R$ {{ number_format($paidAmount, 2, ',', '.') }}"></div>
                    @endif
                    @if ($pendingAmount > 0)
                        <div style="width: {{ $pendingPct }}%; background-color: #fab219;" title="Pendentes: R$ {{ number_format($pendingAmount, 2, ',', '.') }}"></div>
                    @endif
                    @if ($overdueAmount > 0)
                        <div style="width: {{ $overduePct }}%; background-color: #d03b3b;" title="Vencidas: R$ {{ number_format($overdueAmount, 2, ',', '.') }}"></div>
                    @endif
                </div>

                <div class="flex flex-wrap gap-4 mt-4 text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color:#0ca30c"></span>
                        <span class="text-gray-600">Pagas:</span>
                        <span class="font-bold text-gray-900">R$ {{ number_format($paidAmount, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color:#fab219"></span>
                        <span class="text-gray-600">Pendentes:</span>
                        <span class="font-bold text-gray-900">R$ {{ number_format($pendingAmount, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color:#d03b3b"></span>
                        <span class="text-gray-600">Vencidas:</span>
                        <span class="font-bold text-gray-900">R$ {{ number_format($overdueAmount, 2, ',', '.') }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-superadmin::layouts.admin>
