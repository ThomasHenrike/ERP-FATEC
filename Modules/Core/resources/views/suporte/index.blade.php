<x-core::layouts.master>
    <div class="p-6 max-w-3xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Suporte</h1>
            <p class="text-sm text-gray-500">Fale com a equipe do MERP</p>
        </div>

        @if (session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-lg">{{ session('error') }}</div>
        @endif

        @if ($activeTicket)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
                 x-data="supportChat({{ $activeTicket->id }}, {{ Js::from($activeTicket->messages->map(fn ($m) => [
                     'id' => $m->id,
                     'body' => $m->body,
                     'from' => $m->isFromAdmin() ? 'admin' : 'you',
                     'created_at' => $m->created_at->format('d/m H:i'),
                 ])) }}, '{{ route('core.suporte.messages.poll', $activeTicket) }}')">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-bold text-gray-900">Chamado #{{ $activeTicket->id }}</h2>
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700">Aberto</span>
                </div>

                <div class="p-6 space-y-3 max-h-96 overflow-y-auto" x-ref="thread">
                    <template x-for="message in messages" :key="message.id">
                        <div :class="message.from === 'admin' ? 'text-left' : 'text-right'">
                            <div :class="message.from === 'admin' ? 'bg-gray-100 text-gray-800' : 'bg-brand text-white'"
                                 class="inline-block px-4 py-2 rounded-2xl text-sm max-w-[80%] text-left">
                                <p x-text="message.body"></p>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1" x-text="(message.from === 'admin' ? 'Suporte' : 'Voce') + ' - ' + message.created_at"></p>
                        </div>
                    </template>
                </div>

                <form action="{{ route('core.suporte.messages.store', $activeTicket) }}" method="POST" class="p-4 border-t border-gray-100 flex gap-2">
                    @csrf
                    <input type="text" name="body" required maxlength="4000" placeholder="Digite sua mensagem..."
                           class="flex-1 rounded-lg border-gray-300 shadow-sm text-sm">
                    <button type="submit" class="px-4 py-2 bg-brand text-white rounded-lg text-sm font-semibold hover:opacity-90 transition">
                        Enviar
                    </button>
                </form>
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                <p class="text-sm text-gray-600">Nenhum chamado aberto no momento. Descreva sua duvida ou problema abaixo.</p>
                <form action="{{ route('core.suporte.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <textarea name="body" rows="4" required maxlength="4000" placeholder="Como podemos ajudar?"
                              class="w-full rounded-lg border-gray-300 shadow-sm text-sm"></textarea>
                    @error('body') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    <button type="submit" class="px-4 py-2 bg-brand text-white rounded-lg text-sm font-semibold hover:opacity-90 transition">
                        Abrir Chamado
                    </button>
                </form>
            </div>
        @endif

        @if ($history->isNotEmpty())
            <div class="space-y-2">
                <h2 class="text-sm font-bold text-gray-700 uppercase">Chamados anteriores</h2>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">
                    @foreach ($history as $ticket)
                        <div class="px-4 py-3 text-sm flex justify-between text-gray-600">
                            <span>Chamado #{{ $ticket->id }}</span>
                            <span class="text-gray-400">Encerrado em {{ $ticket->closed_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <script>
        function supportChat(ticketId, initialMessages, pollUrl) {
            return {
                messages: initialMessages,
                pollTimer: null,

                init() {
                    this.scrollToBottom();
                    this.startPolling();
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            this.startPolling();
                        } else {
                            this.stopPolling();
                        }
                    });
                },

                startPolling() {
                    this.stopPolling();
                    this.pollTimer = setInterval(() => this.poll(), 5000);
                },

                stopPolling() {
                    if (this.pollTimer) {
                        clearInterval(this.pollTimer);
                        this.pollTimer = null;
                    }
                },

                async poll() {
                    const lastId = this.messages.length ? this.messages[this.messages.length - 1].id : 0;

                    try {
                        const response = await fetch(pollUrl + '?after_id=' + lastId, {
                            headers: { 'Accept': 'application/json' },
                        });

                        if (!response.ok) return;

                        const data = await response.json();

                        if (data.messages.length) {
                            this.messages.push(...data.messages);
                            this.$nextTick(() => this.scrollToBottom());
                        }

                        if (data.status === 'closed') {
                            this.stopPolling();
                        }
                    } catch (e) {
                        // Falha de rede pontual - tenta de novo no proximo ciclo.
                    }
                },

                scrollToBottom() {
                    this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight;
                },
            }
        }
    </script>
</x-core::layouts.master>
