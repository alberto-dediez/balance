<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MerchandisingItem;

use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;

trait GetMerchandisingItemFromIdTrait
{
    private function getMerchandisingItemFromId(string $merchandisingItemId, MerchandisingItemRepository $repository): MerchandisingItem
    {
        $merchandisingItem = $repository->find(new MerchandisingItemId($merchandisingItemId));

        if (null == $merchandisingItem) {
            throw $this->createNotFoundException('Artículo de merchandising no encontrado');
        }

        return $merchandisingItem;
    }
}
