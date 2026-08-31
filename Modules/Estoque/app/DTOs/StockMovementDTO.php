<?php

declare(strict_types=1);

namespace Modules\Estoque\App\DTOs;

readonly class StockMovementDTO
{
    public function __construct(
        public int $tenantId,
        public int $productId,
        public string $type,
        public float $quantity,
        public float $unitPrice = 0.00,
        public ?int $userId = null,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public ?string $notes = null,
    ) {
        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException('A quantidade da movimentacao deve ser estritamente positiva.');
        }

        if (!in_array($this->type, ['in', 'out', 'adjustment', 'loss', 'return'], true)) {
            throw new \InvalidArgumentException("Tipo de movimentacao invalido: {$this->type}");
        }
    }
}