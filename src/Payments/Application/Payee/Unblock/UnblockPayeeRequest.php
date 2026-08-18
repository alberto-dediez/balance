<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\Unblock;

use App\Payments\Domain\Model\Payee\PayeeId;

final class UnblockPayeeRequest
{
    public function __construct(public readonly PayeeId $payeeId) {}
}
