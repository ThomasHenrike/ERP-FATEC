@props(['customers', 'placeholder' => 'Buscar por nome, documento ou telefone...', 'inputClass' => 'block w-full rounded-lg border-gray-300 shadow-sm text-sm'])

{{--
    Componente reutilizavel de busca de cliente (mesmo padrao de
    x-core::product-search). Ao selecionar (clique ou Enter), dispara o
    evento DOM "customer-selected" com o cliente escolhido - quem usa o
    componente escuta esse evento num elemento ancestral
    (ex: @customer-selected="customerId = $event.detail.id").
--}}
<div class="relative" x-data="customerSearchPicker({{ Js::from($customers) }})">
    <input type="text"
           x-model="query"
           @input="open = true; highlightedIndex = 0"
           @keydown.down.prevent="highlightNext()"
           @keydown.up.prevent="highlightPrev()"
           @keydown.enter.prevent="confirmSelection()"
           @focus="open = true"
           @click.outside="open = false"
           placeholder="{{ $placeholder }}"
           autocomplete="off"
           class="customer-search-input {{ $inputClass }}">

    <div x-show="open && results.length > 0" x-cloak
         class="absolute z-20 mt-1 min-w-[240px] w-max max-w-xs bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto">
        <template x-for="(customer, index) in results" :key="customer.id">
            <button type="button"
                    @click="select(customer)"
                    @mouseenter="highlightedIndex = index"
                    :class="index === highlightedIndex ? 'bg-indigo-50' : ''"
                    class="w-full text-left px-3 py-2 text-sm border-b border-gray-100 last:border-0">
                <span class="font-semibold text-gray-800" x-text="customer.name"></span>
                <span class="text-xs text-gray-400 ml-1" x-text="customer.document ? '(' + customer.document + ')' : ''"></span>
            </button>
        </template>
    </div>

    <div x-show="open && query.length > 0 && results.length === 0" x-cloak
         class="absolute z-20 mt-1 min-w-[240px] w-max bg-white border border-gray-200 rounded-lg shadow-lg px-3 py-2 text-xs text-gray-400 italic">
        Nenhum cliente encontrado.
    </div>
</div>
