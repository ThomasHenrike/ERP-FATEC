<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Support;

/**
 * Papeis de acesso ao guard 'admin' (Admin Supremo). Independente de
 * Modules\Core\App\Support\RoleLevel, que e' o papel dentro de uma empresa
 * (guard 'web') - sao dois sistemas de permissao completamente separados.
 */
class AdminRole
{
    private const LABELS = [
        'owner' => 'Dono',
        'support' => 'Suporte',
    ];

    public static function label(string $role): string
    {
        return self::LABELS[$role] ?? $role;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return self::LABELS;
    }
}
