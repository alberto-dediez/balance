<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Packaging;

use App\Property\Domain\Model\Property\PropertyId;

final class PackagingPropertyStock
{
    public function __construct(
        private PackagingPropertyStockId $id,
        private Packaging $packaging,
        private PropertyId $propertyId,
        private int $stock = 0,
    ) {}

    public function id(): PackagingPropertyStockId
    {
        return $this->id;
    }

    public function packaging(): Packaging
    {
        return $this->packaging;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function stock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }
}
