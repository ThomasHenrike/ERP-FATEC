<x-superadmin::layouts.admin>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Empresas</h1>
                <p class="text-sm text-gray-500">Todas as empresas cadastradas na plataforma</p>
            </div>
            <a href="{{ route('admin.companies.create') }}" class="px-4 py-2 bg-amber-500 text-gray-900 rounded-lg text-sm font-semibold hover:bg-amber-600 transition shadow-sm">
                + Nova Empresa
            </a>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <h2 class="text-xs font-bold text-gray-500 uppercase mb-3">Volume Total do Sistema (todas as empresas) - planejamento de hospedagem</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-center">
                    <div class="text-xl font-extrabold text-gray-900">{{ \Modules\SuperAdmin\App\Support\DataVolumeCalculator::humanize($totalDbBytes) }}</div>
                    <div class="text-xs text-gray-500">Banco de Dados</div>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-center">
                    <div class="text-xl font-extrabold text-gray-900">{{ \Modules\SuperAdmin\App\Support\DataVolumeCalculator::humanize($totalStorageBytes) }}</div>
                    <div class="text-xs text-gray-500">Arquivos (logos, certificados, etc)</div>
                </div>
                <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-center">
                    <div class="text-xl font-extrabold text-amber-800">{{ \Modules\SuperAdmin\App\Support\DataVolumeCalculator::humanize($totalDbBytes + $totalStorageBytes) }}</div>
                    <div class="text-xs text-amber-700">Total Ocupado</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Empresa</th>
                            <th class="px-6 py-4">Modulos</th>
                            <th class="px-6 py-4">Faturas</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @forelse ($tenants as $tenant)
                            @php
                                $counts = $invoiceCounts->get($tenant->id, collect());
                                $pending = $counts->firstWhere('status', 'pending')?->total ?? 0;
                                $overdue = $counts->firstWhere('status', 'overdue')?->total ?? 0;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $tenant->trade_name ?: $tenant->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $tenant->document ?: 'Sem documento' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($toggleableModules as $module)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $tenant->hasModule($module) ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-400' }}">
                                                {{ ucfirst($module) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    @if ($overdue > 0)
                                        <span class="text-rose-600 font-bold">{{ $overdue }} atrasada(s)</span>
                                    @endif
                                    @if ($pending > 0)
                                        <div class="text-gray-500">{{ $pending }} pendente(s)</div>
                                    @endif
                                    @if ($overdue === 0 && $pending === 0)
                                        <span class="text-gray-400">Sem faturas</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $tenant->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $tenant->is_active ? 'Ativa' : 'Inativa' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.companies.edit', $tenant) }}" class="text-amber-600 hover:text-amber-800 font-semibold">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400">Nenhuma empresa cadastrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-superadmin::layouts.admin>
