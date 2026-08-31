<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>{{ $title ?? 'ERP Modular' }} - {{ config('app.name', 'Laravel') }}</title>

    <!-- Tipografia Figtree -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS com plugin de formularios (@tailwindcss/forms) -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <style>[x-cloak] { display: none !important; }</style>

    {{--
        Cor personalizada da empresa (Configuracoes > Identidade Visual). Fica no
        layout real usado por todas as paginas - havia uma copia desta mesma logica
        num arquivo de layout antigo (Modules/Core/resources/views/layouts/master.blade.php)
        que nao e mais usado por nenhuma rota, entao a cor nunca aparecia em lugar
        nenhum mesmo depois de salva.
    --}}
    @php
        $tenantAtual = app()->bound('currentTenant') ? app('currentTenant') : null;
        $brandColor = $tenantAtual->primary_color ?? '#4f46e5';
        $isAdmin = app(\Modules\Core\App\Services\TenantManager::class)->hasRoleAtLeast('admin');
        $isManagerOrAboveNav = app(\Modules\Core\App\Services\TenantManager::class)->hasRoleAtLeast('manager');
    @endphp
    <style>
        :root { --color-brand: {{ $brandColor }}; }
        .bg-brand { background-color: var(--color-brand) !important; }
        .text-brand { color: var(--color-brand) !important; }
        .border-brand { border-color: var(--color-brand) !important; }
        .bg-brand-soft { background-color: color-mix(in srgb, var(--color-brand) 12%, white) !important; }
        .hover\:bg-brand-soft:hover { background-color: color-mix(in srgb, var(--color-brand) 12%, white) !important; }
    </style>

    <!-- Alpine.js para reatividade e calculo bidirecional de markup -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    {{--
        Funcoes globais dos componentes de busca reutilizaveis
        (x-core::product-search e x-core::customer-search). Ficam aqui, carregadas
        uma unica vez, porque quando o componente e usado dentro de <template x-if>
        (ex: alternando entre "busca" e "chip selecionado"), o <script> que fica
        dentro do proprio componente nunca roda - elementos inseridos
        dinamicamente via clone/innerHTML nao executam <script>, entao a funcao
        ficava indefinida nesses casos.
    --}}
    <script>
        function productSearchPicker(products) {
            return {
                products: products || [],
                query: '',
                open: false,
                highlightedIndex: 0,

                get results() {
                    if (!this.query) return [];
                    let q = this.query.toLowerCase();
                    return this.products.filter(p =>
                        (p.name && p.name.toLowerCase().includes(q)) ||
                        (p.sku && p.sku.toLowerCase().includes(q)) ||
                        (p.code && p.code.toString().includes(q)) ||
                        (p.barcode && p.barcode.includes(q))
                    ).slice(0, 8);
                },

                highlightNext() {
                    if (this.highlightedIndex < this.results.length - 1) this.highlightedIndex++;
                },

                highlightPrev() {
                    if (this.highlightedIndex > 0) this.highlightedIndex--;
                },

                confirmSelection() {
                    if (this.results.length === 0) return;
                    this.select(this.results[this.highlightedIndex]);
                },

                select(product) {
                    this.$dispatch('product-selected', product);
                    this.query = '';
                    this.open = false;
                    this.highlightedIndex = 0;
                },
            }
        }

        function customerSearchPicker(customers) {
            return {
                customers: customers || [],
                query: '',
                open: false,
                highlightedIndex: 0,

                get results() {
                    if (!this.query) return [];
                    let q = this.query.toLowerCase();
                    let qDigits = this.query.replace(/\D/g, '');
                    return this.customers.filter(c =>
                        (c.name && c.name.toLowerCase().includes(q)) ||
                        (c.document && qDigits && c.document.replace(/\D/g, '').includes(qDigits)) ||
                        (c.email && c.email.toLowerCase().includes(q)) ||
                        (qDigits && c.phone && c.phone.replace(/\D/g, '').includes(qDigits)) ||
                        (qDigits && c.whatsapp && c.whatsapp.replace(/\D/g, '').includes(qDigits))
                    ).slice(0, 8);
                },

                highlightNext() {
                    if (this.highlightedIndex < this.results.length - 1) this.highlightedIndex++;
                },

                highlightPrev() {
                    if (this.highlightedIndex > 0) this.highlightedIndex--;
                },

                confirmSelection() {
                    if (this.results.length === 0) return;
                    this.select(this.results[this.highlightedIndex]);
                },

                select(customer) {
                    this.$dispatch('customer-selected', customer);
                    this.query = '';
                    this.open = false;
                    this.highlightedIndex = 0;
                },
            }
        }
    </script>

    {{-- Diretivas do Vite para build local quando compilado via npm run dev --}}
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="h-full font-sans antialiased text-gray-900 bg-gray-50 flex flex-col min-h-screen">
    @auth
        @if (session('impersonation'))
            {{-- Faixa obrigatoria enquanto um admin do Admin Supremo esta logado
                 como um usuario desta empresa (ver Modules\SuperAdmin\App\Http\Controllers\ImpersonationController).
                 Sem isso e' facil esquecer que a sessao esta dentro da conta de um cliente. --}}
            <div class="bg-rose-600 text-white text-sm sticky top-0 z-40">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-3">
                    <span>
                        <strong>Modo suporte:</strong> visualizando como
                        {{ $tenantAtual->trade_name ?? $tenantAtual->name ?? 'esta empresa' }}
                        &mdash; {{ session('impersonation.admin_name') }}
                    </span>
                    <form action="{{ route('admin.impersonation.stop') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-1 bg-white text-rose-600 rounded-md text-xs font-bold hover:bg-rose-50 transition">
                            Encerrar simulacao
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Barra de Navegacao Superior Global do ERP --}}
        <header class="bg-white border-b border-gray-200 sticky top-0 z-30" x-data="{ mobileOpen: false }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-6">
                        <a href="{{ route('dashboard') }}" class="flex-shrink-0 flex items-center gap-2">
                            <div class="w-8 h-8 bg-brand rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-sm">
                                E
                            </div>
                            <span class="font-bold text-lg text-gray-900 tracking-tight hidden sm:inline">ERP Modular</span>
                        </a>

                        <nav class="hidden md:flex items-center gap-1">
                            <a href="{{ route('dashboard') }}"
                               class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-brand-soft text-brand font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                                Dashboard
                            </a>

                            <a href="{{ route('core.ajuda.index') }}"
                               class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('core.ajuda.*', 'core.suporte.*') ? 'bg-brand-soft text-brand font-semibold' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                                Ajuda
                            </a>

                            @if($tenantAtual?->hasModule('vendas'))
                                <x-core::layouts.nav-dropdown label="Vendas" :active="request()->routeIs('vendas.*', 'orcamentos.*')">
                                    <x-core::layouts.nav-link :href="route('vendas.pos.index')" :active="request()->routeIs('vendas.pos.*')">Frente de Caixa (PDV)</x-core::layouts.nav-link>
                                    <x-core::layouts.nav-link :href="route('vendas.sales.index')" :active="request()->routeIs('vendas.sales.*')">Historico de Vendas</x-core::layouts.nav-link>
                                    <x-core::layouts.nav-link :href="route('vendas.prazos.index')" :active="request()->routeIs('vendas.prazos.*')">Crediario / Prazos</x-core::layouts.nav-link>
                                    @if($isManagerOrAboveNav)
                                        <x-core::layouts.nav-link :href="route('fiscal.documents.index')" :active="request()->routeIs('fiscal.*')">Notas Fiscais 🚧</x-core::layouts.nav-link>
                                    @endif
                                    @if($tenantAtual?->hasModule('orcamento'))
                                        <x-core::layouts.nav-link :href="route('orcamentos.index')" :active="request()->routeIs('orcamentos.*')">Orcamentos</x-core::layouts.nav-link>
                                    @endif
                                </x-core::layouts.nav-dropdown>
                            @elseif($tenantAtual?->hasModule('orcamento'))
                                <x-core::layouts.nav-dropdown label="Orcamentos" :active="request()->routeIs('orcamentos.*')">
                                    <x-core::layouts.nav-link :href="route('orcamentos.index')" :active="request()->routeIs('orcamentos.*')">Orcamentos</x-core::layouts.nav-link>
                                </x-core::layouts.nav-dropdown>
                            @endif

                            @if($tenantAtual?->hasModule('estoque'))
                                <x-core::layouts.nav-dropdown label="Estoque" :active="request()->routeIs('estoque.*')">
                                    <x-core::layouts.nav-link :href="route('estoque.produtos.index')" :active="request()->routeIs('estoque.produtos.*')">Produtos</x-core::layouts.nav-link>
                                    <x-core::layouts.nav-link :href="route('estoque.categorias.index')" :active="request()->routeIs('estoque.categorias.*')">Categorias</x-core::layouts.nav-link>
                                    <x-core::layouts.nav-link :href="route('estoque.movimentacoes.index')" :active="request()->routeIs('estoque.movimentacoes.*')">Kardex (Movimentacoes)</x-core::layouts.nav-link>
                                </x-core::layouts.nav-dropdown>
                            @endif

                            @if($tenantAtual?->hasModule('cadastros'))
                                <x-core::layouts.nav-dropdown label="Cadastros" :active="request()->routeIs('cadastros.*')">
                                    <x-core::layouts.nav-link :href="route('cadastros.clientes.index')" :active="request()->routeIs('cadastros.clientes.*')">Clientes</x-core::layouts.nav-link>
                                    @if($isAdmin)
                                        <x-core::layouts.nav-link :href="route('cadastros.funcionarios.index')" :active="request()->routeIs('cadastros.funcionarios.*')">Equipe</x-core::layouts.nav-link>
                                    @endif
                                    <x-core::layouts.nav-link :href="route('cadastros.fornecedores.index')" :active="request()->routeIs('cadastros.fornecedores.*')">Fornecedores</x-core::layouts.nav-link>
                                </x-core::layouts.nav-dropdown>
                            @endif
                        </nav>
                    </div>

                    <div class="flex items-center gap-3">
                        {{-- Atalho rapido de ajuda - segunda forma de acesso, ao lado
                             do item "Ajuda" da navbar principal --}}
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open" title="Ajuda"
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition font-bold text-sm">
                                ?
                            </button>

                            <div x-show="open" x-cloak x-transition
                                 class="absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-40">
                                <form action="{{ route('core.ajuda.index') }}" method="GET" class="px-3 pb-2">
                                    <input type="text" name="q" placeholder="Pesquisar ajuda..."
                                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                                </form>
                                <a href="{{ route('core.ajuda.show', 'visao-geral') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-50">Visão Geral do Sistema</a>
                                <a href="{{ route('core.ajuda.show', 'papeis-usuarios') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-50">Papéis de Usuário</a>
                                <a href="{{ route('core.ajuda.show', 'fiscal-nfe') }}" class="block px-4 py-1.5 text-sm text-gray-700 hover:bg-gray-50">Emissão de NF-e</a>
                                <div class="border-t border-gray-100 mt-2 pt-2">
                                    <a href="{{ route('core.ajuda.index') }}" class="block px-4 py-1.5 text-sm font-semibold text-brand hover:bg-gray-50">Central de Ajuda completa</a>
                                    <a href="{{ route('core.suporte.index') }}" class="block px-4 py-1.5 text-sm font-semibold text-brand hover:bg-gray-50">Chat de Suporte</a>
                                </div>
                            </div>
                        </div>

                        @if ($tenantAtual)
                            <span class="hidden sm:inline-flex text-xs px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-medium">
                                {{ $tenantAtual->trade_name ?? $tenantAtual->name }}
                            </span>
                        @endif

                        {{-- Menu do Usuario --}}
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open" class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                                <span class="w-8 h-8 rounded-full bg-brand-soft text-brand flex items-center justify-center font-bold text-xs">
                                    {{ Illuminate\Support\Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                                </span>
                                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                            </button>

                            <div x-show="open" x-cloak x-transition
                                 class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-40">
                                @if ($isAdmin)
                                    <a href="{{ route('core.settings.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('core.settings.*') ? 'font-semibold text-brand' : '' }}">
                                        Configuracoes da Empresa
                                    </a>
                                    <a href="{{ route('cadastros.funcionarios.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('cadastros.funcionarios.*') ? 'font-semibold text-brand' : '' }}">
                                        Colaboradores e Acessos
                                    </a>
                                    <a href="{{ route('core.financeiro.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ request()->routeIs('core.financeiro.*') ? 'font-semibold text-brand' : '' }}">
                                        Financeiro
                                    </a>
                                @endif
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-rose-600 hover:bg-rose-50">
                                        Sair do Sistema
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Botao do Menu Mobile --}}
                        <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Navegacao Mobile --}}
                <div x-show="mobileOpen" x-cloak class="md:hidden pb-4 space-y-1">
                    <x-core::layouts.nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" mobile>Dashboard</x-core::layouts.nav-link>
                    <x-core::layouts.nav-link :href="route('core.ajuda.index')" :active="request()->routeIs('core.ajuda.*', 'core.suporte.*')" mobile>Ajuda</x-core::layouts.nav-link>

                    @if($tenantAtual?->hasModule('vendas'))
                        <p class="px-3 pt-2 text-[11px] font-bold text-gray-400 uppercase">Vendas</p>
                        <x-core::layouts.nav-link :href="route('vendas.pos.index')" :active="request()->routeIs('vendas.pos.*')" mobile>Frente de Caixa (PDV)</x-core::layouts.nav-link>
                        <x-core::layouts.nav-link :href="route('vendas.sales.index')" :active="request()->routeIs('vendas.sales.*')" mobile>Historico de Vendas</x-core::layouts.nav-link>
                        <x-core::layouts.nav-link :href="route('vendas.prazos.index')" :active="request()->routeIs('vendas.prazos.*')" mobile>Crediario / Prazos</x-core::layouts.nav-link>
                        @if($isManagerOrAboveNav)
                            <x-core::layouts.nav-link :href="route('fiscal.documents.index')" :active="request()->routeIs('fiscal.*')" mobile>Notas Fiscais 🚧</x-core::layouts.nav-link>
                        @endif
                        @if($tenantAtual?->hasModule('orcamento'))
                            <x-core::layouts.nav-link :href="route('orcamentos.index')" :active="request()->routeIs('orcamentos.*')" mobile>Orcamentos</x-core::layouts.nav-link>
                        @endif
                    @elseif($tenantAtual?->hasModule('orcamento'))
                        <p class="px-3 pt-2 text-[11px] font-bold text-gray-400 uppercase">Orcamentos</p>
                        <x-core::layouts.nav-link :href="route('orcamentos.index')" :active="request()->routeIs('orcamentos.*')" mobile>Orcamentos</x-core::layouts.nav-link>
                    @endif

                    @if($tenantAtual?->hasModule('estoque'))
                        <p class="px-3 pt-2 text-[11px] font-bold text-gray-400 uppercase">Estoque</p>
                        <x-core::layouts.nav-link :href="route('estoque.produtos.index')" :active="request()->routeIs('estoque.produtos.*')" mobile>Produtos</x-core::layouts.nav-link>
                        <x-core::layouts.nav-link :href="route('estoque.categorias.index')" :active="request()->routeIs('estoque.categorias.*')" mobile>Categorias</x-core::layouts.nav-link>
                        <x-core::layouts.nav-link :href="route('estoque.movimentacoes.index')" :active="request()->routeIs('estoque.movimentacoes.*')" mobile>Kardex (Movimentacoes)</x-core::layouts.nav-link>
                    @endif

                    @if($tenantAtual?->hasModule('cadastros'))
                        <p class="px-3 pt-2 text-[11px] font-bold text-gray-400 uppercase">Cadastros</p>
                        <x-core::layouts.nav-link :href="route('cadastros.clientes.index')" :active="request()->routeIs('cadastros.clientes.*')" mobile>Clientes</x-core::layouts.nav-link>
                        @if($isAdmin)
                            <x-core::layouts.nav-link :href="route('cadastros.funcionarios.index')" :active="request()->routeIs('cadastros.funcionarios.*')" mobile>Equipe</x-core::layouts.nav-link>
                        @endif
                        <x-core::layouts.nav-link :href="route('cadastros.fornecedores.index')" :active="request()->routeIs('cadastros.fornecedores.*')" mobile>Fornecedores</x-core::layouts.nav-link>
                    @endif
                </div>
            </div>
        </header>
    @else
        {{-- Cabecalho minimo para telas publicas (login / cadastro de empresa) --}}
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center">
                <a href="{{ route('login') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-sm">
                        E
                    </div>
                    <span class="font-bold text-lg text-gray-900 tracking-tight">ERP Modular</span>
                </a>
            </div>
        </header>
    @endauth

    {{-- Conteudo Principal Injetado pelo Slot dos Modulos --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Rodape Informativo --}}
    <footer class="bg-white border-t border-gray-200 py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-gray-400">
            ERP Modular v1.0 &bull; Ambiente {{ config('app.env') === 'production' ? 'Producao' : 'Local' }}
        </div>
    </footer>
</body>
</html>
