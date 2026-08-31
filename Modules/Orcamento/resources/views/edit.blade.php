<x-core::layouts.master>
    <div class="p-4 md:p-6 max-w-6xl mx-auto space-y-6">
        <div>
            <a href="{{ route('orcamentos.show', $orcamento) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 uppercase">&larr; Voltar para o Orcamento #{{ $orcamento->code }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Editar Orcamento #{{ $orcamento->code }}</h1>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <form action="{{ route('orcamentos.update', $orcamento) }}" method="POST">
                @csrf
                @method('PUT')
                @include('orcamento::_form')
            </form>
        </div>
    </div>
</x-core::layouts.master>
