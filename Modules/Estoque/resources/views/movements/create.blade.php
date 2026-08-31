<x-core::layouts.master>
    <div class="p-6 max-w-4xl mx-auto space-y-6" 
         x-data="movementFormEngine({{ Js::from($products) }})">
        <div>
            <a href="{{ route('estoque.movimentacoes.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 uppercase tracking-wider">
                &larr; Voltar para o Kardex
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Lancar Movimentacao Manual de Estoque</h1>
            <p class="text-sm text-gray-500">Busca rapida de produtos por Codigo, Nome, SKU ou Codigo de Barras com priorizacao automatica</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">
            <form action="{{ route('estoque.movimentacoes.store') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="product_id" :value="selectedProduct ? selectedProduct.id : ''" required>

                {{-- Barra de Busca Inteligente de Produtos --}}
                <div class="space-y-2 relative" @click.away="isOpenDropdown = false">
                    <label class="block text-xs font-bold uppercase text-gray-700">Buscar Produto no Catalogo *</label>
                    <div class="relative">
                        <input type="text" 
                               x-model="searchQuery" 
                               @input="isOpenDropdown = true" 
                               @focus="isOpenDropdown = true"
                               @keydown.enter.prevent="selectFirstMatch()"
                               placeholder="Digite o Codigo (#1), Nome, SKU ou escaneie o Codigo de Barras..." 
                               class="w-full rounded-lg border-gray-300 pl-10 py-2.5 text-sm font-semibold focus:ring-indigo-500 focus:border-indigo-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 font-bold font-mono">
                            🔍
                        </div>
                    </div>

                    {{-- Lista Suspensa Reativa de Resultados com Ordenacao por Relevancia --}}
                    <div x-show="isOpenDropdown && filteredProducts.length > 0" 
                         class="absolute left-0 right-0 mt-1 bg-white rounded-xl border border-gray-200 shadow-2xl max-h-60 overflow-y-auto z-50 divide-y text-xs font-medium" style="display: none;">
                        <template x-for="p in filteredProducts" :key="p.id">
                            <div @click="selectProduct(p)" class="p-3 hover:bg-indigo-50 cursor-pointer flex justify-between items-center transition">
                                <div>
                                    <span class="font-bold text-indigo-600 font-mono" x-text="'#' + (p.code || p.id)"></span>
                                    <span class="font-bold text-gray-900 ml-1" x-text="p.name"></span>
                                    <span class="text-[10px] text-gray-400 ml-1" x-text="'(SKU: ' + (p.sku || 'N/A') + ')'"></span>
                                </div>
                                <div class="text-right">
                                    <span class="font-bold font-mono" :class="p.current_stock <= 0 ? 'text-rose-600' : 'text-emerald-600'" x-text="formatNumber(p.current_stock) + ' ' + p.unit"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Card de Confirmacao Visual do Produto Selecionado --}}
                <div x-show="selectedProduct" class="p-4 bg-indigo-50/60 rounded-xl border border-indigo-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 bg-indigo-600 text-white rounded font-mono font-bold text-xs" x-text="'#' + (selectedProduct ? (selectedProduct.code || selectedProduct.id) : '')"></span>
                            <h3 class="font-bold text-gray-900 text-sm" x-text="selectedProduct ? selectedProduct.name : ''"></h3>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="'SKU: ' + (selectedProduct ? (selectedProduct.sku || 'N/A') : '') + ' | Unidade: ' + (selectedProduct ? selectedProduct.unit : '')"></p>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="block text-[10px] font-bold uppercase text-gray-500">Saldo Fisico em Estoque</span>
                        <span class="text-base font-mono font-extrabold text-indigo-700" x-text="selectedProduct ? (formatNumber(selectedProduct.current_stock) + ' ' + selectedProduct.unit) : ''"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Tipo de Movimentacao --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase">Tipo de Movimentacao *</label>
                        <select name="type" x-model="movementType" required
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm font-semibold">
                            <option value="adjustment">Ajuste de Balanco / Inventario (Define Novo Saldo)</option>
                            <option value="in">Entrada / Compra Avulsa (+)</option>
                            <option value="out">Saida / Consumo Interno (-)</option>
                            <option value="loss">Avaria / Perda / Vencimento (-)</option>
                        </select>
                    </div>

                    {{-- Quantidade --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase">
                            <span x-text="movementType === 'adjustment' ? 'Novo Saldo Contado Fisicamente *' : 'Quantidade da Operacao *'"></span>
                        </label>
                        <div class="mt-1 relative rounded-lg shadow-sm">
                            <input type="number" step="0.001" min="0" name="quantity" x-model.number="inputQuantity" required
                                   class="block w-full rounded-lg border-gray-300 pr-12 text-sm font-bold text-gray-900">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs font-bold text-gray-400" x-text="selectedProduct ? selectedProduct.unit : 'UN'"></div>
                        </div>

                        {{-- Delta de Balanco --}}
                        <div x-show="movementType === 'adjustment' && selectedProduct" class="mt-1 text-xs font-bold text-indigo-600">
                            <span x-text="'Diferenca resultante: ' + (inputQuantity - (selectedProduct ? selectedProduct.current_stock : 0) >= 0 ? '+' : '') + formatNumber(inputQuantity - (selectedProduct ? selectedProduct.current_stock : 0)) + ' ' + (selectedProduct ? selectedProduct.unit : '')"></span>
                        </div>
                    </div>

                    {{-- Custo Unitario Opcional --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase">Custo Unitario (R$) (Opcional)</label>
                        <input type="number" step="0.01" min="0" name="unit_price" :placeholder="selectedProduct ? ('Padrao: R$ ' + formatNumber(selectedProduct.cost_price)) : 'R$ 0,00'"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    </div>

                    {{-- Justificativa --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase">Motivo / Justificativa *</label>
                        <textarea name="notes" rows="2" required placeholder="Ex: Contagem periodica de estoque, correcao de saldo, avaria no transporte..."
                                  class="mt-1 block w-full rounded-lg border-gray-300 text-xs"></textarea>
                    </div>
                </div>

                <div class="pt-4 border-t flex justify-end gap-3">
                    <a href="{{ route('estoque.movimentacoes.index') }}" class="px-4 py-2 bg-gray-100 rounded-lg text-xs font-bold text-gray-600">Cancelar</a>
                    <button type="submit" :disabled="!selectedProduct" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition shadow-sm disabled:bg-gray-300">
                        Gravar no Kardex
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function movementFormEngine(products) {
            return {
                catalog: products || [],
                searchQuery: '',
                selectedProduct: null,
                isOpenDropdown: false,
                movementType: 'adjustment',
                inputQuantity: 1,

                get filteredProducts() {
                    if (!this.searchQuery) return this.catalog.slice(0, 10);
                    
                    let raw = this.searchQuery.trim();
                    let clean = raw.toLowerCase().replace(/^#/, '');
                    let isNumeric = !isNaN(clean) && clean !== '';

                    let scored = [];

                    for (let i = 0; i < this.catalog.length; i++) {
                        let p = this.catalog[i];
                        let score = 0;

                        let codeStr = (p.code !== undefined && p.code !== null) ? p.code.toString() : '';
                        let idStr = p.id ? p.id.toString() : '';
                        let nameLower = (p.name || '').toLowerCase();
                        let skuLower = (p.sku || '').toLowerCase();
                        let barcodeStr = p.barcode ? p.barcode.toString() : '';

                        // Prioridade 1: Correspondencia exata por Codigo Simples (#2 ou 2) ou ID
                        if (isNumeric && (codeStr === clean || idStr === clean)) {
                            score = 1000;
                        } 
                        // Prioridade 2: Correspondencia exata por Codigo de Barras (EAN) ou SKU
                        else if (barcodeStr === raw || barcodeStr === clean || skuLower === clean) {
                            score = 800;
                        }
                        // Prioridade 3: Prefixo no Nome (comeca com o termo)
                        else if (nameLower.startsWith(clean)) {
                            score = 500;
                        }
                        // Prioridade 4: Prefixo em SKU ou Codigo de Barras
                        else if (skuLower.startsWith(clean) || barcodeStr.startsWith(clean)) {
                            score = 400;
                        }
                        // Prioridade 5: Substring em Nome, SKU ou Codigo de Barras
                        else if (nameLower.includes(clean) || skuLower.includes(clean) || barcodeStr.includes(clean)) {
                            score = 100;
                        }

                        if (score > 0) {
                            scored.push({ product: p, score: score });
                        }
                    }

                    // Ordenacao decrescente por score de relevancia
                    scored.sort((a, b) => b.score - a.score);

                    return scored.map(item => item.product).slice(0, 15);
                },

                selectProduct(p) {
                    this.selectedProduct = p;
                    this.searchQuery = '#' + (p.code || p.id) + ' - ' + p.name;
                    this.isOpenDropdown = false;
                    if (this.movementType === 'adjustment') {
                        this.inputQuantity = parseFloat(p.current_stock) || 0;
                    }
                },

                selectFirstMatch() {
                    if (this.filteredProducts.length > 0) {
                        this.selectProduct(this.filteredProducts[0]);
                    }
                },

                formatNumber(val) {
                    return parseFloat(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 3 });
                }
            }
        }
    </script>
</x-core::layouts.master>