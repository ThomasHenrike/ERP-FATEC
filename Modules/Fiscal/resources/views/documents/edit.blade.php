@php
    $existingItems = $fiscalDocument->items->map(fn ($item) => [
        'product_id' => $item->product_id,
        'name' => $item->product?->name ?? 'Produto removido',
        'unit' => $item->product?->unit ?? 'UN',
        'quantity' => (float) $item->quantity,
        'unit_price' => (float) $item->unit_price,
        'discount_amount' => (float) $item->discount_amount,
        'total_price' => (float) $item->total_price,
        'ncm' => $item->ncm,
        'cest' => $item->cest,
        'origin' => $item->origin,
        'cfop' => $item->cfop,
        'csosn' => $item->csosn,
        'cst_icms' => $item->cst_icms,
        'cst_pis' => $item->cst_pis,
        'cst_cofins' => $item->cst_cofins,
        'icms_rate' => $item->icms_rate !== null ? (float) $item->icms_rate : null,
        'pis_rate' => $item->pis_rate !== null ? (float) $item->pis_rate : null,
        'cofins_rate' => $item->cofins_rate !== null ? (float) $item->cofins_rate : null,
    ])->values();
@endphp

<x-core::layouts.master>
    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Revisar NF-e antes de Emitir</h1>
                <p class="text-sm text-gray-500">
                    @if ($fiscalDocument->sale)
                        Originada da Venda #{{ $fiscalDocument->sale->code }} - ajuste o que precisar antes de emitir de verdade.
                    @else
                        Ajuste o que precisar antes de emitir de verdade.
                    @endif
                </p>
            </div>
            <x-fiscal::status-badge :status="$fiscalDocument->status" />
        </div>

        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm rounded-r-lg shadow-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm rounded-r-lg shadow-sm">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm rounded-r-lg shadow-sm">
                <p class="font-bold mb-1">Corrija os campos abaixo:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('fiscal.documents.update', $fiscalDocument) }}"
              x-data="nfeReviewHelper(
                  {{ Js::from($products) }},
                  {{ Js::from($existingItems) }},
                  {{ Js::from($paymentState) }}
              )"
              data-nfe-root class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Cliente --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-xs font-bold text-gray-700 uppercase mb-3">Cliente (Destinatario)</h2>
                @if ($fiscalDocument->customer)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-bold text-gray-900">{{ $fiscalDocument->customer->name }}</p>
                            <p class="text-xs text-gray-500 font-mono">{{ $fiscalDocument->customer->document }}</p>
                        </div>
                        <a href="{{ route('cadastros.clientes.edit', $fiscalDocument->customer) }}" target="_blank"
                           class="text-xs font-bold text-indigo-600 hover:text-indigo-900">
                            Editar Cliente &#8599;
                        </a>
                    </div>
                @else
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Esta venda nao tem cliente vinculado - a NF-e exige um destinatario. Associe um cliente a venda antes de emitir.
                    </p>
                @endif
            </div>

            {{-- Itens --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <h2 class="text-xs font-bold text-gray-700 uppercase mb-1.5">Itens</h2>
                <div class="w-full md:w-1/2">
                    <x-core::product-search :products="$products" placeholder="Buscar por nome, SKU ou codigo para adicionar..." />
                </div>
                <p class="mt-1 text-[11px] text-gray-400">Todos os campos fiscais (NCM, CFOP, CST, aliquotas...) podem ser corrigidos diretamente aqui, item a item - vale so' para esta nota.</p>

                <div class="mt-3 overflow-x-auto border border-gray-200 rounded-lg" x-show="items.length > 0" x-cloak>
                    <table class="w-full text-xs" style="min-width: 1900px">
                        <thead class="bg-gray-50 text-gray-600 font-bold uppercase">
                            <tr>
                                <th class="px-2 py-2 text-left w-48">Produto</th>
                                <th class="px-2 py-2 w-20">Qtd.</th>
                                <th class="px-2 py-2 w-24">Vlr. Unit.</th>
                                <th class="px-2 py-2 w-24">Desconto</th>
                                <th class="px-2 py-2 w-24">NCM</th>
                                <th class="px-2 py-2 w-20">CEST</th>
                                <th class="px-2 py-2 w-16">Orig.</th>
                                <th class="px-2 py-2 w-20">CFOP</th>
                                <th class="px-2 py-2 w-20">CSOSN</th>
                                <th class="px-2 py-2 w-20">CST ICMS</th>
                                <th class="px-2 py-2 w-20">ICMS %</th>
                                <th class="px-2 py-2 w-20">CST PIS</th>
                                <th class="px-2 py-2 w-20">PIS %</th>
                                <th class="px-2 py-2 w-20">CST COFINS</th>
                                <th class="px-2 py-2 w-20">COFINS %</th>
                                <th class="px-2 py-2 w-24 text-right">Total</th>
                                <th class="px-2 py-2 w-10"></th>
                                <th class="px-2 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="px-2 py-2 font-semibold text-gray-800" x-text="item.name"></td>
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.001" min="0.001" x-model.number="item.quantity" @input="recalcItem(item)"
                                               class="w-full rounded border-gray-300 text-xs">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" min="0" x-model.number="item.unit_price" @input="recalcItem(item)"
                                               class="w-full rounded border-gray-300 text-xs font-mono">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" min="0" x-model.number="item.discount_amount"
                                               class="w-full rounded border-gray-300 text-xs font-mono">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="text" maxlength="8" x-model="item.ncm" class="w-full rounded border-gray-300 text-xs font-mono">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="text" maxlength="7" x-model="item.cest" class="w-full rounded border-gray-300 text-xs font-mono">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" step="1" min="0" max="8" x-model.number="item.origin" class="w-full rounded border-gray-300 text-xs">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="text" maxlength="4" x-model="item.cfop" class="w-full rounded border-gray-300 text-xs font-mono">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="text" maxlength="4" x-model="item.csosn" class="w-full rounded border-gray-300 text-xs font-mono">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="text" maxlength="3" x-model="item.cst_icms" class="w-full rounded border-gray-300 text-xs font-mono">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" min="0" max="100" x-model.number="item.icms_rate" class="w-full rounded border-gray-300 text-xs font-mono">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="text" maxlength="2" x-model="item.cst_pis" class="w-full rounded border-gray-300 text-xs font-mono">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" min="0" max="100" x-model.number="item.pis_rate" class="w-full rounded border-gray-300 text-xs font-mono">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="text" maxlength="2" x-model="item.cst_cofins" class="w-full rounded border-gray-300 text-xs font-mono">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number" step="0.01" min="0" max="100" x-model.number="item.cofins_rate" class="w-full rounded border-gray-300 text-xs font-mono">
                                    </td>
                                    <td class="px-2 py-2 text-right font-mono font-bold text-gray-900" x-text="'R$ ' + (item.total_price || 0).toFixed(2).replace('.', ',')"></td>
                                    <td class="px-2 py-2 text-center">
                                        <a :href="'/estoque/produtos/' + item.product_id + '/edit'" target="_blank" title="Editar cadastro do produto" class="text-indigo-600 hover:text-indigo-900 font-bold">&#8599;</a>
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        <button type="button" @click="removeItem(index)" class="text-rose-600 hover:text-rose-800 font-bold">&times;</button>
                                    </td>

                                    <template x-for="field in ['product_id','quantity','unit_price','discount_amount','total_price','ncm','cest','origin','cfop','csosn','cst_icms','cst_pis','cst_cofins','icms_rate','pis_rate','cofins_rate']" :key="field">
                                        <input type="hidden" :name="'items[' + index + '][' + field + ']'" :value="item[field]">
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p x-show="items.length === 0" class="mt-2 text-xs text-gray-400 italic">Nenhum item nesta nota.</p>

                <div class="flex justify-end mt-3">
                    <div class="w-full md:w-72 bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-1 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal:</span>
                            <span class="font-mono" x-text="'R$ ' + subtotal.toFixed(2).replace('.', ',')"></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Desconto:</span>
                            <span class="font-mono" x-text="'R$ ' + totalDesconto.toFixed(2).replace('.', ',')"></span>
                        </div>
                        <div class="flex justify-between font-bold text-gray-900 text-base pt-1 border-t">
                            <span>Total da NF-e:</span>
                            <span class="font-mono text-indigo-700" x-text="'R$ ' + totalLiquido.toFixed(2).replace('.', ',')"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Observacao --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <label class="block text-xs font-bold text-gray-700 uppercase">Observacao (Dados Adicionais da NF-e)</label>
                <textarea name="observacao" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">{{ old('observacao', $fiscalDocument->observacao) }}</textarea>
            </div>

            {{-- Forma de Pagamento --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
                <h2 class="text-xs font-bold text-gray-700 uppercase">Forma de Pagamento</h2>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase">Forma</label>
                        <select name="forma_pagamento" x-model="forma" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <template x-if="forma === 'boleto'">
                        <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Numero de Parcelas</label>
                                <input type="number" name="numero_parcelas" min="1" max="99" x-model.number="numeroParcelas"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Data do 1o Vencimento</label>
                                <input type="date" name="data_primeiro_vencimento" x-model="dataPrimeiroVencimento"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Dias entre Vencimentos</label>
                                <input type="number" name="dias_entre_vencimentos" min="1" max="180" x-model.number="diasEntreVencimentos"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            </div>
                        </div>
                    </template>
                </div>

                <template x-if="forma === 'boleto'">
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-[320px] w-full text-xs">
                            <thead class="bg-gray-50 text-gray-600 font-bold uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left">Parcela</th>
                                    <th class="px-3 py-2 text-left">Vencimento</th>
                                    <th class="px-3 py-2 text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="parcela in parcelasPreview" :key="parcela.numero">
                                    <tr>
                                        <td class="px-3 py-2" x-text="parcela.numero + '/' + numeroParcelas"></td>
                                        <td class="px-3 py-2" x-text="parcela.dataFormatada"></td>
                                        <td class="px-3 py-2 text-right font-mono" x-text="'R$ ' + parcela.valor.toFixed(2).replace('.', ',')"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>

            <div class="pt-4 border-t flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <a href="{{ $fiscalDocument->sale ? route('vendas.sales.index') : route('fiscal.documents.index') }}" class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-semibold text-gray-600">Voltar</a>
                    <button type="button"
                            @click="if (confirm('Excluir este rascunho de NF-e? Essa acao nao pode ser desfeita. O numero ja alocado (se houver) nao sera reaproveitado.')) { document.getElementById('destroy-fiscal-document-form').submit(); }"
                            class="px-4 py-2 bg-white border border-rose-300 text-rose-600 rounded-lg text-sm font-semibold hover:bg-rose-50">
                        Excluir Rascunho
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" name="action" value="save" class="px-5 py-2 bg-gray-800 text-white rounded-lg text-sm font-bold hover:bg-gray-900 shadow-sm">Salvar Alteracoes</button>
                    <button type="submit" name="action" value="emit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-sm">Emitir Nota Fiscal</button>
                </div>
            </div>
        </form>

        <form id="destroy-fiscal-document-form" method="POST" action="{{ route('fiscal.documents.destroy', $fiscalDocument) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</x-core::layouts.master>

<script>
    function nfeReviewHelper(products, existingItems, paymentState) {
        return {
            products,
            items: existingItems,
            forma: paymentState.forma,
            numeroParcelas: paymentState.numeroParcelas,
            dataPrimeiroVencimento: paymentState.dataPrimeiroVencimento,
            diasEntreVencimentos: paymentState.diasEntreVencimentos,

            addProduct(product) {
                if (!product || !product.id) return;

                let existing = this.items.find(i => i.product_id === product.id);
                if (existing) {
                    existing.quantity += 1;
                    this.recalcItem(existing);
                    return;
                }

                let price = parseFloat(product.price_retail || 0);
                this.items.push({
                    product_id: product.id,
                    name: product.name,
                    unit: product.unit,
                    quantity: 1,
                    unit_price: price,
                    discount_amount: 0,
                    total_price: price,
                    ncm: product.ncm || '',
                    cest: product.cest || '',
                    origin: product.origin ?? 0,
                    cfop: product.cfop_default || '',
                    csosn: product.csosn || '',
                    cst_icms: product.cst_icms || '',
                    cst_pis: product.cst_pis || '',
                    cst_cofins: product.cst_cofins || '',
                    icms_rate: parseFloat(product.icms_rate || 0),
                    pis_rate: parseFloat(product.pis_rate || 0),
                    cofins_rate: parseFloat(product.cofins_rate || 0),
                });
            },

            removeItem(index) {
                this.items.splice(index, 1);
            },

            recalcItem(item) {
                item.total_price = parseFloat(((item.quantity || 0) * (item.unit_price || 0)).toFixed(2));
            },

            get subtotal() {
                return this.items.reduce((sum, i) => sum + parseFloat(i.total_price || 0), 0);
            },

            get totalDesconto() {
                return this.items.reduce((sum, i) => sum + parseFloat(i.discount_amount || 0), 0);
            },

            get totalLiquido() {
                return this.subtotal - this.totalDesconto;
            },

            // Espelha Modules\Fiscal\App\Support\InstallmentSplitter no servidor -
            // so' pra preview ao vivo, o valor real e' sempre recalculado no
            // backend ao salvar (nunca confia no valor por parcela do client).
            get parcelasPreview() {
                if (this.forma !== 'boleto' || !this.numeroParcelas || !this.dataPrimeiroVencimento) return [];

                let n = Math.max(1, parseInt(this.numeroParcelas) || 1);
                let totalCentavos = Math.round(this.totalLiquido * 100);
                let base = Math.floor(totalCentavos / n);
                let resto = totalCentavos % n;
                let dias = parseInt(this.diasEntreVencimentos) || 0;
                let primeiro = new Date(this.dataPrimeiroVencimento + 'T00:00:00');

                let rows = [];
                for (let i = 0; i < n; i++) {
                    let centavos = base + (i < resto ? 1 : 0);
                    let data = new Date(primeiro.getTime());
                    data.setDate(data.getDate() + (i * dias));

                    rows.push({
                        numero: i + 1,
                        valor: centavos / 100,
                        dataFormatada: data.toLocaleDateString('pt-BR'),
                    });
                }
                return rows;
            },
        }
    }
</script>
