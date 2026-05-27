<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Register;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterDeliveryLogRequest implements Command
{
    public function __construct(
        public ?\DateTimeImmutable $date = null,
        public float $quantity = 0.0,
        public DeliveryLogUnit $unit = DeliveryLogUnit::Kg,
        public FoodType $productType = FoodType::Others,
        public string $batch = '',
        public ?RecipientOrganizationId $recipientOrganizationId = null,
        public ?PropertyId $propertyId = null,
        public ?AccountId $accountId = null,
        public ?SurplusId $surplusId = null,
    ) {
        if (!$this->date instanceof \DateTimeImmutable) {
            $this->date = new \DateTimeImmutable('NOW');
        }
    }
}
