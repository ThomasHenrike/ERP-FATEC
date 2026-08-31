<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>{{ $title ?? 'ERP Modular' }} - {{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

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

    @php
        $tenant = app('currentTenant');
        $brandColor = $tenant->primary_color ?? '#4f46e5';
    @endphp
    <style>
        :root {
            --color-brand: {{ $brandColor }};
        }
        .bg-brand { background-color: var(--color-brand) !important; }
        .text-brand { color: var(--color-brand) !important; }
        .border-brand { border-color: var(--color-brand) !important; }
        .ring-brand:focus { --tw-ring-color: var(--color-brand) !important; }
    </style>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="h-full font-sans antialiased text-gray-900 bg-gray-50 flex flex-col min-h-screen">
    {{-- Barra de Navegacao Global Unificada --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm flex-shrink-0" x-data="{ openCadastros: false, openEstoque: false, openVendas: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                
                {{-- Logo / Retorno ao Dashboard com 1 Clique --}}
                <div class="flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" title="Voltar ao Painel Inicial (Dashboard)" class="flex-shrink-0 flex items-center gap-2 group">
                        @if ($tenant && $tenant->logo_url)
                            <img src="{{ $tenant->logo_url }}" alt="Logo" class="h-9 max-w-[140px] object-contain transition transform group-hover:scale-105">
                        @else
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-extrabold text-lg shadow-sm bg-brand transition transform group-hover:scale-105">
                                E
                            </div>
                            <span class="font-extrabold text-lg text-gray-900 tracking-tight">{{ $tenant->trade_name ?? 'ERP Modular' }}</span>
                        @endif
                    </a>

                    {{-- Menus de Navegacao Unificados --}}
                    <nav class="hidden md:flex items-center space-x-1">
                        <a href="{{ route('dashboard') }}" 
                           class="px-3 py-2 rounded-md text-xs font-bold {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} transition">
                            Painel Inicial
                        </a>

                        {{-- Dropdown Cadastros --}}
                        <div class="relative" @click.away="openCadastros = false">
                            <button type="button" @click="openCadastros = !openCadastros" 
                                    class="px-3 py-2 rounded-md text-xs font-bold inline-flex items-center gap-1 {{ request()->routeIs('cadastros.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} transition">
                                <span>Cadastros</span>
                                <span class="text-[10px]">▼</span>
                            </button>
                            <div x-show="openCadastros" class="absolute left-0 mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-200 py-1.5 z-40 text-xs font-semibold" style="display: none;">
                                <a href="{{ route('cadastros.clientes.index') }}" class="block px-4 py-2 hover:bg-gray-50 text-gray-700">Carteira de Clientes</a>
                                <a href="{{ route('cadastros.funcionarios.index') }}" class="block px-4 py-2 hover:bg-gray-50 text-gray-700">Equipe & Vendedores</a>
                                <a href="{{ route('cadastros.fornecedores.index') }}" class="block px-4 py-2 hover:bg-gray-50 text-gray-700">Fornecedores</a>
                            </div>
                        </div>

                        {{-- Dropdown Estoque --}}
                        <div class="relative" @click.away="openEstoque = false">
                            <button type="button" @click="openEstoque = !openEstoque" 
                                    class="px-3 py-2 rounded-md text-xs font-bold inline-flex items-center gap-1 {{ request()->routeIs('estoque.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} transition">
                                <span>Estoque</span>
                                <span class="text-[10px]">▼</span>
                            </button>
                            <div x-show="openEstoque" class="absolute left-0 mt-1 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-1.5 z-40 text-xs font-semibold" style="display: none;">
                                <a href="{{ route('estoque.produtos.index') }}" class="block px-4 py-2 hover:bg-gray-50 text-gray-700">Catalogo de Produtos</a>
                                <a href="{{ route('estoque.categorias.index') }}" class="block px-4 py-2 hover:bg-gray-50 text-gray-700">Categorias & Subcategorias</a>
                                <a href="{{ route('estoque.movimentacoes.index') }}" class="block px-4 py-2 hover:bg-gray-50 text-gray-700">Extrato Kardex</a>
                                <a href="{{ route('estoque.movimentacoes.create') }}" class="block px-4 py-2 hover:bg-gray-50 text-indigo-600 font-bold">+ Ajuste de Inventario</a>
                            </div>
                        </div>

                        {{-- Botao Destaque: Frente de Caixa (PDV) --}}
                        <a href="{{ route('vendas.pos.index') }}" 
                           class="px-3.5 py-1.5 rounded-lg text-xs font-extrabold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition inline-flex items-center gap-1.5">
                            <span>$</span>
                            <span>Frente de Caixa (PDV)</span>
                        </a>

                        {{-- Dropdown Vendas --}}
                        <div class="relative" @click.away="openVendas = false">
                            <button type="button" @click="openVendas = !openVendas" 
                                    class="px-3 py-2 rounded-md text-xs font-bold inline-flex items-center gap-1 {{ request()->routeIs('vendas.sales.*') || request()->routeIs('vendas.prazos.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} transition">
                                <span>Vendas</span>
                                <span class="text-[10px]">▼</span>
                            </button>
                            <div x-show="openVendas" class="absolute left-0 mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-200 py-1.5 z-40 text-xs font-semibold" style="display: none;">
                                <a href="{{ route('vendas.sales.index') }}" class="block px-4 py-2 hover:bg-gray-50 text-gray-700">Historico de Vendas</a>
                                <a href="{{ route('vendas.prazos.index') }}" class="block px-4 py-2 hover:bg-gray-50 text-gray-700">Crediario / Prazos</a>
                            </div>
                        </div>

                        <a href="{{ route('core.settings.edit') }}" 
                           class="px-3 py-2 rounded-md text-xs font-bold {{ request()->routeIs('core.settings.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }} transition">
                            Configuracoes
                        </a>
                    </nav>
                </div>

                {{-- Identificacao da Empresa e Usuario --}}
                <div class="flex items-center gap-3">
                    <span class="text-xs px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-medium">
                        {{ $tenant->trade_name ?? $tenant->name ?? 'Empresa Matriz' }}
                    </span>
                    <span class="text-xs font-semibold text-gray-700">
                        {{ auth()->user()->name ?? 'Administrador' }}
                    </span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800 ml-2">Sair</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 overflow-hidden">
        {{ $slot }}
    </main>
</body>
</html>