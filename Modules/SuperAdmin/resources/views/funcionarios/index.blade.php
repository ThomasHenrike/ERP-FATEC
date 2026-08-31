<x-superadmin::layouts.admin>
    <div class="p-6 max-w-5xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Funcionarios</h1>
                <p class="text-sm text-gray-500">Contas com acesso ao Admin Supremo</p>
            </div>
            <a href="{{ route('admin.funcionarios.create') }}" class="px-4 py-2 bg-amber-500 text-gray-900 rounded-lg text-sm font-semibold hover:bg-amber-600 transition shadow-sm">
                Novo Funcionario
            </a>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Nome</th>
                            <th class="px-6 py-4">Email</th>
                            <th class="px-6 py-4">Papel</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($admins as $admin)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ $admin->name }}</td>
                                <td class="px-6 py-4">{{ $admin->email }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold {{ $admin->role === 'owner' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700' }}">
                                        {{ \Modules\SuperAdmin\App\Support\AdminRole::label($admin->role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold {{ $admin->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $admin->is_active ? 'Ativo' : 'Desativado' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <a href="{{ route('admin.funcionarios.edit', $admin) }}" class="text-gray-600 hover:text-gray-900 font-semibold text-xs">Editar</a>
                                    @if ($admin->id !== auth('admin')->id())
                                        <form action="{{ route('admin.funcionarios.toggleActive', $admin) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="{{ $admin->is_active ? 'text-rose-600 hover:text-rose-800' : 'text-emerald-600 hover:text-emerald-800' }} font-semibold text-xs">
                                                {{ $admin->is_active ? 'Desativar' : 'Ativar' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400">Nenhum funcionario cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-superadmin::layouts.admin>
