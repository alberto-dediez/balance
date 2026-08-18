<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

class PayoutNotPendingException extends \RuntimeException
{
    public function __construct(PayoutId $id)
    {
        parent::__construct(sprintf('La liquidación %s ya está resuelta.', (string) $id), 409);
    }
}
