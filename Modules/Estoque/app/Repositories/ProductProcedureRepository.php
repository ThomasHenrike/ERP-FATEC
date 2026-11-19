<?php

declare(strict_types=1);

namespace Modules\Estoque\App\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\App\Services\TenantManager;

/**
 * Acesso as Stored Procedures de produto (wiki/Stored-Procedures.md). So' funciona no MySQL/MariaDB.
 *
 * O tenant_id vem sempre da empresa ativa - nunca de parametro do chamador - e todos os
 * valores vao como binding, nunca interpolados no SQL.
 */
class ProductProcedureRepository
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    public function findById(int $productId): ?object
    {
        return $this->call('sp_produto_por_id', [$productId])[0] ?? null;
    }

    public function searchForPos(string $term): array
    {
        return $this->call('sp_pdv_buscar_produto', [$term]);
    }

    public function byCategory(int $categoryId): array
    {
        return $this->call('sp_produtos_por_categoria', [$categoryId]);
    }

    public function byPriceRange(float $min, float $max, string $priceTable = 'retail'): array
    {
        return $this->call('sp_produtos_por_faixa_preco', [$min, $max, $priceTable]);
    }

    public function listOrdered(string $field = 'nome', string $direction = 'ASC'): array
    {
        return $this->call('sp_listar_produtos_ordenados', [$field, $direction]);
    }

    public function belowMinimumStock(): array
    {
        return $this->call('sp_produtos_abaixo_estoque_minimo');
    }

    public function stockLedger(int $productId, string $startDate, string $endDate): array
    {
        return $this->call('sp_kardex_produto', [$productId, $startDate, $endDate]);
    }

    public function create(
        ?int $categoryId,
        string $name,
        string $unit,
        string $ncm,
        float $costPrice,
        float $priceRetail,
        float $minStock = 0.0
    ): object {
        return $this->call('sp_inserir_produto', [
            $categoryId, $name, $unit, $ncm, $costPrice, $priceRetail, $minStock,
        ])[0];
    }

    public function updateRetailPrice(int $productId, float $priceRetail): object
    {
        return $this->call('sp_atualizar_preco_produto', [$productId, $priceRetail])[0];
    }

    public function softDelete(int $productId): object
    {
        return $this->call('sp_excluir_produto', [$productId])[0];
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
