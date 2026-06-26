<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CateringItem;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;

trait GetCateringItemFromIdTrait
{
    private function getCateringItemFromId(string $cateringItemId, CateringItemRepository $repository): CateringItem
    {
        $cateringItem = $repository->find(new CateringItemId($cateringItemId));

        if (null == $cateringItem) {
            throw $this->createNotFoundException('Elemento de catering no encontrado');
        }

        return $cateringItem;
    }
}
