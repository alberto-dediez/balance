<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Deliver;

final class MixedRecipientOrganizationsException extends \InvalidArgumentException
{
    public static function forSignatureRequest(): self
    {
        return new self('All delivery logs must belong to the same recipient organization to start a signature process.');
    }
}
