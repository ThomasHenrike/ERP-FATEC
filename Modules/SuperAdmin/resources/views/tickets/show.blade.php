<x-superadmin::layouts.admin>
    <div class="p-6 max-w-3xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Chamado #{{ $ticket->id }}</h1>
                <p class="text-sm text-gray-500">{{ $ticket->tenant->trade_name ?: $ticket->tenant->name }}</p>
            </div>
            <a href="{{ route('admin.tickets.index') }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; Voltar</a>
        </div>

        @if (session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-lg">{{ session('error') }}</div>
        @endif

        <div class="flex items-center gap-3">
            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold {{ $ticket->status === 'open' ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                {{ $ticket->status === 'open' ? 'Aberto' : 'Fechado' }}
            </span>

            <form action="{{ route('admin.tickets.impersonate', $ticket) }}" method="POST">
                @csrf
                <button type="submit" class="px-3 py-1.5 bg-gray-900 text-white rounded-lg text-xs font-bold hover:bg-gray-800 transition">
                    Entrar como esta empresa
                </button>
            </form>

            @if ($ticket->status === 'open')
                <form action="{{ route('admin.tickets.close', $ticket) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-50 transition">
                        Fechar chamado
                    </button>
                </form>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-6 space-y-3 max-h-96 overflow-y-auto">
                @foreach ($ticket->messages as $message)
                    <div class="{{ $message->isFromAdmin() ? 'text-right' : 'text-left' }}">
                        <div class="{{ $message->isFromAdmin() ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-800' }} inline-block px-4 py-2 rounded-2xl text-sm max-w-[80%] text-left">
                            {{ $message->body }}
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">
                            {{ $message->isFromAdmin() ? 'Suporte' : 'Empresa' }} &bull; {{ $message->created_at->format('d/m H:i') }}
                        </p>
                    </div>
                @endforeach
            </div>

            @if ($ticket->status === 'open')
                <form action="{{ route('admin.tickets.reply', $ticket) }}" method="POST" class="p-4 border-t border-gray-100 flex gap-2">
                    @csrf
                    <input type="text" name="body" required maxlength="4000" placeholder="Digite sua resposta..."
                           class="flex-1 rounded-lg border-gray-300 shadow-sm text-sm">
                    <button type="submit" class="px-4 py-2 bg-amber-500 text-gray-900 rounded-lg text-sm font-semibold hover:bg-amber-600 transition">
                        Responder
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-superadmin::layouts.admin>
