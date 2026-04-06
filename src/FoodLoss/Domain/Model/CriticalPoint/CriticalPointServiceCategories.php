<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use Doctrine\Common\Collections\ArrayCollection;

/** @extends ArrayCollection<int, CriticalPointServiceCategoryLink> */
final class CriticalPointServiceCategories extends ArrayCollection
{
    public function removeById(ServiceCategoryId $serviceCategoryId): void
    {
        foreach ($this->toArray() as $link) {
            if ($link->serviceCategoryId()->equals($serviceCategoryId)) {
                $this->removeElement($link);
            }
        }
    }

    /** @return ServiceCategoryId[] */
    public function ids(): array
    {
        return array_map(
            fn(CriticalPointServiceCategoryLink $link): ServiceCategoryId => $link->serviceCategoryId(),
            $this->toArray(),
        );
    }

    public function contains(mixed $element): bool
    {
        if ($element instanceof ServiceCategoryId) {
            return $this->has($element);
        }

        return parent::contains($element);
    }

    public function has(ServiceCategoryId $serviceCategoryId): bool
    {
        foreach ($this->toArray() as $link) {
            if ($link->serviceCategoryId()->equals($serviceCategoryId)) {
                return true;
            }
        }

        return false;
    }
}
