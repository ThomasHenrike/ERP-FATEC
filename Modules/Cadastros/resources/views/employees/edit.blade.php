<x-core::layouts.master>
    <div class="p-6 max-w-4xl mx-auto space-y-6">
        <div>
            <a href="{{ route('cadastros.funcionarios.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 uppercase">&larr; Voltar para Funcionarios</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Editar Colaborador: {{ $employee->name }}</h1>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <form action="{{ route('cadastros.funcionarios.update', $employee) }}" method="POST">
                @csrf
                @method('PUT')
                @include('cadastros::employees._form')
            </form>
        </div>
    </div>
</x-core::layouts.master>