<?php

declare(strict_types=1);

namespace Modules\Estoque\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Estoque\App\DTOs\StockMovementDTO;
use Modules\Estoque\App\Models\Product;
use Modules\Estoque\App\Models\StockMovement;

class StockMovementService
{
/**
     * Executa a movimentacao de estoque com bloqueio pessimista (lockForUpdate).
     */
    public function execute(StockMovementDTO $dto): StockMovement
    {
        return DB::transaction(function () use ($dto): StockMovement {
            /** @var Product $product */
            $product = Product::where('tenant_id', $dto->tenantId)
                ->where('id', $dto->productId)
                ->lockForUpdate()
                ->firstOrFail();

            $previousStock = (float) $product->current_stock;
            $newStock = match ($dto->type) {
                StockMovement::TYPE_IN, StockMovement::TYPE_RETURN => $previousStock + $dto->quantity,
                StockMovement::TYPE_OUT, StockMovement::TYPE_LOSS => $previousStock - $dto->quantity,
                StockMovement::TYPE_ADJUSTMENT => $dto->quantity,
            };

            // Criacao do registro imutavel de auditoria (Kardex)
            $movement = StockMovement::create([
                'tenant_id' => $dto->tenantId,
                'product_id' => $product->id,
                'user_id' => $dto->userId,
                'type' => $dto->type,
                'quantity' => $dto->quantity,
                'unit_price' => $dto->unitPrice > 0 ? $dto->unitPrice : (float) $product->cost_price,
                'previous_stock' => $previousStock,
                'current_stock' => $newStock,
                'reference_type' => $dto->referenceType,
                'reference_id' => $dto->referenceId,
                'notes' => $dto->notes,
            ]);

            // Atualizacao direta da propriedade para contornar a protecao de Mass Assignment
            $product->current_stock = $newStock;
            $product->save();

            return $movement;
        });
    }

    /**
     * Helper para registro de entrada de mercadorias.
     */
    public function recordEntry(
        int $tenantId,
        int $productId,
        float $quantity,
        float $unitPrice,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): StockMovement {
        return $this->execute(new StockMovementDTO(
            tenantId: $tenantId,
            productId: $productId,
            type: StockMovement::TYPE_IN,
            quantity: $quantity,
            unitPrice: $unitPrice,
            userId: $userId,
            referenceType: $referenceType,
            referenceId: $referenceId,
            notes: $notes
        ));
    }

    /**
     * Helper para registro de saida ou venda PDV.
     */
    public function recordExit(
        int $tenantId,
        int $productId,
        float $quantity,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): StockMovement {
        return $this->execute(new StockMovementDTO(
            tenantId: $tenantId,
            productId: $productId,
            type: StockMovement::TYPE_OUT,
            quantity: $quantity,
            userId: $userId,
            referenceType: $referenceType,
            referenceId: $referenceId,
            notes: $notes
        ));
    }
}