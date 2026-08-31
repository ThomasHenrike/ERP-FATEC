<x-core::layouts.master>
    <div class="p-6 max-w-6xl mx-auto space-y-6" x-data="{ activeTab: 'general', selectedColor: '{{ $tenant->primary_color ?? '#4f46e5' }}' }">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Configuracoes do Estabelecimento</h1>
            <p class="text-sm text-gray-500">Parametrizacao de identidade visual, formatos de impressao e seguranca fiscal</p>
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            {{-- Navegacao por Abas --}}
            <div class="border-b border-gray-200 bg-gray-50/50 px-6">
                <nav class="-mb-px flex space-x-8">
                    <button type="button" @click="activeTab = 'general'" 
                            :class="activeTab === 'general' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm font-medium transition">
                        1. Identidade Visual & Endereco
                    </button>
                    <button type="button" @click="activeTab = 'print'" 
                            :class="activeTab === 'print' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm font-medium transition">
                        2. Impressao & Documentos
                    </button>
                    <button type="button" @click="activeTab = 'fiscal'" 
                            :class="activeTab === 'fiscal' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm font-medium transition">
                        3. Fiscal & Certificado A1
                    </button>
                    <button type="button" @click="activeTab = 'pdv'"
                            :class="activeTab === 'pdv' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm font-medium transition">
                        4. Regras do Caixa (PDV)
                    </button>
                    <button type="button" @click="activeTab = 'orcamentos'"
                            :class="activeTab === 'orcamentos' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 text-sm font-medium transition">
                        5. Orcamentos
                    </button>
                </nav>
            </div>

            <form action="{{ route('core.settings.update') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                {{-- ABA 1: Identidade Visual & Endereco --}}
                <div x-show="activeTab === 'general'" class="space-y-6">
                    
                    {{-- Upload da Logomarca e Seletor de Cores --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Logomarca --}}
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 flex items-center gap-4">
                            <div class="w-20 h-20 bg-white border border-gray-300 rounded-lg flex items-center justify-center overflow-hidden shadow-sm flex-shrink-0">
                                @if ($tenant->logo_url)
                                    <img src="{{ $tenant->logo_url }}" alt="Logo" class="max-h-full max-w-full object-contain">
                                @else
                                    <span class="text-xs font-bold text-gray-400 text-center">Sem Logo</span>
                                @endif
                            </div>
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-gray-900 uppercase">Logomarca do Estabelecimento</label>
                                <p class="text-[11px] text-gray-500">Exibida no PDV, romaneios A4 e cupons.</p>
                                <input type="file" name="logo" accept="image/*" class="mt-1 text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[11px] file:font-semibold file:bg-indigo-50 file:text-indigo-700">
                            </div>
                        </div>

                        {{-- Seletor de Cor Primaria (White-label) --}}
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-2">
                            <label class="block text-xs font-bold text-gray-900 uppercase">Cor Principal do Sistema (White-label)</label>
                            <p class="text-[11px] text-gray-500">Personaliza o cabecalho, botoes do PDV e crachas com a cor da sua empresa:</p>
                            
                            <div class="flex items-center gap-3 pt-1">
                                <input type="color" name="primary_color" x-model="selectedColor" class="w-10 h-10 p-0 border border-gray-300 rounded cursor-pointer">
                                <input type="text" x-model="selectedColor" class="w-24 rounded border-gray-300 text-xs font-mono font-bold uppercase py-1">
                                
                                {{-- Paletas Rapidas --}}
                                <div class="flex gap-1.5 pl-2 border-l border-gray-300">
                                    <button type="button" @click="selectedColor = '#4f46e5'" class="w-6 h-6 rounded-full bg-[#4f46e5] border" title="Indigo Padrao"></button>
                                    <button type="button" @click="selectedColor = '#059669'" class="w-6 h-6 rounded-full bg-[#059669] border" title="Verde Esmeralda"></button>
                                    <button type="button" @click="selectedColor = '#2563eb'" class="w-6 h-6 rounded-full bg-[#2563eb] border" title="Azul Royal"></button>
                                    <button type="button" @click="selectedColor = '#d97706'" class="w-6 h-6 rounded-full bg-[#d97706] border" title="Ambar / Laranja"></button>
                                    <button type="button" @click="selectedColor = '#e11d48'" class="w-6 h-6 rounded-full bg-[#e11d48] border" title="Vinho / Rose"></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Razao Social / Nome Empresarial *</label>
                            <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome Fantasia</label>
                            <input type="text" name="trade_name" value="{{ old('trade_name', $tenant->trade_name) }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">CNPJ / CPF *</label>
                            <input type="text" name="document" value="{{ old('document', $tenant->document) }}" required
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Inscricao Estadual (IE)</label>
                            <input type="text" name="state_registration" value="{{ old('state_registration', $tenant->state_registration) }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">WhatsApp de Atendimento</label>
                            <input type="text" name="whatsapp" value="{{ old('whatsapp', $tenant->whatsapp) }}" placeholder="(11) 99999-8888"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">CEP</label>
                            <input type="text" name="zip_code" value="{{ old('zip_code', $tenant->zip_code) }}" placeholder="00000-000"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Logradouro / Rua</label>
                            <input type="text" name="street" value="{{ old('street', $tenant->street) }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Numero</label>
                            <input type="text" name="number" value="{{ old('number', $tenant->number) }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bairro</label>
                            <input type="text" name="neighborhood" value="{{ old('neighborhood', $tenant->neighborhood) }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cidade / UF</label>
                            <div class="flex gap-2">
                                <input type="text" name="city" value="{{ old('city', $tenant->city) }}" placeholder="Cidade" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                                <input type="text" name="state" maxlength="2" value="{{ old('state', $tenant->state) }}" placeholder="UF" class="w-16 rounded-lg border-gray-300 shadow-sm text-sm uppercase text-center">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Codigo IBGE do Municipio</label>
                            <input type="text" name="ibge_city_code" maxlength="7" value="{{ old('ibge_city_code', $tenant->ibge_city_code) }}" placeholder="Ex: 3550308"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            <p class="mt-1 text-[11px] text-gray-400">
                                Exigido pela SEFAZ pra emitir NF-e/NFC-e. Consulte pelo nome da cidade em
                                <a href="https://www.ibge.gov.br/explica/codigos-dos-municipios.php" target="_blank" rel="noopener" class="underline">ibge.gov.br</a>.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ABA 2: Impressao & Documentos --}}
                <div x-show="activeTab === 'print'" class="space-y-6" style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-5 bg-white rounded-xl border-2 border-indigo-500 shadow-sm space-y-3">
                            <label class="block text-sm font-bold text-gray-900">Formato de Impressao Padrao *</label>
                            <p class="text-xs text-gray-500">Escolha o modelo compativel com o seu hardware instalado:</p>
                            <div class="space-y-2 pt-1">
                                <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border cursor-pointer hover:bg-indigo-50/50">
                                    <input type="radio" name="print_format" value="a4" @checked(old('print_format', $tenant->print_format) === 'a4') class="text-indigo-600">
                                    <div>
                                        <div class="text-sm font-bold text-gray-900">Folha A4 Convencional (Romaneio)</div>
                                        <div class="text-xs text-gray-500">Impressoras comuns de escritorio (Laser / Jato de Tinta).</div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border cursor-pointer hover:bg-indigo-50/50">
                                    <input type="radio" name="print_format" value="thermal_80" @checked(old('print_format', $tenant->print_format) === 'thermal_80') class="text-indigo-600">
                                    <div>
                                        <div class="text-sm font-bold text-gray-900">Bobina Termica 80mm (Cupom Nao Fiscal)</div>
                                        <div class="text-xs text-gray-500">Impressoras termicas padrao de cupom (Epson, Bematech, Elgin).</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm space-y-3">
                            <label class="block text-sm font-bold text-gray-900">Comportamento do Caixa ao Finalizar Venda *</label>
                            <p class="text-xs text-gray-500">Define o fluxo de tela logo apos concluir o pagamento:</p>
                            <select name="print_behavior" class="w-full rounded-lg border-gray-300 text-sm font-medium">
                                <option value="ask" @selected(old('print_behavior', $tenant->print_behavior) === 'ask')>
                                    Perguntar sempre: "Deseja imprimir comprovante?"
                                </option>
                                <option value="auto" @selected(old('print_behavior', $tenant->print_behavior) === 'auto')>
                                    Imprimir automaticamente sem perguntar
                                </option>
                                <option value="none" @selected(old('print_behavior', $tenant->print_behavior) === 'none')>
                                    Nao imprimir (Concluir e limpar caixa na tela)
                                </option>
                            </select>

                            <div class="pt-4">
                                <label class="block text-xs font-bold uppercase text-gray-700">Mensagem de Rodape do Cupom / Romaneio</label>
                                <textarea name="receipt_footer" rows="2" placeholder="Ex: Garantia de 90 dias com apresentacao deste cupom. Agradecemos a preferencia!"
                                          class="mt-1 block w-full rounded-lg border-gray-300 text-xs">{{ old('receipt_footer', $tenant->receipt_footer) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ABA 3: Fiscal & Certificado A1 --}}
                <div x-show="activeTab === 'fiscal'" class="space-y-6" style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm space-y-4">
                            <h3 class="font-bold text-gray-900">Certificado Digital A1 (.pfx / .p12)</h3>
                            <p class="text-xs text-gray-500">Utilizado para assinatura e emissao de NF-e e NFC-e junto a SEFAZ.</p>

                            @if ($tenant->certificate_path)
                                <div class="p-3 bg-emerald-50 rounded-lg border border-emerald-200 text-xs space-y-1">
                                    <div class="font-bold text-emerald-800 flex items-center gap-1.5">
                                        <span>&#10003;</span> Certificado A1 Ativo no Sistema
                                    </div>
                                    @if ($tenant->certificate_expires_at)
                                        <div class="text-emerald-700">
                                            Validade ate: <strong>{{ $tenant->certificate_expires_at->format('d/m/Y H:i') }}</strong>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Substituir / Enviar Certificado (.pfx)</label>
                                <input type="file" name="certificate_file" accept=".pfx,.p12" class="mt-1 text-xs text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Senha do Certificado A1</label>
                                <input type="password" name="certificate_password" placeholder="Digite apenas para alterar a senha"
                                       class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                                <p class="mt-1 text-[11px] text-gray-400">A senha e gravada com criptografia de ponta a ponta no banco.</p>
                            </div>
                        </div>

                        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm space-y-4">
                            <h3 class="font-bold text-gray-900">Ambiente de Emissao SEFAZ</h3>
                            <p class="text-xs text-gray-500">Controle de ambiente de transmissao fiscal:</p>
                            <select name="sefaz_environment" class="w-full rounded-lg border-gray-300 text-sm font-semibold">
                                <option value="homologation" @selected(old('sefaz_environment', $tenant->sefaz_environment) === 'homologation')>
                                    Homologacao (Ambiente de Testes sem valor fiscal)
                                </option>
                                <option value="production" @selected(old('sefaz_environment', $tenant->sefaz_environment) === 'production')>
                                    Producao (Emissao Oficial com validade juridica)
                                </option>
                            </select>

                            <div class="pt-2">
                                <label class="block text-xs font-semibold text-gray-700">Regime Tributario (CRT)</label>
                                <select name="crt" class="mt-1 w-full rounded-lg border-gray-300 text-sm font-semibold">
                                    <option value="1" @selected((int) old('crt', $tenant->crt) === 1)>Simples Nacional</option>
                                    <option value="2" @selected((int) old('crt', $tenant->crt) === 2)>Simples Nacional - excesso de sublimite</option>
                                    <option value="3" @selected((int) old('crt', $tenant->crt) === 3)>Regime Normal (Lucro Presumido/Real)</option>
                                </select>
                                <p class="mt-1 text-[11px] text-gray-400">Define se a NF-e usa CSOSN (Simples) ou CST (Normal) no ICMS de cada item.</p>
                            </div>

                            <div class="pt-2">
                                <label class="block text-xs font-semibold text-gray-700">Observacao Padrao da NF-e</label>
                                <textarea name="fiscal_default_observation" rows="2" placeholder="Texto que ja vem preenchido ao revisar uma nova NF-e (ex: garantia, isencao, dados complementares)"
                                          class="mt-1 block w-full rounded-lg border-gray-300 text-xs">{{ old('fiscal_default_observation', $tenant->fiscal_default_observation) }}</textarea>
                                <p class="mt-1 text-[11px] text-gray-400">Pode ser editado individualmente em cada nota antes de emitir.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ABA 4: Regras do Caixa (PDV) --}}
                <div x-show="activeTab === 'pdv'" class="space-y-6" style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm space-y-3">
                            <label class="block text-sm font-bold text-gray-900">Politica de Venda Sem Estoque (Saldo Negativo)</label>
                            <p class="text-xs text-gray-500">Define o comportamento do caixa quando um item nao possui saldo fisico registrado:</p>
                            <label class="inline-flex items-center pt-2">
                                <input type="checkbox" name="allow_negative_stock" value="1" @checked(old('allow_negative_stock', $tenant->allow_negative_stock))
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-800 font-medium">Permitir venda no PDV mesmo com saldo zerado (Alerta visual)</span>
                            </label>
                        </div>

                        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm space-y-3">
                            <label class="block text-sm font-bold text-gray-900">Obrigatoriedade de Vendedor Responsavel</label>
                            <p class="text-xs text-gray-500">Define se o caixa pode operar em venda direta ou se exige um vendedor:</p>
                            <label class="inline-flex items-center pt-2">
                                <input type="checkbox" name="require_seller_on_pos" value="1" @checked(old('require_seller_on_pos', $tenant->require_seller_on_pos))
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-800 font-medium">Exigir selecao de Vendedor no PDV (Bloqueia Venda Direta)</span>
                            </label>
                        </div>

                        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm space-y-3">
                            <label class="block text-sm font-bold text-gray-900">Alteracao de Preco no PDV</label>
                            <p class="text-xs text-gray-500">Define se o operador de caixa pode editar o valor unitario de um item ja no carrinho:</p>
                            <label class="inline-flex items-center pt-2">
                                <input type="checkbox" name="allow_price_edit_pos" value="1" @checked(old('allow_price_edit_pos', $tenant->allow_price_edit_pos))
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-800 font-medium">Permitir editar o preco do item diretamente no PDV</span>
                            </label>
                        </div>

                        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm space-y-3 md:col-span-2">
                            <label class="block text-sm font-bold text-gray-900">Limite Maximo de Desconto sem Senha de Gerente (%)</label>
                            <p class="text-xs text-gray-500">Percentual maximo de desconto que o operador de caixa pode conceder:</p>
                            <input type="number" step="0.01" min="0" max="100" name="max_discount_percentage" 
                                   value="{{ old('max_discount_percentage', $tenant->max_discount_percentage) }}"
                                   class="mt-1 block w-32 rounded-lg border-gray-300 text-sm font-bold text-indigo-600">
                        </div>
                    </div>
                </div>

                {{-- ABA 5: Personalizacao de Orcamentos --}}
                <div x-show="activeTab === 'orcamentos'" class="space-y-6" style="display: none;">
                    <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-900">Colunas e Informacoes Exibidas na Proposta Comercial</label>
                            <p class="text-xs text-gray-500">Escolha o que aparece no documento impresso enviado ao cliente. Os dados continuam sendo cadastrados normalmente, independente do que estiver marcado aqui.</p>
                        </div>

                        @php
                            $quoteFields = [
                                'quote_show_ncm' => 'Coluna NCM',
                                'quote_show_icms' => 'Coluna ICMS (%)',
                                'quote_show_icms_st' => 'Coluna e Total de ICMS ST',
                                'quote_show_lead_time' => 'Coluna Faturamento em (prazo por item)',
                                'quote_show_payment_condition' => 'Linha Condicoes de Pagamento',
                                'quote_show_delivery_term' => 'Linha Prazo de Entrega',
                                'quote_show_freight' => 'Linha Frete',
                            ];
                        @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach ($quoteFields as $field => $label)
                                <label class="inline-flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $tenant->{$field}))
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-800 font-medium">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-900">Valores Padrao ao Criar um Orcamento Novo</label>
                            <p class="text-xs text-gray-500">Pre-preenchem o formulario, mas continuam editaveis em cada orcamento individualmente.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Condicao de Pagamento Padrao</label>
                                <input type="text" name="quote_default_payment_condition" value="{{ old('quote_default_payment_condition', $tenant->quote_default_payment_condition) }}" placeholder="Ex: A vista"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Prazo de Entrega Padrao</label>
                                <input type="text" name="quote_default_delivery_term" value="{{ old('quote_default_delivery_term', $tenant->quote_default_delivery_term) }}" placeholder="Ex: Sujeito a confirmacao de estoque"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Frete Padrao</label>
                                <input type="text" name="quote_default_freight_info" value="{{ old('quote_default_freight_info', $tenant->quote_default_freight_info) }}" placeholder="Ex: Sem frete"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Faturamento Padrao por Item (dias)</label>
                                <input type="number" step="1" min="0" name="quote_default_lead_time_days" value="{{ old('quote_default_lead_time_days', $tenant->quote_default_lead_time_days) }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Validade Padrao da Proposta (dias) *</label>
                                <input type="number" step="1" min="0" required name="quote_default_validity_days" value="{{ old('quote_default_validity_days', $tenant->quote_default_validity_days) }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Botao de Salvar --}}
                <div class="pt-6 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 transition shadow-sm">
                        Salvar Configuracoes
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-core::layouts.master>