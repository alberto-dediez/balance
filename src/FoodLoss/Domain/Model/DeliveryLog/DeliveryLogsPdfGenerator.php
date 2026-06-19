<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DeliveryLog;

use App\FoodLoss\Domain\Model\RecipientOrganization\PickupType;

interface DeliveryLogsPdfGenerator
{
    /** @param DeliveryLog[] $deliveryLogs */
    public function generate(array $deliveryLogs, \DateTimeImmutable $deliverAt, PickupType $pickupType): string;
}
