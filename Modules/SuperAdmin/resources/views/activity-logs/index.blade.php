<x-superadmin::layouts.admin>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Log de Auditoria</h1>
            <p class="text-sm text-gray-500">Toda ação de qualquer funcionário no Admin Supremo, incluindo simulações de acesso ("entrar como empresa")</p>
        </div>

        <div class="flex gap-2 text-xs font-bold">
            <a href="{{ route('admin.activity-logs.index') }}"
               class="px-3 py-1.5 rounded-full transition {{ !$onlyImpersonation ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Todas as ações
            </a>
            <a href="{{ route('admin.activity-logs.index', ['impersonation' => 1]) }}"
               class="px-3 py-1.5 rounded-full transition {{ $onlyImpersonation ? 'bg-indigo-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Só simulações de acesso
            </a>
        </div>

        <form action="{{ route('admin.activity-logs.index') }}" method="GET" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            @if ($onlyImpersonation)
                <input type="hidden" name="impersonation" value="1">
            @endif
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nº do chamado</label>
                <input type="text" name="ticket" value="{{ $ticketId }}" placeholder="Ex: 42"
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Empresa</label>
                <input type="text" name="empresa" value="{{ $empresa }}" placeholder="Nome ou razão social..."
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-bold hover:bg-gray-800">Filtrar</button>
                @if ($ticketId !== '' || $empresa !== '')
                    <a href="{{ route('admin.activity-logs.index', $onlyImpersonation ? ['impersonation' => 1] : []) }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-800">Limpar</a>
                @endif
            </div>
        </form>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Quando</th>
                            <th class="px-6 py-4">Funcionário</th>
                            <th class="px-6 py-4">Ação</th>
                            <th class="px-6 py-4">Empresa</th>
                            <th class="px-6 py-4">Chamado</th>
                            <th class="px-6 py-4">Descrição</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-gray-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 font-bold text-gray-900">{{ $log->admin->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold {{ str_contains($log->action, 'imperson') ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ $log->tenant?->trade_name ?? $log->tenant?->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if ($log->ticket_id)
                                        <a href="{{ route('admin.tickets.show', $log->ticket_id) }}" class="text-amber-600 hover:underline font-semibold">#{{ $log->ticket_id }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400">Nenhum registro encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-100">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</x-superadmin::layouts.admin>
