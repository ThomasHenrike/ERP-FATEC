<x-core::layouts.master>
    <div class="p-4 md:p-6 max-w-7xl mx-auto space-y-6">
        @if (session('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm rounded-r-lg shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        @php
            $isAdminDash = app(\Modules\Core\App\Services\TenantManager::class)->hasRoleAtLeast('admin');
            $isManagerDash = app(\Modules\Core\App\Services\TenantManager::class)->hasRoleAtLeast('manager');
        @endphp
        @if ($isAdminDash && $nextDueInvoice)
            @if ($nextDueInvoice->status === 'overdue')
                <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm rounded-r-lg shadow-sm">
                    <span class="font-bold">Fatura vencida.</span> Regularize pelo menu Financeiro para evitar suspensao do acesso.
                    <a href="{{ route('core.financeiro.index') }}" class="underline font-semibold">Ver detalhes</a>
                </div>
            @elseif ($nextDueInvoice->due_date->isBefore(now()->addDays(5)))
                <div class="p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-800 text-sm rounded-r-lg shadow-sm">
                    <span class="font-bold">Fatura proxima do vencimento</span> ({{ $nextDueInvoice->due_date->format('d/m/Y') }}).
                    <a href="{{ route('core.financeiro.index') }}" class="underline font-semibold">Ver detalhes</a>
                </div>
            @endif
        @endif


        {{-- Banner Principal com Acesso Rapido ao PDV e Status do Caixa --}}
        {{-- Grafite (e nao gradiente da cor primaria): o guia MERP nao preenche areas grandes com verde --}}
        <div class="bg-merp-grafite rounded-2xl p-6 text-merp-nevoa shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-0.5 bg-merp-superficie text-merp-nevoa border border-gray-700 rounded-full text-[11px] font-bold uppercase tracking-wider">
                        {{ $tenant->trade_name ?? $tenant->name }}
                    </span>

                    {{-- Indicador de Status do Caixa e Quem Abriu --}}
                    @if ($openCashShifts->isNotEmpty())
                        @foreach ($openCashShifts as $shift)
                            <span class="px-3 py-0.5 bg-merp-superficie text-merp-verde-300 border border-merp-verde-700 rounded-full text-xs font-extrabold flex items-center gap-1 shadow-sm">
                                <span>●</span> Caixa Aberto por: {{ $shift->user->name }} (#{{ $shift->id }} desde {{ $shift->opened_at->format('H:i') }})
                            </span>
                        @endforeach
                    @else
                        <span class="px-3 py-0.5 bg-amber-500 text-merp-grafite rounded-full text-xs font-extrabold flex items-center gap-1 shadow-sm">
                            <span>○</span> Nenhum Caixa Aberto no Momento
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Painel de Gestao Integrada</h1>
                <p class="text-xs sm:text-sm text-gray-300">Central de controle operacional, estoque, vendas e pessoas</p>
            </div>

            @if ($tenant->hasModule('vendas'))
                <div class="flex-shrink-0">
                    <a href="{{ route('vendas.pos.index') }}"
                       class="inline-flex items-center gap-3 px-6 py-4 bg-merp-verde hover:bg-merp-verde-400 text-merp-grafite rounded-xl font-extrabold text-base shadow-lg hover:shadow-merp-verde/30 transition transform hover:-translate-y-0.5">
                        <span class="text-xl">$</span>
                        <span>ACESSAR FRENTE DE CAIXA (PDV)</span>
                    </a>
                </div>
            @endif
        </div>

        {{-- Cards de Indicadores Globais --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase text-gray-500">Vendas de Hoje</p>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1">R$ {{ number_format($todaySalesTotal, 2, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $todaySalesCount }} cupons emitidos</p>
                </div>
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center font-bold text-lg">$</div>
            </div>

            <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase text-gray-500">Catalogo de Produtos</p>
                    <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $totalProducts }} itens</p>
                    @if ($lowStockCount > 0)
                        <p class="text-[11px] text-amber-600 font-bold mt-0.5">{{ $lowStockCount }} em nivel critico</p>
                    @else
                        <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">Estoque regular</p>
                    @endif
                </div>
                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center font-bold text-lg">📦</div>
            </div>

            <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase text-gray-500">Crediario a Receber</p>
                    <p class="text-2xl font-extrabold text-indigo-600 mt-1">R$ {{ number_format($totalReceivablesPending, 2, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Faturas em aberto</p>
                </div>
                <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center font-bold text-lg">📑</div>
            </div>

            <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase text-gray-500">Pessoas & Parceiros</p>
                    <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $totalCustomers }} clientes</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $activeEmployeesCount }} equipe &bull; {{ $totalSuppliers }} fornecedores</p>
                </div>
                <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center font-bold text-lg">👥</div>
            </div>
        </div>

        {{-- Grade de Modulos Interligados --}}
        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-4">Modulos e Funcionalidades do Sistema</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                {{-- Modulo Vendas & PDV --}}
                @if ($tenant->hasModule('vendas'))
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4 hover:border-emerald-500 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-100 text-emerald-700 rounded-xl flex items-center justify-center font-bold text-xl">$</div>
                            <div>
                                <h3 class="font-bold text-gray-900">Vendas & Frente de Caixa</h3>
                                <span class="text-xs text-emerald-600 font-semibold">&bull; Modulo Ativo</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Emissao de cupons, turnos de gaveta, split payment e crediario.</p>
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t text-xs font-semibold">
                            <a href="{{ route('vendas.pos.index') }}" class="p-2 bg-gray-50 hover:bg-emerald-50 rounded-lg text-gray-700 hover:text-emerald-700 text-center transition">Abrir PDV</a>
                            <a href="{{ route('vendas.sales.index') }}" class="p-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 text-center transition">Historico Vendas</a>
                            <a href="{{ route('vendas.prazos.index') }}" class="col-span-2 p-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 text-center transition">Crediario / Prazos</a>
                        </div>
                    </div>
                @endif

                {{-- Modulo Estoque & Inventario --}}
                @if ($tenant->hasModule('estoque'))
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4 hover:border-indigo-500 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 text-indigo-700 rounded-xl flex items-center justify-center font-bold text-xl">📦</div>
                            <div>
                                <h3 class="font-bold text-gray-900">Estoque & Inventario</h3>
                                <span class="text-xs text-indigo-600 font-semibold">&bull; Modulo Ativo</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Catalogo com 3 tabelas de preco, calculadora de markup e extrato Kardex.</p>
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t text-xs font-semibold">
                            <a href="{{ route('estoque.produtos.index') }}" class="p-2 bg-gray-50 hover:bg-indigo-50 rounded-lg text-gray-700 hover:text-indigo-700 text-center transition">Ver Catalogo</a>
                            <a href="{{ route('estoque.produtos.create') }}" class="p-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 text-center transition">+ Novo Produto</a>
                            <a href="{{ route('estoque.movimentacoes.index') }}" class="p-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 text-center transition">Extrato Kardex</a>
                            <a href="{{ route('estoque.movimentacoes.create') }}" class="p-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 text-center transition">Ajuste Manual</a>
                        </div>
                    </div>
                @endif

                {{-- Modulo Cadastros & Pessoas --}}
                @if ($tenant->hasModule('cadastros'))
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4 hover:border-purple-500 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 text-purple-700 rounded-xl flex items-center justify-center font-bold text-xl">👥</div>
                            <div>
                                <h3 class="font-bold text-gray-900">Cadastros & Pessoas</h3>
                                <span class="text-xs text-purple-600 font-semibold">&bull; Modulo Ativo</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Gestao de clientes com limites, equipe com comissoes e fornecedores.</p>
                        <div class="grid grid-cols-3 gap-2 pt-2 border-t text-xs font-semibold">
                            <a href="{{ route('cadastros.clientes.index') }}" class="p-2 bg-gray-50 hover:bg-purple-50 rounded-lg text-gray-700 hover:text-purple-700 text-center transition">Clientes</a>
                            <a href="{{ route('cadastros.funcionarios.index') }}" class="p-2 bg-gray-50 hover:bg-purple-50 rounded-lg text-gray-700 hover:text-purple-700 text-center transition">Equipe</a>
                            <a href="{{ route('cadastros.fornecedores.index') }}" class="p-2 bg-gray-50 hover:bg-purple-50 rounded-lg text-gray-700 hover:text-purple-700 text-center transition">Fornecedores</a>
                        </div>
                    </div>
                @endif

                {{-- Configuracoes do Estabelecimento --}}
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4 hover:border-gray-400 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 text-gray-700 rounded-xl flex items-center justify-center font-bold text-xl">⚙️</div>
                        <div>
                            <h3 class="font-bold text-gray-900">Configuracoes</h3>
                            <span class="text-xs text-gray-500 font-semibold">&bull; Parametrizacao</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Logomarca, formato de impressao A4/Termica e certificado digital A1.</p>
                    <div class="pt-2 border-t text-xs font-semibold">
                        <a href="{{ route('core.settings.edit') }}" class="block p-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 text-center transition">Gerenciar Configuracoes</a>
                    </div>
                </div>

                {{-- Modulo Orcamentos --}}
                @if ($tenant->hasModule('orcamento'))
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4 hover:border-amber-500 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-amber-100 text-amber-700 rounded-xl flex items-center justify-center font-bold text-xl">📝</div>
                            <div>
                                <h3 class="font-bold text-gray-900">Orcamentos Comerciais</h3>
                                <span class="text-xs text-amber-600 font-semibold">&bull; Modulo Ativo</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Propostas comerciais com validade, impressao e conversao em venda com 1 clique.</p>
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t text-xs font-semibold">
                            <a href="{{ route('orcamentos.index') }}" class="p-2 bg-gray-50 hover:bg-amber-50 rounded-lg text-gray-700 hover:text-amber-700 text-center transition">Ver Orcamentos</a>
                            <a href="{{ route('orcamentos.create') }}" class="p-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 text-center transition">+ Novo Orcamento</a>
                        </div>
                    </div>
                @endif

                {{-- Modulo Fiscal (NF-e) --}}
                @if ($tenant->hasModule('fiscal') && $isManagerDash)
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4 hover:border-rose-500 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-rose-100 text-rose-700 rounded-xl flex items-center justify-center font-bold text-xl">🧾</div>
                            <div>
                                <h3 class="font-bold text-gray-900">Fiscal</h3>
                                <span class="text-xs text-rose-600 font-semibold">&bull; Modulo Ativo</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">Emissao de NF-e junto a SEFAZ, com tela de revisao antes de emitir (itens, forma de pagamento e observacao).</p>
                        <div class="grid grid-cols-1 gap-2 pt-2 border-t text-xs font-semibold">
                            <a href="{{ route('fiscal.documents.index') }}" class="p-2 bg-gray-50 hover:bg-rose-50 rounded-lg text-gray-700 hover:text-rose-700 text-center transition">Notas Fiscais</a>
                        </div>
                        <div class="pt-2 border-t flex flex-wrap gap-1.5">
                            <span class="text-[10px] px-2 py-0.5 bg-gray-100 text-gray-500 rounded font-bold uppercase">Cancelamento &bull; Planejado</span>
                            <span class="text-[10px] px-2 py-0.5 bg-gray-100 text-gray-500 rounded font-bold uppercase">Carta de Correcao &bull; Planejado</span>
                            <span class="text-[10px] px-2 py-0.5 bg-gray-100 text-gray-500 rounded font-bold uppercase">NFC-e (Cupom) &bull; Planejado</span>
                        </div>
                    </div>
                @endif

                {{-- Modulo Compras & XML (Roadmap) --}}
                <div class="bg-white/60 rounded-2xl border border-dashed border-gray-300 p-6 space-y-3 opacity-80">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center font-bold text-lg">📥</div>
                        <div>
                            <h3 class="font-bold text-gray-700">Compras & Importador XML</h3>
                            <span class="text-[10px] px-2 py-0.5 bg-blue-100 text-blue-800 rounded font-bold uppercase">Planejado</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400">Leitor de XML de NF-e da SEFAZ com De-Para automatico de fornecedores e estoque.</p>
                </div>
            </div>
        </div>
    </div>
</x-core::layouts.master>