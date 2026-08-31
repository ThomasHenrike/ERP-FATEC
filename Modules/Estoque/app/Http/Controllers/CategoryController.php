<?php

declare(strict_types=1);

namespace Modules\Estoque\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Estoque\App\Models\Category;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Category::query()
            ->with(['parent', 'children'])
            ->latest('id');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->paginate(15)->withQueryString();

        return view('estoque::categories.index', compact('categories'));
    }

    public function create(): View
    {
        $parentCategories = Category::whereNull('parent_id')->active()->orderBy('name')->get();

        return view('estoque::categories.create', compact('parentCategories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = app('currentTenant')->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        Category::create($validated);

        return redirect()
            ->route('estoque.categorias.index')
            ->with('success', 'Categoria cadastrada com sucesso.');
    }

    public function edit(Category $category): View
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('estoque::categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return redirect()
            ->route('estoque.categorias.index')
            ->with('success', 'Categoria atualizada com sucesso.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()
            ->route('estoque.categorias.index')
            ->with('success', 'Categoria removida com sucesso.');
    }
}