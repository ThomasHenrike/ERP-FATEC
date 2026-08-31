<?php

declare(strict_types=1);

namespace Modules\Estoque\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\App\Traits\BelongsToTenant;

class Category extends Model
{
    use BelongsToTenant;

    protected $table = 'categories';

    protected $fillable = [
        'tenant_id',
        'parent_id',
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}