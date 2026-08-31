<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Cadastros\App\Models\Customer;
use Modules\Cadastros\App\Models\Employee;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Modules\Orcamento\App\Models\Orcamento;
use Modules\Vendas\App\Models\CashShift;
use Modules\Vendas\App\Models\Sale;
use Modules\Vendas\App\Services\SaleService;

class PosController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService
    ) {}

    public function index(Request $request): View
    {
        $tenantId = app('currentTenant')->id;
        $userId = (int) auth()->user()->id;

        $products = Product::active()
            ->select([
                'id', 'code', 'name', 'sku', 'barcode', 'unit',
                'cost_price', 'price_retail', 'price_resale', 'price_wholesale',
                'current_stock', 'min_stock'
            ])
            ->orderBy('name')
            ->get();

        $customers = Customer::active()->orderBy('name')->get(['id', 'name', 'document', 'credit_limit', 'default_price_table']);
        $sellers = Employee::sellers()->get(['id', 'name', 'role', 'commission_percentage']);

        // Turno de caixa e' por empresa (uma gaveta fisica), nao por operador -
        // qualquer computador/login precisa ver e vender no mesmo turno aberto.
        $activeShift = CashShift::with('user:id,name')
            ->where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->first();

        /** @var Tenant $tenant */
        $tenant = app('currentTenant');

        [$prefillCart, $prefillQuoteId, $prefillCustomerId, $prefillPriceTable] = $this->buildQuotePrefill($request);

        return view('vendas::pos.index', compact(
            'products', 'customers', 'sellers', 'tenant', 'activeShift',
            'prefillCart', 'prefillQuoteId', 'prefillCustomerId', 'prefillPriceTable'
        ));
    }

    /**
     * Monta o carrinho inicial do PDV a partir de um orcamento aprovado (?from_quote=).
     */
    private function buildQuotePrefill(Request $request): array
    {
        $quoteId = $request->query('from_quote');
        if (!$quoteId) {
            return [[], null, null, null];
        }

        $orcamento = Orcamento::with('items.product')->find($quoteId);
        if (!$orcamento || $orcamento->isConverted()) {
            return [[], null, null, null];
        }

        $cart = $orcamento->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'code' => $item->product?->code ?? $item->product_id,
            'name' => $item->product?->name ?? 'Produto removido',
            'sku' => $item->product?->sku ?? 'N/A',
            'unit' => $item->product?->unit ?? 'UN',
            'unit_price' => (float) $item->unit_price,
            'quantity' => (float) $item->quantity,
            'total_price' => (float) $item->total_price,
        ])->values()->all();

        return [$cart, $orcamento->id, $orcamento->customer_id, null];
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer'],
            'seller_id' => ['nullable', 'integer'],
            'quote_id' => ['nullable', 'integer', 'exists:orcamentos,id'],
            'price_table' => ['required', 'string', 'in:retail,resale,wholesale'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'between:0,100'],
            'discount_fixed' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_price' => ['required', 'numeric', 'min:0'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'string', 'in:money,pix,credit_card,debit_card,on_account'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.installments' => ['nullable', 'integer', 'min:1'],
            'payments.*.first_due_date' => ['nullable', 'date'],
        ]);

        $tenantId = app('currentTenant')->id;
        $userId = (int) $request->user()->id;

        $activeShift = CashShift::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->first();

        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'O caixa esta fechado. Abra um turno antes de registrar vendas.',
            ], 422);
        }

        $hasOnAccount = collect($validated['payments'])->contains('method', 'on_account');
        if ($hasOnAccount && empty($validated['customer_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Para pagamentos A Prazo, selecione um cliente cadastrado.',
            ], 422);
        }

        /** @var Tenant|null $tenant */
        $tenant = app('currentTenant') ?? Tenant::find($tenantId);

        // Validação de vendedor obrigatório posicionada antes da transação de venda
        if ($tenant && $tenant->require_seller_on_pos && empty($validated['seller_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'A configuracao da empresa exige selecionar um Vendedor responsavel para concluir a venda.',
            ], 422);
        }

        $saleData = [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'cash_shift_id' => $activeShift->id,
            'customer_id' => $validated['customer_id'] ?? null,
            'seller_id' => $validated['seller_id'] ?? null,
            'price_table' => $validated['price_table'],
            'subtotal' => (float) $validated['subtotal'],
            'discount_percent' => (float) ($validated['discount_percent'] ?? 0),
            'discount_fixed' => (float) ($validated['discount_fixed'] ?? 0),
            'notes' => $validated['notes'] ?? null,
        ];

        $sale = $this->saleService->executeSale($saleData, $validated['items'], $validated['payments']);

        if (!empty($validated['quote_id'])) {
            $orcamento = Orcamento::find($validated['quote_id']);
            if ($orcamento && !$orcamento->isConverted()) {
                $orcamento->update(['status' => Orcamento::STATUS_CONVERTED, 'converted_sale_id' => $sale->id]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Venda #{$sale->code} finalizada com sucesso.",
            'sale_id' => $sale->id,
            'sale_code' => $sale->code,
            'total' => (float) $sale->total_amount,
            'discount' => (float) $sale->discount_amount,
            'amount_received' => (float) $sale->amount_received,
            'change' => (float) $sale->change_amount,
        ]);
    }

    public function quickCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tenantId = app('currentTenant')->id;
        $validated['tenant_id'] = $tenantId;
        $validated['credit_limit'] = (float) ($validated['credit_limit'] ?? 500.00);

        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'customer' => $customer,
        ]);
    }

    public function receipt(Request $request, Sale $sale): View
    {
        $sale->load(['items.product' => fn($q) => $q->withTrashed(), 'user', 'customer', 'seller', 'payments']);
        
        /** @var Tenant $tenant */
        $tenant = app('currentTenant') ?? Tenant::find($sale->tenant_id);

        $requestedFormat = $request->query('format');
        
        if ($requestedFormat === 'a4') {
            $view = 'vendas::pos.receipt_a4';
        } elseif ($requestedFormat === 'thermal_80' || $requestedFormat === 'thermal_58') {
            $view = 'vendas::pos.receipt';
        } else {
            $view = ($tenant && $tenant->print_format === 'a4') 
                ? 'vendas::pos.receipt_a4' 
                : 'vendas::pos.receipt';
        }

        return view($view, compact('sale', 'tenant'));
    }
}