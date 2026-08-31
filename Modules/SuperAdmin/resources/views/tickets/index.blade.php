<x-superadmin::layouts.admin>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Chamados</h1>
            <p class="text-sm text-gray-500">Suporte de todas as empresas da plataforma</p>
        </div>

        <div class="flex gap-2 text-xs font-bold">
            <a href="{{ route('admin.tickets.index') }}"
               class="px-3 py-1.5 rounded-full transition {{ $status === '' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Todos
            </a>
            <a href="{{ route('admin.tickets.index', ['status' => 'open']) }}"
               class="px-3 py-1.5 rounded-full transition {{ $status === 'open' ? 'bg-amber-500 text-gray-900' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Abertos
            </a>
            <a href="{{ route('admin.tickets.index', ['status' => 'closed']) }}"
               class="px-3 py-1.5 rounded-full transition {{ $status === 'closed' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Fechados
            </a>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Empresa</th>
                            <th class="px-6 py-4">Assunto</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Ultima mensagem</th>
                            <th class="px-6 py-4 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($tickets as $ticket)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-bold text-gray-900">
                                    {{ $ticket->tenant->trade_name ?: $ticket->tenant->name }}
                                </td>
                                <td class="px-6 py-4">
                                    #{{ $ticket->id }} - {{ $ticket->subject }}
                                    @if ($ticket->needsReply())
                                        <span class="inline-flex ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700">Aguardando resposta</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold {{ $ticket->status === 'open' ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $ticket->status === 'open' ? 'Aberto' : 'Fechado' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $ticket->last_message_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-gray-600 hover:text-gray-900 font-semibold text-xs">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400">Nenhum chamado encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-100">
                {{ $tickets->links() }}
            </div>
        </div>
    </div>
</x-superadmin::layouts.admin>
