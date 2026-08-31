<x-superadmin::layouts.admin>
    <div class="p-6 max-w-3xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $admin->name }}</h1>
                <p class="text-sm text-gray-500">{{ $admin->email }}</p>
            </div>
            <a href="{{ route('admin.funcionarios.index') }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; Voltar</a>
        </div>

        @if ($admin->id === auth('admin')->id())
            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg px-4 py-3">
                Esta e' a sua propria conta - o papel nao pode ser alterado por aqui.
            </div>
        @endif

        <form action="{{ route('admin.funcionarios.update', $admin) }}" method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" name="name" value="{{ old('name', $admin->name) }}" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email', $admin->email) }}" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Papel</label>
                <select name="role" required {{ $admin->id === auth('admin')->id() ? 'disabled' : '' }}
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm disabled:bg-gray-100 disabled:text-gray-500">
                    @foreach ($roleOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('role', $admin->role) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @if ($admin->id === auth('admin')->id())
                    <input type="hidden" name="role" value="{{ $admin->role }}">
                @endif
                @error('role') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nova Senha</label>
                    <input type="password" name="password"
                           placeholder="Deixe em branco para manter"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirmar Nova Senha</label>
                    <input type="password" name="password_confirmation"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-6">
                <a href="{{ route('admin.funcionarios.index') }}" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900">Cancelar</a>
                <button type="submit" class="px-4 py-2 bg-amber-500 text-gray-900 rounded-lg text-sm font-semibold hover:bg-amber-600 transition shadow-sm">
                    Salvar Alteracoes
                </button>
            </div>
        </form>
    </div>
</x-superadmin::layouts.admin>
