<x-superadmin::layouts.admin>
    <div class="min-h-[80vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="w-12 h-12 bg-amber-500 rounded-xl mx-auto flex items-center justify-center text-gray-900 font-bold text-2xl shadow-md">
                A
            </div>
            <h2 class="mt-4 text-3xl font-extrabold text-gray-900 tracking-tight">
                Admin Supremo
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Acesso restrito a plataforma - nao e o login das empresas.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-sm border border-gray-200 sm:rounded-2xl sm:px-10">
                <form action="{{ route('admin.login') }}" method="POST" class="space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm">
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                        <input id="password" name="password" type="password" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm">
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-amber-500 shadow-sm focus:ring-amber-500">
                            <span class="ml-2 text-sm text-gray-600">Lembrar-me</span>
                        </label>
                    </div>

                    <div>
                        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-gray-900 bg-amber-500 hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition">
                            Entrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-superadmin::layouts.admin>
