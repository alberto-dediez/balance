<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Deliver;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Application\CommandBus\CommandResponse;

class DeliverDeliveryLogsResponse implements CommandResponse
{
    public function __construct(
        public readonly int $deliveredCount,
        public readonly ?SignatureRequestId $signatureRequestId = null,
    ) {}
}
