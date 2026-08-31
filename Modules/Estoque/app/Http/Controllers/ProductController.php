<?php

declare(strict_types=1);

namespace Modules\Estoque\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Cadastros\App\Models\Supplier;
use Modules\Estoque\App\Http\Requests\StoreProductRequest;
use Modules\Estoque\App\Http\Requests\UpdateProductRequest;
use Modules\Estoque\App\Models\Category;
use Modules\Estoque\App\Models\Product;
use Modules\Estoque\App\Services\StockMovementService;

class ProductController extends Controller
{
    public function __construct(
        private readonly StockMovementService $stockService
    ) {}

    public function index(Request $request): View
    {
        $query = Product::query()
            ->with(['category', 'supplier'])
            ->latest('id');

        // Filtro de Status: Ativos por padrao, Inativos ou Todos
        $status = $request->input('status', 'active');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Tratamento inteligente de busca textual e codigo simples
        if ($search = $request->input('search')) {
            $cleanSearch = trim(str_replace('#', '', (string) $search));
            $isNumericCode = is_numeric($cleanSearch);

            $query->where(function ($q) use ($search, $cleanSearch, $isNumericCode) {
                if ($isNumericCode) {
                    $q->where('code', (int) $cleanSearch)
                      ->orWhere('barcode', $cleanSearch)
                      ->orWhere('sku', 'like', "%{$cleanSearch}%")
                      ->orWhere('name', 'like', "%{$search}%");
                } else {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%")
                      ->orWhere('brand', 'like', "%{$search}%");
                }
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::active()->orderBy('name')->get();

        $activeCount = Product::where('is_active', true)->count();
        $inactiveCount = Product::where('is_active', false)->count();

        return view('estoque::products.index', compact('products', 'categories', 'activeCount', 'inactiveCount'));
    }

    public function create(): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('estoque::products.create', compact('categories', 'suppliers'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $tenantId = app('currentTenant')->id;
        $userId = (int) auth()->user()->id;

        DB::transaction(function () use ($request, $tenantId, $userId) {
            $data = $request->validated();
            
            $initialStock = (float) ($data['initial_stock'] ?? 0);
            unset($data['initial_stock']);

            $data['tenant_id'] = $tenantId;

            /** @var Product $product */
            $product = Product::create($data);

            if ($initialStock > 0) {
                $this->stockService->recordEntry(
                    tenantId: $tenantId,
                    productId: $product->id,
                    quantity: $initialStock,
                    unitPrice: (float) $product->cost_price,
                    userId: $userId,
                    referenceType: null,
                    referenceId: null,
                    notes: 'Estoque inicial registrado no cadastro do produto.'
                );
            }
        });

        return redirect()
            ->route('estoque.produtos.index')
            ->with('success', 'Produto cadastrado com sucesso.');
    }

    public function edit(Product $product): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('estoque::products.edit', compact('product', 'categories', 'suppliers'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('estoque.produtos.index')
            ->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['is_active' => false]);

        return redirect()
            ->route('estoque.produtos.index')
            ->with('success', "Produto #{$product->code} desativado com sucesso.");
    }

    public function activate(Product $product): RedirectResponse
    {
        $product->update(['is_active' => true]);

        return redirect()
            ->route('estoque.produtos.index')
            ->with('success', "Produto #{$product->code} reativado com sucesso para vendas.");
    }

    /**
     * Alias de compatibilidade para rotas nomeadas de reativacao.
     */
    public function reactivate(Product $product): RedirectResponse
    {
        return $this->activate($product);
    }
}