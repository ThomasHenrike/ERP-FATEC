<x-core::layouts.master>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Notas Fiscais</h1>
            <p class="text-sm text-gray-500">NF-e emitidas a partir das vendas - geracao, assinatura e transmissao para a SEFAZ</p>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm rounded-r-lg shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Numero / Serie</th>
                            <th class="px-6 py-4">Venda</th>
                            <th class="px-6 py-4">Cliente</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Emitida em</th>
                            <th class="px-6 py-4 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium">
                        @forelse ($documents as $doc)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 font-mono">#{{ $doc->number }} / {{ $doc->series }}</div>
                                    <div class="text-xs text-gray-400">Modelo {{ $doc->model }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($doc->sale)
                                        <span class="font-mono">Venda #{{ $doc->sale->code }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">{{ $doc->customer?->name ?? 'Venda Direta' }}</td>
                                <td class="px-6 py-4">
                                    <x-fiscal::status-badge :status="$doc->status" />
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    {{ $doc->emitted_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    @if ($doc->isEditable())
                                        <a href="{{ route('fiscal.documents.edit', $doc) }}" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs">Editar e Emitir</a>
                                    @endif
                                    <a href="{{ route('fiscal.documents.show', $doc) }}" class="text-gray-600 hover:text-gray-900 font-bold text-xs">Ver detalhes</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">
                                    Nenhuma NF-e emitida ainda. A emissao fica disponivel no Historico de Vendas e no Crediario, em cada venda concluida.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t">
                {{ $documents->links() }}
            </div>
        </div>
    </div>
</x-core::layouts.master>
