<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Update;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\Command;

class UpdateDeliveryLogRequest implements Command
{
    public function __construct(
        public DeliveryLogId $deliveryLogId,
        public \DateTimeImmutable $date,
        public float $quantity,
        public DeliveryLogUnit $unit,
        public FoodType $productType,
        public string $batch,
        public ?RecipientOrganizationId $recipientOrganizationId = null,
        public ?PropertyId $propertyId = null,
    ) {}
}
