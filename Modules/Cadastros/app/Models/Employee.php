<?php

declare(strict_types=1);

namespace Modules\Cadastros\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\App\Traits\BelongsToTenant;

class Employee extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'document',
        'email',
        'phone',
        'whatsapp',
        'role',
        'commission_percentage',
        'hire_date',
        'is_active',
    ];

    protected $casts = [
        'commission_percentage' => 'decimal:2',
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Escopo para filtrar apenas colaboradores habilitados para vendas e comissionamento.
     */
    public function scopeSellers(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereIn('role', ['vendedor', 'gerente', 'caixa']);
    }

    /**
     * Se o colaborador tem acesso de login ao sistema (user_id vinculado).
     */
    public function hasSystemAccess(): bool
    {
        return $this->user_id !== null;
    }
}