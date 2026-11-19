<x-core::layouts.master>
    <div x-data="posEngine({{ Js::from($products) }}, {{ Js::from($customers) }}, {{ Js::from($sellers) }}, {{ Js::from($activeShift) }}, {{ (float) ($tenant->max_discount_percentage ?? 10) }}, {{ Js::from($prefillCart ?? []) }}, {{ Js::from($prefillQuoteId ?? null) }}, {{ Js::from($prefillCustomerId ?? null) }}, {{ Js::from($prefillPriceTable ?? null) }}, {{ Js::from((bool) $tenant->allow_price_edit_pos) }})"
         @keydown.window.f2.prevent="if (activeShift) openStockModal = true"
         data-pdv-root
         class="h-[calc(100vh-4.25rem)] overflow-hidden flex flex-col p-3 space-y-3 relative">
        
        {{-- Container de Notificacoes Toast Flutuantes --}}
        <div class="fixed top-4 right-4 z-50 space-y-2 max-w-sm w-full pointer-events-none">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-show="toast.show" 
                     x-transition:enter="transform ease-out duration-300 transition"
                     x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                     x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     :class="{
                         'bg-rose-600 text-white border-rose-700': toast.type === 'error',
                         'bg-emerald-600 text-white border-emerald-700': toast.type === 'success',
                         'bg-amber-500 text-white border-amber-600': toast.type === 'warning'
                     }"
                     class="pointer-events-auto p-4 rounded-xl shadow-2xl border flex items-center justify-between gap-3 text-xs font-bold">
                    <div class="flex items-center gap-2">
                        <span x-text="toast.type === 'error' ? '⚠️' : (toast.type === 'success' ? '✓' : 'ℹ️')"></span>
                        <span x-text="toast.message"></span>
                    </div>
                    <button type="button" @click="toast.show = false" class="text-white hover:text-gray-200 font-extrabold text-sm">&times;</button>
                </div>
            </template>
        </div>

        {{-- Barra Superior do Caixa --}}
        <div class="flex flex-wrap items-center justify-between gap-2 bg-white px-4 py-2 rounded-xl border border-gray-200 shadow-sm flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-lg text-white"
                     :class="activeShift ? 'bg-emerald-600' : 'bg-rose-600'">
                    $
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-sm font-bold text-gray-900 leading-tight">Frente de Caixa (PDV)</h1>
                        <span x-show="activeShift" class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-bold"
                              x-text="'Turno #' + (activeShift ? activeShift.id : '') + ' Aberto'"></span>
                        <span x-show="!activeShift" class="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-[10px] font-bold">
                            Caixa Fechado
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-500">
                        <template x-if="activeShift && activeShift.user">
                            <span>Turno aberto por: <span class="font-semibold text-gray-800" x-text="activeShift.user.name"></span></span>
                        </template>
                        <template x-if="!activeShift || !activeShift.user">
                            <span>Operador: <span class="font-semibold text-gray-800">{{ auth()->user()->name ?? 'Caixa 01' }}</span></span>
                        </template>
                    </p>
                </div>
            </div>

            {{-- Botoes de Gaveta e Atalhos --}}
            <div class="flex flex-wrap items-center gap-2">
                <div x-show="activeShift" class="flex items-center gap-2">
                    <button type="button" @click="openMovementModal = true" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition">
                        Sangria / Suprimento
                    </button>
                    <button type="button" @click="loadShiftSummary(); openCloseShiftModal = true;" class="px-2.5 py-1 bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-bold transition">
                        Fechar Caixa
                    </button>
                </div>

                <div x-show="!activeShift">
                    <button type="button" @click="openShiftModal = true" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow transition">
                        Abrir Turno de Caixa
                    </button>
                </div>

                {{-- Vendedor Responsavel --}}
                <div class="flex items-center gap-1.5 pl-2 border-l border-gray-200">
                    <label class="text-[11px] font-bold text-gray-500 uppercase">Vendedor:</label>
                    <select x-model="selectedSellerId" :disabled="!activeShift" class="rounded border-gray-300 text-xs font-semibold py-1 max-w-[130px]">
                        <option value="">Venda Direta</option>
                        <template x-for="s in sellers" :key="s.id">
                            <option :value="s.id" x-text="s.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Cliente --}}
                <div class="flex items-center gap-1.5 pl-2 border-l border-gray-200"
                     @customer-selected="selectedCustomerId = $event.detail.id; onCustomerSelected()">
                    <label class="text-[11px] font-bold text-gray-500 uppercase">Cliente:</label>
                    <template x-if="!selectedCustomerId">
                        <div class="w-40">
                            <x-core::customer-search :customers="$customers" placeholder="Consumidor Final"
                                input-class="w-full rounded border-gray-300 text-xs font-semibold py-1" />
                        </div>
                    </template>
                    <template x-if="selectedCustomerId">
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-50 border border-indigo-200 rounded text-xs font-bold text-indigo-700 max-w-[160px]">
                            <span class="truncate" x-text="customers.find(c => c.id == selectedCustomerId)?.name || ''"></span>
                            <button type="button" :disabled="!activeShift" @click="selectedCustomerId = ''" class="text-indigo-400 hover:text-indigo-700 font-bold flex-shrink-0">&times;</button>
                        </span>
                    </template>
                    <button type="button" @click="openCustomerModal = true" :disabled="!activeShift" class="px-1.5 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs font-bold text-gray-700">+</button>
                </div>

                {{-- Tabela de Preco --}}
                <div class="flex items-center gap-1.5 pl-2 border-l border-gray-200">
                    <label class="text-[11px] font-bold text-gray-500 uppercase">Tabela:</label>
                    <select x-model="priceTable" @change="recalcCartPrices()" :disabled="!activeShift" class="rounded border-gray-300 text-xs font-bold text-indigo-700 py-1">
                        <option value="retail">Varejo</option>
                        <option value="resale">Revenda</option>
                        <option value="wholesale">Atacado</option>
                    </select>
                </div>

                <button type="button" @click="openStockModal = true" :disabled="!activeShift" 
                        class="px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold hover:bg-indigo-100 transition">
                    [F2] Estoque
                </button>
            </div>
        </div>

        {{-- Layout Principal --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 flex-1 overflow-hidden">
            
            {{-- Coluna do Carrinho --}}
            <div class="lg:col-span-2 flex flex-col space-y-2 overflow-hidden h-full">
                <div class="p-2.5 bg-white rounded-xl border border-gray-200 shadow-sm flex-shrink-0">
                    <div class="relative">
                        <input type="text" 
                               name="pos_barcode_scanner_field_no_autocomplete"
                               x-model="searchQuery" 
                               @keydown.enter.prevent="handleDirectSearch()" 
                               x-ref="barcodeInput"
                               :disabled="!activeShift"
                               autocomplete="new-password"
                               autocorrect="off"
                               autocapitalize="off"
                               spellcheck="false"
                               readonly
                               @focus="$el.removeAttribute('readonly')"
                               :placeholder="activeShift ? 'Escaneie o codigo de barras ou digite o codigo simples (#1, #2)...' : 'Abra o turno de caixa para iniciar as vendas...'" 
                               class="w-full text-sm font-bold rounded-lg border-gray-300 pl-9 py-2 focus:border-emerald-500 focus:ring-emerald-500 disabled:bg-gray-100">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 font-mono text-sm">#</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm flex-1 overflow-hidden flex flex-col">
                    <div class="overflow-y-auto flex-1">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                            <thead class="bg-gray-50 text-gray-600 font-semibold uppercase sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2">Item</th>
                                    <th class="px-3 py-2 text-right">Unitario</th>
                                    <th class="px-3 py-2 text-center">Qtd</th>
                                    <th class="px-3 py-2 text-right">Total</th>
                                    <th class="px-3 py-2 text-center">Acao</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 font-medium">
                                <template x-for="(item, index) in cart" :key="item.product_id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2">
                                            <div class="font-bold text-gray-900" x-text="item.name"></div>
                                            <div class="text-[10px] text-gray-400" x-text="'Cod: #' + item.code + ' | SKU: ' + item.sku"></div>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <template x-if="allowPriceEdit">
                                                <input type="number" step="0.01" min="0" x-model.number="item.unit_price" @input="updateItemTotal(index)"
                                                       @keydown.enter.prevent="focusBarcodeInput()"
                                                       class="item-price-input w-20 text-right font-mono font-bold text-xs border-gray-300 rounded py-0.5">
                                            </template>
                                            <template x-if="!allowPriceEdit">
                                                <span class="font-mono text-gray-700" x-text="'R$ ' + formatMoney(item.unit_price)"></span>
                                            </template>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <div class="inline-flex items-center border border-gray-200 rounded">
                                                <button type="button" @click="changeQty(index, -1)" class="px-1.5 py-0.5 text-gray-600 hover:bg-gray-100">-</button>
                                                <input type="number" step="any" min="0.001" x-model.number="item.quantity" @input="updateItemTotal(index)"
                                                       @keydown.enter.prevent="allowPriceEdit ? focusNextInRow($event, '.item-price-input') : focusBarcodeInput()"
                                                       class="item-qty-input w-16 text-center font-bold font-mono text-xs border-0 py-0.5 focus:ring-0">
                                                <button type="button" @click="changeQty(index, 1)" class="px-1.5 py-0.5 text-gray-600 hover:bg-gray-100">+</button>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-bold text-emerald-600" x-text="'R$ ' + formatMoney(item.total_price)"></td>
                                        <td class="px-3 py-2 text-center">
                                            <button type="button" @click="removeItem(index)" class="text-rose-600 hover:text-rose-800 font-bold text-sm">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="cart.length === 0">
                                    <td colspan="5" class="px-4 py-20 text-center text-gray-400 italic">
                                        <span x-text="activeShift ? 'Nenhum produto no caixa. Escaneie um codigo ou use [F2].' : 'Caixa fechado. Clique em Abrir Turno de Caixa para operar.'"></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Coluna Fixa de Totais e Pagamentos --}}
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between overflow-y-auto space-y-3 h-full">
                <div class="space-y-3">
                    <h2 class="text-sm font-bold text-gray-900 border-b pb-1.5">Resumo Financeiro</h2>

                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between text-gray-500">
                            <span>Subtotal Bruto:</span>
                            <span class="font-mono font-bold text-gray-900" x-text="'R$ ' + formatMoney(subtotal)"></span>
                        </div>

                        {{-- Descontos Cumulativos --}}
                        <div class="p-2 bg-gray-50 rounded border border-gray-200 space-y-1">
                            <span class="block text-[10px] font-bold uppercase text-gray-600">Descontos Cumulativos</span>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[9px] text-gray-500">Desconto (%)</label>
                                    <input type="number" step="0.01" min="0" max="100" x-model.number="discountPercent"
                                           :disabled="!activeShift" placeholder="%" class="w-full rounded border-gray-300 text-xs font-mono font-bold py-0.5">
                                </div>
                                <div>
                                    <label class="block text-[9px] text-gray-500">Desconto (R$)</label>
                                    <input type="number" step="0.01" min="0" x-model.number="discountFixed"
                                           :disabled="!activeShift" placeholder="R$" class="w-full rounded border-gray-300 text-xs font-mono font-bold py-0.5">
                                </div>
                            </div>
                            <div class="flex justify-between text-[11px] text-rose-600 font-bold pt-1 border-t">
                                <span>Abatimento Total:</span>
                                <span class="font-mono" x-text="'- R$ ' + formatMoney(totalDiscountAmount)"></span>
                            </div>
                        </div>

                        <div class="border-t pt-1.5 flex justify-between items-baseline">
                            <span class="text-sm font-bold text-gray-900">Total a Pagar:</span>
                            <span class="text-xl font-extrabold text-emerald-600 font-mono" x-text="'R$ ' + formatMoney(totalPayable)"></span>
                        </div>
                    </div>

                    {{-- Formas de Pagamento em Portugues e Reativo --}}
                    <div class="space-y-1.5 pt-1.5 border-t">
                        <div class="flex justify-between items-center text-xs">
                            <label class="text-[10px] font-bold uppercase text-gray-700">Forma de Pagamento</label>
                            <span class="text-[11px] font-mono font-bold" 
                                  :class="remainingToPay <= 0 ? 'text-emerald-600' : 'text-indigo-600'"
                                  x-text="payments.length === 0 ? 'Sugerido: R$ ' + formatMoney(totalPayable) : (remainingToPay <= 0 ? 'Quitado' : 'Falta: R$ ' + formatMoney(remainingToPay))"></span>
                        </div>

                        <div class="flex gap-1.5">
                            <select x-model="newPaymentMethod" :disabled="!activeShift" class="rounded border-gray-300 text-xs font-semibold flex-1 py-1">
                                <option value="money">Dinheiro</option>
                                <option value="pix">PIX</option>
                                <option value="credit_card">Cartao de Credito</option>
                                <option value="debit_card">Cartao de Debito</option>
                                <option value="on_account">A Prazo (Crediario)</option>
                            </select>
                            <input type="number" step="0.01" min="0.01" 
                                   :value="newPaymentAmountOverride !== null ? newPaymentAmountOverride : remainingToPay"
                                   @input="newPaymentAmountOverride = parseFloat($event.target.value) || 0"
                                   @keydown.enter.prevent="addPayment()"
                                   placeholder="R$" 
                                   :disabled="!activeShift" class="w-24 rounded border-gray-300 text-xs font-mono font-bold py-1">
                            <button type="button" @click="addPayment()" :disabled="!activeShift" 
                                    title="Adicionar pagamento fracionado (Split Payment)"
                                    class="px-2.5 py-1 bg-gray-900 text-white rounded text-xs font-bold hover:bg-gray-800 disabled:bg-gray-300">
                                +
                            </button>
                        </div>

                        <div x-show="newPaymentMethod === 'on_account'" class="grid grid-cols-2 gap-1.5 p-1.5 bg-indigo-50/50 rounded border border-indigo-100 text-xs">
                            <div>
                                <label class="block text-[9px] font-bold text-indigo-900">Parcelas:</label>
                                <select x-model.number="newPaymentInstallments" class="w-full rounded border-gray-300 text-xs py-0.5">
                                    <template x-for="i in 12" :key="i">
                                        <option :value="i" x-text="i + 'x'"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold text-indigo-900">1º Vencimento:</label>
                                <input type="date" x-model="newPaymentDueDate" class="w-full rounded border-gray-300 text-xs py-0.5">
                            </div>
                        </div>

                        {{-- Lista de Pagamentos Lançados com Tradução em Português --}}
                        <div class="space-y-1 max-h-20 overflow-y-auto">
                            <template x-for="(p, idx) in payments" :key="idx">
                                <div class="flex justify-between items-center text-[11px] p-1 bg-gray-50 rounded border">
                                    <span class="font-bold text-gray-800" x-text="getPaymentLabel(p.method) + (p.installments > 1 ? ' (' + p.installments + 'x)' : '')"></span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono font-bold text-gray-900" x-text="'R$ ' + formatMoney(p.amount)"></span>
                                        <button type="button" @click="removePayment(idx)" class="text-rose-600 hover:text-rose-800 font-bold">&times;</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Observacoes do Pedido --}}
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-gray-600 mb-0.5">Observacoes (Cupom / Romaneio)</label>
                        <textarea x-model="notes" rows="1" :disabled="!activeShift" placeholder="Ex: Entregar apos as 14h..."
                                  class="w-full rounded border-gray-300 text-xs shadow-sm focus:ring-emerald-500"></textarea>
                    </div>
                </div>

                <button type="button" @click="submitSale()" :disabled="!activeShift || cart.length === 0 || isProcessing"
                        :class="activeShift && cart.length > 0 && !isProcessing ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-300 cursor-not-allowed'"
                        class="w-full py-3 text-white font-bold rounded-xl shadow transition text-sm tracking-wide flex justify-center items-center gap-2">
                    <span x-show="!isProcessing">CONCLUIR VENDA</span>
                    <span x-show="isProcessing">GRAVANDO...</span>
                </button>
            </div>
        </div>

        {{-- MODAIS --}}

        {{-- MODAL 1: Abertura de Caixa --}}
        <div x-show="openShiftModal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-60 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="text-center">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full mx-auto flex items-center justify-center text-2xl font-bold">&#128181;</div>
                    <h3 class="text-lg font-bold text-gray-900 mt-2">Abertura de Turno de Caixa</h3>
                    <p class="text-xs text-gray-500">Informe o valor de fundo de troco presente na gaveta para iniciar.</p>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 text-sm">Fundo de Troco / Saldo Inicial (R$) *</label>
                        <input type="number" step="0.01" min="0" x-model.number="shiftOpeningBalance" autofocus class="w-full rounded-lg border-gray-300 font-mono font-bold text-emerald-600 text-lg py-2">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700">Observacoes de Abertura</label>
                        <textarea x-model="shiftOpeningNotes" rows="2" placeholder="Ex: Abertura turno..." class="w-full rounded border-gray-300 text-xs"></textarea>
                    </div>
                </div>
                <div class="pt-3 border-t flex justify-end gap-2">
                    <button type="button" @click="openShiftModal = false" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold">Cancelar</button>
                    <button type="button" @click="performOpenShift()" class="px-5 py-2 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 shadow">Confirmar e Abrir Caixa</button>
                </div>
            </div>
        </div>

        {{-- MODAL 2: Sangria e Suprimento --}}
        <div x-show="openMovementModal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-60 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex justify-between items-center border-b pb-2"><h3 class="text-sm font-bold text-gray-900">Movimentacao de Gaveta</h3><button type="button" @click="openMovementModal = false" class="text-gray-400 font-bold text-lg">&times;</button></div>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700">Tipo de Operacao *</label>
                        <select x-model="drawerMovementType" class="w-full rounded border-gray-300 text-xs font-bold">
                            <option value="outflow">Sangria (Retirada de Dinheiro para Cofre/Pagamento)</option>
                            <option value="inflow">Suprimento (Reforco de Fundo de Troco)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700">Valor da Operacao (R$) *</label>
                        <input type="number" step="0.01" min="0.01" x-model.number="drawerMovementAmount" class="w-full rounded border-gray-300 font-mono font-bold text-sm">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700">Motivo / Justificativa *</label>
                        <input type="text" x-model="drawerMovementReason" placeholder="Ex: Pagamento fornecedor, recolhimento..." class="w-full rounded border-gray-300 text-xs">
                    </div>
                </div>
                <div class="pt-3 border-t flex justify-end gap-2">
                    <button type="button" @click="openMovementModal = false" class="px-3 py-1.5 bg-gray-100 rounded text-xs font-bold text-gray-700">Cancelar</button>
                    <button type="button" @click="performDrawerMovement()" class="px-4 py-1.5 bg-gray-900 text-white rounded text-xs font-bold hover:bg-gray-800">Salvar Movimento</button>
                </div>
            </div>
        </div>

        {{-- MODAL 3: Fechamento de Caixa com Saldo Esperado --}}
        <div x-show="openCloseShiftModal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-60 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="text-center">
                    <h3 class="text-lg font-bold text-gray-900">Fechamento de Turno de Caixa</h3>
                    <p class="text-xs text-gray-500">Conferencia de valores fisicos na gaveta.</p>
                </div>

                {{-- Painel de Resumo do Saldo Esperado --}}
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-xs space-y-1.5 font-medium">
                    <div class="flex justify-between text-gray-500"><span>Fundo de Troco Inicial:</span><span class="font-mono font-bold text-gray-800" x-text="'R$ ' + formatMoney(liveShiftSummary.opening_balance)"></span></div>
                    <div class="flex justify-between text-gray-500"><span>Vendas em Dinheiro (+):</span><span class="font-mono font-bold text-gray-800" x-text="'R$ ' + formatMoney(liveShiftSummary.cash_sales)"></span></div>
                    <div class="flex justify-between text-gray-500"><span>Suprimentos de Gaveta (+):</span><span class="font-mono font-bold text-gray-800" x-text="'R$ ' + formatMoney(liveShiftSummary.total_inflows)"></span></div>
                    <div class="flex justify-between text-rose-600"><span>Sangrias / Retiradas (-):</span><span class="font-mono font-bold" x-text="'- R$ ' + formatMoney(liveShiftSummary.total_outflows)"></span></div>
                    <div class="border-t pt-1.5 flex justify-between items-baseline font-bold text-indigo-900 text-sm">
                        <span>SALDO ESPERADO NA GAVETA:</span>
                        <span class="font-mono text-base font-extrabold text-indigo-600" x-text="'R$ ' + formatMoney(liveShiftSummary.expected_drawer_cash)"></span>
                    </div>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 text-xs uppercase">Valor Contado em Dinheiro na Gaveta (R$) *</label>
                        <input type="number" step="0.01" min="0" x-model.number="shiftClosingBalance" autofocus
                               class="w-full rounded-lg border-gray-300 font-mono font-bold text-emerald-600 text-lg py-2">
                    </div>

                    {{-- Indicador Dinamico de Diferenca --}}
                    <div class="p-2 rounded-lg text-xs text-center font-bold"
                         :class="{
                             'bg-emerald-50 text-emerald-800 border border-emerald-200': (shiftClosingBalance - liveShiftSummary.expected_drawer_cash) === 0,
                             'bg-blue-50 text-blue-800 border border-blue-200': (shiftClosingBalance - liveShiftSummary.expected_drawer_cash) > 0,
                             'bg-rose-50 text-rose-800 border border-rose-200': (shiftClosingBalance - liveShiftSummary.expected_drawer_cash) < 0
                         }">
                        <span x-show="(shiftClosingBalance - liveShiftSummary.expected_drawer_cash) === 0">✓ Conciliacao Exata (Diferenca: R$ 0,00)</span>
                        <span x-show="(shiftClosingBalance - liveShiftSummary.expected_drawer_cash) > 0" x-text="'Sobra de Caixa: + R$ ' + formatMoney(shiftClosingBalance - liveShiftSummary.expected_drawer_cash)"></span>
                        <span x-show="(shiftClosingBalance - liveShiftSummary.expected_drawer_cash) < 0" x-text="'Quebra de Caixa: - R$ ' + formatMoney(Math.abs(shiftClosingBalance - liveShiftSummary.expected_drawer_cash))"></span>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700">Observacoes de Fechamento</label>
                        <textarea x-model="shiftClosingNotes" rows="2" placeholder="Ex: Turno encerrado sem divergencias..." class="w-full rounded border-gray-300 text-xs"></textarea>
                    </div>
                </div>

                <div class="pt-3 border-t flex justify-end gap-2">
                    <button type="button" @click="openCloseShiftModal = false" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold">Cancelar</button>
                    <button type="button" @click="performCloseShift()" class="px-5 py-2 bg-rose-600 text-white rounded-lg text-xs font-bold hover:bg-rose-700 shadow">Encerrar Caixa e Imprimir</button>
                </div>
            </div>
        </div>

        {{-- MODAL 4: Cliente Rapido com Trava Anti-Duplicacao --}}
        <div x-show="openCustomerModal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4" style="display: none;">
            <div @click.away="if(!isSavingCustomer) openCustomerModal = false" class="bg-white rounded-2xl max-w-md w-full p-5 shadow-2xl space-y-3">
                <div class="flex justify-between items-center border-b pb-2">
                    <h3 class="text-sm font-bold text-gray-900">Cadastro Rapido de Cliente</h3>
                    <button type="button" @click="openCustomerModal = false" :disabled="isSavingCustomer" class="text-gray-400 font-bold text-lg">&times;</button>
                </div>
                <div class="space-y-2 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700">Nome / Razao Social *</label>
                        <input type="text" x-model="newCustomer.name" :disabled="isSavingCustomer" class="w-full rounded border-gray-300 text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700">CPF / CNPJ</label>
                        <input type="text" x-model="newCustomer.document" :disabled="isSavingCustomer" class="w-full rounded border-gray-300 text-xs">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-gray-700">Telefone</label>
                            <input type="text" x-model="newCustomer.phone" :disabled="isSavingCustomer" class="w-full rounded border-gray-300 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700">Limite Credito (R$)</label>
                            <input type="number" step="10" x-model.number="newCustomer.credit_limit" :disabled="isSavingCustomer" class="w-full rounded border-gray-300 text-xs">
                        </div>
                    </div>
                </div>
                <div class="pt-2 border-t flex justify-end gap-2">
                    <button type="button" @click="openCustomerModal = false" :disabled="isSavingCustomer" class="px-3 py-1 bg-gray-100 rounded text-xs font-bold text-gray-600">Cancelar</button>
                    <button type="button" @click="saveQuickCustomer()" :disabled="isSavingCustomer || !newCustomer.name" class="px-4 py-1 bg-indigo-600 text-white rounded text-xs font-bold hover:bg-indigo-700 disabled:bg-gray-400">
                        <span x-show="!isSavingCustomer">Salvar Cliente</span>
                        <span x-show="isSavingCustomer">Salvando...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- MODAL 5: Consulta Estoque F2 --}}
        <div x-show="openStockModal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4" style="display: none;">
            <div @click.away="openStockModal = false" class="bg-white rounded-2xl max-w-3xl w-full p-5 shadow-2xl space-y-3">
                <div class="flex justify-between items-center border-b pb-2"><h3 class="text-base font-bold text-gray-900">Consulta Rapida de Estoque [F2]</h3><button type="button" @click="openStockModal = false" class="text-gray-400 font-bold text-lg">&times;</button></div>
                <input type="text" x-model="modalFilter" placeholder="Filtrar por nome, SKU, codigo simples..." class="w-full rounded-lg border-gray-300 text-xs font-semibold">
                <div class="max-h-72 overflow-y-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-xs text-left">
                        <thead class="bg-gray-50 text-[10px] text-gray-500 uppercase font-semibold"><tr><th class="px-3 py-1.5">Cod</th><th class="px-3 py-1.5">Nome</th><th class="px-3 py-1.5">Saldo</th><th class="px-3 py-1.5">Preco</th><th class="px-3 py-1.5 text-right">Acao</th></tr></thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="p in filteredModalProducts" :key="p.id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-1.5 font-mono font-bold text-indigo-600" x-text="'#' + (p.code || p.id)"></td>
                                    <td class="px-3 py-1.5 font-medium text-gray-900" x-text="p.name"></td>
                                    <td class="px-3 py-1.5 font-bold" :class="p.current_stock <= 0 ? 'text-rose-600' : 'text-emerald-600'" x-text="p.current_stock + ' ' + p.unit"></td>
                                    <td class="px-3 py-1.5 font-mono" x-text="'R$ ' + formatMoney(getItemPrice(p))"></td>
                                    <td class="px-3 py-1.5 text-right"><button type="button" @click="addItemFromModal(p)" class="px-2 py-0.5 bg-indigo-600 text-white rounded text-[11px] font-bold hover:bg-indigo-700">+ Inserir</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- MODAL 6: Sucesso de Venda --}}
        <div x-show="showSuccessModal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-60 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 text-center shadow-2xl space-y-4" @keydown.window.escape.prevent="closeSuccessModal()">
                <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full mx-auto flex items-center justify-center text-3xl font-bold">&#10003;</div>
                <div><h3 class="text-xl font-bold text-gray-900" x-text="'Venda #' + completedSaleCode + ' Concluida!'"></h3><p class="text-xs text-gray-500 mt-1">Baixa de estoque efetuada no Kardex.</p></div>
                <div class="p-3 bg-gray-50 rounded-xl border text-xs space-y-1.5 text-left">
                    <div class="flex justify-between"><span class="text-gray-500">Valor Total:</span><span class="font-bold text-gray-900 font-mono" x-text="'R$ ' + formatMoney(completedTotal)"></span></div>
                    <div class="flex justify-between" x-show="completedDiscount > 0"><span class="text-gray-500">Desconto:</span><span class="font-bold text-rose-600 font-mono" x-text="'- R$ ' + formatMoney(completedDiscount)"></span></div>
                    <div class="flex justify-between" x-show="completedReceived > 0"><span class="text-gray-500">Total Recebido:</span><span class="font-bold text-gray-900 font-mono" x-text="'R$ ' + formatMoney(completedReceived)"></span></div>
                    <div class="flex justify-between p-2 bg-emerald-50 rounded border border-emerald-200" x-show="completedChange > 0"><span class="font-bold text-emerald-800">Troco a Devolver:</span><span class="font-extrabold text-emerald-700 font-mono text-sm" x-text="'R$ ' + formatMoney(completedChange)"></span></div>
                </div>
                <div class="space-y-2 pt-2">
                    <button type="button" @click="printReceipt()" class="w-full py-3 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-md">Imprimir Comprovante</button>
                    <button type="button" @click="closeSuccessModal()" class="w-full py-2 bg-gray-100 text-gray-700 rounded-xl text-xs font-semibold hover:bg-gray-200">Nao Imprimir / Proxima Venda [Esc]</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function posEngine(catalog, customers, sellers, activeShift, maxDiscountPercentage, prefillCart, quoteId, prefillCustomerId, prefillPriceTable, allowPriceEdit) {
            return {
                catalog: catalog || [],
                customers: customers || [],
                sellers: sellers || [],
                activeShift: activeShift || null,
                allowPriceEdit: !!allowPriceEdit,
                cart: prefillCart || [],
                quoteId: quoteId || null,
                searchQuery: '',
                selectedCustomerId: prefillCustomerId || '',
                selectedSellerId: '',
                priceTable: prefillPriceTable || 'retail',
                discountPercent: 0,
                discountFixed: 0,
                notes: '',
                payments: [],
                newPaymentMethod: 'money',
                newPaymentAmountOverride: null,
                newPaymentInstallments: 1,
                newPaymentDueDate: new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0],
                newCustomer: { name: '', document: '', phone: '', credit_limit: 500 },
                toasts: [],
                openStockModal: false,
                openCustomerModal: false,
                showSuccessModal: false,
                openShiftModal: false,
                openMovementModal: false,
                openCloseShiftModal: false,
                isSavingCustomer: false,
                shiftOpeningBalance: 100.00,
                shiftOpeningNotes: '',
                drawerMovementType: 'outflow',
                drawerMovementAmount: 0,
                drawerMovementReason: '',
                shiftClosingBalance: 0,
                shiftClosingNotes: '',
                liveShiftSummary: { opening_balance: 0, cash_sales: 0, total_inflows: 0, total_outflows: 0, expected_drawer_cash: 0 },
                modalFilter: '',
                isProcessing: false,
                completedSaleId: null,
                completedSaleCode: null,
                completedTotal: 0,
                completedDiscount: 0,
                completedReceived: 0,
                completedChange: 0,

                paymentLabels: {
                    'money': 'Dinheiro',
                    'pix': 'PIX',
                    'credit_card': 'Cartao de Credito',
                    'debit_card': 'Cartao de Debito',
                    'on_account': 'A Prazo (Crediario)'
                },

                init() {
                    if (!this.activeShift) {
                        this.openShiftModal = true;
                    }
                    // O <select> do cliente e populado via x-for; se o valor inicial for
                    // aplicado antes das <option> existirem no DOM, o navegador ignora a
                    // selecao. Forca reaplicar depois que o Alpine terminar de montar.
                    if (this.selectedCustomerId) {
                        let prefilledCustomerId = this.selectedCustomerId;
                        this.selectedCustomerId = '';
                        this.$nextTick(() => { this.selectedCustomerId = prefilledCustomerId; });
                    }
                    // Nao chama onCustomerSelected() aqui de proposito: quando o carrinho
                    // vem pre-preenchido de um orcamento, os precos ja negociados no
                    // orcamento nao podem ser sobrescritos pela tabela padrao do cliente.
                },

                triggerToast(message, type = 'error') {
                    let id = Date.now();
                    this.toasts.push({ id: id, message: message, type: type, show: true });
                    setTimeout(() => {
                        let t = this.toasts.find(item => item.id === id);
                        if (t) t.show = false;
                    }, 3500);
                },

                getPaymentLabel(method) {
                    return this.paymentLabels[method] || method;
                },

                onCustomerSelected() {
                    let cust = this.customers.find(c => c.id == this.selectedCustomerId);
                    if (cust && cust.default_price_table) {
                        this.priceTable = cust.default_price_table;
                        this.recalcCartPrices();
                        this.triggerToast('Tabela ajustada para ' + cust.default_price_table.toUpperCase(), 'warning');
                    }
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + item.total_price, 0);
                },

                get totalDiscountAmount() {
                    let fromPercent = (this.subtotal * (this.discountPercent / 100));
                    return parseFloat((fromPercent + this.discountFixed).toFixed(2));
                },

                get totalPayable() {
                    return Math.max(0, parseFloat((this.subtotal - this.totalDiscountAmount).toFixed(2)));
                },

                get totalPaid() {
                    return parseFloat(this.payments.reduce((sum, p) => sum + p.amount, 0).toFixed(2));
                },

                get remainingToPay() {
                    return Math.max(0, parseFloat((this.totalPayable - this.totalPaid).toFixed(2)));
                },

                get filteredModalProducts() {
                    if (!this.modalFilter) return this.catalog;
                    let q = this.modalFilter.toLowerCase();
                    return this.catalog.filter(p => 
                        p.name.toLowerCase().includes(q) || 
                        (p.sku && p.sku.toLowerCase().includes(q)) || 
                        (p.code && p.code.toString().includes(q)) ||
                        (p.barcode && p.barcode.includes(q))
                    );
                },

                getItemPrice(product) {
                    if (this.priceTable === 'resale') return parseFloat(product.price_resale || product.price_retail);
                    if (this.priceTable === 'wholesale') return parseFloat(product.price_wholesale || product.price_retail);
                    return parseFloat(product.price_retail);
                },

                handleDirectSearch() {
                    if (!this.activeShift || !this.searchQuery) return;
                    let raw = this.searchQuery.trim();
                    let qty = 1;

                    // Suporte a multiplicador 300*CODIGO
                    if (raw.includes('*')) {
                        let parts = raw.split('*');
                        qty = parseFloat(parts[0]) || 1;
                        raw = parts[1] ? parts[1].trim() : '';
                    }

                    let q = raw.replace(/^#/, '');

                    let found = this.catalog.find(p => 
                        (p.barcode && p.barcode.trim() === q) || 
                        (p.code && p.code.toString() === q) || 
                        (p.id && p.id.toString() === q) ||
                        (p.sku && p.sku.toLowerCase() === q.toLowerCase())
                    );

                    if (found) {
                        this.addProductToCartWithQty(found, qty);
                        this.searchQuery = '';
                    } else {
                        this.triggerToast('Produto nao localizado por codigo simples, SKU ou codigo de barras.', 'warning');
                    }
                },

                addProductToCartWithQty(product, qty) {
                    let existingIndex = this.cart.findIndex(i => i.product_id === product.id);
                    let price = this.getItemPrice(product);
                    let rowIndex;

                    if (existingIndex !== -1) {
                        let existing = this.cart[existingIndex];
                        existing.quantity += qty;
                        existing.total_price = parseFloat((existing.quantity * existing.unit_price).toFixed(2));
                        rowIndex = existingIndex;
                    } else {
                        this.cart.push({
                            product_id: product.id,
                            code: product.code || product.id,
                            name: product.name,
                            sku: product.sku || 'N/A',
                            unit: product.unit,
                            unit_price: price,
                            quantity: qty,
                            total_price: parseFloat((price * qty).toFixed(2)),
                        });
                        rowIndex = this.cart.length - 1;
                    }
                    this.newPaymentAmountOverride = null;

                    // Fluxo de teclado: apos escanear/adicionar, foca a Quantidade
                    // do item (novo ou existente) pra permitir ajustar na hora.
                    this.$nextTick(() => {
                        let root = document.querySelector('[data-pdv-root]');
                        let target = root ? root.querySelectorAll('.item-qty-input')[rowIndex] : null;
                        if (target) { target.focus(); target.select(); }
                    });
                },

                focusNextInRow(event, selector) {
                    let row = event.target.closest('tr');
                    let next = row ? row.querySelector(selector) : null;
                    if (next) { next.focus(); next.select(); }
                },

                focusBarcodeInput() {
                    this.$nextTick(() => {
                        if (this.$refs.barcodeInput) {
                            this.$refs.barcodeInput.removeAttribute('readonly');
                            this.$refs.barcodeInput.focus();
                        }
                    });
                },

                focusNextInRow(event, selector) {
                    let row = event.target.closest('tr');
                    let next = row ? row.querySelector(selector) : null;
                    if (next) { next.focus(); next.select(); }
                },

                focusBarcodeInput() {
                    this.$nextTick(() => {
                        if (this.$refs.barcodeInput) {
                            this.$refs.barcodeInput.removeAttribute('readonly');
                            this.$refs.barcodeInput.focus();
                        }
                    });
                },

                addProductToCart(product) {
                    this.addProductToCartWithQty(product, 1);
                },

                addItemFromModal(product) {
                    this.addProductToCart(product);
                    this.openStockModal = false;
                },

                updateItemTotal(index) {
                    let item = this.cart[index];
                    if (item.quantity <= 0) item.quantity = 1;
                    item.total_price = parseFloat((item.quantity * item.unit_price).toFixed(2));
                    this.newPaymentAmountOverride = null;
                },

                changeQty(index, delta) {
                    let item = this.cart[index];
                    item.quantity += delta;
                    if (item.quantity <= 0) {
                        this.cart.splice(index, 1);
                    } else {
                        item.total_price = parseFloat((item.quantity * item.unit_price).toFixed(2));
                    }
                    this.newPaymentAmountOverride = null;
                },

                removeItem(index) {
                    this.cart.splice(index, 1);
                    this.newPaymentAmountOverride = null;
                },

                recalcCartPrices() {
                    this.cart.forEach(item => {
                        let product = this.catalog.find(p => p.id === item.product_id);
                        if (product) {
                            item.unit_price = this.getItemPrice(product);
                            item.total_price = parseFloat((item.quantity * item.unit_price).toFixed(2));
                        }
                    });
                    this.newPaymentAmountOverride = null;
                },

                addPayment() {
                    let amountToAdd = this.newPaymentAmountOverride !== null ? this.newPaymentAmountOverride : this.remainingToPay;
                    if (amountToAdd <= 0) return;

                    if (this.newPaymentMethod === 'on_account' && !this.selectedCustomerId) {
                        this.triggerToast('Para pagamentos A Prazo, selecione um cliente cadastrado.', 'error');
                        return;
                    }

                    this.payments.push({
                        method: this.newPaymentMethod,
                        amount: parseFloat(amountToAdd.toFixed(2)),
                        installments: this.newPaymentInstallments,
                        first_due_date: this.newPaymentDueDate
                    });

                    this.newPaymentAmountOverride = null;
                },

                removePayment(index) {
                    this.payments.splice(index, 1);
                    this.newPaymentAmountOverride = null;
                },

                saveQuickCustomer() {
                    if (!this.newCustomer.name || this.isSavingCustomer) return;
                    this.isSavingCustomer = true;

                    fetch("{{ route('vendas.clientes.rapido') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(this.newCustomer)
                    })
                    .then(r => r.json())
                    .then(res => {
                        this.isSavingCustomer = false;
                        if (res.success) {
                            this.customers.push(res.customer);
                            this.selectedCustomerId = res.customer.id;
                            this.openCustomerModal = false;
                            this.newCustomer = { name: '', document: '', phone: '', credit_limit: 500 };
                            this.triggerToast('Cliente cadastrado com sucesso.', 'success');
                        } else {
                            this.triggerToast('Erro ao cadastrar cliente.', 'error');
                        }
                    })
                    .catch(() => {
                        this.isSavingCustomer = false;
                        this.triggerToast('Erro de conexao ao salvar cliente.', 'error');
                    });
                },

                performOpenShift() {
                    if (this.shiftOpeningBalance < 0) return;
                    fetch("{{ route('vendas.caixa.abrir') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            opening_balance: this.shiftOpeningBalance,
                            notes: this.shiftOpeningNotes
                        })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            this.activeShift = res.shift;
                            this.openShiftModal = false;
                            this.triggerToast('Turno de caixa aberto com sucesso.', 'success');
                            this.$nextTick(() => this.$refs.barcodeInput && this.$refs.barcodeInput.focus());
                        } else {
                            this.triggerToast(res.message || 'Nao foi possivel abrir o caixa.', 'error');
                        }
                    });
                },

                performDrawerMovement() {
                    if (!this.activeShift || this.drawerMovementAmount <= 0 || !this.drawerMovementReason) return;
                    fetch("{{ route('vendas.caixa.movimento') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            cash_shift_id: this.activeShift.id,
                            type: this.drawerMovementType,
                            amount: this.drawerMovementAmount,
                            reason: this.drawerMovementReason
                        })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            this.triggerToast(res.message, 'success');
                            this.openMovementModal = false;
                            this.drawerMovementAmount = 0;
                            this.drawerMovementReason = '';
                        } else {
                            this.triggerToast(res.message || 'Nao foi possivel registrar o movimento.', 'error');
                        }
                    });
                },

                loadShiftSummary() {
                    if (!this.activeShift) return;
                    fetch("/vendas/caixa/resumo/" + this.activeShift.id)
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                this.liveShiftSummary = res.summary;
                                this.shiftClosingBalance = res.summary.expected_drawer_cash;
                            }
                        });
                },

                performCloseShift() {
                    if (!this.activeShift) return;
                    fetch("/vendas/caixa/fechar/" + this.activeShift.id, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            closing_balance: this.shiftClosingBalance,
                            notes: this.shiftClosingNotes
                        })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            window.open("/vendas/caixa/comprovante/" + res.shift_id, "_blank");
                            window.location.reload();
                        } else {
                            this.triggerToast(res.message || 'Nao foi possivel fechar o caixa.', 'error');
                        }
                    });
                },

                submitSale() {
                    if (!this.activeShift || this.cart.length === 0 || this.isProcessing) return;

                    // Valida se a empresa exige vendedor obrigatorio
                    if ({{ Js::from($tenant->require_seller_on_pos ?? false) }} && !this.selectedSellerId) {
                        this.triggerToast('A empresa exige a selecao de um Vendedor para concluir a venda.', 'error');
                        return;
                    }

                    // Se nao houver pagamento fracionado lancado, assume 100% no metodo selecionado
                    let finalPayments = this.payments.slice();
                    if (finalPayments.length === 0) {
                        if (this.newPaymentMethod === 'on_account' && !this.selectedCustomerId) {
                            this.triggerToast('Para pagamentos A Prazo, selecione um cliente cadastrado.', 'error');
                            return;
                        }
                        finalPayments.push({
                            method: this.newPaymentMethod,
                            amount: this.totalPayable,
                            installments: this.newPaymentInstallments,
                            first_due_date: this.newPaymentDueDate
                        });
                    }

                    this.isProcessing = true;

                    let payload = {
                        customer_id: this.selectedCustomerId || null,
                        seller_id: this.selectedSellerId || null,
                        quote_id: this.quoteId || null,
                        price_table: this.priceTable,
                        subtotal: this.subtotal,
                        discount_percent: this.discountPercent,
                        discount_fixed: this.discountFixed,
                        notes: this.notes,
                        items: this.cart.map(i => ({
                            product_id: i.product_id,
                            quantity: i.quantity,
                            unit_price: i.unit_price,
                            total_price: i.total_price
                        })),
                        payments: finalPayments
                    };

                    fetch("{{ route('vendas.pos.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(r => r.json())
                    .then(res => {
                        this.isProcessing = false;
                        if (res.success) {
                            this.completedSaleId = res.sale_id;
                            this.completedSaleCode = res.sale_code;
                            this.completedTotal = res.total;
                            this.completedDiscount = res.discount;
                            this.completedReceived = res.amount_received;
                            this.completedChange = res.change;
                            this.showSuccessModal = true;
                        } else {
                            this.triggerToast(res.message || 'Erro ao processar venda.', 'error');
                        }
                    })
                    .catch(() => {
                        this.isProcessing = false;
                        this.triggerToast('Erro de comunicacao com o servidor.', 'error');
                    });
                },

                printReceipt() {
                    if (this.completedSaleId) {
                        window.open("/vendas/recibo/" + this.completedSaleId, "_blank");
                        this.closeSuccessModal();
                    }
                },

                closeSuccessModal() {
                    this.showSuccessModal = false;
                    this.cart = [];
                    this.payments = [];
                    this.discountPercent = 0;
                    this.discountFixed = 0;
                    this.notes = '';
                    this.selectedCustomerId = '';
                    this.selectedSellerId = '';
                    this.newPaymentAmountOverride = null;
                    this.$nextTick(() => this.$refs.barcodeInput && this.$refs.barcodeInput.focus());
                },

                formatMoney(val) {
                    return parseFloat(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            }
        }
    </script>
</x-core::layouts.master>