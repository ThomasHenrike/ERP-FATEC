<x-core::layouts.master>
    <div class="p-6 max-w-4xl mx-auto space-y-6">
        <div>
            <a href="{{ route('cadastros.clientes.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 uppercase">&larr; Voltar para Clientes</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Editar Cliente: {{ $customer->name }}</h1>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <form action="{{ route('cadastros.clientes.update', $customer) }}" method="POST">
                @csrf
                @method('PUT')
                @include('cadastros::customers._form')
            </form>
        </div>
    </div>
</x-core::layouts.master>