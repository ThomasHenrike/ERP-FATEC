<x-core::layouts.master>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Equipe & Funcionarios</h1>
                <p class="text-sm text-gray-500">Gestao de colaboradores, cargos operacionais e taxas de comissao de vendas</p>
            </div>
            <a href="{{ route('cadastros.funcionarios.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">
                + Novo Funcionario
            </a>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
            <form method="GET" action="{{ route('cadastros.funcionarios.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por Nome, CPF ou Telefone..."
                           class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <select name="role" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Todos os Cargos</option>
                        <option value="vendedor" @selected(request('role') === 'vendedor')>Vendedor</option>
                        <option value="caixa" @selected(request('role') === 'caixa')>Caixa</option>
                        <option value="gerente" @selected(request('role') === 'gerente')>Gerente</option>
                        <option value="ajudante" @selected(request('role') === 'ajudante')>Ajudante</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-bold hover:bg-gray-800">
                    Filtrar
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Colaborador</th>
                            <th class="px-6 py-4">Cargo / Funcao</th>
                            <th class="px-6 py-4">Comissao (%)</th>
                            <th class="px-6 py-4">Contato</th>
                            <th class="px-6 py-4">Sistema</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @forelse ($employees as $emp)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $emp->name }}</div>
                                    <div class="text-xs text-gray-400">CPF: {{ $emp->document ?? 'Nao informado' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase
                                        {{ in_array($emp->role, ['vendedor', 'gerente']) ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $emp->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-600">
                                    {{ number_format((float) $emp->commission_percentage, 2, ',', '.') }}%
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    <div>{{ $emp->whatsapp ?? $emp->phone ?? 'Sem telefone' }}</div>
                                    <div class="text-gray-400">{{ $emp->email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($emp->hasSystemAccess())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-50 text-indigo-700">Tem login</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-gray-100 text-gray-500">Sem login</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($emp->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700">Ativo</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-gray-100 text-gray-600">Inativo</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="{{ route('cadastros.funcionarios.edit', $emp) }}" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs">Editar</a>
                                    <form action="{{ route('cadastros.funcionarios.destroy', $emp) }}" method="POST" class="inline" onsubmit="return confirm('Deseja desativar este funcionario?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold text-xs ml-2">Desativar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400 italic">Nenhum funcionario cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t">
                {{ $employees->links() }}
            </div>
        </div>
    </div>
</x-core::layouts.master>