<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Fechamento de Caixa #{{ $shift->id }} - {{ $tenant->name ?? 'MERP' }}</title>
    <x-core::brand.favicons />
    <style>
        @page { 
            size: A4 portrait; 
            margin: 12mm 15mm; 
        }
        * {
            box-sizing: border-box;
        }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
            font-size: 12px; 
            color: #1f2937; 
            background: #ffffff; 
            margin: 0; 
            line-height: 1.4; 
        }

        /* Barra de Acoes Flutuante na Tela (Oculta na Impressao) */
        .action-bar-screen {
            position: sticky;
            top: 0;
            z-index: 50;
            background: #111827;
            color: #ffffff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            margin-bottom: 20px;
        }
        .action-bar-title {
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .action-bar-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: opacity 0.2s;
        }
        .btn-back { background-color: #374151; color: #ffffff; }
        .btn-back:hover { background-color: #4b5563; }
        .btn-print { background-color: #237552; color: #ffffff; }
        .btn-print:hover { background-color: #4338ca; }

        /* Cabecalho Centralizado */
        .company-header {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .company-logo {
            max-height: 55px;
            max-width: 200px;
            margin: 0 auto 6px auto;
            display: block;
            object-fit: contain;
        }
        .company-title {
            font-size: 16px;
            font-weight: 800;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .company-details {
            font-size: 11px;
            color: #4b5563;
        }

        .doc-banner {
            text-align: center;
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 16px;
        }
        .doc-title {
            font-size: 13.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #111827;
            letter-spacing: 0.5px;
        }

        /* Grades de Informacoes */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-bottom: 16px;
        }
        .info-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 14px;
            font-size: 11.5px;
        }
        .card-header {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        .row-detail {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 11.5px;
        }
        .row-detail.total {
            border-top: 1.5px solid #d1d5db;
            margin-top: 6px;
            padding-top: 6px;
            font-size: 12.5px;
            font-weight: 800;
            color: #111827;
        }
        .val-money {
            font-family: monospace;
            white-space: nowrap;
            font-weight: 700;
        }

        /* Conciliacao de Caixa */
        .diff-box {
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            font-weight: 800;
            font-size: 12px;
            margin-top: 10px;
        }
        .diff-ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .diff-sobra { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .diff-quebra { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* Assinaturas */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 10px;
        }
        .sig-line {
            width: 45%;
            border-top: 1px solid #374151;
            text-align: center;
            font-size: 11px;
            color: #374151;
            padding-top: 4px;
            font-weight: 600;
        }

        @media print {
            .no-print { display: none !important; }
            .action-bar-screen { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print()">

    {{-- Barra Flutuante Superior na Tela (Oculta na Impressao) --}}
    <div class="action-bar-screen no-print">
        <div class="action-bar-title">
            <span>&#128181;</span> Relatorio de Fechamento do Turno #{{ $shift->id }}
        </div>
        <div class="action-bar-buttons">
            <button type="button" onclick="handleReturnToPos()" class="btn-action btn-back">
                <span>&larr;</span> Voltar ao Caixa (PDV)
            </button>
            <button type="button" onclick="window.print()" class="btn-action btn-print">
                <span>&#128438;</span> Imprimir Novamente
            </button>
        </div>
    </div>

    {{-- 1. Cabecalho Centralizado --}}
    <div class="company-header">
        @if ($tenant && $tenant->logo_url)
            <img src="{{ $tenant->logo_url }}" alt="Logo" class="company-logo">
        @endif
        <div class="company-title">{{ $tenant->trade_name ?? $tenant->name ?? 'ERP MODULAR' }}</div>
        <div class="company-details">
            CNPJ: <strong>{{ $tenant->document ?? 'Nao informado' }}</strong>
            &bull; {{ $tenant->city ?? 'Cidade' }}/{{ $tenant->state ?? 'UF' }}
            @if(!empty($tenant->whatsapp) || !empty($tenant->phone))
                &bull; Contato: <strong>{{ $tenant->whatsapp ?? $tenant->phone }}</strong>
            @endif
        </div>
    </div>

    {{-- 2. Banner do Documento --}}
    <div class="doc-banner">
        <div class="doc-title">Relatorio Consolidado de Fechamento de Turno</div>
    </div>

    {{-- 3. Grade Principal: Identificacao e Movimentacao de Gaveta --}}
    <div class="grid-2">
        <div class="info-card">
            <div class="card-header">Identificacao do Turno</div>
            <div class="row-detail"><span>Numero do Turno:</span><strong>#{{ $shift->id }}</strong></div>
            <div class="row-detail"><span>Operador de Caixa:</span><span>{{ $shift->user->name }}</span></div>
            <div class="row-detail"><span>Data/Hora de Abertura:</span><span>{{ $shift->opened_at->format('d/m/Y H:i') }}</span></div>
            <div class="row-detail"><span>Data/Hora de Fechamento:</span><span>{{ $shift->closed_at?->format('d/m/Y H:i') ?? 'Em Aberto' }}</span></div>
            <div class="row-detail"><span>Status:</span><strong style="color: #059669;">ENCERRADO</strong></div>
        </div>

        <div class="info-card">
            <div class="card-header">Apuracao da Gaveta Fisica</div>
            <div class="row-detail"><span>Fundo de Troco Inicial:</span><span class="val-money">R$ {{ number_format((float)$summary['opening_balance'], 2, ',', '.') }}</span></div>
            <div class="row-detail"><span>Vendas em Dinheiro (+):</span><span class="val-money">R$ {{ number_format((float)$summary['cash_sales'], 2, ',', '.') }}</span></div>
            <div class="row-detail"><span>Suprimentos de Gaveta (+):</span><span class="val-money">R$ {{ number_format((float)$summary['total_inflows'], 2, ',', '.') }}</span></div>
            <div class="row-detail" style="color: #dc2626;"><span>Sangrias / Retiradas (-):</span><span class="val-money">- R$ {{ number_format((float)$summary['total_outflows'], 2, ',', '.') }}</span></div>
            <div class="row-detail total">
                <span>SALDO ESPERADO NA GAVETA:</span>
                <span class="val-money" style="color: #237552;">R$ {{ number_format((float)$summary['expected_drawer_cash'], 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- 4. Faturamento Geral por Modalidade --}}
    <div class="info-card" style="margin-bottom: 16px;">
        <div class="card-header">Totalizacao por Forma de Pagamento</div>
        <div class="row-detail"><span>Vendas em Dinheiro:</span><span class="val-money">R$ {{ number_format((float)$summary['cash_sales'], 2, ',', '.') }}</span></div>
        <div class="row-detail"><span>Vendas em PIX:</span><span class="val-money">R$ {{ number_format((float)$summary['pix_sales'], 2, ',', '.') }}</span></div>
        <div class="row-detail"><span>Vendas em Cartoes (Debito/Credito):</span><span class="val-money">R$ {{ number_format((float)$summary['card_sales'], 2, ',', '.') }}</span></div>
        <div class="row-detail"><span>Vendas A Prazo (Crediario):</span><span class="val-money">R$ {{ number_format((float)$summary['on_account_sales'], 2, ',', '.') }}</span></div>
        <div class="row-detail total" style="color: #059669; font-size: 13.5px;">
            <span>FATURAMENTO TOTAL DO TURNO:</span>
            <span class="val-money">R$ {{ number_format((float)$summary['total_sales'], 2, ',', '.') }}</span>
        </div>
    </div>

    {{-- 5. Conciliacao Contabil Final --}}
    <div class="info-card">
        <div class="card-header">Conferencia Fisica da Gaveta</div>
        <div class="row-detail"><span>Valor Informado pelo Operador (Contado):</span><span class="val-money" style="font-size: 13px;">R$ {{ number_format((float)$shift->closing_balance, 2, ',', '.') }}</span></div>
        <div class="row-detail"><span>Saldo Esperado Calculado pelo Sistema:</span><span class="val-money">R$ {{ number_format((float)$shift->expected_balance, 2, ',', '.') }}</span></div>

        @if ((float)$shift->difference_amount == 0)
            <div class="diff-box diff-ok">CONCILIACAO EXATA (Diferenca: R$ 0,00)</div>
        @elseif ((float)$shift->difference_amount > 0)
            <div class="diff-box diff-sobra">SOBRA DE CAIXA (+ R$ {{ number_format((float)$shift->difference_amount, 2, ',', '.') }})</div>
        @else
            <div class="diff-box diff-quebra">QUEBRA / FALTA DE CAIXA (- R$ {{ number_format(abs((float)$shift->difference_amount), 2, ',', '.') }})</div>
        @endif
    </div>

    @if ($shift->notes)
        <div style="margin-top: 14px; font-size: 11px; padding: 8px 12px; background: #fafafa; border: 1px dashed #d1d5db; border-radius: 6px;">
            <strong>Observacoes do Fechamento:</strong><br>{{ $shift->notes }}
        </div>
    @endif

    {{-- 6. Assinaturas --}}
    <div class="signatures">
        <div class="sig-line">Assinatura do Operador de Caixa</div>
        <div class="sig-line">Assinatura do Gerente / Conferencia</div>
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