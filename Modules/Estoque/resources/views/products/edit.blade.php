<x-core::layouts.master>
    <div class="p-6 max-w-5xl mx-auto space-y-6">
        <div>
            <a href="{{ route('estoque.produtos.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 uppercase tracking-wider">
                &larr; Voltar para o Estoque
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Editar Produto: {{ $product->name }}</h1>
            <p class="text-sm text-gray-500">Atualize os parametros de venda e cadastro fiscal do item</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <form action="{{ route('estoque.produtos.update', $product) }}" method="POST">
                @csrf
                @method('PUT')
                @include('estoque::products._form')
            </form>
        </div>
    </div>
</x-core::layouts.master>