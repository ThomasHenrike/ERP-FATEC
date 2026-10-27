<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\App\Services\TenantManager;

/**
 * Acesso as Stored Procedures de consulta de vendas (wiki/StoredProcedures.md). So' funciona no
 * MySQL/MariaDB. O tenant_id vem sempre da empresa ativa e os valores vao como binding.
 */
class SaleProcedureRepository
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    public function salesByPeriod(string $startDate, string $endDate): ?object
    {
        return $this->call('sp_vendas_por_periodo', [$startDate, $endDate])[0] ?? null;
    }

    public function topSellingProducts(string $startDate, string $endDate, int $limit = 10): array
    {
        return $this->call('sp_produtos_mais_vendidos', [$startDate, $endDate, $limit]);
    }

    public function sellerCommissions(string $startDate, string $endDate): array
    {
        return $this->call('sp_comissao_vendedores', [$startDate, $endDate]);
    }

    public function cashShiftSummary(int $cashShiftId): ?object
    {
        return $this->call('sp_resumo_caixa', [$cashShiftId])[0] ?? null;
    }

    public function openReceivables(int $customerId): array
    {
        return $this->call('sp_contas_receber_cliente', [$customerId]);
    }

    /**
     * Executa CALL procedure(tenant_id, ...parametros) com um placeholder por valor.
     */
    private function call(string $procedure, array $parameters = []): array
    {
        $tenantId = $this->tenantManager->getTenantId()
            ?? throw new \RuntimeException('Nenhuma empresa ativa para executar a procedure.');

        $bindings = [$tenantId, ...$parameters];
        $placeholders = implode(', ', array_fill(0, count($bindings), '?'));

        return DB::select("CALL {$procedure}({$placeholders})", $bindings);
    }
}
