<x-core::layouts.master>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        {{-- Cabecalho e Acoes Principais --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Controle de Estoque</h1>
                <p class="text-sm text-gray-500">Gerenciamento de catalogo, precificacao e niveis de inventario</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('estoque.categorias.index') }}" 
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 transition shadow-sm">
                    Categorias & Subcategorias
                </a>
                <a href="{{ route('estoque.categorias.create') }}" 
                   class="inline-flex items-center px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition shadow-sm">
                    + Nova Categoria
                </a>
                <a href="{{ route('estoque.movimentacoes.index') }}" 
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 transition shadow-sm">
                    Extrato Kardex
                </a>
                <a href="{{ route('estoque.produtos.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-xs font-extrabold text-white hover:bg-indigo-700 transition shadow-sm">
                    + Novo Produto
                </a>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Cards de KPI no Topo --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total no Catalogo</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $products->total() }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $activeCount ?? 0 }} ativos &bull; {{ $inactiveCount ?? 0 }} inativos</p>
                </div>
                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center font-bold">
                    IT
                </div>
            </div>

            <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Estoque Critico</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">
                        {{ $products->filter(fn($p) => $p->isLowStock())->count() }}
                    </p>
                    <p class="text-[11px] text-amber-600 font-medium mt-0.5">Abaixo do minimo</p>
                </div>
                <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center font-bold">
                    !
                </div>
            </div>

            <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-600">Itens Zerados</p>
                    <p class="text-2xl font-bold text-rose-600 mt-1">
                        {{ $products->filter(fn($p) => $p->isOutOfStock())->count() }}
                    </p>
                    <p class="text-[11px] text-rose-500 font-medium mt-0.5">Saldo fisico nulo</p>
                </div>
                <div class="w-10 h-10 bg-rose-50 text-rose-600 rounded-lg flex items-center justify-center font-bold">
                    0
                </div>
            </div>

            <a href="{{ route('estoque.categorias.index') }}" class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between hover:border-indigo-500 transition group">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 group-hover:text-indigo-600">Categorias Ativas &rarr;</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $categories->count() }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Departamentos ativos</p>
                </div>
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center font-bold">
                    CT
                </div>
            </a>
        </div>

        {{-- Filtros de Pesquisa com Suporte a Status e Codigo Simples --}}
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
            <form method="GET" action="{{ route('estoque.produtos.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-2">
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Buscar por Nome, Codigo simples (#1), SKU ou Codigo de Barras..." 
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <select name="category_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todas as Categorias</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-medium">
                        <option value="active" @selected(request('status', 'active') === 'active')>Apenas Ativos ({{ $activeCount ?? 0 }})</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Apenas Inativos ({{ $inactiveCount ?? 0 }})</option>
                        <option value="all" @selected(request('status') === 'all')>Todos os Status</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition">
                        Filtrar
                    </button>
                    @if (request()->hasAny(['search', 'category_id', 'status', 'low_stock']))
                        <a href="{{ route('estoque.produtos.index') }}" class="p-2 text-gray-500 hover:text-gray-700 text-xs font-semibold">
                            Limpar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabela de Produtos com Codigo Simples # e Acao de Reativacao --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Item / Identificacao</th>
                            <th class="px-6 py-4">Categoria</th>
                            <th class="px-6 py-4">Preco Varejo</th>
                            <th class="px-6 py-4">Saldo Atual</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @forelse ($products as $product)
                            <tr class="hover:bg-gray-50 transition {{ !$product->is_active ? 'bg-gray-50/60 opacity-80' : '' }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 bg-indigo-50 border border-indigo-200 text-indigo-700 font-mono font-extrabold rounded text-xs">
                                            #{{ $product->code ?? $product->id }}
                                        </span>
                                        <span class="font-bold text-gray-900">{{ $product->name }}</span>
                                    </div>
                                    <div class="text-xs text-gray-400 flex flex-wrap gap-2 mt-0.5">
                                        <span>SKU: {{ $product->sku ?? 'N/A' }}</span>
                                        @if ($product->barcode)
                                            <span>&bull; EAN: {{ $product->barcode }}</span>
                                        @endif
                                        @if ($product->brand)
                                            <span>&bull; Marca: {{ $product->brand }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $product->category?->name ?? 'Sem categoria' }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 font-mono">
                                    R$ {{ number_format((float) $product->price_retail, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $stock = (float) $product->current_stock;
                                        $min = (float) $product->min_stock;
                                    @endphp
                                    @if ($stock <= 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                            {{ number_format($stock, 3, ',', '.') }} {{ $product->unit }} (Zerado)
                                        </span>
                                    @elseif ($min > 0 && $stock <= $min)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            {{ number_format($stock, 3, ',', '.') }} {{ $product->unit }} (Critico)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            {{ number_format($stock, 3, ',', '.') }} {{ $product->unit }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($product->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700">
                                            Ativo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-700">
                                            Inativo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('estoque.produtos.edit', $product) }}" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs">
                                        Editar
                                    </a>
                                    @if ($product->is_active)
                                        <form action="{{ route('estoque.produtos.destroy', $product) }}" method="POST" class="inline-block" onsubmit="return confirm('Deseja realmente desativar este produto?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold text-xs ml-2">
                                                Desativar
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('estoque.produtos.reactivate', $product) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="text-emerald-600 hover:text-emerald-900 font-bold text-xs ml-2">
                                                Reativar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    Nenhum produto cadastrado ou encontrado com os filtros atuais.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</x-core::layouts.master>