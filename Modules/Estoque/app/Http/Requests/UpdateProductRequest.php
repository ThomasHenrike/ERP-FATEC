<?php

declare(strict_types=1);

namespace Modules\Estoque\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Estoque\App\Models\Product;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app('currentTenant')->id;
        
        /** @var Product|int $product */
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->id : (int) $product;

        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->where('tenant_id', $tenantId)->ignore($productId)],
            'barcode' => ['nullable', 'string', 'max:14', Rule::unique('products', 'barcode')->where('tenant_id', $tenantId)->ignore($productId)],
            'unit' => ['required', 'string', 'max:6'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'margin_retail' => ['nullable', 'numeric', 'min:0'],
            'price_retail' => ['required', 'numeric', 'min:0'],
            'margin_resale' => ['nullable', 'numeric', 'min:0'],
            'price_resale' => ['nullable', 'numeric', 'min:0'],
            'margin_wholesale' => ['nullable', 'numeric', 'min:0'],
            'price_wholesale' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'ncm' => ['required', 'string', 'size:8'],
            'cest' => ['nullable', 'string', 'max:7'],
            'origin' => ['required', 'integer', 'between:0,8'],
            'cfop_default' => ['required', 'string', 'size:4'],
            'csosn' => ['nullable', 'string', 'max:4'],
            'cst_icms' => ['nullable', 'string', 'max:3'],
            'cst_pis' => ['required', 'string', 'max:2'],
            'cst_cofins' => ['required', 'string', 'max:2'],
            'icms_rate' => ['nullable', 'numeric', 'between:0,100'],
            'pis_rate' => ['nullable', 'numeric', 'between:0,100'],
            'cofins_rate' => ['nullable', 'numeric', 'between:0,100'],
            'is_active' => ['boolean'],
        ];
    }
}