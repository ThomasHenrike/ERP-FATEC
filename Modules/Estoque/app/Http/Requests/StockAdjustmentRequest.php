<?php

declare(strict_types=1);

namespace Modules\Estoque\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Estoque\App\Models\StockMovement;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('currentTenant')->id;

        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'type' => [
                'required',
                'string',
                Rule::in([
                    StockMovement::TYPE_IN,
                    StockMovement::TYPE_OUT,
                    StockMovement::TYPE_ADJUSTMENT,
                    StockMovement::TYPE_LOSS,
                ]),
            ],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}