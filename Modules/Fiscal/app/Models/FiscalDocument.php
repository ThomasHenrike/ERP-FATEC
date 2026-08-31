<?php

declare(strict_types=1);

namespace Modules\Fiscal\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Cadastros\App\Models\Customer;
use Modules\Core\App\Traits\BelongsToTenant;
use Modules\Vendas\App\Models\Sale;

class FiscalDocument extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_TRANSMITTED = 'transmitted';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DENIED = 'denied';
    public const STATUS_ERROR = 'error';
    public const STATUS_CANCELLED = 'cancelled';

    public const MODEL_NFE = 55;
    public const MODEL_NFCE = 65;

    protected $table = 'fiscal_documents';

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'customer_id',
        'model',
        'series',
        'number',
        'status',
        'chave_acesso',
        'protocol',
        'signed_xml_path',
        'authorized_xml_path',
        'danfe_path',
        'rejection_reason',
        'observacao',
        'environment',
        'emitted_at',
        'authorized_at',
        'cancelled_at',
    ];

    protected $casts = [
        'model' => 'integer',
        'series' => 'integer',
        'number' => 'integer',
        'emitted_at' => 'datetime',
        'authorized_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FiscalDocumentItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FiscalDocumentPayment::class);
    }

    public function isAuthorized(): bool
    {
        return $this->status === self::STATUS_AUTHORIZED;
    }

    /**
     * So' pode editar/reemitir enquanto nao estiver autorizada - uma NF-e
     * autorizada e' imutavel, igual no mundo real (cancelamento/CCe sao
     * fluxos separados, ainda nao implementados).
     */
    public function isEditable(): bool
    {
        return !$this->isAuthorized();
    }
}
