<x-superadmin::layouts.admin>
    <div class="p-6 max-w-3xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Novo Funcionario</h1>
            <p class="text-sm text-gray-500">Cria uma conta de acesso ao Admin Supremo</p>
        </div>

        <form action="{{ route('admin.funcionarios.store') }}" method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Papel</label>
                <select name="role" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    @foreach ($roleOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('role', 'support') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
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

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-6">
                <a href="{{ route('admin.funcionarios.index') }}" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-amber-500 text-gray-900 rounded-lg text-sm font-semibold hover:bg-amber-600 transition shadow-sm">
                    Criar Funcionario
                </button>
            </div>
        </form>
    </div>
</x-superadmin::layouts.admin>
