<div class="space-y-6" x-data="{
        hasAccess: {{ isset($employee) && $employee->user_id ? 'true' : (old('has_system_access') ? 'true' : 'false') }},
        systemRole: '{{ old('system_role', $pivot->role ?? 'operator') }}',
        roleDescriptions: {
            operator: 'Uso do dia a dia: vender no PDV, abrir turno de caixa, cadastrar clientes e criar orcamentos. Nao acessa configuracoes da empresa, RH nem acoes financeiras sensiveis.',
            manager: 'Tudo que o Operador faz, alem de: estornar vendas, fechar caixa, autorizar sangria/suprimento, ajustar estoque manualmente, excluir produtos/clientes/fornecedores/orcamentos e baixar recebiveis.',
            admin: 'Acesso total: tudo do Gerente, alem de configuracoes da empresa, emissao fiscal e gerenciar outros colaboradores e seus acessos ao sistema.'
        }
    }">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Nome Completo do Colaborador *</label>
            <input type="text" name="name" value="{{ old('name', $employee->name ?? '') }}" required
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">CPF</label>
            <input type="text" name="document" value="{{ old('document', $employee->document ?? '') }}" placeholder="000.000.000-00"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Cargo / Comissao *</label>
            <select name="role" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                @foreach (['vendedor' => 'Vendedor de Balcao', 'caixa' => 'Operador de Caixa', 'gerente' => 'Gerente de Loja', 'ajudante' => 'Ajudante Geral'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(old('role', $employee->role ?? 'vendedor') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-400">So define a comissao/atribuicao de venda - nao tem relacao com o acesso ao sistema abaixo.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Comissao sobre Vendas (%) *</label>
            <input type="number" step="0.01" min="0" max="100" name="commission_percentage"
                   value="{{ old('commission_percentage', $employee->commission_percentage ?? 0.00) }}" required
                   class="mt-1 block w-full rounded-lg border-gray-300 font-mono font-bold text-indigo-600 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Data de Admissao</label>
            <input type="date" name="hire_date" value="{{ old('hire_date', isset($employee->hire_date) ? $employee->hire_date->format('Y-m-d') : '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Telefone / Celular</label>
            <input type="text" name="phone" value="{{ old('phone', $employee->phone ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
            <input type="text" name="whatsapp" value="{{ old('whatsapp', $employee->whatsapp ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Email (contato)</label>
            <input type="email" name="email" value="{{ old('email', $employee->email ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>
    </div>

    {{-- Acesso ao Sistema (login) - separado do cargo/comissao acima --}}
    <div class="pt-6 border-t space-y-4">
        <h3 class="text-sm font-bold text-gray-700 uppercase">Acesso ao Sistema</h3>

        @if (($limitReached ?? false) && !(isset($employee) && $employee->user_id))
            <p class="text-xs text-rose-600 bg-rose-50 border border-rose-200 rounded-lg p-3">
                O limite de usuarios do seu plano foi atingido. Fale com o suporte pra liberar mais acessos.
            </p>
        @endif

        @if (isset($employee) && $employee->user_id)
            {{-- Ja tem login: mostra papel atual + reset de senha + revogar/reativar --}}
            <input type="hidden" name="system_role" x-bind:value="systemRole">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Papel no Sistema</label>
                    @if ($isSelf)
                        <input type="text" value="{{ \Modules\Core\App\Support\RoleLevel::label($pivot->role) }}" disabled
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm bg-gray-100">
                        <p class="mt-1 text-xs text-gray-400">Voce nao pode alterar o proprio papel.</p>
                    @else
                        <select x-model="systemRole" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            @foreach ($roleOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500" x-text="roleDescriptions[systemRole]"></p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nova Senha (opcional)</label>
                    <input type="password" name="login_password" placeholder="Deixe em branco pra manter a atual"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    <input type="password" name="login_password_confirmation" placeholder="Confirmar nova senha"
                           class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                </div>
            </div>

            @unless ($isSelf)
                <div>
                    @if ($pivot->is_active)
                        <button type="submit" formaction="{{ route('cadastros.funcionarios.revokeAccess', $employee) }}" formmethod="POST" formnovalidate
                                class="text-xs font-semibold text-rose-600 hover:text-rose-800">
                            Revogar acesso ao sistema (mantem o cadastro)
                        </button>
                    @else
                        <button type="submit" formaction="{{ route('cadastros.funcionarios.restoreAccess', $employee) }}" formmethod="POST" formnovalidate
                                class="text-xs font-semibold text-emerald-600 hover:text-emerald-800">
                            Reativar acesso ao sistema
                        </button>
                    @endif
                    @method('PATCH')
                </div>
            @endunless
        @else
            {{-- Ainda nao tem login: opcao de conceder agora --}}
            <label class="flex items-center gap-2">
                <input type="checkbox" name="has_system_access" value="1" x-model="hasAccess"
                       class="rounded border-gray-300 text-indigo-600 shadow-sm" {{ ($limitReached ?? false) ? 'disabled' : '' }}>
                <span class="text-sm text-gray-700">Dar acesso de login ao sistema pra esse colaborador</span>
            </label>

            <div x-show="hasAccess" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-6 border-l-2 border-indigo-100">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email de Login</label>
                    <input type="email" name="login_email" value="{{ old('login_email') }}" placeholder="funcionario@suaempresa.com"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    @error('login_email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Papel no Sistema</label>
                    <select name="system_role" x-model="systemRole" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        @foreach ($roleOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500" x-text="roleDescriptions[systemRole]"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Senha</label>
                    <input type="password" name="login_password" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirmar Senha</label>
                    <input type="password" name="login_password_confirmation" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                </div>
            </div>
        @endif
    </div>

    <div class="pt-4 border-t flex items-center justify-between">
        <label class="inline-flex items-center">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $employee->is_active ?? true))
                   class="rounded border-gray-300 text-indigo-600 shadow-sm">
            <span class="ml-2 text-sm text-gray-700">Funcionario Ativo</span>
        </label>

        <div class="flex gap-2">
            <a href="{{ route('cadastros.funcionarios.index') }}" class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-semibold text-gray-600">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700">Salvar Funcionario</button>
        </div>
    </div>
</div>
