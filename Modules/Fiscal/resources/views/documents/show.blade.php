<x-core::layouts.master>
    <div class="p-6 max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 font-mono">NF-e #{{ $fiscalDocument->number }} / Serie {{ $fiscalDocument->series }}</h1>
                <p class="text-sm text-gray-500">Modelo {{ $fiscalDocument->model }} - ambiente {{ $fiscalDocument->environment === 'production' ? 'Producao' : 'Homologacao' }}</p>
            </div>
            <x-fiscal::status-badge :status="$fiscalDocument->status" />
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

        @if ($fiscalDocument->status === 'error' || $fiscalDocument->status === 'rejected')
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                <span class="font-bold">Motivo:</span> {{ $fiscalDocument->rejection_reason ?? 'Sem detalhes.' }}
                @if ($fiscalDocument->status === 'error' && str_contains(strtolower($fiscalDocument->rejection_reason ?? ''), 'certificado'))
                    <p class="mt-2 text-xs text-amber-700">
                        Isso e' esperado enquanto a empresa nao tiver um certificado digital A1 valido (ICP-Brasil)
                        cadastrado em Configuracoes &gt; Fiscal &amp; Certificado A1 - sem ele, a SEFAZ nunca autoriza a nota.
                    </p>
                @else
                    <p class="mt-2 text-xs text-amber-700">Corrija o cadastro indicado acima e tente emitir a NF-e novamente.</p>
                @endif
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-bold text-gray-700 uppercase">Identificacao</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <dt class="text-xs text-gray-500">Chave de Acesso</dt>
                    <dd class="font-mono text-xs text-gray-900 break-all">{{ $fiscalDocument->chave_acesso ?? 'Ainda nao gerada' }}</dd>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <dt class="text-xs text-gray-500">Protocolo de Autorizacao</dt>
                    <dd class="font-mono text-xs text-gray-900">{{ $fiscalDocument->protocol ?? '-' }}</dd>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <dt class="text-xs text-gray-500">Venda de Origem</dt>
                    <dd class="font-bold text-gray-900">{{ $fiscalDocument->sale ? 'Venda #'.$fiscalDocument->sale->code : '-' }}</dd>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <dt class="text-xs text-gray-500">Cliente (Destinatario)</dt>
                    <dd class="font-bold text-gray-900">{{ $fiscalDocument->customer?->name ?? 'Venda Direta (sem destinatario)' }}</dd>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <dt class="text-xs text-gray-500">Emitida em</dt>
                    <dd class="font-bold text-gray-900">{{ $fiscalDocument->emitted_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <dt class="text-xs text-gray-500">Autorizada em</dt>
                    <dd class="font-bold text-gray-900">{{ $fiscalDocument->authorized_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="flex gap-2">
            @if ($fiscalDocument->isEditable())
                <a href="{{ route('fiscal.documents.edit', $fiscalDocument) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-sm">
                    Editar e Emitir
                </a>
                <form method="POST" action="{{ route('fiscal.documents.destroy', $fiscalDocument) }}"
                      onsubmit="return confirm('Excluir este rascunho de NF-e? Essa acao nao pode ser desfeita. O numero ja alocado (se houver) nao sera reaproveitado.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-white border border-rose-300 text-rose-600 rounded-lg text-sm font-semibold hover:bg-rose-50">
                        Excluir Rascunho
                    </button>
                </form>
            @endif
            @if ($fiscalDocument->signed_xml_path)
                <a href="{{ route('fiscal.documents.xml', $fiscalDocument) }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-bold hover:bg-gray-800">
                    Baixar XML
                </a>
            @endif
            <a href="{{ route('fiscal.documents.index') }}" class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-semibold text-gray-600">
                Voltar
            </a>
        </div>
    </div>
</x-core::layouts.master>
