<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DeliveryLog;

final class DeliveryLogAlreadyProcessedException extends \DomainException
{
    public static function alreadyDelivered(DeliveryLogId $id): self
    {
        return new self(sprintf('DeliveryLog "%s" is already delivered.', $id));
    }

    public static function alreadyInSignatureProcess(DeliveryLogId $id): self
    {
        return new self(sprintf('DeliveryLog "%s" is already in signature process.', $id));
    }
}
