<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\CancelSignatureProcess;

use App\Shared\Application\CommandBus\CommandResponse;

class CancelSignatureProcessResponse implements CommandResponse
{
    public function __construct(public int $cancelledCount) {}
}
