<x-core::layouts.master>
    <div class="min-h-[85vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-xl text-center">
            <a href="{{ route('login') }}" class="inline-block mb-4" title="MERP - voltar ao login">
                <x-core::brand.logo variante="horizontal-claro" class="h-14 w-auto" />
            </a>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Cadastre sua Empresa</h2>
            <p class="mt-2 text-sm text-gray-600">Configure o ambiente corporativo e o acesso do primeiro administrador</p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-xl">
            <div class="bg-white py-8 px-6 shadow-sm border border-gray-200 sm:rounded-2xl sm:px-10">
                <form action="{{ route('core.tenant.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <h3 class="text-base font-bold text-gray-900 border-b pb-2">1. Dados da Empresa</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Razao Social / Nome Empresarial *</label>
                            <input type="text" name="company_name" value="{{ old('company_name') }}" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            @error('company_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome Fantasia</label>
                            <input type="text" name="trade_name" value="{{ old('trade_name') }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">CNPJ / CPF *</label>
                            <input type="text" name="document" value="{{ old('document') }}" required placeholder="00.000.000/0000-00"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            @error('document') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <h3 class="text-base font-bold text-gray-900 border-b pb-2 pt-2">2. Administrador da Conta</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Nome do Administrador *</label>
                            <input type="text" name="admin_name" value="{{ old('admin_name') }}" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Email de Acesso *</label>
                            <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            @error('admin_email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Senha (min. 6 caracteres) *</label>
                            <input type="password" name="password" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Confirmar Senha *</label>
                            <input type="password" name="password_confirmation" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition shadow-sm">
                            Criar Empresa e Iniciar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-core::layouts.master>