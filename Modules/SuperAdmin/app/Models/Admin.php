<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Conta do Admin Supremo (dono da plataforma). Deliberadamente separada
 * da tabela/guard 'users' das empresas - ver guard 'admin' em config/auth.php.
 */
class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';

    /**
     * Defaults em PHP (nao so no schema) pra evitar MissingAttributeException
     * quando algo cria um Admin sem passar role/is_active explicitamente
     * (create() nao re-busca a linha do banco pra pegar o default da coluna).
     */
    protected $attributes = [
        'role' => 'support',
        'is_active' => true,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
