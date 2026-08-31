<div x-data="productMarkupCalculator({
    cost: {{ (float) old('cost_price', $product->cost_price ?? 0) }},
    marginRetail: {{ (float) old('margin_retail', $product->margin_retail ?? 0) }},
    priceRetail: {{ (float) old('price_retail', $product->price_retail ?? 0) }},
    marginResale: {{ (float) old('margin_resale', $product->margin_resale ?? 0) }},
    priceResale: {{ (float) old('price_resale', $product->price_resale ?? 0) }},
    marginWholesale: {{ (float) old('margin_wholesale', $product->margin_wholesale ?? 0) }},
    priceWholesale: {{ (float) old('price_wholesale', $product->price_wholesale ?? 0) }}
})" class="space-y-4">

    {{-- Navegacao por 2 Abas Compactas --}}
    <div x-data="{ activeTab: 'details' }" class="space-y-4">
        <div class="border-b border-gray-200 bg-gray-50/50 px-4 rounded-t-xl">
            <nav class="-mb-px flex space-x-6">
                <button type="button" 
                        @click="activeTab = 'details'" 
                        :class="activeTab === 'details' ? 'border-indigo-600 text-indigo-600 font-extrabold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-3 px-2 border-b-2 text-xs uppercase tracking-wider transition">
                    1. Detalhes do Produto, Estoque & Precificacao
                </button>
                <button type="button" 
                        @click="activeTab = 'tax'" 
                        :class="activeTab === 'tax' ? 'border-indigo-600 text-indigo-600 font-extrabold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-3 px-2 border-b-2 text-xs uppercase tracking-wider transition">
                    2. Informacoes Fiscais SEFAZ
                </button>
            </nav>
        </div>

        {{-- ABA 1: Detalhes, Estoque e Precificacao Integrados (Sem Rolagem) --}}
        <div x-show="activeTab === 'details'" class="space-y-4">
            
            {{-- Linha 1: Identificacao Comercial --}}
            <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-gray-600 uppercase">Codigo (PDV)</label>
                    <input type="text" readonly 
                           value="{{ (isset($product) && $product->getAttribute('code')) ? '#' . $product->getAttribute('code') : '# AUTO' }}"
                           class="mt-0.5 block w-full rounded-lg border-gray-200 bg-gray-100 font-mono font-bold text-indigo-600 text-xs py-1.5 cursor-not-allowed">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase">Nome do Produto / Descricao *</label>
                    <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
                           placeholder="Ex: Compressor Rotativo 12000 BTU R410A"
                           class="mt-0.5 block w-full rounded-lg border-gray-300 shadow-sm text-xs py-1.5 focus:ring-indigo-500">
                    @error('name') <p class="text-[10px] text-rose-600 mt-0.5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase">Marca / Fabricante</label>
                    <input type="text" name="brand" value="{{ old('brand', $product->brand ?? '') }}" placeholder="Ex: Elgin, Daikin, Gree"
                           class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1.5">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase">Unidade *</label>
                    <select name="unit" required class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1.5">
                        @foreach (['UN', 'KG', 'MT', 'CX', 'PC', 'LT', 'PAR', 'M2'] as $u)
                            <option value="{{ $u }}" @selected(old('unit', $product->unit ?? 'UN') == $u)>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <div class="flex justify-between items-center">
                        <label class="block text-[11px] font-bold text-gray-700 uppercase">Categoria</label>
                        <a href="{{ route('estoque.categorias.create') }}" target="_blank" class="text-[10.5px] font-bold text-indigo-600 hover:text-indigo-800">+ Nova Categoria</a>
                    </div>
                    <select name="category_id" class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1.5">
                        <option value="">Sem Categoria</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id ?? '') == $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase">Codigo SKU Interno</label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" placeholder="Ex: COMP-12K-R410"
                           class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1.5">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase">Codigo de Barras (EAN-13)</label>
                    <input type="text" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}" placeholder="Ex: 7891234567890"
                           class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1.5">
                </div>
            </div>

            {{-- Linha 2: Estoque, Localizacao Fisica e Garantia (Campos Opcionais) --}}
            <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 grid grid-cols-1 sm:grid-cols-4 gap-3 items-center">
                <div>
                    <label class="block text-[10.5px] font-bold text-gray-700 uppercase">Estoque Minimo (Alerta)</label>
                    <input type="number" step="0.001" min="0" name="min_stock" value="{{ old('min_stock', $product->min_stock ?? 0) }}"
                           class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1">
                </div>

                <div>
                    <label class="block text-[10.5px] font-bold text-gray-700 uppercase">Localizacao (Prateleira)</label>
                    <input type="text" name="location" value="{{ old('location', $product->location ?? '') }}" placeholder="Ex: Corredor A, Prat. 3"
                           class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1">
                </div>

                <div>
                    <label class="block text-[10.5px] font-bold text-gray-700 uppercase">Garantia (Meses)</label>
                    <input type="number" min="0" name="warranty_months" value="{{ old('warranty_months', $product->warranty_months ?? '') }}" placeholder="Ex: 12"
                           class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1">
                </div>

                @if (!isset($product))
                    <div class="p-2 bg-emerald-50/80 rounded-lg border border-emerald-200">
                        <label class="block text-[10.5px] font-bold text-emerald-900 uppercase">Estoque Inicial</label>
                        <input type="number" step="0.001" min="0" name="initial_stock" value="{{ old('initial_stock', 0) }}"
                               class="mt-0.5 block w-full rounded-lg border-emerald-300 text-xs font-bold text-emerald-800 py-1">
                    </div>
                @else
                    <div class="flex items-center justify-between p-2 bg-white rounded-lg border">
                        <span class="text-xs font-bold text-gray-600 uppercase">Saldo Fisico:</span>
                        <span class="font-mono font-extrabold text-sm text-indigo-600">
                            {{ number_format((float) $product->current_stock, 3, ',', '.') }} {{ $product->unit }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Linha 3: Formacao de Precos e 3 Tabelas com Custo Central --}}
            <div class="p-3.5 bg-white rounded-xl border border-gray-200 shadow-sm space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b pb-2">
                    <div>
                        <h3 class="text-xs font-bold text-gray-900 uppercase">Tabelas de Preco & Margens de Lucro</h3>
                        <p class="text-[10.5px] text-gray-500">Revenda e Atacado adotam automaticamente o Varejo se nao preenchidos.</p>
                    </div>
                    <div class="w-full sm:w-56">
                        <label class="block text-[10.5px] font-extrabold text-indigo-900 uppercase">Preco de Custo Base (R$) *</label>
                        <input type="number" step="0.01" min="0" name="cost_price" x-model.number="cost" @input="recalcAllPrices()" required
                               class="mt-0.5 block w-full rounded-lg border-indigo-300 font-bold text-gray-900 bg-indigo-50/40 text-sm py-1">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    {{-- Varejo --}}
                    <div class="p-3 bg-indigo-50/40 rounded-lg border-2 border-indigo-500 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-gray-900">Varejo (Principal)</span>
                            <span class="text-[9.5px] px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded font-bold uppercase">Consumidor</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[9.5px] text-gray-500 uppercase">Markup %</label>
                                <input type="number" step="0.01" name="margin_retail" x-model.number="marginRetail" @input="updatePrice('retail')"
                                       class="w-full rounded border-gray-300 text-xs py-1">
                            </div>
                            <div>
                                <label class="block text-[9.5px] font-bold text-indigo-900 uppercase">Preco Final *</label>
                                <input type="number" step="0.01" min="0" name="price_retail" x-model.number="priceRetail" @input="updateMargin('retail')" required
                                       class="w-full rounded border-gray-300 font-bold text-indigo-600 text-xs py-1">
                            </div>
                        </div>
                        <p class="text-[10.5px] text-emerald-600 font-bold" x-text="'Lucro: R$ ' + getProfit('retail')"></p>
                    </div>

                    {{-- Revenda --}}
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-gray-900">Revenda</span>
                            <span class="text-[9.5px] px-1.5 py-0.5 bg-gray-200 text-gray-700 rounded font-bold uppercase">Parceiros</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[9.5px] text-gray-500 uppercase">Markup %</label>
                                <input type="number" step="0.01" name="margin_resale" x-model.number="marginResale" @input="updatePrice('resale')"
                                       class="w-full rounded border-gray-300 text-xs py-1">
                            </div>
                            <div>
                                <label class="block text-[9.5px] text-gray-700 uppercase">Preco Final</label>
                                <input type="number" step="0.01" min="0" name="price_resale" x-model.number="priceResale" @input="updateMargin('resale')"
                                       class="w-full rounded border-gray-300 font-semibold text-gray-800 text-xs py-1">
                            </div>
                        </div>
                        <p class="text-[10.5px] text-emerald-600 font-semibold" x-text="'Lucro: R$ ' + getProfit('resale')"></p>
                    </div>

                    {{-- Atacado --}}
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-gray-900">Atacado</span>
                            <span class="text-[9.5px] px-1.5 py-0.5 bg-gray-200 text-gray-700 rounded font-bold uppercase">Volume</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[9.5px] text-gray-500 uppercase">Markup %</label>
                                <input type="number" step="0.01" name="margin_wholesale" x-model.number="marginWholesale" @input="updatePrice('wholesale')"
                                       class="w-full rounded border-gray-300 text-xs py-1">
                            </div>
                            <div>
                                <label class="block text-[9.5px] text-gray-700 uppercase">Preco Final</label>
                                <input type="number" step="0.01" min="0" name="price_wholesale" x-model.number="priceWholesale" @input="updateMargin('wholesale')"
                                       class="w-full rounded border-gray-300 font-semibold text-gray-800 text-xs py-1">
                            </div>
                        </div>
                        <p class="text-[10.5px] text-emerald-600 font-semibold" x-text="'Lucro: R$ ' + getProfit('wholesale')"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ABA 2: Informacoes Fiscais SEFAZ --}}
        <div x-show="activeTab === 'tax'" class="pt-2 space-y-4" style="display: none;">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase">NCM (8 digitos) *</label>
                    <input type="text" name="ncm" maxlength="8" value="{{ old('ncm', $product->ncm ?? '') }}" required
                           placeholder="Ex: 84151011" class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1.5">
                    @error('ncm') <p class="text-[10px] text-rose-600 mt-0.5">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase">CEST (7 digitos)</label>
                    <input type="text" name="cest" maxlength="7" value="{{ old('cest', $product->cest ?? '') }}"
                           class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1.5">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase">Origem da Mercadoria *</label>
                    <select name="origin" required class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1.5">
                        @foreach ([
                            0 => '0 - Nacional',
                            1 => '1 - Estrangeira - Importacao Direta',
                            2 => '2 - Estrangeira - Adquirida no Mercado Interno'
                        ] as $key => $label)
                            <option value="{{ $key }}" @selected(old('origin', $product->origin ?? 0) == $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase">CFOP Padrao *</label>
                    <input type="text" name="cfop_default" maxlength="4" value="{{ old('cfop_default', $product->cfop_default ?? '5102') }}" required
                           class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1.5">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase">CSOSN (Simples)</label>
                    <input type="text" name="csosn" maxlength="4" value="{{ old('csosn', $product->csosn ?? '102') }}"
                           class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1.5">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase">CST PIS *</label>
                    <input type="text" name="cst_pis" maxlength="2" value="{{ old('cst_pis', $product->cst_pis ?? '07') }}" required
                           class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1.5">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase">CST COFINS *</label>
                    <input type="text" name="cst_cofins" maxlength="2" value="{{ old('cst_cofins', $product->cst_cofins ?? '07') }}" required
                           class="mt-0.5 block w-full rounded-lg border-gray-300 text-xs py-1.5">
                </div>
            </div>
        </div>
    </div>

    {{-- Barra de Acao Inferior --}}
    <div class="pt-4 border-t border-gray-200 flex items-center justify-between">
        <label class="inline-flex items-center">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))
                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
            <span class="ml-2 text-xs font-bold text-gray-700">Produto Ativo para Vendas</span>
        </label>

        <div class="flex gap-2">
            <a href="{{ route('estoque.produtos.index') }}" 
               class="px-4 py-2 bg-gray-100 rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-200 transition">
                Cancelar
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm">
                Salvar Produto
            </button>
        </div>
    </div>
</div>

<script>
    function productMarkupCalculator(config) {
        return {
            cost: parseFloat(config.cost) || 0,
            marginRetail: parseFloat(config.marginRetail) || 0,
            priceRetail: parseFloat(config.priceRetail) || 0,
            marginResale: parseFloat(config.marginResale) || 0,
            priceResale: parseFloat(config.priceResale) || 0,
            marginWholesale: parseFloat(config.marginWholesale) || 0,
            priceWholesale: parseFloat(config.priceWholesale) || 0,

            recalcAllPrices() {
                this.updatePrice('retail');
                if (this.marginResale > 0) this.updatePrice('resale');
                if (this.marginWholesale > 0) this.updatePrice('wholesale');
            },

            updatePrice(table) {
                if (this.cost <= 0) return;
                if (table === 'retail') {
                    this.priceRetail = parseFloat((this.cost * (1 + (this.marginRetail / 100))).toFixed(2));
                } else if (table === 'resale') {
                    this.priceResale = parseFloat((this.cost * (1 + (this.marginResale / 100))).toFixed(2));
                } else if (table === 'wholesale') {
                    this.priceWholesale = parseFloat((this.cost * (1 + (this.marginWholesale / 100))).toFixed(2));
                }
            },

            updateMargin(table) {
                if (this.cost <= 0) return;
                if (table === 'retail' && this.priceRetail > 0) {
                    this.marginRetail = parseFloat((((this.priceRetail - this.cost) / this.cost) * 100).toFixed(2));
                } else if (table === 'resale' && this.priceResale > 0) {
                    this.marginResale = parseFloat((((this.priceResale - this.cost) / this.cost) * 100).toFixed(2));
                } else if (table === 'wholesale' && this.priceWholesale > 0) {
                    this.marginWholesale = parseFloat((((this.priceWholesale - this.cost) / this.cost) * 100).toFixed(2));
                }
            },

            getProfit(table) {
                let price = 0;
                if (table === 'retail') price = this.priceRetail;
                if (table === 'resale') price = this.priceResale > 0 ? this.priceResale : this.priceRetail;
                if (table === 'wholesale') price = this.priceWholesale > 0 ? this.priceWholesale : this.priceRetail;
                let diff = price - this.cost;
                return (diff > 0 ? diff : 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    }
</script>