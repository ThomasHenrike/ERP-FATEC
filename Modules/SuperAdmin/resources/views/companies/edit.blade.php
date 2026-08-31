<x-superadmin::layouts.admin>
    <div class="p-6 max-w-5xl mx-auto space-y-6" x-data="{ activeTab: 'info' }">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $tenant->trade_name ?: $tenant->name }}</h1>
                <p class="text-sm text-gray-500">{{ $tenant->document }}</p>
            </div>
            <a href="{{ route('admin.companies.index') }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; Voltar</a>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 bg-gray-50/50 px-6">
                <nav class="-mb-px flex space-x-8">
                    <button type="button" @click="activeTab = 'info'"
                            :class="activeTab === 'info' ? 'border-amber-500 text-amber-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm font-medium transition">
                        1. Dados
                    </button>
                    <button type="button" @click="activeTab = 'modules'"
                            :class="activeTab === 'modules' ? 'border-amber-500 text-amber-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm font-medium transition">
                        2. Modulos
                    </button>
                    <button type="button" @click="activeTab = 'invoices'"
                            :class="activeTab === 'invoices' ? 'border-amber-500 text-amber-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm font-medium transition">
                        3. Faturas
                    </button>
                    <button type="button" @click="activeTab = 'system'"
                            :class="activeTab === 'system' ? 'border-amber-500 text-amber-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm font-medium transition">
                        4. Informacoes do Sistema
                    </button>
                </nav>
            </div>

            {{-- Aba 1: Dados --}}
            <div x-show="activeTab === 'info'" class="p-6">
                <form action="{{ route('admin.companies.update', $tenant) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Razao Social</label>
                        <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome Fantasia</label>
                        <input type="text" name="trade_name" value="{{ old('trade_name', $tenant->trade_name) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">CNPJ/CPF</label>
                        <input type="text" name="document" value="{{ old('document', $tenant->document) }}" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" value="{{ old('email', $tenant->email) }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Telefone</label>
                            <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Dia de Vencimento</label>
                            <input type="number" min="1" max="28" name="billing_due_day" value="{{ old('billing_due_day', $tenant->billing_due_day) }}"
                                   placeholder="Ex: 10"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            <p class="mt-1 text-xs text-gray-400">Usado pra gerar a fatura mensal automaticamente.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valor da Mensalidade</label>
                            <input type="number" step="0.01" min="0" name="billing_amount" value="{{ old('billing_amount', $tenant->billing_amount) }}"
                                   placeholder="Ex: 199.90"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Limite de Usuarios (plano)</label>
                        <input type="number" min="1" name="max_users" value="{{ old('max_users', $tenant->max_users) }}"
                               placeholder="Deixe vazio para ilimitado"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        <p class="mt-1 text-xs text-gray-400">Quantos usuarios com login essa empresa pode cadastrar. Vazio = sem limite.</p>
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $tenant->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-amber-500 shadow-sm focus:ring-amber-500">
                        <span class="text-sm text-gray-700">Empresa ativa</span>
                    </label>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-4 py-2 bg-amber-500 text-gray-900 rounded-lg text-sm font-semibold hover:bg-amber-600 transition shadow-sm">
                            Salvar Dados
                        </button>
                    </div>
                </form>
            </div>

            {{-- Aba 2: Modulos --}}
            <div x-show="activeTab === 'modules'" class="p-6" style="display:none;">
                <form action="{{ route('admin.companies.modules', $tenant) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <p class="text-sm text-gray-500 mb-4">Desativar um modulo bloqueia o acesso a ele imediatamente para todos os usuarios dessa empresa.</p>

                    @foreach ($toggleableModules as $module)
                        <label class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">{{ ucfirst($module) }}</span>
                            <input type="checkbox" name="{{ $module }}" value="1" {{ $tenant->hasModule($module) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-amber-500 shadow-sm focus:ring-amber-500">
                        </label>
                    @endforeach

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-4 py-2 bg-amber-500 text-gray-900 rounded-lg text-sm font-semibold hover:bg-amber-600 transition shadow-sm">
                            Salvar Modulos
                        </button>
                    </div>
                </form>
            </div>

            {{-- Aba 3: Faturas --}}
            <div x-show="activeTab === 'invoices'" class="p-6 space-y-6" style="display:none;">
                <form action="{{ route('admin.invoices.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end p-4 bg-gray-50 rounded-lg border border-gray-200">
                    @csrf
                    <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Valor</label>
                        <input type="number" step="0.01" min="0" name="amount" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Vencimento</label>
                        <input type="date" name="due_date" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Referencia</label>
                        <input type="text" name="reference" placeholder="Ex: Mensalidade Setembro" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-semibold hover:bg-gray-800 transition">
                        Nova Fatura
                    </button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">Referencia</th>
                                <th class="px-4 py-3">Valor</th>
                                <th class="px-4 py-3">Vencimento</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Acoes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td class="px-4 py-3">{{ $invoice->reference ?: '-' }}</td>
                                    <td class="px-4 py-3 font-mono">R$ {{ number_format((float) $invoice->amount, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3">{{ $invoice->due_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $statusStyle = match ($invoice->status) {
                                                'paid' => 'bg-emerald-50 text-emerald-700',
                                                'overdue' => 'bg-rose-50 text-rose-700',
                                                'cancelled' => 'bg-gray-100 text-gray-500',
                                                default => 'bg-amber-50 text-amber-700',
                                            };
                                            $statusLabel = match ($invoice->status) {
                                                'paid' => 'Paga',
                                                'overdue' => 'Atrasada',
                                                'cancelled' => 'Cancelada',
                                                default => 'Pendente',
                                            };
                                        @endphp
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold {{ $statusStyle }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if (in_array($invoice->status, ['pending', 'overdue']))
                                            <form action="{{ route('admin.invoices.markPaid', $invoice) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-emerald-600 hover:text-emerald-800 font-semibold text-xs">Marcar como paga</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">Nenhuma fatura ainda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Aba 4: Informacoes do Sistema --}}
            <div x-show="activeTab === 'system'" class="p-6 space-y-8" style="display:none;">
                <div>
                    <h2 class="text-sm font-bold text-gray-700 uppercase mb-3">Identificacao</h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <dt class="text-xs text-gray-500">Tenant ID</dt>
                            <dd class="font-mono font-bold text-gray-900">#{{ $tenant->id }}</dd>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <dt class="text-xs text-gray-500">UUID</dt>
                            <dd class="font-mono text-xs text-gray-700 break-all">{{ $tenant->uuid }}</dd>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <dt class="text-xs text-gray-500">Cadastrada em</dt>
                            <dd class="font-bold text-gray-900">{{ $tenant->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h2 class="text-sm font-bold text-gray-700 uppercase mb-3">Volume de Dados</h2>
                    <p class="text-xs text-gray-500 mb-3">
                        Quantidade de registros que essa empresa acumulou em cada modulo, com estimativa de espaco em disco - util pra acompanhar crescimento e negociar plano.
                        @if ($estimatedDbBytesIsPartial)
                            <span class="text-gray-400">(estimativa de tamanho indisponivel pra algum modulo neste ambiente)</span>
                        @endif
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        @foreach ($usageCounts as $label => $count)
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-center">
                                <div class="text-xl font-extrabold text-gray-900">{{ $count }}</div>
                                <div class="text-xs text-gray-500">{{ $label }}</div>
                                <div class="text-[11px] text-gray-400 font-mono mt-1">{{ \Modules\SuperAdmin\App\Support\DataVolumeCalculator::humanize($usageBytes[$label]) }}</div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        Estimativa de banco de dados desta empresa: <span class="font-bold text-gray-800">{{ \Modules\SuperAdmin\App\Support\DataVolumeCalculator::humanize($estimatedDbBytes) }}</span>
                        @if ($estimatedDbBytesIsPartial) e mais @endif
                    </p>
                </div>

                <div>
                    <h2 class="text-sm font-bold text-gray-700 uppercase mb-3">Arquivos Armazenados</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach ($files as $label => $info)
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 flex items-center justify-between text-sm">
                                <span class="text-gray-700">{{ $label }}</span>
                                @if ($info['exists'])
                                    <span class="font-mono text-xs text-gray-900">{{ number_format($info['size'] / 1024, 1) }} KB</span>
                                @else
                                    <span class="text-xs text-gray-400">Nenhum arquivo</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-bold text-gray-700 uppercase mb-3">Usuarios com Acesso</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3">Nome</th>
                                    <th class="px-4 py-3">Email</th>
                                    <th class="px-4 py-3">Papel</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Desde</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-4 py-3 font-bold text-gray-900">{{ $user->name }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-indigo-50 text-indigo-700">{{ \Modules\Core\App\Support\RoleLevel::label($user->role) }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                                {{ $user->is_active ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($user->created_at)->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">Nenhum usuario vinculado a essa empresa.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-superadmin::layouts.admin>
