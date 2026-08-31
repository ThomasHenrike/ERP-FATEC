<x-core::layouts.master>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Carteira de Clientes</h1>
                <p class="text-sm text-gray-500">Gestao de clientes, limites de credito para o crediario e tabelas de preco vinculadas</p>
            </div>
            <a href="{{ route('cadastros.clientes.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">
                + Novo Cliente
            </a>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm space-y-4">
            <div class="flex gap-2 text-xs font-bold">
                <a href="{{ route('cadastros.clientes.index', array_filter(['search' => request('search')])) }}"
                   class="px-3 py-1.5 rounded-full transition {{ request('status', 'active') === 'active' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Ativos ({{ $activeCount }})
                </a>
                <a href="{{ route('cadastros.clientes.index', array_filter(['status' => 'inactive', 'search' => request('search')])) }}"
                   class="px-3 py-1.5 rounded-full transition {{ request('status') === 'inactive' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Inativos ({{ $inactiveCount }})
                </a>
                <a href="{{ route('cadastros.clientes.index', array_filter(['status' => 'all', 'search' => request('search')])) }}"
                   class="px-3 py-1.5 rounded-full transition {{ request('status') === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Todos ({{ $activeCount + $inactiveCount }})
                </a>
            </div>

            <form method="GET" action="{{ route('cadastros.clientes.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <div class="md:col-span-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por Nome, CPF/CNPJ, Telefone ou Cidade..."
                           class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-bold hover:bg-gray-800">Filtrar</button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Cliente</th>
                            <th class="px-6 py-4">Documento</th>
                            <th class="px-6 py-4">Tabela Padrao</th>
                            <th class="px-6 py-4">Limite Credito</th>
                            <th class="px-6 py-4">Contato</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @forelse ($customers as $c)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $c->isJuridica() ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">
                                            {{ $c->isJuridica() ? 'PJ' : 'PF' }}
                                        </span>
                                        <span class="font-bold text-gray-900">{{ $c->name }}</span>
                                    </div>
                                    <div class="text-xs text-gray-400">{{ $c->city ? $c->city . '/' . $c->state : 'Sem endereco' }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-700">
                                    {{ $c->document ?: 'Nao informado' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-indigo-50 text-indigo-700">
                                        {{ $c->price_table_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-600">
                                    R$ {{ number_format((float) $c->credit_limit, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    <div>{{ $c->whatsapp ?? $c->phone ?? 'Sem telefone' }}</div>
                                    <div class="text-gray-400">{{ $c->email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($c->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-emerald-50 text-emerald-700">Ativo</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-gray-100 text-gray-500">Inativo</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="{{ route('cadastros.clientes.edit', $c) }}" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs">Editar</a>
                                    @if ($c->is_active)
                                        <form action="{{ route('cadastros.clientes.destroy', $c) }}" method="POST" class="inline" onsubmit="return confirm('Deseja desativar este cliente?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold text-xs ml-2">Desativar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">Nenhum cliente encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t">
                {{ $customers->links() }}
            </div>
        </div>
    </div>
</x-core::layouts.master>