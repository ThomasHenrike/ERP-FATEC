<?php

declare(strict_types=1);

namespace Modules\Estoque\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Estoque\App\DTOs\StockMovementDTO;
use Modules\Estoque\App\Http\Requests\StockAdjustmentRequest;
use Modules\Estoque\App\Models\Product;
use Modules\Estoque\App\Models\StockMovement;
use Modules\Estoque\App\Services\StockMovementService;

class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockMovementService $stockService
    ) {}

    public function index(Request $request): View
    {
        $query = StockMovement::query()
            ->with(['product' => fn($q) => $q->withTrashed(), 'user', 'reference'])
            ->latest('id');

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $movements = $query->paginate(20)->withQueryString();
        $products = Product::withTrashed()->orderBy('name')->get();

        return view('estoque::movements.index', compact('movements', 'products'));
    }

    public function create(): View
    {
        $products = Product::active()->orderBy('name')->get();

        return view('estoque::movements.create', compact('products'));
    }

    public function store(StockAdjustmentRequest $request): RedirectResponse
    {
        $tenantId = app('currentTenant')->id;
        $userId = (int) $request->user()->id;

        $dto = new StockMovementDTO(
            tenantId: $tenantId,
            productId: (int) $request->input('product_id'),
            type: (string) $request->input('type'),
            quantity: (float) $request->input('quantity'),
            unitPrice: (float) ($request->input('unit_price') ?? 0.00),
            userId: $userId,
            referenceType: null,
            referenceId: null,
            notes: $request->input('notes')
        );

        $this->stockService->execute($dto);

        return redirect()
            ->route('estoque.movimentacoes.index')
            ->with('success', 'Movimentacao de estoque registrada com sucesso no Kardex.');
    }
}