<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DeliveryLog;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;

trait GetDeliveryLogFromIdTrait
{
    private function getDeliveryLogFromId(string $deliveryLogId, DeliveryLogRepository $repository): DeliveryLog
    {
        $deliveryLog = $repository->find(new DeliveryLogId($deliveryLogId));

        if (!$deliveryLog instanceof DeliveryLog) {
            throw $this->createNotFoundException('Registro de entrega no encontrado');
        }

        return $deliveryLog;
    }
}
