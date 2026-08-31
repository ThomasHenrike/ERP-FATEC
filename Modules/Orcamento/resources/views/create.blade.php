<x-core::layouts.master>
    <div class="p-4 md:p-6 max-w-6xl mx-auto space-y-6">
        <div>
            <a href="{{ route('orcamentos.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 uppercase">&larr; Voltar para Orcamentos</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Novo Orcamento</h1>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <form action="{{ route('orcamentos.store') }}" method="POST">
                @csrf
                @include('orcamento::_form', ['orcamento' => null])
            </form>
        </div>
    </div>
</x-core::layouts.master>
