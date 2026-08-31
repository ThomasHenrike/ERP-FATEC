<?php

declare(strict_types=1);

namespace Modules\Core\App\Support;

class RoleLevel
{
    public static function label(?string $role): string
    {
        return match ($role) {
            'admin' => 'Administrador',
            'manager' => 'Gerente',
            default => 'Operador',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'operator' => 'Operador',
            'manager' => 'Gerente',
            'admin' => 'Administrador',
        ];
    }
}
