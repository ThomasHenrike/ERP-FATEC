<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin Supremo' }} - ERP Modular</title>

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
    <style>[x-cloak] { display: none !important; }</style>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>

<body class="h-full font-sans antialiased text-gray-900 bg-gray-50 flex flex-col min-h-screen">
    @auth('admin')
        @if (session('impersonation'))
            {{-- Aviso do lado do Admin Supremo, com texto proprio (nao repete o
                 da tela da empresa - aqui e' do ponto de vista de quem esta
                 simulando, la' e' um aviso pro que estiver olhando a tela da
                 empresa). Sem isso, dava pra navegar pelo Admin Supremo sem
                 lembrar que ainda ha' uma sessao de simulacao aberta. --}}
            @php
                $impersonatedTenant = \Modules\Core\App\Models\Tenant::find(session('impersonation.tenant_id'));
            @endphp
            <div class="bg-indigo-700 text-white text-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-3">
                    <span>
                        Você tem uma simulação ativa em <strong>{{ $impersonatedTenant?->trade_name ?? $impersonatedTenant?->name ?? 'uma empresa' }}</strong>
                        desde {{ \Illuminate\Support\Carbon::parse(session('impersonation.started_at'))->diffForHumans() }}.
                    </span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('dashboard') }}" class="px-3 py-1 bg-white text-indigo-700 rounded-md text-xs font-bold hover:bg-indigo-50 transition">
                            Voltar pra simulação
                        </a>
                        <form action="{{ route('admin.impersonation.stop') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1 border border-white/50 rounded-md text-xs font-bold hover:bg-indigo-600 transition">
                                Encerrar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <header class="bg-gray-900 border-b border-gray-800 sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-6">
                        <a href="{{ route('admin.dashboard') }}" class="flex-shrink-0 flex items-center gap-2">
                            <div class="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center text-gray-900 font-bold text-lg shadow-sm">
                                A
                            </div>
                            <span class="font-bold text-lg text-white tracking-tight">Admin Supremo</span>
                        </a>

                        @php
                            $pendingTicketsCount = \Modules\SuperAdmin\App\Models\Ticket::countNeedingReply();
                        @endphp
                        <nav class="hidden sm:flex items-center gap-1">
                            <a href="{{ route('admin.dashboard') }}"
                               class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-800' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('admin.tickets.index') }}"
                               class="px-3 py-2 rounded-md text-sm font-medium transition flex items-center gap-1.5 {{ request()->routeIs('admin.tickets.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-800' }}">
                                Chamados
                                @if ($pendingTicketsCount > 0)
                                    <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 bg-rose-600 text-white text-[11px] font-bold rounded-full">{{ $pendingTicketsCount }}</span>
                                @endif
                            </a>
                            @if (auth('admin')->user()?->isOwner())
                                <a href="{{ route('admin.companies.index') }}"
                                   class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('admin.companies.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-800' }}">
                                    Empresas
                                </a>
                                <a href="{{ route('admin.invoices.index') }}"
                                   class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('admin.invoices.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-800' }}">
                                    Faturas
                                </a>
                                <a href="{{ route('admin.funcionarios.index') }}"
                                   class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('admin.funcionarios.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-800' }}">
                                    Funcionarios
                                </a>
                                <a href="{{ route('admin.activity-logs.index') }}"
                                   class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('admin.activity-logs.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-800' }}">
                                    Logs
                                </a>
                            @endif
                        </nav>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="hidden sm:inline text-sm text-gray-300">{{ auth('admin')->user()->name }}</span>
                        <form action="{{ route('admin.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-sm text-gray-300 hover:text-white px-3 py-2 rounded-md hover:bg-gray-800 transition">
                                Sair
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
    @endauth

    <main class="flex-1">
        @if (session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">
                    {{ session('success') }}
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                <div class="bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-lg px-4 py-3">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="bg-white border-t border-gray-200 py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-gray-400">
            Admin Supremo &bull; ERP Modular
        </div>
    </footer>
</body>
</html>
