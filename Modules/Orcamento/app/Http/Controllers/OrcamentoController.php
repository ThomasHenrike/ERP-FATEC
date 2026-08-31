<?php

declare(strict_types=1);

namespace Modules\Orcamento\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Cadastros\App\Models\Customer;
use Modules\Estoque\App\Models\Product;
use Modules\Orcamento\App\Models\Orcamento;
use Modules\Orcamento\App\Services\OrcamentoService;

class OrcamentoController extends Controller
{
    public function __construct(
        private readonly OrcamentoService $orcamentoService
    ) {}

    public function index(Request $request): View
    {
        $query = Orcamento::query()->with('customer')->latest('id');

        $status = $request->input('status');
        if ($status) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $cleanSearch = trim(str_replace('#', '', (string) $search));
            $query->where(function ($q) use ($search, $cleanSearch) {
                $q->where('code', $cleanSearch)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $orcamentos = $query->paginate(15)->withQueryString();
        $statusCounts = Orcamento::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('orcamento::index', compact('orcamentos', 'statusCounts'));
    }

    public function create(): View
    {
        return view('orcamento::create', [
            'orcamento' => null,
            'customers' => $this->customerOptions(),
            'products' => $this->productOptions(),
            'tenant' => app('currentTenant'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateOrcamento($request);

        $orcamento = $this->orcamentoService->createOrcamento([
            'tenant_id' => app('currentTenant')->id,
            'user_id' => $request->user()->id,
            'customer_id' => $validated['customer_id'],
            'status' => Orcamento::STATUS_DRAFT,
            'payment_condition' => $validated['payment_condition'] ?? null,
            'delivery_term' => $validated['delivery_term'] ?? null,
            'freight_info' => $validated['freight_info'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], $validated['items']);

        return redirect()->route('orcamentos.show', $orcamento)
            ->with('success', "Orcamento #{$orcamento->code} criado com sucesso.");
    }

    public function show(Orcamento $orcamento): View
    {
        $orcamento->load(['customer', 'items.product', 'user']);

        return view('orcamento::show', [
            'orcamento' => $orcamento,
            'tenant' => app('currentTenant'),
        ]);
    }

    public function edit(Orcamento $orcamento): View
    {
        $orcamento->load('items.product');

        return view('orcamento::edit', [
            'orcamento' => $orcamento,
            'customers' => $this->customerOptions(),
            'products' => $this->productOptions(),
            'tenant' => app('currentTenant'),
        ]);
    }

    public function update(Request $request, Orcamento $orcamento): RedirectResponse
    {
        $validated = $this->validateOrcamento($request);

        $this->orcamentoService->updateOrcamento($orcamento, [
            'customer_id' => $validated['customer_id'],
            'payment_condition' => $validated['payment_condition'] ?? null,
            'delivery_term' => $validated['delivery_term'] ?? null,
            'freight_info' => $validated['freight_info'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], $validated['items']);

        return redirect()->route('orcamentos.show', $orcamento)
            ->with('success', "Orcamento #{$orcamento->code} atualizado com sucesso.");
    }

    public function destroy(Orcamento $orcamento): RedirectResponse
    {
        $orcamento->delete();

        return redirect()->route('orcamentos.index')
            ->with('success', "Orcamento #{$orcamento->code} cancelado com sucesso.");
    }

    public function send(Orcamento $orcamento): RedirectResponse
    {
        $orcamento->update(['status' => Orcamento::STATUS_SENT]);

        return redirect()->route('orcamentos.show', $orcamento)
            ->with('success', 'Orcamento marcado como enviado.');
    }

    public function approve(Orcamento $orcamento): RedirectResponse
    {
        $orcamento->update(['status' => Orcamento::STATUS_APPROVED]);

        return redirect()->route('orcamentos.show', $orcamento)
            ->with('success', 'Orcamento aprovado.');
    }

    public function reject(Orcamento $orcamento): RedirectResponse
    {
        $orcamento->update(['status' => Orcamento::STATUS_REJECTED]);

        return redirect()->route('orcamentos.show', $orcamento)
            ->with('success', 'Orcamento rejeitado.');
    }

    public function convert(Orcamento $orcamento): RedirectResponse
    {
        if ($orcamento->isConverted()) {
            return redirect()->route('orcamentos.show', $orcamento)
                ->with('error', 'Este orcamento ja foi convertido em venda.');
        }

        return redirect()->route('vendas.pos.index', ['from_quote' => $orcamento->id]);
    }

    private function customerOptions()
    {
        return Customer::active()->orderBy('name')->get(['id', 'name', 'document', 'default_price_table', 'whatsapp']);
    }

    private function productOptions()
    {
        return Product::active()->orderBy('name')->get([
            'id', 'code', 'name', 'sku', 'unit', 'ncm', 'icms_rate',
            'price_retail', 'price_resale', 'price_wholesale',
        ]);
    }

    private function validateOrcamento(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'payment_condition' => ['nullable', 'string', 'max:255'],
            'delivery_term' => ['nullable', 'string', 'max:255'],
            'freight_info' => ['nullable', 'string', 'max:255'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.total_price' => ['required', 'numeric', 'min:0'],
            'items.*.icms_percent' => ['nullable', 'numeric', 'between:0,100'],
            'items.*.icms_st_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.lead_time_days' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
