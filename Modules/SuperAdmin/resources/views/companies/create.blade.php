<x-superadmin::layouts.admin>
    <div class="p-6 max-w-3xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Nova Empresa</h1>
            <p class="text-sm text-gray-500">Cria a empresa e o usuario administrador dela</p>
        </div>

        <form action="{{ route('admin.companies.store') }}" method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">
            @csrf

            <div class="space-y-4">
                <h2 class="text-sm font-bold text-gray-700 uppercase">Dados da Empresa</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Razao Social</label>
                    <input type="text" name="company_name" value="{{ old('company_name') }}" required
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    @error('company_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome Fantasia</label>
                    <input type="text" name="trade_name" value="{{ old('trade_name') }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">CNPJ/CPF</label>
                    <input type="text" name="document" value="{{ old('document') }}" required
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    @error('document') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-4 border-t border-gray-100 pt-6">
                <h2 class="text-sm font-bold text-gray-700 uppercase">Usuario Administrador da Empresa</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}" required
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    @error('admin_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    @error('admin_email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Senha</label>
                        <input type="password" name="password" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirmar Senha</label>
                        <input type="password" name="password_confirmation" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-6">
                <a href="{{ route('admin.companies.index') }}" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-amber-500 text-gray-900 rounded-lg text-sm font-semibold hover:bg-amber-600 transition shadow-sm">
                    Criar Empresa
                </button>
            </div>
        </form>
    </div>
</x-superadmin::layouts.admin>
