<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\Unblock;

use App\Payments\Domain\Model\Payee\PayeeStatus;

final class UnblockPayeeResponse
{
    public function __construct(public readonly PayeeStatus $status) {}
}
