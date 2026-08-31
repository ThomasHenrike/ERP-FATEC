@php
    $existingItems = ($orcamento?->items ?? collect())->map(fn ($item) => [
        'product_id' => $item->product_id,
        'code' => $item->product?->code ?? $item->product_id,
        'name' => $item->product?->name ?? 'Produto removido',
        'sku' => $item->product?->sku ?? 'N/A',
        'unit' => $item->product?->unit ?? 'UN',
        'unit_price' => (float) $item->unit_price,
        'quantity' => (float) $item->quantity,
        'total_price' => (float) $item->total_price,
        'icms_percent' => (float) $item->icms_percent,
        'icms_st_value' => (float) $item->icms_st_value,
        'lead_time_days' => $item->lead_time_days,
    ])->values();
@endphp

<div x-data="orcamentoFormHelper(
        {{ Js::from($customers) }},
        {{ Js::from($products) }},
        {{ Js::from($existingItems) }},
        {{ Js::from($tenant->quote_default_lead_time_days ?? null) }}
    )" @product-selected="addProduct($event.detail)"
       @customer-selected="customerId = $event.detail.id; onCustomerSelected()"
       data-orcamento-root class="space-y-6">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-700 uppercase">Cliente *</label>
            <input type="hidden" name="customer_id" :value="customerId" required>
            <x-core::customer-search :customers="$customers" placeholder="Buscar cliente por nome, documento ou telefone..." />
            <div x-show="customerId" x-cloak class="mt-1.5 inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 border border-indigo-200 rounded-full text-xs font-semibold text-indigo-700">
                <span x-text="customers.find(c => c.id == customerId)?.name || ''"></span>
                <button type="button" @click="customerId = ''" class="text-indigo-400 hover:text-indigo-700 font-bold">&times;</button>
            </div>
            @error('customer_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Tabela de Preco</label>
            <select x-model="priceTable" @change="recalcAllPrices()" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm font-semibold text-indigo-700">
                <option value="retail">Varejo (Consumidor)</option>
                <option value="resale">Revenda (Parceiros/Tecnicos)</option>
                <option value="wholesale">Atacado (Volume)</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Validade da Proposta</label>
            <input type="date" name="valid_until"
                   value="{{ old('valid_until', $orcamento?->valid_until?->format('Y-m-d') ?? now()->addDays($tenant->quote_default_validity_days ?? 5)->format('Y-m-d')) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>
    </div>

    {{-- Adicionar Itens --}}
    <div class="border-t pt-4">
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Adicionar Produto</label>
        <div class="w-full md:w-1/2">
            <x-core::product-search :products="$products" placeholder="Buscar por nome, SKU ou codigo para adicionar..." />
        </div>
        <p class="mt-1 text-[11px] text-gray-400">Dica: digite e aperte Enter para adicionar - o foco vai pra Quantidade, depois Valor Unitario, e volta pra busca.</p>

        <div class="mt-3 overflow-x-auto border border-gray-200 rounded-lg" x-show="items.length > 0" x-cloak>
            <table class="min-w-[720px] w-full text-xs">
                <thead class="bg-gray-50 text-gray-600 font-bold uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left">Produto</th>
                        <th class="px-3 py-2 w-20">Qtd.</th>
                        <th class="px-3 py-2 w-28">Vlr. Unit.</th>
                        <th class="px-3 py-2 w-24">ICMS %</th>
                        <th class="px-3 py-2 w-28">Vlr. ICMS ST</th>
                        <th class="px-3 py-2 w-28">Faturamento (dias)</th>
                        <th class="px-3 py-2 w-28 text-right">Total</th>
                        <th class="px-3 py-2 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(item, index) in items" :key="item.product_id">
                        <tr>
                            <td class="px-3 py-2 font-semibold text-gray-800" x-text="item.name"></td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.001" min="0.001" x-model.number="item.quantity" @input="recalcItem(item)"
                                       @keydown.enter.prevent="focusNextInRow($event, '.item-price-input')"
                                       class="item-qty-input w-full rounded border-gray-300 text-xs">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.01" min="0" x-model.number="item.unit_price" @input="recalcItem(item)"
                                       @keydown.enter.prevent="focusProductSearch($el)"
                                       class="item-price-input w-full rounded border-gray-300 text-xs font-mono">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.01" min="0" max="100" x-model.number="item.icms_percent"
                                       class="w-full rounded border-gray-300 text-xs font-mono">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.01" min="0" x-model.number="item.icms_st_value"
                                       class="w-full rounded border-gray-300 text-xs font-mono">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="1" min="0" x-model.number="item.lead_time_days"
                                       class="w-full rounded border-gray-300 text-xs">
                            </td>
                            <td class="px-3 py-2 text-right font-mono font-bold text-gray-900" x-text="'R$ ' + item.total_price.toFixed(2).replace('.', ',')"></td>
                            <td class="px-3 py-2 text-center">
                                <button type="button" @click="removeItem(index)" class="text-rose-600 hover:text-rose-800 font-bold">&times;</button>
                            </td>

                            {{-- Campos ocultos enviados no submit do formulario --}}
                            <template x-for="field in ['product_id','quantity','unit_price','total_price','icms_percent','icms_st_value','lead_time_days']" :key="field">
                                <input type="hidden" :name="'items[' + index + '][' + field + ']'" :value="item[field]">
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <p x-show="items.length === 0" class="mt-2 text-xs text-gray-400 italic">Nenhum item adicionado ainda.</p>
    </div>

    {{-- Totais --}}
    <div class="flex justify-end">
        <div class="w-full md:w-80 bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-1 text-sm">
            <div class="flex justify-between text-gray-600">
                <span>Subtotal:</span>
                <span class="font-mono" x-text="'R$ ' + subtotal.toFixed(2).replace('.', ',')"></span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>Total ICMS ST:</span>
                <span class="font-mono" x-text="'R$ ' + totalIcmsSt.toFixed(2).replace('.', ',')"></span>
            </div>
            <div class="flex justify-between font-bold text-gray-900 text-base pt-1 border-t">
                <span>Total Geral:</span>
                <span class="font-mono text-indigo-700" x-text="'R$ ' + grandTotal.toFixed(2).replace('.', ',')"></span>
            </div>
        </div>
    </div>

    {{-- Condicoes Comerciais --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-t pt-4">
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Condicao de Pagamento</label>
            <input type="text" name="payment_condition" value="{{ old('payment_condition', $orcamento->payment_condition ?? $tenant->quote_default_payment_condition ?? '') }}" placeholder="Ex: Cartao de Credito - 1x"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Prazo de Entrega</label>
            <input type="text" name="delivery_term" value="{{ old('delivery_term', $orcamento->delivery_term ?? $tenant->quote_default_delivery_term ?? '') }}" placeholder="Ex: Sujeito a confirmacao de estoque"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Frete</label>
            <input type="text" name="freight_info" value="{{ old('freight_info', $orcamento->freight_info ?? $tenant->quote_default_freight_info ?? '') }}" placeholder="Ex: Sem frete"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
        </div>
        <div class="md:col-span-3">
            <label class="block text-xs font-bold text-gray-700 uppercase">Observacoes</label>
            <textarea name="notes" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">{{ old('notes', $orcamento->notes ?? '') }}</textarea>
        </div>
    </div>

    <div class="pt-4 border-t flex items-center justify-end gap-2">
        <a href="{{ route('orcamentos.index') }}" class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-semibold text-gray-600">Cancelar</a>
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-sm">Salvar Orcamento</button>
    </div>
</div>

<script>
    function orcamentoFormHelper(customers, products, existingItems, defaultLeadTimeDays) {
        return {
            customers,
            products,
            defaultLeadTimeDays,
            customerId: '{{ old('customer_id', $orcamento->customer_id ?? '') }}',
            priceTable: '{{ old('price_table', 'retail') }}',
            items: existingItems,

            onCustomerSelected() {
                let customer = this.customers.find(c => c.id == this.customerId);
                if (customer && customer.default_price_table) {
                    this.priceTable = customer.default_price_table;
                    this.recalcAllPrices();
                }
            },

            getItemPrice(product) {
                if (this.priceTable === 'resale') return parseFloat(product.price_resale || product.price_retail);
                if (this.priceTable === 'wholesale') return parseFloat(product.price_wholesale || product.price_retail);
                return parseFloat(product.price_retail);
            },

            addProduct(product) {
                if (!product || !product.id) return;

                let existing = this.items.find(i => i.product_id === product.id);
                if (existing) {
                    existing.quantity += 1;
                    this.recalcItem(existing);
                } else {
                    let price = this.getItemPrice(product);
                    this.items.push({
                        product_id: product.id,
                        code: product.code || product.id,
                        name: product.name,
                        sku: product.sku || 'N/A',
                        unit: product.unit,
                        unit_price: price,
                        quantity: 1,
                        total_price: price,
                        icms_percent: parseFloat(product.icms_rate || 0),
                        icms_st_value: 0,
                        lead_time_days: this.defaultLeadTimeDays,
                    });
                }

                // Fluxo de teclado: apos adicionar, foca a Quantidade da ultima linha
                this.$nextTick(() => {
                    let inputs = this.$el.querySelectorAll('.item-qty-input');
                    let last = inputs[inputs.length - 1];
                    if (last) { last.focus(); last.select(); }
                });
            },

            focusNextInRow(event, selector) {
                let row = event.target.closest('tr');
                let next = row ? row.querySelector(selector) : null;
                if (next) { next.focus(); next.select(); }
            },

            focusProductSearch(triggerEl) {
                this.$nextTick(() => {
                    let root = triggerEl ? triggerEl.closest('[data-orcamento-root]') : null;
                    let input = root ? root.querySelector('.product-search-input') : null;
                    if (input) input.focus();
                });
            },

            removeItem(index) {
                this.items.splice(index, 1);
            },

            recalcItem(item) {
                item.total_price = parseFloat(((item.quantity || 0) * (item.unit_price || 0)).toFixed(2));
            },

            recalcAllPrices() {
                this.items.forEach((item) => {
                    let product = this.products.find(p => p.id === item.product_id);
                    if (product) {
                        item.unit_price = this.getItemPrice(product);
                        this.recalcItem(item);
                    }
                });
            },

            get subtotal() {
                return this.items.reduce((sum, i) => sum + parseFloat(i.total_price || 0), 0);
            },

            get totalIcmsSt() {
                return this.items.reduce((sum, i) => sum + parseFloat(i.icms_st_value || 0), 0);
            },

            get grandTotal() {
                return this.subtotal + this.totalIcmsSt;
            },
        }
    }
</script>
