<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cupom #{{ $sale->code }} - {{ $tenant->name ?? 'MERP' }}</title>
    <x-core::brand.favicons />
    <style>
        @page { margin: 0; }
        body { font-family: monospace; font-size: 12px; width: 80mm; margin: 0; padding: 10px; color: #000; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .border-b { border-bottom: 1px dashed #000; margin: 5px 0; }
        .bold { font-weight: bold; }
        table { width: 100%; font-size: 11px; border-collapse: collapse; }
        td, th { padding: 2px 0; }

        .action-bar-screen {
            background: #111827;
            color: #ffffff;
            padding: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 4px;
            margin-bottom: 12px;
            gap: 6px;
        }
        .btn-action {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-back { background: #4b5563; color: #fff; }
        .btn-print { background: #237552; color: #fff; }

        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

    {{-- Barra Flutuante de Controle Hibrido (Termica e A4) --}}
    <div class="action-bar-screen no-print">
        <button type="button" onclick="handleReturnToPos()" class="btn-action btn-back">&larr; Caixa</button>
        <a href="{{ route('vendas.pos.receipt', ['sale' => $sale, 'format' => 'a4']) }}" class="btn-action" style="background-color: #237552; color: #fff; text-decoration: none;">
            Ver em A4
        </a>
        <button type="button" onclick="window.print()" class="btn-action btn-print">&#128438; Imprimir</button>
    </div>

    <div class="text-center bold">
        <div>{{ $tenant->trade_name ?? $tenant->name ?? 'ERP MODULAR' }}</div>
        <div style="font-size: 10px;">CNPJ: {{ $tenant->document ?? 'Nao informado' }}</div>
        <div>CUPOM DE VENDA / ROMANEIO</div>
    </div>
    <div class="border-b"></div>
    <div>Venda: #{{ $sale->code }}</div>
    <div>Data: {{ $sale->created_at->format('d/m/Y H:i:s') }}</div>
    <div>Operador: {{ $sale->user?->name ?? 'Caixa' }}</div>
    <div>Vendedor: {{ $sale->seller?->name ?? 'Venda Direta' }}</div>
    <div>Cliente: {{ $sale->customer?->name ?? 'Consumidor Final' }}</div>
    <div>Pagamento: {{ $sale->payment_method_formatted }}</div>
    <div class="border-b"></div>
    <table>
        <thead>
            <tr>
                <th align="left">Item</th>
                <th align="center">Qtd</th>
                <th align="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td colspan="3">{{ $item->product?->name ?? 'Item #' . $item->product_id }}</td>
                </tr>
                <tr>
                    <td style="font-size: 9px; color: #555;">Cod: #{{ $item->product?->code ?? $item->product_id }}</td>
                    <td align="center">{{ number_format((float) $item->quantity, 3, ',', '.') }}</td>
                    <td align="right">R$ {{ number_format((float) $item->total_price, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="border-b"></div>
    <div class="text-right">Subtotal: R$ {{ number_format((float) $sale->subtotal, 2, ',', '.') }}</div>
    @if ((float) $sale->discount_amount > 0)
        <div class="text-right">Desconto: - R$ {{ number_format((float) $sale->discount_amount, 2, ',', '.') }}</div>
    @endif
    <div class="text-right bold" style="font-size: 14px;">TOTAL: R$ {{ number_format((float) $sale->total_amount, 2, ',', '.') }}</div>
    
    @if ((float) $sale->amount_received > 0 && ($sale->payment_method === 'money' || (float)$sale->change_amount > 0))
        <div class="text-right">Recebido: R$ {{ number_format((float) $sale->amount_received, 2, ',', '.') }}</div>
        <div class="text-right bold">Troco: R$ {{ number_format((float) $sale->change_amount, 2, ',', '.') }}</div>
    @endif

    @if ($sale->notes)
        <div class="border-b"></div>
        <div class="bold">Observacoes:</div>
        <div>{{ $sale->notes }}</div>
    @endif

    <div class="border-b"></div>
    <div class="text-center" style="margin-top: 10px; font-size: 10px;">
        {{ $tenant->receipt_footer ?? 'Documento Sem Valor Fiscal - Obrigado pela preferencia!' }}
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