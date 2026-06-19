<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Deliver;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\RecipientOrganization\PickupType;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class DeliverDeliveryLogsRequest implements Command
{
    /** @var DeliveryLogId[] */
    public array $deliveryLogIds = [];

    public bool $withSignature = false;

    public \DateTimeImmutable $deliveredAt;

    public PickupType $pickupType = PickupType::Pickup;

    public bool $otpRequired = true;

    public ?AccountId $accountId = null;
}
