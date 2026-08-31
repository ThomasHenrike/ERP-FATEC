<x-core::layouts.master>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Fornecedores & Fabricantes</h1>
                <p class="text-sm text-gray-500">Cadastro de parceiros comerciais para compras e vinculo automatico com XML de NF-e</p>
            </div>
            <a href="{{ route('cadastros.fornecedores.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">
                + Novo Fornecedor
            </a>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
            <form method="GET" action="{{ route('cadastros.fornecedores.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por Razao Social, CNPJ ou Contato..."
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
                            <th class="px-6 py-4">Fornecedor</th>
                            <th class="px-6 py-4">CNPJ</th>
                            <th class="px-6 py-4">Contato / Representante</th>
                            <th class="px-6 py-4">Telefone / WhatsApp</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @forelse ($suppliers as $s)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $s->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $s->trade_name ?: 'Razao Direta' }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-700">
                                    {{ $s->document ?: 'Nao informado' }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-800 font-semibold">
                                    {{ $s->contact_name ?: 'Central' }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    <div>{{ $s->whatsapp ?? $s->phone ?? 'Sem telefone' }}</div>
                                    <div class="text-gray-400">{{ $s->email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($s->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700">Ativo</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-gray-100 text-gray-600">Inativo</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="{{ route('cadastros.fornecedores.edit', $s) }}" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs">Editar</a>
                                    <form action="{{ route('cadastros.fornecedores.destroy', $s) }}" method="POST" class="inline" onsubmit="return confirm('Deseja desativar este fornecedor?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold text-xs ml-2">Desativar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">Nenhum fornecedor cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t">
                {{ $suppliers->links() }}
            </div>
        </div>
    </div>
</x-core::layouts.master>