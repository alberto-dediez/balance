<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\PurchaseService;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;

trait GetPurchaseServiceFromIdTrait
{
    private function getPurchaseServiceFromId(string $purchaseServiceId, PurchaseServiceRepository $repository): PurchaseService
    {
        $purchaseService = $repository->find(new PurchaseServiceId($purchaseServiceId));

        if (null == $purchaseService) {
            throw $this->createNotFoundException('Bien o servicio comprado no encontrado');
        }

        return $purchaseService;
    }
}
