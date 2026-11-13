@php
    // Formas de pagamento aceitas por esta versao do sistema.
    $paymentOptions = [
        'money' => 'Dinheiro',
        'pix' => 'PIX',
        'credit_card' => 'Cartão de Crédito',
        'debit_card' => 'Cartão de Débito',
        'on_account' => 'A Prazo (Crediário)',
    ];
@endphp
<x-core::layouts.master>
    <style>
        /* Colunas da lista e da linha de contexto: largas no desktop, compactas
           em tablet/notebook (o tema simplificado, que cobria o toque, saiu). */
        [data-pdv-cols] { grid-template-columns: 40px minmax(0,1fr) 124px 128px 144px 44px; }
        [data-pdv-context] { grid-template-columns: auto minmax(96px,170px) auto minmax(120px,240px) 34px auto minmax(96px,120px); }
        [data-pdv-pay-cols] { grid-template-columns: repeat(2, calc((100% - 24px) / 3)); }
        @media (max-width: 1279px) {
            [data-pdv-cols] { grid-template-columns: 30px minmax(0,1fr) 100px 112px 116px 34px; }
            [data-pdv-context] { grid-template-columns: minmax(0,1fr) minmax(0,1.5fr) 34px minmax(0,0.9fr); row-gap: 6px; }
            [data-pdv-context-label] { display: none; }
            [data-pdv-pay-cols] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        /* Quantidade do carrinho: sem as setinhas do navegador, que escondiam o numero. */
        [data-pdv-root] .item-qty-input::-webkit-outer-spin-button,
        [data-pdv-root] .item-qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        [data-pdv-root] .item-qty-input { -moz-appearance: textfield; appearance: textfield; }
    </style>
    <div x-data="posEngine({{ Js::from($products) }}, {{ Js::from($customers) }}, {{ Js::from($sellers) }}, {{ Js::from($activeShift) }}, {{ (float) ($tenant->max_discount_percentage ?? 10) }}, {{ Js::from($prefillCart ?? []) }}, {{ Js::from($prefillQuoteId ?? null) }}, {{ Js::from($prefillCustomerId ?? null) }}, {{ Js::from($prefillPriceTable ?? null) }}, {{ Js::from((bool) $tenant->allow_price_edit_pos) }})"
         @keydown.window.f2.prevent="if (activeShift && !anyModalOpen()) openStockModal = true"
         @keydown.window.f4.prevent="if (currentStep === 'carrinho' && !anyModalOpen()) goToPayment()"
         @keydown.window.f12.prevent="if (currentStep === 'carrinho' && activeShift && !anyModalOpen()) openMovementModal = true"
         data-pdv-root
         class="h-[calc(100vh-4.25rem)] overflow-hidden flex flex-col relative">

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

        {{-- Cabecalho (48px, fixo) --}}
        <div class="h-12 shrink-0 bg-white border-b border-[#c3c8d1] px-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-emerald-600 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M5 10V7a2 2 0 012-2h10a2 2 0 012 2v3M4 10l1 9a2 2 0 002 2h10a2 2 0 002-2l1-9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15h6" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class="text-base font-bold text-gray-900 leading-tight">Frente de Caixa (PDV)</h1>
                        <span x-show="activeShift" class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[11px] font-bold"
                              x-text="'Turno #' + (activeShift ? activeShift.id : '') + ' Aberto'"></span>
                        <span x-show="!activeShift" class="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-[11px] font-bold">
                            Caixa Fechado
                        </span>
                    </div>
                    <template x-if="currentStep === 'carrinho'">
                        <p class="text-xs text-[#4b5563] truncate">
                            <template x-if="activeShift && activeShift.user">
                                <span>Turno aberto por: <span class="font-semibold text-gray-800" x-text="activeShift.user.name"></span></span>
                            </template>
                            <template x-if="!activeShift || !activeShift.user">
                                <span>Operador: <span class="font-semibold text-gray-800">{{ auth()->user()->name ?? 'Caixa 01' }}</span></span>
                            </template>
                        </p>
                    </template>
                    <template x-if="currentStep === 'pagamento'">
                        <p class="text-xs text-[#4b5563] truncate">
                            Cliente: <strong class="text-gray-800" x-text="selectedCustomerId ? (customers.find(c => c.id == selectedCustomerId)?.name || '') : 'Consumidor Final'"></strong>
                            &middot; Vendedor: <strong class="text-gray-800" x-text="selectedSellerId ? (sellers.find(s => s.id == selectedSellerId)?.name || '') : '—'"></strong>
                            &middot; <span x-text="cart.length"></span> itens
                            &middot; <span x-text="totalUnits"></span> un.
                        </p>
                    </template>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <template x-if="currentStep === 'pagamento'">
                    <div class="hidden sm:flex items-center gap-1 text-xs font-bold">
                        <span class="px-2.5 py-1 rounded-full bg-[#e5e7eb] text-gray-600">1 Itens</span>
                        <span class="text-gray-300">&rsaquo;</span>
                        <span class="px-2.5 py-1 rounded-full bg-emerald-600 text-white">2 Pagamento</span>
                    </div>
                </template>

                <template x-if="currentStep === 'carrinho'">
                    <button type="button" @click="loadShiftSummary(); openCloseShiftModal = true;" :disabled="!activeShift"
                            class="h-8 px-3 bg-[#fff1f2] border border-[#fda4af] hover:bg-[#ffe4e6] disabled:opacity-40 text-[#be123c] rounded-lg text-xs font-bold transition">
                        Fechar Caixa
                    </button>
                </template>
                <template x-if="!activeShift && currentStep === 'carrinho'">
                    <button type="button" @click="openShiftModal = true" class="h-8 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow transition">
                        Abrir Turno de Caixa
                    </button>
                </template>
            </div>
        </div>

        {{-- Corpo: etapa 1 (carrinho) --}}
        {{-- grid via classe (nao style inline): x-show remove o display inline ao mostrar --}}
        <div x-show="currentStep === 'carrinho'" class="flex-1 min-h-0 px-3 lg:px-4 py-3 grid grid-cols-1 grid-rows-[minmax(0,1fr)_auto] lg:grid-rows-1 lg:grid-cols-[minmax(0,1fr)_300px] xl:grid-cols-[minmax(0,1fr)_360px] gap-3">

            {{-- Coluna esquerda --}}
            <div class="flex flex-col gap-2.5 min-h-0">
                {{-- Linha de contexto: vendedor / cliente / tabela --}}
                {{-- Grid com colunas que encolhem (minmax) em vez das larguras fixas do
                     design: com o menu lateral aberto a coluna fica mais estreita e as
                     larguras fixas passavam por baixo do Resumo da venda. --}}
                <div class="grid items-center gap-x-1.5 shrink-0 min-w-0" data-pdv-context
                     @customer-selected="selectedCustomerId = $event.detail.id; onCustomerSelected()">
                    <label data-pdv-context-label class="text-[11px] font-bold uppercase tracking-wide text-[#374151]">Vendedor</label>
                    <select x-model="selectedSellerId" :disabled="!activeShift"
                            class="w-full min-w-0 h-[34px] rounded-lg border-[#9aa1ad] text-sm font-semibold py-1">
                        <option value="">Venda Direta</option>
                        <template x-for="s in sellers" :key="s.id">
                            <option :value="s.id" x-text="s.name"></option>
                        </template>
                    </select>

                    <label data-pdv-context-label class="ml-1.5 text-[11px] font-bold uppercase tracking-wide text-[#374151]">Cliente</label>
                    <div class="min-w-0">
                        <template x-if="!selectedCustomerId">
                            <div class="w-full">
                                <x-core::customer-search :customers="$customers" placeholder="Consumidor Final"
                                    input-class="w-full h-[34px] rounded-lg border-[#9aa1ad] text-sm font-semibold py-1" />
                            </div>
                        </template>
                        <template x-if="selectedCustomerId">
                            <span class="flex w-full items-center gap-1 h-[34px] px-2 bg-indigo-50 border border-indigo-200 rounded-lg text-sm font-bold text-indigo-700">
                                <span class="truncate" x-text="customers.find(c => c.id == selectedCustomerId)?.name || ''"></span>
                                <button type="button" :disabled="!activeShift" @click="selectedCustomerId = ''" class="text-indigo-400 hover:text-indigo-700 font-bold shrink-0 ml-auto">&times;</button>
                            </span>
                        </template>
                    </div>
                    <button type="button" @click="openCustomerModal = true" :disabled="!activeShift" class="h-[34px] w-[34px] bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-bold text-gray-700">+</button>

                    <label data-pdv-context-label class="ml-1.5 text-[11px] font-bold uppercase tracking-wide text-[#374151]">Tabela</label>
                    <select x-model="priceTable" @change="recalcCartPrices()" :disabled="!activeShift"
                            class="w-full min-w-0 h-[34px] rounded-lg border-[#9aa1ad] text-sm font-bold text-indigo-700 py-1">
                        <option value="retail">Varejo</option>
                        <option value="resale">Revenda</option>
                        <option value="wholesale">Atacado</option>
                    </select>
                </div>

                {{-- Painel da lista --}}
                <div class="flex-1 min-h-0 bg-white border border-[#9aa1ad] rounded-[10px] flex flex-col overflow-hidden">
                    {{-- Leitor --}}
                    <div class="shrink-0 p-2.5 border-b border-[#c3c8d1]">
                        <div class="relative">
                            <input type="text"
                                   name="pos_barcode_scanner_field_no_autocomplete"
                                   x-model="searchQuery"
                                   @keydown.enter.prevent="handleDirectSearch()"
                                   @keydown.delete="if (!searchQuery && cart.length > 0) { $event.preventDefault(); removeItem(cart.length - 1); }"
                                   x-ref="barcodeInput"
                                   :disabled="!activeShift"
                                   autocomplete="new-password"
                                   autocorrect="off"
                                   autocapitalize="off"
                                   spellcheck="false"
                                   readonly
                                   @focus="$el.removeAttribute('readonly')"
                                   :placeholder="activeShift ? 'Escaneie o código de barras ou digite o código simples (#1, #2)...' : 'Abra o turno de caixa para iniciar as vendas...'"
                                   class="w-full h-11 text-base font-bold rounded-lg border-2 border-emerald-600 pl-9 pr-28 focus:border-emerald-600 focus:ring focus:ring-[#d1fae5] disabled:bg-gray-100 disabled:border-gray-300">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#047857]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6v12M8 6v12M11 6v12M15 6v12M17 6v12M20 6v12" />
                                </svg>
                            </div>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[12px] text-gray-400 font-semibold">
                                Qtd: <span class="font-mono ml-1">3*código</span>
                            </div>
                        </div>
                    </div>

                    {{-- Cabecalho da tabela --}}
                    <div class="shrink-0 h-[34px] bg-[#374151] text-white text-[11px] font-bold uppercase flex items-center"
                         style="display:grid;" data-pdv-cols>
                        <div class="px-3">#</div>
                        <div class="px-1">Item</div>
                        <div class="px-3 text-right">Unitário</div>
                        <div class="px-3 text-center">Qtd</div>
                        <div class="px-3 text-right">Total</div>
                        <div></div>
                    </div>

                    {{-- Linhas (unico elemento que rola) --}}
                    <div class="flex-1 min-h-0 overflow-y-auto">
                        <template x-for="(item, index) in cart" :key="item.product_id">
                            <div class="cart-row h-[52px] border-b border-[#d1d5db] flex items-center border-l-4"
                                 :class="[index % 2 === 0 ? 'bg-white' : 'bg-[#e6e8ec]', index === cart.length - 1 ? 'border-l-emerald-600' : 'border-l-transparent']"
                                 style="display:grid;" data-pdv-cols>
                                <div class="px-3 text-[13px] font-bold text-[#6b7280]" x-text="index + 1"></div>
                                <div class="px-1 min-w-0 flex items-center gap-2">
                                    <div class="min-w-0">
                                        <div class="font-bold text-gray-900 text-sm truncate" x-text="item.name"></div>
                                        <div class="text-[11px] text-[#6b7280]" x-text="'Cód #' + item.code + ' · SKU ' + item.sku"></div>
                                    </div>
                                </div>
                                <div class="px-3 text-right">
                                    <template x-if="allowPriceEdit">
                                        <input type="number" step="0.01" min="0" x-model.number="item.unit_price" @input="updateItemTotal(index)"
                                               @keydown.enter.prevent="focusBarcodeInput()"
                                               class="item-price-input w-full text-right font-mono font-bold text-sm border-gray-300 rounded py-1 tabular-nums">
                                    </template>
                                    <template x-if="!allowPriceEdit">
                                        <span class="whitespace-nowrap font-mono text-[12px] xl:text-[14px] text-[#374151] tabular-nums" x-text="'R$ ' + formatMoney(item.unit_price)"></span>
                                    </template>
                                </div>
                                <div class="px-3 flex items-center justify-center">
                                    <div class="inline-flex items-center border border-[#9aa1ad] rounded-lg h-[30px]">
                                        <button type="button" @click="changeQty(index, -1)" class="w-[30px] h-full flex items-center justify-center text-gray-600 hover:bg-gray-100 text-base font-bold">-</button>
                                        <input type="number" step="any" min="0.001" x-model.number="item.quantity" @input="updateItemTotal(index)"
                                               @keydown.enter.prevent="allowPriceEdit ? focusNextInRow($event, '.item-price-input') : focusBarcodeInput()"
                                               class="item-qty-input w-14 px-1 h-full text-center font-bold font-mono text-sm border-x border-[#c3c8d1] focus:ring-0">
                                        <button type="button" @click="changeQty(index, 1)" class="w-[30px] h-full flex items-center justify-center text-gray-600 hover:bg-gray-100 text-base font-bold">+</button>
                                    </div>
                                </div>
                                <div class="px-2 text-right whitespace-nowrap font-mono font-extrabold text-[13px] xl:text-[15px] text-[#047857] tabular-nums" x-text="'R$ ' + formatMoney(item.total_price)"></div>
                                <div class="flex items-center justify-center">
                                    <button type="button" @click="removeItem(index)" class="w-[30px] h-[30px] inline-flex items-center justify-center text-[#be123c] hover:bg-[#ffe4e6] rounded font-bold text-lg">&times;</button>
                                </div>
                            </div>
                        </template>
                        <div x-show="cart.length === 0" class="px-4 py-20 text-center text-gray-400 italic text-sm">
                            <span x-text="activeShift ? 'Nenhum produto no caixa. Escaneie um código ou use [F2].' : 'Caixa fechado. Clique em Abrir Turno de Caixa para operar.'"></span>
                        </div>
                    </div>

                    {{-- Rodape da lista --}}
                    <div class="shrink-0 h-[34px] bg-[#e5e7eb] border-t border-[#9aa1ad] px-3 flex items-center text-[13px] text-gray-700">
                        <span class="font-bold" x-text="cart.length"></span>&nbsp;itens &middot; <span class="font-bold ml-1" x-text="totalUnits"></span>&nbsp;unidades
                    </div>
                </div>
            </div>

            {{-- Coluna direita: Resumo da venda --}}
            <div class="self-start rounded-[10px] overflow-hidden" style="background:#111827">
                <div class="h-9 flex items-center px-3.5 text-[12px] font-bold uppercase tracking-widest text-[#9ca3af] border-b border-[#374151]">
                    Resumo da Venda
                </div>
                <div class="px-3.5 pt-3 pb-3.5 flex flex-col gap-2.5">
                    <div>
                        <div class="text-[12px] font-bold uppercase tracking-widest text-[#9ca3af]">Total dos Itens</div>
                        <div class="font-extrabold tabular-nums" style="font-size:40px;color:#34d399" x-text="'R$ ' + formatMoney(subtotal)"></div>
                    </div>

                    <button type="button" @click="goToPayment()" :disabled="!activeShift || cart.length === 0"
                            :class="activeShift && cart.length > 0 ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-700 opacity-50 cursor-not-allowed'"
                            class="h-12 text-white font-extrabold rounded-lg text-base transition">
                        IR PARA PAGAMENTO [F4] &rarr;
                    </button>


                    <input type="text" x-model="notes" :disabled="!activeShift" placeholder="Observações (cupom / romaneio)…"
                           class="h-8 rounded-lg bg-[#1f2937] border border-[#4b5563] text-sm text-gray-100 placeholder-[#9ca3af] px-2.5">
                </div>
            </div>
        </div>

        {{-- Corpo: etapa 2 (pagamento) --}}
        <div x-show="currentStep === 'pagamento'" x-cloak
             @keydown.window.escape="if (currentStep === 'pagamento' && !isProcessing && !emitNfModal.open && !anyModalOpen()) currentStep = 'carrinho'"
             class="flex-1 min-h-0 px-4 py-3 flex justify-center" style="display:none;">
            <div class="w-full h-full" style="display:grid;justify-content:center;gap:12px;" data-pdv-pay-cols>

                {{-- Esquerda: Pagamento, descontos e creditos --}}
                <div class="bg-white border border-[#9aa1ad] rounded-[10px] flex flex-col min-h-0 overflow-hidden">
                    <div class="h-9 shrink-0 flex items-center px-3.5 bg-[#e5e7eb] text-sm font-bold text-gray-800">
                        Pagamento, descontos e créditos
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto">
                        {{-- Forma de pagamento --}}
                        <div class="px-3.5 py-3 border-b border-[#c3c8d1] space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wide text-[#374151]">Forma de Pagamento <span class="normal-case font-semibold text-gray-400">· tecle 1–N</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" :class="newPaymentMethod ? 'text-gray-500' : 'text-[#b45309]'">
                                    <template x-if="paymentIconGroup(newPaymentMethod) === 'money'"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/></svg></template>
                                    <template x-if="paymentIconGroup(newPaymentMethod) === 'pix'"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></template>
                                    <template x-if="paymentIconGroup(newPaymentMethod) === 'card'"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></template>
                                    <template x-if="paymentIconGroup(newPaymentMethod) === 'account'"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/></svg></template>
                                    <template x-if="paymentIconGroup(newPaymentMethod) === 'barcode'"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v16M8 4v16M11 4v16M15 4v16M17 4v16M20 4v16"/></svg></template>
                                    <template x-if="paymentIconGroup(newPaymentMethod) === 'voucher'"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8a2 2 0 012-2h12a2 2 0 012 2v1a2 2 0 000 4v1a2 2 0 01-2 2H6a2 2 0 01-2-2v-1a2 2 0 000-4V8z"/></svg></template>
                                    <template x-if="paymentIconGroup(newPaymentMethod) === 'bank'"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10l9-6 9 6M4 10v9h16v-9M9 19v-6h6v6"/></svg></template>
                                    <template x-if="paymentIconGroup(newPaymentMethod) === 'check'"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="1"/><path d="M6 16h6"/></svg></template>
                                    <template x-if="paymentIconGroup(newPaymentMethod) === 'other'"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="12" r="5"/><circle cx="16" cy="12" r="5"/></svg></template>
                                </div>
                                <select x-model="newPaymentMethod" x-ref="paymentMethodSelect" :disabled="!activeShift"
                                        @change="onPaymentMethodPicked()"
                                        :class="newPaymentMethod ? 'border border-[#9aa1ad] text-gray-900 font-bold' : 'border-2 border-[#d97706] ring ring-[#fef3c7] focus:border-[#d97706] focus:ring-[#fef3c7] text-[#6b7280]'"
                                        class="w-full h-11 rounded-lg pl-9 pr-8 text-base appearance-none bg-white">
                                    <option value="" x-bind:selected="!newPaymentMethod" disabled>Selecione a forma…</option>
                                    @foreach ($paymentOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" /></svg>
                                </div>
                            </div>

                            <div x-show="installmentMethods.includes(newPaymentMethod)" x-cloak class="grid grid-cols-2 gap-2 p-2.5 bg-indigo-50/50 rounded-lg border border-indigo-100 text-sm">
                                <div>
                                    <label class="block text-xs font-bold text-indigo-900">Parcelas:</label>
                                    <select x-model.number="newPaymentInstallments" class="w-full rounded-lg border-gray-300 text-sm py-1.5">
                                        <template x-for="i in 12" :key="i">
                                            <option :value="i" x-text="i + 'x'"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-indigo-900">1º Vencimento:</label>
                                    <input type="date" x-model="newPaymentDueDate" class="w-full rounded-lg border-gray-300 text-sm py-1.5">
                                </div>
                            </div>

                        </div>

                        {{-- Desconto --}}
                        <div class="px-3.5 py-3 border-b border-[#c3c8d1] space-y-1.5">
                            <label class="text-[11px] font-bold uppercase tracking-wide text-[#374151]">Desconto</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="relative">
                                    <input type="number" step="0.01" min="0" max="100" x-model.number="discountPercent" :disabled="!activeShift"
                                           class="w-full h-10 rounded-lg border-gray-300 text-sm font-mono font-bold pr-7">
                                    <span class="absolute inset-y-0 right-2.5 flex items-center text-xs text-gray-400 font-semibold">%</span>
                                </div>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-2.5 flex items-center text-xs text-gray-400 font-semibold">R$</span>
                                    <input type="number" step="0.01" min="0" x-model.number="discountFixed" :disabled="!activeShift"
                                           class="w-full h-10 rounded-lg border-gray-300 text-sm font-mono font-bold pl-8">
                                </div>
                            </div>
                            <div class="flex justify-between text-[13px] font-bold text-[#be123c]">
                                <span>Abatimento total:</span>
                                <span class="font-mono" x-text="'- R$ ' + formatMoney(totalDiscountAmount)"></span>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Direita: Valores --}}
                <div class="bg-white border border-[#9aa1ad] rounded-[10px] flex flex-col min-h-0 overflow-hidden">
                    <div class="h-9 shrink-0 flex items-center px-3.5 bg-[#e5e7eb] border-b border-[#9aa1ad] text-sm font-bold text-gray-800">
                        Valores
                    </div>

                    <div class="h-9 shrink-0 flex items-center justify-between px-3.5 border-b border-[#c3c8d1]">
                        <span class="text-sm text-[#374151]">Subtotal</span>
                        <span class="text-[15px] font-bold text-gray-900 tabular-nums" x-text="'R$ ' + formatMoney(subtotal)"></span>
                    </div>
                    <div x-show="totalDiscountAmount > 0" class="h-9 shrink-0 flex items-center justify-between px-3.5 border-b border-[#c3c8d1]">
                        <span class="text-sm text-[#374151]" x-text="'Desconto' + (discountPercent > 0 && discountFixed > 0 ? ' (' + discountPercent + '% + R$ ' + formatMoney(discountFixed) + ')' : (discountPercent > 0 ? ' (' + discountPercent + '%)' : ''))"></span>
                        <span class="text-[15px] font-bold text-[#be123c] tabular-nums" x-text="'- R$ ' + formatMoney(totalDiscountAmount)"></span>
                    </div>
                    <div class="h-9 shrink-0 flex items-center justify-between px-3.5 border-b border-[#c3c8d1]" :class="totalPaid > totalPayable ? 'bg-[#ecfdf5]' : ''">
                        <span class="text-sm text-[#374151]">Troco</span>
                        <span class="font-extrabold tabular-nums" :class="totalPaid > totalPayable ? 'text-[#047857] text-[17px]' : 'text-[#6b7280] text-[17px]'"
                              x-text="totalPaid > totalPayable ? 'R$ ' + formatMoney(totalPaid - totalPayable) : 'R$ 0,00'"></span>
                    </div>
                    <div class="h-9 shrink-0 flex items-center justify-between px-3.5 border-b border-[#a5b4fc]" style="background:#eef2ff">
                        <span class="text-sm font-bold" style="color:#3730a3">Falta receber</span>
                        <span class="font-extrabold text-[17px] tabular-nums" :class="remainingToPay <= 0 && payments.length > 0 ? 'text-emerald-600' : ''" style="color:#3730a3"
                              x-text="remainingToPay <= 0 && payments.length > 0 ? 'Quitado' : 'R$ ' + formatMoney(remainingToPay)"></span>
                    </div>
                    <div class="h-[46px] shrink-0 flex items-center justify-between px-3.5 border-b border-[#6ee7b7]" style="background:#ecfdf5">
                        <span class="text-[13px] font-extrabold" style="color:#065f46">TOTAL A PAGAR</span>
                        <span class="font-extrabold text-2xl tabular-nums" style="color:#047857" x-text="'R$ ' + formatMoney(totalPayable)"></span>
                    </div>

                    <div class="h-6 shrink-0 bg-[#374151] text-white text-[11px] font-bold flex items-center justify-between px-3.5">
                        <span>PAGAMENTOS</span>
                        <span x-text="'R$ ' + formatMoney(totalPaid)"></span>
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto">
                        <template x-for="(p, idx) in payments" :key="idx">
                            <div class="h-8 flex items-center justify-between px-3.5 text-sm"
                                 :class="idx % 2 === 0 ? 'bg-white' : 'bg-[#e6e8ec]'">
                                <span class="font-bold text-gray-800 truncate" x-text="(p.label || getPaymentLabel(p.method)) + (p.installments > 1 ? ' (' + p.installments + 'x)' : '')"></span>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="font-mono font-extrabold text-gray-900 tabular-nums" x-text="'R$ ' + formatMoney(p.amount)"></span>
                                    <button type="button" @click="removePayment(idx)" class="text-[#be123c] hover:text-rose-800 font-bold text-lg leading-none">&times;</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Rodape fixo: valor + concluir --}}
                    <div class="shrink-0 border-t border-[#c3c8d1] bg-[#f9fafb] px-3.5 pt-2 pb-2.5 space-y-1.5">
                        <label class="block text-[10px] font-bold whitespace-nowrap" :class="newPaymentMethod ? 'text-[#374151]' : 'text-[#b45309]'">
                            <span x-show="!newPaymentMethod">SELECIONE A FORMA DE PAGAMENTO (1–N)</span>
                            <span x-show="newPaymentMethod && !isSpecialMethod(newPaymentMethod)" x-text="'VALOR EM ' + getPaymentLabel(newPaymentMethod).toUpperCase()"></span>
                            <span x-show="isSpecialMethod(newPaymentMethod)">LANCE PELO PAINEL AO LADO</span>
                        </label>
                        <div class="flex gap-1.5">
                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-2.5 flex items-center text-xs text-gray-400 font-semibold">R$</span>
                                <input type="number" step="0.01" min="0.01" x-ref="paymentValueInput"
                                       :value="newPaymentAmountOverride !== null ? newPaymentAmountOverride : remainingToPay"
                                       @input="newPaymentAmountOverride = parseFloat($event.target.value) || 0"
                                       @keydown.enter.prevent="addPayment()"
                                       :disabled="!activeShift || !newPaymentMethod || isSpecialMethod(newPaymentMethod)"
                                       :class="newPaymentMethod && !isSpecialMethod(newPaymentMethod) ? 'border-2 border-emerald-600 focus:ring focus:ring-[#d1fae5]' : 'border-dashed border-[#9aa1ad] bg-[#f3f4f6] text-[#9ca3af]'"
                                       class="w-full h-[38px] rounded-lg pl-8 text-[17px] font-extrabold font-mono disabled:cursor-not-allowed">
                            </div>
                            <button type="button" @click="addPayment()" :disabled="!activeShift || !newPaymentMethod || isSpecialMethod(newPaymentMethod)"
                                    :class="newPaymentMethod && !isSpecialMethod(newPaymentMethod) ? 'bg-[#1f2937] hover:bg-gray-800 text-white' : 'bg-gray-100 border border-dashed border-[#9aa1ad] text-[#9ca3af] cursor-not-allowed'"
                                    class="h-[38px] px-4 rounded-lg text-[13px] font-bold shrink-0">
                                + Adicionar <span class="opacity-60">[Enter]</span>
                            </button>
                        </div>

                        <button type="button" @click="submitSale()" :disabled="!activeShift || cart.length === 0 || isProcessing"
                                :class="activeShift && cart.length > 0 && !isProcessing ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-300 cursor-not-allowed'"
                                class="w-full h-[46px] text-white font-extrabold rounded-xl shadow transition text-base tracking-wide flex justify-center items-center gap-2">
                            <span x-show="!isProcessing">&#10003; CONCLUIR VENDA [F10]</span>
                            <span x-show="isProcessing">GRAVANDO...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Barra de atalhos (30px) --}}
        <div class="h-[30px] shrink-0 bg-[#374151] flex items-center gap-2 px-3 text-[12px] text-[#e5e7eb]">
            <template x-if="currentStep === 'carrinho'">
                <div class="flex items-center gap-2">
                    <button type="button" @click="openStockModal = true" :disabled="!activeShift"
                            class="h-[22px] px-2 rounded-[5px] bg-[#4b5563] hover:bg-gray-600 disabled:opacity-40 font-bold text-white cursor-pointer">F2 Consultar estoque</button>
                    <button type="button" @click="openMovementModal = true" :disabled="!activeShift"
                            class="h-[22px] px-2 rounded-[5px] bg-[#4b5563] hover:bg-gray-600 disabled:opacity-40 font-bold text-white cursor-pointer">F12 Sangria / Suprimento</button>
                    <span><strong class="text-white">F4</strong> Pagamento</span>
                    <span>&middot; <strong class="text-white">Enter</strong> Adicionar item</span>
                    <span>&middot; <strong class="text-white">3*código</strong> Quantidade</span>
                    <span>&middot; <strong class="text-white">Del</strong> Remover último</span>
                </div>
            </template>
            <template x-if="currentStep === 'pagamento'">
                <div class="flex items-center gap-2">
                    <span><strong class="text-white">1–9</strong> Forma</span>
                    <span>&middot; <strong class="text-white">Enter</strong> Adicionar valor</span>
                    <span>&middot; <strong class="text-white">F10</strong> Concluir</span>
                    <span>&middot; <strong class="text-white">Esc</strong> Voltar aos itens</span>
                </div>
            </template>
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
                        <label class="block font-bold text-gray-700">Observações de Abertura</label>
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
                <div class="flex justify-between items-center border-b pb-2"><h3 class="text-sm font-bold text-gray-900">Movimentação de Gaveta</h3><button type="button" @click="openMovementModal = false" class="text-gray-400 font-bold text-lg">&times;</button></div>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700">Tipo de Operação *</label>
                        <select x-model="drawerMovementType" class="w-full rounded border-gray-300 text-xs font-bold">
                            <option value="outflow">Sangria (Retirada de Dinheiro para Cofre/Pagamento)</option>
                            <option value="inflow">Suprimento (Reforço de Fundo de Troco)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700">Valor da Operação (R$) *</label>
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
                    <p class="text-xs text-gray-500">Conferência de valores físicos na gaveta.</p>
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
                        <span x-show="(shiftClosingBalance - liveShiftSummary.expected_drawer_cash) === 0">✓ Conciliação Exata (Diferença: R$ 0,00)</span>
                        <span x-show="(shiftClosingBalance - liveShiftSummary.expected_drawer_cash) > 0" x-text="'Sobra de Caixa: + R$ ' + formatMoney(shiftClosingBalance - liveShiftSummary.expected_drawer_cash)"></span>
                        <span x-show="(shiftClosingBalance - liveShiftSummary.expected_drawer_cash) < 0" x-text="'Quebra de Caixa: - R$ ' + formatMoney(Math.abs(shiftClosingBalance - liveShiftSummary.expected_drawer_cash))"></span>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700">Observações de Fechamento</label>
                        <textarea x-model="shiftClosingNotes" rows="2" placeholder="Ex: Turno encerrado sem divergências..." class="w-full rounded border-gray-300 text-xs"></textarea>
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
                    <h3 class="text-sm font-bold text-gray-900">Cadastro Rápido de Cliente</h3>
                    <button type="button" @click="openCustomerModal = false" :disabled="isSavingCustomer" class="text-gray-400 font-bold text-lg">&times;</button>
                </div>
                <div class="space-y-2 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700">Nome / Razão Social *</label>
                        <input type="text" x-model="newCustomer.name" :disabled="isSavingCustomer" class="uppercase w-full rounded border-gray-300 text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700">CPF / CNPJ</label>
                        <input type="text" inputmode="numeric" maxlength="18" placeholder="000.000.000-00"
                               :value="newCustomer.document" @input="newCustomer.document = maskDocument($event.target.value); $event.target.value = newCustomer.document"
                               :disabled="isSavingCustomer"
                               :class="documentError ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-200' : (documentDigits.length ? 'border-emerald-500' : 'border-gray-300')"
                               class="w-full rounded text-xs">
                        <p x-show="documentError" x-cloak class="mt-0.5 text-[11px] font-semibold text-rose-600" x-text="documentError"></p>
                        <p x-show="!documentError && (documentDigits.length === 11 || documentDigits.length === 14)" x-cloak class="mt-0.5 text-[11px] font-semibold text-emerald-600"
                           x-text="(documentDigits.length === 11 ? 'CPF' : 'CNPJ') + ' válido'"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-gray-700">Telefone</label>
                            <input type="text" x-model="newCustomer.phone" :disabled="isSavingCustomer" class="w-full rounded border-gray-300 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700">Limite Crédito (R$)</label>
                            <input type="number" step="10" x-model.number="newCustomer.credit_limit" :disabled="isSavingCustomer" class="w-full rounded border-gray-300 text-xs">
                        </div>
                    </div>
                </div>
                <div class="pt-2 border-t flex justify-end gap-2">
                    <button type="button" @click="openCustomerModal = false" :disabled="isSavingCustomer" class="px-3 py-1 bg-gray-100 rounded text-xs font-bold text-gray-600">Cancelar</button>
                    <button type="button" @click="saveQuickCustomer()" :disabled="isSavingCustomer || !newCustomer.name || !!documentError" class="px-4 py-1 bg-indigo-600 text-white rounded text-xs font-bold hover:bg-indigo-700 disabled:bg-gray-400">
                        <span x-show="!isSavingCustomer">Salvar Cliente</span>
                        <span x-show="isSavingCustomer">Salvando...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- MODAL: Trocar tabela de preco pela do cliente? (so' pergunta quando ha itens no carrinho) --}}
        <div x-show="priceTableAsk" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl max-w-md w-full p-5 shadow-2xl space-y-4" @keydown.window.escape.prevent="priceTableAsk && keepPriceTable()">
                <h3 class="text-base font-bold text-gray-900">Trocar a tabela de preço?</h3>
                <p class="text-sm text-gray-600" x-show="priceTableAsk">
                    O cliente <strong x-text="priceTableAsk?.customer"></strong> usa a tabela
                    <strong x-text="priceTableLabel(priceTableAsk?.to)"></strong>, e esta venda está em
                    <strong x-text="priceTableLabel(priceTableAsk?.from)"></strong>.
                    Trocar recalcula o preço de todos os itens já lançados, inclusive os que você ajustou.
                </p>
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="keepPriceTable()" class="flex-1 py-2.5 bg-gray-100 rounded-lg text-sm font-bold text-gray-600">Manter <span x-text="priceTableLabel(priceTableAsk?.from)"></span></button>
                    <button type="button" @click="applyPriceTable(priceTableAsk.to)" class="flex-1 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700">Trocar para <span x-text="priceTableLabel(priceTableAsk?.to)"></span></button>
                </div>
            </div>
        </div>

        {{-- MODAL 5: Consulta Estoque F2 --}}
        <div x-show="openStockModal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4" style="display: none;">
            <div @click.away="openStockModal = false" class="bg-white rounded-2xl max-w-3xl w-full p-5 shadow-2xl space-y-3">
                <div class="flex justify-between items-center border-b pb-2"><h3 class="text-base font-bold text-gray-900">Consulta Rápida de Estoque [F2]</h3><button type="button" @click="openStockModal = false" class="text-gray-400 font-bold text-lg">&times;</button></div>
                <input type="text" x-model="modalFilter" placeholder="Filtrar por nome, SKU, código simples..." class="w-full rounded-lg border-gray-300 text-xs font-semibold">
                <div class="max-h-72 overflow-y-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-xs text-left">
                        <thead class="bg-gray-50 text-[10px] text-gray-500 uppercase font-semibold"><tr><th class="px-3 py-1.5">Cod</th><th class="px-3 py-1.5">Nome</th><th class="px-3 py-1.5">Saldo</th><th class="px-3 py-1.5">Preço</th><th class="px-3 py-1.5 text-right">Ação</th></tr></thead>
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

        {{-- Modal unico de pos-venda: imprimir comprovante ou continuar --}}
        <div x-show="emitNfModal.open" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-60 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl space-y-4" @keydown.window.escape.prevent="emitNfModal.open && emitNfModal.stage === 'choose' && skipEmitNf()">
                <template x-if="emitNfModal.stage === 'choose'">
                    <div class="space-y-4 text-center">
                        <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full mx-auto flex items-center justify-center text-3xl font-bold">&#10003;</div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900" x-text="'Venda #' + completedSaleCode + ' Concluída!'"></h3>
                            <p class="text-xs text-gray-500 mt-1">Baixa de estoque efetuada no Kardex.</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-xl border text-xs space-y-1.5 text-left">
                            <div class="flex justify-between"><span class="text-gray-500">Valor Total:</span><span class="font-bold text-gray-900 font-mono" x-text="'R$ ' + formatMoney(completedTotal)"></span></div>
                            <div class="flex justify-between" x-show="completedDiscount > 0"><span class="text-gray-500">Desconto:</span><span class="font-bold text-rose-600 font-mono" x-text="'- R$ ' + formatMoney(completedDiscount)"></span></div>
                            <div class="flex justify-between" x-show="completedReceived > 0"><span class="text-gray-500">Total Recebido:</span><span class="font-bold text-gray-900 font-mono" x-text="'R$ ' + formatMoney(completedReceived)"></span></div>
                            <div class="flex justify-between p-2 bg-emerald-50 rounded border border-emerald-200" x-show="completedChange > 0"><span class="font-bold text-emerald-800">Troco a Devolver:</span><span class="font-extrabold text-emerald-700 font-mono text-sm" x-text="'R$ ' + formatMoney(completedChange)"></span></div>
                        </div>
                        <div class="space-y-2 pt-2">
                            <button type="button" @click="printReceiptOnly()" class="w-full py-3 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-md">Imprimir Comprovante</button>
                            <button type="button" @click="skipEmitNf()" class="w-full py-2 text-xs font-semibold text-gray-400 hover:text-gray-600">Continuar / Próxima Venda [Esc]</button>
                        </div>
                    </div>
                </template>

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
                priceTableAsk: null,
                discountPercent: 0,
                discountFixed: 0,
                notes: '',
                currentStep: 'carrinho', // 'carrinho' | 'pagamento' - o pagamento e' uma tela propria
                payments: [],
                newPaymentMethod: '',
                newPaymentAmountOverride: null,
                newPaymentInstallments: 1,
                newPaymentDueDate: new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0],
                newCustomer: { name: '', document: '', phone: '', credit_limit: 500 },
                toasts: [],
                openStockModal: false,
                openCustomerModal: false,
                emitNfModal: { open: false, stage: 'choose', saleId: null, number: null, danfeUrl: null, message: '', reviewUrl: null },
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

                paymentLabels: {{ Js::from($paymentOptions) }},
                // Formas que geram conta a receber (exigem cliente) e mostram Parcelas.
                receivableMethods: ['on_account'],
                installmentMethods: ['on_account'],

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

                    // Atalhos 1-9/Alt+1-9 (etapa pagamento) e F10 - registrados aqui
                    // (nao via @keydown.window no template) porque precisam ler o
                    // foco atual do documento a cada tecla.
                    window.addEventListener('keydown', (event) => this.handlePaymentStepKeydown(event));
                },

                anyModalOpen() {
                    return this.openStockModal || this.openCustomerModal || this.openShiftModal
                        || this.openMovementModal || this.openCloseShiftModal || this.emitNfModal.open
                        || !!this.priceTableAsk;
                },

                handlePaymentStepKeydown(event) {
                    if (this.currentStep !== 'pagamento' || this.anyModalOpen()) return;

                    let focusTag = document.activeElement ? document.activeElement.tagName : '';
                    let focusedInField = ['INPUT', 'SELECT', 'TEXTAREA'].includes(focusTag);

                    if (event.key === 'F10') {
                        event.preventDefault();
                        this.submitSale();
                        return;
                    }

                    let digit = parseInt(event.key, 10);
                    if (!Number.isInteger(digit) || digit < 1 || digit > 9) return;
                    if (focusedInField && !event.altKey) return;

                    let select = this.$refs.paymentMethodSelect;
                    if (!select) return;
                    // options[0] e' o placeholder "Selecione a forma..." - as formas
                    // de verdade comecam em options[1].
                    let option = select.options[digit];
                    if (!option) return;

                    event.preventDefault();
                    this.newPaymentMethod = option.value;
                    this.onPaymentMethodPicked();
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

                paymentIconGroup(method) {
                    if (!method) return 'other';
                    if (method === 'money') return 'money';
                    if (['pix', 'pix_static', 'pix_auto'].includes(method)) return 'pix';
                    if (['debit_card', 'credit_card', 'store_card'].includes(method)) return 'card';
                    if (['on_account', 'crediario'].includes(method)) return 'account';
                    if (['boleto', 'trade_bill'].includes(method)) return 'barcode';
                    if (['food_voucher', 'meal_voucher', 'gift_voucher', 'fuel_voucher', 'exchange_ticket'].includes(method)) return 'voucher';
                    if (method === 'store_credit') return 'account';
                    if (['bank_transfer', 'bank_deposit', 'book_transfer'].includes(method)) return 'bank';
                    if (method === 'check') return 'check';
                    return 'other';
                },

                onCustomerSelected() {
                    let cust = this.customers.find(c => c.id == this.selectedCustomerId);
                    if (!cust) return;

                    if (!cust.default_price_table || cust.default_price_table === this.priceTable) {
                        // segue pra checagem de tabela abaixo mesmo sem troca de tabela
                    } else if (this.cart.length === 0) {
                        this.applyPriceTable(cust.default_price_table);
                    } else {
                        this.priceTableAsk = { customer: cust.name, from: this.priceTable, to: cust.default_price_table };
                    }
                },

                // Haver e ticket de troca nao existem nesta versao: nenhuma forma e' especial.
                isSpecialMethod(method) {
                    return false;
                },

                priceTableLabel(table) {
                    return { retail: 'Varejo', resale: 'Revenda', wholesale: 'Atacado' }[table] || table;
                },

                applyPriceTable(table) {
                    this.priceTable = table;
                    this.recalcCartPrices();
                    this.priceTableAsk = null;
                    this.triggerToast('Tabela de preço alterada para ' + this.priceTableLabel(table) + '.', 'warning');
                },

                keepPriceTable() {
                    this.priceTableAsk = null;
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + item.total_price, 0);
                },

                get totalUnits() {
                    return this.cart.reduce((sum, item) => sum + item.quantity, 0);
                },

                // Mesma conta do SaleService: arredonda a parte em % pra centavos ANTES
                // de somar o R$ - senao a tela mostra 1 centavo diferente do servidor e
                // o pagamento exato (ex: PIX) e' recusado como "troco fora do dinheiro".
                get totalDiscountAmount() {
                    const round2 = (v) => Math.round((v + Number.EPSILON) * 100) / 100;
                    const fromPercent = round2(this.subtotal * ((this.discountPercent || 0) / 100));
                    return round2(fromPercent + (this.discountFixed || 0));
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
                        (p.sku && p.sku.toLowerCase() === q.toLowerCase())
                    );

                    if (found) {
                        this.addProductToCartWithQty(found, qty);
                        this.searchQuery = '';
                    } else {
                        this.triggerToast('Produto não localizado por código simples, SKU ou código de barras.', 'warning');
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
                    let row = event.target.closest('.cart-row');
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

                onPaymentMethodPicked() {
                    if (!this.newPaymentMethod || this.isSpecialMethod(this.newPaymentMethod)) return;
                    this.newPaymentAmountOverride = null;
                    this.$nextTick(() => {
                        if (this.$refs.paymentValueInput) {
                            this.$refs.paymentValueInput.focus();
                            this.$refs.paymentValueInput.select();
                        }
                    });
                },

                addPayment() {
                    if (!this.newPaymentMethod) return;
                    let amountToAdd = this.newPaymentAmountOverride !== null ? this.newPaymentAmountOverride : this.remainingToPay;
                    if (amountToAdd <= 0) return;

                    if (this.receivableMethods.includes(this.newPaymentMethod) && !this.selectedCustomerId) {
                        this.triggerToast('Para pagamentos A Prazo, selecione um cliente cadastrado.', 'error');
                        return;
                    }

                    this.payments.push({
                        method: this.newPaymentMethod,
                        amount: parseFloat(amountToAdd.toFixed(2)),
                        installments: this.newPaymentInstallments,
                        first_due_date: this.newPaymentDueDate
                    });

                    this.newPaymentMethod = '';
                    this.newPaymentAmountOverride = null;
                    this.$nextTick(() => {
                        if (this.$refs.paymentMethodSelect) this.$refs.paymentMethodSelect.focus();
                    });
                },

                removePayment(index) {
                    this.payments.splice(index, 1);
                    this.newPaymentAmountOverride = null;
                },

                // --- Verificador de CPF/CNPJ do cadastro rapido ---
                get documentDigits() {
                    return (this.newCustomer.document || '').replace(/\D/g, '');
                },

                // Mensagem de erro do documento ('' = ok, ou campo vazio, que e' opcional).
                get documentError() {
                    let d = this.documentDigits;
                    if (d.length === 0) return '';
                    if (d.length < 11) return 'CPF incompleto (11 dígitos).';
                    if (d.length === 11) return this.isValidCpf(d) ? '' : 'CPF inválido: confira os dígitos.';
                    if (d.length < 14) return 'CNPJ incompleto (14 dígitos).';
                    return this.isValidCnpj(d) ? '' : 'CNPJ inválido: confira os dígitos.';
                },

                maskDocument(value) {
                    let d = (value || '').replace(/\D/g, '').slice(0, 14);
                    if (d.length <= 11) {
                        return d.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                    }
                    return d.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                            .replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
                },

                isValidCpf(cpf) {
                    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
                    for (let t = 9; t < 11; t++) {
                        let sum = 0;
                        for (let i = 0; i < t; i++) sum += parseInt(cpf[i], 10) * (t + 1 - i);
                        let digit = ((sum * 10) % 11) % 10;
                        if (digit !== parseInt(cpf[t], 10)) return false;
                    }
                    return true;
                },

                isValidCnpj(cnpj) {
                    if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) return false;
                    const calc = (len) => {
                        let sum = 0, pos = len - 7;
                        for (let i = 0; i < len; i++) {
                            sum += parseInt(cnpj[i], 10) * pos--;
                            if (pos < 2) pos = 9;
                        }
                        let r = sum % 11;
                        return r < 2 ? 0 : 11 - r;
                    };
                    return calc(12) === parseInt(cnpj[12], 10) && calc(13) === parseInt(cnpj[13], 10);
                },

                saveQuickCustomer() {
                    if (!this.newCustomer.name || this.isSavingCustomer) return;
                    if (this.documentError) {
                        this.triggerToast(this.documentError, 'error');
                        return;
                    }
                    this.isSavingCustomer = true;

                    fetch("{{ route('vendas.clientes.rapido') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
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
                            // 422 do Laravel: mostra o motivo real (ex.: CPF invalido ou ja cadastrado).
                            let firstError = res.errors ? Object.values(res.errors)[0][0] : null;
                            this.triggerToast(firstError || res.message || 'Erro ao cadastrar cliente.', 'error');
                        }
                    })
                    .catch(() => {
                        this.isSavingCustomer = false;
                        this.triggerToast('Erro de conexão ao salvar cliente.', 'error');
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
                            this.triggerToast(res.message || 'Não foi possível abrir o caixa.', 'error');
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
                            this.triggerToast(res.message || 'Não foi possível registrar o movimento.', 'error');
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
                            window.open("/vendas/caixa/comprovante/" + res.shift_id, "_blank", "noopener");
                            window.location.reload();
                        } else {
                            this.triggerToast(res.message || 'Não foi possível fechar o caixa.', 'error');
                        }
                    });
                },

                submitSale() {
                    if (!this.activeShift || this.cart.length === 0 || this.isProcessing) return;

                    // Valida se a empresa exige vendedor obrigatorio
                    if ({{ Js::from($tenant->require_seller_on_pos ?? false) }} && !this.selectedSellerId) {
                        this.triggerToast('A empresa exige a seleção de um Vendedor para concluir a venda.', 'error');
                        return;
                    }

                    // Se nao houver pagamento fracionado lancado, assume 100% no metodo selecionado -
                    // mas a forma precisa ter sido escolhida (newPaymentMethod nao pode ser null).
                    let finalPayments = this.payments.slice();
                    if (finalPayments.length === 0) {
                        if (!this.newPaymentMethod) {
                            this.triggerToast('Selecione a forma de pagamento.', 'error');
                            return;
                        }
                        if (this.receivableMethods.includes(this.newPaymentMethod) && !this.selectedCustomerId) {
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
                            this.emitNfModal = { open: true, stage: 'choose', saleId: res.sale_id, number: null, danfeUrl: null, message: '', reviewUrl: null };
                        } else {
                            this.triggerToast(res.message || 'Erro ao processar venda.', 'error');
                        }
                    })
                    .catch(() => {
                        this.isProcessing = false;
                        this.triggerToast('Erro de comunicação com o servidor.', 'error');
                    });
                },

                printReceiptOnly() {
                    if (this.completedSaleId) {
                        window.open("/vendas/recibo/" + this.completedSaleId, "_blank", "noopener");
                    }
                    this.emitNfModal.open = false;
                    this.resetForNextSale();
                },

                skipEmitNf() {
                    this.emitNfModal.open = false;
                    this.resetForNextSale();
                },

                finishAfterEmitNf() {
                    this.emitNfModal.open = false;
                    this.resetForNextSale();
                },

                goToPayment() {
                    if (!this.activeShift || this.cart.length === 0) return;

                    if ({{ Js::from($tenant->require_seller_on_pos ?? false) }} && !this.selectedSellerId) {
                        this.triggerToast('A empresa exige a seleção de um Vendedor para continuar.', 'error');
                        return;
                    }

                    this.newPaymentMethod = '';
                    this.newPaymentAmountOverride = null;
                    this.currentStep = 'pagamento';
                    this.$nextTick(() => {
                        if (this.$refs.paymentMethodSelect) this.$refs.paymentMethodSelect.focus();
                    });
                },

                resetForNextSale() {
                    this.currentStep = 'carrinho';
                    this.cart = [];
                    this.payments = [];
                    this.discountPercent = 0;
                    this.discountFixed = 0;
                    this.notes = '';
                    this.selectedCustomerId = '';
                    this.selectedSellerId = '';
                    this.newPaymentMethod = '';
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
