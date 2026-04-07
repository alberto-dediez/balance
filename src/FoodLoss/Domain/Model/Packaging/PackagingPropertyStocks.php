<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Packaging;

use App\Property\Domain\Model\Property\PropertyId;
use Doctrine\Common\Collections\ArrayCollection;

/** @extends ArrayCollection<int, PackagingPropertyStock> */
final class PackagingPropertyStocks extends ArrayCollection
{
    public function has(PropertyId $propertyId): bool
    {
        foreach ($this->toArray() as $stock) {
            if ($stock->propertyId()->equals($propertyId)) {
                return true;
            }
        }

        return false;
    }

    public function getByPropertyId(PropertyId $propertyId): ?PackagingPropertyStock
    {
        foreach ($this->toArray() as $stock) {
            if ($stock->propertyId()->equals($propertyId)) {
                return $stock;
            }
        }

        return null;
    }

    public function removeById(PropertyId $propertyId): void
    {
        foreach ($this->toArray() as $stock) {
            if ($stock->propertyId()->equals($propertyId)) {
                $this->removeElement($stock);
            }
        }
    }
}
