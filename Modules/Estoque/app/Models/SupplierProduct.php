<?php

declare(strict_types=1);

namespace Modules\Estoque\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Traits\BelongsToTenant;

class SupplierProduct extends Model
{
    // Isolamento por tenant para o mapeamento de produtos de fornecedores
    use BelongsToTenant;

    protected $table = 'supplier_products';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'supplier_cnpj',
        'supplier_name',
        'supplier_product_code',
        'supplier_product_name',
    ];

    /**
     * Produto interno correspondente ao codigo do fornecedor.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}