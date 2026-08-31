@props(['products', 'placeholder' => 'Buscar por nome, SKU ou codigo...'])

{{--
    Componente reutilizavel de busca de produto. Ao selecionar um item (clique ou
    Enter), dispara o evento DOM "product-selected" com o produto escolhido - quem
    usa o componente escuta esse evento num elemento ancestral (ex: @product-selected="addProduto($event.detail)").
    Isso evita depender de $refs entre escopos diferentes do Alpine.
--}}
<div class="relative" x-data="productSearchPicker({{ Js::from($products) }})">
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
           class="product-search-input block w-full rounded-lg border-gray-300 shadow-sm text-sm">

    <div x-show="open && results.length > 0" x-cloak
         class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto">
        <template x-for="(product, index) in results" :key="product.id">
            <button type="button"
                    @click="select(product)"
                    @mouseenter="highlightedIndex = index"
                    :class="index === highlightedIndex ? 'bg-indigo-50' : ''"
                    class="w-full text-left px-3 py-2 text-sm border-b border-gray-100 last:border-0">
                <span class="font-mono text-xs text-indigo-600 font-bold" x-text="'#' + (product.code || product.id)"></span>
                <span class="font-semibold text-gray-800 ml-1" x-text="product.name"></span>
                <span class="text-xs text-gray-400 ml-1" x-text="product.sku ? '(' + product.sku + ')' : ''"></span>
            </button>
        </template>
    </div>

    <div x-show="open && query.length > 0 && results.length === 0" x-cloak
         class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg px-3 py-2 text-xs text-gray-400 italic">
        Nenhum produto encontrado.
    </div>
</div>
