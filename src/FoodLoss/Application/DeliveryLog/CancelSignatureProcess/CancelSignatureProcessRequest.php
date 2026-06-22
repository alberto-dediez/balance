<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\CancelSignatureProcess;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Application\CommandBus\Command;

class CancelSignatureProcessRequest implements Command
{
    public function __construct(public SignatureRequestId $signatureRequestId) {}
}
