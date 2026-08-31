<?php

declare(strict_types=1);

namespace Modules\Fiscal\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Traits\BelongsToTenant;

/**
 * Representacao de pagamento da NF-e - independente do SalePayment real,
 * pode divergir (ex: venda paga em dinheiro mas nota emitida como boleto
 * parcelado, se for esse o caso fiscal correto).
 */
class FiscalDocumentPayment extends Model
{
    use BelongsToTenant;

    protected $table = 'fiscal_document_payments';

    protected $fillable = [
        'tenant_id',
        'fiscal_document_id',
        'forma_pagamento',
        'numero_parcela',
        'data_vencimento',
        'valor',
    ];

    protected $casts = [
        'numero_parcela' => 'integer',
        'data_vencimento' => 'date',
        'valor' => 'decimal:2',
    ];

    public function fiscalDocument(): BelongsTo
    {
        return $this->belongsTo(FiscalDocument::class);
    }
}
