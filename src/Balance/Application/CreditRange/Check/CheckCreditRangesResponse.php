<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditRange\Check;

use App\Shared\Application\CommandBus\CommandResponse;

final class CheckCreditRangesResponse implements CommandResponse
{
    /** @param list<CreditRangeFinding> $findings */
    public function __construct(
        public readonly array $findings = [],
        public readonly int $checkedLots = 0,
    ) {}

    public function hasFindings(): bool
    {
        return [] !== $this->findings;
    }
}
