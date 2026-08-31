<?php

declare(strict_types=1);

namespace Modules\Fiscal\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Modules\Fiscal\App\Models\FiscalDocument;
use Modules\Fiscal\App\Models\FiscalDocumentItem;
use Modules\Fiscal\App\Models\FiscalDocumentPayment;
use Modules\Fiscal\App\Services\NFeEmissionService;
use Modules\Fiscal\App\Support\InstallmentSplitter;
use Modules\Fiscal\App\Support\PaymentMethodMapper;
use Modules\Vendas\App\Models\Sale;

class FiscalDocumentController extends Controller
{
    public function __construct(
        private readonly NFeEmissionService $emissionService
    ) {}

    public function index(): View
    {
        $documents = FiscalDocument::query()
            ->with(['sale', 'customer'])
            ->latest('id')
            ->paginate(15);

        return view('fiscal::documents.index', compact('documents'));
    }

    public function show(FiscalDocument $fiscalDocument): View
    {
        $fiscalDocument->load(['sale', 'customer']);

        return view('fiscal::documents.show', compact('fiscalDocument'));
    }

    /**
     * Cria (ou reaproveita) um rascunho de NF-e a partir de uma venda e
     * leva pra tela de revisao - nao emite nada ainda, so' copia os dados
     * da venda pra uma versao propria e editavel do documento fiscal.
     */
    public function start(Sale $sale): RedirectResponse
    {
        $existing = FiscalDocument::where('sale_id', $sale->id)
            ->where('status', '!=', FiscalDocument::STATUS_AUTHORIZED)
            ->latest('id')
            ->first();

        if ($existing) {
            return redirect()->route('fiscal.documents.edit', $existing);
        }

        /** @var Tenant $tenant */
        $tenant = app('currentTenant');
        $sale->loadMissing(['items', 'payments']);

        $fiscalDocument = DB::transaction(function () use ($sale, $tenant) {
            $doc = FiscalDocument::create([
                'tenant_id' => $tenant->id,
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'model' => FiscalDocument::MODEL_NFE,
                'series' => $tenant->nfe_series,
                'number' => null,
                'status' => FiscalDocument::STATUS_DRAFT,
                'environment' => $tenant->sefaz_environment,
                'observacao' => $tenant->fiscal_default_observation,
            ]);

            foreach ($sale->items as $item) {
                FiscalDocumentItem::create([
                    'tenant_id' => $tenant->id,
                    'fiscal_document_id' => $doc->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'total_price' => $item->total_price,
                    'ncm' => $item->ncm,
                    'cest' => $item->cest,
                    'origin' => $item->origin,
                    'cfop' => $item->cfop,
                    'csosn' => $item->csosn,
                    'cst_icms' => $item->cst_icms,
                    'cst_pis' => $item->cst_pis,
                    'cst_cofins' => $item->cst_cofins,
                    'icms_rate' => $item->icms_rate,
                    'pis_rate' => $item->pis_rate,
                    'cofins_rate' => $item->cofins_rate,
                ]);
            }

            FiscalDocumentPayment::create([
                'tenant_id' => $tenant->id,
                'fiscal_document_id' => $doc->id,
                'forma_pagamento' => $this->guessFormaPagamento($sale->payment_method),
                'valor' => (float) $sale->total_amount,
            ]);

            return $doc;
        });

        return redirect()->route('fiscal.documents.edit', $fiscalDocument);
    }

    public function edit(FiscalDocument $fiscalDocument): View
    {
        abort_if(!$fiscalDocument->isEditable(), 403, 'Esta NF-e ja foi autorizada e nao pode mais ser editada.');

        $fiscalDocument->load(['items.product', 'payments', 'customer', 'sale']);

        $products = Product::active()->orderBy('name')->get([
            'id', 'code', 'name', 'sku', 'unit',
            'ncm', 'cest', 'origin', 'cfop_default', 'csosn', 'cst_icms', 'cst_pis', 'cst_cofins',
            'icms_rate', 'pis_rate', 'cofins_rate',
            'price_retail', 'price_resale', 'price_wholesale',
        ]);

        return view('fiscal::documents.edit', [
            'fiscalDocument' => $fiscalDocument,
            'products' => $products,
            'paymentMethods' => PaymentMethodMapper::LABELS,
            'paymentState' => $this->derivePaymentState($fiscalDocument),
        ]);
    }

    /**
     * Reconstroi os campos de "numero de parcelas / primeiro vencimento /
     * dias entre vencimentos" a partir das linhas ja salvas em
     * fiscal_document_payments, pra pre-preencher a tela de revisao com o
     * que ja foi configurado antes (ou um padrao razoavel na primeira vez).
     */
    private function derivePaymentState(FiscalDocument $fiscalDocument): array
    {
        $payments = $fiscalDocument->payments->sortBy('numero_parcela')->values();
        $first = $payments->first();

        $forma = $first->forma_pagamento ?? 'money';

        if ($forma !== 'boleto' || $payments->count() < 1 || !$first->data_vencimento) {
            return [
                'forma' => $forma,
                'numeroParcelas' => max(1, $payments->count()),
                'dataPrimeiroVencimento' => now()->format('Y-m-d'),
                'diasEntreVencimentos' => 30,
            ];
        }

        $dias = 30;
        if ($payments->count() > 1 && $payments[1]->data_vencimento) {
            $dias = (int) $first->data_vencimento->diffInDays($payments[1]->data_vencimento);
        }

        return [
            'forma' => 'boleto',
            'numeroParcelas' => $payments->count(),
            'dataPrimeiroVencimento' => $first->data_vencimento->format('Y-m-d'),
            'diasEntreVencimentos' => $dias ?: 30,
        ];
    }

    public function update(Request $request, FiscalDocument $fiscalDocument): RedirectResponse
    {
        abort_if(!$fiscalDocument->isEditable(), 403, 'Esta NF-e ja foi autorizada e nao pode mais ser editada.');

        $validated = $request->validate([
            'observacao' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_price' => ['required', 'numeric', 'min:0'],
            'items.*.ncm' => ['nullable', 'string', 'max:8'],
            'items.*.cest' => ['nullable', 'string', 'max:7'],
            'items.*.origin' => ['nullable', 'integer', 'between:0,8'],
            'items.*.cfop' => ['nullable', 'string', 'max:4'],
            'items.*.csosn' => ['nullable', 'string', 'max:4'],
            'items.*.cst_icms' => ['nullable', 'string', 'max:3'],
            'items.*.cst_pis' => ['nullable', 'string', 'max:2'],
            'items.*.cst_cofins' => ['nullable', 'string', 'max:2'],
            'items.*.icms_rate' => ['nullable', 'numeric', 'between:0,100'],
            'items.*.pis_rate' => ['nullable', 'numeric', 'between:0,100'],
            'items.*.cofins_rate' => ['nullable', 'numeric', 'between:0,100'],
            'forma_pagamento' => ['required', 'string', 'in:money,pix,credit_card,debit_card,boleto,other'],
            'numero_parcelas' => ['required_if:forma_pagamento,boleto', 'nullable', 'integer', 'min:1', 'max:99'],
            'data_primeiro_vencimento' => ['required_if:forma_pagamento,boleto', 'nullable', 'date'],
            'dias_entre_vencimentos' => ['required_if:forma_pagamento,boleto', 'nullable', 'integer', 'min:1', 'max:180'],
        ]);

        DB::transaction(function () use ($fiscalDocument, $validated) {
            $fiscalDocument->update(['observacao' => $validated['observacao'] ?? null]);

            $fiscalDocument->items()->delete();
            foreach ($validated['items'] as $item) {
                FiscalDocumentItem::create([
                    'tenant_id' => $fiscalDocument->tenant_id,
                    'fiscal_document_id' => $fiscalDocument->id,
                    ...$item,
                ]);
            }

            $total = round(
                collect($validated['items'])->sum('total_price') - collect($validated['items'])->sum(fn ($i) => $i['discount_amount'] ?? 0),
                2
            );

            $fiscalDocument->payments()->delete();

            if ($validated['forma_pagamento'] === 'boleto') {
                $parcelas = InstallmentSplitter::split(
                    $total,
                    (int) $validated['numero_parcelas'],
                    Carbon::parse($validated['data_primeiro_vencimento']),
                    (int) $validated['dias_entre_vencimentos']
                );

                foreach ($parcelas as $parcela) {
                    FiscalDocumentPayment::create([
                        'tenant_id' => $fiscalDocument->tenant_id,
                        'fiscal_document_id' => $fiscalDocument->id,
                        'forma_pagamento' => 'boleto',
                        'numero_parcela' => $parcela['numero'],
                        'data_vencimento' => $parcela['data_vencimento'],
                        'valor' => $parcela['valor'],
                    ]);
                }
            } else {
                FiscalDocumentPayment::create([
                    'tenant_id' => $fiscalDocument->tenant_id,
                    'fiscal_document_id' => $fiscalDocument->id,
                    'forma_pagamento' => $validated['forma_pagamento'],
                    'valor' => $total,
                ]);
            }
        });

        if ($request->input('action') === 'emit') {
            $fiscalDocument = $this->emissionService->emit($fiscalDocument->fresh());

            return match ($fiscalDocument->status) {
                FiscalDocument::STATUS_AUTHORIZED => redirect()
                    ->route('fiscal.documents.show', $fiscalDocument)
                    ->with('success', "NF-e #{$fiscalDocument->number} autorizada com sucesso."),
                FiscalDocument::STATUS_REJECTED => redirect()
                    ->route('fiscal.documents.show', $fiscalDocument)
                    ->with('error', "NF-e #{$fiscalDocument->number} rejeitada pela SEFAZ: {$fiscalDocument->rejection_reason}"),
                default => redirect()
                    ->route('fiscal.documents.show', $fiscalDocument)
                    ->with('error', "Nao foi possivel concluir a emissao da NF-e: {$fiscalDocument->rejection_reason}"),
            };
        }

        return redirect()->route('fiscal.documents.edit', $fiscalDocument)->with('success', 'Alteracoes salvas.');
    }

    /**
     * Exclui um rascunho (nunca uma nota autorizada) - pra quando algo deu
     * errado na revisao e o operador precisa emitir outra pra mesma venda
     * com urgencia. So' apaga o registro local: NAO devolve/reaproveita o
     * numero ja alocado (se houver) pra serie - o numero fica "queimado" de
     * proposito, pra nunca existirem duas notas com o mesmo numero. Isso e'
     * inofensivo: um numero nunca transmitido a SEFAZ nao tem nenhuma
     * obrigacao associada, so' fica pulado na sequencia local.
     */
    public function destroy(FiscalDocument $fiscalDocument): RedirectResponse
    {
        abort_if(!$fiscalDocument->isEditable(), 403, 'Esta NF-e ja foi autorizada e nao pode mais ser excluida.');

        $sale = $fiscalDocument->sale;
        $fiscalDocument->delete();

        return $sale
            ? redirect()->route('vendas.sales.index')->with('success', 'Rascunho de NF-e excluido. Pode emitir uma nova a qualquer momento - o numero ja usado neste rascunho nao sera reaproveitado.')
            : redirect()->route('fiscal.documents.index')->with('success', 'Rascunho de NF-e excluido.');
    }

    public function downloadXml(FiscalDocument $fiscalDocument): Response
    {
        $path = $fiscalDocument->authorized_xml_path ?? $fiscalDocument->signed_xml_path;

        abort_if(empty($path) || !Storage::disk('fiscal')->exists($path), 404, 'XML nao disponivel.');

        return response(Storage::disk('fiscal')->get($path), 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="nfe-'.$fiscalDocument->number.'.xml"',
        ]);
    }

    /**
     * Chute inicial da forma de pagamento da NF-e a partir do metodo real
     * da venda - fica livre pra revisar/trocar na tela de revisao antes
     * de emitir (ex: virar boleto parcelado).
     */
    private function guessFormaPagamento(string $salePaymentMethod): string
    {
        return match ($salePaymentMethod) {
            'money', 'pix', 'credit_card', 'debit_card' => $salePaymentMethod,
            'on_account' => 'boleto',
            default => 'other',
        };
    }
}
