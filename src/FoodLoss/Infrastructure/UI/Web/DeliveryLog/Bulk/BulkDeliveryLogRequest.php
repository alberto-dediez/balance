<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DeliveryLog\Bulk;

use App\FoodLoss\Domain\Model\RecipientOrganization\PickupType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;

final class BulkDeliveryLogRequest extends BulkRequest
{
    public \DateTimeImmutable $deliveredAt;

    public ?PickupType $pickupType = null;

    public bool $otpRequired = false;
}
