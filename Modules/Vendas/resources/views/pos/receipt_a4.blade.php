<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Romaneio de Venda #{{ $sale->code }} - {{ $tenant->name ?? 'MERP' }}</title>
    <x-core::brand.favicons />
    <style>
        @page { size: A4 portrait; margin: 12mm 15mm; }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 12px; color: #1f2937; background: #ffffff; margin: 0; line-height: 1.4; }
        .action-bar-screen { position: sticky; top: 0; z-index: 50; background: #111827; color: #ffffff; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15); margin-bottom: 20px; }
        .action-bar-title { font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .action-bar-buttons { display: flex; gap: 10px; }
        .btn-action { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; border: none; }
        .btn-back { background-color: #374151; color: #ffffff; }
        .btn-back:hover { background-color: #4b5563; }
        .btn-print { background-color: #237552; color: #ffffff; }
        .btn-print:hover { background-color: #4338ca; }
        .company-header { text-align: center; border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 16px; }
        .company-logo { max-height: 60px; max-width: 200px; margin: 0 auto 8px auto; display: block; object-fit: contain; }
        .company-title { font-size: 16px; font-weight: 800; color: #111827; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .company-details { font-size: 11px; color: #4b5563; line-height: 1.35; }
        .doc-banner { text-align: center; background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; margin-bottom: 14px; }
        .doc-title { font-size: 14px; font-weight: 800; text-transform: uppercase; color: #111827; letter-spacing: 0.5px; }
        .doc-subtitle { font-size: 10px; color: #6b7280; text-transform: uppercase; margin-top: 1px; }
        .meta-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px 16px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; font-size: 11.5px; }
        .meta-item strong { color: #374151; font-size: 10.5px; text-transform: uppercase; }
        .meta-item span { color: #111827; font-weight: 600; }
        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; table-layout: fixed; }
        table.items-table thead th { background-color: #f3f4f6; border-top: 1px solid #d1d5db; border-bottom: 2px solid #9ca3af; padding: 8px 6px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: #374151; }
        table.items-table tbody td { padding: 8px 6px; border-bottom: 1px solid #e5e7eb; font-size: 11.5px; vertical-align: middle; }
        .col-code { width: 9%; text-align: center; font-family: monospace; font-weight: 700; color: #237552; }
        .col-desc { width: 43%; }
        .col-unit { width: 7%; text-align: center; color: #6b7280; }
        .col-qty  { width: 11%; text-align: center; font-weight: 700; }
        .col-unit-price { width: 15%; text-align: right; font-family: monospace; white-space: nowrap; }
        .col-total-price { width: 15%; text-align: right; font-family: monospace; font-weight: 700; white-space: nowrap; color: #111827; }
        .product-name { font-weight: 600; color: #111827; }
        .product-sku { font-size: 10px; color: #6b7280; margin-left: 4px; }
        .finance-section { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 20px; }
        .notes-area { flex: 1; background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 6px; padding: 10px 12px; font-size: 11px; min-height: 80px; }
        .notes-title { font-weight: 700; text-transform: uppercase; color: #374151; font-size: 10px; margin-bottom: 4px; }
        .totals-box { width: 320px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 14px; }
        .totals-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; color: #4b5563; }
        .totals-row.discount { color: #dc2626; font-weight: 600; }
        .totals-row.final { font-size: 15px; font-weight: 800; color: #111827; border-top: 2px solid #e5e7eb; margin-top: 4px; padding-top: 6px; }
        .totals-row.received { border-top: 1px dashed #e5e7eb; margin-top: 4px; padding-top: 4px; font-size: 11px; }
        .totals-row.change { color: #059669; font-weight: 700; font-size: 12px; }
        .signature-section { margin-top: 45px; display: flex; justify-content: center; }
        .signature-box { width: 65%; text-align: center; border-top: 1px solid #374151; padding-top: 6px; font-size: 11px; font-weight: 600; color: #374151; }
        .footer-note { text-align: center; font-size: 9.5px; color: #9ca3af; border-top: 1px solid #f3f4f6; margin-top: 24px; padding-top: 8px; }
        @media print {
            .no-print { display: none !important; }
            .action-bar-screen { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print()">

    {{-- Barra Flutuante de Controle Hibrido (A4 e Termica) --}}
    <div class="action-bar-screen no-print">
        <div class="action-bar-title">
            <span>&#128424;</span> Romaneio da Venda #{{ $sale->code }} (Formato Folha A4)
        </div>
        <div class="action-bar-buttons">
            <a href="{{ route('vendas.pos.receipt', ['sale' => $sale, 'format' => 'thermal_80']) }}" class="btn-action" style="background-color: #059669; color: #ffffff;">
                <span>&#129534;</span> Ver em Bobina Termica (80mm)
            </a>
            <button type="button" onclick="window.print()" class="btn-action btn-print">
                <span>&#128438;</span> Imprimir A4
            </button>
            <button type="button" onclick="handleReturnToPos()" class="btn-action btn-back">
                <span>&larr;</span> Voltar ao Caixa
            </button>
        </div>
    </div>

    <div class="company-header">
        @if ($tenant && $tenant->logo_url)
            <img src="{{ $tenant->logo_url }}" alt="Logo" class="company-logo">
        @endif
        
        <div class="company-title">{{ $tenant->trade_name ?? $tenant->name ?? 'ERP MODULAR' }}</div>

        <div class="company-details">
            CNPJ: <strong>{{ $tenant->document ?? 'Nao informado' }}</strong> 
            @if(!empty($tenant->state_registration)) &bull; IE: <strong>{{ $tenant->state_registration }}</strong> @endif
            <br>
            {{ $tenant->street ?? 'Endereco' }}, {{ $tenant->number ?? 'S/N' }}
            &bull; {{ $tenant->city ?? 'Cidade' }}/{{ $tenant->state ?? 'UF' }}
            @if(!empty($tenant->whatsapp) || !empty($tenant->phone))
                <br>Telefone / WhatsApp: <strong>{{ $tenant->whatsapp ?? $tenant->phone }}</strong>
            @endif
        </div>
    </div>

    <div class="doc-banner">
        <div class="doc-title">Comprovante de Venda / Romaneio de Entrega</div>
        <div class="doc-subtitle">Documento Auxiliar de Venda &bull; Sem Valor Fiscal</div>
    </div>

    <div class="meta-container">
        <div class="meta-item">
            <strong>Numero da Venda:</strong><br>
            <span style="font-size: 13px; color: #237552;">#{{ $sale->code }}</span>
        </div>
        <div class="meta-item">
            <strong>Data / Hora:</strong><br>
            <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="meta-item">
            <strong>Cliente:</strong><br>
            <span>{{ $sale->customer?->name ?? 'Consumidor Final' }}</span>
            @if(!empty($sale->customer?->document))
                <small style="color: #6b7280;">({{ $sale->customer->document }})</small>
            @endif
        </div>
        <div class="meta-item">
            <strong>Vendedor Responsavel:</strong><br>
            <span>{{ $sale->seller?->name ?? 'Venda Direta' }}</span>
        </div>
        <div class="meta-item">
            <strong>Operador de Caixa:</strong><br>
            <span>{{ $sale->user?->name ?? 'Caixa' }}</span>
        </div>
        <div class="meta-item">
            <strong>Forma de Pagamento:</strong><br>
            <span style="font-weight: 700; color: #111827;">{{ $sale->payment_method_formatted }}</span>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th class="col-code">Cod.</th>
                <th class="col-desc" align="left">Descricao do Produto</th>
                <th class="col-unit">UN</th>
                <th class="col-qty">Qtd.</th>
                <th class="col-unit-price">Preco Unit.</th>
                <th class="col-total-price">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td class="col-code">
                        #{{ $item->product?->code ?? $item->product?->id ?? $item->product_id }}
                    </td>
                    <td class="col-desc">
                        <span class="product-name">{{ $item->product?->name ?? 'Item #' . $item->product_id }}</span>
                        @if ($item->product?->sku)
                            <span class="product-sku">(SKU: {{ $item->product->sku }})</span>
                        @endif
                    </td>
                    <td class="col-unit">{{ $item->product?->unit ?? 'UN' }}</td>
                    <td class="col-qty">{{ number_format((float) $item->quantity, 3, ',', '.') }}</td>
                    <td class="col-unit-price">R$ {{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                    <td class="col-total-price">R$ {{ number_format((float) $item->total_price, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="finance-section">
        <div class="notes-area">
            <div class="notes-title">Observacoes do Pedido / Pagamento:</div>
            <div>{{ $sale->notes ?: 'Nenhuma observacao informada.' }}</div>
            
            {{-- Detalhamento de Multiplos Pagamentos se houver --}}
            @if ($sale->payments->count() > 1 || $sale->payment_method === 'on_account')
                <div style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed #d1d5db;">
                    <strong>Detalhamento Financeiro:</strong>
                    @foreach ($sale->payments as $p)
                        <div style="font-size: 10.5px; color: #4b5563;">
                            &bull; {{ $p->method_formatted }}: <strong>R$ {{ number_format((float)$p->amount, 2, ',', '.') }}</strong>
                            @if ($p->installments > 1) (em {{ $p->installments }}x) @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="totals-box">
            <div class="totals-row">
                <span>Subtotal Bruto:</span>
                <span style="font-family: monospace;">R$ {{ number_format((float) $sale->subtotal, 2, ',', '.') }}</span>
            </div>
            @if ((float) $sale->discount_amount > 0)
                <div class="totals-row discount">
                    <span>Desconto Concedido:</span>
                    <span style="font-family: monospace;">- R$ {{ number_format((float) $sale->discount_amount, 2, ',', '.') }}</span>
                </div>
            @endif
            <div class="totals-row final">
                <span>VALOR LIQUIDO:</span>
                <span style="font-family: monospace; color: #237552;">R$ {{ number_format((float) $sale->total_amount, 2, ',', '.') }}</span>
            </div>
            @if ((float) $sale->amount_received > 0 && ($sale->payment_method === 'money' || (float)$sale->change_amount > 0))
                <div class="totals-row received">
                    <span>Valor Recebido (Dinheiro):</span>
                    <span style="font-family: monospace;">R$ {{ number_format((float) $sale->amount_received, 2, ',', '.') }}</span>
                </div>
                <div class="totals-row change">
                    <span>Troco Devolvido:</span>
                    <span style="font-family: monospace;">R$ {{ number_format((float) $sale->change_amount, 2, ',', '.') }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            Assinatura do Cliente / Recebedor da Mercadoria
        </div>
    </div>

    <div class="footer-note">
        {{ $tenant->receipt_footer ?? 'Comprovante impresso via MERP. Agradecemos a preferencia!' }}
    </div>

    <script>
        function handleReturnToPos() {
            if (window.opener && !window.opener.closed) {
                window.close();
            } else {
                window.location.href = "{{ route('vendas.pos.index') }}";
            }
        }
    </script>
</body>
</html>