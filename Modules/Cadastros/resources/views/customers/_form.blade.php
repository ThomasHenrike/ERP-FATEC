<div x-data="customerFormHelper()" class="space-y-6">

    {{-- Tipo de Pessoa: define quais campos aparecem e a mascara do documento --}}
    <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Tipo de Pessoa *</label>
        <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden text-sm font-semibold">
            <button type="button" @click="personType = 'fisica'"
                    :class="personType === 'fisica' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                    class="px-4 py-2 transition">
                Pessoa Fisica
            </button>
            <button type="button" @click="personType = 'juridica'"
                    :class="personType === 'juridica' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                    class="px-4 py-2 transition border-l border-gray-300">
                Pessoa Juridica
            </button>
        </div>
        <input type="hidden" name="person_type" :value="personType">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- CPF / CNPJ com mascara dinamica e Botao de Consulta Automatica (so PJ) --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase" x-text="documentLabel"></label>
            <div class="mt-1 flex gap-1.5">
                <input type="text" name="document" x-model="doc" @input="doc = maskDocument(doc)"
                       :placeholder="personType === 'juridica' ? '00.000.000/0000-00' : '000.000.000-00'"
                       :maxlength="personType === 'juridica' ? 18 : 14"
                       class="block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                <button type="button" x-show="personType === 'juridica'" @click="consultCnpj()" :disabled="isLoadingCnpj"
                        title="Consultar dados da empresa na Receita Federal via BrasilAPI"
                        class="px-2.5 py-1 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-xs font-bold transition disabled:bg-gray-400">
                    <span x-show="!isLoadingCnpj">Buscar CNPJ</span>
                    <span x-show="isLoadingCnpj">...</span>
                </button>
            </div>
            @error('document') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div x-show="personType === 'juridica'" x-cloak>
            <label class="block text-xs font-bold text-gray-700 uppercase">Inscricao Estadual (IE)</label>
            <input type="text" name="state_registration" x-model="stateRegistration"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Tabela de Preco Preferencial *</label>
            <select name="default_price_table" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm font-semibold text-indigo-700">
                <option value="retail" @selected(old('default_price_table', $customer->default_price_table ?? 'retail') === 'retail')>Varejo (Consumidor)</option>
                <option value="resale" @selected(old('default_price_table', $customer->default_price_table ?? 'retail') === 'resale')>Revenda (Parceiros/Tecnicos)</option>
                <option value="wholesale" @selected(old('default_price_table', $customer->default_price_table ?? 'retail') === 'wholesale')>Atacado (Volume)</option>
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-700 uppercase" x-text="nameLabel"></label>
            <input type="text" name="name" x-model="name" required
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div x-show="personType === 'juridica'" x-cloak>
            <label class="block text-xs font-bold text-gray-700 uppercase">Nome Fantasia</label>
            <input type="text" name="trade_name" x-model="tradeName"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Limite de Credito Crediario (R$) *</label>
            <input type="number" step="0.01" min="0" name="credit_limit"
                   value="{{ old('credit_limit', $customer->credit_limit ?? 500.00) }}" required
                   class="mt-1 block w-full rounded-lg border-gray-300 font-mono font-bold text-emerald-600 text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Telefone Principal</label>
            <input type="text" name="phone" x-model="phone" @input="phone = maskPhone(phone)" placeholder="(19) 3800-0000" maxlength="15"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">WhatsApp</label>
            <div class="mt-1 flex gap-1.5">
                <input type="text" name="whatsapp" x-model="whatsapp" @input="whatsapp = maskPhone(whatsapp)" placeholder="(19) 99999-8888" maxlength="15"
                       class="block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                <a :href="whatsappLink" target="_blank" rel="noopener"
                   :class="whatsapp.replace(/\D/g, '').length < 10 ? 'pointer-events-none opacity-40' : ''"
                   title="Abrir conversa no WhatsApp"
                   class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition flex items-center">
                    WhatsApp
                </a>
            </div>
        </div>

        <div class="md:col-span-3">
            <label class="block text-xs font-bold text-gray-700 uppercase">Email de Faturamento</label>
            <input type="email" name="email" x-model="email"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>

        {{-- Endereco com Consulta ViaCEP Automatica --}}
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">CEP (Preenchimento Automatico)</label>
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
            <input type="text" name="number" value="{{ old('number', $customer->number ?? '') }}"
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
        <input type="hidden" name="ibge_city_code" x-model="ibgeCityCode">
    </div>

    <div class="pt-4 border-t flex items-center justify-between">
        <label class="inline-flex items-center">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->is_active ?? true))
                   class="rounded border-gray-300 text-indigo-600 shadow-sm">
            <span class="ml-2 text-sm text-gray-700 font-medium">Cliente Ativo</span>
        </label>

        <div class="flex gap-2">
            <a href="{{ route('cadastros.clientes.index') }}" class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-semibold text-gray-600">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-sm">Salvar Cliente</button>
        </div>
    </div>
</div>

<script>
    function customerFormHelper() {
        return {
            personType: '{{ old('person_type', $customer->person_type ?? 'fisica') }}',
            doc: '{{ old('document', $customer->document ?? '') }}',
            name: '{{ old('name', $customer->name ?? '') }}',
            tradeName: '{{ old('trade_name', $customer->trade_name ?? '') }}',
            stateRegistration: '{{ old('state_registration', $customer->state_registration ?? '') }}',
            phone: '{{ old('phone', $customer->phone ?? '') }}',
            whatsapp: '{{ old('whatsapp', $customer->whatsapp ?? '') }}',
            email: '{{ old('email', $customer->email ?? '') }}',
            cep: '{{ old('zip_code', $customer->zip_code ?? '') }}',
            street: '{{ old('street', $customer->street ?? '') }}',
            neighborhood: '{{ old('neighborhood', $customer->neighborhood ?? '') }}',
            city: '{{ old('city', $customer->city ?? '') }}',
            state: '{{ old('state', $customer->state ?? '') }}',
            ibgeCityCode: '{{ old('ibge_city_code', $customer->ibge_city_code ?? '') }}',
            isLoadingCep: false,
            isLoadingCnpj: false,

            get documentLabel() {
                return this.personType === 'juridica' ? 'CNPJ' : 'CPF';
            },

            get nameLabel() {
                return this.personType === 'juridica' ? 'Razao Social *' : 'Nome Completo *';
            },

            get whatsappLink() {
                let digits = this.whatsapp.replace(/\D/g, '');
                return digits.length >= 10 ? `https://wa.me/55${digits}` : '#';
            },

            // Mascara CPF (000.000.000-00) ou CNPJ (00.000.000/0000-00) conforme o tipo de pessoa
            maskDocument(value) {
                let digits = value.replace(/\D/g, '');

                if (this.personType === 'juridica') {
                    digits = digits.slice(0, 14);
                    return digits
                        .replace(/^(\d{2})(\d)/, '$1.$2')
                        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                        .replace(/\.(\d{3})(\d)/, '.$1/$2')
                        .replace(/(\d{4})(\d)/, '$1-$2');
                }

                digits = digits.slice(0, 11);
                return digits
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            },

            // Mascara de telefone fixo (00) 0000-0000 ou celular (00) 00000-0000
            maskPhone(value) {
                let digits = value.replace(/\D/g, '').slice(0, 11);

                if (digits.length <= 10) {
                    return digits
                        .replace(/^(\d{2})(\d)/, '($1) $2')
                        .replace(/(\d{4})(\d)/, '$1-$2');
                }

                return digits
                    .replace(/^(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{5})(\d)/, '$1-$2');
            },

            handleCepInput() {
                this.cep = this.cep.replace(/\D/g, '').slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2');
                if (this.cep.replace(/\D/g, '').length === 8) {
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
                            this.ibgeCityCode = data.ibge || this.ibgeCityCode;
                        }
                    })
                    .catch(() => { this.isLoadingCep = false; });
            },

            consultCnpj() {
                let cleanCnpj = this.doc.replace(/\D/g, '');
                if (cleanCnpj.length !== 14) {
                    alert('Informe um CNPJ valido com 14 digitos para consulta na Receita Federal.');
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
                            this.phone = data.ddd_telefone_1 ? this.maskPhone(data.ddd_telefone_1) : this.phone;
                            this.email = data.email || this.email;
                            this.cep = data.cep ? data.cep.replace(/^(\d{5})(\d{3})/, '$1-$2') : this.cep;
                            this.street = data.logradouro ? `${data.descricao_tipo_de_logradouro || ''} ${data.logradouro}`.trim() : this.street;
                            this.neighborhood = data.bairro || this.neighborhood;
                            this.city = data.municipio || this.city;
                            this.state = data.uf || this.state;
                            this.ibgeCityCode = data.codigo_municipio_ibge ? String(data.codigo_municipio_ibge) : this.ibgeCityCode;
                        } else {
                            alert(data.message || 'CNPJ nao localizado na base publica da Receita Federal.');
                        }
                    })
                    .catch(() => {
                        this.isLoadingCnpj = false;
                        alert('Erro de comunicacao com o servico de consulta de CNPJ.');
                    });
            }
        }
    }
</script>
