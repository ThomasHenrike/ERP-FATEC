<x-core::layouts.master>
    <div class="p-4 md:p-6 max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Orcamentos</h1>
                <p class="text-sm text-gray-500">Propostas comerciais, acompanhamento e conversao em venda</p>
            </div>
            <a href="{{ route('orcamentos.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">
                + Novo Orcamento
            </a>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm rounded-r-lg shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm space-y-4">
            <div class="flex flex-wrap gap-2 text-xs font-bold">
                @php
                    $statusFilters = [
                        '' => 'Todos',
                        'draft' => 'Rascunho',
                        'sent' => 'Enviado',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        'converted' => 'Convertido',
                    ];
                @endphp
                @foreach ($statusFilters as $value => $label)
                    <a href="{{ route('orcamentos.index', array_filter(['status' => $value, 'search' => request('search')])) }}"
                       class="px-3 py-1.5 rounded-full transition {{ request('status', '') === $value ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $label }} ({{ $value === '' ? $statusCounts->sum() : ($statusCounts[$value] ?? 0) }})
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('orcamentos.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <div class="md:col-span-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por numero do orcamento ou cliente..."
                           class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-bold hover:bg-gray-800">Filtrar</button>
            </form>
        </div>

        @php
            $statusBadgeClass = fn ($status) => match ($status) {
                'sent' => 'bg-blue-50 text-blue-700',
                'approved' => 'bg-emerald-50 text-emerald-700',
                'rejected' => 'bg-rose-50 text-rose-700',
                'converted' => 'bg-indigo-50 text-indigo-700',
                'expired' => 'bg-amber-50 text-amber-700',
                default => 'bg-gray-100 text-gray-600',
            };
        @endphp

        {{-- Lista em Cards (Mobile) --}}
        <div class="md:hidden space-y-3">
            @forelse ($orcamentos as $o)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono font-bold text-indigo-700">#{{ $o->code }}</span>
                            <div class="font-bold text-gray-900">{{ $o->customer->name ?? 'Cliente removido' }}</div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase {{ $statusBadgeClass($o->status) }}">
                            {{ $o->status_label }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-mono font-bold text-emerald-600">R$ {{ number_format((float) $o->total_amount, 2, ',', '.') }}</span>
                        <span class="text-xs text-gray-500">{{ $o->valid_until?->format('d/m/Y') ?? 'Sem validade' }}</span>
                    </div>
                    <div class="flex gap-3 pt-2 border-t text-xs font-bold">
                        <a href="{{ route('orcamentos.show', $o) }}" class="text-indigo-600">Ver</a>
                        @if ($o->isEditable())
                            <a href="{{ route('orcamentos.edit', $o) }}" class="text-gray-600">Editar</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center text-gray-400 italic text-sm">
                    Nenhum orcamento encontrado.
                </div>
            @endforelse
            <div class="pt-2">{{ $orcamentos->links() }}</div>
        </div>

        {{-- Tabela (Desktop) --}}
        <div class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Numero</th>
                            <th class="px-6 py-4">Cliente</th>
                            <th class="px-6 py-4">Valor Total</th>
                            <th class="px-6 py-4">Validade</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @forelse ($orcamentos as $o)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono font-bold text-indigo-700">#{{ $o->code }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $o->customer->name ?? 'Cliente removido' }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-600">
                                    R$ {{ number_format((float) $o->total_amount, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    {{ $o->valid_until?->format('d/m/Y') ?? 'Sem validade definida' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $statusBadgeClass($o->status) }}">
                                        {{ $o->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="{{ route('orcamentos.show', $o) }}" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs">Ver</a>
                                    @if ($o->isEditable())
                                        <a href="{{ route('orcamentos.edit', $o) }}" class="text-gray-600 hover:text-gray-900 font-bold text-xs ml-2">Editar</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">Nenhum orcamento encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t">
                {{ $orcamentos->links() }}
            </div>
        </div>
    </div>
</x-core::layouts.master>
