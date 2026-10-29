<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditRange\Check;

use App\Shared\Application\CommandBus\Command;

final class CheckCreditRangesRequest implements Command
{
    public function __construct(
        public readonly bool $repair = false,
    ) {}
}
