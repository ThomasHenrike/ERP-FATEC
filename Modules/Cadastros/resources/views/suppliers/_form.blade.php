<div x-data="supplierFormHelper()" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        {{-- CNPJ com Botao de Consulta Automatica --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">CNPJ *</label>
            <div class="mt-1 flex gap-1.5">
                <input type="text" name="document" x-model="cnpj" required placeholder="00.000.000/0000-00"
                       class="block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                <button type="button" @click="consultCnpj()" :disabled="isLoadingCnpj"
                        class="px-2.5 py-1 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-xs font-bold transition disabled:bg-gray-400">
                    <span x-show="!isLoadingCnpj">Consultar</span>
                    <span x-show="isLoadingCnpj">...</span>
                </button>
            </div>
            @error('document') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Inscricao Estadual</label>
            <input type="text" name="state_registration" x-model="stateRegistration"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Nome do Representante Comercial</label>
            <input type="text" name="contact_name" value="{{ old('contact_name', $supplier->contact_name ?? '') }}" placeholder="Ex: Carlos Vendas"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-700 uppercase">Razao Social do Fornecedor *</label>
            <input type="text" name="name" x-model="name" required
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Nome Fantasia</label>
            <input type="text" name="trade_name" x-model="tradeName"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Telefone Comercial</label>
            <input type="text" name="phone" x-model="phone"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">WhatsApp de Pedidos</label>
            <input type="text" name="whatsapp" value="{{ old('whatsapp', $supplier->whatsapp ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Email de Faturamento</label>
            <input type="email" name="email" x-model="email"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        {{-- CEP e Endereco --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">CEP</label>
            <div class="mt-1 flex gap-1.5">
                <input type="text" name="zip_code" x-model="cep" @input="handleCepInput()" placeholder="00000-000" maxlength="9"
                       class="block w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono font-bold">
                <button type="button" @click="consultCep()" :disabled="isLoadingCep"
                        class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold border border-indigo-200">
                    <span x-show="!isLoadingCep">ViaCEP</span>
                    <span x-show="isLoadingCep">...</span>
                </button>
            </div>
        </div>

        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-700 uppercase">Logradouro / Rua</label>
            <input type="text" name="street" x-model="street"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Numero</label>
            <input type="text" name="number" value="{{ old('number', $supplier->number ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Bairro</label>
            <input type="text" name="neighborhood" x-model="neighborhood"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Cidade / UF</label>
            <div class="flex gap-2 mt-1">
                <input type="text" name="city" x-model="city" placeholder="Cidade" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                <input type="text" name="state" maxlength="2" x-model="state" placeholder="UF" class="w-16 rounded-lg border-gray-300 shadow-sm text-sm uppercase text-center font-bold">
            </div>
        </div>
    </div>

    <div class="pt-4 border-t flex items-center justify-between">
        <label class="inline-flex items-center">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active ?? true))
                   class="rounded border-gray-300 text-indigo-600 shadow-sm">
            <span class="ml-2 text-sm text-gray-700 font-medium">Fornecedor Ativo</span>
        </label>

        <div class="flex gap-2">
            <a href="{{ route('cadastros.fornecedores.index') }}" class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-semibold text-gray-600">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-sm">Salvar Fornecedor</button>
        </div>
    </div>
</div>

<script>
    function supplierFormHelper() {
        return {
            cnpj: '{{ old('document', $supplier->document ?? '') }}',
            name: '{{ old('name', $supplier->name ?? '') }}',
            tradeName: '{{ old('trade_name', $supplier->trade_name ?? '') }}',
            stateRegistration: '{{ old('state_registration', $supplier->state_registration ?? '') }}',
            phone: '{{ old('phone', $supplier->phone ?? '') }}',
            email: '{{ old('email', $supplier->email ?? '') }}',
            cep: '{{ old('zip_code', $supplier->zip_code ?? '') }}',
            street: '{{ old('street', $supplier->street ?? '') }}',
            neighborhood: '{{ old('neighborhood', $supplier->neighborhood ?? '') }}',
            city: '{{ old('city', $supplier->city ?? '') }}',
            state: '{{ old('state', $supplier->state ?? '') }}',
            isLoadingCep: false,
            isLoadingCnpj: false,

            handleCepInput() {
                let cleanCep = this.cep.replace(/\D/g, '');
                if (cleanCep.length === 8) {
                    this.consultCep();
                }
            },

            consultCep() {
                let cleanCep = this.cep.replace(/\D/g, '');
                if (cleanCep.length !== 8) return;
                this.isLoadingCep = true;

                fetch(`https://viacep.com.br/ws/${cleanCep}/json/`)
                    .then(r => r.json())
                    .then(data => {
                        this.isLoadingCep = false;
                        if (!data.erro) {
                            this.street = data.logradouro || this.street;
                            this.neighborhood = data.bairro || this.neighborhood;
                            this.city = data.localidade || this.city;
                            this.state = data.uf || this.state;
                        }
                    })
                    .catch(() => { this.isLoadingCep = false; });
            },

            consultCnpj() {
                let cleanCnpj = this.cnpj.replace(/\D/g, '');
                if (cleanCnpj.length !== 14) {
                    alert('Informe um CNPJ valido com 14 digitos.');
                    return;
                }
                this.isLoadingCnpj = true;

                fetch(`https://brasilapi.com.br/api/cnpj/v1/${cleanCnpj}`)
                    .then(r => r.json())
                    .then(data => {
                        this.isLoadingCnpj = false;
                        if (data.razao_social) {
                            this.name = data.razao_social;
                            this.tradeName = data.nome_fantasia || this.tradeName;
                            this.phone = data.ddd_telefone_1 ? `(${data.ddd_telefone_1.slice(0,2)}) ${data.ddd_telefone_1.slice(2)}` : this.phone;
                            this.email = data.email || this.email;
                            this.cep = data.cep ? data.cep.replace(/^(\d{5})(\d{3})/, '$1-$2') : this.cep;
                            this.street = data.logradouro ? `${data.descricao_tipo_de_logradouro || ''} ${data.logradouro}`.trim() : this.street;
                            this.neighborhood = data.bairro || this.neighborhood;
                            this.city = data.municipio || this.city;
                            this.state = data.uf || this.state;
                        } else {
                            alert(data.message || 'CNPJ nao localizado na base publica da Receita Federal.');
                        }
                    })
                    .catch(() => {
                        this.isLoadingCnpj = false;
                        alert('Erro de comunicacao ao consultar CNPJ.');
                    });
            }
        }
    }
</script>