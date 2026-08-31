<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Nome da Categoria / Subcategoria *</label>
            <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required placeholder="Ex: Ar Condicionado Split Inverter"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm">
            @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase">Categoria Pai (Opcional para Subcategoria)</label>
            <select name="parent_id" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm font-semibold">
                <option value="">Nenhuma (Esta e uma Categoria Principal)</option>
                @foreach ($parentCategories as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id ?? '') == $parent->id)>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="pt-4 border-t flex items-center justify-between">
        <label class="inline-flex items-center">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))
                   class="rounded border-gray-300 text-indigo-600 shadow-sm">
            <span class="ml-2 text-sm text-gray-700 font-medium">Categoria Ativa</span>
        </label>

        <div class="flex gap-2">
            <a href="{{ route('estoque.categorias.index') }}" class="px-4 py-2 bg-gray-100 rounded-lg text-xs font-bold text-gray-600">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 shadow-sm">Salvar Categoria</button>
        </div>
    </div>
</div>