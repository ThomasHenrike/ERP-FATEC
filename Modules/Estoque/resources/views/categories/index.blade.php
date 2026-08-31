<x-core::layouts.master>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Categorias & Subcategorias</h1>
                <p class="text-sm text-gray-500">Classificacao hierarquica do catalogo de mercadorias e departamentos</p>
            </div>
            <a href="{{ route('estoque.categorias.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">
                + Nova Categoria
            </a>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Categoria / Departamento</th>
                            <th class="px-6 py-4">Estrutura Hierarquica</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @forelse ($categories as $cat)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $cat->name }}</div>
                                    <div class="text-[11px] text-gray-400 font-mono">{{ $cat->slug }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    @if ($cat->parent)
                                        <span class="text-gray-500">{{ $cat->parent->name }} &rarr;</span>
                                        <span class="font-bold text-indigo-600">Subcategoria</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 font-bold rounded">Categoria Principal</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($cat->is_active)
                                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded">Ativa</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs font-bold rounded">Inativa</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="{{ route('estoque.categorias.edit', $cat) }}" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs">Editar</a>
                                    <form action="{{ route('estoque.categorias.destroy', $cat) }}" method="POST" class="inline" onsubmit="return confirm('Deseja excluir esta categoria?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold text-xs ml-2">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">Nenhuma categoria cadastrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t">
                {{ $categories->links() }}
            </div>
        </div>
    </div>
</x-core::layouts.master>