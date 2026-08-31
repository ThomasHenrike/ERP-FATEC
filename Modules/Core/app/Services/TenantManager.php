<?php

declare(strict_types=1);

namespace Modules\Core\App\Services;

use Modules\Core\App\Models\Tenant;

class TenantManager
{
    private ?Tenant $currentTenant = null;

    private ?string $currentRole = null;

    private const ROLE_LEVELS = [
        'operator' => 1,
        'manager' => 2,
        'admin' => 3,
    ];

    /**
     * Define o tenant ativo na requisicao atual.
     */
    public function setTenant(?Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    /**
     * Define o papel (role) do usuario dentro do tenant ativo.
     */
    public function setRole(?string $role): void
    {
        $this->currentRole = $role;
    }

    /**
     * Retorna o papel do usuario dentro do tenant ativo.
     */
    public function getRole(): ?string
    {
        return $this->currentRole;
    }

    /**
     * Verifica se o papel atual tem nivel igual ou maior que o exigido
     * (operator < manager < admin). Sem papel definido = nivel 0 (nega tudo).
     */
    public function hasRoleAtLeast(string $minRole): bool
    {
        $current = self::ROLE_LEVELS[$this->currentRole] ?? 0;
        $required = self::ROLE_LEVELS[$minRole] ?? 99;

        return $current >= $required;
    }

    /**
     * Retorna a instancia do tenant ativo.
     */
    public function getTenant(): ?Tenant
    {
        return $this->currentTenant;
    }

    /**
     * Retorna o ID do tenant ativo.
     */
    public function getTenantId(): ?int
    {
        return $this->currentTenant?->id;
    }

    /**
     * Verifica se existe um tenant ativo configurado.
     */
    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }
}