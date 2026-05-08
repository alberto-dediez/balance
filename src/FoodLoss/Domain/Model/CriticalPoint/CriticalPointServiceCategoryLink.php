<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;

final class CriticalPointServiceCategoryLink
{
    public function __construct(
        private CriticalPointServiceCategoryLinkId $id,
        private CriticalPoint $criticalPoint,
        private ServiceCategoryId $serviceCategoryId,
    ) {}

    public function id(): CriticalPointServiceCategoryLinkId
    {
        return $this->id;
    }

    public function criticalPoint(): CriticalPoint
    {
        return $this->criticalPoint;
    }

    public function serviceCategoryId(): ServiceCategoryId
    {
        return $this->serviceCategoryId;
    }
}
