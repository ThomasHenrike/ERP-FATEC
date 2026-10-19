<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orcamento #{{ $orcamento->code }} - {{ $tenant->name ?? 'MERP' }}</title>
    <x-core::brand.favicons />
    <style>
        @page { size: A4 portrait; margin: 10mm 12mm; }
        * { box-sizing: border-box; }
        html { background: #6b7280; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 10px; color: #1f2937; background: transparent; margin: 0; line-height: 1.35; }
        .paper { max-width: 210mm; margin: 0 auto; background: #ffffff; padding: 8mm 10mm; }
        .action-bar-screen { position: sticky; top: 0; z-index: 50; background: #111827; color: #ffffff; padding: 10px 20px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .action-bar-title { font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .action-bar-buttons { display: flex; flex-wrap: wrap; gap: 8px; }
        .btn-action { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; border: none; }
        .btn-back { background-color: #374151; color: #ffffff; }
        .btn-print { background-color: #237552; color: #ffffff; }
        .btn-whatsapp { background-color: #059669; color: #ffffff; }
        .btn-approve { background-color: #059669; color: #ffffff; }
        .btn-reject { background-color: #dc2626; color: #ffffff; }
        .btn-convert { background-color: #f59e0b; color: #111827; }
        .company-header { text-align: center; border-bottom: 1.5px solid #111827; padding-bottom: 8px; margin-bottom: 10px; }
        .company-logo { max-height: 42px; max-width: 160px; margin: 0 auto 6px auto; display: block; object-fit: contain; }
        .company-title { font-size: 13px; font-weight: 800; color: #111827; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 2px; }
        .company-details { font-size: 9px; color: #4b5563; line-height: 1.3; }
        .doc-banner { text-align: center; background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 4px; padding: 5px; margin-bottom: 10px; }
        .doc-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #111827; letter-spacing: 0.3px; }
        .doc-subtitle { font-size: 8.5px; color: #6b7280; text-transform: uppercase; margin-top: 1px; }
        .info-box { border: 1px solid #e5e7eb; border-radius: 4px; margin-bottom: 8px; overflow: hidden; }
        .info-box-title { background-color: #ea580c; color: #ffffff; font-weight: 800; font-size: 8.5px; text-transform: uppercase; padding: 4px 8px; }
        .info-box-body { padding: 6px 10px; font-size: 9px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 3px 14px; }
        .info-box-body strong { color: #374151; }
        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.items-table thead th { background-color: #f3f4f6; border-top: 1px solid #d1d5db; border-bottom: 2px solid #9ca3af; padding: 4px 3px; font-size: 8px; font-weight: 700; text-transform: uppercase; color: #374151; }
        table.items-table tbody td { padding: 4px 3px; border-bottom: 1px solid #e5e7eb; font-size: 9px; vertical-align: middle; }
        .col-item { width: 4%; text-align: center; }
        .col-code { text-align: center; font-family: monospace; font-weight: 700; color: #237552; }
        .col-desc { text-align: left; }
        .col-num { text-align: center; }
        .col-money { text-align: right; font-family: monospace; white-space: nowrap; }
        .totals-box { width: 260px; margin-left: auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 12px; margin-bottom: 12px; }
        .totals-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 9.5px; color: #4b5563; }
        .totals-row.final { font-size: 12px; font-weight: 800; color: #111827; border-top: 2px solid #e5e7eb; margin-top: 3px; padding-top: 5px; }
        .terms-title { font-weight: 800; text-transform: uppercase; font-size: 9px; color: #111827; margin-bottom: 4px; }
        .terms-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .terms-table td { padding: 4px 3px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .terms-table td:first-child { width: 120px; font-weight: 700; color: #374151; }
        .footer-note { text-align: center; font-size: 8px; color: #9ca3af; border-top: 1px solid #f3f4f6; margin-top: 16px; padding-top: 6px; }
        @media print {
            .no-print { display: none !important; }
            html { background: #ffffff; }
            .paper { max-width: none; padding: 0; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @media screen and (max-width: 480px) {
            .paper { padding: 5mm 4mm; }
            body { font-size: 11px; }
            .company-title { font-size: 15px; }
            .company-details { font-size: 10.5px; }
            .doc-title { font-size: 13px; }
            .doc-subtitle { font-size: 9.5px; }
            .info-box-body { grid-template-columns: 1fr; font-size: 10.5px; gap: 4px; }
            .items-table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0 -4mm 10px; padding: 0 4mm; }
            table.items-table { min-width: 640px; margin-bottom: 0; }
            table.items-table thead th, table.items-table tbody td { font-size: 10px; }
            .totals-box { width: 100%; }
            .terms-table td:first-child { width: 100px; }
        }
    </style>
</head>
<body>

    {{-- Barra de Acoes (nao aparece na impressao) --}}
    <div class="action-bar-screen no-print">
        <div class="action-bar-title">
            <span>&#128196;</span> Proposta Comercial #{{ $orcamento->code }} &bull; {{ $orcamento->status_label }}
        </div>
        <div class="action-bar-buttons">
            @if ($orcamento->customer->whatsapp)
                <a href="https://wa.me/55{{ preg_replace('/\D/', '', $orcamento->customer->whatsapp) }}?text={{ urlencode("Ola {$orcamento->customer->name}, segue nossa proposta comercial #{$orcamento->code} no valor de R$ " . number_format((float) $orcamento->total_amount, 2, ',', '.') . ". Qualquer duvida estou a disposicao!") }}"
                   target="_blank" rel="noopener" class="btn-action btn-whatsapp">Enviar por WhatsApp</a>
            @endif

            @if ($orcamento->status === 'draft')
                <form action="{{ route('orcamentos.send', $orcamento) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn-action" style="background-color:#2563eb;color:#fff;">Marcar como Enviado</button>
                </form>
            @endif

            @if (in_array($orcamento->status, ['draft', 'sent']))
                <form action="{{ route('orcamentos.approve', $orcamento) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn-action btn-approve">Aprovar</button>
                </form>
                <form action="{{ route('orcamentos.reject', $orcamento) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn-action btn-reject">Rejeitar</button>
                </form>
            @endif

            @if ($orcamento->status === 'approved')
                <form action="{{ route('orcamentos.convert', $orcamento) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn-action btn-convert">Converter em Venda</button>
                </form>
            @endif

            <button type="button" onclick="window.print()" class="btn-action btn-print">Imprimir</button>
            <a href="{{ route('orcamentos.index') }}" class="btn-action btn-back">Voltar</a>
        </div>
    </div>

    <div class="paper">
    <div class="company-header">
        @if ($tenant && $tenant->logo_url)
            <img src="{{ $tenant->logo_url }}" alt="Logo" class="company-logo">
        @endif

        <div class="company-title">{{ $tenant->trade_name ?? $tenant->name ?? 'ERP MODULAR' }}</div>

        <div class="company-details">
            CNPJ: <strong>{{ $tenant->document ?? 'Nao informado' }}</strong>
            @if (!empty($tenant->state_registration)) &bull; Insc. Estadual: <strong>{{ $tenant->state_registration }}</strong> @endif
            @if (!empty($tenant->phone) || !empty($tenant->whatsapp)) &bull; Tel: <strong>{{ $tenant->phone ?? $tenant->whatsapp }}</strong> @endif
        </div>
    </div>

    <div class="doc-banner">
        <div class="doc-title">Proposta Comercial</div>
        <div class="doc-subtitle">Orcamento de Venda #{{ $orcamento->code }} &bull; Data: {{ $orcamento->created_at->format('d/m/Y') }}</div>
    </div>

    <div class="info-box">
        <div class="info-box-title">Informacoes do Cliente</div>
        <div class="info-box-body">
            <div><strong>Cliente:</strong> {{ $orcamento->customer->name }}</div>
            <div><strong>Cod. Cliente:</strong> {{ $orcamento->customer->id }}</div>
            <div><strong>{{ $orcamento->customer->document_label }}:</strong> {{ $orcamento->customer->document ?: 'Nao informado' }}</div>
            <div><strong>Endereco:</strong> {{ $orcamento->customer->street ?: 'Nao informado' }}{{ $orcamento->customer->number ? ', ' . $orcamento->customer->number : '' }}</div>
            <div><strong>Bairro:</strong> {{ $orcamento->customer->neighborhood ?: '-' }}</div>
            <div><strong>Cidade:</strong> {{ $orcamento->customer->city ? $orcamento->customer->city . '/' . $orcamento->customer->state : '-' }}</div>
            <div><strong>CEP:</strong> {{ $orcamento->customer->zip_code ?: '-' }}</div>
            <div><strong>Telefone:</strong> {{ $orcamento->customer->whatsapp ?? $orcamento->customer->phone ?? '-' }}</div>
            <div><strong>Email:</strong> {{ $orcamento->customer->email ?: '-' }}</div>
        </div>
    </div>

    <div class="items-table-scroll">
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-item">Item</th>
                <th class="col-code">Codigo</th>
                <th class="col-desc">Descricao</th>
                <th class="col-num">Qtd.</th>
                <th class="col-num">UN</th>
                <th class="col-money">Vlr. Unit.</th>
                <th class="col-money">Vlr. Total</th>
                @if ($tenant->quote_show_ncm) <th class="col-num">NCM</th> @endif
                @if ($tenant->quote_show_icms) <th class="col-num">ICMS</th> @endif
                @if ($tenant->quote_show_icms_st) <th class="col-money">Vlr. ST</th> @endif
                @if ($tenant->quote_show_lead_time) <th class="col-num">Faturamento em</th> @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($orcamento->items as $index => $item)
                <tr>
                    <td class="col-item">{{ $index + 1 }}</td>
                    <td class="col-code">{{ $item->product?->code ?? $item->product_id }}</td>
                    <td class="col-desc">{{ $item->product?->name ?? 'Produto removido' }}</td>
                    <td class="col-num">{{ number_format((float) $item->quantity, 3, ',', '.') }}</td>
                    <td class="col-num">{{ $item->product?->unit ?? 'UN' }}</td>
                    <td class="col-money">{{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                    <td class="col-money">{{ number_format((float) $item->total_price, 2, ',', '.') }}</td>
                    @if ($tenant->quote_show_ncm) <td class="col-num">{{ $item->product?->ncm ?? '-' }}</td> @endif
                    @if ($tenant->quote_show_icms) <td class="col-num">{{ number_format((float) $item->icms_percent, 0) }}%</td> @endif
                    @if ($tenant->quote_show_icms_st) <td class="col-money">{{ number_format((float) $item->icms_st_value, 2, ',', '.') }}</td> @endif
                    @if ($tenant->quote_show_lead_time) <td class="col-num">{{ $item->lead_time_days ? $item->lead_time_days . ' Dias' : '-' }}</td> @endif
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>

    <div class="totals-box">
        <div class="totals-row">
            <span>Total sem ICMS/ST:</span>
            <span>R$ {{ number_format((float) $orcamento->subtotal, 2, ',', '.') }}</span>
        </div>
        @if ($tenant->quote_show_icms_st)
            <div class="totals-row">
                <span>Total ICMS/ST:</span>
                <span>R$ {{ number_format((float) $orcamento->total_icms_st, 2, ',', '.') }}</span>
            </div>
        @endif
        <div class="totals-row final">
            <span>Total com ICMS/ST:</span>
            <span>R$ {{ number_format((float) $orcamento->total_amount, 2, ',', '.') }}</span>
        </div>
    </div>

    <div class="info-box">
        <div class="info-box-title" style="background-color:#374151;">Informacoes Gerais</div>
        <div style="padding: 10px 12px;">
            <div class="terms-title">Condicoes Comerciais</div>
            <table class="terms-table">
                <tr>
                    <td>Preco e tributos:</td>
                    <td>Os precos indicados nesta proposta sao validos somente para o fechamento total da proposta, assim como as condicoes e caracteristicas especificadas, levando em consideracao a atual politica economica vigente no pais. Quaisquer mudancas nesta politica que impliquem em alteracao real do valor ofertado, face ao prazo de validade da proposta, condicoes de pagamento ou da forma de entrega, o mesmo sera revisado e adaptado a fim que seja mantido o equilibrio economico e financeiro da presente proposta.</td>
                </tr>
                @if ($tenant->quote_show_payment_condition && $orcamento->payment_condition)
                    <tr>
                        <td>Condicoes de pagamento:</td>
                        <td>{{ $orcamento->payment_condition }}</td>
                    </tr>
                @endif
                @if ($tenant->quote_show_delivery_term && $orcamento->delivery_term)
                    <tr>
                        <td>Prazo de Entrega:</td>
                        <td>{{ $orcamento->delivery_term }}</td>
                    </tr>
                @endif
                @if ($tenant->quote_show_freight && $orcamento->freight_info)
                    <tr>
                        <td>Frete:</td>
                        <td>{{ $orcamento->freight_info }}</td>
                    </tr>
                @endif
                <tr>
                    <td>Validade:</td>
                    <td>{{ $orcamento->valid_until ? 'Esta proposta e valida ate ' . $orcamento->valid_until->format('d/m/Y') . '.' : 'Consulte-nos para confirmacao da validade.' }}</td>
                </tr>
                @if ($orcamento->notes)
                    <tr>
                        <td>Observacoes:</td>
                        <td>{{ $orcamento->notes }}</td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

    <div class="footer-note">
        Documento gerado via MERP &bull; Vendedor Responsavel: {{ $orcamento->user->name ?? 'Sistema' }}
    </div>
    </div>
</body>
</html>
